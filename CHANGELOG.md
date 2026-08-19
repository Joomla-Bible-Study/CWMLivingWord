# Changelog

All notable changes to CWMLivingWord are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Milestone 1 — Engagement & Progress Tracking (planned)
- Reading completion tracking per day (`#__livingword_progress` table)
- Progress indicator: Day X of Y, percentage complete
- Reading streak tracking (current and best streak)
- Catch-up / skip UX in user Settings view
- Chapter-level completion within multi-passage days
- One-click email unsubscribe with signed token (CAN-SPAM compliance)

### Milestone 2 — Social & Accountability (planned)
- Group reading plans for church campaigns
- Accountability partner feature
- Pastor / admin congregation progress dashboard

### Milestone 3 — Content & Reading Experience (planned)
- Inline Bible text via API.Bible (with caching)
- Audio Bible playback on home view
- Devotional/reflection content display
- Short-duration plan support (3–365 day plans, self-paced mode)

### Milestone 4 — Notifications & Delivery (planned)
- User-controlled email delivery time preference
- Weekly progress digest email
- CSV bulk import for reading plan details

---

## [5.7.2] — 2026-08-19

### Fixed

- **`idx_action_token` was missing on every site installed from v5.0.0 to
  v5.5.0, and no upgrade repaired it.** Those releases shipped an `install.sql`
  that creates `#__livingword_users.action_token` without its index. The only
  file that adds the index is `5.3.0.sql`, and a fresh install of any of them
  stamps `#__schemas` at 5.4.0 or higher — so `5.3.0.sql` is below the stamp
  and never runs. v5.6.0 added the index to `install.sql`, which fixed new
  installs and left every existing one behind.

  `5.7.2.sql` adds it, guarded so sites that already have it are unaffected.

- **Three migrations were unsafe if they ever ran.** `5.1.0`–`5.4.0` are below
  the stamp every released install gets, so no released site runs them — but an
  install from the dev builds between 2026-03-20 and 2026-05-05 can be below
  them, and a hand-reset `#__schemas` reaches them too.

  `5.1.0` and `5.3.0` did unguarded `ADD COLUMN` on columns `install.sql`
  already creates: a hard error, which Joomla treats as a failed update, which
  would stop that site reaching any later migration. Both are now
  `/** CAN FAIL **/`.

  ⚠️ `5.3.0` is also **split into two statements**, deliberately. It added the
  column and its index in one `ALTER TABLE`, and the affected sites have the
  column but not the index — so a single marker on the combined statement would
  skip both and repair neither.

  `5.4.0` seeded four rows `install.sql` already seeds. `CAN FAIL` does not help
  there — the insert *succeeds* and silently duplicates — and `INSERT IGNORE`
  cannot either, since `name` carries no unique constraint. Each row is now
  guarded with `WHERE NOT EXISTS`.

  All four are now executed by `composer schema-replay` in CI.

## [5.0.0] — 2026

### Added
- Joomla 5/6 migration with namespaced MVC and PSR-4 autoloading
- PHP 8.3+ compatibility (`#[Override]` attributes, modern patterns)
- Joomla Task Scheduler plugin (`plg_task_livingword`) for daily email notifications
- Parallel Bible version comparison (`config_parallel_version`)
- Alternate audio version fallback (`config_alt_audio`)
- Frontend ACL: configurable per-view access permissions
- Admin database utilities: Optimize, Check, Repair, Backup
- Module `mod_livingword`: today's reading in any module position
- Calendar layout for full plan view
- GitHub Actions CI: PHP lint + PHPUnit on push/PR
- CodeQL weekly security scanning
- Auto-assign reviewer workflow

### Changed
- Complete rewrite from Joomla 3.x architecture
- Database tables converted from MyISAM to InnoDB
- BibleGateway URL generation moved to `CwmbiblegatewayHelper`
- Reading calculation moved to `CwmreadingHelper`
- All admin menu items rebuilt as Joomla 5 native admin views

### Removed
- All legacy `JFactory`, `JText`, `JRoute`, `JFilterOutput` patterns
- Joomla 3.x plugin architecture
- MyISAM table format

---

*Versions prior to 5.0.0 were Joomla 3.x releases maintained under the original MLWebTechnologies codebase.*
