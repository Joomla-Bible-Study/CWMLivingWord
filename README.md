# CWM LivingWord Component

LivingWord (`com_livingword`) is a Joomla 5/6 Bible reading plan component maintained by Christian Web Ministries (CWM). Originally created by Mike Leeper (MLWebTechnologies) as a Joomla 3.x component, it has been migrated to Joomla 5+ architecture with namespaced MVC, PSR-4 autoloading, and PHP 8.3+ patterns.

**Version:** 5.0.0 | **License:** GPL-2.0-or-later

## Requirements

- Joomla 5.0+ or 6.0+
- PHP 8.3+
- MySQL 8.0+ / MariaDB 10.4+

## What It Does

LivingWord provides structured Bible reading plans that guide users through scripture on a daily schedule. The system includes:

- **8 built-in reading plans**: Comprehensive, New Testament, Old Testament, Chronological, Biographical, Survey, Thru the Bible, NT + Psalms
- **Custom plans**: Administrators can create and manage their own reading plans
- **Daily reading**: Calculates today's reading based on user start date and plan progress
- **User preferences**: Each user selects their plan, Bible version, start date, and email preference
- **Email notifications**: Daily reading emails sent via Joomla's Task Scheduler
- **Resource links**: Curated Bible study links organized by category
- **Module**: Standalone Joomla module displays today's reading in any module position
- **Calendar & list views**: Two ways to see the full reading plan
- **Frontend ACL**: Configurable access to Home, Resources, Settings, and Tools pages
- **Database utilities**: Admin tools for optimize, check, repair, and backup

## Architecture Overview

```
com_livingword/
├── livingword.xml              # Joomla extension manifest
├── script.php                  # Install/update/uninstall script
│
├── admin/                      # Administrator component
│   ├── services/provider.php   # DI container registration
│   ├── src/
│   │   ├── Extension/          # LivingwordComponent (boot, version checks)
│   │   ├── Controller/         # 10 controllers (CRUD + utilities)
│   │   ├── Model/              # 8 models (list + edit for plans, links, readings, users)
│   │   ├── View/               # 9 views (dashboard, lists, edit forms, utilities)
│   │   ├── Table/              # 4 table classes (DB access)
│   │   ├── Helper/             # ACL helper
│   │   └── Dispatcher/         # Admin dispatcher
│   ├── tmpl/                   # 9 admin templates
│   ├── forms/                  # 4 edit forms + 4 filter forms (XML)
│   ├── sql/                    # Install, uninstall, update SQL
│   ├── language/en-GB/         # Admin language strings
│   ├── access.xml              # ACL definitions
│   └── config.xml              # Component configuration
│
├── site/                       # Frontend component
│   ├── src/
│   │   ├── Controller/         # Display controller
│   │   ├── Model/              # 5 models (home, plan view, resources, settings, tools)
│   │   ├── View/               # 5 views with prepareDocument() for page titles
│   │   ├── Helper/             # 4 helpers (reading calc, BibleGateway, user, menu)
│   │   ├── Dispatcher/         # Site dispatcher
│   │   └── Service/            # URL router
│   ├── tmpl/                   # 6 site templates (includes calendar layout)
│   └── language/en-GB/         # Site language strings
│
├── mod_livingword/             # Joomla module (today's reading widget)
│   ├── src/Helper/             # Gets today's reading for module display
│   ├── src/Dispatcher/         # Module dispatcher
│   ├── tmpl/default.php        # Module template
│   └── mod_livingword.xml      # Module manifest
│
├── plg_task_livingword/        # Task plugin (email notifications)
│   ├── src/Extension/          # Sends daily reading emails to subscribers
│   └── livingword.xml          # Plugin manifest
│
├── build/                      # Build tools
│   └── livingword_build.php    # Build/dev automation script
│
└── tests/                      # PHPUnit test suites
    ├── unit/                   # Unit tests
    └── integration/            # Integration tests
```

## Namespace Structure

| Namespace | Maps to |
|-----------|---------|
| `CWM\Component\Livingword\Administrator\` | `admin/src/` |
| `CWM\Component\Livingword\Site\` | `site/src/` |
| `CWM\Module\Livingword\Site\` | `mod_livingword/src/` |
| `CWM\Plugin\Task\Livingword\` | `plg_task_livingword/src/` |

## Database Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `#__livingword` | User settings/preferences | userid, bibleplan, bibleversion, startdate, email |
| `#__livingword_plans` | Reading plan definitions | name (slug), description, audio, newtest |
| `#__livingword_plans_details` | Individual daily readings per plan | plan (FK to name), reading, audio, ordering |
| `#__livingword_links` | Curated Bible resource links | name, url, category, target |

### How Reading Plans Work

Each plan has numbered readings stored in `#__livingword_plans_details`, ordered by the `ordering` column. The system calculates which reading to show today:

1. User has a `startdate` and optional `dateoffset` in `#__livingword`
2. `CwmreadingHelper::getCurrentReadingDay()` computes days elapsed since start
3. Day number wraps around `totalDays` so plans repeat annually
4. `getReadingForDay()` fetches the reading at that offset position

Reading references use an internal format: `LWBIBLEBOOK25 1-3;LWBIBLEBOOK50 12` which `CwmbiblegatewayHelper::parseReadingReference()` converts to human-readable passages and BibleGateway.com URLs.

## Admin Interface

### Dashboard (cwmcpanel)
Shows quick stats: published plans count, published links count, total subscribers.

### Plans Management (cwmplans → cwmplan)
- List all reading plans with publish/unpublish, ordering, trash
- Edit plan: name (slug), description, message, audio support, testament type
- Each plan links to its readings list

### Readings Management (cwmplandetails → cwmplandetail)
- List daily readings filtered by plan
- Edit reading: plan assignment, reading reference, audio reference, figures, description
- Ordering determines the day sequence within the plan

### Links Management (cwmlinks → cwmlink)
- Curated Bible resource links with categories
- Edit: name, URL (auto-prepends https://), category, target window, publish state

### Subscribers (cwmusers)
- Read-only list of users who have configured LivingWord preferences
- Shows username, selected plan, Bible version, email subscription status

### Utilities (cwmutilities)
- **Optimize** — runs `OPTIMIZE TABLE` on all 4 tables
- **Check** — runs `CHECK TABLE` for integrity verification
- **Repair** — runs `REPAIR TABLE` for corruption recovery
- **Backup** — downloads SQL dump of all LivingWord data

## Frontend Views

| View | URL param | What it shows |
|------|-----------|---------------|
| Home | `view=cwmhome` | Today's reading with BibleGateway link |
| Plan View | `view=cwmplanview` | Full plan (list or calendar layout based on user/config preference) |
| Resources | `view=cwmresources` | Bible resource links grouped by category |
| Settings | `view=cwmsettings` | User preferences form (plan, version, start date, email) |
| Tools | `view=cwmtools` | Bible study tools page |

Frontend navigation is built by `CwmmenuHelper::buildMenu()` and respects ACL permissions defined in `access.xml`.

## Module: mod_livingword

Displays today's reading in any Joomla module position. Gets the current user's plan and calculates today's reading using the same helpers as the component.

**Parameter:** `show_reading_link` (yes/no) — whether to show a link to the full reading.

## Task Plugin: plg_task_livingword

Registered as a Joomla Task Scheduler task (`livingword.email_notifications`). When triggered:

1. Queries all users with `email=1` who aren't blocked
2. For each subscriber, calculates their current reading day
3. Builds an HTML email with the reading text and BibleGateway link
4. Sends via Joomla's mailer

## Component Configuration (admin/config.xml)

| Setting | Key | Description |
|---------|-----|-------------|
| Global Start Date | `config_startdate` | Default start date for new users |
| Default Bible Version | `config_version` | Default version code (NLT, ESV, KJV, etc.) |
| Parallel Version | `config_parallel_version` | Optional parallel version for comparison |
| Show Audio Icon | `config_show_audio` | Show audio icon when version supports it |
| Alternate Audio Version | `config_alt_audio` | Fallback audio version |
| Default Plan | `config_plan` | Default plan slug (comp, newtest, bio, etc.) |
| Plan Template | `config_plan_template` | Full plan display: "default" (list) or "calendar" |
| Enable Email | `config_enable_email` | Enable daily reading email feature |
| Show Menu | `config_show_menu` | Show LivingWord navigation menu on frontend |

## ACL Permissions (admin/access.xml)

| Permission | Controls |
|------------|----------|
| `core.admin` | Component configuration access |
| `core.manage` | Admin panel access |
| `core.create` | Create plans, links |
| `core.delete` | Delete plans, links |
| `core.edit` | Edit plans, links |
| `core.edit.state` | Publish/unpublish/trash |
| `livingword.home` | Frontend home page access |
| `livingword.settings` | Frontend settings access |
| `livingword.tools` | Frontend tools access |
| `livingword.links` | Frontend resource links access |

## Development Setup

### Prerequisites

- PHP 8.3+
- [Composer](https://getcomposer.org/)
- A local Joomla 5 or 6 installation
- MySQL accessible from CLI (for `composer verify`)

### Quick Start

```bash
# Install dependencies
composer install

# Interactive setup (configure Joomla paths, DB credentials)
composer setup

# Create symlinks to your local Joomla installation(s)
composer symlink

# Register extensions in Joomla database (creates tables, menus, namespace map)
composer verify
```

After `composer symlink && composer verify`, the component is fully installed and live-linked to your source code. Any file changes are immediately reflected in Joomla.

### How Dev Installation Works

The `composer verify` command does the following for each configured Joomla path:

1. **Plugins** — Inserts into `#__extensions` if missing
2. **Component** — Full registration if missing:
   - Inserts `#__extensions` row with namespace
   - Creates `#__assets` ACL record
   - Creates 5 admin menu items (dashboard, plans, links, subscribers)
   - Runs `install.mysql.utf8.sql` to create all 4 tables
   - Inserts `#__schemas` version record
3. **Namespace map** — Adds PSR-4 entries to `administrator/cache/autoload_psr4.php` so Joomla can autoload component classes

### Multiple Joomla Installations

`build.properties` supports comma-separated paths for simultaneous J5/J6 development:

```properties
builder.joomla_paths=/path/to/j5-dev,/path/to/j6-dev
```

All commands (symlink, verify, clean) operate on every configured path.

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
- PHP: 4-space indent | JS/JSON/CSS/YAML: 2-space indent
- Unix line endings (LF), UTF-8, trim trailing whitespace

### CI/CD

- **GitHub Actions CI** (`.github/workflows/ci.yml`): PHP lint + PHPUnit on push/PR
- **CodeQL** (`.github/workflows/codeql.yml`): Weekly security scanning
- **Auto-assign** (`.github/auto-assign.yml`): Auto-assigns reviewers

## Joomla Compatibility

| Feature | Joomla 5 | Joomla 6 |
|---------|----------|----------|
| Namespaced MVC | Yes | Yes |
| PSR-4 autoloading | Yes | Yes |
| `#[\Override]` attribute | Yes | Yes |
| GenericDataException | Yes | Yes |
| Factory::getContainer() | Yes | Yes |
| prepareDocument() in site views | Yes | Yes |

Runtime version checks in `LivingwordComponent::boot()` verify PHP 8.3+ and Joomla 5.0+ at component load time. The manifest declares compatibility with both Joomla 5.x and 6.x.

## License

GNU General Public License version 2 or later.
