# Changelog

All notable changes to CWMLivingWord are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Removed

- **BibleGateway.** LivingWord no longer sends readers to BibleGateway or any other external Bible site. Wherever no scripture text can be shown, the reading now appears as its plain reference. That covers a missing scripture library, the "Reference Only" display mode, a provider that didn't answer, and the plan view's day list. `CwmscriptureHelper::buildBibleGatewayLink()` and `getBibleGatewayUrl()` are gone, and `buildReadingLink()` is deprecated in favour of `buildReadingReference()`.

### Changed

- The "Link Only (BibleGateway)" scripture display mode is now "Reference Only (no scripture text)". Saved settings keep working.
- The module's "Show Reading Link" option is now "Show Scripture Text", which is what it actually switches.
- Registration with lib_cwmscripture's consumer registry goes through the library's documented `src/consumer.php` entry point. It no longer depends on the library being autoloadable, so it also succeeds on the first install, inside the same request that installs the library.
- The build refuses a bundled scripture package without `plg_system_cwmscripture`, the plugin that protects downloaded Bible translations during library updates.
- **The release gate reports the version being released.** The 5.8.0-rc1 gate read "CLEAN-INSTALL TEST PASSED for 5.7.2" and "UPGRADE TEST PASSED — 5.7.2 -> 5.7.2".
  - `test-install.sh` and `test-upgrade.sh` took the version from `versions.json` `active_development`. That was still 5.7.2, and after a pre-release it also lags the manifests.
  - They now report against `CWM_RELEASE_VERSION`, falling back to the package manifest. Until the bump they note that the artifact is still labelled with the previous version.
  - They also verify the release's schema expectations, so a migration added by the release is checked.
- **The upgrade test's baseline comes from cwm-build-tools' `cwm-baseline`.** It replaces the script's own copy of the release lookup, and the 5.6.0-beta2 floor moves to `baseline.minimum` in `cwm-build.config.json`.

### Fixed

- The "Show Scripture Text" toggle button was hard-coded English. It is now translated.
- **Warning-coloured text meets WCAG contrast.** `text-warning` (Bootstrap's light yellow) on a light background measured 1.72:1. The release gate's accessibility scan caught it on the dashboard's inactive-subscriber count, which only turns yellow on a site that has inactive subscribers. The same pattern was on the calendar view's partial-progress count and the plan form's audio-test messages. All four now use `text-warning-emphasis`, the same colour `livingword.css` already uses for warning text.
- **Scripture text for chapter ranges and comma lists.** Plan readings like "Ecclesiastes 1-3", "Romans 5-8,12" and "Luke 1:26-56,2:1-7" now get their text.
  - Readings were passed to the scripture library as raw strings, and each provider read them in its own dialect. Comma lists got nothing anywhere, and a chapter range came back as the first three verses from local translations.
  - Each passage is now split by `CwmscriptureHelper::parsePassage()` and fetched as structured references through `getPassageFor()`, the way Proclaim calls the library.
  - Whole chapters and chapter ranges served by GetBible also need lib_cwmscripture's chapter-endpoint fix (Joomla-Bible-Study/lib_cwmscripture#100). This release must ship with a scripture package that includes it.
  - Still open: API.Bible builds no passage for a whole chapter or a chapter range. On API.Bible-served translations those readings still show only their heading; that's a library follow-up.
  - Plan numbers are bounded (at most 150 chapters, 200 verses, three digits), so a mistyped reading can't push an audio or chapter loop into overflow.
- **A passage with no text is no longer dropped from the day.** If one passage of a multi-passage day can't be fetched, its heading still shows instead of vanishing. A passage counts as fetched only when every part of it returned text.
- **Audio for comma lists and cross-chapter ranges.** "Romans 5-8,12" now plays chapters 5 to 8 and 12, not only 5. "Philippians 2,4" plays both chapters instead of nothing. "2 Timothy 3:10-4:22" includes chapter 4.

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
