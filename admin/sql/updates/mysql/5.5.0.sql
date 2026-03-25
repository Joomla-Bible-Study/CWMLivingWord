-- Add passage_index column for chapter-level completion tracking (#7)
ALTER TABLE `#__livingword_progress`
  ADD COLUMN `passage_index` smallint UNSIGNED NOT NULL DEFAULT 0
  COMMENT '0-based passage index within the day reading'
  AFTER `day`;

-- Replace unique key to include passage_index
ALTER TABLE `#__livingword_progress`
  DROP INDEX `idx_user_plan_day`,
  ADD UNIQUE KEY `idx_user_plan_day_passage` (`user_id`, `plan_id`, `day`, `passage_index`),
  ADD KEY `idx_user_plan_day` (`user_id`, `plan_id`, `day`);