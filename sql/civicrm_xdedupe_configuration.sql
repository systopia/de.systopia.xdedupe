-- CREATE civicrm_xdedupe_configuration TABLE
CREATE TABLE IF NOT EXISTS `civicrm_xdedupe_configuration`(
  `id`             int unsigned NOT NULL AUTO_INCREMENT,
  `name`           varchar(64)  COMMENT 'name of the config',
  `description`    text         COMMENT 'config description',
  `is_manual`      tinyint      COMMENT 'is config flagged for manual execution',
  `is_automatic`   tinyint      COMMENT 'is config enabled for automatic execution',
  `config`         text         COMMENT 'configuration (JSON)',
  `last_run`       text         COMMENT 'stats of the last run (JSON)',
  `weight`         int unsigned COMMENT 'defines listing order',
  PRIMARY KEY ( `id` )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
