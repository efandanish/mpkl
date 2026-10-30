# Stage 1 — Security and data integrity handover

Date: 23 October 2026. Runtime tested: XAMPP PHP 7.4.33 / MariaDB. Reference: `docs/client-requirements-audit-v1.md`.

## Outcome and scope

Implemented controller authorization, framework-backed CSRF protection, server-side charge validation, paid-schedule safeguards and transaction boundaries. Current admin/user permissions remain: normal users still cannot create or update charges/projects. No new installment/interest formula, dashboard financial calculation, departmental access model, effective-year feature or receipt-number schema was implemented. No migration, dependency installation, commit or push was performed.

Client database rows and existing receipt paths/content were fingerprinted before/after the isolated HTTP suite and were unchanged. All test mutations used a randomly named disposable database and temporary receipt/session files. Temporary test databases and fixture directories were removed by the runner.

## Reinspection of the audit

The listed controllers, models, SecurityComponent, charge/project forms, SQL schema and tests were re-read before edits. Initial Git status contained only the untracked audit report, with no tracked source changes. Stage 1 findings were still present:

| Audit finding | Reconfirmed behaviour | Stage 1 disposition |
| --- | --- | --- |
| Ordinary user could clear everyone's notifications | Notify clearAll had login-only access; model deleted both tables globally | Admin-only controller fallback; hide ordinary-user clear button |
| Missing POST CSRF | Module controllers loaded no Security component; active forms and generated finance forms lacked tokens | Shared POST gate reuses existing SecurityComponent token validation |
| Charge validation missing | Normalizer defaulted/cast invalid input; schedule loops skipped bad entries | Validate complete payload before writes on create/edit |
| Paid/history risks | Charge pending SQL had a paid-status guard; project schedule replacement could delete paid schedules | Retain charge protections; reject project replacement/deletion involving paid/linked schedules; transactions |
| Stale session roles | Authorization read only cached session role | Reload current user/role on protected module requests |
| Incorrect dashboard cash totals | Assessed-charge aggregate and zero balance/expenditure placeholders | Confirmed; deferred to Stage 2 |
| No user department/PIC routing | User schema lacks association; notification queries are global | Confirmed; deferred; not represented as solved by admin clear restriction |
| Wrong automatic installment calculation | Manual inputs/equal-division fallback; no approved interest specification | Confirmed; no new formula introduced |
| Historical year loss | Navigation drops year; charge query uses created_at | Confirmed; deferred |
| No receipt reference/date wording | Only date/file stored; no reference field | Confirmed; reference feature deferred; upload safety improved |
| Reported three-project failure | No count cap; duplicate IDs/blank date insertion failures | No arbitrary limit added; blank optional project dates now normalize to NULL consistently with edit |

The pre-existing audit remains unchanged and describes the pre-Stage-1 application.

## Security changes

### Notification deletion and read state

`NotifyController::clearAll` now checks current admin authority in the controller. Both `/notification/clearAll` and legacy `/notify/clearAll` inherit the restriction and CSRF validation. Ordinary users cannot clear the global notification pool or submit another user's ID to do so. The clear button is admin-only.

The current schema has read markers but no dismissal records. Therefore **per-user dismissal is not implemented**. The explicitly permitted temporary fallback was selected: admin global clear remains global and destructive. No existing notification was cleared during installation; admin clear was tested only on disposable fixtures. `read` and `markAllRead` continue to use the authenticated session user ID, ignoring submitted recipient IDs, and existing redirects are preserved.

### CSRF

`ModuleController::requirePostCsrf` calls the existing `SecurityComponent::CsrfToken` method, rather than maintaining a second token mechanism. It protects charge/project writes, finance operations, user management, notification actions and authentication POSTs; dashboard POSTs are covered too. GET pages and printable reports retain their existing behaviour.

MiniPHP's full Security startup also enforces fixed form fields and a Referer requirement. It was not enabled wholesale because the modules have dynamic installment/file inputs and legitimate requests may lack Referer. Instead the existing token validator is invoked explicitly before actions. No conflicting field-count or second token validator was added.

Token generation now uses `random_bytes(32)` and hexadecimal encoding. Validation requires scalar string tokens, uses `hash_equals`, checks the existing one-day token lifetime and does not log submitted token values. Missing, invalid, expired or array-shaped tokens are rejected with HTTP 403 and a Malay reload/retry message. The expiration branch was reviewed but not separately exercised by the HTTP suite.

Tokens are included in active charge/project/user/notification/login/logout forms. JavaScript-generated project payment, cancellation, schedule, expense and variation forms obtain the same token from the module page's meta element. Login and logout retain session behaviour and now require tokens for POST. Any external script posting directly to these URLs must first obtain its session token from a form.

### Current-user authorization

`ModuleController::requireLogin` reloads the user from the database and refreshes session role/name/email before protected module actions. Missing/deleted users or unknown roles lose access; demoted admins cannot keep using an old admin session for financial writes. Dashboard and reports use the same current-user check. Existing action restrictions and URLs remain; this does not grant Stage 3 entry privileges.

## Validation rules

`app/utility/ChargeValidation.php` validates the current manual schedule format on both charge create and update:

- Required trimmed text: developer (200 characters), project (255), OSC ID (100), lot/PT (100), mukim (150), file number (150). Reject absent, whitespace-only, array-shaped and overlong values.
- Required positive charge amount within DECIMAL(12,2): at most ten integer digits and two decimal places. Reject zero, negatives, nonnumeric input, scientific notation, separators, excess precision and overflow. No zero-value exemption policy was invented; obtain client signoff if exemptions exist.
- Allowlisted OSC decisions: Lulus, Tangguh, Ditolak. Payment plans: Bayaran Penuh, 3 Ansuran, 4 Ansuran.
- New manual installments require matching array keys, the selected three/four row count, sequential numbers starting at one, positive amounts and valid real YYYY-MM-DD calendar dates. Reject malformed, nested, mismatched, missing, duplicate or nonpositive entries rather than skipping them.
- Pending edits must include the current pending rows with unique IDs belonging to that charge. Paid rows and foreign schedule IDs cannot be submitted for modification. Plan changes after payment are rejected; old/new schedule arrays cannot be mixed during a plan change.
- Existing full-payment creation continues to store a NULL deadline because its existing form has no deadline input. An unchanged historical NULL deadline is preserved on an unrelated edit, including old multi-installment schedules. These compatibility exceptions are explicit; new manual schedules cannot omit their dates.
- The current schedule amounts remain manual. No threshold, interest, three-month formula, schedule-total equality or chronological-spacing business rule was invented. Principal/interest reconciliation remains Stage 2 work.

Validation failures return HTTP 422, show escaped Malay errors and preserve main charge input and manual schedule values where practical. Malformed structures cannot always be reconstructed as usable rows; users must correct/re-enter those entries. HTML required/min attributes were aligned with the server, and restored create rows are populated by JavaScript. Invalid select/date values may be reset by browser controls.

Related project data-integrity checks validate required core identifiers, enum values, monetary/date input and complete optional schedule rows before persistence. Blank optional start/end dates bind NULL on create, matching edit. Project finance dates/amounts/types are validated server-side. Existing valid zero project financial fields remain allowed; positive-charge requirements apply to development charges. Malformed user-management scalar fields are rejected safely.

## Financial data safeguards and retention

- Charge create/update, schedule generation, receipt recording/cancellation DB writes and deletion use transaction boundaries where multiple DB writes occur. Project parent/schedule saves, recording/cancelling schedule expenses and deletion are also grouped. Notification global clear's two deletes are grouped.
- Charge/project inserts now obtain their own connection's last inserted ID instead of looking up a business ID that could race with another insert.
- Parent row locks serialize charge edit/schedule generation/receipt posting and relevant project finance changes. Repeat receipt posting cannot overwrite a paid installment; repeat project schedule payments are rejected.
- Project schedule replacement involving paid rows or linked expenses is rejected, with the parent update rolled back. Identical submitted schedules are retained instead of deleted/reinserted. Schedule deletion rechecks paid/expense linkage under the parent lock. Cancelling one legacy linked expense does not mark its schedule pending while other linked expenses remain.
- Receipt posting requires a successful upload server-side, a valid calendar date, permitted extension and matching detected MIME type, within the existing 5 MB maximum. Random filenames avoid same-second overwrite collisions. A failed DB posting cleans up only the new file uploaded by that request.
- Receipt cancellation filesystem access is constrained to the application's receipt filename namespace and skips symlink files. `RECEIPT_STORAGE_PATH` is an optional local configuration override used by tests; the production default remains `public/uploads/receipts`. No live configuration file was changed.

**Retention semantics were not replaced.** Admin charge/project deletion still cascades to financial rows; cancellation still clears receipt metadata and removes a recognized receipt file; project expense cancellation still deletes that expense row. User management and single-row finance operations are not comprehensively redesigned as an audit ledger. No historical values or receipt paths were backfilled.

Filesystem deletion cannot participate in MariaDB rollback. Cancellation still performs file removal before its database work; a later DB failure can leave missing evidence. Path-constrained deletion may retain a nonstandard historical file while clearing its metadata. These are remaining risks, not claims of immutable retention. Approve an archive/reversal policy before changing that behaviour, then add staged migrations for actor/reason/history and authenticated receipt serving. Back up and reconcile existing rows/files first; do not silently rewrite paid history or drop existing cascades without a reviewed migration.

## Tests and results

| Check | Result |
| --- | --- |
| XAMPP PHP 7.4 syntax, app/public/scripts/tests | 126 PHP files; zero failures |
| `node --check public/js/dashboard.js` | Passed |
| `node --check public/js/project-finance.js` | Passed |
| `/Applications/XAMPP/xamppfiles/bin/php tests/compatibility-smoke.php` | All 21 assertions passed |
| `/Applications/XAMPP/xamppfiles/bin/php tests/startup-smoke.php` | All 6 assertions passed; temporary fixtures cleaned |
| `/Applications/XAMPP/xamppfiles/bin/php tests/modules-smoke.php` | 403 assertions passed, including included Stage 1 tests; exit 0 |
| `git diff --check` | Passed |
| PHP 8-only syntax/dependency review | New code lints/runs on actual PHP 7.4.33; no added dependencies |
| Client database before/after row fingerprints | Unchanged |
| Client receipt path/content fingerprints | Unchanged |

The expanded runner creates a random `cajpemajuan_modules_<random>_test` database, imports the clean schema there, and runs the real front controller on an ephemeral localhost port. It overrides DB_NAME before models connect, uses temporary session/router/server log files, and overrides receipt storage to its temporary directory. Cleanup terminates the server, removes fixtures and drops only its generated database. The include `tests/stage1-security.php` refuses execution outside that setup.

Coverage includes normal-user notification deletion denial on both URLs; attempts to supply another user's read identity; preserved read/unread and “Tanda Semua Dibaca”; missing CSRF across all sensitive action families; invalid/array tokens; token-bearing rendered forms; valid authorized POSTs; every required charge field; nonpositive/nonnumeric/overflow/precision amounts; invalid enums; invalid calendar dates and installment structures; rejected edits causing no parent changes; stale admin roles and deleted users; transaction rollback; paid charge/project guards; preserved legacy NULL deadlines; required receipt upload; admin CRUD/finance/variations; notification redirects; report rendering; MPKL/dashboard structure; login/logout. Admin global clear and financial deletes/cancellations were exercised only against temporary fixtures.

JavaScript syntax and rendered token fields were verified; a real browser did not execute SweetAlert or interactive restoration. HTTP requests exercised server behaviour, not visual layout. Concurrency guards were inspected and sequential duplicate/paid protections exercised; no parallel load test was run. Receipt tests check upload, persisted path, view-link rendering and temporary-file cancellation; authenticated download/content rendering is not provided by the current design.

The first sandboxed DB connection was blocked, so the isolated suite ran with explicit local-DB execution approval. Initial development runs exposed response-status propagation through MiniPHP and a fingerprint comparison timezone mismatch; both were corrected before the final successful runs. The schema import sets UTC, so the fingerprint connection now uses UTC consistently before both snapshots. No client-data repair was performed. Final detailed test output is in the temporary file `/private/tmp/cajpemajuan-stage1-test-results.txt`.

`tests/database-smoke.php` was inspected but not run against the configured client database: it requires a separate configured `_test` database and writes through model calls. Equivalent model/finance paths are covered by the disposable HTTP suite. No production dashboard GET or destructive client-data test was used.

## Files

Created for Stage 1:

- `app/utility/ChargeValidation.php`
- `app/views/charge/validation.php`
- `tests/stage1-security.php`
- `docs/stage1-security-data-integrity.md`

Modified (32 tracked files):

- Controllers: `app/controllers/AuthController.php`, `ChargeController.php`, `DashboardController.php`, `ModuleController.php`, `NotifyController.php`, `ProjectController.php`, `ReportController.php`, `UserController.php`.
- Framework/model helpers: `app/core/Session.php`, `app/core/components/SecurityComponent.php`, `app/models/Database.php`, `app/models/DevelopmentCharge.php`, `app/models/Project.php`.
- Views: `app/views/auth/login.php`; `app/views/charge/create.php`, `edit.php`, `index.php`, `payment.php`; `app/views/layout/cajpemajuan/header.php`, `sidebar.php`, `topbar.php`; `app/views/notification/index.php`; `app/views/project/create.php`, `edit.php`, `finance.php`, `index.php`; `app/views/user/create.php`, `edit.php`, `index.php`.
- Scripts/tests: `public/js/dashboard.js`, `public/js/project-finance.js`, `tests/modules-smoke.php`.

`app/models/Notification.php`, `database/schema.sql` and Composer files were inspected and left unchanged. The untracked `docs/client-requirements-audit-v1.md` existed at task start and was not edited.

## Remaining risks and recommended Stage 2 work

1. Approve retention/archive/reversal rules before replacing destructive admin actions, cascades or receipt removal. Database/file operations are not a single atomic unit, and not every legacy financial action has an immutable actor/reason history.
2. Correct dashboard actual-receipt/expenditure accounting and define opening balances, record year versus cash year, interest presentation and report reconciliation. Stage 1 deliberately leaves the existing financial totals unchanged.
3. Obtain the original table's unresolved interest/rounding/anchor/below-RM100,000 rules before an authoritative installment calculator. Keep current manual/legacy plans available during a reviewed transition.
4. Design departments/PICs, recipient authorization and personal dismissal. Until migrated, notifications and report/project visibility remain global for authenticated users, and admin clear deletes everyone's notification history. Read-state scoping is not recipient access control.
5. Automatic reminders still write during dashboard GET; an independent scheduled reminder process is not implemented. CSRF tokens do not make those GET-triggered reminder writes read-only.
6. Receipt files remain under public storage by default. MIME/extension checks are not malware scanning, and authenticated file-serving/retention policy remains unresolved. No receipt-reference or duplicate-reference policy was introduced.
7. Charge validation is enforced at controller save boundaries, not by new database constraints or all possible direct model callers. Existing invalid rows remain. Admin financial corrections can still leave principal/schedule totals inconsistent until Stage 2 reconciliation rules are approved. Project duplicate business IDs are still enforced by the database; a polished duplicate-ID recovery flow remains future work.
8. Continue with isolated browser checks for generated finance forms, validation recovery, multiple tabs/token expiry and failure recovery. Operational session-cookie/TLS settings and authentication rate limiting were not comprehensively redesigned. Existing login/logout behaviour was preserved.

No Stage 2/3 feature was implemented. Stage 3 permissions should be changed only after explicitly defining financial versus nonfinancial fields, using the new POST/auth/validation safeguards.

## Git status

Working tree contains the 32 tracked modified files above and these untracked files:

```text
?? app/utility/ChargeValidation.php
?? app/views/charge/validation.php
?? docs/client-requirements-audit-v1.md
?? docs/stage1-security-data-integrity.md
?? tests/stage1-security.php
```

The audit report was already untracked before Stage 1. No changes were staged, committed or pushed.
