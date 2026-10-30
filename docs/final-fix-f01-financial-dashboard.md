# F-01 — Financial dashboard accuracy

## Outcome and root cause

F-01 is resolved. The dashboard controller hardcoded opening balance and expenditure to zero, then calculated closing balance from assessed charges. Assessed charges include unpaid obligations and cannot establish cash received. The original disposable acceptance probe reproduced all three incorrect values before this change.

The dashboard now obtains recorded expenditure from its ledger and explicitly withholds opening/closing balances. No opening value or closing formula has been activated. F-02, installment calculation, receipts, notifications, permissions, dependencies and PHP configuration are unchanged by this task.

## Expenditure source and accounting basis

Source: `project_expenses.amount` (`DECIMAL(12,2)`) and `project_expenses.payment_date` (`DATE`). Each retained ledger row contributes once. The new `Project::getExpenditureByPaymentYear()` uses inclusive January 1–December 31 boundaries, SQL DECIMAL SUM, and no project/schedule joins. It includes manual and schedule-linked expenses, including expenses on earlier-year projects. Claim date, project start/creation date and expense creation timestamp do not determine the cash year. Decimal strings remain exact through display, without conversion to floating point.

There is no expense status column. A retained recorded expense is counted regardless of project or schedule status: completing/cancelling a project does not reverse its expenditure. Schedule obligations without an expense row do not count. Two real expense rows with equal amounts both count; joins cannot multiply rows.

Existing finance workflows were inspected and preserved:

- `addManualExpense` validates amount/date and creates a ledger row.
- `recordPayment` validates amount/date, locks the parent in a transaction, checks the pending schedule, inserts one expense and marks the schedule paid. Duplicate payment attempts are rejected.
- `cancelPayment` deletes the expense and reopens its linked schedule only when no other retained expenses remain.
- There is no expense-update endpoint. Project deletion cascades its expenses; schedule deletion preserves expenses with a NULL schedule link.

These are retained-ledger totals, not independently reconciled bank balances. Existing cancellation/deletion behavior can restate historical totals; no reversal-date model or immutable accounting ledger is introduced.

Reliability fallback: any unassignable legacy date (NULL, zero, partial or impossible calendar date) makes the annual expenditure unavailable because its correct year is unknown. A NULL/negative amount in the selected year also makes that year's expenditure unavailable. Invalid amounts in other known years do not block the selected year. The card displays **Belum Disahkan**, identifies the number of affected records and says no value was estimated. No dates or financial rows are repaired/backfilled. Verified absence of expenses produces **RM 0.00**.

Existing project reports and project summary use projects selected by start year with lifetime expenses. Those queries remain unchanged. The annual dashboard uses expenditure payment year across all projects; the two bases can legitimately differ. The card explicitly states its basis and inclusion of earlier-year projects.

## Before and after: original acceptance scenario

Disposable fixtures: RM300,000 assessed, RM100,000 paid, RM200,000 unpaid, RM20,000 actually recorded expenditure dated in the selected year on an earlier-year project.

| Card | Before | After |
|---|---|---|
| Baki Bawa Hadapan | RM 0.00 | Belum Disahkan |
| Jumlah Caj Pemajuan | RM 300,000.00 | RM 300,000.00 |
| Jumlah Kutipan | RM 100,000.00 | RM 100,000.00 |
| Jumlah Perbelanjaan | RM 0.00 | RM 20,000.00 |
| Baki Akhir Semasa | RM 300,000.00 | Belum Tersedia |

Closing guidance: “Baki akhir semasa memerlukan baki bawa hadapan yang disahkan, kutipan sebenar dan perbelanjaan yang sah.” No approved opening-balance source currently exists. Any future closing formula, opening authorization and reporting scope require separate accounting approval.

## Files changed in this task

- `app/models/Project.php`: add exact annual recorded-expenditure query and reliability result; existing report/finance methods unchanged.
- `app/controllers/DashboardController.php`: use that result; remove fabricated balances and assessed-based closing calculation.
- `app/views/dashboard/partials/summary-cards.php`: preserve five-card layout, show unavailable balances and Malay explanations, format exact decimal strings.
- `tests/modules-smoke.php`: extract cards by label, replace obsolete placeholder expectations and include F-01 cases.
- `tests/stage2b-dashboard.php`: replace obsolete placeholder expectations while preserving valid collection/year assertions.
- `tests/f01-dashboard.php` (new): isolated acceptance and expenditure-boundary coverage.
- `docs/final-fix-f01-financial-dashboard.md` (new): this report.

A task-start source-hash comparison establishes this scope despite the pre-existing dirty worktree. F-02 source/tests, financial calculation, receipt and notification implementations, schema/migrations, JavaScript and PHP configuration were not modified during this task.

## Runtime, tests and results

All PHP checks used `/Applications/XAMPP/xamppfiles/bin/php`, **PHP 7.4.33**, against the existing MariaDB 10.4.27 service. Active XAMPP runtime was not switched. PHP 8.2.5 was neither used nor claimed verified here. Test processes use E_ALL; `-d track_errors=0` disables the obsolete PHP 7.4 option for the processes only, without hiding application warnings or changing server configuration.

| Check | Result |
|---|---|
| Before-change isolated regression run plus original F-01 probe | Existing 799 assertions passed; defect reproduced with values above |
| Updated full HTTP integration suite | **844 assertions passed**: previous 799 plus 45 F-01 assertions |
| PHP syntax, application/scripts/tests/public/vendor/docker | **270 files**, zero failures |
| Problem 4 PHP calculator | **40 assertions passed** |
| Compatibility smoke | **33 assertions passed** |
| Startup smoke | **6 assertions passed** |
| Problem 4 JavaScript | **42 assertions passed** |
| Project confirmation JavaScript | Fallback, cancellation and repeated-submission checks passed |
| HTTP diagnostic scan | No PHP warnings, notices, deprecations, fatal/parse errors or ErrorException entries |
| Client database schema/row fingerprints and receipt paths/content | Unchanged before/after |
| Whitespace validation | `git diff --check` passed |

Coverage includes the original F-01 probe for admin and ordinary user; verified zero with unpaid/paid-status schedules lacking expenses; January 1/December 31 and adjacent years; earlier-year projects; equal-valued expenses and exact cents; completed/cancelled project statuses; real scheduled payment, duplicate rejection and cancellation; manual expense cancellation; older-charge receipts in the current payment year; unpaid charges and cancelled payments; invalid/unknown legacy dates; invalid amounts; year navigation; five-card layout; and exact formatting beyond floating-point precision. The date/amount anomalies were synthetic fixtures confined to disposable databases, never client records.

Stage 1 security, Stage 2A migrations/receipts, Stage 2B dashboard, Problem 2 permissions, Stage 3A/3B departments/notifications, Problem 4 and F-02 regression modules all passed in the integration run. Migration rehearsals were confined to disposable databases; no deployment migration was applied. Existing valid assertions were retained; only obsolete financial-placeholder expectations were replaced.

One intermediate run failed because a new test helper treated the boolean return from `Project::addSchedule()` as an inserted ID. The helper now queries the actual fixture ID. No production finance behavior was changed to accommodate it; the final complete run passed.

The harness creates randomized disposable schemas and isolated receipt/session storage, then removes them. Client database access was read-only fingerprinting; there were no client data/schema mutations or recorded-expense changes. This task verified actual HTTP responses and rendered markup, not an interactive graphical browser session.

## Remaining assumptions and manual verification

An expense row establishes a recorded expenditure under the existing application workflow, not external accounting certification. Unknown legacy dates require separate authorized review. Opening balance remains unverified, so closing balance stays unavailable even when expenditure is zero. No accounting policy or new balance formula is silently introduced.

On a disposable installation only:

1. Select a year and create RM300,000 assessed charges, recording only RM100,000 paid. Record a RM20,000 project expense with a payment date in that year, preferably on an earlier-year project.
2. Open dashboard as admin and ordinary user. Expect assessed RM300,000.00, collected RM100,000.00, spent RM20,000.00, opening Belum Disahkan and closing Belum Tersedia. Read the Malay explanation.
3. Select a year with no recorded expense. Expect RM0.00 expenditure with both balances still unavailable. Verify year links remain usable.
4. Record expenses on January 1 and December 31, and on adjacent-year dates. Verify only selected-year payment dates count, including earlier-year projects. Pending obligations must not count.
5. Cancel a recorded expense through the existing finance workflow. Verify its amount disappears from its payment year's expenditure. Confirm cancelled charge receipts do not count toward collections.
6. Run the automated suite below for invalid legacy-date fixtures, precise cents, duplicates, permissions, F-02 and other regressions. Do not inject malformed records into the client database.

```sh
/Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/modules-smoke.php
/Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/problem4-calculator.php
/Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/compatibility-smoke.php
/Applications/XAMPP/xamppfiles/bin/php -d track_errors=0 -d error_reporting=-1 tests/startup-smoke.php
node tests/problem4-installments.js
node tests/stage2a-project-confirmation.js
```

## Git status

No commit or push was performed. The following snapshot includes prior uncommitted work; it is not a list of changes made by this task. The task-specific scope is listed above.

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
 M app/core/Redirector.php
 M app/core/Request.php
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
?? docker/
?? docs/client-requirements-audit-v1.md
?? docs/final-acceptance-audit.md
?? docs/final-fix-f01-financial-dashboard.md
?? docs/final-fix-f02-installment-reconciliation.md
?? docs/php825-upgrade.md
?? docs/problem3b-department-notifications.md
?? docs/problem4-automatic-installments.md
?? docs/stage1-security-data-integrity.md
?? docs/stage2a-functional-improvements.md
?? docs/stage2b-problem1.md
?? docs/stage3a-user-departments.md
?? public/js/charge-installments.js
?? scripts/php825
?? scripts/php825-stage
?? tests/f01-dashboard.php
?? tests/f02-reconciliation.php
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
