# holdout

Mobile-first Laravel 13 + Filament 5 app for a family emergency food & water supply.

## Stack and conventions
- PHP 8.5 everywhere. The version lives in exactly three places, changed together:
  `Dockerfile` (`ARG PHP_VERSION`), `composer.json` (`"php": "^8.5"`), `.devcontainer/devcontainer.json`.
- Runs on the home network, never exposed to the internet (it must work in a grid or internet outage).
  GitHub Actions builds the image (`.github/workflows/build.yml`) and pushes it to GHCR; it runs as a
  Portainer stack (`deploy/docker-compose.portainer.yml`), updated by Portainer's GitOps polling. This
  deliberately deviates from the GitHub → GHCR → Coolify deployment standard. No secrets in the image.
- One container, SQLite in `storage/app/data` (bind-mounted), sync queue, logs to stderr. The stack starts the
  container as root so `docker/entrypoint.d/40-holdout-data.sh` can switch www-data to `PUID`/`PGID`
  (Synology default 1026/100; otherwise uid 82), hand it the folder and create the database and `app.key` on
  first start; `45-holdout-backup.sh` copies the database to `backups/` before pending migrations (keeps 10);
  `60-holdout-permissions.sh` fixes ownership after the Laravel automations. The image is built
  world-readable (`chmod -R u+rwX,go+rX,go-w`) so any UID can run it. PHP-FPM and nginx workers run
  unprivileged. `config/app.php` reads the key from that file when `APP_KEY` is unset. Proxies are trusted (`X-Forwarded-*`) for HTTPS via NPM.
- Local: `docker compose up -d` (app on :8080, SQLite), then
  `docker compose exec app composer install` and `docker compose exec app php artisan migrate`.
- Tests use in-memory SQLite (`phpunit.xml`). Before committing: `vendor/bin/pint` and `php artisan test`.
- No sign-in, no accounts, no email. Each device picks a person on "Who are you?" (`/who`, route `login`),
  which logs that person in with a remember-me cookie, so Filament and the history know who acts.
  People (`users`) have only a name. One household per installation; everyone may manage the family list.
- Household size lives in `members` (type adult/child + count, per the roadmap), separate from the people
  who use the app; target days and the "eat soon" window are on `households` (no language there).
- Modules live in `app/Modules/<Name>/` (service provider in `bootstrap/providers.php`) and register into the
  registries in `app/Registries/` (`CategoryRegistry`, `NavigationRegistry` for Filament resources/pages,
  `ProductLookupRegistry` for barcode data sources; a dashboard registry comes with its first use). Core and other modules only read registries;
  `tests/Unit/ArchitectureTest.php` enforces it. A module owns its models, policies (`Gate::policy`), Filament
  resources and migrations (`loadMigrationsFrom`); factories stay in `database/factories`.
- Scan page (Inventory, `inventory::scan` view, `resources/js/scanner.js` with zxing; camera needs HTTPS, manual input
  always works): known barcode -> BatchResource create `?product=`, unknown -> ProductResource create `?barcode=`
  (fills and looks it up). The theme's `@source` includes `app/Modules/**`.
- Modules: `Supply` (categories), `Inventory` (products; batches = packages + best-before date, status from `eat_soon_days`; locations per household, optional on a batch, `nullOnDelete`; movements = history: `BatchObserver` records added/corrected via `StockLedger`, take-out goes through `StockLedger::takeOut()` (saves quietly, deletes empty batches), read-only, `user_id`/`batch_id` `nullOnDelete`), `OpenFoodFacts`
  (lookup via the registry; local cache 30 days / not found 7 days, stale cache when offline, 30 req/min, generic
  User-Agent without contact data, `HOLDOUT_SOURCE_URL` optional; downloads the front photo once, https on
  openfoodfacts.org only, real images ≤ 2 MB, 15 s timeout). Files: private `data` disk = data folder, signed
  temporary URLs at `/files`; products keep their own photo copy in `product-images/` (+ `image_credit`). Barcodes: 13 digits (`App\Support\Barcode`). Package units
  g/kg/ml/l, converted to kg or l with 1 l = 1 kg. Lists are mobile first (details in the name column).
- Categories: the Supply module registers BLE values per person/day (children only differ for drinks), plus
  `other` without a daily need (`CategoryDefinition::hasTarget()` false: no target, never counted in days of supply).
  `CategorySeeder` syncs them on every start (`migrate --seed`); `DatabaseSeeder` must stay idempotent, demo
  data is `DemoSeeder`. Category names are English source strings; keep them in `lang/de.json`.
- Targets: `App\Support\SupplyTargets` = (adults × per adult + children × per child) × target days, from the
  `CategoryRegistry` only; shown on the Household page.
- Emergency fallbacks matter more than the live app: printout (#13) and the PWA offline copy (#14).
- Validate every input server-side, use `$request->validated()`, give every external call a rate limit,
  and never put API keys in the frontend (no secrets in `VITE_*`).
- Languages: English (main, fallback), German, Spanish, Portuguese, French (`App\Support\Locales`). Every
  person picks theirs on the Profile page (`users.locale`); otherwise the browser decides. UI strings are English
  `__()` sources translated in `lang/{de,es,pt,fr}.json`; add every new string to all four (LocaleTest checks).
  Laravel's own texts come from laravel-lang (dev dependency, committed in `lang/<locale>/`).
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
