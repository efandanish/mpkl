-- Apply after Stage 3A. Existing rows retain legacy visibility and NULL reminder keys.
ALTER TABLE notifications
  ADD COLUMN audience VARCHAR(20) NOT NULL DEFAULT 'legacy',
  ADD COLUMN department VARCHAR(150) DEFAULT NULL,
  ADD COLUMN reminder_key VARCHAR(150) DEFAULT NULL,
  ADD UNIQUE KEY unique_project_reminder (reminder_key),
  ADD KEY notification_entity_action (entity_type, entity_id, action);
