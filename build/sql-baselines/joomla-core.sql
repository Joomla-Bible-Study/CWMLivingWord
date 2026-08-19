-- Joomla core tables that com_cwmlivingword's migrations touch.
--
-- The first half of the cwm-schema-replay baseline. An extension's own install
-- SQL is not a site: the files in admin/sql/updates/mysql reference tables no
-- LivingWord manifest creates, and replaying onto the extension's own tables
-- alone fails on the first of them for a reason that says nothing about the
-- migration.
--
-- | #__users                 | referenced when a migration back-fills authorship|
-- | #__schemas               | the version row every upgrade reads and writes  |
-- | #__categories            | LivingWord content is categorised               |
-- | #__content_types         | registers the type so tags and workflows see it |
-- | #__contentitem_tag_map   | tag associations cleaned up on a type change    |
--
-- ## Why only these
--
-- This is the set the migrations actually use, not a whole Joomla schema.
-- Vendoring all 76 core tables would put ~225 KB of upstream SQL in this repo
-- to make five of them reachable, and it would go stale against whichever
-- Joomla release it was copied from.
--
-- The failure mode of a short list is loud and self-describing: a migration
-- touching a sixth core table fails with "Table ... doesn't exist" naming
-- exactly what to add here.
--
-- ## Where these came from
--
-- Copied verbatim from Joomla's own installation SQL -- a real core schema,
-- not a dump of somebody's dev site, so it reviews as "this is what Joomla
-- creates":
--
--   installation/sql/mysql/base.sql      -- #__users, #__schemas
--   installation/sql/mysql/supports.sql  -- the three content tables
--
-- Taken from joomla-cms 6.1.3. Re-copy from those two files if one ever
-- changes in a way that matters.
--
-- No data rows. The migrations supply their own.

-- from installation/sql/mysql/base.sql
CREATE TABLE IF NOT EXISTS `#__users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(400) NOT NULL DEFAULT '',
  `username` varchar(150) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(100) NOT NULL DEFAULT '',
  `block` tinyint NOT NULL DEFAULT 0,
  `sendEmail` tinyint DEFAULT 0,
  `registerDate` datetime NOT NULL,
  `lastvisitDate` datetime,
  `activation` varchar(100) NOT NULL DEFAULT '',
  `params` text NOT NULL,
  `lastResetTime` datetime COMMENT 'Date of last password reset',
  `resetCount` int NOT NULL DEFAULT 0 COMMENT 'Count of password resets since lastResetTime',
  `otpKey` varchar(1000) NOT NULL DEFAULT '' COMMENT 'Two factor authentication encrypted keys',
  `otep` varchar(1000) NOT NULL DEFAULT '' COMMENT 'Backup Codes',
  `requireReset` tinyint NOT NULL DEFAULT 0 COMMENT 'Require user to reset password on next login',
  `authProvider` varchar(100) NOT NULL DEFAULT '' COMMENT 'Name of used authentication plugin',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`(100)),
  KEY `idx_block` (`block`),
  UNIQUE KEY `idx_username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- from installation/sql/mysql/base.sql
CREATE TABLE IF NOT EXISTS `#__schemas` (
  `extension_id` int NOT NULL,
  `version_id` varchar(20) NOT NULL,
  PRIMARY KEY (`extension_id`,`version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- from installation/sql/mysql/supports.sql
CREATE TABLE IF NOT EXISTS `#__categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'FK to the #__assets table.',
  `parent_id` int unsigned NOT NULL DEFAULT 0,
  `lft` int NOT NULL DEFAULT 0,
  `rgt` int NOT NULL DEFAULT 0,
  `level` int unsigned NOT NULL DEFAULT 0,
  `path` varchar(400) NOT NULL DEFAULT '',
  `extension` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `note` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext,
  `published` tinyint NOT NULL DEFAULT 0,
  `checked_out` int unsigned,
  `checked_out_time` datetime,
  `access` int unsigned NOT NULL DEFAULT 0,
  `params` text,
  `metadesc` varchar(1024) NOT NULL DEFAULT '' COMMENT 'The meta description for the page.',
  `metakey` varchar(1024) NOT NULL DEFAULT '' COMMENT 'The keywords for the page.',
  `metadata` varchar(2048) NOT NULL DEFAULT '' COMMENT 'JSON encoded metadata properties.',
  `created_user_id` int unsigned NOT NULL DEFAULT 0,
  `created_time` datetime NOT NULL,
  `modified_user_id` int unsigned NOT NULL DEFAULT 0,
  `modified_time` datetime NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `language` char(7) NOT NULL DEFAULT '',
  `version` int unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `cat_idx` (`extension`,`published`,`access`),
  KEY `idx_access` (`access`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_path` (`path`(100)),
  KEY `idx_left_right` (`lft`,`rgt`),
  KEY `idx_alias` (`alias`(100)),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- from installation/sql/mysql/supports.sql
CREATE TABLE IF NOT EXISTS `#__content_types` (
  `type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_title` varchar(255) NOT NULL DEFAULT '',
  `type_alias` varchar(400) NOT NULL DEFAULT '',
  `table` varchar(2048) NOT NULL DEFAULT '',
  `rules` text NOT NULL,
  `field_mappings` text NOT NULL,
  `router` varchar(255) NOT NULL DEFAULT '',
  `content_history_options` varchar(5120) COMMENT 'JSON string for com_contenthistory options',
  PRIMARY KEY (`type_id`),
  KEY `idx_alias` (`type_alias`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=10000;

-- from installation/sql/mysql/supports.sql
CREATE TABLE IF NOT EXISTS `#__contentitem_tag_map` (
  `type_alias` varchar(255) NOT NULL DEFAULT '',
  `core_content_id` int unsigned NOT NULL COMMENT 'PK from the core content table',
  `content_item_id` int NOT NULL COMMENT 'PK from the content type table',
  `tag_id` int unsigned NOT NULL COMMENT 'PK from the tag table',
  `tag_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date of most recent save for this tag-item',
  `type_id` mediumint NOT NULL COMMENT 'PK from the content_type table',
  UNIQUE KEY `uc_ItemnameTagid` (`type_id`,`content_item_id`,`tag_id`),
  KEY `idx_tag_type` (`tag_id`,`type_id`),
  KEY `idx_date_id` (`tag_date`,`tag_id`),
  KEY `idx_core_content_id` (`core_content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci COMMENT='Maps items from content tables to tags';

