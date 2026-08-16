5.7.0 adds a Web Services API and brings every view to WCAG 2.2 AA — but the bulk of it is things that were quietly broken. Three separate defects in this release had the same shape: the component answered "saved", stored nothing, and wrote nothing to any log. Reading preferences, per-passage completion and the daily reading module were all affected, and none of them announced themselves.

## New features

- **Web Services API.** Read-only catalog endpoints for reading plans, plan days, study tools, resource links and groups, plus authenticated per-user endpoints for reading progress, journal notes and preferences, under `/v1/livingword/`. Built for a mobile client to work against. Every user-data request is scoped to the requesting account. The webservices plugin is enabled on install, so nothing needs switching on by hand.
- **Reader scenarios can be seeded into a development site.** For anyone testing LivingWord: `composer seed` fills an install with readers at different points in a plan, groups, partners and journal entries, so the email routines and progress views have realistic data to work against.

## Fixes

- **Daily reading emails now arrive at the reader's chosen hour.** Joomla pins PHP to UTC, so "6am" meant 6am UTC for everyone — a reader in America/Chicago who asked for 6am was mailed at 1am, midnight in summer. The timezone they had chosen was saved to the database and then read by nothing at all. Readers with no timezone set fall back to the site's own.
- **Per-passage completion is recorded again on sites that upgraded.** Sites installed before per-passage reading landed kept a database key that admitted only one passage per day. Ticking the second passage of "Genesis 1-3; Psalm 1; Matthew 1" returned success and stored nothing, so multi-passage days showed as permanently part-read while streaks counted them as done. Updating repairs the key.
- **Reading preferences save on older sites.** Audio version, preferred email hour and timezone were silently discarded on save on any site created before those three columns existed — the screen still said preferences had been saved. Updating adds the missing columns.
- **The daily reading module renders.** Its namespace carried a segment Joomla appends itself, so the module emitted nothing whatsoever: no markup, no error, nothing in the log. This bit twice, in two different files, and both are now covered by tests.
- **The Settings screen saves.** The controller its form posted to did not exist.
- **The Plan Days admin screen works.** It returned a 500 on every request — it selected four columns that are not on the table — and the day editor did not save what you typed into it.
- **A fault in the scripture library no longer takes the reading view with it.** Passage text falls back cleanly instead.
- **Accessibility: WCAG 2.2 AA across every administrator and site view**, now scanned before each release rather than after.
- **Journal autosave says what it is doing.** Its status messages were never reaching the page.
- **Scripture book names resolve through the library's public API**, and LivingWord registers itself as a consumer, so the library can no longer be uninstalled out from under it.

## Changes

- **Joomla 7 is not declared supported.** A declaration added during this cycle was withdrawn: the manifests target Joomla 5 and 6. Testing against 7 continues, and support will be declared when it reaches Beta.
- **Releases are now gated on more than unit tests.** Every release runs a clean install, an upgrade from the previous release, an accessibility scan and an API acceptance suite against real Joomla sites first, and the schema each migration is supposed to produce is verified afterwards.

## Notes

- Requires PHP 8.3 or later, and Joomla 5 or 6.
- Days completed before this update may still show as part-read. Only one passage per day was ever stored for them, and which passage it was is not recorded, so filling in the rest would invent progress that may not have happened. Re-ticking those days records them properly.
- If you ran 5.7.0-beta1 through beta4, updating applies the same repairs. Nothing to do by hand.
- The scheduled routines — daily reading email, weekly progress digest, accountability-partner digest — still need tasks created under **System → Scheduled Tasks**. The plugin being enabled does not by itself send anything.
