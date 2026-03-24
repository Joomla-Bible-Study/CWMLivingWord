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
