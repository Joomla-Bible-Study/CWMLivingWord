5.7.1 is a single fix, and it matters most to sites running both LivingWord and Proclaim: uninstalling LivingWord took the shared scripture library with it, along with every Bible translation the site had downloaded. If you run both, update before you ever remove either one.

## Fixes

- **Removing LivingWord no longer uninstalls the scripture stack.** LivingWord declared the CWM Scripture library and its two plugins as its own package contents. Joomla removes exactly what a package declares, so uninstalling LivingWord deleted the library, the scripture-links plugin and the scripture task plugin — even on a site where Proclaim was still using them, and even though Proclaim had installed them first. Every downloaded translation went with the library. Nothing warned about it, and the only way back was to reinstall and re-download.

  LivingWord now ships the scripture package alongside its own extensions instead of claiming them. Installing or updating LivingWord still puts the library in place on a site that does not have it, and still upgrades an older one — but a site already running a current version, from Proclaim or from the standalone package, is left completely alone and is never downgraded. Uninstalling LivingWord now removes LivingWord.

  Updating to 5.7.1 is what applies this. Until you do, the old install's records still claim the scripture stack, so the removal is still live on that site.

## Changes

- **The release gate tests the upgrade it is gating.** The pre-release upgrade check took its baseline from the version file, which during a release still reads as the version being released — so it reported "nothing newer to test" about the very build under test. On 5.7.0 that meant a release whose headline fix only runs on the update route was gated by a check that skipped itself; it was verified by hand instead. The baseline now comes from the releases that actually exist, so every release from here is tested upgrading from the artifact most sites are on.

## Notes

- Requires PHP 8.3 or later, and Joomla 5 or 6.
- No database changes and no configuration to review. Reading plans, progress, notes and groups are untouched.
- If you have already uninstalled LivingWord from a site and lost the scripture library, reinstalling the CWM Scripture package restores it. Downloaded translations have to be downloaded again.
