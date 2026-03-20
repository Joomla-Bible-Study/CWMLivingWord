# CWM LivingWord Component

LivingWord (`com_livingword`) is a Joomla Bible reading plan component adopted and maintained by Christian Web Ministries (CWM). It uses CWM's shared `lib_cwmscripture` library for Bible data, scripture reference parsing, and text retrieval.

## Requirements

- Joomla 5.0+
- PHP 8.3+
- MySQL 8.0+ / MariaDB 10.4+

## Features

- Multiple Bible reading plans (Comprehensive, New Testament, Old Testament, Chronological, Biographical, Survey, Thru the Bible, NT + Psalms)
- Bible translations sourced from the shared CWM Scripture library (`lib_cwmscripture`)
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

## Development Setup

### Prerequisites

- PHP 8.3+
- [Composer](https://getcomposer.org/)
- A local Joomla 5 installation for testing

### Install Dependencies

```bash
composer install
```

This installs PHPUnit, PHP-CS-Fixer, Joomla framework packages for testing, and security advisories. Vendor files are placed in `libraries/vendor/` (matching the Proclaim convention).

### Scripture Library

The shared CWM Scripture library is included as a git submodule:

```bash
git submodule add <cwmscripture-repo-url> libraries/cwmscripture_src
git submodule update --init
```

This provides `CWM\Library\Scripture` classes for Bible data, reference parsing, and text retrieval — shared with CWM Proclaim.

## Testing

Tests use [PHPUnit 11](https://phpunit.de/) with the same structure as Proclaim:

```
tests/
├── unit/
│   ├── Admin/Helper/     # Admin helper tests
│   ├── Site/Helper/      # Site helper tests (reading calculations, etc.)
│   └── bootstrap.php
└── integration/          # Integration tests (requires DB)
```

### Run Tests

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# With phpunit directly
./libraries/vendor/bin/phpunit
./libraries/vendor/bin/phpunit --testsuite 'LivingWord Unit Tests'
```

## Code Quality

### PHP-CS-Fixer

Code style follows PSR-12 with the same rules as Proclaim (see `.php-cs-fixer.dist.php`):

```bash
# Check for style issues (dry run)
composer lint

# Auto-fix style issues
composer lint:fix
```

### Full Check

```bash
# Lint + tests
composer check
```

## Installation

Package the component directory as a zip and install via Joomla's Extension Manager. The module and task plugin are installed separately from their respective directories.

## License

GNU General Public License version 2 or later.
