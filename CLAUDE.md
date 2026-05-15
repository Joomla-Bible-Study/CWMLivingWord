# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LivingWord is a **Joomla 5/6 component** (`com_livingword`) that provides Bible reading plans, study tools, group reading, and email delivery. The package ships a component (`com_livingword`), a Joomla module (`mod_livingword`) for daily reading display, and a task plugin (`plg_task_livingword`) with three scheduled routines: daily reading email, weekly progress digest, and accountability-partner digest.

**License:** GPL-2.0-or-later | **Maintained by:** CWM Team (Christian Web Ministries) | **Current version:** see `build/versions.json` (single source of truth)

Originally created by Mike Leeper (MLWebTechnologies) as a Joomla 3.x component, migrated to Joomla 5 architecture with namespaced MVC, PSR-4 autoloading, and modern PHP 8.3+ patterns.

## Architecture

This follows the standard **Joomla 5/6 MVC component pattern** with namespaced classes under `CWM\Component\Livingword`.

### Top-level Layout

- `livingword.xml` — Joomla extension manifest (Joomla 5+6 compatibility, PHP 8.3+)
- `script.php` — Install/update/uninstall script with version checks
- `admin/` — Administrator component (controllers, models, views, templates, forms, SQL, language)
- `site/` — Frontend component (controllers, models, views, templates, language)
- `mod_livingword/` — Joomla module for displaying daily Bible reading
- `plg_task_livingword/` — Task plugin for scheduled email delivery
- `build/` — Build tools and scripts
- `tests/` — PHPUnit test suites (unit + integration)

### Component Structure (MVC)

**Admin side** (`admin/src/`):
- `Extension/LivingwordComponent.php` — Boot class with runtime PHP/Joomla version verification + `CategoryServiceInterface` integration (study tools use `com_categories`)
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
- `Model/` — 12 models, one per CRUD entity (no model for Audio, Utilities, or DisplayController)
- `View/` — 13 views; each pairs `admin/src/View/Cwm*/HtmlView.php` with `admin/tmpl/cwm*/{default,edit}.php`
- `Table/` — 6 table classes: `CwmplanTable`, `CwmplandetailTable`, `CwmlinkTable`, `CwmtoolTable`, `CwmgroupTable`, `CwmuserTable`
- `Field/` — 5 custom form fields: `BibleVersionField`, `PlanField`, `PlanIdField`, `LinkCategoryField`, `LinkCategoryFilterField` (replace Joomla's `sql` field for J6 compatibility — see Key Patterns)
- `Helper/CwmlivingwordHelper.php` — ACL helper
- `Dispatcher/Dispatcher.php` — admin dispatcher; default controller = `cwmcpanel`

**Site (frontend) side** (`site/src/`):
- `Controller/` — 8 controllers:
  - `DisplayController` — view dispatcher with guest caching
  - `CwmsubscribeController`, `CwmunsubscribeController` — email opt-in / token-based unsubscribe
  - `CwmcompleteController` — one-click "mark as read" from email
  - `CwmprogressController` — AJAX progress tracking
  - `CwmnotesController` — per-day reflection notes
  - `CwmgroupController` — group join/leave
  - `CwmaudioController` — audio URL fetcher
- `Model/` — 7 models: `CwmhomeModel`, `CwmplanviewModel`, `CwmresourcesModel`, `CwmsettingsModel`, `CwmtoolsModel`, `CwmgroupsModel`, `CwmgroupdetailModel`
- `View/` — 9 views with `prepareDocument()` for page titles: `Cwmhome`, `Cwmplanview`, `Cwmresources`, `Cwmsettings`, `Cwmtools`, `Cwmgroups`, `Cwmgroupdetail`, plus `Cwmcomplete` and `Cwmunsubscribe` (email-only landing pages — intentionally unrouted)
- `Helper/` — 9 helpers:
  - `CwmreadingHelper` — date math, plan-day calculations
  - `CwmuserHelper` — user prefs, token generation, action URLs
  - `CwmmenuHelper` — navigation
  - `CwmprogressHelper` — completion tracking, streaks
  - `CwmscriptureHelper` — passage rendering via `lib_cwmscripture`
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
CWM\Module\Livingword\Site\              → mod_livingword/src/
CWM\Plugin\Task\Livingword\              → plg_task_livingword/src/
```

### Database Tables (MySQL, prefixed with `#__`)

| Table | Purpose |
|-------|---------|
| `#__livingword_plans` | Reading plan definitions (alias, title, description, message, audio settings, testament filter, duration_type, total_days) |
| `#__livingword_plans_details` | Daily readings per plan (plan FK, reading ref, optional audio override, devotional `descrip` text) |
| `#__livingword_users` | Per-user settings: plan FK, bible/audio version, email opt-in, planview mode, start_date, streak tracking, accountability partner, unsubscribe + action tokens, timezone, preferred email hour |
| `#__livingword_progress` | Completed readings (user FK, plan FK, day, passage_index, completed_at) |
| `#__livingword_links` | Curated Bible resource links (name, url, category, target, published, ordering) |
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

## Development Setup

The build, dev, and release pipeline is driven entirely by [`cwm-build-tools`](https://github.com/Joomla-Bible-Study/cwm-build-tools) v1.0+, configured via `cwm-build.config.json`. The only project-specific build code is `build/fetch_dependencies.php` (downloads the latest `pkg_cwmscripture` release at build time).

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ and npm 10+ (for frontend asset pipeline)
- A local Joomla 5 (or 6) installation for symlinked development

### Quick Start

```bash
# Install PHP + npm dependencies (auto-creates build.properties from template)
composer install
npm install

# Interactive setup wizard — writes build.properties with per-install [j5]/[j6] sections
composer setup

# Symlink the repo into your local Joomla install(s)
composer link

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
| `composer lint:syntax` | `find ... \| xargs php -l` | Parallel PHP syntax check across admin/site/mod/plugin source |
| `composer lint:js` | eslint | Lint `build/media_source/js/*.es6.js` against the CWM shared base config |
| `composer build:js` | rollup | Bundle `build/media_source/js/*.es6.js` → `media/com_livingword/js/*.{js,min.js,min.js.gz}` |
| `composer build:css` | csso | Minify `build/media_source/css/*.css` → `media/com_livingword/css/*.min.css` |
| `composer check` | composite | `lint:syntax` + `lint` + `test` |
| `composer build` | cwm-package | Assemble `build/dist/pkg_livingword-{version}.zip` (see Build Flow below) |
| `composer setup` | cwm-setup | Interactive build.properties wizard |
| `composer link` | cwm-link | Symlink the repo into each configured Joomla install |
| `composer link-check` | cwm-link-check | Verify symlinks are still healthy |
| `composer clean` | cwm-clean | Remove symlinks |
| `composer verify` | cwm-verify | Reconcile extension state in `#__extensions` (pass `--fix` to repair) |
| `composer joomla-install` | cwm-joomla-install | Download + install Joomla into each configured path |
| `composer joomla-latest` | cwm-joomla-latest | Print the latest stable Joomla tag |
| `composer release` | cwm-release | Full release pipeline: bump → build → tag → GitHub release → ARS publish |

### Build Flow

`composer build` invokes `cwm-package`, which:

1. Runs the `preBuild` hook: `npm install --no-audit --no-fund && npm run build && php build/fetch_dependencies.php`
   - npm bundles JS (rollup) and minifies CSS (csso) into `media/com_livingword/{js,css}/` — these dirs are **gitignored**; only the sources in `build/media_source/` are tracked.
   - `fetch_dependencies.php` downloads the latest `pkg_cwmscripture` release from GitHub and extracts its 3 inner zips into `build/vendor/` (also gitignored).
2. Builds the **self** include (`com_livingword.zip`) from `admin/`, `site/`, `media/`, plus the root manifest/script files.
3. Builds the **inline** includes — `mod_livingword.zip` and `plg_task_livingword.zip` — in-process from their source directories (no per-extension build script).
4. Picks up the 3 **prebuilt** scripture zips from `build/vendor/`.
5. Assembles all six into `pkg_livingword-{version}.zip` under `packages/`, with `build/pkg_livingword.xml` at the root.
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
- PHP: 4-space indent; JS/JSON/CSS/YAML: 2-space indent; others: tabs
- Unix line endings (LF), UTF-8, trim trailing whitespace
- JS sources live in `build/media_source/js/*.es6.js`; never edit the generated bundles under `media/com_livingword/js/`.

### CI/CD

- **GitHub Actions CI** (`.github/workflows/ci.yml`): PHP lint + PHPUnit on push/PR
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
