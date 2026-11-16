# holdout

Mobile-first web app to build and maintain a family emergency food & water supply. Scan barcodes, track best-before dates, see how many days you'd last. Self-hosted, Laravel + Filament.

**Status:** v0.1 in progress. Sign-in without passwords (passkeys, email links, authenticator app), family invitations, target amounts per category (BLE values), products with photos and barcode lookup, batches, storage locations, history, dashboard, shopping list, equipment checklists (home pharmacy, power outage, documents, …) with a check log, a paper printout and an offline copy on phones work.

## Quick start

Holdout ships as a Docker image built by GitHub Actions. Deploy `deploy/docker-compose.coolify.yml` (app + queue worker) in Coolify with a separate PostgreSQL resource and an SMTP account, set the environment variables, open the site over HTTPS and create your household.

In an emergency, use the paper printout and the offline copy on your phone; they need no server.

Details: [Installation](handbuch/Installation.md) · Development: [Development](handbuch/Development.md) · Full handbook: [handbuch/](handbuch/Home.md)

## License

[AGPL-3.0](LICENSE). If you run a modified version for others over a network, you must offer them its source code.
