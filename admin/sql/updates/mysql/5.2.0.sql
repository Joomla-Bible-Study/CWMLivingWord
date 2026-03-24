-- LivingWord 5.2.0 — Add unsubscribe token for CAN-SPAM compliance
ALTER TABLE `#__livingword_users` ADD COLUMN IF NOT EXISTS
  `unsubscribe_token` varchar(64) DEFAULT NULL COMMENT 'One-click email unsubscribe token' AFTER `email`;
