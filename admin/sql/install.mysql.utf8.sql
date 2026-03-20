CREATE TABLE IF NOT EXISTS `#__livingword` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userid` int NOT NULL DEFAULT 0,
  `bibleplan` varchar(50) NOT NULL DEFAULT '',
  `bibleversion` varchar(20) NOT NULL DEFAULT '',
  `pbversion` varchar(20) NOT NULL DEFAULT '',
  `audioversion` varchar(50) NOT NULL DEFAULT '',
  `email` int NOT NULL DEFAULT 0,
  `planview` int NOT NULL DEFAULT 0,
  `readstate` int NOT NULL DEFAULT 0,
  `startdate` date DEFAULT NULL,
  `dateoffset` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__livingword_links` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `url` varchar(200) NOT NULL DEFAULT '',
  `category` varchar(100) NOT NULL DEFAULT '',
  `target` int NOT NULL DEFAULT 0,
  `published` smallint NOT NULL DEFAULT 0,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int UNSIGNED DEFAULT NULL,
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__livingword_plans` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `message` text NOT NULL,
  `audio` smallint NOT NULL DEFAULT 0,
  `newtest` smallint NOT NULL DEFAULT 0,
  `published` smallint NOT NULL DEFAULT 0,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int UNSIGNED DEFAULT NULL,
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_published` (`published`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__livingword_plans_details` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan` varchar(50) NOT NULL DEFAULT '',
  `reading` varchar(100) NOT NULL DEFAULT '',
  `audio` varchar(50) NOT NULL DEFAULT '',
  `figure` varchar(100) NOT NULL DEFAULT '',
  `descrip` text NOT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int UNSIGNED DEFAULT NULL,
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_plan` (`plan`),
  KEY `idx_ordering` (`plan`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
