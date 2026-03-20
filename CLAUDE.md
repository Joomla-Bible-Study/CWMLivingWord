# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LivingWord is a **Joomla 3.x component** (`com_livingword`) that provides Bible reading plans and resources. It integrates with BibleGateway.com for Bible text, audio versions, and translations. The component includes an admin panel, a frontend site component, a Joomla module (`mod_livingword`), and a Joomla plugin for email subscriptions.

**Version:** 3.0.0 | **License:** GNU/GPL | **Original Author:** Mike Leeper (MLWebTechnologies)

## Architecture

This follows the standard **Joomla MVC component pattern** with legacy API classes (`JControllerLegacy`, `JModelAdmin`, `JViewLegacy`, etc.).

### Top-level Layout

- `Component/` — The installable Joomla component package
  - `livingword.xml` — Joomla extension manifest (defines files, SQL scripts, menus, update server)
  - `script.php` — Install/update/uninstall script (`com_livingwordInstallerScript`)
- `Update/` — Update server XML for Joomla's extension updater

### Component Structure (MVC)

**Admin side** (`Component/admin/`):
- `livingword.php` — Admin entry point; loads helpers, boots controller
- `controller.php` — Main admin controller with tasks: `manage_plans`, `manage_books`, `manage_lang`, `manage_link`, `manage_sub`, `manage_css`, `utilities`, `addplan`, `editplan`, `addreading`, `editreading`, `addlink`, `editlink`, DB maintenance (`optimizeLWTables`, `checkLWTables`, `repairLWTables`, `backupLWTables`)
- `controllers/` — Sub-controllers for plans, links
- `models/` — Admin models (edit/manage plans, links, readings, subscribers, books, languages)
- `views/` — Admin views: `livingword` (cpanel), `editplan`, `editreading`, `editlink`, `editbook`, `editlang`, `manageplans`, `manageplan`, `managelink`, `managebooks`, `managelang`, `managesub`, `managecss`, `settings`, `utilities`
- `tables/` — Joomla table classes for DB access
- `helpers/` — `LivingWordHelper` (submenu), `admin_lw_class.php` (admin logic), `admin_lw_includes.php` (global setup), `lw_version.php`
- `elements/` — Custom Joomla form field types (version/plan selectors)
- `sql/` — MySQL install/uninstall/update scripts

**Site (frontend) side** (`Component/site/`):
- `livingword.php` — Site entry point; loads controller + helpers
- `controller.php` — Site controller with tasks: `display`, `settings`, `resources`, `view_plan`, `tools`, `createICS` (calendar export), `rss` (feed generation)
- `helpers/lw_class.php` — Core `livingword` class: user auth (`LWgetAuth`), reading plan logic, Bible version/plan data arrays, BibleGateway integration
- `helpers/lw_includes.php` — Global includes and config setup
- `views/` — Site views: `livingword` (main), `showplan` (plan display with calendar layout option), `showresources`, `showsettings`, `showtools`, `rss`

**Module** (`Component/module/`):
- `mod_livingword.php` + `helper.php` — Standalone Joomla module for displaying daily Bible reading
- A Joomla 3.0-specific version also exists in `Component/admin/module/j30/`

**Plugin** (`Component/admin/plugin/j30/`):
- Email subscription plugin that sends daily readings to subscribers

### Database Tables (MySQL, prefixed with `#__`)

| Table | Purpose |
|-------|---------|
| `#__livingword` | User settings (selected plan, version, audio pref, start date, plan view) |
| `#__livingword_links` | Curated Bible resource links with categories |
| `#__livingword_plans` | Reading plan definitions (name, description, audio/NT flags) |
| `#__livingword_plans_details` | Individual daily readings per plan (reading text, audio ref, description) |

### Key Globals

The codebase uses PHP globals extensively: `$livingword` (site-side core class instance), `$livingwordadmin` (admin-side), `$lwConfig` (component configuration array), `$bible_version`, `$bible_plan`, `$db`.

## Development Notes

- **No build system or package manager** — This is plain PHP with no Composer, npm, or build step.
- **No test suite** — There are no automated tests.
- **Joomla 3.x legacy API** — Uses deprecated classes like `JRequest`, `JError::raiseWarning`, `jimport()`. The `JFactory::getDBO()`, `JControllerLegacy`, `JModelAdmin` patterns are standard Joomla 3.x.
- **Installation** — Package the `Component/` directory as a zip and install via Joomla's Extension Manager. SQL scripts in `admin/sql/` create the required tables automatically.
- **Bible data source** — Reading content is fetched from BibleGateway.com at runtime; the `lw_class.php` helper contains all version/plan mappings and URL construction logic.
- **ACL** — Custom access levels defined in `admin/access.xml`: `livingword.home`, `livingword.links`, `livingword.settings`, `livingword.tools`.