# holdout

Mobile-first web app to build and maintain a family emergency food & water supply. Scan barcodes, track best-before dates, see how many days you'd last. Self-hosted on your home network, Laravel + Filament.

**Status:** v0.1 in progress. Household setup and the family list work; stock tracking follows.

## Quick start

Holdout runs at home, so it keeps working when the internet is down. On any always-on Docker host (e.g. a Synology DiskStation with Portainer):

1. Create a data folder (e.g. `/volume1/docker/holdout`) and the Docker network `proxy`.
2. Deploy `deploy/docker-compose.portainer.yml` as a stack with `APP_IMAGE` set to the published image.
3. Open `http://<server-ip>:8090` and create your household. No sign-in, no configuration.

For the camera scanner and installing the app on phones, add HTTPS with Nginx Proxy Manager. Details: [Installation](handbuch/Installation.md) · [Development](handbuch/Development.md) · [Handbook](handbuch/Home.md)

## License

MIT
