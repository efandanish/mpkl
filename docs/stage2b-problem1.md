# Stage 2B — Problem 1

Scope: separate dashboard assessed charges from received payments. No client records, schema, reports, installment rules, authentication, CSRF or RBAC changes. No migrations applied.

Previously Jumlah Kutipan displayed SUM(development_charges.charge_amount), grouped by COALESCE(record_year, YEAR(created_at)).

Now:

- Jumlah Caj Pemajuan retains that assessed-charge formula, including unpaid charges and all existing OSC decisions. When the selected cohort contains NULL record_year rows, its footer marks the figure provisional, identifies the number of uncertain records and explains the created_at fallback.
- Jumlah Kutipan sums development_charge_payments.amount only for status Dibayar and payment_date from January 1 through December 31 inclusive. The DATE column has no time component. Parent registration year does not restrict receipts. NULL payment dates and pending/cancelled installments are excluded. MariaDB performs DECIMAL summation; PHP does not accumulate receipt values using floats. Empty totals return zero.
- Baki Bawa Hadapan, Jumlah Perbelanjaan and Baki Akhir Semasa retain their existing values/formulas. In particular closing balance still uses assessed charges, not the corrected collection variable. It is not a reconciled cash balance; correction is a separately approved task.

The five cards reuse the existing responsive design; the final card spans the two-column row. No changes to notification generation or year navigation.

Validation on PHP 7.4.33:

- Syntax checks passed for all five changed/new PHP files.
- `php-7.4.33 tests/modules-smoke.php --skip-migrations` passed, including Stage 1, Stage 2A functional and Stage 2B dashboard assertions. The option skips migration tests entirely; only the disposable schema is initialized from schema.sql.
- `node tests/stage2a-project-confirmation.js` passed.
- Client database row/schema fingerprints and receipt-file fingerprints matched before/after the isolated suite.
- `git diff --check` passed.

New cases cover unpaid, partial and full payment, cancellation, pending rows with dates, paid rows without dates, earlier parent cohorts, empty years, NULL record_year, January 1/December 31 boundaries, exact cents, repeat reads and both dashboard roles. Tests do not change actual client financial records.

Manual verification: log in at http://localhost/cajpemajuan/dashboard and select a year. Confirm five cards, separate assessed/received figures, the selected receipt period and a provisional notice where legacy years are uncertain. Compare collections to paid installments dated within that year, including older parent charges. Use only a disposable test environment to create/pay/cancel fixtures. Check mobile width and navigation. Opening, expenditure and closing remain the pre-existing calculations.

Outstanding decisions: opening/closing balances, expenditure reporting, reversal history, missing legacy payment dates, interest allocation and OSC assessment eligibility remain outside Problem 1. Existing cancellation changes active status rather than retaining a dated reversal; missing cash dates are not inferred. Stop after Problem 1.
