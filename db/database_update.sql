-- =====================================================================
-- GREATER FMS - database update
-- Adds the per-user email notification preference.
--
-- Engine : MySQL 8.0 (written against the Sql1800295_2 export of 2026-11-01)
-- Changes: one new column, users.email_notifications_enabled
-- Data   : no existing row is changed. Every existing and future user
--          defaults to 1 (enabled), so nobody's notifications change until
--          they turn them off themselves on the Settings page.
--
-- Forex access for Local Coordinators needs NO database change: it uses the
-- existing role_id / role_name checks in application/libraries/Auth_manager.php.
--
-- HOW TO APPLY (manually, against the existing database)
--   1. Back up the database first.
--   2. phpMyAdmin: select the database, open Import, choose this file, click Go.
--      Or from a shell:  mysql -u <user> -p <database_name> < db/database_update.sql
--   3. Safe to run twice: if the column already exists nothing is altered.
--      (MySQL has no ADD COLUMN IF NOT EXISTS, hence the check below.)
--
-- HOW TO VERIFY
--   SHOW COLUMNS FROM `users` LIKE 'email_notifications_enabled';
--   SELECT COUNT(*) AS total_users, SUM(email_notifications_enabled) AS enabled FROM `users`;
--   Right after applying, total_users and enabled should be equal.
--
-- HOW TO UNDO (this discards every user's saved preference)
--   ALTER TABLE `users` DROP COLUMN `email_notifications_enabled`;
-- =====================================================================

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
