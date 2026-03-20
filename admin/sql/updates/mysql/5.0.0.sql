-- Migration from LivingWord 3.0.0 to 5.0.0

ALTER TABLE `#__livingword` ENGINE=InnoDB;
ALTER TABLE `#__livingword_links` ENGINE=InnoDB;
ALTER TABLE `#__livingword_plans` ENGINE=InnoDB;
ALTER TABLE `#__livingword_plans_details` ENGINE=InnoDB;

ALTER TABLE `#__livingword` MODIFY `startdate` date DEFAULT NULL;

ALTER TABLE `#__livingword_links` MODIFY `checked_out_time` datetime DEFAULT NULL;
ALTER TABLE `#__livingword_links` MODIFY `checked_out` int UNSIGNED DEFAULT NULL;

ALTER TABLE `#__livingword_plans` MODIFY `checked_out_time` datetime DEFAULT NULL;
ALTER TABLE `#__livingword_plans` MODIFY `checked_out` int UNSIGNED DEFAULT NULL;

ALTER TABLE `#__livingword_plans_details` MODIFY `checked_out_time` datetime DEFAULT NULL;
ALTER TABLE `#__livingword_plans_details` MODIFY `checked_out` int UNSIGNED DEFAULT NULL;

-- Add indexes for performance
ALTER TABLE `#__livingword` ADD KEY `idx_userid` (`userid`);
ALTER TABLE `#__livingword_links` ADD KEY `idx_published` (`published`);
ALTER TABLE `#__livingword_plans` ADD KEY `idx_published` (`published`);
ALTER TABLE `#__livingword_plans` ADD KEY `idx_name` (`name`);
ALTER TABLE `#__livingword_plans_details` ADD KEY `idx_plan` (`plan`);
ALTER TABLE `#__livingword_plans_details` ADD KEY `idx_ordering` (`plan`, `ordering`);
