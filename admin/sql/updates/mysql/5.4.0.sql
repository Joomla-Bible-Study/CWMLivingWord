-- Study tools table
CREATE TABLE IF NOT EXISTS `#__livingword_tools` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `description` varchar(500) NOT NULL DEFAULT '',
  `url` varchar(200) NOT NULL DEFAULT '',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT 'CSS icon class',
  `color` varchar(100) NOT NULL DEFAULT '' COMMENT 'CSS color class',
  `catid` int UNSIGNED NOT NULL DEFAULT 0,
  `published` tinyint NOT NULL DEFAULT 0,
  `checked_out` int UNSIGNED DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_published` (`published`),
  KEY `idx_catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Seed with the 4 previously hard-coded tools.
--
-- ⚠️ Each row is guarded on its own name rather than inserted outright.
--
-- install.sql has seeded these same four rows since before v5.0.0, and every
-- released install stamps #__schemas above this file, so no released site runs
-- it. But if one ever does -- a dev-build install, or a hand-reset #__schemas --
-- a plain INSERT adds a SECOND copy of all four and the admin list shows eight
-- tools with no error anywhere.
--
-- CAN FAIL cannot help: the insert succeeds, it just inserts the wrong thing.
-- INSERT IGNORE cannot either -- `name` carries no unique constraint, so there
-- is nothing for it to ignore against. WHERE NOT EXISTS is what actually holds,
-- and it holds without changing the table's shape.
INSERT INTO `#__livingword_tools` (`name`, `description`, `url`, `icon`, `color`, `published`, `ordering`)
SELECT * FROM (SELECT 'Bible Dictionary' AS `name`, 'Look up definitions of Bible words and terms.' AS `description`, 'https://www.blueletterbible.org/lexicon/' AS `url`, 'icon-book' AS `icon`, 'text-primary' AS `color`, 1 AS `published`, 1 AS `ordering`) AS s
WHERE NOT EXISTS (SELECT 1 FROM `#__livingword_tools` WHERE `name` = 'Bible Dictionary');

INSERT INTO `#__livingword_tools` (`name`, `description`, `url`, `icon`, `color`, `published`, `ordering`)
SELECT * FROM (SELECT 'Bible Commentary' AS `name`, 'Read commentary and study notes.' AS `description`, 'https://enduringword.com/bible-commentary/' AS `url`, 'icon-file-alt' AS `icon`, 'text-info' AS `color`, 1 AS `published`, 2 AS `ordering`) AS s
WHERE NOT EXISTS (SELECT 1 FROM `#__livingword_tools` WHERE `name` = 'Bible Commentary');

INSERT INTO `#__livingword_tools` (`name`, `description`, `url`, `icon`, `color`, `published`, `ordering`)
SELECT * FROM (SELECT 'Bible Concordance' AS `name`, 'Search for words and phrases across the entire Bible.' AS `description`, 'https://www.blueletterbible.org/search.cfm' AS `url`, 'icon-search' AS `icon`, 'text-success' AS `color`, 1 AS `published`, 3 AS `ordering`) AS s
WHERE NOT EXISTS (SELECT 1 FROM `#__livingword_tools` WHERE `name` = 'Bible Concordance');

INSERT INTO `#__livingword_tools` (`name`, `description`, `url`, `icon`, `color`, `published`, `ordering`)
SELECT * FROM (SELECT 'Bible Maps' AS `name`, 'Explore geographic locations mentioned in Scripture.' AS `description`, 'https://www.openbible.info/geo/' AS `url`, 'icon-location' AS `icon`, 'text-warning' AS `color`, 1 AS `published`, 4 AS `ordering`) AS s
WHERE NOT EXISTS (SELECT 1 FROM `#__livingword_tools` WHERE `name` = 'Bible Maps');

-- Fix icon-file-text which doesn't exist in Joomla's icon bridge.
-- Idempotent on its own -- re-running matches nothing.
UPDATE `#__livingword_tools` SET `icon` = 'icon-file-alt' WHERE `icon` = 'icon-file-text';
