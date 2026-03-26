# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LivingWord is a **Joomla 5/6 component** (`com_livingword`) that provides Bible reading plans and resources. The component includes an admin panel, a frontend site component, a Joomla module (`mod_livingword`), and a task plugin (`plg_task_livingword`) for scheduled email delivery.

**Version:** 5.0.0 | **License:** GPL-2.0-or-later | **Maintained by:** CWM Team (Christian Web Ministries)

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
- `Extension/LivingwordComponent.php` — Boot class with runtime PHP/Joomla version verification
- `Controller/` — 10 controllers: DisplayController, Cwmcpanel, Cwmplans, Cwmplan, Cwmlinks, Cwmlink, Cwmplandetails, Cwmplandetail, Cwmusers, Cwmutilities
- `Model/` — 8 models: CwmcpanelModel, CwmplansModel, CwmplanModel, CwmlinksModel, CwmlinkModel, CwmplandetailsModel, CwmplandetailModel, CwmusersModel
- `View/` — 9 views: Cwmcpanel, Cwmplans, Cwmplan, Cwmlinks, Cwmlink, Cwmplandetails, Cwmplandetail, Cwmusers, Cwmutilities
- `Table/` — 4 table classes: CwmplanTable, CwmlinkTable, CwmplandetailTable, CwmuserTable
- `Helper/CwmlivingwordHelper.php` — ACL helper
- `Dispatcher/` — Admin request dispatcher

**Site (frontend) side** (`site/src/`):
- `Controller/DisplayController.php` — Routes to views, enables caching for guests
- `Model/` — 5 models: CwmhomeModel, CwmplanviewModel, CwmresourcesModel, CwmsettingsModel, CwmtoolsModel
- `View/` — 5 views with `prepareDocument()` for page titles: Cwmhome, Cwmplanview, Cwmresources, Cwmsettings, Cwmtools
- `Helper/` — 4 helpers: CwmreadingHelper (date math), CwmbiblegatewayHelper (URLs), CwmuserHelper (prefs), CwmmenuHelper (nav)
- `Service/Router.php` — URL routing
- `Dispatcher/` — Site request dispatcher

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
| `#__livingword` | User settings (userid, bibleplan, bibleversion, startdate, email subscription, planview) |
| `#__livingword_links` | Curated Bible resource links (name, url, category, target, published, ordering) |
| `#__livingword_plans` | Reading plan definitions (name slug, description, message, audio, newtest flags) |
| `#__livingword_plans_details` | Individual daily readings per plan (plan FK, reading ref, audio, ordering) |

### Key Patterns

- **Admin list views** use `GenericDataException` for error handling with `$model->setUseExceptions(true)`
- **Admin edit views** use `GenericDataException`, set layout to 'edit', hide main menu
- **Site views** use `prepareDocument()` to set document title respecting Joomla's `sitename_pagetitles` config
- **Filter forms** must be named `filter_cwm{viewname}.xml` (Joomla auto-derives from model class name)
- **DB maintenance commands** (OPTIMIZE/CHECK/REPAIR) use `$db->getConnection()->query()` since prepared statements don't support DDL
- **Container access** uses `Factory::getContainer()` (not `$app->getContainer()` which is protected in J6)

## Development Setup

### Prerequisites

- PHP 8.3+
- Composer
- A local Joomla 5 (or 6) installation for symlinked development

### Quick Start

```bash
# Install dependencies (auto-creates build.properties from template)
composer install

# Interactive setup wizard (configure Joomla paths, dev site URLs, DB credentials)
composer setup

# Create symlinks to your local Joomla installation(s)
composer symlink

# Register extensions in Joomla database (creates tables, menus, namespace map)
composer verify
```

After `symlink + verify`, the component is fully installed. No browser-based installation needed.

### What `composer verify` Does

1. Inserts `#__extensions` row for component and plugin
2. Creates `#__assets` ACL record
3. Creates admin menu items (dashboard, plans, links, subscribers)
4. Runs `install.mysql.utf8.sql` to create all 4 database tables
5. Inserts `#__schemas` version record
6. Adds PSR-4 namespace entries to `administrator/cache/autoload_psr4.php`

### Build & Test Commands

| Command | Description |
|---------|-------------|
| `composer test` | Run all PHPUnit tests |
| `composer test:unit` | Run unit tests only |
| `composer test:integration` | Run integration tests only |
| `composer lint` | Check code style (PSR-12 + custom rules) |
| `composer lint:fix` | Auto-fix code style |
| `composer lint:syntax` | Check PHP syntax errors |
| `composer check` | Run syntax check + lint + tests |
| `composer build` | Build installable ZIP package |
| `composer setup` | Interactive dev environment setup |
| `composer symlink` | Create symlinks to Joomla |
| `composer clean` | Remove symlinks |
| `composer verify` | Verify/register extensions in Joomla DB |
| `composer joomla-install` | Download and install Joomla |
| `composer joomla-latest` | Show latest Joomla version |

### Code Style

- **PSR-12** base with custom rules via `.php-cs-fixer.dist.php`
- **EditorConfig** for consistent formatting (`.editorconfig`)
- PHP: 4-space indent; JS/JSON/CSS/YAML: 2-space indent; others: tabs
- Unix line endings (LF), UTF-8, trim trailing whitespace

### CI/CD

- **GitHub Actions CI** (`.github/workflows/ci.yml`): PHP lint + PHPUnit on push/PR
- **CodeQL** (`.github/workflows/codeql.yml`): Weekly security scanning
- **Auto-assign** (`.github/auto-assign.yml`): Auto-assigns reviewers to PRs

### Build Properties

The `build.dist.properties` file is the template. Copy to `build.properties` (gitignored) for local config. Supports multiple Joomla installations (comma-separated paths) for simultaneous J5/J6 development.

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
