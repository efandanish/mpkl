# Stage 2A — Receipt numbers, historical years and project creation

Date: 23 October 2026. Branch: `fix/security-data-integrity-v1`. Runtime: XAMPP PHP 7.4.33 / MariaDB. References: `docs/client-requirements-audit-v1.md`, `docs/stage1-security-data-integrity.md` and the client feedback requirements.

## Outcome and deployment dependency

Phase 2A is implemented and tested in a disposable database. New charge payments require No. Resit; new historical charges store an explicit reporting year; navigation and forms preserve selected years; project creation handles duplicate IDs and insertion failures with recoverable Malay errors.

**No migration has been applied to the client database. The new code requires the two migrations before it is enabled for client use.** Pause writes and deploy SQL/code together in a reviewed maintenance window. `scripts/check-setup.php` checks the new columns but does not migrate them. The current live schema remains the Stage 1 schema until the operator explicitly applies the migrations.

All existing Stage 1 uncommitted changes were preserved. No reset, checkout-overwrite, staging, commit or push was performed. No Composer dependency changed. No interest calculation, automatic installment amount, opening balance or final dashboard financial formula was implemented.

## Inspection and decisions

Before editing, Git status and branch, the Stage 1 handover, actual charge/project/report/payment code, SQL schema, year navigation, JavaScript and the isolated test runner were inspected. A read-only live schema inspection confirmed that neither receipt_number nor record_year existed. Stage 1 already normalized empty project dates to NULL and provided transaction/paid-schedule guards; these were retained.

The existing year mechanisms were inconsistent: charge lists/reports used YEAR(created_at), projects used YEAR(start_date), and controllers/navigation frequently fell back to hardcoded 2026. Payment dates were already stored separately. The project create path had no three-record limit; duplicate IDs could throw and validation errors did not restore the create form. JavaScript prevented the ordinary form POST before calling a potentially unavailable SweetAlert CDN.

Chosen compatibility rules:

- New HTTP charge creation assigns record_year from the validated selected year and leaves created_at to the database's real creation timestamp.
- Ordinary charge edit/payment operations do not reassign an existing record_year, including legacy NULLs. The navigation year is not an instruction to move an existing record between reporting years.
- NULL record_year marks an uncertain legacy assignment. Such rows remain discoverable through the previous creation-year grouping, clearly labelled provisional. No ID/file-number/date guessing or automatic backfill occurs.
- Project annual lists/reports retain start-year semantics. Undated projects appear in a separate “Projek Belum Bertarikh” section and are excluded from annual project reports. No project date/year is invented.
- Receipt references are not assumed unique. A nullable field supports old payments; newly posted payments require a number.

## Receipt-number behaviour

The standalone charge payment page now contains required **No. Resit** and the requested **Tarikh Bayaran / Tarikh Resit** label. The controller rejects absent, array-shaped, empty, whitespace-only or over-100-character receipt references before any file is moved or payment is posted. Valid input is trimmed and saved with the payment date/file in the existing transaction.

Paid installments display the escaped saved reference. Legacy NULL/empty references display “Belum direkodkan (rekod lama)”. Failure responses retain the year, receipt reference and date when usable; browsers require the receipt file to be selected again. The original 5 MB limit, extension/MIME checks, randomized filenames, authenticated controller access and paid-state recheck remain intact. Duplicate payment POSTs cannot replace a paid receipt or reference.

No receipt-number API or unauthenticated reference endpoint was added. Existing logged-in payment-page viewing permissions are unchanged, including ordinary-user read access. Numbers are not included in notification messages. Public receipt-file access/retention remains the pre-existing design and is not represented as solved here.

Cancellation clears receipt_number alongside the existing payment date/file metadata, consistent with the current cancellation flow. It does not add receipt correction, immutable reversal history or a new retention policy. There is no uniqueness index; duplicate-reference policy still needs client signoff.

Main implementation: `app/controllers/ChargeController.php`, `app/models/DevelopmentCharge.php`, `app/views/charge/payment.php`.

## Year handling

`app/utility/ReportingYear.php` centralizes year validation/context. Supplied years must be four digits in the technical range 1000–9999. Array-shaped, truncated/nonnumeric or conflicting query/form years return HTTP 400 before state changes. The range is storage/format validation, not a new client business-year policy.

The precedence is explicit matching query/POST year, then the authenticated session's valid selection, then the actual current calendar year. There is no hardcoded 2026 fallback. The year selector retains the relevant 2024/2025/2026 choices, current nearby years and any explicitly selected valid year.

Links, POST actions, hidden year fields, redirects, notification targets and JavaScript-generated project finance forms carry the year. Explicit form values protect a 2024 form when another tab changes the session selection to 2025. Conflicting query/hidden values require a form reload rather than silently choosing one. The topbar selector stays in the charge/project module on create/edit/payment/finance pages. Report printing uses the current validated selection.

Charge list, charge report and existing charge dashboard cohort queries now use `COALESCE(record_year, YEAR(created_at))`. Only the year grouping changed: the existing assessed-charge aggregate, zero expenditure/opening placeholders and dashboard layout remain otherwise unchanged. This is **not** the pending correction to actual-cash dashboard totals.

The charge creation page states its assigned year; detail/payment pages show the actual stored record year or an unconfirmed legacy notice. The list/report label provisional legacy rows and explain the fallback. created_at and payment_date are neither backfilled nor replaced.

| Concept | Current Phase 2A meaning |
| --- | --- |
| Charge record_year | Explicit selected year for new charge registrations |
| Charge created_at | Actual entry/audit timestamp; provisional legacy grouping only when year is NULL |
| Payment payment_date | Actual supplied receipt/payment date; preserved for future cash-year reporting |
| Project start_date year | Existing annual project list/report grouping |
| Navigation year | Context carried between pages; does not alter an existing record's year/date |

## Project creation reliability

The application still has no project-count cap. The suite created six distinct projects through the real HTTP controller, including one with blank optional start/end dates.

The create controller now prechecks the unique business ID and catches the database duplicate-key result as well, covering a duplicate that appears between precheck and insert. It never overwrites an existing project. A duplicate displays “ID Projek telah digunakan” and asks for another ID. Other insertion failures return a safe Malay retry/admin message without raw SQL or credentials. Expected database failure details go to the server error log as SQLSTATE only.

Validation/insertion failures re-render the standalone create form with escaped main fields, select choices, description, manual schedule values and selected-year context where practical. Structurally malformed arrays may require re-entry; invalid browser date/number values may not be displayable by their controls.

Parent insert, schedules and creation notification remain in the Stage 1 transaction. A forced schedule failure in the test database verified that the parent insert rolls back and a recoverable form is shown. Blank optional dates bind NULL. Success redirects to the saved finance/detail page with selected-year context, so a saved project remains immediately visible even if undated or its start year differs from navigation context. Dated projects appear in their actual start-year list/report; undated ones remain discoverable separately.

The project confirmation handler falls back to native confirmation plus the normal POST if SweetAlert is unavailable. A second submission while the confirmation dialog is open does not launch another dialog. JavaScript-disabled submission remains an ordinary server-validated form. This does not impose a count limit or automatically generate/overwrite business IDs.

The client's original fourth-project payload was unavailable, so there is no claim that one unique historical root cause was identified. The concrete duplicate/error-recovery and blank-date paths are covered, and six-record HTTP creation succeeds.

## Database changes and preservation plan

Versioned SQL files:

1. `database/migrations/20261023_001_receipt_number.up.sql` — nullable VARCHAR(100) receipt reference, no uniqueness constraint.
2. `database/migrations/20261023_001_receipt_number.down.sql` — removes only that new column, with export warning.
3. `database/migrations/20261023_002_charge_record_year.up.sql` — nullable indexed SMALLINT year plus range check; no UPDATE/backfill.
4. `database/migrations/20261023_002_charge_record_year.down.sql` — removes only the new year column/index/check, with export warning.

Full pre-inspection SQL, password-prompt backup commands, receipt archive commands, manual deployment instructions, reviewed backfill steps and rollback limitations are in **`database/migrations/README.md`**. `database/schema.sql` reflects the same resulting structure for empty installations; it must not be imported over existing client data. No financial table is dropped or recreated by the versioned migrations.

For legacy year review, export charge IDs, existing timestamps/identifiers and proposed years into a separate approval register. Obtain documentary evidence and client approval for each mapping. Apply only separately reviewed ID-specific updates later, with backup, expected-row checks and an audit of old/new values. Leave unresolved records NULL. No executable backfill UPDATE was introduced in this phase.

Rollback should preferably restore the Stage 1 code artifact while retaining the harmless additive nullable columns. If down migrations are necessary, export new reference/year values first and run them in reverse order with writes paused. Column removal loses those values and changes how new historical charges group in the old application. DDL implicitly commits; down scripts are not a transaction rollback or a reversal of later client activity. Never restore an old dump over subsequent transactions casually.

## Stage 1 safeguards retained

The combined suite verified CSRF rejection and authorized tokens; current-user role refresh and deleted-user denial; mandatory/positive charge validation; paid charge/project schedule protections; required receipt file; upload/view-link paths; transaction rollback; admin-only global notification clear on both routes; normal-user create/update denial; admin CRUD/finance; notification redirects; reports; login/logout; and dashboard/MPKL structure. Session/token algorithms and the existing charge validator were not replaced.

No new normal-user creation permission was granted. Notification personal dismissal/department scoping and approved financial-retention policies remain future work. Stage 1 filesystem/DB cancellation limitations remain.

## Validation and test results

| Command/check | Result |
| --- | --- |
| PHP 7.4 lint over app/public/scripts/tests | 131 files; zero failures |
| `node --check public/js/dashboard.js` | Passed |
| `node --check public/js/project-finance.js` | Passed |
| `node tests/stage2a-project-confirmation.js` | Confirmation fallback, cancellation and repeated-submit checks passed |
| `/Applications/XAMPP/xamppfiles/bin/php tests/compatibility-smoke.php` | 21 assertions passed |
| `/Applications/XAMPP/xamppfiles/bin/php tests/startup-smoke.php` | 6 assertions passed; fixtures cleaned |
| `/Applications/XAMPP/xamppfiles/bin/php tests/modules-smoke.php` | 458 assertions passed, exit 0; includes Stage 1 and Phase 2A |
| `git diff --check` | Passed |
| Client table row/definition fingerprints | Unchanged |
| Client receipt path/content fingerprints | Unchanged |

The runner uses a generated disposable database, ephemeral localhost HTTP server, temporary sessions and temporary receipt storage. Migration checks first exercise down/up/up against legacy fixtures there, verifying original rows/timestamps remain intact and new values are NULL. Every forced error, trigger, payment, cancellation and delete is confined to that database/storage. Cleanup drops the generated database and removes temporary files. Tests never issue a live client ALTER or financial write.

Phase 2A coverage includes valid/trimmed, missing, whitespace, array and overlong receipt numbers; required file; readable legacy reference; duplicate payment protection; 2024 registration with current creation timestamp; year-preserving successful/failed edit/payment/navigation; conflicting/malformed year rejection; 2024 list/print report inclusion and 2026 exclusion; provisional legacy labels; six HTTP projects; duplicate ID without overwrite; restored input; NULL optional dates; start-year versus undated visibility; forced schedule-insert failure rolling back its parent; and migration reversibility limitations.

An initial sandboxed connection was blocked; local DB execution approval was used for isolated tests. Development test assertions were corrected to inspect table rows instead of notification text, compare audit timestamps consistently across connection timezones, and submit malformed receipt arrays as actual arrays rather than the multipart fixture helper's string conversion. The final suite passed after these fixture/assertion corrections. Detailed final output: `/private/tmp/cajpemajuan-stage2a-test-results.txt`.

No manual browser test, real receipt viewing/rendering, print-dialog execution or concurrent-load test was performed. Confirmation tests use DOM stubs around the actual handler. Duplicate-race handling is implemented but the suite's duplicate-ID test is sequential. The live schema was only read, not migrated or used for destructive testing.

## Files created in Phase 2A

- `app/utility/ReportingYear.php`
- `app/views/project/create-schedule-rows.php`
- `app/views/project/undated.php`
- The four versioned SQL files and `database/migrations/README.md` listed above
- `tests/stage2a-migrations.php`
- `tests/stage2a-functional.php`
- `tests/stage2a-project-confirmation.js`
- `docs/stage2a-functional-improvements.md`

## Files modified relative to the Stage 1 working tree

- Controllers: `app/controllers/ModuleController.php`, `ChargeController.php`, `ProjectController.php`, `DashboardController.php`, `ReportController.php`, `NotifyController.php`, `UserController.php`.
- Models: `app/models/DevelopmentCharge.php`, `app/models/Project.php`.
- Views: `app/views/charge/{index,create,edit,payment}.php`; `app/views/project/{index,create,edit,finance}.php`; `app/views/report/charges.php`; `app/views/layout/cajpemajuan/{header,sidebar,topbar}.php`; `app/views/notification/index.php`; `app/views/user/{index,create,edit}.php`.
- Scripts/schema/tests: `public/js/dashboard.js`, `public/js/project-finance.js`, `scripts/check-setup.php`, `database/schema.sql`, `tests/modules-smoke.php`, `tests/stage1-security.php`.

User/notification view/controller changes carry year context; they do not change permissions. Stage 1 reports, the audit report, SecurityComponent, Session and ChargeValidation remain unchanged in this phase.

## Remaining Stage 2B/2C dependencies

- Confirm actual-cash dashboard accounting, opening balances, cash-year reports, interest display and assessed-versus-outstanding terminology before financial totals are changed. Current dashboard totals are still the known Stage 1 implementation.
- Approve installment threshold edge cases, below-RM100,000 behaviour, interest basis/frequency, anchor dates, deadlines and rounding before automatic amounts/interest. No 8% formula is supplied here.
- Review legacy record years and original receipt references before any backfill/correction workflow. Confirm receipt-number uniqueness scope before adding any constraint.
- Approve retention/reversal/archive policy, authenticated receipt serving, department/PIC recipient access and per-user notification dismissal. Existing admin global clear and finance cascades remain as documented in Stage 1.
- Review production backup/restore rehearsal and apply the two additive migrations manually before enabling Phase 2A. Follow with real browser checks for multi-tab years, receipt forms, project confirmations and print output.

## Git status

The working tree remains uncommitted on `fix/security-data-integrity-v1`. It contains the preserved Stage 1 modifications plus the Phase 2A files above. At task start there were 32 tracked modified files and five untracked Stage 1/audit files. Phase 2A additionally modifies the clean schema, charge report and setup checker, so 35 tracked files are now modified in total; Phase 2A also updates the already-untracked Stage 1 test include.

New Phase 2A files and all pre-existing untracked files remain untracked; `database/migrations/` appears as an untracked directory in the short Git summary. Nothing was staged, committed or pushed, and no client migration was applied.
