# Module refactor handover

Completed locally on 2026-10-11. No commit or push was made. The starting working tree was clean.

## Architecture

The dashboard now renders its financial summary cards only, with the existing year selector and summary calculations. Charge, project and user tables/forms use standalone views under their module directories. The shared cajpemajuan layout remains in use; its footer no longer includes functional modals or queries the User model.

ModuleController adds only authenticated/admin guards and shared rendering with recent notifications and unread counts. Existing model methods and POST workflows remain in the original controllers. ChargeController and ProjectController allow authenticated GET list/detail pages, with admin authorization for creation and mutation. Ordinary users see read-only detail/finance/payment pages. UserController remains admin-only, including direct access to create/edit routes.

Charge/payment/{id} has an intentional compatibility distinction: GET takes a charge ID to render its payment schedule; POST continues to take an installment/payment ID, as the original forms did. Existing payment/cancellation URLs continue to work. Successful financial mutations return to the relevant payment/finance page. Lists retain search, filters and report links; their year selectors now submit to their own modules.

NotificationController inherits NotifyController so the canonical /notification route and legacy /notify links both work. Reading a notification remains POST, marks it read, then resolves a verified target. Payment and schedule notifications resolve to their parent charge/project. Deleted, missing, unknown and null targets fall back to notification history. User targets are restricted to admins. New charge/user creation notifications now retain the created record ID; old null targets safely fall back.

No database schema, existing records, authentication implementation, dependency package versions, or report controller/templates were changed. Bootstrap 5.3.3 remains the only Bootstrap loaded by the active application/auth layouts. Existing unused legacy assets were retained.

The existing application explicitly blocked PHP 7.4. Startup checks, Composer platform metadata, .php-version, setup documentation and CI were updated to permit PHP 7.4 and retain PHP 8.0 support. Composer regenerated its tracked installed/platform metadata. Dependency versions are unchanged.

## Directory structure

```text
app/controllers/
  ModuleController.php
  NotificationController.php
  ChargeController.php
  ProjectController.php
  UserController.php
  DashboardController.php
  NotifyController.php
  ReportController.php
app/views/
  dashboard/
    index.php
    partials/summary-cards.php
  charge/
    index.php
    create.php
    edit.php
    payment.php
  project/
    index.php
    create.php
    edit.php
    finance.php
  user/
    index.php
    create.php
    edit.php
    profile.php             # pre-existing legacy view retained
  notification/index.php
  report/
    charges.php
    projects.php
  layout/cajpemajuan/
    header.php
    sidebar.php
    topbar.php
    footer.php
tests/modules-smoke.php
docs/module-refactor.md
```

## File inventory

### Created

- `app/controllers/ModuleController.php`
- `app/controllers/NotificationController.php`
- `app/views/charge/create.php`
- `app/views/charge/edit.php`
- `app/views/charge/index.php`
- `app/views/charge/payment.php`
- `app/views/project/create.php`
- `app/views/project/finance.php`
- `app/views/project/index.php`
- `app/views/user/create.php`
- `app/views/user/edit.php`
- `app/views/user/index.php`
- `docs/module-refactor.md`
- `tests/modules-smoke.php`

### Modified

- `.github/workflows/php80.yml`
- `.php-version`
- `README.md`
- `app/controllers/ChargeController.php`
- `app/controllers/DashboardController.php`
- `app/controllers/NotifyController.php`
- `app/controllers/ProjectController.php`
- `app/controllers/UserController.php`
- `app/core/Startup.php`
- `app/models/Notification.php`
- `app/views/dashboard/index.php`
- `app/views/layout/cajpemajuan/footer.php`
- `app/views/layout/cajpemajuan/sidebar.php`
- `app/views/layout/cajpemajuan/topbar.php`
- `app/views/notification/index.php`
- `app/views/project/edit.php`
- `composer.json`
- `composer.lock`
- `public/css/app.css`
- `public/css/project-finance.css`
- `public/js/dashboard.js`
- `scripts/check-setup.php`
- `vendor/composer/installed.php`
- `vendor/composer/platform_check.php`

### Removed after migration and reference checks

- `app/views/dashboard/partials/add-development-charge-modal.php`
- `app/views/dashboard/partials/add-project-modal.php`
- `app/views/dashboard/partials/add-user-modal.php`
- `app/views/dashboard/partials/charge-payment-modal.php`
- `app/views/dashboard/partials/development-charge-table.php`
- `app/views/dashboard/partials/edit-development-charge-modal.php`
- `app/views/dashboard/partials/edit-project-modal.php`
- `app/views/dashboard/partials/edit-user-modal.php`
- `app/views/dashboard/partials/manage-user-modal.php`
- `app/views/dashboard/partials/project-table.php`

## Routes and permissions

All paths below are relative to /cajpemajuan. Default index actions deliberately omit /index.

| GET page | Access |
| --- | --- |
| /dashboard | Authenticated; summary only |
| /charge | Authenticated |
| /charge/create | Admin |
| /charge/edit/{id} | Authenticated; ordinary users read-only |
| /charge/payment/{id} | Authenticated; ordinary users read-only |
| /project | Authenticated |
| /project/create | Admin |
| /project/edit/{id} | Authenticated; ordinary users read-only |
| /project/finance/{id} | Authenticated; ordinary users read-only |
| /notification | Authenticated; history |
| /notify | Retained compatibility route |
| /user | Admin |
| /user/create | Admin |
| /user/edit/{id} | Admin |
| /report/charges | Existing authenticated report route retained |
| /report/projects | Existing authenticated report route retained |

Existing create/update/delete/payment/expense/schedule/variation POST actions remain in use. Notification read, markAllRead and clearAll actions are also available under /notification and /notify. Project cancellation now explicitly requires POST, matching the existing form/JS submission.

## Validation completed

- PHP 7.4.33 syntax checks: all 29 changed/new PHP files passed.
- Node syntax checks: dashboard.js, project-finance.js, table-filters.js and the project edit inline script passed.
- Composer validation and PHP 7.4 platform checks passed; Composer reports the existing empty PSR-4 namespace performance warning.
- Existing compatibility-smoke and startup-smoke suites passed on PHP 7.4.33.
- Read-only setup check passed, including all database tables and administrator presence.
- Real HTTP integration suite passed on PHP 7.4.33 against a temporary isolated database, with no PHP warnings/notices/fatal errors in the server log.
- HTTP coverage: admin/normal login and logout; anonymous redirects; module pages for both roles; admin-only user pages and create pages; mutation denial for ordinary users; normal-user navigation; module year selector target; missing-record fallbacks.
- Charge coverage: create three installments, edit, receipt upload/link, payment date/status, cancellation and receipt deletion, paid-installment/payment-plan locking, pending-installment editing, deletion, and parent-charge redirects using different payment/charge IDs.
- Project coverage: create/edit, schedules, scheduled payment, expenditure totals, cancellation/restored schedule, manual expenses, variations, schedule deletion, project deletion, and detail/finance GET rendering for both roles.
- User coverage: create, duplicate-email validation during create/edit, optional password preservation/update, role update, self-deletion prevention, and deletion of another user.
- Notification coverage: verified charge/project/user targets; payment/schedule parent resolution; admin-only user targets; deleted/missing/null/unknown fallbacks; per-user mark-read records; mark-all and unread count reset; canonical and legacy history routes.
- Charge/project report routes returned rendered tables with year/status/department/project-status filters.
- Searches confirmed no removed modal IDs or partial includes remain in app/public/tests. git diff --check passed.
- Tests removed their temporary database, session files, server/router fixtures and uploaded test receipt. Existing application records were untouched.

Run the HTTP suite with `php tests/modules-smoke.php` under PHP 7.4 or 8.0. It requires local database credentials with permission to create/drop a temporary database and permission to start a loopback HTTP server. CI now includes this suite in its PHP 7.4/8.0 matrix; no remote CI run was triggered.

## Remaining technical debt and validation limits

These pre-existing behaviors were deliberately preserved rather than changing financial rules during a navigation refactor:

- Dashboard carried-forward and expenditure values are placeholders (zero); collection uses the existing charge-total calculation.
- Project list expenditure is displayed as zero and its payment-status data attribute remains hardcoded to Belum Dibayar. Existing project search/status/department/payment filters and print parameters remain wired as before; the payment-status filter inherits that data limitation.
- Project finance Kontrak Terkini uses meeting_approval_value with an SST fallback. The old modal calculated SST plus variation total and then overwrote it with approved value. Variations remain recorded and displayed, but do not change this preserved contract calculation.
- Project update replaces schedules when schedule arrays are submitted. This existing behavior remains and deserves a separate review for paid schedule/expense linkage.
- The shared dashboard.js name remains even though its preserved confirmation, installment, print and sidebar handlers are used by module pages. Splitting it further was unnecessary for this migration.
- Legacy framework views/assets and earlier PHP 8 verification documentation remain; this handover describes the current runtime support and current tests.

No requested working behavior was identified as lost. The intentional changes are module navigation, standalone/read-only detail views, admin-only presentation, notification destinations and project cancellation requiring POST. Browser visual layout, interactive confirmation dialogs, browser print output and actual receipt-download behavior were not manually exercised; syntax, HTTP rendering, upload/file lifecycle and receipt-link generation were verified. PHP 8.0 is configured in CI but was not executed locally because an 8.0 binary was unavailable.

## Git status

Created: 14. Modified: 24. Removed: 10. All changes are unstaged.

```text
 M .github/workflows/php80.yml
 M .php-version
 M README.md
 M app/controllers/ChargeController.php
 M app/controllers/DashboardController.php
 M app/controllers/NotifyController.php
 M app/controllers/ProjectController.php
 M app/controllers/UserController.php
 M app/core/Startup.php
 M app/models/Notification.php
 M app/views/dashboard/index.php
 D app/views/dashboard/partials/add-development-charge-modal.php
 D app/views/dashboard/partials/add-project-modal.php
 D app/views/dashboard/partials/add-user-modal.php
 D app/views/dashboard/partials/charge-payment-modal.php
 D app/views/dashboard/partials/development-charge-table.php
 D app/views/dashboard/partials/edit-development-charge-modal.php
 D app/views/dashboard/partials/edit-project-modal.php
 D app/views/dashboard/partials/edit-user-modal.php
 D app/views/dashboard/partials/manage-user-modal.php
 D app/views/dashboard/partials/project-table.php
 M app/views/layout/cajpemajuan/footer.php
 M app/views/layout/cajpemajuan/sidebar.php
 M app/views/layout/cajpemajuan/topbar.php
 M app/views/notification/index.php
 M app/views/project/edit.php
 M composer.json
 M composer.lock
 M public/css/app.css
 M public/css/project-finance.css
 M public/js/dashboard.js
 M scripts/check-setup.php
 M vendor/composer/installed.php
 M vendor/composer/platform_check.php
?? app/controllers/ModuleController.php
?? app/controllers/NotificationController.php
?? app/views/charge/create.php
?? app/views/charge/edit.php
?? app/views/charge/index.php
?? app/views/charge/payment.php
?? app/views/project/create.php
?? app/views/project/finance.php
?? app/views/project/index.php
?? app/views/user/create.php
?? app/views/user/edit.php
?? app/views/user/index.php
?? docs/module-refactor.md
?? tests/modules-smoke.php
```
