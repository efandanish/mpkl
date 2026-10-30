-- Additive only. Existing users stay unassigned; no UPDATE/backfill.
ALTER TABLE users ADD COLUMN department VARCHAR(150) NULL DEFAULT NULL AFTER role;
