# Client requirements compliance audit V1

Application: Sistem Akaun Caj Pemajuan. Audit date: 23 October 2026. Scope: read-only inspection, safe compatibility checks, live schema metadata reads and model checks in disposable isolated databases. No application fixes, client-data writes, dependency changes, commits or pushes were performed. This report is the only repository file created.

## 1. Executive summary

The application does not yet meet the client feedback as a whole. The most consequential findings are assessed charges presented as collections, zero expenditure/opening balance placeholders, missing server-side charge validation, unrestricted global notification deletion by ordinary users, absent departmental recipient controls, and loss of historical year context.

| Overall classification | Requirements |
| --- | ---: |
| PASS | 0 |
| PARTIAL | 3 |
| FAIL | 4 |
| UNVERIFIED | 1 |

Totals classify the eight requirements, not individual subchecks. PARTIAL means useful supporting behaviour exists but material requirements remain unmet; it does not mean production acceptance. UNVERIFIED for requirement 8 concerns resolution of the reported browser symptom: six project model inserts succeeded, disproving a model/schema three-record cap, but the client's exact failed submission was unavailable.

### Source documents actually inspected

Both files were located and readable outside the repository:

- `${HOME}/Desktop/Personal/Maklum Balas Sistem Caj Pemajuan V1.docx`
- `${HOME}/Desktop/Personal/Maklum Balas Sistem Caj Pemajuan V1.pdf`

The complete DOCX body text was extracted directly from `word/document.xml`. Its embedded installment table (`word/media/image6.png`) and edit screenshot (`word/media/image7.png`) were visually inspected. The PDF has eight pages; pages 4 and 5 were rendered directly in memory and visually inspected, including page 5's red mandatory-field annotations. No extracted document/image files were saved. Other PDF pages were not independently rendered; the corresponding feedback was read from the DOCX text. This is not a claim of full visual comparison of every page between formats.

Page 4 explicitly distinguishes `≤ RM200,000` from `> RM200,000`, specifies the first installment before the planning-permission form is issued, and later installments three months apart. Its interest sentence says 8% of the balance after installment 1, without a complete interest calculation convention. Page 5 marks all nine main charge fields mandatory. It shows installment amount and date fields, but does not separately mark these with red ticks; the general completeness instruction and the user's checklist require their validation when applicable.

Evidence references below are repository-relative `file:line` anchors. Live schema metadata was compared against the checked-in schema; no business records were printed or changed. Runtime verification does not include authenticated browser/UI submission, real receipt uploads or delivered notifications.

## 2. Requirements matrix

| # | Requirement | Result | Priority | Principal evidence |
| --- | --- | --- | --- | --- |
| 1 | Actual financial collections and balance | FAIL | P0 | `app/controllers/DashboardController.php:83`, `app/models/DevelopmentCharge.php:78` |
| 2 | User creation, admin financial corrections | PARTIAL | P1; associated security issues P0 | `app/controllers/ChargeController.php:11`, `app/controllers/ProjectController.php:12` |
| 3 | User departments and departmental PIC notifications | FAIL | P0/P1 | `database/schema.sql:204`, `app/models/Notification.php:172` |
| 4 | Automatic installment amounts and deadlines | FAIL | P0 | `public/js/dashboard.js:379`, `app/controllers/ChargeController.php:433` |
| 5 | Mandatory charge fields, including backend checks | FAIL | P0/P1 | `app/controllers/ChargeController.php:504`, `app/models/DevelopmentCharge.php:107` |
| 6 | Historical-year entry and persistence | PARTIAL | P1 | `app/models/DevelopmentCharge.php:55`, `app/views/layout/cajpemajuan/sidebar.php:32` |
| 7 | Receipt reference and payment-date label | PARTIAL | P1/P2 | `app/views/charge/payment.php:109`, `database/schema.sql:57` |
| 8 | Unlimited project creation | UNVERIFIED | P1 | `app/models/Project.php:39`, `database/schema.sql:253`; six isolated inserts succeeded |

## 3. Detailed findings

### 1. Dashboard financial values — FAIL

**Expected:** Jumlah Kutipan represents received money; unpaid charges are distinguished as Jumlah Caj Pemajuan. Partial receipts, reversals, financial-year attribution and expenditure must reconcile to Baki Akhir Semasa.

**Implementation and evidence:**

- The displayed collection value calls `getTotalChargesByYear`, which sums `charge_amount`, filtered on charge `created_at`: `app/controllers/DashboardController.php:83`, `app/models/DevelopmentCharge.php:78`. Pending and partially paid charges therefore contribute their entire assessed amount. Cancellation of a receipt does not reduce this displayed collection value.
- A better paid-status aggregate exists at `app/models/DevelopmentCharge.php:38`, and per-charge totals correctly select only `status = 'Dibayar'` at `app/models/DevelopmentCharge.php:399`. The dashboard fetches the summary at `app/controllers/DashboardController.php:96`, but its rendered page uses only the original four financial cards: `app/views/dashboard/index.php:17`. The paid aggregate is not used for those cards.
- Even that paid aggregate attributes all receipts to `YEAR(c.created_at)`, not payment year: `app/models/DevelopmentCharge.php:48`. Isolated evidence: a 2024 charge for RM180,000 paid RM100,000 in 2025 produces assessed 2024 total RM180,000, paid 2024 cohort RM100,000, and paid 2025 cohort RM0. Cancellation reduced `getTotalPaid` to zero, but does not change assessed charge totals.
- Opening balance and expenditure are hardcoded to zero; closing balance consequently equals assessed charges: `app/controllers/DashboardController.php:87`. Project expenditure aggregation exists but uses project start-year cohorts rather than expense dates: `app/models/Project.php:502`.
- Cards say Jumlah Kutipan, Jumlah Perbelanjaan and Baki Akhir Semasa: `app/views/dashboard/partials/summary-cards.php:14`, `:24`, `:34`. There is no distinct assessed/unpaid charge card in that rendered partial.
- Cancellation resets paid status to pending: `app/models/DevelopmentCharge.php:481`; paid-only totals then exclude it. There is no separate reversal state or immutable reversal ledger in `database/schema.sql:57`. A payment row represents an entire scheduled amount; the receipt POST accepts a date/file, not a partial amount (`app/controllers/ChargeController.php:590`, `:740`). Partial payment across installments is representable; partial receipt within one installment is not independently represented.

**Gap/risk:** Cash and balances are materially overstated or misleading, and cash-year reporting can be wrong. Existing tests explicitly expect the incorrect assessed-charge card value: `tests/modules-smoke.php:140`.

**Recommended correction:** Aggregate posted receipts and expenses by their approved accounting dates, distinguish assessed charges/unpaid principal, and reconcile opening/closing balances. Preserve reversal history. Obtain signoff on whether Jumlah Caj Pemajuan means all assessed charges or only outstanding amounts, on interest presentation, and on financial-year versus charge-cohort reporting. Define opening balances and carry-forward before changing calculations.

### 2. Normal user entry / admin financial control — PARTIAL

**Expected:** Both roles can create charges/projects. Protected financial corrections after saving are admin-only. Nonfinancial editing rights require an explicit policy rather than an assumption that every edit must be blocked.

**Implementation and evidence:**

- Both controllers require admin for every non-GET request and for GET create. Only index/edit/payment or finance GET pages are allowed for normal users: `app/controllers/ChargeController.php:5`, `app/controllers/ProjectController.php:6`. Thus normal users cannot open create pages or submit valid forms. Create buttons are also admin-only: `app/views/charge/index.php:1`, `app/views/project/index.php:1`.
- This is actual controller authorization, not merely hidden UI. Startup runs before actions (`app/core/App.php:113`, `app/core/Controller.php:73`), and `requireAdmin` rejects any role other than session `admin` (`app/controllers/ModuleController.php:16`). Crafted direct charge/project update POSTs are blocked for an ordinary session, including financial values. Tests encode these denials at `tests/modules-smoke.php:113` and `:188`, but were inspected rather than executed in this audit.
- Admin update binds charge amounts and project SST/meeting values: `app/models/DevelopmentCharge.php:236`, `app/models/Project.php:173`. Ordinary users' nonfinancial fields are disabled too (`app/views/charge/edit.php:45`, `:62`), and all update POSTs are rejected. This is broader restriction than the client's stated financial-value rule; permission for particular nonfinancial edits remains to be agreed.
- Charge/project delete, receipt recording/cancellation, project schedules, expenses and variations all inherit the admin guard. Relevant actions include `app/controllers/ChargeController.php:18`, `:554`, `:782`; `app/controllers/ProjectController.php:294`, `:430`, `:486`, `:511`, `:552`. No field-by-field financial protection exists for a future ordinary-user edit path: simply removing the blanket guard would expose all fields supplied by `getFormData`.
- Roles are cached in session at login (`app/controllers/AuthController.php:142`). Module authorization reads this cached role without reloading the user (`app/controllers/ModuleController.php:18`). Revoking admin rights or deleting a user does not visibly invalidate their other active sessions through these paths (`app/controllers/UserController.php:388`, `:412`, `:526`).

**Additional P0 security/destructive risks:** Module controllers disable framework security components with `loadComponents([])` (`app/controllers/ChargeController.php:7`, `app/controllers/ProjectController.php:8`, `app/controllers/NotifyController.php:7`). Their forms contain no CSRF token and no equivalent controller validation was found. Framework CSRF support exists (`app/core/components/SecurityComponent.php:220`) but is not invoked by these controllers. This exposes sensitive actions to cross-site request forgery where browser/session conditions permit; an exploit was not attempted. Parent deletion also cascades to receipt/payment or expense records (`database/schema.sql:351`, `:364`, `:372`), and charge cancellation deletes the receipt file (`app/controllers/ChargeController.php:817`). These behaviours require deliberate retention policy, not just admin-only buttons.

**Recommended correction:** Define action/field permissions; allow ordinary-user create without allowing arbitrary financial updates. Enforce server-side financial field comparison/allowlists, retain intentional sensitive-action restrictions, activate CSRF protection, revalidate session authority, and specify audit/retention controls. Do not automatically grant all nonfinancial edits.

### 3. User departments / departmental PIC notifications — FAIL

**Expected:** User registration/management records Jabatan; projects map reliably to departments; appropriate users receive scoped project-duration and unclaimed-payment reminders, with suitable admin oversight.

**Implementation and evidence:**

- Live `users` schema matches the checked-in fields name/email/password/role/timestamps, with no department or PIC association (`database/schema.sql:204`). User create/edit views capture name/email/password/role only (`app/views/user/create.php:29`, `:68`; `app/views/user/edit.php:29`, `:93`). List columns omit Jabatan (`app/views/user/index.php:43`). Create/update controller payloads omit it (`app/controllers/UserController.php:191`, `:388`); SQL likewise omits it (`app/models/User.php:72`, `:192`, `:215`). Login session has no departmental identity (`app/controllers/AuthController.php:144`). No separate self-registration route exists in the inspected AuthController; managed creation is admin-only (`app/controllers/UserController.php:43`).
- Project department is a plain varchar (`database/schema.sql:124`). UI has four fixed department strings (`app/views/project/create.php:48`); backend trims arbitrary submitted text without membership validation (`app/controllers/ProjectController.php:340`). There is no foreign-key association to users/departments.
- `getAllByYear` and `getById` have no departmental predicate (`app/models/Project.php:21`, `:518`); authenticated ordinary users can inspect any project's finance page (`app/controllers/ProjectController.php:26`). Reports require login only and optional department filtering is user-supplied, not access control (`app/controllers/ReportController.php:16`, `:150`, `:224`).
- Notification user IDs distinguish read state only. Recent/history/count queries do not restrict recipients (`app/models/Notification.php:76`, `:172`, `:207`). Notifications store no recipient/department (`database/schema.sql:81`), so admins and ordinary users see the same global activity subject to recency/read status.
- Pending schedule query includes department (`app/models/Project.php:649`), but reminder creation never uses it for recipient selection (`app/models/Notification.php:518`). Reminders use claim-date overdue/today/within-seven-days conditions (`:562`, `:621`, `:673`), with global entity/action deduplication (`:260`). No project `end_date` overdue reminder or separate claimed/unclaimed contractor state is implemented in this path. Pending payment is not necessarily equivalent to an unclaimed contractor payment.
- Generation happens during dashboard GET (`app/controllers/DashboardController.php:47`, `:53`), not demonstrably on an independent automatic schedule. Such a GET can write notifications; it was deliberately not requested against client data.
- **P0 destructive access:** Any logged-in user can POST `notification/clearAll` or legacy `notify/clearAll` (`app/controllers/NotifyController.php:92`). It deletes all users' notification reads and all notifications (`app/models/Notification.php:244`). The UI exposes the button without a role check (`app/views/notification/index.php:23`). This was traced, not executed.

**Gap/risk:** Cross-department visibility, missed responsibility, no specific PIC routing, global deletion and no reliable handling of department transfers/missing departments/multiple members. Admins retain global visibility, but this is not a considered recipient policy.

**Recommended correction:** Normalize departments, map users/projects, define access independently of list filters, and introduce notification recipients checked on list/count/read/deep-link/dismiss paths. Make dismissal personal and restrict any global purge. Add distinct project-overdue and unclaimed-payment rules with scheduled execution and deduplication. Client must designate how a PIC is chosen when several users share a department, delegation/fallback rules, and whether recipients are captured at notification creation or reassigned on transfer.

### 4. Automatic installments — FAIL

**Expected:** Server-authoritative category calculation and dates consistent with PDF page 4, plus a matching preview and persistent auditable schedule, after unresolved financial rules receive signoff.

**Observed document table:**

| Installment | Charge ≤ RM200,000 | Charge > RM200,000 | Timing |
| --- | --- | --- | --- |
| 1 | RM100,000 | 50% of charge | Before planning-permission form issued |
| 2 | 50% of remaining charge | 25% of charge | Three months after installment 1 |
| 3 | 50% of remaining charge | 25% of charge | Three months after installment 2 |

The low-band third row repeats “50% daripada baki amaun caj pemajuan”; it does not explicitly name a frozen post-first-installment balance. Treating it as a recalculated remaining balance after installment 2 would leave principal unpaid. This ambiguity needs confirmation rather than a silently chosen interpretation. The table itself contains no interest allocation columns.

**Implementation and evidence:**

- Create JavaScript only generates three/four blank amount and date inputs, all user-editable (`public/js/dashboard.js:379`, `:452`, `:470`). It does not calculate thresholds, interest or due dates.
- Edit plan-change preview divides total equally and rounds every row with `toFixed(2)` (`public/js/dashboard.js:930`, `:952`, `:1010`), not the client's table. It updates on plan change, with no demonstrated authoritative amount-change recalculation.
- Create trusts submitted installment numbers/amounts/dates, skips nonpositive entries and accepts absent dates (`app/controllers/ChargeController.php:433`). Update similarly accepts new schedules and pending schedule overrides (`:212`, `:269`). No count/sequence/total/date-spacing validation prevents crafted schedules. Admin-only access limits the actor, not calculation error.
- Fallback generation divides equally by three or four and stores NULL dates (`app/controllers/ChargeController.php:947`, `:974`). Full payment also receives NULL due date (`:421`). The model stores arbitrary supplied schedule values (`app/models/DevelopmentCharge.php:330`); the schema has nullable dates and no principal/interest split, anchor date, formula version or unique charge/installment number (`database/schema.sql:57`, `:232`).
- Paid schedules are protected from the pending update SQL and plan changes are frozen when any payment is paid (`app/models/DevelopmentCharge.php:551`, `app/controllers/ChargeController.php:111`). However the main charge amount still changes independently (`:185`); even an unchanged plan can leave a stale or inconsistent schedule. The multi-step save is not wrapped in a transaction in these actions.

**Gap/risk:** Neither exact RM200,000 nor any other band is implemented; no 8% computation, deadline anchor or three-month dates. Equal rounding can lose/gain cents (e.g. RM100 divided by three). Missing/mismatched arrays can silently leave incomplete schedules after the main record is saved.

**Recommended correction:** Approve the calculation specification first. Then use a single PHP 7.4-compatible server calculator, deterministic money rounding and residual allocation, persist principal/interest/date/version data, validate the entire operation atomically and reject user-overridden derived values. Match preview to server results. Decide how legacy four-installment plans and paid schedules are migrated or preserved.

### 5. Mandatory development charge fields — FAIL

**Expected:** All nine fields shown on page 5, plus applicable installment details, must be complete and valid on create/edit, with clear errors and preserved input.

**Implementation and evidence:**

| Field | Create HTML | Edit HTML | Backend |
| --- | --- | --- | --- |
| Nama Pemaju / Nama Projek | required (`app/views/charge/create.php:39`, `:50`) | required (`app/views/charge/edit.php:45`, `:62`) | trim/default only |
| ID OSC / No. Lot/PT / Mukim / No. Fail | optional (`app/views/charge/create.php:61`, `:72`, `:83`, `:94`) | optional (`app/views/charge/edit.php:79`, `:95`, `:111`, `:127`) | trim/default only |
| Jumlah Caj | required but min=0 (`app/views/charge/create.php:105`) | same (`app/views/charge/edit.php:143`) | float cast/default zero |
| Keputusan OSC | select without required (`app/views/charge/create.php:116`) | without required (`app/views/charge/edit.php:162`) | no allowlist; default Tangguh |
| Pelan Bayaran | required (`app/views/charge/create.php:141`) | plan control near `app/views/charge/edit.php:200` | no allowlist; default full payment |
| Installment values/dates | JS required controls (`public/js/dashboard.js:452`, `:470`) | pending controls required (`app/views/charge/edit.php:387`, `:406`) | no comprehensive validation |

`getFormData` normalizes input but does not validate it (`app/controllers/ChargeController.php:504`). Both create and update persist immediately (`:405`, `:97`, `:185`); model insert/update also contain no business validation (`app/models/DevelopmentCharge.php:107`, `:236`). Live schema permits optional fields, empty strings and zero amounts; NOT NULL does not reject empty text (`database/schema.sql:31`).

**Functional evidence:** Calling the actual private normalizer through reflection with an empty POST produced empty names/IDs and zero charge. The actual model accepted that payload in the isolated database. Whitespace normalized to empty and a negative amount remained negative; empty decision/plan strings were also returned without error. These are model/normalization tests, not full HTTP POST tests. Invalid nonempty dates can throw at MariaDB, but the controller does not implement strict date validation or helpful recovery; omitted schedule arrays can skip schedule creation entirely (`app/controllers/ChargeController.php:438`).

**Gap/risk:** Browser validation can be bypassed, malformed arrays can cause warnings/errors, partial schedules can persist, and redirects offer no field-level validation with old input (`app/controllers/ChargeController.php:412`, `:500`).

**Recommended correction:** Validate trimmed strings, lengths, enum allowlists, decimal format/positive limits, real calendar dates, array shape and required schedule entries before any write. Return actionable field errors with submitted values. Align HTML and server validation and add database constraints only after existing records are reviewed. Clarify whether zero-charge exemptions ever exist rather than silently supporting zero.

### 6. Year-specific data entry — PARTIAL

**Expected:** Preserve selected year through navigation/forms/redirects, and store an explicit effective charge year so new historical records do not become current-year records.

**Implementation and evidence:** Year selection offers 2024/2025/2026 (`app/views/layout/cajpemajuan/topbar.php:66`); module lists correctly read a supplied year (`app/controllers/ChargeController.php:367`, `app/controllers/ProjectController.php:22`). But sidebar links discard it (`app/views/layout/cajpemajuan/sidebar.php:32`), as do create/back links (`app/views/charge/index.php:1`, `app/views/charge/create.php:1`), create form action (`:8`), and success redirects (`app/controllers/ChargeController.php:358`, `:500`, `:775`). Edit/payment rendering does not supply a retained year (`:378`, `:554`); topbar defaults to 2026 and falls back to dashboard where `yearModule` is absent (`app/views/layout/cajpemajuan/topbar.php:2`, `:57`). No session year fallback is implemented in these paths.

The charge insert has no effective-year input (`app/models/DevelopmentCharge.php:111`); schema supplies current `created_at` (`database/schema.sql:42`), and all charge-year filters use that timestamp (`app/models/DevelopmentCharge.php:55`, `:78`). Therefore a 2024 form context cannot make a newly inserted 2026 charge belong to 2024. Projects instead filter by start date (`app/models/Project.php:518`), meaning NULL dates disappear from year lists and historical dates can hide a successful insert after a redirect to 2026.

Print reports reuse the same charge/project cohort queries (`app/controllers/ReportController.php:34`, `:144`), so they share the flawed semantics. Report JS passes the currently available year (`public/js/dashboard.js:1311`, `:1373`); printable HTML invokes browser printing (`app/views/report/charges.php:306`, `app/views/report/projects.php:261`). No separate spreadsheet export path was identified in these controllers; rendered/exported document correctness was not tested.

**Risk/correction:** Historical input appears lost or under the wrong year. Add validated `record_year` or approved effective date, keep `created_at` as audit metadata, review/backfill historical assignments with evidence, preserve year on all relevant URLs/forms/redirects, and align lists/reports. Keep cash payment year distinct from record year and project start year. Do not infer historical year automatically from file-number text.

### 7. Receipt number / payment-date label — PARTIAL

**Expected:** Required No. Resit plus “Tarikh Bayaran / Tarikh Resit”, persisted with receipt metadata and visible after payment; deliberate correction/cancellation and duplicate policies.

**Implementation:** Payment form has a required date labelled only “Tarikh Bayaran” and required file, with no receipt reference (`app/views/charge/payment.php:109`, `:117`, `:131`). Schema stores file/date/status but no receipt number (`database/schema.sql:57`). Controller and model accept no receipt number (`app/controllers/ChargeController.php:590`, `app/models/DevelopmentCharge.php:446`). Paid entries show date and a file link (`app/views/charge/payment.php:159`, `:188`), not reference number.

Upload code supports jpg/jpeg/png/pdf extension checks, 5 MB maximum, destination creation and move-result handling (`app/controllers/ChargeController.php:615`, `:630`, `:655`, `:685`, `:720`). It does not inspect file content/MIME, and missing/failed uploads bypass the upload branch and can still mark the installment paid with a NULL file (`:612`, `:740`). Payment-date validation is empty-only (`:596`). No paid-state guard prevents re-posting to an already paid row (`:573`, `:740`), potentially replacing metadata; database result is not checked before notification/success. Receipts are linked from a public upload path, not an authenticated download endpoint. Real upload/view functionality remains runtime UNVERIFIED in this audit; existing tests cover it (`tests/modules-smoke.php:125`) but were not run because they write files into the repository.

Cancellation removes the actual receipt file before clearing database metadata (`app/controllers/ChargeController.php:817`, `:837`; `app/models/DevelopmentCharge.php:481`), with no transactional file/DB recovery or retained receipt history. No distinct correction/versioning flow or duplicate-number rule was found.

**Risk/correction:** Missing departmental receipt reference, incomplete evidence and destructive corrections. Add server-required receipt number/date/file validation, secure content validation/access, controlled correction/reversal history and consistent metadata handling. Decide whether duplicate numbers are globally unique, scoped by year/issuer, or legitimately reusable; do not add an arbitrary unique constraint before signoff. Wording is P2, receipt persistence/validation P1, retention/security P0 where applicable.

### 8. Unlimited project creation — UNVERIFIED (no three-record cap found)

**Expected:** Valid unique projects continue to save beyond three; failures explain the cause and preserve input.

**Trace and evidence:** Correct POST action and form ID (`app/views/project/create.php:7`); manual required project ID (`:37`); controller reads form, inserts, then saves schedules and notification (`app/controllers/ProjectController.php:52`). Model uses an ordinary INSERT followed by lookup by project ID, without count-based limits (`app/models/Project.php:39`, `:143`, `:149`). Schema has auto-increment primary ID and a unique business project ID (`database/schema.sql:253`, `:317`). No generated business ID mechanism exists in this creation path. JavaScript uses a SweetAlert confirmation followed by native submit (`public/js/dashboard.js:788`); no three-record counter was found in the inspected controller/model/form/scripts. Missing SweetAlert/CDN availability could prevent this JS submission after `preventDefault`; that is a possible general failure, not a reproduced cause. Assets are loaded from CDNs (`app/views/layout/cajpemajuan/footer.php:7`). List queries do not apply a three-row pagination/limit (`app/models/Project.php:518`).

**Isolated integration evidence:** On the current MariaDB 10.4.27 and checked-in schema, six consecutive unique valid projects saved through the actual Project model; database count was six. Reusing the sixth business ID produced SQLSTATE 23000. Passing empty `start_date` produced SQLSTATE 22007 under current strict SQL mode. Create binds blank date strings directly (`app/models/Project.php:128`), whereas update converts blanks to NULL (`:206`). UI dates are optional (`app/views/project/create.php:323`, `:334`). Duplicate-ID prevalidation and a local insertion-error catch are absent; PDO exceptions are enabled (`app/models/Database.php:48`). Global handler may show an error page, rather than the specific return-to-form symptom; that symptom was not reproduced. A false insert result redirects to list with no explanation (`app/controllers/ProjectController.php:67`). Successful historical or NULL-start records can also disappear from the default 2026 view (`:92`, `app/models/Project.php:525`).

**Conclusion:** Evidence rules out a hard three-project limit in this tested model/schema. Duplicate IDs, blank dates, year visibility and script failure are separate concrete/potential paths, not a proven diagnosis of the client's fourth submission. Full browser/controller flow and original failed payload/log remain unverified. Test databases were uniquely named and dropped; no live projects were inserted.

**Recommended correction:** Validate business-ID uniqueness with concurrency-safe database handling, normalize/validate optional dates, show errors with old input, preserve selected year, save parent/schedules atomically and verify JS submission. Reproduce the client's exact fourth-record data in an isolated HTTP environment before declaring the incident fixed. Adding a higher maximum would not address these failure paths.

## 4. Detailed role and permission matrix

“Allowed” below describes source-enforced current behaviour, not approval of the policy or a completed HTTP test. Admin authority comes from cached session role; ordinary users have no department identity.

| Operation | Anonymous | Normal user | Admin | Evidence / assessment |
| --- | --- | --- | --- | --- |
| Dashboard/list charges/projects | Denied | Allowed globally | Allowed globally | `app/controllers/DashboardController.php:17`; charge/project initialization |
| GET charge/project create | Denied | Denied | Allowed | `app/controllers/ChargeController.php:12`, `app/controllers/ProjectController.php:13`; client requires user access |
| POST create charge/project | Denied | Denied | Allowed | Same guards; client requires user access |
| GET charge/project edit details | Denied | Allowed globally, controls disabled | Allowed | `app/controllers/ChargeController.php:378`, `app/controllers/ProjectController.php:97`; department scope absent |
| POST nonfinancial update | Denied | Denied | Allowed | Blanket guards; precise intended policy unresolved |
| POST financial update | Denied | Denied | Allowed | Guard effective for direct user POST; validation/reconciliation deficient |
| GET charge payments/project finance | Denied | Allowed globally | Allowed globally | `app/controllers/ChargeController.php:554`, `app/controllers/ProjectController.php:26` |
| Record/cancel receipts and expenses | Denied | Denied | Allowed | Admin guards; no CSRF component enabled |
| Add/delete financial schedules or variations | Denied | Denied | Allowed | `app/controllers/ProjectController.php:511`, `:634`, `:646` and initialization; confirm policy with client |
| Delete charge/project | Denied | Denied | Allowed, cascading | `app/controllers/ChargeController.php:18`, `app/controllers/ProjectController.php:294`; retention risk |
| Manage users/roles | Denied | Denied | Allowed | `app/controllers/UserController.php:43`; current admin cannot demote/delete self (`:379`, `:477`) |
| View/print reports | Denied | Allowed globally | Allowed globally | `app/controllers/ReportController.php:16`; filters are not department authorization |
| View notifications | Denied | Global notifications | Global notifications | `app/models/Notification.php:172`; user-specific read markers only |
| Mark read/all read | Denied | Own read markers on global pool | Same | `app/controllers/NotifyController.php:41`, `:66` |
| Clear notifications | Denied | Deletes global pool | Deletes global pool | `app/controllers/NotifyController.php:92`; P0 unintended destructive capability |
| Direct public receipt URL | No controller gate in linked path | Same path | Same path | `app/views/charge/payment.php:188`; actual web-server access was not tested |

## 5. Database gaps and proposed migration plan — proposal only

The live nine-table metadata matches relevant `database/schema.sql` structures: no user department, recipient association, effective charge year or receipt number. Unique project ID exists. No CHECK constraints appeared in live constraint metadata. Live server reports strict SQL mode. The event metadata query failed because its event scheduler is disabled; no conclusion is made about installed event definitions or triggers because the batch stopped before querying triggers.

Checked-in SQL reviewed: `database/schema.sql`, populated legacy snapshot `database/caj_pemajuan.sql`, and `_installation/01 database.sql`, `_installation/02 tables.sql`, `_installation/03 triggers.sql`, `_installation/04 users.sql`. The `_installation` scripts describe the older generic MiniPHP tables/triggers, not incremental migrations for the charge/project schema (`_installation/02 tables.sql:5`, `_installation/03 triggers.sql:6`). No versioned migration directory was found among repository files. Do not import these scripts over the working database.

Proposed steps, subject to review:

1. Inventory/back up and reconcile existing financial records before constraints or backfills. Define accounting years, receipt retention and approved formulas. No automatic rewriting of paid history.
2. Add departments and validated user/project department associations; retain original department text during reviewed mapping. Select PIC association design only after deciding department versus project PIC, substitutes and membership cardinality. Stage nullable associations before enforcing completeness.
3. Add effective charge year/date and an index; backfill through reviewed evidence, then require values for new saves. Preserve creation timestamps. Define project reporting year separately from project start date. Store opening balances only under an approved ledger/balance policy.
4. Add receipt reference and controlled reversal/correction metadata. If partial receipts per installment are required, split schedule obligations from payment transactions; record actual receipt amounts and status independently. Keep monetary values as DECIMAL and distinguish principal/interest. Add uniqueness only after duplicate policy is approved.
5. Add schedule anchor/formula version and principal/interest breakdown; validate sequence and consider unique `(development_charge_id, installment_no)` after duplicates are reviewed. Add sensible positive-amount/date/state constraints compatible with MariaDB, and application validation for PHP 7.4. Do not guess missing first deadlines during migration.
6. Add notification recipients/dismissal and appropriate indexes; retain per-user read uniqueness (`database/schema.sql:245`). Define recipient history/transfers and global admin visibility explicitly. Add distinct unclaimed/claimed state if the client confirms that workflow.
7. Replace destructive finance deletion/cascades with the approved retention approach and actor/reason audit history. Review existing cascades at `database/schema.sql:351`, `:364`, `:372`, `:377` before schema changes. Backfill and enforce constraints in stages, with staging verification and a documented rollback plan.

No migration was authored or executed.

## 6. Calculation and business ambiguities requiring client signoff

- Exact RM200,000 belongs to the lower category: the original table resolves this. The implementation has no such branch.
- Below RM100,000 the literal first RM100,000 exceeds the principal. At exactly RM100,000 later principal is zero. Confirm eligibility, full-payment alternative and whether zero-value installments exist.
- Confirm installments 2 and 3 each use half the same post-installment-1 principal balance in the low category. The repeated “remaining balance” wording alone does not fully define the third-row base.
- Is 8% flat once, annual simple, compounded, or prorated? What day/month basis and duration apply? Does each later installment carry interest, and on which balance? Is interest embedded or additional? How do late/early payments and reversals affect it?
- What is the stored anchor for installment 1: proposed payment date, actual receipt date, approval date or form-issue deadline? Is the three-month offset tied to actual payment or scheduled due date? Define month-end/leap-year handling and business-day adjustment.
- Specify monetary precision, rounding mode and residual-cent allocation. Specify whether outstanding status and dashboard/report totals include interest.
- Are full payment and legacy four-installment plans still allowed? How are admin corrections after receipts reconciled without rewriting paid obligations?
- Does Jumlah Caj Pemajuan mean all assessed charges, unpaid charges only, or residual balances? Define opening balance source and whether yearly reporting is cash year or registration cohort.
- Define normal-user nonfinancial edits, approval transitions, sensitive actions and department scope. Decide PIC designation among multiple department users, missing-PIC escalation and department transfers.
- Define “unclaimed” separately from scheduled/unpaid, project-overdue criteria and reminder recurrence. Define receipt uniqueness and evidence-retention rules.

## 7. Suggested regression tests

1. Dashboard: unpaid RM180,000 yields zero collection; one paid installment contributes only its actual receipt; reversal removes cash impact with history retained; expense reduces balance; opening/closing balances reconcile. Cross-year charge/receipt/expense fixtures must follow approved accounting policy.
2. Permissions: normal user GET/POST create succeeds in both modules; direct POSTs altering charge/SST/meeting value/schedules fail after save; permitted nonfinancial edits succeed; admin corrections work and reconcile. Test demoted/deleted users with existing sessions, CSRF rejection and each destructive action.
3. Departments: admin CRUD persists Jabatan; arbitrary/missing department rejected; same/cross-department access, reports, notification counts/read/deep links/dismissal all agree. Test zero/one/multiple PICs, inactive users, reassignment and admin visibility. A user's dismiss operation must not delete another's history.
4. Calculation: RM99,999.99, RM100,000, RM100,000.01, RM199,999.99, RM200,000, RM200,000.01, high values and fractional cents under signed-off rules. Test anchors on 31 January/leap day, late/early receipts, interest and rounding reconciliation. Tampered preview values and missing/extra/duplicate array entries must fail safely; no half-saved schedule.
5. Validation: omit each required field, submit whitespace-only text, scalar instead of array, absent array index, bad enums, zero/negative/non-numeric/overflow amounts, impossible dates and dates out of order. Verify both create/edit, useful errors, input preservation and unchanged DB on failure.
6. Years: start dashboard in 2024/2025/2026; navigate/create/edit/view/pay/cancel/back/redirect/print; retain year. New historical charge retains current audit creation time but appears under intended effective year. Reports match lists and approved cash-year semantics.
7. Receipts: required reference/date/file; correct displayed label; persisted reference and view link; valid formats/size/MIME; absent/failed upload; malformed date; duplicate policy; repeat POST/concurrent correction; cancellation retains appropriate metadata/history and handles filesystem/DB failure coherently.
8. Projects: create at least six through HTTP with distinct manual IDs; duplicate and concurrent duplicate ID; optional blank dates, valid historical dates, malformed enums; JS enabled/disabled/CDN failure; schedule-insert failure rollback; visible result under selected year. Reproduce client's fourth submission verbatim in isolation.

## 8. Priority and implementation order

1. **P0:** Close global notification deletion and CSRF gaps; resolve financial data retention/cascades and cached authorization. Establish correct paid/expense/balance reporting and server-side financial/required-field validation before enabling more writers.
2. **P0 specification dependency:** Obtain installment, interest, cash-year and opening-balance signoff. Do not ship an invented 8% formula. Implement authoritative transactional schedules after agreement.
3. **P1:** Introduce reviewed effective years, receipt metadata, department/PIC and recipient schema. Fix ID/date insertion errors and useful error recovery. Update normal-user create and any agreed nonfinancial-edit permissions using explicit action/field rules.
4. **P1:** Align reminders, reports, cross-page year state and receipt correction/viewing with the revised data model. Add meaningful integration tests that assert client requirements rather than today's incorrect expectations.
5. **P2:** Apply exact payment-date wording, mandatory indicators, card labels and form usability. These must agree with backend behaviour.

## 9. Files expected to need modification in a later implementation

This is a proposal, not an instruction to change files during the audit:

- Controllers: `app/controllers/ChargeController.php`, `ProjectController.php`, `DashboardController.php`, `UserController.php`, `AuthController.php`, `ModuleController.php`, `NotifyController.php`, `ReportController.php`.
- Models/security: `app/models/DevelopmentCharge.php`, `Project.php`, `User.php`, `Notification.php`; review `app/core/Session.php` and `app/core/components/SecurityComponent.php` for integration. Prefer a dedicated calculator/validation/authorization service where justified rather than duplicating rules.
- Views: `app/views/charge/{index,create,edit,payment}.php`, `app/views/project/{index,create,edit,finance}.php`, `app/views/user/{index,create,edit}.php`, `app/views/dashboard/partials/summary-cards.php`, `app/views/layout/cajpemajuan/{sidebar,topbar,footer}.php`, `app/views/notification/index.php`, `app/views/report/{charges,projects}.php`.
- JavaScript: `public/js/dashboard.js`, `public/js/project-finance.js`; review `public/js/table-filters.js` for consistent UI state.
- Schema/testing: `database/schema.sql`; new versioned migrations and isolated requirement tests; update `tests/modules-smoke.php` and `tests/database-smoke.php`. Preserve populated legacy SQL as historical reference unless an explicit sanitized fixture policy is adopted. Document agreed rules and deployment steps in `README.md`/appropriate docs later.
- Potential new components: departments/PIC management, recipient persistence, scheduler entry point, authenticated receipt-serving action and immutable financial audit history. Exact files depend on approved design. Maintain PHP 7.4 compatibility and Bootstrap 5.3.3.

## 10. Test commands, results and limitations

### Executed

| Check | Command / method | Result |
| --- | --- | --- |
| Initial working tree | `git status --short` | Clean |
| Document discovery | `find` on parent workspace and user folders for `*maklum*` / `AGENTS.md` | Found DOCX/PDF in Desktop/Personal; additional duplicate copies elsewhere. No applicable AGENTS.md found in inspected workspace tree |
| Document inspection | Python stdlib ZIP/XML; in-memory embedded PNG inspection; macOS CoreGraphics in-memory PDF render | DOCX body read; PDF pages 4/5 visually checked; eight PDF pages detected |
| Required runtime | `/Applications/XAMPP/xamppfiles/bin/php -v` | PHP 7.4.33 |
| PHP syntax | Python `pathlib` enumerated PHP in app/public/scripts/tests and invoked `/Applications/XAMPP/xamppfiles/bin/php -l <file>` | 123 files, zero lint failures |
| Existing safe smoke | `/Applications/XAMPP/xamppfiles/bin/php tests/compatibility-smoke.php` | All 21 assertions passed: URL handling, PDO types, error responses, mail dependency load, CAPTCHA render |
| Live metadata | Inline PHP/PDO using configured credentials; `START TRANSACTION READ ONLY`, server version/SQL mode, SHOW TABLES, information_schema columns/constraints | Relevant live structures confirmed; sandbox initially blocked connection, approved escalation succeeded. Event query then failed with MariaDB 1577, scheduler disabled; later trigger query not reached. No live mutations |
| Input normalizer | Inline PHP reflection invoking `ChargeController::getFormData` for empty/whitespace/negative/empty-enum POST payloads | Invalid values returned without rejection; source agrees |
| Isolated DB checks | Inline PHP, actual models, fresh randomly named `caj_audit_<random>_test`, imported `database/schema.sql`, `Config::set('DB_NAME', $testName)` before model connection; unconditional `finally` drops only that DB | Six projects saved; duplicate ID 23000; empty date 22007; empty charge saved; paid/cohort/year and cancellation results confirmed |

The inline isolated harness used the same configured MariaDB server with a separate database; all writes were confined to that randomly named database. Valid project fixtures used distinct `AUDIT-1`…`AUDIT-6`, department Kejuruteraan, positive values and valid 2026 start/end dates. Empty-charge input came from the actual controller normalizer. A test-only 2024 creation timestamp and 2025 payment reproduced cohort-year behaviour. No receipt file or HTTP session was created.

For reproducibility, the executed isolated harness sequence was: create random database → import clean schema → override process-local DB_NAME → call `Project::create` six times → retry duplicate → retry blank start date → normalize empty charge POST and `DevelopmentCharge::create` → add/mark/cancel a payment on a 2024 fixture with 2025 payment date → query totals → drop database in `finally`. These are model integration checks, not browser acceptance. The first harness run completed the same creation checks and dropped its database, then exited 255 because PDO `rollBack()` did not recognize a transaction begun through raw SQL. The corrected rerun omitted that unnecessary transaction block and exited 0; both temporary databases were dropped. The initial metadata process also exited after its event-query error, closing its read-only connection.

### Existing tests inspected but not run

- `tests/modules-smoke.php`: isolates database but writes router/session fixtures and uploads a receipt into `public/uploads/receipts` (`:62`, `:74`, `:125`, `:234`). Not run under the sole-new-file restriction. It explicitly expects normal-user create denial (`:113`) and assessed-charge financial cards (`:143`); a green run would not certify the feedback requirements.
- `tests/database-smoke.php`: requires a separately configured `_test` DB, writes within a transaction and acknowledges auto-increment effects (`:3`). Not run against current configured data. Its charge fixture permits NULL fields now required by the client (`:24`). Equivalent selected model checks ran in newly created disposable databases instead.
- `tests/startup-smoke.php`: creates/deletes filesystem fixtures (`:10`). Not run to preserve the strict file-write boundary; its startup-specific behaviour does not establish financial compliance.

No source, dependency, live row or live schema was changed. No local server/dashboard HTTP request was made because dashboard GET generates notifications. No real email/PIC delivery, uploaded file access, JS execution, report printing, concurrent request handling or full session/role revocation was tested. Results based on static traces are identified as such. PHP lint and compatibility checks cover application syntax/runtime basics, not complete production compatibility or security certification. No external web research was needed; the audit concerns local implementation and supplied client requirements.

## 11. Git status

Initial `git status --short`: clean. Final expected and verified status after writing this report:

```text
?? docs/client-requirements-audit-v1.md
```

No tracked source files modified. No commit or push performed. Implementation awaits client review of this report and the unresolved business rules.
