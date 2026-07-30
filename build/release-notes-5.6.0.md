This release adds tagging and a working group-invitation flow — and, more importantly, it is the first LivingWord package that actually installs.

Every release before 5.6.0-beta2 shipped a package Joomla refused to install: the inner extensions were assembled into a `packages/` folder the manifest never pointed at, so the installer aborted with "Unable to install extension" and nothing more. If a previous install failed for you, that is why. 5.6.0 installs and updates cleanly, and both paths are now verified against Joomla 5.4, 6.1 and 6.2 before each release rather than after.

## New features

- **Tagging for reading plans, resource links and groups.** Uses Joomla's own tag system, so tags are shared with the rest of your site and work with existing tag modules and searches. Tag fields appear on the edit screens for all three.
- **Invitation links now work for people without an account.** Previously an invite link bounced anyone who was not already logged in, which is most people receiving one. Visitors now get a landing page describing the group, with the invitation preserved through registration or login so they land in the right group afterwards.

## Fixes

- **The package installs.** The package manifest now points at the folder its inner extensions are actually assembled into.
- **Updates appear in the Extension Manager.** The package shipped with no update server declared at all, so no site was ever notified of a new version. It is now wired to the CWM update stream, and a Changelog tab appears alongside the update.
- **Scheduled emails no longer need the plugin switched on by hand.** Joomla registers new plugins disabled, so the daily reading email, weekly progress digest and accountability-partner digest never ran until an admin found the plugin and enabled it — with nothing to indicate that was the problem. It is now enabled on install, and existing sites get it enabled once when they update.
- **The one-click "mark as read" link in reading emails is no longer a full table scan.** Fresh installs were missing an index that upgraded sites had.
- **Group member counts no longer break the page.** The member-count text used a plural form Joomla does not support, which produced a 500 error where the count was shown.

## Notes

- Requires PHP 8.3 or later and Joomla 5 or 6.
- If you tested 5.6.0-beta1 or beta2, updating applies the missing index and enables the task plugin automatically. Nothing to do by hand.
- Enabling the task plugin does not by itself send anything. The routines still need a task created under **System → Scheduled Tasks**.
