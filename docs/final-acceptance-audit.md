# Final acceptance audit — Sistem Akaun Caj Pemajuan

Audit date: 30 October 2026. **Verdict: NOT READY for unconditional client acceptance.**

This is a verification report, not an implementation. Two confirmed financial integrity/presentation defects remain, along with client decisions needed to close the literal PDF requirements for interest and contractor/PIC notifications. Passing the existing regressions does not establish that these remaining requirements are satisfied.

No application, test, dependency, runtime, Apache configuration or migration source was changed. This report is the only repository artifact added by the audit. All additional test code, browser profiles, extracted PDF pages, screenshots and logs are under `/tmp/cajpemajuan-final-audit`. No client records or receipts were changed. No PHP upgrade, commit or push was performed.

## Source and evidence

The actual eight-page **Maklum Balas Sistem Caj Pemajuan V1.pdf** was located and read. Text was extracted from every page, and **all eight pages were rendered and visually inspected**, including the embedded installment table on page 4 and red mandatory-field annotations on page 5.

Matching copies:

- `${HOME}/Desktop/Personal/Maklum Balas Sistem Caj Pemajuan V1.pdf`
- `${HOME}/Downloads/Maklum Balas Sistem Caj Pemajuan V1.pdf`

Both are 663,695 bytes; SHA-256:

```text
12e2701cb624848877a6579300a5ff648549ff6ed1ffe885cc14bf17a0f41c6f
```

The previous implementation reports were reviewed as supporting context, not substituted for the PDF. Previous Problem 4/compatibility reports correctly said the PDF was absent from the checkout; it is available in the user folders above.

The submitted audit prompt stops after “The client's actual…”. The missing continuation was requested while work proceeded. Requirements 5–8 were recovered directly from PDF pages 5–8: mandatory fields, historical-year entry, receipt reference/date wording, and project creation beyond three records. No additional unpublished acceptance policy is assumed.

## Environment and audit boundary

| Component | Verified value |
|---|---|
| Active XAMPP Apache | 2.4.54 |
| Active HTTP PHP banner | PHP/7.4.33 |
| Explicit test CLI | `/Applications/XAMPP/xamppfiles/bin/php`, 7.4.33 |
| Native MariaDB | 10.4.27-MariaDB |
| Existing deployment | `http://localhost/cajpemajuan`, redirects to trailing slash |
| Audit HTTP application | Real router, separate temporary loopback listener, PHP 7.4.33 |
| Browser | Separate headless Google Chrome profile, actual JavaScript/DOM and native form submissions |

The live deployment received only a read-only HEAD request for server headers. Authenticated audit GET/POST requests, dashboard notification generation, uploads, user creation and deliberate invalid edits were directed only to disposable test databases. The dashboard was not opened against client data because its GET can create reminders.

Existing PHP 8.2.5 provisioning was neither upgraded nor switched during this audit. Its earlier verification remains documented in `docs/php825-upgrade.md`; it is not presented as a fresh result of this audit.

## Acceptance matrix

PASS means the tested requirement is met within the documented scope. PARTIAL means significant behavior passes but the complete PDF requirement or an important lifecycle boundary remains unresolved; it is **not acceptance**. FAIL identifies a directly failed supplied acceptance criterion.

| # | Requirement and PDF page | Result | Decision |
|---|---|---|---|
| 1 | Financial dashboard, p1 | **FAIL** | Correct paid-only collections, separate assessments and cash-year filtering; unverified opening/expenditure/closing values remain presented as actual financial figures. F-01 blocks acceptance. |
| 2 | Ordinary-user creation/admin financial control, p2 | **PASS** | Both create paths/buttons and initial amounts work; ordinary saved-data/payment mutations and direct bypass attempts are denied; admin controls remain. |
| 3 | Departments and responsible PIC notifications, p3 | **PARTIAL** | Department registration and scoped notification behavior pass. “Unclaimed by contractor” is represented only by unpaid schedules, and designated PIC/operational generation semantics need agreement. C-02. |
| 4 | Automatic installment table, interest and deadlines, p4 | **PARTIAL** | New-record principal/date calculations and tamper rejection pass. A later unpaid admin correction can break principal reconciliation (F-02). Actual interest/form-issuance timing acceptance also requires C-01. |
| 5 | Complete mandatory charge data, p5 | **PASS** | All nine annotated main fields are mandatory server-side; invalid edits do not persist. Applicable automatic/manual schedule validation and input restoration pass. |
| 6 | Entry by historical year, p6 | **PASS** | Explicit record year, historical entry, navigation, retry/redirect and report context pass; creation timestamps remain audit timestamps. |
| 7 | Receipt reference and date label, p7 | **PASS** | Required trimmed receipt number and exact requested date wording persist/display; invalid/missing and repeated payment submissions are handled. |
| 8 | Project creation beyond three records, p8 | **PASS** | Six distinct projects persist through HTTP; actual ordinary-user browser creation also succeeds. No three-record application limit found. |

**Summary: 5 PASS, 2 PARTIAL, 1 FAIL.** There are two confirmed acceptance-blocking defects, not merely missing test coverage. The original customer's exact failed fourth-project payload was unavailable, so the historical incident itself is not claimed to have been reproduced verbatim.

## Confirmed defects

### F-01 — Unverified balances/expenditure shown as actual figures — P1, acceptance blocker

**Evidence:** `app/controllers/DashboardController.php:82`, `:84`, `:86`; `app/views/dashboard/partials/summary-cards.php:4`, `:38`, `:48`. Probe `R1-unverified-balance-labels` failed.

The controller sets opening balance and expenditure to zero. Closing balance is opening balance + **assessed charges** − zero expenditure. The interface calls these values “Baki daripada tempoh sebelumnya”, “Jumlah perbelanjaan projek” and “Kedudukan kewangan semasa”, without a provisional/unverified qualification.

Disposable reproduction:

1. Create RM300,000 assessed principal, with RM100,000 received and RM200,000 unpaid.
2. Record RM20,000 of actual project expense in the same test year.
3. Request the dashboard for that year.

Observed cards:

| Card | Displayed |
|---|---:|
| Baki Bawa Hadapan | RM0.00 |
| Jumlah Caj Pemajuan | RM300,000.00 |
| Jumlah Kutipan | RM100,000.00 |
| Jumlah Perbelanjaan | **RM0.00 despite RM20,000 expense** |
| Baki Akhir Semasa | **RM300,000.00** |

The assessed/received distinction passes. The three unverified financial figures fail the supplied final-audit criterion. The existing Stage 2B suite explicitly asserts the preserved placeholder behavior, so its green result does not refute this defect.

**Impact:** Users can read an assessment-based number as a verified cash/balance position and read a placeholder zero as absence of spending.

**Required before acceptance:** Obtain an approved opening/expense/balance basis and either implement it or clearly withhold/qualify the unverified values. This audit does not invent a correct closing balance or approve a financial formula. No fix was made.

### F-02 — Unpaid charge correction can leave unreconciled installment principal — P1, acceptance blocker

**Evidence:** `app/utility/ChargeValidation.php:64`, `:111`, `:136`; `app/controllers/ChargeController.php:84`. Probe `R4-post-save-principal-reconciliation` failed.

The authoritative calculator runs for new 3 Ansuran creation. For an existing unchanged plan, pending rows are checked for shape, ownership, positive amounts and dates, but their total is not reconciled to the parent charge.

Disposable reproduction through genuine authenticated, CSRF-protected requests:

1. Create a new, unpaid RM150,000 3 Ansuran charge using the automatic creation path.
2. Verify saved principal RM100,000 + RM25,000 + RM25,000.
3. As admin, edit Jumlah Caj to RM180,000 and submit the same three pending amounts/dates.
4. Server returns **HTTP 302 success**.

Observed saved state:

```text
Parent charge:   RM180,000.00
Installments:   RM100,000.00 + RM25,000.00 + RM25,000.00
Schedule total: RM150,000.00
Unreconciled:   RM30,000.00
```

**Impact:** Completing every scheduled installment can still leave the charge apparently underpaid by RM30,000, with no corresponding scheduled principal obligation. This affects a newly created automatic record after a correction, not only imported history.

This is **not an ordinary-user authorization bypass**: the mutation is an authorized admin edit. Paid-row/principal protections and ordinary-user denials pass. The earlier Problem 4 report explicitly limits automatic calculation to new creation and preserves manual existing edits; this finding exposes the acceptance gap at that lifecycle boundary rather than claiming that scope was silently expanded.

**Required before acceptance:** Agree a correction rule that atomically reconciles unpaid parent principal and its schedule, or rejects an inconsistent correction, while preserving paid and genuinely historical rows. Add this exact scenario to future regressions. Do not automatically rewrite all legacy schedules. No fix or schema proposal was implemented by the audit.

## PDF decisions that still require client acceptance

### C-01 — Final interest policy and first-installment timing

PDF page 4 explicitly refers to interest for applicable installments and states 8% of the balance after installment 1. The current interface deliberately displays **“Anggaran Bunga 8%”** as a reference estimate and excludes it from payment obligations, collections and financial totals. This correctly follows the later implementation instructions and avoids imposing unapproved interest.

It is therefore **not proof that the PDF's complete chargeable-interest requirement has been accepted**. Obtain written acceptance of reference-only behavior or a complete approved interest rule. Do not infer annual, compound, prorated or per-installment allocations from the PDF alone.

The table also describes installment 1 as due before the planning-permission form is issued. The implementation requires an explicit initial deadline but has no form-issuance event/date against which to verify that condition. Confirm the manual deadline procedure satisfies that business control.

The low-band table repeats “50% of remaining balance” for rows 2 and 3. The later instructions explicitly resolve principal conservation with a residual final row; the implementation follows those instructions. Half-up cent rounding, sequential month-end clamping, the below-RM100,000 full-payment alternative and the two zero rows at exactly RM100,000 should be included in acceptance signoff.

### C-02 — PIC/unclaimed-payment meaning and operating conditions

Department routing itself passes. Notifications go to current members of the saved project department and all admins. This is a departmental audience, not a stored designated individual PIC. A user's current database membership controls history access; notification department is a creation-time snapshot.

The database has pending/paid schedule state (`Menunggu`/`Dibayar`) and `claim_date`, but no separate contractor claim-submission state. Thus the code cannot distinguish “not yet claimed by contractor” from “claimed but not yet paid”. Pending reminders are tested and work; equivalence to the PDF wording requires agreement.

Generation occurs on dashboard requests, not an independently running scheduler. Reminder uniqueness prevents repeated dashboard inserts; it does not create a separate recurring escalation policy. Existing reminders remain history after deadlines/status/payment changes. These are documented conditions, not failed department-authorization tests.

Confirm that departmental members act as PICs, that pending-payment semantics are acceptable, and that dashboard-triggered generation fits the intended monitoring process. No individual assignment, claim workflow, scheduler or unrelated project permission restriction was added during the audit.

## Detailed requirement checks

### 1. Financial dashboard

- **PASS:** Collections SQL selects only `Dibayar` rows with payment dates inside the selected calendar year; unpaid rows and NULL payment dates do not contribute. Cancellation resets paid state, excluding that row. Evidence: `app/models/DevelopmentCharge.php:48` and the executed `tests/stage2b-dashboard.php` fixtures.
- **PASS:** Earlier charge-cohort receipts are counted by receipt year, with January 1/December 31 boundaries tested. Repeated requests do not double-count.
- **PASS:** All assessed charges are separately labelled “Jumlah Caj Pemajuan”, including unpaid charges; the UI explicitly describes this basis. This is the approved assessed-total interpretation, rather than an outstanding-only card.
- **PASS:** Uncertain legacy `record_year=NULL` records use a labelled provisional `created_at` fallback; fixtures verify the warning. The current client database has zero NULL charge years; their actual historical correctness was not independently certified.
- **FAIL:** Opening/expenditure/closing placeholder presentation, F-01. No verified balance/ledger reconciliation is claimed.

### 2. Ordinary-user permissions

- **PASS:** Create buttons and GET forms appear for both roles; ordinary users can save charges/projects with initial amounts and year context.
- **PASS:** Actual Chrome native form submissions, including the shipped confirmation handlers, succeeded for both modules using an ordinary fixture account.
- **PASS:** Saved financial inputs are read-only/disabled for ordinary users; direct authenticated requests to update/delete/record/cancel/payment-schedule/expense/variation/user-management actions are denied without database changes.
- **PASS:** Admin create/update/receipt/expense workflows remain; settled principal/plan changes retain the deliberate paid-history restrictions.
- **PASS:** CSRF is enforced server-side, current role is refreshed from the database, revoked/deleted sessions are rejected, and internal controller methods are not routable.
- Evidence: `app/controllers/ChargeController.php:5`, `app/controllers/ProjectController.php:6`, `app/controllers/ModuleController.php:5`; executed Stage 1 and Problem 2 tests.

### 3. Departments and notifications

- **PASS:** User create/edit/list forms include Jabatan. New ordinary accounts require a canonical department; existing NULL membership remains readable/editable. Admin accounts retain the permitted NULL/global-oversight convention.
- **PASS:** User and project forms share the four `Departments::options()` values; invalid values are rejected without account insertion.
- **PASS:** New Kejuruteraan, Perancang Bandar and Landskap notifications were created in the disposable database. A Landskap user saw **only Landskap** of those three; admin saw **all three**. Both retained the inserted legacy notification.
- **PASS:** Unread counts and history use the same visibility predicate. Existing tests additionally compare recent lists and rendered HTTP history.
- **PASS:** Both `/notify/read/ID` and `/notification/read/ID` rejected a Landskap user's direct POST for an engineering notification even with valid CSRF and forged user/department fields: HTTP 302 to history, **zero read markers**.
- **PASS:** Overdue active projects and overdue/today/soon pending schedules produce reminders; paid schedules, completed/cancelled projects and invalid/absent overdue dates are excluded as specified. Unique reminder keys and repeated-dashboard tests prevent duplicates. Missing/unknown project departments are admin-only.
- **PARTIAL PDF acceptance:** C-02. Notification scope is not a project/report confidentiality boundary: existing authenticated project detail/report access remains global by the accepted implementation scope. Do not claim otherwise.
- Evidence: `app/models/Notification.php:6`, `:30`, `:68`, `:79`, `:99`, `:395`; `app/models/Project.php:664`, `:675`; executed Stage 3A/3B tests and added audit routing probes.

### 4. Automatic installments

Both PHP tests and real Chrome confirm:

| Charge (RM) | Principal 1 | Principal 2 | Principal 3 | Reference interest |
|---|---:|---:|---:|---:|
| 99,999.99 | Rejected for 3 Ansuran; full payment remains available | — | — | — |
| 100,000.00 | 100,000.00 | 0.00 | 0.00 | 0.00 |
| 150,000.00 | 100,000.00 | 25,000.00 | 25,000.00 | 4,000.00 |
| 180,000.00 | 100,000.00 | 40,000.00 | 40,000.00 | 6,400.00 |
| 200,000.00 | 100,000.00 | 50,000.00 | 50,000.00 | 8,000.00 |
| 200,000.01 | 100,000.01 | 50,000.00 | 50,000.00 | 8,000.00 |
| 300,000.00 | 150,000.00 | 75,000.00 | 75,000.00 | 12,000.00 |
| 300,000.03 | 150,000.02 | 75,000.01 | 75,000.00 | 12,000.00 |

- **PASS new creation:** Integer-cent PHP calculation, exact principal conservation, final residual cents, maximum DECIMAL amount and fractional-cent percentages. Three-decimal input is rejected rather than silently rounded.
- **PASS new creation:** Explicit required first deadline, no implicit current/creation/OSC date; generated three-month intervals and read-only derived inputs update on changes.
- **PASS boundaries:** 2024-01-31 → 2024-04-30 → 2024-07-30; 2023-08-31 → 2023-11-30 → 2024-02-29; nonleap/century boundaries and invalid/out-of-range dates tested.
- **PASS approved scope:** Provisional interest is separately labelled, derived from the balance after rounded installment 1, and excluded from persisted principal, cash and assessment totals. Browser-supplied interest does not create a financial obligation.
- **PASS security/preservation:** Missing/invalid dates, manipulated amounts/dates/row counts/numbers/nested inputs reject with HTTP 422 before writes. Paid principal/row/receipt protections pass. Historical manual schedules and NULL deadlines are not automatically rewritten. Full payment and manual legacy four-installment behavior remain.
- **KNOWN BOUNDARY/DEFECT:** F-02. Existing same-plan pending edits and unpaid plan changes still use manual behavior; the old edit-plan JavaScript can divide into equal thirds. New-create guarantees must not be advertised as an all-record/all-edit guarantee. Evidence: `app/utility/ChargeValidation.php:64`, `:111`; `public/js/dashboard.js:816`.
- **PARTIAL PDF acceptance:** C-01. Initial deadline entry alone is not proof of form-issuance compliance, and provisional interest is not a finalized chargeable-interest implementation.

### 5. Mandatory fields and data integrity

- **PASS:** The nine page-5 fields are checked server-side: Nama Pemaju, Nama Projek, ID OSC, No. Lot/PT, Mukim, No. Fail, Jumlah Caj, Keputusan OSC, Pelan Bayaran.
- **PASS:** Missing/whitespace values, bad enums, negative/non-numeric/overflow/extra-decimal amounts and malformed schedule arrays reject. The create regressions cover each required field; the added audit individually blanked **all nine edit fields**, receiving HTTP 422 for each.
- **PASS:** Parent and all payment rows were compared after the rejected edit batch and were unchanged. Old input/error rendering, schedule ownership/paid state and transaction rollback are covered.
- **EXCEPTION DOCUMENTED:** Full payment and historical NULL deadlines retain the prior convention; page-5 red annotations identify the nine main fields, not a demand to fabricate missing historical installment dates. Automatic 3 Ansuran does require the explicit first deadline.
- Evidence: `app/utility/ChargeValidation.php:27`, `app/views/charge/create.php`, `app/views/charge/edit.php`; executed Stage 1 and audit edit probes.

### 6. Historical years

- **PASS:** New 2024 records persist `record_year=2024` while `created_at` remains the actual creation timestamp. They appear in the 2024 list/report and not the creation-year cohort.
- **PASS:** Sidebar/back/create/edit/payment links, form actions/hidden year, validation retries, success redirects, session fallback and separate-tab explicit-year requests retain context. Bad/conflicting years reject with HTTP 400.
- **PASS:** Uncertain legacy years are not inferred from file text; explicit provisional warnings are rendered where applicable.
- **CLARIFICATION:** Dashboard collections use cash year; charge list/print reports use charge record year with paid amounts for that charge cohort. Those are intentionally different views, not interchangeable annual cash statements. Projects retain start-year reporting, with undated projects separately discoverable.
- Evidence: `app/utility/ReportingYear.php:7`, `app/models/DevelopmentCharge.php:48`, `:94`; executed Stage 2A/2B tests and actual 1997 print-to-PDF.

### 7. Receipts

- **PASS:** Required No. Resit, trimming, maximum 100 characters, persistence with payment date and visible saved reference. The label is **“Tarikh Bayaran / Tarikh Resit”**.
- **PASS:** Missing/invalid reference/date/file and repeated posting reject; a paid row cannot have its receipt metadata overwritten by duplicate submission. Legacy NULL references remain readable with a historical label.
- **PASS:** Receipt upload/view-link/cancellation regressions, 5 MB/extension/MIME controls, year context and ordinary-user payment denials pass. Test files were confined to disposable receipt directories.
- **BOUNDARY:** Actual live receipt content retrieval was not performed: there are zero native receipt files excluding the placeholder. Existing tests verify upload storage and link rendering, not an authenticated download service.
- **Supporting follow-up:** `Lihat Resit` links directly to public storage (`app/views/charge/payment.php:205`), and existing files bypass the front-controller rewrite. A URL holder would not pass through controller authorization; no confidential live file was fetched to demonstrate this. Cancellation deliberately removes receipt metadata/file under current behavior. Confirm download and evidence-retention policy separately; neither global receipt-number uniqueness nor immutable reversal history was specified in the PDF. These are not silently claimed as implemented.

### 8. Project creation

- **PASS:** Six distinct project records, including the fourth/fifth/sixth, save through real HTTP requests. No source/SQL three-record count check or list limit was found.
- **PASS:** A real ordinary-user browser submission using the shipped form/confirmation handler also saved and redirected to project finance with the selected year.
- **PASS:** Duplicate IDs return a useful error without overwriting the original and retain form/schedule/year input. Empty optional dates become NULL; undated projects remain discoverable. A forced schedule-insertion failure rolls back the project rather than leaving a half-save.
- **PASS:** Existing JavaScript checks cover native confirmation fallback/cancellation/repeated submit when the confirmation library is absent.
- **LIMITATION:** The original failed fourth payload/browser log was not available. The verified conclusion is that the current implementation has no three-project cap and passes the stated valid-creation scenarios, not that every possible original failure cause has been identified.

## Fresh test results

| Test | Runtime | Exact result |
|---|---|---|
| Existing full integration/migration/HTTP suites | PHP 7.4.33 | **723 PASS, exit 0**, repeated within the audit harness with the same 723 passing assertions |
| Added disposable acceptance probes | PHP 7.4.33 | **22 checks: 20 PASS, 2 FAIL** (F-01/F-02), recorded without aborting cleanup |
| Real Chrome DOM, native form submissions and print | Existing Chrome + PHP 7.4.33 server | **19 PASS, 0 FAIL**, zero captured page JavaScript exceptions |
| Problem 4 PHP calculator | PHP 7.4.33 | **40 PASS**, exit 0 |
| Compatibility smoke | PHP 7.4.33 | **33 PASS**, exit 0 |
| Startup smoke | PHP 7.4.33 | **6 PASS**, exit 0 |
| PHP syntax | PHP 7.4.33 | **268 source/vendor/helper files**, zero failures |
| Problem 4 JavaScript | Node v24.21.0 | **42 assertions PASS** |
| Project confirmation JavaScript | Node v24.21.0 | PASS |
| Composer actual platform check | PHP 7.4.33 | All **10** requirements succeed |
| Composer validation | PHP 7.4.33 | Valid; existing empty-namespace PSR-4 performance warning only |
| PHP HTTP warnings/notices/deprecations/fatal errors | PHP 7.4.33 | None in completed regression/audit HTTP checks |

PHP tests used `-d error_reporting=-1 -d track_errors=0`. The latter disables the existing obsolete PHP 7.4 INI feature for the test process; it does not mask application warnings or edit server settings. The failed acceptance checks are business/financial assertions, not hidden runtime errors. No package install/update or PHP 8.2 run was performed.

## Evidence artifacts and reproduction

Temporary artifacts are under `/tmp/cajpemajuan-final-audit`:

- `pdf-text.txt`, `page-1.png` … `page-8.png`: actual PDF inspection.
- `regressions.log`: unmodified suite's fresh 723 passing assertions.
- `runner.php`, `extra.php`, `acceptance-run.log`, `acceptance-results.json`: disposable acceptance probes and all outcomes.
- `browser.js`, `browser.log`, `browser-results.json`: actual Chrome automation and 19 outcomes.
- `browser-dashboard.png`: rendered financial cards with the placeholder problem visible.
- `browser-charge.png`: actual charge-create form; browser DOM results record all derived values/read-only attributes even where the screenshot viewport truncates the form.
- `browser-charge-report.pdf`, `printed-report-text.txt`: a real one-page printed 1997 charge report with audit-only records.
- `calculator.log`, `compatibility.log`, `startup.log`, `javascript.log`, `lint.json`, `composer-platform.log`, `composer-validate.log`.
- `live-before.json`, `live-after.json`, `source-before.json`, `source-integrity.json`, `xampp-headers.txt`, `git-before.txt`.

The audit runner is a temporary copy of the existing harness with absolute include paths, extra fixture probes and a static-asset return path for real browser JavaScript/CSS. Application source and existing tests were not edited. All model/controller writes use the existing random `cajpemajuan_modules_<hex>_test` schema and temporary session/receipt directories. Both fixture PDO and schema-name guards are checked. The original harness's finally cleanup drops the disposable DB and checks live fingerprints; Chrome's audit-only process was closed through its DevTools API.

For another acceptance run, reproduce F-01/F-02 exactly in a disposable installation. Do not create those inconsistent records in the client database. Run existing regressions with the explicit PHP 7.4 executable; start a fresh separate headless profile only if rerunning the browser harness, because this run closed it afterward.

## Client-data observations and integrity

Private operational distributions and account/record completeness observations are omitted from this publication copy. The original audit was retained privately. Automated fixture evidence and unchanged-before/after verification remain described elsewhere.

## Acceptance exit criteria

1. Resolve F-01 without presenting an invented accounting balance as verified.
2. Resolve F-02 with a safe approved unpaid-correction invariant and a regression for the exact RM150,000 → RM180,000 case. Preserve paid and historical obligations.
3. Obtain written C-01 interest/timing and C-02 PIC/unclaimed/reminder acceptance decisions. Reference-only interest must not be described as implemented chargeable interest without that agreement.
4. Re-run the affected financial/browser checks and full regressions after any separately authorized implementation.
5. Complete client UAT with representative departmental users, real approved financial examples and the intended receipt/printing workflow. Do not use a green automated test total as a blanket financial or security certification.

**No fixes were attempted during this audit.** The system is ready for review of these findings, not unconditional client signoff.

## Closing integrity verification

The final read-only metadata check also confirmed these deployed controls:

- Unique project business ID index: `projects.project_id`.
- Unique read marker: `notification_reads(notification_id,user_id)`.
- Unique project reminder key: `notifications(reminder_key)` via `unique_project_reminder`.
- Notification entity/action lookup index and charge `record_year` index.
- `chk_charge_record_year`: NULL or 1000–9999.

`cmp live-before.json live-after.json` exited **0** after both disposable runs and the browser audit. Identical native client fingerprints:

```text
PHP: 7.4.33
MariaDB: 10.4.27-MariaDB
Client schema/rows SHA-256:
[private integrity fingerprint omitted]
Receipt paths/content SHA-256:
[private integrity fingerprint omitted]
```

`source-integrity.json` compares the pre-audit hashes of all existing files under app/public/scripts/tests/database/docker/docs plus Composer files and `.php-version`. Result:

```json
{
  "changed_or_removed_preexisting_files": [],
  "new_files": ["docs/final-acceptance-audit.md"]
}
```

The pre-existing modified/untracked working tree was preserved. Comparing the initial and final Git status adds only:

```text
?? docs/final-acceptance-audit.md
```

No existing tests or implementation reports were rewritten. `git diff --check` passes. Both disposable databases were dropped by the harness, temporary test sessions/receipt files were cleaned up, and the separate headless Chrome process exited. The active XAMPP PHP runtime remains 7.4.33. No commit or push was made.
