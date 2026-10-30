# PHP 8.0 verification results

Checked locally on 11 October 2026 using an isolated **PHP 8.0.30** runtime and MariaDB 10.4.27. No global PHP or XAMPP configuration was changed.

## Passed checks

- Composer targets PHP 8.0.30 with an application requirement of `>=8.0 <8.1`. A clean dependency installation and platform checks passed using PHP 8.0.30. Composer validates the manifest/lock file, with the existing empty PSR-4 namespace prefix reported as a performance warning.
- All 242 PHP files in app, public, scripts, tests, and vendor passed syntax checks on PHP 8.0.30. A PHPCompatibility scan against 8.0 reported no errors; PHPMailer has two guarded references to deprecated APIs which the checker reports as warnings.
- Compatibility smoke tests passed for URL roots, renamed folders, custom ports, PDO binding types, error pages, mail dependency loading, and CAPTCHA rendering.
- Startup regression tests passed for missing Composer dependencies, missing AuthController/ErrorsController, failed logging/templates, and PHP error-mask handling.
- A clean schema import, administrator creation, all controller/extension/directory/table setup checks, and transactional database smoke tests passed in a temporary clone and database.
- Database tests covered user/password storage, projects, charges, payment schedules, recording/cancelling payments, totals, variations, notifications, and read tracking.
- HTTP tests on PHP 8.0.30 passed for the home/login pages, static assets, unknown-route 404, administrator login, populated dashboard, project edit page, notifications, populated reports, logout, normal-user creation/login, administrator-only route restrictions, receipt upload/download, and payment cancellation.
- Temporarily removing AuthController, ErrorsController, vendor/autoload.php, or Startup.php in the test clone produced visible HTTP 500 setup messages. Restoring them restored the login page.
- Synthetic log/template failures produced a visible standalone HTTP 500 page. The login page rendered with a deliberately missing database; attempting to log in then produced a visible 500 error page.
- PHP 7.4 and PHP 8.2 were rejected by the runtime guard in CLI checks. The PHP 8.2 guard check used `-n` to bypass the old XAMPP php.ini.
- JavaScript syntax, workflow YAML syntax, and Git diff whitespace checks passed.

## Blank-page findings

The original default route referenced a missing LoginController. Error rendering depended on a missing ErrorsController, and the old error handler required a fifth callback argument which PHP 8 does not pass. Error reporting was suppressed, and a logging or error-template failure could break error handling itself. The default route now loads AuthController, ErrorsController is present, the callback accepts PHP 8's arguments, and independent startup/fallback diagnostics are available.

The local XAMPP php.ini also contains `track_errors=On`. Testing its PHP 8 executable with this old configuration produced an engine startup failure before project code ran. PHP 8 requires an appropriate php.ini without this removed directive. The global file was not edited. The friend's exact failure cannot be confirmed without their runtime/configuration or error log.

## Limits and environment

The temporary PHP 8.0.30 runtime was built from the official PHP source using OpenSSL 1.1.1w, with required application extensions enabled. PCRE JIT was disabled in its temporary php.ini because this macOS environment restricts executable-memory allocation. These build tools, libraries, and settings live under /tmp and do not change the system PHP.

SMTP delivery, external CDN availability, and every possible browser interaction were not exhaustively tested. The GitHub Actions workflow is configured for PHP 8.0 and fresh-install checks but has not run remotely here. Old framework demo models/views still require their separate demo tables/controllers; they are not part of the active application's schema.

All test writes and uploads used a temporary clone and database. The existing application's database and uploads were not modified. Changes remain local until committed and pushed, including all new controller/startup/setup files.
