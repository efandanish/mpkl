-- Apply manually after backup. Nullable for legacy receipts; no uniqueness policy assumed.
ALTER TABLE development_charge_payments
    ADD COLUMN receipt_number VARCHAR(100) NULL DEFAULT NULL AFTER receipt_file;
