ALTER TABLE `#__livingword_groups` ADD COLUMN `join_mode` varchar(20) NOT NULL DEFAULT 'open' COMMENT 'open, request, or private' AFTER `invite_token`;

UPDATE `#__livingword_group_members` SET `role` = 'leader' WHERE `role` = 'group_admin';
