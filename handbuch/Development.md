# Development

## Stack

Laravel 13, Filament 5, PHP 8.5, PostgreSQL in production and development, in-memory SQLite in tests (CI also runs the tests against PostgreSQL). Frontend assets are built with Vite and Tailwind CSS 4.

The PHP version appears in exactly three places that change together: `Dockerfile` (`ARG PHP_VERSION`), `composer.json` and `.devcontainer/devcontainer.json`.

## Local setup

The devcontainer (GitHub Codespaces or VS Code) provides PHP, Composer, Node and Docker.

```bash
cp .env.example .env
docker compose up -d                        # app on http://localhost:8080 + PostgreSQL
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan db:seed --class=DemoSeeder
npm ci && npm run build
```

`DemoSeeder` creates a demo household with `owner@example.com` and `member@example.com`. With `MAIL_MAILER=log`, sign-in links appear in `storage/logs/laravel.log`.

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

- **Modules and registries.** Features are split into modules in `app/Modules/<Name>/`, each with a service provider listed in `bootstrap/providers.php`. Modules register their parts in the registries in `app/Registries/`: `CategoryRegistry` (supply categories), `NavigationRegistry` (Filament resources and pages, read by the panel provider) `ProductLookupRegistry` (product data sources by barcode, implementing `ProductLookup` and returning `ProductInfo`) `StockRegistry` (stock per category, implementing `StockSource` and returning `StockAmount` with usable and expired amounts) `DashboardRegistry` (Filament widgets on the dashboard; order from each widget's `$sort`; core registers `SupplyStatusWidget` itself) and `PrintoutRegistry` (sections of the paper emergency copy, implementing `PrintSection`; core registers `SupplySection` itself). Core code and other modules only read from the registries and never use a module directly; `tests/Unit/ArchitectureTest.php` enforces this. A module keeps its models, policies, Filament resources and migrations in its own folder (migrations via `loadMigrationsFrom`); factories stay in `database/factories`. Further registries are added with their first use.
- **Modules so far.** `Supply`: food and water categories. `Inventory`: products, batches, locations, movements. `OpenFoodFacts`: barcode lookup with a local cache (`off_cache`), rate limit and offline fallback; tests fake the HTTP calls. `Equipment`: non-food checklists (areas from the BBK guidance, suggestions in `Support/Suggestions`, status, check log), connected through the `NavigationRegistry` and the `PrintoutRegistry`.
- **Scanner.** `resources/js/scanner.js` (zxing, loaded only on the Scan page via `@vite`) exposes `window.holdoutScanner`; the page waits for it, because the module may load after Alpine. Module views are loaded with `loadViewsFrom` (`inventory::scan`); the Filament theme also scans `app/Modules/**` for Tailwind classes.
- **Files.** The `data` disk (`storage/app/data`, on the `app-storage` volume) is private and served only through signed temporary URLs at `/files` (Laravel's `serve` option). Product photos live in `product-images/`, Open Food Facts downloads in `off-images/`.
- **Barcodes** are normalized to 13 digits by `App\Support\Barcode`.
- **Targets.** `App\Support\SupplyTargets` calculates the target amount per category from the household size and target days, reading only the `CategoryRegistry`. `App\Support\SupplyStatus` sets it against the stock from the `StockRegistry` (`CategoryStatus`: percent, days, missing, `StockLevel`) and gives the days of supply (the smallest category).
- **Categories.** The supply module registers the food and water categories with the BLE values. `CategorySeeder` syncs them into the `categories` table; the container runs `migrate --seed` on every start, so `DatabaseSeeder` must stay idempotent and never create demo data (that is `DemoSeeder`).

- **Sign-in.** No passwords: passkeys (`laravel/passkeys`, `resources/js/passkeys.js`), email links (`LoginToken`, 15 minutes, single use, hashed) and an authenticator app (`App\Support\Totp`, encrypted secret, recovery codes). Changing sign-in methods needs a recent sign-in (`auth.password_confirmed_at`), which all three methods set. Invitations (`Invitation`, 7 days) let the owner add people.
- **Deployment** follows the GitHub → GHCR → Coolify standard: `deploy/docker-compose.coolify.yml` (app + queue worker), PostgreSQL as a separate Coolify resource on the external `coolify` network, environment from Coolify. The container runs migrations and `CategorySeeder` on every start (`migrate --seed`).

## Documentation workflow

- `handbuch/<Page>.md` holds all details and is the source of the wiki. Always English.
- `README.md` stays short: only updated for a new module or a changed quick start.
- The wiki is generated, never edited by hand:

```bash
git clone "$(git remote get-url origin | sed 's/\.git$//').wiki.git" ../holdout.wiki
python3 scripts/build-wiki.py ../holdout.wiki
cd ../holdout.wiki && git add -A && git commit -m "Update wiki from handbuch" && git push
```

## Project page

The project page that introduces Holdout to new users lives in `site/` and is published to GitHub Pages by `.github/workflows/pages.yml` on every push to `main` that touches `site/`. It is a single static page (`index.html`, `style.css`) without a build tool, external fonts or trackers. Always English.

- **Placeholders:** `__SITE__` (the URL from `site/CNAME`), `__REPO__` (owner/repository from `github.repository`) and `__DATE__` (build date) are replaced at build time, so neither the domain nor the repository name is written into the pages twice.
- **Content:** an overview of every feature with links into the wiki, the daily amounts per person, installation in three steps and an FAQ. When a feature, a module or the quick start changes, update the page together with the handbook.
- **SEO and GEO:** title, description, canonical URL, Open Graph image (`og.png`, the social preview), JSON-LD (`SoftwareApplication`, `WebSite`, `FAQPage`, whose questions must match the visible FAQ), `robots.txt`, `sitemap.xml` and `llms.txt`, a plain-text summary for AI assistants. Keep concrete facts (numbers, sources, requirements) on the page: search engines and AI assistants quote them.
- **Preview locally:** copy `site/` somewhere, replace the placeholders as the workflow does, and serve it with `python3 -m http.server`.
- **Setup (once):** in the repository settings, *Pages → Source: GitHub Actions* and the custom domain from `site/CNAME` with *Enforce HTTPS*; at the DNS provider a `CNAME` record from that domain to `<github-owner>.github.io`, not proxied.
