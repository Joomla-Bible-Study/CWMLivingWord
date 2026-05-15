# CWMLivingWord — Project Status

**Repository:** [`Joomla-Bible-Study/CWMLivingWord`](https://github.com/Joomla-Bible-Study/CWMLivingWord)
**Component:** `com_livingword` (Joomla 5/6 Bible Reading Plan)
**Maintainers:** [@bcordis](https://github.com/bcordis), [@tomfuller2](https://github.com/tomfuller2)
**Current release:** v5.0.1 (2026-05-14)
**Last status update:** 2026-05-14

> This document used to be a forward-looking roadmap. As of 2026-05, the original four-milestone plan has substantially shipped — the doc is now a snapshot of *what landed*, *what's still open*, and *where to file new work*.

---

## Status snapshot

| Milestone | Theme | Closed | Open |
|---|---|---|---|
| M1 | Engagement & Progress Tracking | 7 | 0 |
| M2 | Social & Accountability | 3 | 0 |
| M3 | Content & Reading Experience | 8 | **2** |
| M4 | Notifications & Delivery | 4 | 0 |
| — | Other improvements | 4 | 1 |

Source of truth: [GitHub Issues](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues) and [Milestones](https://github.com/Joomla-Bible-Study/CWMLivingWord/milestones).

---

## Shipped

### Milestone 1 — Engagement & Progress Tracking ✅

Closed via the daily-reading completion flow, progress UI, streaks, and CAN-SPAM compliance.

- [#3](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/3) Per-day reading completion tracking (`#__livingword_progress` table + `CwmcompleteController`)
- [#4](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/4) Progress indicator (% complete + day count)
- [#5](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/5) Reading streak tracking (current / best / last-date columns + `livingword-progress.js`)
- [#6](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/6) Catch-up / skip UX improvement
- [#7](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/7) Chapter-level completion within a day's reading (passage-toggle data attributes in `cwmhome`)
- [#8](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/8) One-click email unsubscribe (`CwmunsubscribeController` + token in `#__livingword_users`)
- [#60](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/60) Home page redesign with dashboard and engagement flow

### Milestone 2 — Social & Accountability ✅

Group functionality plus an admin congregation view.

- [#9](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/9) Group reading plans (`#__livingword_groups`, `#__livingword_group_members`, `CwmgroupController`, `Cwmgroups`/`Cwmgroupdetail` views)
- [#10](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/10) Accountability partner feature
- [#11](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/11) Pastor / admin congregation progress dashboard

### Milestone 3 — Content & Reading Experience (mostly shipped)

Inline Bible text, audio, devotional content, and reading-experience redesign. Two refactors still open — see [Open work](#open-work).

- [#12](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/12) Inline Bible text via bundled `lib_cwmscripture`
- [#13](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/13) Audio Bible playback integration (`CwmaudioController`, `livingword-audio.js`/`.css`)
- [#14](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/14) Devotional/reflection content per reading day
- [#15](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/15) Short-duration plan support (3–21 day plans)
- [#28](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/28) Frontend reading experience redesign
- [#56](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/56) Plan view monthly accordion grouping
- [#58](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/58) Reading notes/journal per daily reading (`#__livingword_notes` + `CwmnotesController` + `livingword-notes.js`)
- [#59](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/59) Enhanced study/devotional text display

### Milestone 4 — Notifications & Delivery ✅

Email scheduling, digest, and admin bulk-import.

- [#16](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/16) User-controlled email delivery time preference
- [#17](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/17) Weekly progress digest email
- [#18](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/18) CSV bulk import for reading plans
- [#57](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/57) One-click reading completion from daily email

### Other improvements (no milestone) ✅

- [#1](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/1) Inline readings editor on plan edit view
- [#25](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/25) Audio Bible admin UX improvements
- [#66](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/66) Study Tools made admin-managed instead of hard-coded (`#__livingword_tools` + `CwmtoolsModel`)
- [#67](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/67) Home menu item made clearly identifiable as the primary entry point

### Build & release pipeline ✅

Out of the issue tracker but worth noting — the build pipeline has been fully migrated to [`cwm/build-tools`](https://github.com/Joomla-Bible-Study/cwm-build-tools) v1.0 (see [`CLAUDE.md`](../CLAUDE.md#build-flow)):

- Frontend assets wired via `npm` + rollup + csso (sources in `build/media_source/`)
- Bespoke `build/livingword_build.php` retired in favor of `cwm-package` with `self`/`inline`/`prebuilt` includes
- Scripture deps fetched at build time via `build/fetch_dependencies.php` preBuild hook
- Release pipeline (`composer release -- X.Y.Z`) drives all 9 steps: bump → token sub → build → push → GitHub release → changelog → ARS publish → versions.json → announcement
- `build/versions.json` bootstrapped (current=5.0.1, next.patch=5.0.2)

First v1.0-pipeline release: v5.0.1 on 2026-05-14.

---

## Open work

### Active (in M3)

#### [#68](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/68) — Migrate links from text categories to Joomla `com_categories`

**Type:** refactor • **Priority:** medium • **Milestone:** M3

Replace the free-text `#__livingword_links.category` varchar with a `catid` FK into `#__categories`. Gives the standard Joomla category management UI, nesting, ACL, and publishing state. Eliminates the custom `LinkCategoryField`. Includes a `script.php` migration that promotes existing distinct text values into `#__categories` rows before dropping the old column. See the issue body for the full file-by-file plan.

#### [#69](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/69) — Integrate Joomla tagging system (`com_tags`)

**Type:** feature • **Priority:** medium • **Milestone:** M3

Integrate Joomla's built-in `com_tags`. Make plans, links, and groups taggable via `TaggableTableInterface` so they show up in tag clouds and cross-content search. Tags live in `#__contentitem_tag_map` — no schema changes to our tables. The issue body lays out content-type registration, table-class changes, form-field additions, and frontend display options. **Likely best done alongside #68** since both touch the same files (`admin/forms/link.xml`, `LivingwordComponent.php`, etc.).

### Research

#### [#55](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/55) — Group invitation system for non-registered users

**Type:** enhancement • **Label:** research • **No milestone**

The existing `?task=cwmgroup.join&token=...` invite link only works for users with a Joomla account. Need a path for inviting people who haven't registered — landing page that explains the group, registration flow that preserves the invite token, optional email-based invitations. Issue body proposes three options (email-only, shareable-link-only, hybrid). Decision needed before implementation. Considers `join_mode` (open/request/private) interaction and mobile UX.

---

## Where to file new work

- **Bugs:** [open a Bug Report](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/new?template=bug_report.yml) — `.github/ISSUE_TEMPLATE/bug_report.yml`
- **Feature ideas:** [open a Feature Request](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/new?template=feature_request.yml) — discuss in [GitHub Discussions → Feature Ideas](https://github.com/Joomla-Bible-Study/CWMLivingWord/discussions) first if it's a larger design decision.
- **Church-admin needs:** [Church Admin Request template](https://github.com/Joomla-Bible-Study/CWMLivingWord/issues/new?template=church_admin_request.yml).
- **Code:** see [`CONTRIBUTING.md`](../CONTRIBUTING.md) and [`CLAUDE.md`](../CLAUDE.md) for dev setup, branch naming, and commit conventions.

For larger roadmap-level conversations (post-M4 themes, breaking changes for v6, etc.), start a thread under Discussions → Roadmap rather than opening a placeholder issue.
