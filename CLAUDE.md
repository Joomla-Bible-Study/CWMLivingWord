# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LivingWord is a **Joomla 5/6 component** (`com_livingword`) that provides Bible reading plans, study tools, group reading, and email delivery. The package (`pkg_livingword`) ships:

- the component (`com_livingword`), including a REST API under `api/`
- a Joomla module (`mod_livingword`) for daily reading display
- a task plugin (`plg_task_livingword`) with three scheduled routines: daily reading email, weekly progress digest, and accountability-partner digest
- a web services plugin (`plg_webservices_livingword`) that registers the `v1/livingword/*` API routes
- the bundled CWM Scripture package (`pkg_cwmscripture`), which supplies scripture text and audio — see [Scripture Integration](#scripture-integration)

**License:** GPL-2.0-or-later | **Maintained by:** CWM Team (Christian Web Ministries) | **Current version:** see `build/versions.json` (single source of truth)

Originally created by Mike Leeper (MLWebTechnologies) as a Joomla 3.x component, migrated to Joomla 5 architecture with namespaced MVC, PSR-4 autoloading, and modern PHP 8.3+ patterns.

## Architecture

This follows the standard **Joomla 5/6 MVC component pattern** with namespaced classes under `CWM\Component\Livingword`.

### Top-level Layout

- `livingword.xml` — Joomla extension manifest (Joomla 5+6 compatibility, PHP 8.3+)
- `script.php` — Install/update/uninstall script with version checks
- `admin/` — Administrator component (controllers, models, views, templates, forms, SQL, language)
- `site/` — Frontend component (controllers, models, views, templates, language)
- `api/` — Web Services API controllers and JSON:API views (ships inside `com_livingword.zip`)
- `mod_livingword/` — Joomla module for displaying daily Bible reading
- `plg_task_livingword/` — Task plugin for scheduled email delivery
- `plg_webservices_livingword/` — Web services plugin; registers the API routes (enabling it is the API's on/off switch)
- `build/` — Build tools and scripts, including `build/script.install.php` (the package install script) and `build/pkg_livingword.xml` (the package manifest)
- `tests/` — PHPUnit suites (unit + integration) and Playwright suites (`tests/e2e/`: browser, a11y, API)
- `libraries/vendor/` — Composer's `vendor-dir` (dev tooling such as `cwm-build-tools`; never shipped)

### Component Structure (MVC)

**Admin side** (`admin/src/`):
- `Extension/LivingwordComponent.php` — Boot class with runtime PHP/Joomla version verification, plus `CategoryServiceInterface` (tools and links use `com_categories`) and `TagServiceInterface` (plans, links and groups are taggable)
- `Controller/` — 15 controllers grouped by feature area:
  - Plans: `Cwmplans`, `Cwmplan`, `Cwmplandetails`, `Cwmplandetail`
  - Links: `Cwmlinks`, `Cwmlink`
  - Tools (study-tool catalog): `Cwmtools`, `Cwmtool`
  - Groups (reading-plan cohorts): `Cwmgroups`, `Cwmgroup`
  - Subscribers: `Cwmusers`
  - Dashboard: `Cwmcpanel`
  - DB maintenance: `Cwmutilities`
  - Audio preview endpoint: `Cwmaudio`
  - Request router: `DisplayController`
- `Model/` — 15 models: one per CRUD entity, plus `CwmnotesModel`, `CwmprogressModel` and `CwmsettingsModel`, which back the API's user-scoped resources (no model for Audio, Utilities, or DisplayController)
- `View/` — 13 views; each pairs `admin/src/View/Cwm*/HtmlView.php` with `admin/tmpl/cwm*/{default,edit}.php`
- `Table/` — 6 table classes: `CwmplanTable`, `CwmplandetailTable`, `CwmlinkTable`, `CwmtoolTable`, `CwmgroupTable`, `CwmuserTable`
- `Field/` — 3 custom form fields: `BibleVersionField`, `PlanField`, `PlanIdField` (replace Joomla's `sql` field for J6 compatibility — see Key Patterns). Link and tool categories use Joomla's core `categoryedit` field.
- `Helper/CwmlivingwordHelper.php` — ACL helper
- `Dispatcher/Dispatcher.php` — admin dispatcher; default controller = `cwmcpanel`

**Site (frontend) side** (`site/src/`):
- `Controller/` — 9 controllers:
  - `DisplayController` — view dispatcher with guest caching
  - `CwmsettingsController` — saves the reader's preferences form
  - `CwmsubscribeController`, `CwmunsubscribeController` — email opt-in / token-based unsubscribe
  - `CwmcompleteController` — one-click "mark as read" from email
  - `CwmprogressController` — AJAX progress tracking
  - `CwmnotesController` — per-day reflection notes
  - `CwmgroupController` — group join/leave
  - `CwmaudioController` — audio URL fetcher
- `Model/` — 8 models: `CwmhomeModel`, `CwmplanviewModel`, `CwmresourcesModel`, `CwmsettingsModel`, `CwmtoolsModel`, `CwmgroupsModel`, `CwmgroupdetailModel`, `CwminviteModel`
- `View/` — 10 views with `prepareDocument()` for page titles: `Cwmhome`, `Cwmplanview`, `Cwmresources`, `Cwmsettings`, `Cwmtools`, `Cwmgroups`, `Cwmgroupdetail`, plus three landing pages that are intentionally unrouted: `Cwmcomplete` and `Cwmunsubscribe` (reached from email links) and `Cwminvite` (public group-invitation page, reached from a shared `?view=cwminvite&token=…` link)
- `Helper/` — 9 helpers:
  - `CwmreadingHelper` — date math, plan-day calculations
  - `CwmuserHelper` — user prefs, token generation, action URLs
  - `CwmmenuHelper` — navigation
  - `CwmprogressHelper` — completion tracking, streaks
  - `CwmscriptureHelper` — passage text and audio via `lib_cwmscripture`; falls back to the plain reading reference (see [Scripture Integration](#scripture-integration))
  - `CwmemailHelper` — branded layout + `send()` mailer (shared by task plugin)
  - `CwmnotesHelper` — note CRUD
  - `CwmgroupHelper` — group queries
  - `CwmpartnerHelper` — accountability-partner pairing
- `Service/Router.php` — URL routing for the 7 menu-reachable views
- `Dispatcher/Dispatcher.php` — site dispatcher; default controller = `display`

### Namespace Structure

```
CWM\Component\Livingword\Administrator\  → admin/src/
CWM\Component\Livingword\Site\           → site/src/
CWM\Component\Livingword\Api\            → api/src/
CWM\Module\Livingword\Site\              → mod_livingword/src/
CWM\Plugin\Task\Livingword\              → plg_task_livingword/src/
CWM\Plugin\WebServices\Livingword\       → plg_webservices_livingword/src/
```

### REST API (Web Services)

`plg_webservices_livingword` registers routes under `v1/livingword/{resource}`; the controllers and JSON:API views live in `api/src/`. Two kinds of resource, with deliberately different rules:

- **Catalog** (`plans`, `plandays`, `tools`, `links`, `groups`): read-only (`GET` list + item). They require an API token unless the plugin's `public_reads` option is on (off by default).
- **User-scoped** (`progress`, `notes`, `settings`): readable and writable, always authenticated, and always scoped to the current user. `public_reads` never applies to them. `settings` is a singleton with no item route.

Enabling the plugin is the API's on/off switch. Its install script enables it, but the API still needs a token. Playwright covers the API in `tests/e2e/` (`composer test:api`).

### Database Tables (MySQL, prefixed with `#__`)

| Table | Purpose |
|-------|---------|
| `#__livingword_plans` | Reading plan definitions (alias, title, description, message, audio settings, testament filter, duration_type, total_days) |
| `#__livingword_plans_details` | Daily readings per plan (plan FK, reading ref, optional audio override, devotional `descrip` text) |
| `#__livingword_users` | Per-user settings: plan FK, bible/audio version, email opt-in, planview mode, start_date, streak tracking, accountability partner, unsubscribe + action tokens, timezone, preferred email hour |
| `#__livingword_progress` | Completed readings (user FK, plan FK, day, passage_index, completed_at) |
| `#__livingword_links` | Curated Bible resource links (name, url, catid for `com_categories`, target, published, ordering) |
| `#__livingword_tools` | Study-tool catalog (name, description, url, icon, color, catid for `com_categories`) |
| `#__livingword_groups` | Reading-plan groups (name, plan FK, start_date, invite_token, join_mode, created_by) |
| `#__livingword_group_members` | Group membership (group FK, user FK, role: member or leader) |
| `#__livingword_notes` | Per-day journal entries (user FK, plan FK, day, note_text) |

### Key Patterns

- **Admin list views** use `GenericDataException` for error handling with `$model->setUseExceptions(true)`
- **Admin edit views** use `GenericDataException`, set layout to 'edit', hide main menu
- **Site views** use `prepareDocument()` to set document title respecting Joomla's `sitename_pagetitles` config
- **Filter forms** must be named `filter_cwm{viewname}.xml` (Joomla auto-derives from model class name)
- **DB maintenance commands** (OPTIMIZE/CHECK/REPAIR) use `$db->getConnection()->query()` since prepared statements don't support DDL
- **Container access** uses `Factory::getContainer()` (not `$app->getContainer()` which is protected in J6)

### Scripture Integration

Scripture text, audio and translations come from the **CWM Scripture** stack, which lives in sibling repos and is versioned there:

- `lib_cwmscripture`: [Joomla-Bible-Study/lib_cwmscripture](https://github.com/Joomla-Bible-Study/lib_cwmscripture). Its README is the integration contract.
- `plg_content_scripturelinks`, `plg_task_cwmscripture` and `plg_system_cwmscripture`: [Joomla-Bible-Study/CWMScriptureLinks](https://github.com/Joomla-Bible-Study/CWMScriptureLinks).

Rules that matter here:

- **Soft dependency.** Every call goes through `site/src/Helper/CwmscriptureHelper.php`, which guards on `class_exists()` / `LibraryVersion::isInstalled()` and catches `\Throwable` around library calls. A library fault must degrade the page, never 500 it.
- **Structured references, never raw plan strings.** Plan readings use citation shorthand: chapter ranges (`Ecclesiastes 1-3`) and comma lists (`Romans 5-8,12`, `Romans 4:1-5,9-25`).
  - `CwmscriptureHelper::parsePassage()` splits each passage into plain parts. It's pure, and unit-tested against every passage in the install seed.
  - `fetchPart()` then asks for each part with `getPassageFor(ScriptureReference)`, the same way Proclaim's `Cwmshowscripture::fetchPassage()` does.
  - Don't hand plan strings to `getPassage()`. The library's parsers disagree on `Genesis 1-3`: `ScriptureHelper::parseReference()` rejects it on purpose, and LocalProvider reads it as 1:1–3. None of them reads comma lists. Those are this component's data format to parse, not the library's.
  - Audio uses the same parts (`audioChapters()`).
- **No external Bible sites.** LivingWord does not link to BibleGateway or any other external reader. When no scripture text can be shown (library missing, display mode "reference only", or no provider answered), the reading renders as its plain reference via `CwmscriptureHelper::buildReadingReference()`. `buildReadingLink()` is a deprecated alias kept for template overrides.
- **Display mode** is the component option `config_scripture_display`: `inline`, `toggle`, or `link` ("Reference only"). The stored value `link` is kept so saved configs still validate.
- **Provider settings** (GetBible, API.Bible, BibleBrain audio, GDPR mode, default version) live in `plg_content_scripturelinks`'s params. Read them through `ScriptureParamsHelper::getParams()`, not this component's options.
- **Shipping.** `pkg_cwmscripture.zip` (library + content, task and system plugins) rides inside `pkg_livingword` as an *undeclared* extra. `build/script.install.php` installs it in `preflight`, skipping it when an equal or newer copy is already installed. It is deliberately absent from `build/pkg_livingword.xml`'s `<files>`, so uninstalling LivingWord cannot remove a stack Proclaim may share.
- **Consumer registration.** `script.php` registers `com_livingword` with the library's consumer registry through the library's `src/consumer.php` entry point. Use `(require $file)->register(...)` behind an `is_file()` guard. Use `require`, not `require_once`, and don't call `ConsumerRegistry` directly from an install script. This stops an administrator from uninstalling the library, or dropping the shared `#__bsms_*` tables, while LivingWord still needs them. Never drop those tables from this repo.
- **`plg_system_cwmscripture`** exists for stacks like this one, which carry the library without Proclaim. It disarms the library's legacy uninstall SQL before an update can run it and wipe downloaded Bibles. `build/fetch_dependencies.php` refuses a scripture package that lacks it.

## Development Setup

The build, dev, and release pipeline is driven entirely by [`cwm-build-tools`](https://github.com/Joomla-Bible-Study/cwm-build-tools) (`^1.34` in `composer.json`), configured via `cwm-build.config.json`. The only project-specific build code is `build/fetch_dependencies.php` (downloads the latest `pkg_cwmscripture` release at build time) and `build/script.install.php` (the package install script that installs it).

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 24+ and npm 10.1+ (for frontend asset pipeline; see `engines` in `package.json`)
- A local Joomla 5 (or 6) installation for symlinked development

### Quick Start

```bash
# Install PHP + npm dependencies (auto-creates build.properties from template)
composer install
npm install

# Interactive setup wizard — writes build.properties with per-install [j5]/[j6] sections
composer setup

# Symlink the repo into your local Joomla install(s)
composer symlink

# Register libraries/plugins/modules in Joomla's #__extensions table
composer verify
```

**One-time step after first `composer verify`:** install `com_livingword` itself via Joomla's Extension Manager (Admin → Extensions → Manage → Discover). `cwm-verify` reports missing components but doesn't auto-insert them — the rest of Joomla's component lifecycle (asset rows, menu items, install SQL) only fires through the Extension Manager. Subsequent `composer verify` runs reconcile drift; pass `--fix` to repair extension state.

### Build & Test Commands

| Command | Backed by | Description |
|---------|-----------|-------------|
| `composer test` | phpunit | Run all PHPUnit tests |
| `composer test:unit` | phpunit | Unit tests only |
| `composer test:integration` | phpunit | Integration tests only |
| `composer lint` | php-cs-fixer | Check code style (PSR-12 + custom rules) |
| `composer lint:fix` | php-cs-fixer | Auto-fix code style |
| `composer lint:syntax` | `find ... \| xargs php -l` | Parallel PHP syntax check across admin/site/api/mod/plugin source |
| `composer lint-workflows` | cwm-lint-workflows | Lint the GitHub Actions workflows |
| `composer lint:js` | eslint | Lint `build/media_source/js/*.es6.js` against the CWM shared base config |
| `composer build:js` | rollup | Bundle `build/media_source/js/*.es6.js` → `media/com_livingword/js/*.{js,min.js,min.js.gz}` |
| `composer build:css` | csso | Minify `build/media_source/css/*.css` → `media/com_livingword/css/*.min.css` |
| `composer check` | composite | `lint:syntax` + `lint-workflows` + `lint` + `test` |
| `composer test:e2e` | Playwright | Browser end-to-end suite against the configured dev site (`test:a11y` for the `@a11y` scans only, `test:api` for the `api-test` project) |
| `composer test:install` / `test:upgrade` / `test:release` | `build/test-*.sh` | Release gates: clean install, upgrade from the last release, or both, on the `role = test` installs |
| `composer test:migrations` | `build/verify-migrations.php` | Assert the schema actually landed on every `role = test` install |
| `composer schema-replay` | cwm-schema-replay | Replay the migrations over the 5.0.0 baseline (see `schemaReplay` in `cwm-build.config.json`) |
| `composer seed` | `build/seed_scenarios.php` | Seed reader state (streaks, groups, partners, notes) into a dev/test install |
| `composer sync-languages` | cwm-sync-languages | Mirror en-GB keys into every locale and machine-translate new or changed strings (needs the Google Translate key from 1Password or `GOOGLE_TRANSLATE_API_KEY`) |
| `composer build` | cwm-package | Assemble `build/dist/pkg_livingword-{version}.zip` (see Build Flow below) |
| `composer setup` | cwm-setup | Interactive build.properties wizard |
| `composer symlink` | cwm-link | Symlink the repo into each configured Joomla install |
| `composer symlink:check` | cwm-link-check | Verify symlinks are still healthy |
| `composer clean` | cwm-clean | Remove symlinks |
| `composer verify` | cwm-verify | Reconcile extension state in `#__extensions` (pass `--fix` to repair) |
| `composer joomla-install` | cwm-joomla-install | Download + install Joomla into each configured path |
| `composer joomla-latest` | cwm-joomla-latest | Print the latest stable Joomla tag |
| `composer release` | cwm-release | Full release pipeline: bump → build → tag → GitHub release → ARS publish |

### Build Flow

`composer build` invokes `cwm-package`, which:

1. Runs the `preBuild` hook: `rm -rf build/dist build/vendor && npm install --no-audit --no-fund && npm run build && php build/fetch_dependencies.php`
   - npm bundles JS (rollup) and minifies CSS (csso) into `media/com_livingword/{js,css}/` — these dirs are **gitignored**; only the sources in `build/media_source/` are tracked.
   - `fetch_dependencies.php` downloads the latest `pkg_cwmscripture` release from GitHub, checks that it carries all four scripture extensions (`lib_cwmscripture`, `plg_content_scripturelinks`, `plg_task_cwmscripture`, `plg_system_cwmscripture`), and stages the package zip **whole** as `build/vendor/pkg_cwmscripture.zip` (also gitignored).
2. Builds the **self** include (`com_livingword.zip`) from `admin/`, `site/`, `api/`, `media/`, plus the root manifest/script files.
3. Builds the **inline** includes — `mod_livingword.zip`, `plg_task_livingword.zip` and `plg_webservices_livingword.zip` — in-process from their source directories (no per-extension build script).
4. Picks up the **prebuilt** `pkg_cwmscripture.zip` from `build/vendor/`.
5. Assembles the five zips into `pkg_livingword-{version}.zip` under `packages/`, with `build/pkg_livingword.xml` and `build/script.install.php` at the root. Only the four LivingWord zips are declared in the manifest. The installer script installs the scripture package itself (see [Scripture Integration](#scripture-integration)).
6. Self-verifies the output contains all 7 expected entries.

To preview a version override without committing: `composer build -- --version 5.0.1`.

### Build Properties

`build.dist.properties` is the canonical template (INI format with one `[id]` section per Joomla install). Copy to `build.properties` for local config:

```bash
cp build.dist.properties build.properties
# Then either edit by hand or run:
composer setup
```

`build.properties` is gitignored — it carries DB and admin credentials. Section names (`j5`, `j6`, etc.) must match the comma-separated `installs = ...` list at the top.

### Release Retention Policy

Old releases are kept as historical / rollback records. The bar for removing a release artifact is a **breaking bug** or **CVE** — not "stale", "outdated", or "we don't want clutter". Users may need a specific older version for rollback, version pinning, or audit.

**GitHub side — strongest preservation:**
- Never delete an entire GitHub release or tag.
- Never delete the canonical (correctly-named, correctly-versioned) asset of a release.
- Removing a *misplaced duplicate* asset is OK — e.g. if a release-pipeline bug attaches a wrong-version zip to the wrong tag, the misfile can be removed without touching history.

**ARS side — preserve by default, narrow cleanup acceptable:**
- Default is to keep old ARS download items and releases.
- Acceptable to remove only when an item/release is in a *broken state* (e.g. pointing at a deleted GitHub asset) AND the version it represents is functionally superseded by a successor release. In that case removing the whole broken ARS release is sometimes cleaner than repairing item by item. The GitHub release for the same version stays intact.

When in doubt, ask before deleting.

### Code Style

- **PSR-12** base with custom rules via `.php-cs-fixer.dist.php`
- **EditorConfig** for consistent formatting (`.editorconfig`)
- PHP and JS: 4-space indent; JSON/CSS/YAML: 2-space indent; others: tabs
- Unix line endings (LF), UTF-8, trim trailing whitespace
- JS sources live in `build/media_source/js/*.es6.js`; never edit the generated bundles under `media/com_livingword/js/`.

### CI/CD

- **GitHub Actions CI** (`.github/workflows/ci.yml`): on push/PR to `main`, runs cwm-build-tools' reusable `joomla-package-ci.yml@v1`. That covers PHP syntax lint, php-cs-fixer, ESLint, PHPUnit, migration schema replay, and a full `composer build`.
- **Nightly E2E** (`.github/workflows/e2e.yml`): Playwright suite at 04:23 UTC, plus manual dispatch
- **Release** (`.github/workflows/release.yml`): runs on `v*` tags
- **CodeQL** (`.github/workflows/codeql.yml`): Weekly security scanning
- **Auto-assign** (`.github/auto-assign.yml`): Auto-assigns reviewers to PRs

## Coding Standards

This project follows Proclaim coding standards:
- PSR-12 with CWM customizations
- PHP 8.3+ features (named arguments, match expressions, enums, readonly properties, typed constants)
- Joomla 5/6 namespaced MVC pattern
- `#[\Override]` attribute on all overridden methods
- ACL defined in `admin/access.xml`
- Use `Factory::getContainer()->get(DatabaseInterface::class)` for DB access (Joomla 6 compatible)

### Joomla 6 Compatibility Requirements

These patterns are **required** for Joomla 6 (PHP 8.3+ strict typing):

- **SQL form fields** (`type="sql"`) must always include `key_field` and `value_field` attributes. Without these, Joomla 6's `SqlField` passes `null` to `trim()`, causing PHP 8.3 deprecation warnings. Use the `query` attribute with aliased columns:
  ```xml
  <field name="plan_id" type="sql"
         key_field="value" value_field="text"
         query="SELECT id AS value, title AS text FROM #__table WHERE published = 1" />
  ```
  Do NOT use the legacy `sql_select`/`sql_value`/`sql_from` attributes — they are deprecated.

- **Admin edit templates** with `class="form-validate"` must load the form validator web asset:
  ```php
  $this->getDocument()->getWebAssetManager()->useScript('form.validate');
  ```

- **Container access** uses `Factory::getContainer()` (not `$app->getContainer()` which is protected in J6)
