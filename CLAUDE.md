# holdout

Mobile-first Laravel 13 + Filament 5 app for a family emergency food & water supply.

## Stack and conventions
- PHP 8.5 everywhere. The version lives in exactly three places, changed together:
  `Dockerfile` (`ARG PHP_VERSION`), `composer.json` (`"php": "^8.5"`), `.devcontainer/devcontainer.json`.
- Production: GitHub Actions builds the image (`.github/workflows/build.yml`) and pushes it to GHCR;
  Coolify only pulls (`deploy/docker-compose.coolify.yml`). No secrets in the image.
- Local: `docker compose up -d` (app on :8080, Postgres), then
  `docker compose exec app composer install` and `docker compose exec app php artisan migrate`.
- Tests use in-memory SQLite (`phpunit.xml`). Before committing: `vendor/bin/pint` and `php artisan test`.
- Login (issue #2): passkeys (`laravel/passkeys`) first, magic link as fallback. No passwords.
- Validate every input server-side, use `$request->validated()`, give every external call a rate limit,
  and never put API keys in the frontend (no secrets in `VITE_*`).
- UI strings in English and German from the start (`lang/en`, `lang/de`).
