# CWM LivingWord

LivingWord (`com_livingword`) is a Joomla 5/6 Bible reading plan component maintained by Christian Web Ministries (CWM). It guides readers through scripture on a daily schedule, tracks their progress, and supports group reading, accountability partners, and email delivery.

Originally created by Mike Leeper (MLWebTechnologies) as a Joomla 3.x component, it has been rebuilt for Joomla 5+ with namespaced MVC, PSR-4 autoloading, and PHP 8.3+ patterns.

**License:** GPL-2.0-or-later | **Current version:** see [Releases](https://github.com/Joomla-Bible-Study/CWMLivingWord/releases) (`build/versions.json` in the repo)

## Requirements

- Joomla 5.x or 6.x
- PHP 8.3+
  - PHP 8.3 is supported until Proclaim 11 is released. From then on, the CWM extensions, this one included, require PHP 8.4 or later.
- MySQL 8.0+ / MariaDB 10.4+

## What's in the Package

Install `pkg_livingword-{version}.zip` through Joomla's Extension Manager. It contains:

| Extension | Purpose |
|-----------|---------|
| `com_livingword` | The component: admin interface, frontend views, and the REST API |
| `mod_livingword` | Today's reading in any module position |
| `plg_task_livingword` | Scheduled emails: daily reading, weekly progress digest, accountability-partner digest |
| `plg_webservices_livingword` | Registers the LivingWord REST API routes |
| `pkg_cwmscripture` (bundled) | The CWM Scripture library and its content, task and system plugins, which supply scripture text and audio |

The scripture package is shared with [CWM Proclaim](https://github.com/Joomla-Bible-Study/Proclaim). The LivingWord installer installs it only when no equal or newer copy is present, and uninstalling LivingWord leaves it in place.

## Features

- **Reading plans** — 8 built-in plans (Biographical, Chronological, Comprehensive, New Testament, Old Testament, New Testament & Psalms, Survey, Thru the Bible), plus custom plans. Plans can be annual, fixed-length, or self-paced, and days can carry devotional text. Plan days can be bulk-imported from CSV.
- **Daily reading** — today's reading for each reader, worked out from their plan and start date, with per-passage "mark as read", streak tracking, and a list or calendar view of the whole plan.
- **Scripture text and audio** — inline Bible text and audio playback through the bundled CWM Scripture library (see [Scripture Text and Audio](#scripture-text-and-audio)).
- **Journal** — per-day reflection notes.
- **Groups** — reading-plan groups with leaders and members, joined through a shareable invite link.
- **Accountability partners** — readers can pair up and receive a weekly digest of each other's progress.
- **Email** — the daily reading arrives at each reader's preferred hour in their own timezone. Readers can mark a reading as read with one click from the email, and every email carries a token-based unsubscribe link.
- **Resources and study tools** — curated link and study-tool catalogs organized with Joomla categories. Plans, links and groups can be tagged.
- **REST API** — reading plans and per-user progress over the Joomla Web Services API, for mobile and third-party clients.
- **Frontend ACL** — per-view access permissions.
- **Database utilities** — optimize, check, repair, and SQL backup from the admin.

## Scripture Text and Audio

Scripture comes from the **CWM Scripture library** (`lib_cwmscripture`), which LivingWord treats as an optional dependency:

- **Text** comes from locally downloaded translations, GetBible.net, or API.Bible. **Audio** comes from Bible Brain.
- Providers, API keys, GDPR mode and the translation manager are configured in the **Content - ScriptureLinks** plugin (`plg_content_scripturelinks`), not in LivingWord.
- The component option **Scripture Display Mode** chooses how a reading appears: *Inline*, *Toggle* (click to show), or *Reference Only*.
- LivingWord does not send readers to external Bible websites. When scripture text is unavailable (the library is missing, the mode is Reference Only, or no provider answered), the reading is shown as its plain reference, for example "Genesis 1-3".

## Scheduled Tasks

`plg_task_livingword` adds three task types to **System → Scheduled Tasks**:

| Task | What it sends | Suggested schedule |
|------|---------------|--------------------|
| LivingWord: Send Daily Reading Emails | Today's reading to every subscriber whose preferred hour has arrived in their timezone | **Every hour.** Each run mails only the readers whose hour it is. |
| LivingWord: Send Weekly Progress Digest | Readings completed, streak and pace for each subscriber | Weekly |
| LivingWord: Send Weekly Partner Progress Digest | Each accountability partner's progress | Weekly |

Emails use the branding set in the component's Email options (logo, organization name, brand color, footer).

## REST API

`plg_webservices_livingword` registers routes under `/api/index.php/v1/livingword/`. Enabling the plugin turns the API on.

| Resource | Methods | Access |
|----------|---------|--------|
| `plans`, `plandays`, `tools`, `links`, `groups` | `GET` (list, item) | Read-only catalog. Requires an API token unless the plugin's **Public catalog reads** option is on. |
| `progress`, `notes` | `GET`, `POST`, `PATCH`, `DELETE` | Always authenticated; scoped to the current user |
| `settings` | `GET`, `POST`, `PATCH` | Always authenticated; the current user's single settings record |

```bash
curl -H "X-Joomla-Token: <your API token>" \
     https://example.org/api/index.php/v1/livingword/plans
```

## Frontend Views

| View | URL param | What it shows |
|------|-----------|---------------|
| Home | `view=cwmhome` | Today's reading, progress, streak, journal, and audio player |
| Plan View | `view=cwmplanview` | The whole plan as a list or calendar |
| Resources | `view=cwmresources` | Bible resource links grouped by category |
| Settings | `view=cwmsettings` | Reader preferences (plan, Bible and audio version, start date, email opt-in and hour, timezone, accountability partner, progress sharing) |
| Tools | `view=cwmtools` | Bible study tools |
| Groups | `view=cwmgroups`, `view=cwmgroupdetail` | Reading groups and their members' progress |

Three landing pages are reached from links rather than menus: `cwminvite` (group invitations), and `cwmcomplete` and `cwmunsubscribe` (from emails).

## Configuration

Component options (**System → Global Configuration → LivingWord**):

| Group | Options |
|-------|---------|
| General | Global start date, default Bible version, default plan, plan template (list or calendar), enable email, print icon, scripture display mode, audio player, default audio version |
| Email | Logo URL, organization name, brand color, custom footer message |
| Menu | Show the LivingWord navigation menu on the frontend |
| Permissions | Standard Joomla actions, plus frontend access to Home, Settings, Tools and Links, and group administration (`livingword.groups.admin`) |

The module has one option, **Show Scripture Text**. When it is on, the module shows the day's scripture according to the component's display mode. When it is off, the module shows only the reading reference.

## Development

Prerequisites: PHP 8.5 (the shipped code supports 8.3+), Composer, Node.js 24+ / npm 10.1+, and a local Joomla 5 or 6 site.

```bash
composer install     # PHP deps, incl. cwm-build-tools (creates build.properties)
npm install          # frontend build deps
composer setup       # interactive wizard: Joomla paths, DB and admin credentials
composer symlink     # symlink the repo into each configured Joomla site
composer verify      # reconcile extension rows in #__extensions (--fix to repair)
```

After the first `composer verify`, install `com_livingword` itself through **System → Install → Discover**.

| Command | Description |
|---------|-------------|
| `composer check` | Syntax lint, workflow lint, code style, and PHPUnit |
| `composer test` | PHPUnit (unit + integration) |
| `composer test:e2e` | Playwright end-to-end suite (`test:a11y`, `test:api` for subsets) |
| `composer build` | Build `build/dist/pkg_livingword-{version}.zip` |
| `composer release` | Full release pipeline (bump, build, tag, GitHub release, ARS publish) |

The build, dev and release tooling comes from [cwm-build-tools](https://github.com/Joomla-Bible-Study/cwm-build-tools). See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow and conventions, and [CLAUDE.md](CLAUDE.md) for the architecture in depth.

### Repository Layout

| Path | Contents | Namespace |
|------|----------|-----------|
| `admin/` | Administrator component | `CWM\Component\Livingword\Administrator\` |
| `site/` | Frontend component | `CWM\Component\Livingword\Site\` |
| `api/` | Web Services API controllers and views | `CWM\Component\Livingword\Api\` |
| `mod_livingword/` | Daily reading module | `CWM\Module\Livingword\Site\` |
| `plg_task_livingword/` | Scheduled email task plugin | `CWM\Plugin\Task\Livingword\` |
| `plg_webservices_livingword/` | API route registration | `CWM\Plugin\WebServices\Livingword\` |
| `build/` | Package manifest and installer, build and test scripts, frontend sources (`build/media_source/`) | |
| `tests/` | PHPUnit (`unit/`, `integration/`) and Playwright (`e2e/`) suites | |

## License

GNU General Public License version 2 or later.
