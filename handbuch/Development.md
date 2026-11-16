# Development

## Stack

Laravel 13, Filament 5, PHP 8.5, SQLite (in production and in tests). Frontend assets are built with Vite and Tailwind CSS 4.

The PHP version appears in exactly three places that change together: `Dockerfile` (`ARG PHP_VERSION`), `composer.json` and `.devcontainer/devcontainer.json`.

## Local setup

The devcontainer (GitHub Codespaces or VS Code) provides PHP, Composer, Node and Docker.

```bash
cp .env.example .env
docker compose up -d                        # app on http://localhost:8080
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan db:seed --class=DemoSeeder
npm ci && npm run build
```

The database is `database/database.sqlite`. `DemoSeeder` creates a demo household with two people.

## Tests and code style

```bash
vendor/bin/pint          # format
php artisan test         # feature tests, in-memory SQLite
```

CI runs Pint in test mode and the test suite on every push and pull request.

## Conventions

- Every input is validated on the server; use validated data only.
- Every route that calls an external API (e.g. Open Food Facts) or does expensive work has a rate limit.
- No API keys or secrets in the frontend; `VITE_*` variables are public.
- All UI text is translatable: English source strings with `__()`, translations in `lang/{de,es,pt,fr}.json`. English is the main language and the fallback. `tests/Feature/LocaleTest.php` fails when a string (or a category name) is missing in one of the languages.
- Laravel's own texts (validation messages etc.) come from [Laravel Lang](https://laravel-lang.com) (`laravel-lang/common`, dev dependency) and are committed in `lang/<locale>/`. Update them with `php artisan lang:update`; a new language with `php artisan lang:add <code>` plus an entry in `App\Support\Locales`.
- No personal data of the maintainer in the repository (code, docs, commits, issues).

## Architecture notes

- **Modules and registries.** Features are split into modules in `app/Modules/<Name>/`, each with a service provider listed in `bootstrap/providers.php`. Modules register their parts in the registries in `app/Registries/`: `CategoryRegistry` (supply categories), `NavigationRegistry` (Filament resources and pages, read by the panel provider) and `ProductLookupRegistry` (product data sources by barcode, implementing `ProductLookup` and returning `ProductInfo`). Core code and other modules only read from the registries and never use a module directly; `tests/Unit/ArchitectureTest.php` enforces this. A module keeps its models, policies, Filament resources and migrations in its own folder (migrations via `loadMigrationsFrom`); factories stay in `database/factories`. Further registries (e.g. dashboard tiles) are added with their first use.
- **Modules so far.** `Supply`: food and water categories. `Inventory`: products (later batches, locations, movements). `OpenFoodFacts`: barcode lookup with a local cache (`off_cache`), rate limit and offline fallback; tests fake the HTTP calls.
- **Files.** The `data` disk is the bind-mounted data folder; it is private and served only through signed temporary URLs at `/files` (Laravel's `serve` option). Product photos live in `product-images/`, Open Food Facts downloads in `off-images/`.
- **Barcodes** are normalized to 13 digits by `App\Support\Barcode`.
- **Targets.** `App\Support\SupplyTargets` calculates the target amount per category from the household size and target days, reading only the `CategoryRegistry`.
- **Categories.** The supply module registers the food and water categories with the BLE values. `CategorySeeder` syncs them into the `categories` table; the container runs `migrate --seed` on every start, so `DatabaseSeeder` must stay idempotent and never create demo data (that is `DemoSeeder`).

- **Home network, no sign-in.** The app is never exposed to the internet. Laravel's auth guard is still used to remember which person uses a device ("Who are you?" logs that person in with a remember-me cookie), so Filament and the history work as usual. People have no email or password.
- **One container, SQLite.** The image defaults to SQLite in `storage/app/data`, synchronous queue and logs to stderr. The stack starts the container as root: `docker/entrypoint.d/40-holdout-data.sh` switches `www-data` to `PUID`/`PGID` when set (otherwise uid 82), hands it the data folder and creates the database and `app.key` on first start; the image is built world-readable (not writable) so the app works under any UID; `60-holdout-permissions.sh` fixes ownership after the Laravel automations. PHP-FPM and nginx workers run unprivileged. `config/app.php` reads the key from the file when `APP_KEY` is not set.
- **Deployment** deviates from the GitHub → GHCR → Coolify standard: images are still built by GitHub Actions and pushed to GHCR, but they run as a Portainer stack at home, updated by Portainer's GitOps polling.

## Documentation workflow

- `handbuch/<Page>.md` holds all details and is the source of the wiki. Always English.
- `README.md` stays short: only updated for a new module or a changed quick start.
- The wiki is generated, never edited by hand:

```bash
git clone "$(git remote get-url origin | sed 's/\.git$//').wiki.git" ../holdout.wiki
python3 scripts/build-wiki.py ../holdout.wiki
cd ../holdout.wiki && git add -A && git commit -m "Update wiki from handbuch" && git push
```
