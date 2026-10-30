-- Export reviewed record_year assignments before rollback: dropping them changes grouping.
-- No other columns or financial records are removed.
ALTER TABLE development_charges
    DROP CONSTRAINT chk_charge_record_year,
    DROP INDEX idx_charge_record_year,
    DROP COLUMN record_year;
