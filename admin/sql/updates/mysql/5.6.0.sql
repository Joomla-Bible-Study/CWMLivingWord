-- Add accountability partner columns (#10)
ALTER TABLE `#__livingword_users`
  ADD COLUMN `accountability_partner_id` int DEFAULT NULL
  COMMENT 'FK to #__users.id — paired partner'
  AFTER `streak_last_date`,
  ADD COLUMN `share_progress` tinyint NOT NULL DEFAULT 0
  COMMENT 'Share progress with partner (0/1)'
  AFTER `accountability_partner_id`;
