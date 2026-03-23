# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

LivingWord is a **Joomla 5+ component** (`com_livingword`) that provides Bible reading plans and resources. The component includes an admin panel, a frontend site component, a Joomla module (`mod_livingword`), and a task plugin (`plg_task_livingword`) for scheduled email delivery.

**Version:** 5.0.0 | **License:** GPL-2.0-or-later | **Maintained by:** CWM Team (Christian Web Ministries)

Originally created by Mike Leeper (MLWebTechnologies) as a Joomla 3.x component, migrated to Joomla 5 architecture with namespaced MVC, PSR-4 autoloading, and modern PHP 8.3+ patterns.

## Architecture

This follows the standard **Joomla 5 MVC component pattern** with namespaced classes under `CWM\Component\Livingword`.

### Top-level Layout

- `livingword.xml` — Joomla extension manifest (defines files, SQL scripts, menus, update server)
- `script.php` — Install/update/uninstall script
- `admin/` — Administrator component (controllers, models, views, templates, forms, SQL, language)
- `site/` — Frontend component (controllers, models, views, templates, language)
- `mod_livingword/` — Joomla module for displaying daily Bible reading
- `plg_task_livingword/` — Task plugin for scheduled email delivery
- `media/com_livingword/` — Media assets and `joomla.asset.json`
- `build/` — Build tools and scripts
- `tests/` — PHPUnit test suites (unit + integration)

### Component Structure (MVC)

**Admin side** (`admin/src/`):
- `Controller/` — Admin controllers (Cwmcpanel, Cwmplans, Cwmplan, Cwmlinks, Cwmlink, Cwmplandetails, Cwmplandetail, Cwmusers, Cwmutilities)
- `Model/` — Admin models for CRUD operations on plans, links, plan details, users
- `View/` — Admin HTML views
- `Table/` — Joomla table classes for DB access
- `Helper/` — Admin helper classes
- `Extension/` — Component extension/boot class
- `Field/` — Custom form field types

**Site (frontend) side** (`site/src/`):
- `Controller/` — Site display controller
- `Model/` — Site models
- `View/` — Site HTML views
- `Helper/` — `CwmreadingHelper` (reading plan date calculations)

### Namespace Structure

```
CWM\Component\Livingword\Administrator\  → admin/src/
CWM\Component\Livingword\Site\           → site/src/
CWM\Component\Livingword\Tests\          → tests/unit/
```

### Database Tables (MySQL, prefixed with `#__`)

| Table | Purpose |
|-------|---------|
| `#__livingword` | User settings (selected plan, version, audio pref, start date, plan view) |
| `#__livingword_links` | Curated Bible resource links with categories |
| `#__livingword_plans` | Reading plan definitions (name, description, audio/NT flags) |
| `#__livingword_plans_details` | Individual daily readings per plan (reading text, audio ref, description) |

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

# Verify extensions are registered in Joomla's database
composer verify
```

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
| `composer verify` | Verify extensions in Joomla DB |
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
- PHP 8.3+ features (named arguments, match expressions, enums, readonly properties)
- Joomla 5 namespaced MVC pattern
- ACL defined in `admin/access.xml`
