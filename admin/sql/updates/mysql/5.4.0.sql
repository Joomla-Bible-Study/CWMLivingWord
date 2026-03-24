-- LivingWord 5.4.0 — Reading streaks
ALTER TABLE `#__livingword_users` ADD COLUMN IF NOT EXISTS `streak_current` int NOT NULL DEFAULT 0 COMMENT 'Current consecutive days read' AFTER `date_offset`;
ALTER TABLE `#__livingword_users` ADD COLUMN IF NOT EXISTS `streak_best` int NOT NULL DEFAULT 0 COMMENT 'Best streak ever achieved' AFTER `streak_current`;
ALTER TABLE `#__livingword_users` ADD COLUMN IF NOT EXISTS `streak_last_date` date DEFAULT NULL COMMENT 'Last date a reading was completed' AFTER `streak_best`;
