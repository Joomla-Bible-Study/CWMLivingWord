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

-- Seed with the 4 previously hard-coded tools
INSERT INTO `#__livingword_tools` (`name`, `description`, `url`, `icon`, `color`, `published`, `ordering`) VALUES
('Bible Dictionary', 'Look up definitions of Bible words and terms.', 'https://www.blueletterbible.org/lexicon/', 'icon-book', 'text-primary', 1, 1),
('Bible Commentary', 'Read commentary and study notes.', 'https://enduringword.com/bible-commentary/', 'icon-file-alt', 'text-info', 1, 2),
('Bible Concordance', 'Search for words and phrases across the entire Bible.', 'https://www.blueletterbible.org/search.cfm', 'icon-search', 'text-success', 1, 3),
('Bible Maps', 'Explore geographic locations mentioned in Scripture.', 'https://www.openbible.info/geo/', 'icon-location', 'text-warning', 1, 4);

-- Fix icon-file-text which doesn't exist in Joomla's icon bridge
UPDATE `#__livingword_tools` SET `icon` = 'icon-file-alt' WHERE `icon` = 'icon-file-text';
