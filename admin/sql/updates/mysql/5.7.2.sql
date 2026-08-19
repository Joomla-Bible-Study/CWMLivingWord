-- Repair: idx_action_token, missing on every site installed from v5.0.0 to v5.5.0.
--
-- Those five releases shipped an install.sql that creates
-- #__livingword_users.action_token WITHOUT the index on it. The only file that
-- adds the index is 5.3.0.sql -- and a fresh install of any of them stamps
-- #__schemas at 5.4.0 or higher, so 5.3.0.sql is below the stamp and never
-- runs. Upgrading does not help either: install.sql only runs on a fresh
-- install, so a site that came in on 5.0.0 still has no index today.
--
-- v5.6.0 added the index to install.sql, which fixed new installs and left
-- every existing one behind. This file is the part that was missing.
--
-- Guarded, because sites installed from v5.6.0 onward already have it and a
-- hard failure here would abort their update.
ALTER TABLE `#__livingword_users`
  ADD KEY `idx_action_token` (`action_token`) /** CAN FAIL **/;
