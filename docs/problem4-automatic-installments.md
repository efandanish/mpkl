# Problem 4 — Automatic development charge installments

Implemented 2026-10-30 for Sistem Akaun Caj Pemajuan. Scope: automatic principal schedules and explicit deadlines for **new 3 Ansuran records**. No application redesign, client database changes, live migrations, historical schedule conversion, package changes, commit or push.

The referenced `Maklum Balas Sistem Caj Pemajuan V1.pdf` is not present in this checkout. This implementation follows the detailed rules supplied in the request and the existing requirements/audit documents; no independent verification of PDF page 4 is claimed.

## Existing behavior inspected

Reviewed `ChargeController`, `DevelopmentCharge`, `ChargeValidation`, create/edit/payment views, dashboard JavaScript, schema, existing requirements and regressions.

- Bayaran Penuh creates one principal row with the existing NULL deadline convention.
- New 3 Ansuran and 4 Ansuran previously accepted manually entered principal amounts and dates.
- Existing edits validate ownership, pending status, schedule IDs and dates. Paid rows are excluded from edits. Plan changes after payment are prohibited.
- An admin-only legacy generation endpoint previously split 3 Ansuran into equal thirds with NULL deadlines. It could contradict the new rule; its 3 Ansuran branch is now blocked with HTTP 422 and a Malay explanation. Existing schedules are not changed. Its full-payment and four-installment behavior remains unchanged.
- Existing create persistence already uses one transaction for parent, payment rows and notification. This boundary is retained. PDO errors roll back the transaction; existing security regression coverage exercises transaction rollback.

## Principal formulas and rounding

All authoritative calculation uses integer cents on 64-bit PHP. Input uses the existing DECIMAL(12,2) limit: at most ten whole digits and two decimal digits, no exponents or thousands separators. No float-based principal arithmetic is used.

Let `C` be charge cents and `R(x)` mean rounding a nonnegative fractional cent to the nearest cent, with halves rounded up.

For RM100,000.00 through RM200,000.00 inclusive:

```text
P1 = 10,000,000 cents
P2 = R((C − P1) / 2)
P3 = C − P1 − P2
```

Above RM200,000.00:

```text
P1 = R(C / 2)
P2 = R(C / 4)
P3 = C − P1 − P2
```

The last row receives the residual: `P1 + P2 + P3 = C` exactly. Values are persisted as decimal strings with two decimal places.

Below RM100,000.00, new 3 Ansuran creation returns HTTP 422 with:

> Pelan 3 Ansuran memerlukan Jumlah Caj sekurang-kurangnya RM100,000.00. Sila pilih Bayaran Penuh bagi jumlah yang lebih rendah.

Bayaran Penuh remains available. At exactly RM100,000.00, the explicit formula gives RM100,000.00 / RM0.00 / RM0.00; all three rows and their deadlines are retained. Existing edit validation permits those zero rows to remain unchanged instead of making unrelated metadata edits impossible. This does not permit arbitrary zero amounts in new manual four-installment schedules.

## Separate reference interest

```text
Anggaran Bunga 8% = R((C − P1) × 8 / 100)
```

This is a display-only estimate rounded to two decimal places. It is not stored as a charge, payment, fee, collection or extra schedule row. Submitted browser interest fields are ignored. No annual, compounding, prorating or per-installment interest rule is introduced. Existing financial queries, financial formulas and dashboard totals are unchanged.

## Explicit deadlines and calendar policy

New 3 Ansuran requires `first_installment_due_date`, a valid YYYY-MM-DD date in the supported four-digit year range. No default is derived from today's date, creation timestamp or OSC approval.

1. Row 1 uses the supplied deadline.
2. Row 2 adds three calendar months to row 1, clamping its day to the destination month's last valid day.
3. Row 3 applies the same operation to row 2.

This is sequential clamping, not retention of an original month-end anchor. Examples:

| Initial deadline | Second deadline | Third deadline |
|---|---|---|
| 2024-01-31 | 2024-04-30 | 2024-07-30 |
| 2023-08-31 | 2023-11-30 | 2024-02-29 |
| 2022-08-31 | 2022-11-30 | 2023-02-28 |
| 2024-02-29 | 2024-05-29 | 2024-08-29 |
| 2099-11-30 | 2100-02-28 | 2100-05-28 |

Invalid/missing initial dates and dates whose generated schedule exceeds year 9999 are rejected.

## Worked examples

Amounts below are RM. Principal rows exclude the separate estimate.

| Jumlah Caj | Ansuran 1 | Ansuran 2 | Ansuran 3 | Anggaran Bunga 8% |
|---|---:|---:|---:|---:|
| 99,999.99 | Rejected for 3 Ansuran | — | — | — |
| 100,000.00 | 100,000.00 | 0.00 | 0.00 | 0.00 |
| 150,000.00 | 100,000.00 | 25,000.00 | 25,000.00 | 4,000.00 |
| 180,000.00 | 100,000.00 | 40,000.00 | 40,000.00 | 6,400.00 |
| 200,000.00 | 100,000.00 | 50,000.00 | 50,000.00 | 8,000.00 |
| 200,000.01 | 100,000.01 | 50,000.00 | 50,000.00 | 8,000.00 |
| 300,000.00 | 150,000.00 | 75,000.00 | 75,000.00 | 12,000.00 |
| 100,000.01 | 100,000.00 | 0.01 | 0.00 | 0.00 |
| 300,000.03 | 150,000.02 | 75,000.01 | 75,000.00 | 12,000.00 |

Fractional-cent rounding refers to the calculated percentages of an amount already expressed in cents. A charge entered with three decimal places, such as `150000.001`, is rejected rather than silently rounded.

## Frontend and backend behavior

The existing form/card layout is retained. Selecting 3 Ansuran shows an explicit initial-deadline field, three read-only principal/date rows, a separate “Anggaran Bunga 8%” estimate, and a Malay note explaining its provisional status. Amount and deadline changes recompute the preview. Browser validation blocks invalid amounts/deadlines. Switching away clears custom validity and disables the initial-deadline field. Bayaran Penuh hides installment rows; 4 Ansuran retains four manually editable rows and validation-error restoration.

`ChargeInstallments` is the authoritative PHP calculator. JavaScript is a preview only. Backend validation:

- Recalculates principal and all three deadlines from the charge and explicit initial deadline.
- Accepts an omitted preview array set so server-side derivation works independently of submitted derived fields.
- If any preview array is supplied, requires all three arrays, exactly three sequential entries, the expected installment numbers, equal cent amounts and exactly matching dates.
- Rejects missing/extra/duplicate/nested rows, invalid dates, malformed amounts and mismatches with HTTP 422, before persistence.
- Persists its own calculated rows, never the preview values.
- Retains ordinary-user create permission, admin-only updates/payment mutation, login checks, CSRF, record year, receipt numbers and notifications.
- Also prohibits changing the parent principal once a paid row exists. The paid charge amount is read-only in the edit form. Existing paid-row and plan protections remain enforced server-side.

There is no automatic recalculation on existing-record edits. Existing pending-row editing and unpaid manual plan-change behavior are retained, including the legacy manual `new_installment_*` edit path. Thus these creation rules do not retroactively certify, replace or rebalance historical schedules. Existing paid amounts, deadlines, receipt data and statuses are not converted.

## Files created/modified

Created:

- `app/utility/ChargeInstallments.php`: integer-cent principal/estimate calculation and deterministic calendar dates.
- `public/js/charge-installments.js`: pure preview helpers and create-form binding.
- `tests/fixtures/problem4-installments.json`: shared independently specified expected principal/interest examples.
- `tests/problem4-calculator.php`: PHP calculation/date/input checks.
- `tests/problem4-installments.js`: JavaScript calculation checks and mounted-form listener tests.
- `tests/problem4-http.php`: guarded disposable-database HTTP and persistence coverage.
- `docs/problem4-automatic-installments.md`: this report.

Modified:

- `app/utility/ChargeValidation.php`: authoritative new-record schedule validation, paid principal lock and unchanged-zero edit support.
- `app/controllers/ChargeController.php`: block the conflicting legacy equal-third generation route for existing 3 Ansuran records.
- `app/views/charge/create.php`: explicit initial deadline and separate estimate/message controls.
- `app/views/charge/edit.php`: paid principal read-only field.
- `app/views/charge/payment.php`: explain why an empty existing 3 Ansuran schedule is not generated automatically.
- `app/views/layout/cajpemajuan/footer.php`: load the preview script before dashboard bindings.
- `public/js/dashboard.js`: replace the old create-form manual-row handler with the scoped preview mount; other modules' handlers and historical edit handlers remain.
- `tests/modules-smoke.php`: include new HTTP coverage; seed the previous RM900 manual three-row fixture directly as historical data rather than trying to create it through the new API. Previous security/payment/report assertions remain.

`DevelopmentCharge.php`, financial model queries, schema/migrations, Composer and XAMPP/Apache settings were reviewed but not modified for Problem 4.

## Runtime verification and test results

Before testing:

- `/Applications/XAMPP/xamppfiles/bin/php -v`: **PHP 7.4.33**, NTS, x86_64.
- Default `php -v`: **PHP 8.5.3**, Homebrew; this is outside the prepared application range and was not used for these tests.
- XAMPP `httpd -v`: **Apache 2.4.54**.
- Read-only HTTP HEAD to `http://localhost/cajpemajuan` returned 301 to the trailing-slash URL with `Server: Apache/2.4.54 ... PHP/7.4.33`. The existing XAMPP include loads `libphp7.so`. This confirms the active server advertises the existing PHP 7.4.33 installation; no PHP 8.2 switch occurred.
- **Exact PHP 8.2.5 is unavailable. PHP 8.2.5 runtime verification is NOT claimed.** See `docs/php825-upgrade.md` for separate-runtime installation and later switching instructions.

Final results:

| Check | Result |
|---|---|
| PHP syntax validation | 265 files across app/scripts/tests/public/vendor; zero failures |
| PHP calculator tests | 40 PASS; exit 0 |
| JavaScript calculator and mounted-form tests | 42 assertions; exit 0 |
| Existing project confirmation JavaScript tests | PASS; exit 0 |
| JavaScript syntax checks | PASS for preview and dashboard scripts |
| Compatibility smoke | 30 PASS; exit 0 |
| Startup smoke | 6 PASS; exit 0 |
| Full HTTP/integration/migration regressions | **723 PASS; exit 0** (previous 641 plus 82 additional assertions) |
| HTTP warning/deprecation/notice/fatal check | PASS; none detected |
| Client database schema/row fingerprints | Unchanged before/after |
| Client receipt paths/content fingerprints | Unchanged before/after |
| Git whitespace validation | `git diff --check` PASS |

All PHP tests ran using the explicit **7.4.33** binary. E_ALL remained enabled. Test invocations used `-d track_errors=0` because the existing XAMPP PHP 7.4 INI otherwise emits an obsolete-directive startup warning; no runtime warning mask or server INI was weakened or edited.

HTTP writes used only a randomly named `cajpemajuan_modules_<hex>_test` database, separate session/receipt paths, and an isolated local PHP HTTP server using the real application router. Prior migration regressions operated only on disposable schemas. The test database and temporary server were removed afterward. Client database access was limited to read-only fingerprints. No fixtures were sent to the live application URL.

Coverage includes all requested charge thresholds, odd-cent percentage residuals and the maximum supported charge, missing/invalid first deadlines, leap years including year 2100, sequential month-end clamping, malformed/tampered form values, ordinary-user creation, CSRF, notifications, record year, no interest obligations/collections, paid principal/receipt protection, zero-row metadata edits, blocked legacy generation, and retained full/four-payment behavior. Existing login/logout, dashboard, financial calculation, receipt upload/cancellation, security and department-notification regressions also pass.

One intermediate run exposed a test assertion comparing the existing `getTotalPaid()` float return to the string `100000.00`. The assertion was corrected to compare the paid principal value; application financial logic was not changed. Final view review also caught a condition attached to the receipt form instead of the empty-schedule generator. It was corrected before completion, and explicit receipt-form render assertions were added for all nine automatic schedule examples. The final complete suite passed.

No interactive graphical browser session was performed. UI event checks use the actual preview module with DOM stubs; HTTP checks use the real router. Exact PHP 8.2.5 execution remains a release-verification requirement.

Local test logs: `/tmp/problem4-calculator.log`, `/tmp/problem4-modules.log`, `/tmp/problem4-lint.log`, `/tmp/problem4-compatibility.log`, `/tmp/problem4-startup.log`.

## Assumptions and boundaries

- Half-up intermediate cent rounding is explicit; final principal absorbs the residual. The supplied request did not specify a tie-breaking policy.
- The sequential clamping policy above is deterministic and follows “second deadline + 3 calendar months”; it does not make month-end sticky.
- The RM100,000 boundary retains two zero-principal rows. No automatic payment, receipt, status change or fee is invented for them.
- The estimate is rounded for display only. Any eventual enforceable interest policy requires a separate instruction; none is introduced here.
- Existing records, including existing records whose plans are changed manually by an admin, remain outside automatic new-record calculation. No schema/version marker was added.
- Historical four-installment behavior remains unchanged, including its separate legacy generator. This task does not repair or reconcile legacy four-installment rounding.
- Browser preview requires JavaScript; the server can calculate without submitted preview arrays when the explicit initial deadline is provided.

## Manual verification on a disposable installation

Do not perform these creation/payment steps against client records. Use a separate checkout/configuration connected to a disposable database. `http://localhost/cajpemajuan` is the deployment base URL; the automated test server uses a separate loopback port.

1. Confirm CLI PHP with the explicit executable and verify the web SAPI/version independently. For a later PHP 8.2.5 deployment, require exactly `8.2.5` in CLI and the protected web diagnostic before rerunning these checks.
2. Log in as an ordinary user. Open Caj Pemajuan → Daftar Caj. Fill the existing required fields and choose the desired record year.
3. Select 3 Ansuran and enter RM150,000.00. Verify principal RM100,000.00 / RM25,000.00 / RM25,000.00, plus separate “Anggaran Bunga 8%” RM4,000.00. The estimate must not be part of the scheduled principal.
4. Enter initial deadline 2024-01-31. Verify 2024-04-30 and 2024-07-30. Change it to 2023-08-31 and verify 2023-11-30 and 2024-02-29. Derived amounts/dates must be read-only and update when their inputs change.
5. Try RM99,999.99 under 3 Ansuran; verify the Malay message and no creation. Select Bayaran Penuh and verify that the amount is allowed under the prior full-payment behavior. Check RM100,000.00 produces the documented two zero rows.
6. Create valid threshold examples from the table. Inspect saved payment rows and record year; scheduled principal must equal charge principal exactly, and a creation notification must exist. Verify the dashboard does not add the provisional interest to assessment, collections or balance.
7. Through a valid-CSRF POST in the disposable installation, alter an amount/date, duplicate an installment number, omit the initial deadline or submit an invalid leap date. Expect HTTP 422 and no partial charge/schedule insertion.
8. As an admin, record a receipt against an eligible principal installment using the existing receipt workflow. Verify paid amount/receipt number remain intact; attempts to change the paid principal/plan/paid row must fail. An ordinary user must remain unable to update or record/cancel another mutation.
9. Inspect a seeded historical schedule before and after unrelated metadata edits; verify no automatic formula/date conversion. The empty legacy 3 Ansuran generation route must return 422. Verify ordinary-user creation for Bayaran Penuh and manually entered 4 Ansuran still works.
10. Rerun:

    ```sh
    /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/problem4-calculator.php
    node tests/problem4-installments.js
    node tests/stage2a-project-confirmation.js
    /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/compatibility-smoke.php
    /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/startup-smoke.php
    /Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/modules-smoke.php
    git diff --check
    ```

    Once exact PHP 8.2.5 exists, repeat PHP lint and these PHP commands using its explicit path (with a fresh PHP 8.2 INI); require E_ALL and inspect logs. Do not claim the 7.4 results establish 8.2.5 runtime behavior.

## Git status

The workspace already contained substantial modified/untracked files from Stage 1–3B and PHP compatibility preparation. They were preserved. The Problem 4 file list above isolates this task; the full `git status --short` snapshot below includes that earlier work. No commit or push was performed.

```text
 M app/controllers/AuthController.php
 M app/controllers/ChargeController.php
 M app/controllers/DashboardController.php
 M app/controllers/ModuleController.php
 M app/controllers/NotifyController.php
 M app/controllers/ProjectController.php
 M app/controllers/ReportController.php
 M app/controllers/UserController.php
 M app/core/Controller.php
 M app/core/Session.php
 M app/core/Startup.php
 M app/core/components/SecurityComponent.php
 M app/models/Database.php
 M app/models/DevelopmentCharge.php
 M app/models/Notification.php
 M app/models/Project.php
 M app/models/User.php
 M app/views/auth/login.php
 M app/views/charge/create.php
 M app/views/charge/edit.php
 M app/views/charge/index.php
 M app/views/charge/payment.php
 M app/views/dashboard/partials/summary-cards.php
 M app/views/layout/cajpemajuan/footer.php
 M app/views/layout/cajpemajuan/header.php
 M app/views/layout/cajpemajuan/sidebar.php
 M app/views/layout/cajpemajuan/topbar.php
 M app/views/notification/index.php
 M app/views/project/create.php
 M app/views/project/edit.php
 M app/views/project/finance.php
 M app/views/project/index.php
 M app/views/report/charges.php
 M app/views/user/create.php
 M app/views/user/edit.php
 M app/views/user/index.php
 M composer.json
 M composer.lock
 M database/schema.sql
 M public/css/app.css
 M public/js/dashboard.js
 M public/js/project-finance.js
 M scripts/check-setup.php
 M tests/compatibility-smoke.php
 M tests/modules-smoke.php
?? app/utility/ChargeInstallments.php
?? app/utility/ChargeValidation.php
?? app/utility/CompatibleCaptchaBuilder.php
?? app/utility/Departments.php
?? app/utility/ReportingYear.php
?? app/views/charge/validation.php
?? app/views/project/create-schedule-rows.php
?? app/views/project/undated.php
?? database/migrations/
?? docs/client-requirements-audit-v1.md
?? docs/php825-upgrade.md
?? docs/problem3b-department-notifications.md
?? docs/problem4-automatic-installments.md
?? docs/stage1-security-data-integrity.md
?? docs/stage2a-functional-improvements.md
?? docs/stage2b-problem1.md
?? docs/stage3a-user-departments.md
?? public/js/charge-installments.js
?? tests/fixtures/
?? tests/php825-nullable.php
?? tests/problem2-permissions.php
?? tests/problem3b-migrations.php
?? tests/problem3b-notifications.php
?? tests/problem4-calculator.php
?? tests/problem4-http.php
?? tests/problem4-installments.js
?? tests/stage1-security.php
?? tests/stage2a-functional.php
?? tests/stage2a-migrations.php
?? tests/stage2a-project-confirmation.js
?? tests/stage2b-dashboard.php
?? tests/stage3a-departments.php
?? tests/stage3a-migrations.php
```
