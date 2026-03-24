-- LivingWord 5.1.0 Migration
-- Converts LWBIBLEBOOK reading format to human-readable passage references
-- Drops unused figure column, clears legacy audio data, widens reading column

-- Widen reading column to accommodate longer human-readable references
ALTER TABLE `#__livingword_plans_details` MODIFY `reading` varchar(255) NOT NULL DEFAULT '';

-- Drop figure column (no longer used)
ALTER TABLE `#__livingword_plans_details` DROP COLUMN IF EXISTS `figure`;

-- Clear legacy audio data (old comma-separated LWBIBLEBOOK triplets)
-- Audio is now derived from the reading field by BibleBrain provider
UPDATE `#__livingword_plans_details` SET `audio` = '' WHERE `audio` != '';

-- Convert LWBIBLEBOOK references to human-readable book names
-- This handles the 73-book numbering used by the original Joomla 3 component
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK01 ', 'Genesis ') WHERE `reading` LIKE '%LWBIBLEBOOK01 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK02 ', 'Exodus ') WHERE `reading` LIKE '%LWBIBLEBOOK02 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK03 ', 'Leviticus ') WHERE `reading` LIKE '%LWBIBLEBOOK03 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK04 ', 'Numbers ') WHERE `reading` LIKE '%LWBIBLEBOOK04 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK05 ', 'Deuteronomy ') WHERE `reading` LIKE '%LWBIBLEBOOK05 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK06 ', 'Joshua ') WHERE `reading` LIKE '%LWBIBLEBOOK06 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK07 ', 'Judges ') WHERE `reading` LIKE '%LWBIBLEBOOK07 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK08 ', 'Ruth ') WHERE `reading` LIKE '%LWBIBLEBOOK08 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK09 ', '1 Samuel ') WHERE `reading` LIKE '%LWBIBLEBOOK09 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK10 ', '2 Samuel ') WHERE `reading` LIKE '%LWBIBLEBOOK10 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK11 ', '1 Kings ') WHERE `reading` LIKE '%LWBIBLEBOOK11 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK12 ', '2 Kings ') WHERE `reading` LIKE '%LWBIBLEBOOK12 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK13 ', '1 Chronicles ') WHERE `reading` LIKE '%LWBIBLEBOOK13 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK14 ', '2 Chronicles ') WHERE `reading` LIKE '%LWBIBLEBOOK14 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK15 ', 'Ezra ') WHERE `reading` LIKE '%LWBIBLEBOOK15 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK16 ', 'Nehemiah ') WHERE `reading` LIKE '%LWBIBLEBOOK16 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK19 ', 'Esther ') WHERE `reading` LIKE '%LWBIBLEBOOK19 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK22 ', 'Job ') WHERE `reading` LIKE '%LWBIBLEBOOK22 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK23 ', 'Psalm ') WHERE `reading` LIKE '%LWBIBLEBOOK23 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK24 ', 'Proverbs ') WHERE `reading` LIKE '%LWBIBLEBOOK24 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK25 ', 'Ecclesiastes ') WHERE `reading` LIKE '%LWBIBLEBOOK25 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK26 ', 'Song of Solomon ') WHERE `reading` LIKE '%LWBIBLEBOOK26 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK29 ', 'Isaiah ') WHERE `reading` LIKE '%LWBIBLEBOOK29 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK30 ', 'Jeremiah ') WHERE `reading` LIKE '%LWBIBLEBOOK30 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK31 ', 'Lamentations ') WHERE `reading` LIKE '%LWBIBLEBOOK31 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK33 ', 'Ezekiel ') WHERE `reading` LIKE '%LWBIBLEBOOK33 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK34 ', 'Daniel ') WHERE `reading` LIKE '%LWBIBLEBOOK34 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK35 ', 'Hosea ') WHERE `reading` LIKE '%LWBIBLEBOOK35 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK36 ', 'Joel ') WHERE `reading` LIKE '%LWBIBLEBOOK36 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK37 ', 'Amos ') WHERE `reading` LIKE '%LWBIBLEBOOK37 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK38 ', 'Obadiah ') WHERE `reading` LIKE '%LWBIBLEBOOK38 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK39 ', 'Jonah ') WHERE `reading` LIKE '%LWBIBLEBOOK39 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK40 ', 'Micah ') WHERE `reading` LIKE '%LWBIBLEBOOK40 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK41 ', 'Nahum ') WHERE `reading` LIKE '%LWBIBLEBOOK41 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK42 ', 'Habakkuk ') WHERE `reading` LIKE '%LWBIBLEBOOK42 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK43 ', 'Zephaniah ') WHERE `reading` LIKE '%LWBIBLEBOOK43 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK44 ', 'Haggai ') WHERE `reading` LIKE '%LWBIBLEBOOK44 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK45 ', 'Zechariah ') WHERE `reading` LIKE '%LWBIBLEBOOK45 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK46 ', 'Malachi ') WHERE `reading` LIKE '%LWBIBLEBOOK46 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK47 ', 'Matthew ') WHERE `reading` LIKE '%LWBIBLEBOOK47 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK48 ', 'Mark ') WHERE `reading` LIKE '%LWBIBLEBOOK48 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK49 ', 'Luke ') WHERE `reading` LIKE '%LWBIBLEBOOK49 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK50 ', 'John ') WHERE `reading` LIKE '%LWBIBLEBOOK50 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK51 ', 'Acts ') WHERE `reading` LIKE '%LWBIBLEBOOK51 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK52 ', 'Romans ') WHERE `reading` LIKE '%LWBIBLEBOOK52 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK53 ', '1 Corinthians ') WHERE `reading` LIKE '%LWBIBLEBOOK53 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK54 ', '2 Corinthians ') WHERE `reading` LIKE '%LWBIBLEBOOK54 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK55 ', 'Galatians ') WHERE `reading` LIKE '%LWBIBLEBOOK55 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK56 ', 'Ephesians ') WHERE `reading` LIKE '%LWBIBLEBOOK56 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK57 ', 'Philippians ') WHERE `reading` LIKE '%LWBIBLEBOOK57 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK58 ', 'Colossians ') WHERE `reading` LIKE '%LWBIBLEBOOK58 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK59 ', '1 Thessalonians ') WHERE `reading` LIKE '%LWBIBLEBOOK59 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK60 ', '2 Thessalonians ') WHERE `reading` LIKE '%LWBIBLEBOOK60 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK61 ', '1 Timothy ') WHERE `reading` LIKE '%LWBIBLEBOOK61 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK62 ', '2 Timothy ') WHERE `reading` LIKE '%LWBIBLEBOOK62 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK63 ', 'Titus ') WHERE `reading` LIKE '%LWBIBLEBOOK63 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK64 ', 'Philemon ') WHERE `reading` LIKE '%LWBIBLEBOOK64 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK65 ', 'Hebrews ') WHERE `reading` LIKE '%LWBIBLEBOOK65 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK66 ', 'James ') WHERE `reading` LIKE '%LWBIBLEBOOK66 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK67 ', '1 Peter ') WHERE `reading` LIKE '%LWBIBLEBOOK67 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK68 ', '2 Peter ') WHERE `reading` LIKE '%LWBIBLEBOOK68 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK69 ', '1 John ') WHERE `reading` LIKE '%LWBIBLEBOOK69 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK70 ', '2 John ') WHERE `reading` LIKE '%LWBIBLEBOOK70 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK71 ', '3 John ') WHERE `reading` LIKE '%LWBIBLEBOOK71 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK72 ', 'Jude ') WHERE `reading` LIKE '%LWBIBLEBOOK72 %';
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, 'LWBIBLEBOOK73 ', 'Revelation ') WHERE `reading` LIKE '%LWBIBLEBOOK73 %';

-- Clean up semicolon separators to use '; ' consistently
UPDATE `#__livingword_plans_details` SET `reading` = REPLACE(`reading`, ';', '; ') WHERE `reading` LIKE '%;%' AND `reading` NOT LIKE '%; %';
