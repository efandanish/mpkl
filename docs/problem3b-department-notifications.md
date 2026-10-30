# Problem 3B — Department-aware project notifications

## Root cause and scope

Notifications previously had only entity references and per-user read markers. Count, recent/history, lookup and read queries did not restrict recipients. Project payment reminders already ran on dashboard GET, but did not exclude completed/cancelled projects. Project end dates did not generate overdue alerts.

Stage 3A is available in the source: nullable `users.department`, stored `projects.department`, and four canonical `Departments::options()` values. The existing Problem 3 audit and Stage 3A report were reviewed. The referenced feedback PDF is not present in this checkout; implementation follows the supplied Problem 3B requirements.

Only Problem 3B is implemented. Project/report permissions, authentication design, financial calculations, payment recording/cancellation, UI, routes and dependencies are retained. No live migration, financial record change, notification deletion, password/role change, commit or push was performed by this task.

## Implementation and files

Modified:

- `app/models/Notification.php`: shared visibility rules for counts, recent/history, lookup, single read and bulk read; database-resolved project audience; project overdue generator; existing payment reminder timing/wording retained; unique project reminder keys.
- `app/models/Project.php`: overdue project query and completed/cancelled exclusion in pending schedule retrieval.
- `app/controllers/DashboardController.php`: invokes overdue generation alongside existing reminders before loading notifications.
- `app/controllers/NotifyController.php`: resolves notification IDs using the authenticated session user before marking or choosing a deep link.
- `app/controllers/ProjectController.php`: creates deletion notification before deleting the project, inside the existing transaction, so its department can be resolved from the database.
- `database/schema.sql`: fresh-install audience columns and indexes.
- `tests/modules-smoke.php`: includes isolated migration and notification tests.

Created:

- `database/migrations/20261028_004_notification_audience.up.sql`
- `database/migrations/20261028_004_notification_audience.down.sql`
- `tests/problem3b-migrations.php`
- `tests/problem3b-notifications.php`
- `docs/problem3b-department-notifications.md`

Inspected the requested models/controllers, shared department utility, bell/history views, schema, earlier reports/audit and notification tests. `NotificationController` remains the canonical alias of `NotifyController`. No view changes are required; both bell and history already use the model methods now filtered centrally. Existing Stage 1 login/current-role refresh, POST/CSRF checks, admin-only clear and year-aware redirects remain in place.

## Audience rules

| Audience | Visibility |
| --- | --- |
| `legacy` | Every existing authenticated user, preserving historical visibility |
| `global` | Every authenticated user; new unrelated charge/account/general notifications |
| `department` | Current ordinary members of the stored canonical department, plus every admin |
| `admin` | Admins only; missing, deleted or unrecognized project department/entity |
| Unknown audience | Admins only |

New project/project-payment notifications resolve department through the database entity/project join; caller-supplied audience/department values are ignored. Department membership and role are fetched from the current user database record, not browser fields or cached department values. No valid department means no departmental access. Notification department is a creation-time snapshot: changing a project's department does not reassign its existing notifications. Changing a user's department changes which departmental history they can see; read markers remain intact.

Historical rows receive the explicit default `legacy`, with NULL department/reminder key. No history is inferred/reassigned from entity IDs, and existing notification IDs, contents and read markers are preserved. Deleted project links safely return to history. Unauthorized notification IDs return to history without a read marker or entity redirect. Direct project/report authorization remains unchanged, as requested.

## Reminder rules and duplicate control

- End date must be a valid `Y-m-d`, before the PHP application's current date; `Selesai` and `Dibatalkan` are excluded. Missing/zero/invalid dates are skipped. Project data/status is never updated.
- Payment schedules still use `Menunggu`, `claim_date`, amount and project ID. Overdue, today and the next seven days retain their existing actions and Malay payment terminology. Paid schedules and completed/cancelled projects generate no new pending reminders. No claim-submission state is introduced.
- Existing action/entity existence checks preserve the previous once-per-entity/action behavior, including historical reminders. A nullable unique `reminder_key` additionally makes concurrent new project-reminder inserts idempotent. Each payment can have one reminder per existing timing category. Each project has one lifetime overdue reminder, rather than one per dashboard visit or deadline revision.
- The entity/action index supports reminder existence checks. The existing unique read-marker index is retained. No additional audience index is needed for the current shared global/department visibility query.
- Charge reminder generation is retained. Only project message storage is safely bounded to the existing 255-character column.

Reminders require a dashboard application request. They are not independently scheduled; no cron, queue, email or external delivery exists. Dates use the application's PHP timezone and `date()` consistently, not a separate database clock.

## Validation

Run with PHP 7.4.33 and local XAMPP MariaDB using `tests/modules-smoke.php`. All writes occur in a randomly named `cajpemajuan_modules_<hex>_test` database and temporary receipt/session directories. The real front controller serves the isolated database. The suite drops its disposable database afterward and compares client schema/row and receipt fingerprints before/after.

Coverage includes admin, Kejuruteraan, Landskap and unassigned users; different departments; unknown/empty project departments; active overdue, completed, cancelled and undated projects; paid and pending schedules; overdue/today/upcoming/outside-window payment dates. Checks cover lists/counts/recent alignment, individual lookup/read denial, forged request user/department values, mark-all-visible only, legacy/global visibility, deep links and deleted-entity fallback, current membership refresh, CSRF, admin-only clearing, repeat dashboards and unique-key duplicate insertion. Migration down/up/down/up rehearsals verify original notification fields/IDs and read markers are preserved.

The suite also runs Stage 1, Stage 2A (including migration tests), Stage 2B Problem 1, Problem 2 and Stage 3A regressions. PHP lint, JavaScript project-confirmation tests and `git diff --check` are checked separately. Final run: **636 PASS assertions**, exit 0, no PHP warnings/notices/fatal errors; client database schema/row fingerprints and receipt paths/content unchanged. PHP 7.4.33 lint passed for every changed/new PHP file. JavaScript project-confirmation checks and `git diff --check` passed. The initial sandbox prevented local MariaDB access; the suite was run with explicitly approved local database/server access. One intermediate run exposed a test fixture using the wrong PDO connection for a generated ID; that fixture lookup was corrected and the full suite rerun successfully.

## Manual migration on XAMPP/macOS

Do not import `database/schema.sql` over an existing installation. No migration is applied automatically.

1. Pause application traffic/writes and retain the previous deployment artifact. Confirm actual database credentials/name/socket in the deployment configuration. The examples use `caj_pemajuan` and `DB_USER`.
2. Verify Stage 2A migrations and Stage 3A `20261027_003_user_department.up.sql` are already deployed in their documented order. In particular, `users.department` must exist. Do not rerun earlier migrations blindly.
3. Inspect notification columns/indexes and record notification/read counts. If the new columns or indexes already exist, stop and inspect deployment history rather than repeating ALTER.

```sh
/Applications/XAMPP/xamppfiles/bin/mysql -u DB_USER -p caj_pemajuan -e "SHOW COLUMNS FROM users LIKE 'department'; SHOW CREATE TABLE notifications; SHOW CREATE TABLE notification_reads; SELECT COUNT(*) FROM notifications; SELECT COUNT(*) FROM notification_reads;"
```

4. Take a private backup and verify its restore in a separate database. Check every command exit status.

```sh
umask 077
PROBLEM3B_BACKUP_DIR="$HOME/cajpemajuan-backups/problem3b-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$PROBLEM3B_BACKUP_DIR"
/Applications/XAMPP/xamppfiles/bin/mysqldump --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan > "$PROBLEM3B_BACKUP_DIR/before-problem3b.sql"
shasum -a 256 "$PROBLEM3B_BACKUP_DIR/before-problem3b.sql"
```

5. Apply only the additive Problem 3B migration once, then deploy this code while access is paused:

```sh
/Applications/XAMPP/xamppfiles/bin/mysql --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan < database/migrations/20261028_004_notification_audience.up.sql
/Applications/XAMPP/xamppfiles/bin/mysql -u DB_USER -p caj_pemajuan -e "SHOW CREATE TABLE notifications; SELECT audience, COUNT(*) FROM notifications GROUP BY audience; SELECT COUNT(*) FROM notification_reads;"
```

6. Before allowing dashboard requests, verify counts/IDs/content/read markers against the backup: old rows must all be legacy with NULL department/reminder key. Reopen access after schema/code alignment and verification. Use disposable data for reminder generation/browser rehearsal; do not create financial fixtures in client data.

### Rollback limits

MariaDB ALTER implicitly commits. Prefer retaining the additive metadata and fixing forward. The down script preserves IDs and read markers but **irreversibly discards audience snapshots and deduplication keys**; old code also shows all records globally even if columns remain. Rolling back to old code therefore exposes newly restricted notifications. Keep access restricted during rollback and review that disclosure before reopening. Export metadata securely first if column removal is necessary; do not run the down script with Problem 3B code serving requests. Do not restore an old full backup over subsequent client activity.

## Manual browser checklist (disposable installation)

1. Apply the migration, assign ordinary fixture accounts to Kejuruteraan and Landskap, and retain an unassigned account/admin. Seed a legacy notification/read marker and an unrelated global notification.
2. Create active overdue projects in each department, completed/cancelled overdue projects and an undated active project. Add `Menunggu` schedules overdue, today, within seven days and later; include a paid schedule.
3. Open the dashboard as each account. Verify bell count and recent dropdown agree with full history, each department sees its own projects, admin sees all, and unassigned sees only legacy/global. Completed/cancelled projects must generate no new reminders.
4. Revisit dashboard repeatedly: record counts must stay unchanged. Verify project statuses and payment records are unchanged.
5. Read project/payment reminders: authorized users reach existing project finance links with year context. Try another department's ID through both `/notification/read/ID` and `/notify/read/ID`: no marker or project redirect. Missing CSRF must fail.
6. Mark all read as Landskap: only visible records become read. Check Kejuruteraan records remain untouched. Confirm old read markers persist.
7. Change a fixture user's department as admin and revisit history using its existing session; visibility must follow the saved membership. Missing/unknown project department reminders must be admin-only.
8. Verify charge/account/global notifications and their existing redirects. Confirm ordinary users cannot clear global history. Avoid actually clearing historical records during verification.
9. Confirm project/report permissions, payment recording/cancellation and the bell/history presentation remain unchanged.

## Remaining limitations and Git state

No interactive browser session was performed; automated HTTP coverage uses the real router. Existing notification history remains globally visible by explicit compatibility requirement. Reminder history is not withdrawn when a project completes, its deadline changes or a schedule is paid. New generation excludes those closed/paid items. Admin clearing remains the existing explicit destructive operation; clearing removes reminder keys and permits regeneration on a later dashboard request. No project-specific PIC/delegation or independent scheduling was introduced.

The workspace already contained extensive uncommitted changes from earlier stages. Those were retained. This task modifies the seven files listed above and adds the five listed files; all other pre-existing modifications/untracked files remain. Final `git status --short`: 39 modified tracked entries and 23 untracked entries (directories such as `database/migrations/` count as one entry). No commit or push was made. Use `git status --short` to review the combined working tree, rather than treating its full diff as Problem 3B alone.
