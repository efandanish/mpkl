# Final fix F-02 — Charge and installment reconciliation

Date: 30 October 2026. Project: Sistem Akaun Caj Pemajuan.

**F-02 is fixed for application charge edits.** The exact failed audit scenario now returns HTTP 422 and leaves the original RM150,000 charge, all installments, notifications and payment/receipt history unchanged. Explicit consistent admin corrections remain supported. Nothing is recalculated silently.

This task does not address F-01, change interest policy/dashboard formulas, upgrade PHP, apply a client migration, repair existing client data, commit or push.

## Root cause and inspected paths

Reviewed `ChargeController.php`, `ChargeValidation.php`, `ChargeInstallments.php`, `DevelopmentCharge.php`, the charge edit view, dashboard JavaScript, and Stage 1–3B/Problem 4 regressions.

`ChargeController::update()` already locks the parent row and performs parent/schedule/notification writes through `ModuleController::atomic()`. Existing validation checked pending-row ownership, complete IDs, duplicate IDs, positive/valid amounts and dates; paid IDs were excluded from edits and the parent amount/plan were locked once payment existed.

The missing rule was aggregate principal reconciliation. An unchanged payment plan bypassed the new-record calculator, allowing the parent to change independently of pending obligations. One old regression even allowed RM300 paid + RM350 pending + RM300 pending under a RM900 parent. That fixture was corrected to a legitimate redistribution, RM300 + RM350 + RM250, while retaining its admin-edit and paid-history assertions.

`ChargeInstallments` remains the authoritative new-record calculator. Existing edit-plan JavaScript and manual legacy schedules were inspected but were not replaced with a new automatic recalculation path.

## Rule implemented

All principal comparisons and sums use integer cents through the existing `ChargeInstallments::cents()` converter. Provisional interest is never included.

Within the locked edit transaction:

1. Retain all existing field, CSRF, role, ownership, duplicate-ID and paid-state validation.
2. Construct the complete proposed schedule from stored rows, replacing only explicitly submitted pending amounts. Paid rows contribute their original stored principal and cannot be submitted as editable rows.
3. For a permitted plan replacement, use the explicitly submitted replacement rows; existing paid-plan locks still prohibit replacement after payment.
4. Require the schedule to have exactly 1, 3 or 4 rows for its plan, with installment numbers 1 through the expected count appearing once each, and principal total exactly equal to Jumlah Caj.
5. Enforce that rule whenever the edit changes parent principal, payment plan, a pending amount or a pending deadline. Also retain the invariant for an already complete/reconciled schedule.
6. An unchanged historically inconsistent/missing schedule may remain during a nonfinancial edit. This exception is derived from stored data and comparison with submitted values; there is no browser-controlled bypass flag.
7. Filter unchanged pending rows out of the write set. Metadata-only edits do not rewrite historical payment rows.

A principal/schedule mismatch returns HTTP 422 with:

> Jumlah prinsipal ansuran mesti sepadan dengan Jumlah Caj. Sila lengkapkan jadual mengikut pelan bayaran dan selaraskan jumlah ansuran sebelum menyimpan. Bunga anggaran 8% tidak termasuk dalam prinsipal.

Validation failures now throw a handled domain exception inside `atomic()`, causing a rollback before the existing error renderer displays submitted values. Unexpected domain failures still propagate rather than being disguised as validation errors. No parent/schedule/notification writes occur before successful validation.

### Administrator corrections

The chosen behavior is **reject a mismatch, never automatically recalculate**. For RM150,000 → RM180,000, the admin can explicitly supply RM100,000 / RM40,000 / RM40,000 and save successfully; dates remain exactly as supplied. No first deadline or replacement dates are inferred.

An explicit correction to the supported RM100,000 three-installment boundary may supply RM100,000 / RM0 / RM0. Changed zero amounts are permitted for boundary rows 2/3 only in that context; pre-existing zero rows can remain unchanged. Full-payment and four-installment edits retain their own counts and manual splits, with the same exact-total rule rather than a three-installment formula.

### Historical preservation

- Paid principal, due/payment dates, status, receipt reference and receipt file are never modified by this edit path.
- Existing parent amount/plan locks after payment remain intact.
- A valid redistribution among pending rows can still succeed if immutable paid principal plus pending principal equals the unchanged parent total.
- Unchanged legacy discrepancies, missing schedules, duplicate historical installment numbers, NULL deadlines and identical unparseable historical pending values can remain during nonfinancial edits.
- Unchanged historical fields are retained verbatim, including an invalid/missing deadline represented by a blank date input. They are not cleaned, inferred or rewritten.
- Unrecognized historical payment states are not guessed to be unpaid: financial edits/replacements are rejected for review.
- Any financial/schedule change must produce a complete, parseable, reconciled principal schedule. Incomplete or structurally ambiguous history therefore rejects unsafe financial changes rather than creating missing obligations automatically.
- Existing main-field validation still applies; this is not a blanket exemption from all prior validation requirements.

## UI

The existing edit-page layout and confirmation flow remain.

A short Malay explanation beside the schedule states that financial changes must reconcile with Jumlah Caj, that the admin must explicitly adjust unpaid installments, and that amounts/dates are not automatically recalculated. It also distinguishes the reference interest from principal.

Pending amount controls accommodate supported zero rows and unchanged historical negative values without treating those as valid new financial edits server-side. Historical missing/invalid deadlines are not made newly mandatory merely to save metadata. Actual changed values continue to pass server validation. Paid controls remain disabled/read-only as before.

The existing HTTP 422 renderer preserves the submitted parent amount, descriptive fields, pending values and selected reporting year for correction. The displayed attempted RM180,000 on the error form is retained input, not a persisted parent amount.

## Files changed

Modified:

- `app/utility/ChargeValidation.php`: complete proposed-schedule reconciliation, exact-cent comparisons, legacy preservation and unchanged-row filtering; supported zero boundary correction.
- `app/controllers/ChargeController.php`: explicit rollback on validation failure, retaining existing error handling and admin transaction boundaries.
- `app/views/charge/edit.php`: concise Malay guidance and legacy/zero-aware input constraints; no redesign.
- `tests/modules-smoke.php`: include F-02 coverage and correct the old inconsistent pending-edit fixture while retaining its workflow assertions.

Created:

- `tests/f02-reconciliation.php`: guarded disposable-database regression cases.
- `docs/final-fix-f02-installment-reconciliation.md`: this report.

Unchanged for this task: `DevelopmentCharge.php`, `ChargeInstallments.php`, `dashboard.js`, new-record calculation/interest rules, dashboard source/formulas, schema/migrations, dependencies, runtime configuration and earlier audit/implementation reports. Existing unrelated working-tree changes were retained.

## Exact reproduction before and after

A fresh before-fix run used the real create/update routes in a disposable database and reproduced the defect. The after-fix test uses the same automatic-create scenario, authenticated admin session and valid CSRF.

| Observation | Before fix | After fix |
|---|---|---|
| Runtime | PHP 7.4.33 | PHP 7.4.33 |
| Initial parent | RM150,000.00 | RM150,000.00 |
| Initial installments | RM100,000 / RM25,000 / RM25,000 | Same |
| Attempted parent correction | RM180,000.00 | RM180,000.00 |
| Submitted installments | Original unchanged rows | Original unchanged rows |
| HTTP response | **302 success** | **422 validation error** |
| Persisted parent | **RM180,000.00** | **RM150,000.00** |
| Persisted installments | RM100,000 / RM25,000 / RM25,000 | Same, all fields unchanged |
| Residual without an obligation | **RM30,000.00** | **None introduced** |

The after-fix snapshot also compares parent metadata, all payment fields, related notifications and temporary receipt contents before/after rejected edits. No receipt, status or payment history changes are accepted on failure. Separate tests use an actual temporary paid receipt file/reference and verify those remain byte-for-byte/value-for-value unchanged during both rejected edits and an allowed pending redistribution.

Evidence:

```text
/tmp/cajpemajuan-f02/before-result.json
/tmp/cajpemajuan-f02/before-run.log
/tmp/cajpemajuan-f02/after-result.json
/tmp/cajpemajuan-f02/after-run.log
```

## Tests and results

All PHP execution used `/Applications/XAMPP/xamppfiles/bin/php`, **7.4.33**. PHP 8.2.5 was not run or upgraded. Tests use E_ALL; `-d track_errors=0` disables only the obsolete existing PHP 7.4 feature in test processes and does not mask application diagnostics or alter the server INI.

| Check | Result |
|---|---|
| Before-fix existing suite and reproduction | 723 existing assertions PASS; exact defect reproduced with HTTP 302 / saved RM180,000 |
| Final full integration suite | **799 PASS, exit 0**: existing 723 plus **76 F-02 assertions** |
| PHP syntax | **269 files**, zero failures |
| Problem 4 calculator | **40 PASS**, exit 0 |
| Compatibility smoke | **33 PASS**, exit 0 |
| Startup smoke | **6 PASS**, exit 0 |
| Problem 4 JavaScript | **42 assertions PASS** |
| Existing project confirmation JavaScript | PASS |
| HTTP runtime log check | No PHP warnings, notices, deprecations, parse/fatal errors or ErrorException entries |
| Client schema/row fingerprints | Unchanged before/after both integration runs |
| Client receipt paths/content fingerprints | Unchanged |
| Whitespace check | `git diff --check` PASS |

Coverage includes:

- Exact F-02 rejection, Malay explanation, input/year retention and unchanged stored parent/installments.
- Explicit consistent correction, unchanged dates and exclusion of provisional interest from obligations.
- Missing arrays, missing row, duplicate IDs, extra/foreign ID and nested malformed amount.
- Ordinary-user denial and missing-CSRF rejection, with unchanged financial history.
- Exact-cent correction and one-cent mismatch.
- Explicit RM100,000 correction with supported zero rows.
- Full-payment correction with NULL deadline, nonuniform four-installment corrections and mismatched totals.
- Explicit replacement-plan reconciliation and conservative rejection of an unrecognized historical payment state.
- Unchanged legacy inconsistent/missing/negative-principal records remaining readable and permitting nonfinancial edits; unsafe financial changes rejected.
- Historical duplicate installment numbers preserved only when unchanged.
- Paid-parent lock, forged paid-row IDs, paid-plus-pending total, and valid pending redistribution with original paid row/reference/file unchanged.
- Existing Stage 1 security, Stage 2A migration/receipt/year tests, Stage 2B dashboards, Problem 2 permissions, Stage 3A/3B department/notification tests and Problem 4 regressions.

Writes occurred only in randomly named `cajpemajuan_modules_<hex>_test` databases and temporary session/receipt directories. The suite drops its disposable database afterward and compares client fingerprints. No deployment migration was authored/applied; unchanged migration rehearsal tests operated only on disposable schemas.

Other logs: `/tmp/cajpemajuan-f02/calculator.log`, `compatibility.log`, `startup.log`, `javascript.log`, `lint.json`.

## Remaining limitations

- No formula/version marker exists to reliably distinguish automatic-created records from historical manual records. This fix guarantees principal conservation/count integrity for edits subject to reconciliation; it does not retrospectively impose the new-record percentage split or three-month dates on every historical edit.
- No historical data repair, migration, automatic recalculation or silent obligation replacement is performed. Existing inconsistent records can remain inconsistent through metadata-only edits by deliberate compatibility policy.
- Missing/duplicate/unparseable history may require a separately reviewed correction/replacement process before financial edits are safe. Paid obligations remain immutable; this task does not provide an override to repair paid history.
- Legacy schedule-generation paths retain their existing behavior; this task protects validated edits rather than retroactively changing those generators.
- Low-level model/direct SQL imports are not new reconciliation APIs; the protection applies to the application's validated edit route. Existing model APIs/schema were not redesigned.
- The existing nine-main-field and other unrelated validation rules remain; unchanged legacy principal preservation does not make all incomplete parent metadata valid.
- No graphical-browser session was repeated for this task. HTTP tests check the rendered validation response, and JavaScript regressions pass; the edit layout/JavaScript handler was not redesigned.
- **F-01 remains open and unchanged.** This fix does not turn the earlier final audit into unconditional client acceptance or resolve interest/PIC policy decisions.

## Manual verification — disposable installation only

1. Use an isolated database/receipt store; do not create these fixtures at the client URL against client records. Log in as admin and create RM150,000, 3 Ansuran, with an explicit first deadline such as 2024-01-31. Confirm stored RM100,000 / RM25,000 / RM25,000.
2. Edit Jumlah Caj to RM180,000, leave the three amounts/dates unchanged and save. Expect HTTP 422 and the Malay reconciliation message. Reload from the database/detail page: parent must still be RM150,000 and all payment/receipt/status fields unchanged. The retry form may retain the attempted RM180,000 as submitted input.
3. Submit RM180,000 with explicit amounts RM100,000 / RM40,000 / RM40,000. Expect success and the entered values/dates, with no provisional interest obligation.
4. Alter only a pending amount by one cent without reconciling the total; expect 422. Remove/duplicate/add a schedule ID; expect 422 and no partial save.
5. Check full payment and a nonuniform four-installment record using correct and incorrect sums. Verify no three-installment percentage formula is imposed.
6. Record a disposable receipt against one installment, then attempt a paid-row/parent-principal change. Expect rejection and unchanged paid amount/date/status/reference/file. A correct redistribution within the remaining unpaid total may still save.
7. Inspect a seeded legacy discrepancy or missing schedule. An unchanged nonfinancial edit should succeed without rewriting schedule rows or inventing dates; an unreconciled financial change must fail.
8. Repeat an edit as an ordinary user and with missing/invalid CSRF; expect denial and unchanged data.
9. Run the guarded automated suite with the explicit existing runtime:

   ```sh
   /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/modules-smoke.php
   /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/problem4-calculator.php
   /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/compatibility-smoke.php
   /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/startup-smoke.php
   node tests/problem4-installments.js
   node tests/stage2a-project-confirmation.js
   git diff --check
   ```

No commit or push was made. Work stops at F-02.
