# Installation

Holdout runs on your **home network**, not in the cloud. In a power or internet outage, a cloud server is out of reach exactly when you need your supply list; a server at home keeps working as long as it has power.

The reference setup is a Synology DiskStation with Portainer, but any always-on machine with Docker works (x86-64; other architectures are not built yet).

## Overview

| Part | Purpose |
|---|---|
| Holdout container | The app: web server and PHP, SQLite database in a data folder |
| Data folder | `holdout.sqlite` (all data) and `app.key` (created on first start) |
| Nginx Proxy Manager (recommended) | HTTPS with a real certificate, needed for the camera barcode scanner and for installing the app on phones |
| Local DNS entry | Lets a name like `holdout.example.org` point to the server on your network |

There is no sign-in. Everyone who can reach the app on your network can use it; each device asks once "Who are you?" (see [Family](Family.md)). **Never forward a port to Holdout from the internet.**

## 1. Prepare the server

Create a folder for the data, e.g. `/volume1/docker/holdout` (on a DiskStation: File Station, inside the shared folder `docker`). You do not need to change its owner or permissions.

The container starts as root only to prepare this folder, then runs PHP and the web server workers unprivileged. With `PUID` and `PGID` (like linuxserver.io images) the app runs as a user of your server, so the database belongs to that user:

| Variable | Default | Meaning |
|---|---|---|
| `PUID` | `1026` | User ID that owns the data. On a Synology NAS, the first user created in DSM usually has 1026. |
| `PGID` | `100` | Group ID. On a Synology NAS, the group `users` is 100. |

To check the IDs of a DSM user, run `id <username>` via SSH. Choose a user who may write to the shared folder.

If the app user still cannot write to the folder, the container stops with a message. Then pick a `PUID` with write access, or give that folder read/write for **Everyone** (DSM: File Station → the folder → Properties → Permission).

Create the Docker network `proxy` once. Holdout and Nginx Proxy Manager share it (Portainer → Networks → Add network, or):

```bash
docker network create proxy
```

## 2. Start Holdout as a Portainer stack

1. Portainer → Stacks → Add stack → **Repository**.
2. Repository URL: this project's GitHub URL; compose path: `deploy/docker-compose.portainer.yml`.
3. Turn on **GitOps updates** with polling (e.g. every 15 minutes) and **Re-pull image**. Portainer then installs new versions by itself; GitHub cannot reach your home network, so it has to ask.
4. Environment variables:

| Variable | Required | Description |
|---|---|---|
| `APP_IMAGE` | yes | The image GitHub Actions publishes, e.g. `ghcr.io/<github-owner>/holdout` |
| `HOLDOUT_PORT` | no | Host port for direct access, default `8090` |
| `HOLDOUT_DATA` | no | Data folder on the host, default `/volume1/docker/holdout` |
| `PUID`, `PGID` | no | Owner of the data, default `1026` / `100` (see step 1) |
| `APP_TAG` | no | Image tag, default `latest`; set to an older `sha-…` tag to roll back |
| `OPENFOODFACTS_ENABLED` | no | `false` turns the barcode lookup in Open Food Facts off; default `true` |
| `OPENFOODFACTS_IMAGE_TIMEOUT` | no | Seconds to wait for a product photo, default `15` (the image server is slower than the API) |
| `HOLDOUT_SOURCE_URL` | no | Link to your copy of the source code; added to the identification Holdout sends to Open Food Facts |

5. Deploy. Holdout is now reachable at `http://<server-ip>:8090`.

On the first start the container creates the database and an app key in the data folder and runs the database migrations. Open the address and **create your household** right away.

The direct address over plain HTTP stays available on purpose: it is the emergency access if the HTTPS certificate ever expires (see below). Without HTTPS, everything works except the camera scanner and installing the app on phones.

## 3. HTTPS with Nginx Proxy Manager (recommended)

Browsers allow the camera and installable apps (service workers) only on HTTPS pages. Public certificate authorities do not issue certificates for private IP addresses or names like `holdout.lan`, so Holdout needs a **real domain name that points to your server on the local network**. The app is still not reachable from the internet.

You need a domain whose DNS is hosted by a provider with an API (for example Cloudflare, Hetzner DNS or INWX), so the certificate can be issued with a DNS challenge, without opening any port.

1. **Run Nginx Proxy Manager on the same machine as Holdout**, so both have the same power supply. On a DiskStation, DSM already uses ports 80 and 443: map NPM to other ports, e.g. `8443:443` and `81:81` (admin), and attach it to the `proxy` network. (Alternative: give NPM its own IP address with a macvlan network; containers on macvlan cannot reach their host's IP, so then address Holdout by container name only.)
2. In NPM, request a Let's Encrypt certificate for `holdout.<your-domain>` with the **DNS challenge** of your DNS provider.
3. Add a proxy host: domain `holdout.<your-domain>`, forward to `holdout` port `8080` (the container name on the `proxy` network), with the certificate from step 2.
4. In your router (or Pi-hole), add a **local DNS entry**: `holdout.<your-domain>` → IP address of the server. On a FRITZ!Box, also add the domain as an exception to the DNS rebind protection.
5. Open `https://holdout.<your-domain>:8443` on a phone and add it to the home screen.

Holdout trusts the `X-Forwarded-*` headers from the proxy, so all links use HTTPS.

### Certificates and long outages

Let's Encrypt certificates are valid for 90 days and NPM renews them about 30 days before they expire, which needs internet. Without internet, the certificate therefore stays valid for at least about 30 more days. If it expires, use the direct address `http://<server-ip>:8090` until the internet is back.

## Emergency readiness

Holdout is meant to help when things go wrong, so plan for power as well:

- **UPS:** connect the server to a UPS and let it shut down cleanly when the battery runs low. Enable "restart automatically after a power failure" (on a DiskStation: Control Panel → Hardware & Power).
- **Network:** put the **router and the Wi-Fi access point** on the UPS too. Otherwise the server keeps running but no phone can reach it.
- **Backup power:** with a PV system in island mode or a power station, the server and network can run for days (together they typically draw 50–60 W). The UPS then bridges the switchover. Test it by switching off the grid supply.
- **Phones:** keep power banks in your supply.
- **Paper:** keep a current [printout](Printout.md) in your emergency folder. When the server is down, paper still works.

## Backups

All data is in the data folder: `holdout.sqlite`, `app.key` and the product photos (`product-images/`, `off-images/`).

Before every update that changes the database structure, the container saves a copy of the database to `backups/` in the data folder (`holdout-<date>-<time>.sqlite`) and keeps the newest 10 (change with `HOLDOUT_BACKUP_KEEP`). To go back, stop the stack, replace `holdout.sqlite` with a copy and start an image version from before the update.

This does not replace a real backup: back up the whole data folder with your usual tool, e.g. Btrfs snapshots and Hyper Backup on a DiskStation, ideally also to an external drive. To restore, put both files back and start the container.

## Updates and rollback

Every push to `main` builds a new `latest` image; a weekly rebuild picks up PHP security patches. Portainer pulls new images through its GitOps polling. To roll back, set `APP_TAG` to an older `sha-…` tag and redeploy the stack. Database migrations are not rolled back automatically, so restore a backup if a rollback crosses a migration.
