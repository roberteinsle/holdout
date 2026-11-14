# Development

## Stack

Laravel 13, Filament 5, PHP 8.5, PostgreSQL in production, SQLite in tests. Frontend assets are built with Vite and Tailwind CSS 4.

The PHP version appears in exactly three places that change together: `Dockerfile` (`ARG PHP_VERSION`), `composer.json` and `.devcontainer/devcontainer.json`.

## Local setup

The devcontainer (GitHub Codespaces or VS Code) provides PHP, Composer, Node and Docker.

```bash
cp .env.example .env
docker compose up -d                        # app on http://localhost:8080 + PostgreSQL
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm ci && npm run build
```

The seeder creates a demo household with `owner@example.com` and `member@example.com`. With `MAIL_MAILER=log`, sign-in links appear in `storage/logs/laravel.log`.

## Tests and code style

```bash
vendor/bin/pint          # format
php artisan test         # feature tests, in-memory SQLite
```

CI runs Pint in test mode and the test suite on every push and pull request.

## Conventions

- Every input is validated on the server; use validated data only.
- Every route that can be abused (sign-in, invitations, external APIs) has a rate limit.
- No API keys or secrets in the frontend; `VITE_*` variables are public.
- All UI text is translatable: English source strings with `__()`, German in `lang/de.json`.
- No personal data of the maintainer in the repository (code, docs, commits, issues).

## Documentation workflow

- `handbuch/<Page>.md` holds all details and is the source of the wiki. Always English.
- `README.md` stays short: only updated for a new module or a changed quick start.
- The wiki is generated, never edited by hand:

```bash
git clone "$(git remote get-url origin | sed 's/\.git$//').wiki.git" ../holdout.wiki
python3 scripts/build-wiki.py ../holdout.wiki
cd ../holdout.wiki && git add -A && git commit -m "Update wiki from handbuch" && git push
```
