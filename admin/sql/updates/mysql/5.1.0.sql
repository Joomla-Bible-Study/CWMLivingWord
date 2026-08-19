-- ⚠️ Guarded because this file is BELOW the stamp every released install gets.
--
-- install.sql has created `join_mode` since before v5.0.0 was tagged, and every
-- release stamps #__schemas at 5.4.0 or higher, so no released site runs this
-- file. The ones that could are installs from the dev builds between
-- 2026-03-20 and 2026-05-05, whose install.sql did not have the column yet.
--
-- For those the ADD COLUMN is the real path to it; for anyone else it is a
-- duplicate-column error, which Joomla treats as a failed update and which
-- would stop them reaching any later migration. CAN FAIL is what makes the
-- file safe for both.
ALTER TABLE `#__livingword_groups` ADD COLUMN `join_mode` varchar(20) NOT NULL DEFAULT 'open' COMMENT 'open, request, or private' AFTER `invite_token` /** CAN FAIL **/;

-- Idempotent on its own -- re-running matches nothing -- so deliberately not marked.
UPDATE `#__livingword_group_members` SET `role` = 'leader' WHERE `role` = 'group_admin';
