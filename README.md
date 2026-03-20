# CWM LivingWord Component

LivingWord (`com_livingword`) is a Joomla Bible reading plan component adopted and maintained by Christian Web Ministries (CWM). It integrates with BibleGateway.com for Bible text, audio versions, and translations.

## Requirements

- Joomla 5.0+
- PHP 8.3+
- MySQL 8.0+ / MariaDB 10.4+

## Features

- Multiple Bible reading plans (Comprehensive, New Testament, Old Testament, Chronological, Biographical, Survey, Thru the Bible, NT + Psalms)
- Bible translations in many different languages via BibleGateway.com
- Create custom reading plans through the admin panel
- Curated Bible resource links organized by category
- User preferences: plan selection, Bible version, start date, email subscription
- Calendar and list views for full plan display
- Daily reading email notifications via Joomla Task Scheduler
- Standalone module (`mod_livingword`) for displaying daily readings
- Frontend ACL: configurable access to Home, Resources, Settings, and Tools pages
- Database utilities: optimize, check, repair, backup

## Architecture

This component follows the Joomla 5 MVC architecture with PSR-4 namespaces, DI container registration, and the same coding standards as the CWM Proclaim component.

- **Namespace**: `CWM\Component\Livingword`
- **Admin**: `admin/src/` — Controllers, Models, Views, Tables, Helpers, Dispatcher, Extension
- **Site**: `site/src/` — Controllers, Models, Views, Helpers, Router, Dispatcher
- **Module**: `mod_livingword/` — Joomla 5 module with DI provider
- **Task Plugin**: `plg_task_livingword/` — Scheduled email notifications

### Legacy Code

The original Joomla 3.x codebase (v3.0.0 by Mike Leeper / MLWebTechnologies) is preserved in the `Component/` directory for reference.

## Installation

Package the component directory as a zip and install via Joomla's Extension Manager. The module and task plugin are installed separately from their respective directories.

## License

GNU General Public License version 2 or later.
