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
- Sign-in: passkeys (`laravel/passkeys`), magic link, and authenticator app (TOTP) as backup. No passwords.
  Changing sign-in methods needs a recent sign-in (`auth.password_confirmed_at`); passkey, magic-link and
  code sign-ins all set it.
- One household per installation (v0.1); roles owner/member via policies.
- Validate every input server-side, use `$request->validated()`, give every external call a rate limit,
  and never put API keys in the frontend (no secrets in `VITE_*`).
- UI strings in English and German from the start (`__()` with English source, `lang/de.json`).
- Colours: `app/Support/Palette.php` and `handbuch/Design.md`; never white text on signal orange.

## Rules
- No personal data of the maintainer in the repository: code, docs, `handbuch/`, wiki, commit messages,
  issues. Derive owner/repo names from the git remote or configuration, never hardcode them.
- For every new feature or module, check how it interacts with the existing modules, and connect it
  only through the registries, never through direct access between modules.
- Update documentation where affected (always English):
  - `handbuch/<Page>.md`: all details; source of the wiki.
  - `README.md`: only for a new module or a changed quick start; keep it short.
  - `CLAUDE.md`: on changes to architecture, rules or workflows.
  - Wiki: regenerate with `scripts/build-wiki.py` from `handbuch/` and push to the `holdout.wiki.git`
    repository; never edit the wiki by hand.
- Commit and push to `main` when done.
