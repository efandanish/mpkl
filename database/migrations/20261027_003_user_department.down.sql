-- Export user IDs and departments before rollback: this removes all assignments.
-- Prefer reverting application code while retaining the nullable column.
ALTER TABLE users DROP COLUMN department;
