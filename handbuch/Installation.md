# Installation

Holdout runs as a Docker image on a server. GitHub Actions builds the image and pushes it to the GitHub Container Registry (GHCR); the server only pulls finished images. The reference setup is [Coolify](https://coolify.io) with a separate PostgreSQL database, but any Docker Compose host works.

> **In an emergency, Holdout works offline.** The live app is for managing the supply in normal times. When the power or the internet is gone, the [paper printout](Printout.md) in your emergency folder and the [offline copy](Offline.md) on every phone are what count. Keep both up to date.

## Requirements

- A PostgreSQL database (in Coolify: a separate PostgreSQL resource)
- An SMTP account for sending email (sign-in links and invitations)
- A domain with HTTPS. Passkeys, the camera scanner and the offline copy only work over HTTPS, on the exact domain set in `APP_URL`.

## Services

`deploy/docker-compose.coolify.yml` defines two containers from the same image:

| Service | Purpose |
|---|---|
| `app` | Web server (Nginx + PHP-FPM) on port 8080. Runs database migrations and syncs the categories on every start. |
| `worker` | Queue worker. Sends emails (sign-in links, invitations) in the background. |

Product photos are stored on the `app-storage` volume. The database is not part of the compose file; both containers reach it over the external `coolify` network.

## Environment variables

| Variable | Required | Description |
|---|---|---|
| `APP_IMAGE` | yes | Image to run, e.g. `ghcr.io/<github-owner>/holdout` |
| `APP_TAG` | no | Image tag; empty means `latest`. Set to `sha-…` or a version to roll back. |
| `APP_KEY` | yes | Encryption key. Create with `php artisan key:generate --show`. Never change it after the first start: it encrypts the authenticator-app secrets. |
| `APP_URL` | yes | Public URL with `https://`, exactly as users open it |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | yes | PostgreSQL connection. In Coolify, `DB_HOST` is the internal hostname of the database resource. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | yes | SMTP account. Without working email nobody can sign in for the first time. |
| `HOLDOUT_SOURCE_URL` | no | Link to your copy of the source code; added to the identification Holdout sends to Open Food Facts |

Optional settings for the barcode lookup: `OPENFOODFACTS_ENABLED=false` turns it off; `OPENFOODFACTS_TIMEOUT` (default 5) and `OPENFOODFACTS_IMAGE_TIMEOUT` (default 15) are the waiting times in seconds.

## Setup in Coolify

1. Create a **PostgreSQL** resource and note its internal hostname and credentials. Turn on its scheduled backups.
2. Create a new resource of type **Docker Compose (Empty)** and paste `deploy/docker-compose.coolify.yml`.
3. Fill in the environment variables above.
4. On the `app` service, set the domain (`https://…`) and port `8080`. Coolify's proxy takes care of the certificate.
5. Deploy. `https://<your-domain>/up` must return HTTP 200.
6. Open the domain right away and create your household (see below).

Keep the resource's network setting so that the external `coolify` network stays attached; otherwise the app cannot reach the database.

## First start

On a fresh installation, every page leads to **Set up your household**: household name, your name, your email address and how many adults and children live in the household. You become the household's owner and receive a sign-in link by email; registration closes afterwards. Everyone else joins by invitation (see [Family](Family.md)).

Register immediately after the first deploy: until then, anyone who opens the URL could create the household.

## Updates and rollback

Every push to `main` builds a new `latest` image; a weekly rebuild picks up PHP security patches. With automatic deploys enabled (repository variable `AUTO_DEPLOY=true`, secrets `COOLIFY_WEBHOOK` and `COOLIFY_TOKEN`), GitHub Actions triggers the Coolify deploy webhook (HTTP `POST`) after each build.

To roll back, set `APP_TAG` to an older `sha-…` tag and deploy again. Database migrations are not rolled back automatically; restore a database backup if a rollback crosses a migration.

## Backups

- **Database:** use Coolify's scheduled backups of the PostgreSQL resource, ideally to S3 or another off-site target.
- **Product photos:** the `app-storage` volume, covered by the server backup.
- **Paper:** keep the printout in your emergency folder current; it is the backup that needs no server at all.
