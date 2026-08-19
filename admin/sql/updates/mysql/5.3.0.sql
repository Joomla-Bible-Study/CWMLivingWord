-- ⚠️ Split into two statements on purpose. Do not recombine them.
--
-- This was one ALTER TABLE adding the column and its index together. Sites
-- installed from v5.0.0 to v5.5.0 have the column (install.sql created it) but
-- NOT the index (install.sql did not add that until v5.6.0) -- so the two
-- clauses are not in the same state, and one CAN FAIL marker on a combined
-- statement would skip the whole thing and repair neither.
--
-- Separate statements mean each is guarded on its own condition: a site with
-- the column but no index still gets the index.
--
-- Those sites are also repaired directly by 5.7.2.sql, which is what actually
-- reaches them -- this file is below their stamp and never runs. Both exist
-- because they answer different questions: this one keeps the file safe if it
-- ever does run, 5.7.2 fixes the sites it cannot reach.
ALTER TABLE `#__livingword_users`
  ADD COLUMN `action_token` varchar(64) DEFAULT NULL COMMENT 'Token for email-based reading completion' AFTER `unsubscribe_token` /** CAN FAIL **/;

ALTER TABLE `#__livingword_users`
  ADD KEY `idx_action_token` (`action_token`) /** CAN FAIL **/;
