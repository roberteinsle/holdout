# Installation

Holdout runs as a single Docker image. GitHub Actions builds it and pushes it to the GitHub Container Registry (GHCR); the server only pulls finished images. The examples use [Coolify](https://coolify.io), but any Docker Compose host works.

## Requirements

- A PostgreSQL database (in Coolify: a separate PostgreSQL resource)
- An SMTP account for sending email (sign-in links and invitations)
- A domain with HTTPS. Passkeys only work on the exact domain set in `APP_URL`.

## Services

`deploy/docker-compose.coolify.yml` defines two containers from the same image:

| Service | Purpose |
|---|---|
| `app` | Web server (Nginx + PHP-FPM) on port 8080. Runs database migrations on start. |
| `worker` | Queue worker. Sends emails (sign-in links, invitations) in the background. |

A scheduler container is **not** needed yet: Holdout has no scheduled tasks. It will be added together with features that need one (for example weekly expiry reminders).

## Environment variables

| Variable | Required | Description |
|---|---|---|
| `APP_IMAGE` | yes | Image to run, e.g. `ghcr.io/<github-owner>/holdout` |
| `APP_TAG` | no | Image tag; empty means `latest`. Set to `sha-…` or a version to roll back. |
| `APP_KEY` | yes | Encryption key. Create with `php artisan key:generate --show`. Never change it after the first start: it encrypts the authenticator-app secrets. |
| `APP_URL` | yes | Public URL with `https://`, exactly as users open it |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | yes | PostgreSQL connection. In Coolify, `DB_HOST` is the internal hostname of the database resource. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | yes | SMTP account. Without working email nobody can sign in for the first time. |

## Setup in Coolify

1. Create a PostgreSQL resource and note its internal hostname and credentials.
2. Create a new resource of type **Docker Compose (Empty)** and paste `deploy/docker-compose.coolify.yml`.
3. Fill in the environment variables above.
4. On the `app` service, set the domain and port `8080`.
5. Deploy. `https://<your-domain>/up` must return HTTP 200.
6. Open the domain right away and create your household (see below).

The `app` and `worker` services join the external `coolify` network to reach the database. Keep the resource's network setting so that this network stays attached.

## First start

On a fresh installation, every page leads to **Set up your household**. The first person to register becomes the household's owner, and registration closes afterwards. Everyone else joins by invitation (see [Family and invitations](Family-and-invitations.md)).

Register immediately after the first deploy: until then, anyone who opens the URL could create the household.

## Updates and rollback

Every push to `main` builds a new `latest` image; a weekly rebuild picks up PHP security patches. With automatic deploys enabled, GitHub Actions triggers the Coolify deploy webhook (HTTP `POST`) after each build.

To roll back, set `APP_TAG` to an older `sha-…` tag and deploy again. Database migrations are not rolled back automatically.
