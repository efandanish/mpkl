-- Export receipt_number values before rollback. Removing this column loses those values.
-- Deploy the matching previous application version during the same maintenance window.
ALTER TABLE development_charge_payments DROP COLUMN receipt_number;
