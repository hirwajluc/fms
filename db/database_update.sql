-- =====================================================================
-- GREATER FMS - database update
--
-- Engine : MySQL 8.0 (written against the Sql1800295_2 export of 2026-11-01)
--
-- Part 1  users.email_notifications_enabled: per-user email notification setting.
--         Every user defaults to 1 (enabled).
-- Part 2  forex_rates.partner_id: each partner keeps its own Forex rates.
--         One rate per date becomes one rate per date for each partner.
--
-- Data   : no existing row is changed. All existing Forex rates keep partner_id
--          NULL, which makes them the shared default rates, so every expense
--          converts exactly as before until a partner enters a rate of its own.
--
-- HOW TO APPLY (manually, against the existing database)
--   1. Back up the database first.
--   2. phpMyAdmin: select the database, open Import, choose this file, click Go.
--      Or from a shell:  mysql -u <user> -p <database_name> < db/database_update.sql
--   3. Safe to run again, including if Part 1 was applied earlier: each step checks
--      first and skips what is already done.
--      (MySQL has no ADD COLUMN IF NOT EXISTS, hence the checks below.)
--
-- HOW TO VERIFY
--   SHOW COLUMNS FROM `users` LIKE 'email_notifications_enabled';
--   SHOW COLUMNS FROM `forex_rates` LIKE 'partner_id';
--   SHOW INDEX FROM `forex_rates` WHERE Key_name = 'unique_partner_date';
--   SELECT partner_id, COUNT(*) AS rates FROM `forex_rates` GROUP BY partner_id;
--   Right after applying, the last query shows one line: NULL and your rate count.
--
-- HOW TO UNDO
--   Part 1 (discards every user's saved preference):
--     ALTER TABLE `users` DROP COLUMN `email_notifications_enabled`;
--   Part 2 (discards every rate entered by a partner; run these one at a time):
--     DELETE FROM `forex_rates` WHERE `partner_id` IS NOT NULL;
--     ALTER TABLE `forex_rates` DROP FOREIGN KEY `forex_rates_ibfk_1`;
--     ALTER TABLE `forex_rates` DROP INDEX `unique_partner_date`;
--     ALTER TABLE `forex_rates` DROP COLUMN `partner_id`;
--     ALTER TABLE `forex_rates` ADD UNIQUE KEY `unique_date` (`rate_date`);
-- =====================================================================

-- ---------------------------------------------------------------------
-- Part 1: users.email_notifications_enabled
-- ---------------------------------------------------------------------

SET @fms_column_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'email_notifications_enabled'
);

SET @fms_statement := IF(
  @fms_column_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT ''1'' COMMENT ''1 = receives optional email notifications, 0 = turned off'' AFTER `force_password_change`',
  'SELECT ''users.email_notifications_enabled already exists, nothing to do'' AS result'
);

PREPARE fms_update FROM @fms_statement;
EXECUTE fms_update;
DEALLOCATE PREPARE fms_update;

-- ---------------------------------------------------------------------
-- Part 2: forex_rates.partner_id
-- ---------------------------------------------------------------------

-- 2a. The new column. NULL = shared default rate.
SET @fms_column_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'forex_rates'
    AND COLUMN_NAME = 'partner_id'
);

SET @fms_statement := IF(
  @fms_column_exists = 0,
  'ALTER TABLE `forex_rates` ADD COLUMN `partner_id` int DEFAULT NULL COMMENT ''NULL = shared default rate, otherwise the partner that owns this rate'' AFTER `id`',
  'SELECT ''forex_rates.partner_id already exists, nothing to do'' AS result'
);

PREPARE fms_update FROM @fms_statement;
EXECUTE fms_update;
DEALLOCATE PREPARE fms_update;

-- 2b. Remove the old limit of one rate per date across all partners.
SET @fms_index_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'forex_rates'
    AND INDEX_NAME = 'unique_date'
);

SET @fms_statement := IF(
  @fms_index_exists > 0,
  'ALTER TABLE `forex_rates` DROP INDEX `unique_date`',
  'SELECT ''forex_rates.unique_date already removed, nothing to do'' AS result'
);

PREPARE fms_update FROM @fms_statement;
EXECUTE fms_update;
DEALLOCATE PREPARE fms_update;

-- 2c. One rate per date for each partner.
SET @fms_index_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'forex_rates'
    AND INDEX_NAME = 'unique_partner_date'
);

SET @fms_statement := IF(
  @fms_index_exists = 0,
  'ALTER TABLE `forex_rates` ADD UNIQUE KEY `unique_partner_date` (`partner_id`,`rate_date`)',
  'SELECT ''forex_rates.unique_partner_date already exists, nothing to do'' AS result'
);

PREPARE fms_update FROM @fms_statement;
EXECUTE fms_update;
DEALLOCATE PREPARE fms_update;

-- 2d. A rate must belong to a real partner, and is removed along with its partner.
SET @fms_fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'forex_rates'
    AND CONSTRAINT_NAME = 'forex_rates_ibfk_1'
);

SET @fms_statement := IF(
  @fms_fk_exists = 0,
  'ALTER TABLE `forex_rates` ADD CONSTRAINT `forex_rates_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE',
  'SELECT ''forex_rates_ibfk_1 already exists, nothing to do'' AS result'
);

PREPARE fms_update FROM @fms_statement;
EXECUTE fms_update;
DEALLOCATE PREPARE fms_update;
