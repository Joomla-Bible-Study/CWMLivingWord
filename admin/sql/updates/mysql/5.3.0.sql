ALTER TABLE `#__livingword_users`
  ADD COLUMN `action_token` varchar(64) DEFAULT NULL COMMENT 'Token for email-based reading completion' AFTER `unsubscribe_token`,
  ADD KEY `idx_action_token` (`action_token`);
