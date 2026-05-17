-- Migrate #__livingword_links from free-text `category` column to a `catid` FK to #__categories.
-- Additive only — the legacy `category` column is preserved as a safety net and dropped in 5.6.0.
-- The actual data migration (creating category rows, mapping text -> catid) runs in script.php
-- because com_categories nested-set integrity requires the CategoryTable API, not raw INSERTs.

ALTER TABLE `#__livingword_links`
  ADD COLUMN `catid` int UNSIGNED NOT NULL DEFAULT 0 AFTER `url`,
  ADD KEY `idx_catid` (`catid`);