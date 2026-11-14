# holdout

Mobile-first web app to build and maintain a family emergency food & water supply. Scan barcodes, track best-before dates, see how many days you'd last. Self-hosted, Laravel + Filament.

**Status:** v0.1 in progress. Household setup, passwordless sign-in (passkeys, email links, authenticator app) and family invitations work; stock tracking follows.

## Quick start

Holdout ships as a Docker image built by GitHub Actions. Deploy `deploy/docker-compose.coolify.yml` (app + queue worker) with a PostgreSQL database and an SMTP account, set the environment variables, open the site and create your household.

Details: [Installation](handbuch/Installation.md) · Development: [Development](handbuch/Development.md) · Full handbook: [handbuch/](handbuch/Home.md)

## License

MIT
