# Final acceptance re-audit after F-01 and F-02

Date: 30 October 2026. Project: Sistem Akaun Caj Pemajuan.

## Decision

**Ready for controlled client UAT on the current PHP 7.4.33 environment; not ready for unconditional client acceptance or an automatic production rollout.** Both previously confirmed financial blockers, F-01 and F-02, pass their original reproductions. No new P0 or confirmed P1 implementation defect was identified in the exercised scope. Requirements 3 and 4 remain partial against the literal PDF until the client resolves the policy/operational decisions below.

Updated matrix: **6 implementation PASS, 2 PARTIAL, 0 FAIL**. PASS describes verified behavior, not formal client signoff, bank reconciliation or a general security certification. No client approval has been inferred from an automated test result.

Audit only: no application/test/dependency/schema/runtime source was modified. This report is the only new repository file. Tests, browser scripts, profiles, screenshots and logs were written under `/tmp/cajpemajuan-reaudit`. All financial/account/user/notification mutation requests used disposable databases and receipt/session directories. No PHP upgrade, deployment migration, commit or push occurred.

## Reference and environment evidence

Read the eight-page `Maklum Balas Sistem Caj Pemajuan V1.pdf`, using the complete extracted text and visually rechecking pages 4 and 5 for the embedded installment table and nine mandatory-field annotations. The actual file remains available at `${HOME}/Desktop/Personal/Maklum Balas Sistem Caj Pemajuan V1.pdf`. Its freshly checked SHA-256 matches the original audit:

```text
12e2701cb624848877a6579300a5ff648549ff6ed1ffe885cc14bf17a0f41c6f
```

Reviewed supporting reports: `final-acceptance-audit.md`, both final-fix reports, `stage2b-problem1.md`, `problem4-automatic-installments.md` and `php825-upgrade.md`. Earlier failed values are historical evidence, not freshly reintroduced defects.

| Component | Fresh evidence |
|---|---|
| Explicit CLI | `/Applications/XAMPP/xamppfiles/bin/php`, PHP **7.4.33**, NTS |
| Active XAMPP HTTP headers | Apache **2.4.54**, PHP/**7.4.33** |
| Database | **10.4.27-MariaDB** |
| Application address | `http://localhost/cajpemajuan/` |
| Audit HTTP server | Separate randomized loopback port, actual front controller, PHP 7.4.33 and private schema |
| Real browser | Google Chrome **156.0.8078.76**, separate headless profile, actual DOM/JavaScript/native form submissions |
| JavaScript CLI | Node **v24.21.0** |
| Runtime marker | `.php-version` remains **7.4.33** |

The live URL received an unauthenticated request for response headers only. No authenticated live dashboard request was made: dashboard GETs generate reminder writes. Authenticated browser and HTTP tests were directed only to the isolated listener.

## Updated eight-requirement matrix

| # | PDF requirement | Result | Current evidence and acceptance boundary |
|---|---|---|---|
| 1 | Dashboard: separate paid collections and assessed charges, p1 | **PASS** | Original F-01: RM300,000 assessed / RM100,000 collected / RM20,000 recorded expenditure. Opening/closing explicitly unavailable. Unpaid/cancelled receipts excluded; cash-year and record-year bases preserved; uncertain legacy years labelled. This is not an approved opening/closing account statement. |
| 2 | Ordinary users can create; only admins correct saved financial values, p2 | **PASS** | Both create buttons/forms and initial amounts work; native ordinary-user charge/project submissions succeed. Saved financial edits, deletion, receipt/payment/expense mutations and direct-request bypasses remain denied. Admin edits remain available with history protections. |
| 3 | Jabatan registration/management and responsible PIC notifications, p3 | **PARTIAL** | Canonical department create/edit, NULL legacy support, department-only notification routing, admin access, preserved legacy visibility, bell/history consistency, read authorization, reminders and deduplication pass. Individual PIC assignment, contractor claim-submission semantics and unattended generation are not implemented; client decisions C-02–C-04 remain. |
| 4 | Automatic principal, deadlines and interest table, p4 | **PARTIAL** | Automatic new 3 Ansuran calculation, explicit first deadline, calendar intervals, cent conservation, tamper rejection and F-02 reconciliation pass. Paid/history protections, full payment and manual four-installment behavior pass. Interest remains reference-only and form-issuance timing lacks an event/check: C-01. |
| 5 | Mandatory charge information on create/edit, p5 | **PASS** | All nine annotated fields validated server-side. Each blank edit field returns 422; parent/payment snapshot unchanged. Invalid enums/amounts/dates/row shapes and failed-transaction rollback remain covered. |
| 6 | Historical-year entry/navigation, p6 | **PASS** | Explicit record_year, earlier-year entry/list/report/redirect/retry context and invalid/conflicting year rejection pass. Browser created/printed 1997 data; creation timestamps remain actual audit timestamps. Project reporting still uses start year. |
| 7 | Receipt reference and payment/receipt-date wording, p7 | **PASS** | Required trimmed No. Resit, length/date/upload validation, persistence/display, exact “Tarikh Bayaran / Tarikh Resit” wording, duplicate submission and paid-history protection pass. Legacy NULL references stay readable. Receipt-access/retention policy follow-up P2-01 remains separate. |
| 8 | Project creation beyond three records, p8 | **PASS** | Six distinct valid projects persist through HTTP, including fourth–sixth; ordinary-user native browser creation succeeds. Duplicate-ID handling, optional dates, retained input and atomic schedule-failure rollback pass. No three-project cap exists in reviewed source. The original client's exact failed payload is still unavailable. |

## F-01: original scenario and closure evidence

Historical before evidence is retained in the original audit and `/tmp/cajpemajuan-f01/before-result.json`. Fresh after evidence appears in both the unchanged regression suite and the additional re-audit probe, using disposable financial records.

| Card | Before fixes | Fresh after |
|---|---|---|
| Baki Bawa Hadapan | RM 0.00, unverified | **Belum Disahkan** |
| Jumlah Caj Pemajuan | RM 300,000.00 | **RM 300,000.00** |
| Jumlah Kutipan | RM 100,000.00 | **RM 100,000.00** |
| Jumlah Perbelanjaan | RM 0.00 despite ledger RM20,000 | **RM 20,000.00** |
| Baki Akhir Semasa | RM 300,000.00 from assessments | **Belum Tersedia** |

Actual expense source is `project_expenses.amount`, selected by inclusive January 1–December 31 **payment_date**, SQL DECIMAL sum, no join that could multiply rows. Earlier-year projects contribute current-year actual expenses. Equal-valued legitimate entries each contribute once. Pending obligations/status alone do not establish spending. There is no expense status column; retained actual expense rows count even if project status later becomes completed/cancelled. Existing payment cancellation removes the expense row; no dated reversal policy was added.

Verified absence yields RM0.00. Unassignable legacy expense dates make the amount **Belum Disahkan** rather than silently omitting an unknown year; invalid selected-year amounts do the same. This conservative fallback passed zero/partial/impossible-date fixtures. No client dates/amounts were repaired or guessed.

The closing card explicitly states: “Baki akhir semasa memerlukan baki bawa hadapan yang disahkan, kutipan sebenar dan perbelanjaan yang sah.” No opening zero or closing formula is activated. Project reports retain project-start-year cohorts and lifetime expenses, so they can differ from dashboard calendar-year cash expenditure. Charge cohort reports similarly differ from receipt-year collections.

**F-01: CLOSED for its specified defect and acceptance scenario.** Opening-balance approval remains a limitation, not a fabricated completed financial feature.

## F-02: original scenario and closure evidence

The fresh probe uses actual authenticated, CSRF-protected automatic-create and admin-update endpoints.

| Observation | Historical before fix | Fresh after |
|---|---|---|
| Initial principal | RM150,000.00 | RM150,000.00 |
| Initial installments | RM100,000 / RM25,000 / RM25,000 | Same |
| Attempted parent | RM180,000.00 | RM180,000.00 |
| Submitted installments | Unchanged | Unchanged |
| HTTP | **302 success** | **422 validation error** |
| Saved parent | **RM180,000.00** | **RM150,000.00** |
| Saved schedule | RM150,000 total | Original rows and fields unchanged |
| Unscheduled new principal | RM30,000 | None introduced |

The full regression snapshots parent data, all payment fields, notifications and temporary receipt contents. Failed edits leave them unchanged. A separate paid-receipt fixture confirms immutable paid principal/date/status/reference/file history.

Explicit reconciled correction **succeeds with HTTP 302**: parent RM180,000.00 and installments RM100,000 / RM40,000 / RM40,000. Dates are not silently recalculated. The rule uses integer cents, excludes provisional interest, requires the proper complete/unique installment set and executes inside the parent lock/transaction. Full-payment and manual four-installment plans retain their own row counts and workflows.

Unchanged historical discrepancies remain readable and may survive nonfinancial edits. They are not silently repaired; financial changes must reconcile safely, and paid parent/plan locks remain. There is no stored automatic-versus-legacy formula marker, so the implementation intentionally enforces principal conservation rather than retroactively applying a new formula to every saved manual schedule. Existing edit-plan preview is not a promise of automatic historical recalculation.

Real Chrome native admin submission also displays the Malay reconciliation error, preserves attempted RM180,000 input and shows original pending installment values. The error form's attempted value is not persisted principal.

**F-02: CLOSED for its specified defect and acceptance scenario.**

## Fresh automated and browser results

| Check | Exact result |
|---|---|
| Unmodified full PHP 7.4.33 integration/migration/HTTP suite | **844 PASS, exit 0** |
| Same full suite within temporary acceptance/browser harness | **844 PASS, exit 0** |
| Additional re-audit acceptance probes | **22 PASS, 0 FAIL** |
| Actual Chrome DOM/events/native submissions/print | **23 PASS, 0 FAIL**, zero captured page JavaScript exceptions |
| Standalone database smoke, copied only to inject disposable schema setup | **21 PASS**, rollback then private schema dropped |
| Problem 4 PHP calculator | **40 PASS**, exit 0 |
| Compatibility smoke | **33 PASS**, exit 0 |
| Startup smoke | **6 PASS**, exit 0 |
| Problem 4 JavaScript | **42 assertions PASS** |
| Project-confirmation JavaScript | Fallback, cancellation and repeated-submit checks PASS |
| PHP syntax | **270 files**, zero failures |
| Composer actual platform requirements | All **10** requirements succeed under PHP 7.4.33 |
| Composer validation | Valid; existing empty-namespace PSR-4 performance warning |
| Read-only setup checker | **37 PASS**, exit 0 |
| Completed HTTP PHP diagnostic scans | No PHP warning, notice, deprecation, parse/fatal error or ErrorException |
| Client schema/row and receipt fingerprints | Identical before/after; also identical to original audit |
| Git whitespace validation | `git diff --check` passes |

The full suite includes Stage 1 security, Stage 2A receipts/historical years/migration preservation, Stage 2B dashboard, Problem 2 permissions, Stage 3A departments, Stage 3B notification routing/read authorization/migrations, Problem 4 and both F-01/F-02 modules. These totals describe separate runs and checks; they should not be summed as unique business cases.

Extra probes freshly repeated all nine mandatory edit-field rejections, departmental Kejuruteraan/Perancang Bandar/Landskap notification creation, Landskap visibility of only its new department notification, admin visibility of all three, legacy visibility, unread/history consistency and both direct POST read-route denials with zero unauthorized markers.

PHP tests use `-d error_reporting=-1 -d track_errors=0`. The latter disables PHP 7.4's obsolete track_errors feature for test processes only; application diagnostics remain enabled and server INI is unchanged. Source tests were not edited. The standalone database smoke uses lower-level model fixtures, not approval of bypassing controller validation.

The first temporary browser-script syntax check caught quotation escaping before execution and was corrected only under /tmp. The first browser launch lacked its automation port; that attempt is not counted as browser acceptance. After a separate Chrome launch, the final complete run passed. Chrome's process diagnostics included macOS display-link/allocator messages; there were **no captured page JavaScript exceptions** and no application PHP runtime errors. These browser-process diagnostics are not hidden or described as application failures.

### Real-browser scope

Chrome loaded shipped scripts/styles through the isolated router, then checked:

- Explicit blank required first deadline and nine valid amount cases, including RM100,000, RM150,000, RM180,000, RM200,000, RM200,000.01, RM300,000, fractional rounding and maximum DECIMAL input. Derived amounts/dates are read-only; separate interest matches the reference result.
- January 31 → April 30 → July 30; below-threshold Malay validation; manual four-installment and full-payment alternatives. Automated PHP/HTTP suites additionally cover missing/invalid dates, leap-year and century boundaries and tampering.
- Ordinary-user native charge and project creation using the shipped confirmation handler, successful redirects and selected historical year.
- Historical 1997 report and actual printed PDF; visible charge-create link; locked ordinary-user saved financial field.
- Native admin inconsistent edit and visible Malay error; dashboard cash/expense values and unavailable opening/closing, followed by screenshot inspection.

The later browser dashboard contains **RM450,123.45 assessed**, because the same private year also contains the RM150,000 F-02 fixture and RM123.45 browser-created charge alongside the original RM300,000 scenario. Cash remains RM100,000 and expense RM20,000. This is expected fixture accumulation, not a failed exact-scenario assertion. The original isolated F-01 probe precedes those extra creations and verifies exactly RM300,000. Screenshots were visually inspected; the browser checks are not a substitute for client-led UAT or cross-browser certification.

## Remaining decisions and findings by priority

P0 = critical operational/data-loss defect; P1 = acceptance/release blocker; P2 = follow-up limitation/risk requiring explicit disposition; P3 = low-priority documentation/operational clarity. Policy gates below are clearly distinguished from failed implementation tests.

| Priority / ID | Finding and evidence | Required disposition |
|---|---|---|
| P0 | **None identified** in exercised scope. | No blanket guarantee beyond tested scope. |
| P1 / F-01 | **Closed**, financial presentation/query fixed. | Keep original scenario as regression. |
| P1 / F-02 | **Closed**, inconsistent admin correction rejects atomically. | Keep original scenario and paid-history regressions. |
| P1 approval gate / C-01 | PDF p4 includes applicable interest and installment 1 before planning-permission form issuance. Only provisional interest and an explicit manual first deadline exist. | Written agreement to reference-only behavior and a controlled manual deadline procedure, or separately authorized implementation of complete approved rules. This blocks unconditional literal requirement-4 signoff, not use of the tested principal calculator. |
| P1 approval gate / C-02 | PDF p3 names responsible PIC and contractor-unclaimed payments. Actual implementation uses department membership and unpaid schedules. | Client must accept these meanings or specify individual assignment and claim-submission lifecycle. Requirement 3 remains partial. |
| P2 / C-03 | Reminders generate when a dashboard is requested; no independent background scheduler/escalation service exists. | Agree monitoring responsibility/frequency, or authorize a separate scheduler design. No claim of unattended delivery. |
| P2 / C-04 | Current membership controls notification history; department is captured at notification creation. NULL/unknown project departments are admin-only; legacy/global notices remain compatible. No individual ownership; project/report access is still global for authenticated users. | Confirm departmental handover, unassigned-project oversight and whether project confidentiality is separately required. Notification filtering is not a project-access restriction. |
| P2 / A-01 | Opening/closing balance are unavailable, and retained-ledger cancellation/deletion can restate past expenditure. Cross-report year bases differ. | Financial owner must approve opening balance, scope, reconciliation and retention/reversal policy before treating dashboard as a complete financial statement. No accounting value is invented. |
| P2 / A-02 | Unchanged historical principal discrepancies may survive metadata-only edits; missing/invalid expense dates withhold expenditure. | Evidence-based historical review, outside this audit. No bulk correction/backfill. These safe exceptions are documented, not reported as repaired history. |
| P2 / S-01 | Receipt view links target public storage and the shipped rewrite bypasses existing files; no controller authorization protects a direct existing-file request in that configuration. Cancellation clears receipt metadata/file. | Confirm confidentiality and evidence-retention requirements; authorize download/retention hardening separately if required. No confidential live file was requested. External Apache protections and actual unauthorized receipt retrieval were not established in this audit; this is a source-backed access-policy risk, not a newly demonstrated client-data disclosure. |
| P2 / U-01 | No formal client signoff or the original failed fourth-project payload; no real SMTP delivery or full cross-browser/client workstation certification. | Complete checklist below. Current valid fourth–sixth creation paths pass. |
| P3 / D-01 | Migration README still says Stage 2A files “have not been applied to the client database”; current metadata and prior task context show required additions present. No migration-version table exists. | Update deployment ledger/documentation in a separately authorized documentation task. Do not rerun ALTERs based on stale prose. |

### Exact policy limitations

**Interest and planning permission:** “Anggaran Bunga 8%” equals 8% of principal remaining after installment 1. It is excluded from obligations, receipts, paid totals, outstanding principal and dashboard financial totals. There is no annual, compound, prorated or per-installment charging rule, no planning-permission issuance event/date, and no automated issuance interlock. The explicit deadline is not evidence that payment precedes actual form issuance. The PDF low-band wording repeats 50% of the balance; the approved implementation preserves principal with a residual final installment. Client UAT must acknowledge this interpretation, sequential month-end clamping, RM100,000's two zero rows and the full-payment alternative below the threshold.

**Departments versus individual PICs:** New project notices snapshot the saved project department. Current database membership determines ordinary-user visibility; administrators see all departments and legacy/global records. No per-project designated user or task owner is stored. Department assignment changes do not retarget prior notification rows; membership changes can change which departmental history a user sees.

**Pending payment versus contractor claims:** A schedule has `Menunggu`/`Dibayar` and `claim_date`, with no independent “contractor submitted claim” state. A reminder can identify unpaid scheduling, not reliably distinguish unclaimed from submitted-but-unpaid. The existing column name does not establish a contractor-submission workflow.

**Dashboard reminders versus background work:** Dashboard visits call the reminder generators; unique reminder keys prevent duplicate inserts. No scheduler was found/added, and no recurring escalation policy is inferred. Existing reminder history is preserved after payment/deadline/status changes; history is not an assertion that every reminder remains currently outstanding.

**Balances:** Opening is Belum Disahkan; closing is Belum Tersedia with the Malay prerequisite explanation. Recorded cash/expenses do not certify a bank position, and an approved opening balance has not been supplied. Any future balance formula requires separate review.

## Migrations and deployment consistency

All four paired up/down migrations remain present and unchanged:

1. `20261023_001_receipt_number`: nullable VARCHAR(100) reference.
2. `20261023_002_charge_record_year`: nullable unsigned SMALLINT, record-year index and 1000–9999/NULL check.
3. `20261027_003_user_department`: nullable VARCHAR(150) assignment.
4. `20261028_004_notification_audience`: audience default legacy, nullable department/reminder key, unique reminder key and entity/action lookup index.

Read-only deployed metadata matches those additions. The setup checker confirms exact Stage 2A column/index/check definitions; inspection also confirms notification audience/department/reminder columns, unique notification-read user pair, unique reminder key and unique project business ID. Clean `database/schema.sql` contains the corresponding additions; existing migration regression up/down/up rehearsals preserve original legacy financial/account/notification fields and values. No live migration or backfill was executed. Metadata establishes current schema state, not a historical migration execution log.

Current read-only completeness counts: zero NULL charge record years, zero paid rows missing payment dates and zero paid rows missing receipt references. This is not verification of historical truth. Notifications remain 22 legacy, 4 global, 2 Kejuruteraan and 1 Perancang Bandar; Landskap routing was exercised only with disposable fixtures. No live account membership was changed.

## Client UAT checklist and release gate

The following require named client reviewer, date, result and written disposition; none is pre-checked as client accepted.

- [ ] Finance owner confirms assessed versus received figures using approved examples, expense payment-year basis and differences from cohort reports. Acknowledges unavailable opening/closing and invalid-history fallback.
- [ ] Admin reproduces rejected RM150,000 → RM180,000 unchanged-schedule edit, then explicitly saves RM100,000 / RM40,000 / RM40,000. Confirms no automatic deadline/paid-history changes.
- [ ] Representative ordinary users create charge/project records with initial amounts; saved financial/payment/delete attempts remain denied. Admin correction and historical paid-record protections are accepted.
- [ ] Kejuruteraan, Perancang Bandar and Landskap users verify bell/history/read scope; admin checks all departments, unassigned projects and preserved legacy visibility. Decide whether departmental members constitute PICs and how handover works.
- [ ] Client resolves unclaimed-versus-unpaid meaning and dashboard-triggered monitoring. If independent scheduling/claim/PIC workflows are required, scope them separately before requirement-3 acceptance.
- [ ] Finance/planning officers approve interest behavior, low-band residual interpretation, RM100,000 zero rows, cent rounding, below-threshold full payment and calendar month-end behavior. Decide how actual planning-permission issuance is controlled.
- [ ] Create and edit reject each of the nine page-5 mandatory fields; correction retains user input. Review legitimate historical exceptions without rewriting payments.
- [ ] Enter representative 2024/2025 records and navigate/retry/print them; verify record-year versus cash-year behavior and undated-project discovery.
- [ ] Upload representative permitted receipt, verify No. Resit/date wording/view/print and duplicate protection. Decide confidentiality and cancellation evidence-retention requirements before using confidential receipts broadly.
- [ ] Create fourth/fifth/sixth projects on the intended workstation/browser; retry the original failed payload if available; validate duplicate handling and confirmation dialogs.
- [ ] Deployment owner inventories every modified and untracked file, records already-applied migrations, reviews backup/restore and rollback, and confirms exact staged artifact. Do not deploy only Git-tracked files and omit untracked utilities/migrations/assets/tests.
- [ ] Record requirement-by-requirement client signoff and explicit acceptance/deferment of remaining items. A green suite alone does not authorize rollout.

Controlled UAT can proceed without changing the runtime or inventing business rules. Unconditional acceptance remains withheld until C-01/C-02 are resolved and the client records UAT results. Production deployment needs a reviewed complete artifact, backup/rollback rehearsal, migration-state check and receipt/security-policy disposition; no deployment is executed or authorized by this report.

## PHP upgrade readiness

PHP upgrade remains **deferred**. Current verification is PHP 7.4.33 only. Composer constrains PHP to `>=7.4 <8.3`, with resolution platform override `7.4.33`; actual platform checks passed and no package changed.

`docs/php825-upgrade.md` records prior exact PHP 8.2.5 Linux arm64 Docker verification, six required extensions and 723 then-current integration assertions. That historical run predates the F-01/F-02 suites and does **not** verify the current 844-assertion snapshot under PHP 8.2.5. Before a separately approved upgrade, refresh the isolated source snapshot and rerun the complete current suite with `PHP_VERSION === '8.2.5'`, extension checks and browser acceptance. No fresh 8.2.5 result is claimed here.

The Linux arm64 NTS Docker runtime cannot replace macOS XAMPP's Apache PHP module directly. Existing XAMPP PHP 7.4.33 remains available; no Apache/FPM transition was attempted. Exact historical-version compatibility and a supported production security/maintenance policy are separate release decisions. This report does not recommend a production runtime change.

## Integrity, Git review and evidence artifacts

Task-start hashes cover all existing files under app/public/scripts/tests/database/docker/docs plus Composer files and `.php-version`. Closing comparison confirms **zero changed or missing pre-existing files** and only this report added. An additional comparison with the original audit's stored source hashes found no missing files and exactly the expected eight existing-file modifications from F-01/F-02:

```text
app/controllers/ChargeController.php
app/controllers/DashboardController.php
app/models/Project.php
app/utility/ChargeValidation.php
app/views/charge/edit.php
app/views/dashboard/partials/summary-cards.php
tests/modules-smoke.php
tests/stage2b-dashboard.php
```

The original audit/fix reports and other previously uncommitted files are preserved. `tests/f01-dashboard.php`, `tests/f02-reconciliation.php` and both fix reports remain present. Reviewed reconciliation transaction/legacy guards, exact annual expenditure source, unchanged receipt/notification/permission paths, migration SQL and Git diff/stat. No accidental regression was identified in this review; historical logic outside the exercised scope is not mathematically certified.

Client read-only fingerprints are identical before/after and to the original audit:

```text
schema/rows SHA-256: [private integrity fingerprint omitted]
receipt paths/content SHA-256: [private integrity fingerprint omitted]
```

Both complete test runs' finally blocks confirm unchanged client fingerprints and remove their disposable databases, sessions and receipts. The standalone smoke rolls back and drops its separate schema. Chrome closes through DevTools; its temporary profile/screenshots remain as evidence. No actual client receipt was opened or deleted.

Evidence under `/tmp/cajpemajuan-reaudit`:

- `regressions.log`: fresh unchanged 844-assertion suite.
- `runner.php`, `extra.php`, `acceptance-run-final.log`, `acceptance-results.json`: temporary real-router harness, 844 regressions plus 22 re-audit outcomes.
- `browser.js`, `browser.log`, `browser-results.json`, `browser-dashboard.png`, `browser-edit.png`, `browser-charge.png`, `browser-charge-report.pdf`: actual Chrome evidence. Screenshots contain fixtures only.
- `checks.json`, calculator/compatibility/startup/JavaScript logs, `database-smoke.log`, `setup.log`, Composer logs.
- `live-before.json`, `live-after.json`, `live-indexes.json`, `source-before.json`, `source-integrity.json`, `changes-since-original-audit.json`, `git-before.txt`, `git-after.txt`.
- `acceptance-run.log` and `browser-errors-initial.log`: first browser-launch attempt and a reconstructed diagnostic summary, retained as evidence and excluded from final browser pass counts.

Original PDF text/renderings and historical before evidence remain under `/tmp/cajpemajuan-final-audit`, `/tmp/cajpemajuan-f01` and `/tmp/cajpemajuan-f02`. Temporary artifacts are local working evidence and can be removed by OS cleanup; the exact outcomes and key values are recorded in this report.

### Git status at completion

No commit/push. The following includes extensive work already dirty before this audit; it is not an audit change list. The only audit Git-status addition is `?? docs/final-acceptance-reaudit.md`.

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
?? docs/final-acceptance-reaudit.md
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
