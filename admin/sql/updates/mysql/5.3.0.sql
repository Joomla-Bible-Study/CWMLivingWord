-- LivingWord 5.3.0 — Reading completion tracking
CREATE TABLE IF NOT EXISTS `#__livingword_progress` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'FK to #__users.id',
  `plan_id` int UNSIGNED NOT NULL COMMENT 'FK to plans.id',
  `day` int UNSIGNED NOT NULL COMMENT '1-based day number',
  `completed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_plan_day` (`user_id`, `plan_id`, `day`),
  KEY `idx_user_plan` (`user_id`, `plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
