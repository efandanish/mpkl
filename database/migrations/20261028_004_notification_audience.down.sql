-- WARNING: Removing audience metadata makes restricted notifications globally visible to old code.
-- Prefer retaining these columns and restricting access during rollback.
ALTER TABLE notifications
  DROP INDEX unique_project_reminder,
  DROP INDEX notification_entity_action,
  DROP COLUMN reminder_key,
  DROP COLUMN department,
  DROP COLUMN audience;
