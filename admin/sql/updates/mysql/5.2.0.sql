CREATE TABLE IF NOT EXISTS `#__livingword_notes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'FK to #__users.id',
  `plan_id` int UNSIGNED NOT NULL COMMENT 'FK to plans.id',
  `day` int UNSIGNED NOT NULL COMMENT '1-based day number',
  `note_text` text NOT NULL COMMENT 'User journal/reflection text',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_plan_day` (`user_id`, `plan_id`, `day`),
  KEY `idx_user_plan` (`user_id`, `plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
