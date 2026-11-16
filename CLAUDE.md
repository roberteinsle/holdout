# holdout

Mobile-first Laravel 13 + Filament 5 app for a family emergency food & water supply.

## Stack and conventions
- PHP 8.5 everywhere. The version lives in exactly three places, changed together:
  `Dockerfile` (`ARG PHP_VERSION`), `composer.json` (`"php": "^8.5"`), `.devcontainer/devcontainer.json`.
- Production: GitHub Actions builds the image (`.github/workflows/build.yml`) and pushes it to GHCR; Coolify
  only pulls (`deploy/docker-compose.coolify.yml`: app + queue worker for mail) and is triggered by the deploy
  webhook (POST) when `AUTO_DEPLOY=true`. PostgreSQL is a separate Coolify resource (never in the compose file),
  reached over the external `coolify` network. No secrets in the image; environment comes from Coolify. The
  container runs `migrate --seed` on every start. Product photos live on the `app-storage` volume. Proxies are
  trusted (`X-Forwarded-*`) for HTTPS behind Coolify's proxy.
- Local: `docker compose up -d` (app on :8080 + PostgreSQL), then
  `docker compose exec app composer install` and `docker compose exec app php artisan migrate --seed`.
- Tests use in-memory SQLite (`phpunit.xml`); CI also runs them against PostgreSQL. Before committing:
  `vendor/bin/pint` and `php artisan test`.
- Sign-in without passwords: passkeys (`laravel/passkeys`), magic link (15 min, single use, hashed tokens, same
  answer for unknown addresses), authenticator app (TOTP) as backup with recovery codes. Changing sign-in methods
  needs a recent sign-in (`auth.password_confirmed_at`); passkey, magic-link and code sign-ins all set it.
  One household per installation: the first person registers (with household size) and becomes owner; others
  join by invitation (7 days). Roles owner/member via policies. Mail is queued (worker). Name, language and
  sign-in methods are on one page, `App\Filament\Pages\Profile` (`/profile`, user menu, not in the navigation).
- In an emergency only offline processes count (printout, offline copy on phones); the live app is for managing
  the supply in normal times.
- Household size lives in `members` (type adult/child + count, per the roadmap), separate from the people
  who use the app; target days and the "eat soon" window are on `households` (no language there).
- Modules live in `app/Modules/<Name>/` (service provider in `bootstrap/providers.php`) and register into the
  registries in `app/Registries/` (`CategoryRegistry`, `NavigationRegistry` for Filament resources/pages,
  `ProductLookupRegistry` for barcode data sources, `StockRegistry` for stock per category, `DashboardRegistry` for dashboard widgets: core `SupplyStatusWidget`,
  Inventory quick actions / eat soon / recently; all full width, order by `$sort`; `PrintoutRegistry` for printout sections (`PrintSection`): core supply, Inventory eat first / stock by location). Core and other modules only read registries;
  `tests/Unit/ArchitectureTest.php` enforces it. A module owns its models, policies (`Gate::policy`), Filament
  resources and migrations (`loadMigrationsFrom`); factories stay in `database/factories`.
- Scan page (Inventory, `inventory::scan` view, `resources/js/scanner.js` with zxing; camera needs HTTPS, manual input
  always works): known barcode -> BatchResource create `?product=`, unknown -> ProductResource create `?barcode=`
  (fills and looks it up). The theme's `@source` includes `app/Modules/**`.
- Modules: `Supply` (categories), `Inventory` (products; batches = packages + best-before date, status from `eat_soon_days`; locations per household, optional on a batch, `nullOnDelete`; movements = history: `BatchObserver` records added/corrected via `StockLedger`, take-out goes through `StockLedger::takeOut()` (saves quietly, deletes empty batches), read-only, `user_id`/`batch_id` `nullOnDelete`), `OpenFoodFacts`
  (lookup via the registry; local cache 30 days / not found 7 days, stale cache when offline, 30 req/min, generic
  User-Agent without contact data, `HOLDOUT_SOURCE_URL` optional; downloads the front photo once, https on
  openfoodfacts.org only, real images ≤ 2 MB, 15 s timeout). Files: private `data` disk = `storage/app/data` (app-storage volume), signed
  temporary URLs at `/files`; products keep their own photo copy in `product-images/` (+ `image_credit`). Barcodes: 13 digits (`App\Support\Barcode`). Package units
  g/kg/ml/l, converted to kg or l with 1 l = 1 kg. Lists are mobile first (details in the name column).
- Categories: the Supply module registers BLE values per person/day (children only differ for drinks), plus
  `other` without a daily need (`CategoryDefinition::hasTarget()` false: no target, never counted in days of supply).
  `CategorySeeder` syncs them on every start (`migrate --seed`); `DatabaseSeeder` must stay idempotent, demo
  data is `DemoSeeder`. Category names are English source strings; keep them in `lang/de.json`.
- Targets: `App\Support\SupplyTargets` = (adults × per adult + children × per child) × target days, from the
  `CategoryRegistry` only. `App\Support\SupplyStatus` compares them with the `StockRegistry` (Inventory's
  `InventoryStock`: non-expired batches in kg/l; expired shown, not counted); days of supply = smallest category.
  Shown on the Household page. `App\Support\ShoppingList` (core page `/shopping-list`) = what is missing per category
  from `SupplyStatus`, rounded up to 0.1, with a share/copy text. Plurals use `trans_choice` with `|` keys in the JSON files (LocaleTest checks them too).
- Emergency fallbacks matter more than the live app: printout (#13) and the PWA offline copy (#14).
  Printout = route `printout` outside the panel (`auth` + `SetLocale`), inline styles only, A4 black and white, status
  written out; the menu page "Emergency copy" is `App\Filament\Pages\Printout` (`/print`). Offline copy = `public/sw.js`
  (registered in `partials/head-icons` when signed in, HTTPS/localhost only): caches only `/printout` (header
  `X-Holdout-Printout`), network first for pages, serves the copy with its banner when the server is unreachable.
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
