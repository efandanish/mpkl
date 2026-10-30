-- No backfill: NULL explicitly marks legacy years awaiting evidence-based review.
-- created_at remains the actual audit timestamp.
ALTER TABLE development_charges
    ADD COLUMN record_year SMALLINT UNSIGNED NULL DEFAULT NULL AFTER payment_plan,
    ADD INDEX idx_charge_record_year (record_year),
    ADD CONSTRAINT chk_charge_record_year CHECK (record_year IS NULL OR record_year BETWEEN 1000 AND 9999);
