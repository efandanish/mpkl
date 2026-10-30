# Sistem Akaun Caj Pemajuan

Complete MiniPHP development project for user/department management, the financial dashboard, Projek Pembangunan, Caj Pemajuan, installment schedules, payments/receipts, notifications and historical-year reports.

## Requirements

- PHP **7.4 through 8.2**; verified runtimes are **7.4.33** and **8.2.5**. PHP 8.3+ is rejected by startup. Verify both CLI and web-server versions. `.php-version` retains the existing 7.4.33 fallback; it does not switch Apache.
- PDO/pdo_mysql, mbstring, GD with FreeType/JPEG/PNG, OpenSSL, fileinfo and sessions; MariaDB **10.4** (verified 10.4.27).
- Composer 2 using a supported PHP interpreter, with PHP zip support or an available unzip/7z command for extracting package archives. `vendor/` is installed from the lockfile and is not committed in this development tree. The lockfile resolves for PHP 7.4.33 to retain fallback compatibility; `check-platform-reqs` verifies your actual runtime.
- Apache with mod_rewrite and AllowOverride All. PHP's built-in server below is for local development only.
- Browser access to the CDNs used for Bootstrap, Bootstrap Icons and SweetAlert.

## Fresh installation

1. Clone the repository and run from its root with the intended PHP interpreter:

   ```sh
   php -v
   composer install --no-dev --prefer-dist --no-interaction
   composer check-platform-reqs --no-dev
   ```

   Do not use `--ignore-platform-reqs`. Keep `composer.json` and `composer.lock` together.

2. Copy the configuration template, then generate private local keys WITHOUT printing them:

   ```sh
   cp app/config/config.example.php app/config/config.php
   php -r '$p="app/config/local.php"; if (file_exists($p)) { fwrite(STDERR,"Local configuration already exists; preserve it.\n"); exit(1); } $c=require "app/config/local.example.php"; foreach (["COOKIE_SECRET_KEY","ENCRYPTION_KEY","HASH_KEY"] as $k) { $c[$k]=bin2hex(random_bytes(32)); } umask(0077); if (file_put_contents($p,"<?php\nreturn ".var_export($c,true).";\n")===false) { exit(1); }'
   ```

   Edit the ignored `app/config/local.php` privately with your DB_HOST, DB_NAME, DB_USER and DB_PASS. Use a dedicated database account; do not publish this file or reuse another deployment's keys. SMTP settings can be overridden here if email is configured. Existing installations must preserve their private keys and configuration instead of rerunning initialization. Keep private configuration readable only by the owner and the intended web runtime account; do not solve permissions with world-readable secrets.

3. Create an EMPTY database and import the NEW sanitized export:

   ```sh
   mysql -u DB_USER -p -e "CREATE DATABASE caj_pemajuan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
   mysql --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan < database/caj_pemajuan.sanitized.sql
   ```

   Replace DB_USER and the database name to match local configuration. The export includes all nine application tables, keys, constraints and current migration columns. It contains **no accounts, password hashes, financial records or receipts**. Reference choices are enums/application constants, so no seed rows are needed. `database/schema.sql` is the equivalent structure-only alternative: import **one** of these files, not both. Fresh installations must NOT run the additive migrations afterward. Never import either file over an existing client database.

4. Create the first administrator in a private local terminal:

   ```sh
   php scripts/create-admin.php administrator@example.test "Local Administrator"
   ```

   Substitute your own administrator email/name. The command generates a unique password and displays it once. Store it securely; do not capture the output in logs, commit it or share it. There are no default login credentials. Use the application's user management to create other accounts and assign departments.

5. Ensure the web-server account can write `app/logs`, `app/uploads`, `app/backups` and `public/uploads/receipts`; these empty directories are included with placeholder files. Configure uploads for at least 5 MB and post requests for at least 8 MB. Keep errors logged with `E_ALL`; remove obsolete `track_errors` from a PHP 8 INI. Do not replace a PHP runtime or Apache module by copying binaries from a different OS/architecture.

6. Check configuration:

   ```sh
   php scripts/check-setup.php
   # Exact-version check when using the requested upgrade runtime:
   php scripts/check-setup.php --target-php825
   ```

7. Serve the application:

   - XAMPP: place under `htdocs`, enable the rewrite/override settings and visit `http://localhost/<folder>/`. Root and public `.htaccess` files are both required.
   - Apache vhost: point DocumentRoot to `public/` and enable its `.htaccess` rules.
   - Local development only:

     ```sh
     php -S 127.0.0.1:8080 -t public scripts/router.php
     ```

     Visit `http://127.0.0.1:8080/`. Selecting a terminal PHP binary does not change XAMPP's active PHP handler.

## Existing database upgrades

Keep the existing records, private configuration and receipts. Follow [database/migrations/README.md](database/migrations/README.md) to inspect and apply only missing migrations after a private backup and disposable restore rehearsal. There is no automatic startup migration. Do not execute every migration blindly or use the fresh installation export as an upgrade script.

## Included behavior

- Admin and ordinary-user authentication, session/CSRF protections, department-aware user management and permissions. Ordinary users can create charge/project records; saved financial corrections and payment mutations require admin access.
- Separate assessed charges, actual collections and payment-year expenditure on the dashboard (F-01). Opening/closing balances remain explicitly unverified/unavailable until the required accounting policy exists.
- Project schedules, expenses, variations, payment recording/cancellation, charge installment payments, validated receipt numbers/dates/uploads and history protections.
- Automatic new three-installment principal/deadline calculations and exact-cent reconciliation on saved financial edits (F-02). An inconsistent edit is rejected atomically; paid history is protected. Interest remains reference-only, not an automatically posted obligation.
- Department notifications, bell/history access controls and reminder deduplication. Reminders are generated through dashboard access; no unattended scheduler or individual PIC assignment is included.
- Explicit historical charge years, year navigation/reports and undated project handling.
- PHP 8.2 compatibility for controller properties, nullable strings and the CAPTCHA adapter.

## Troubleshooting and verification

For a fresh disposable installation, log in as admin; create a user and project, create a charge, inspect the calculated installments, record/cancel a payment with a test receipt, compare dashboard assessed/collected/spent totals, review department notifications, historical-year lists and logout. Repeat relevant access checks with an ordinary user. Never create these verification records in client data.

If login fails, verify DB settings and create the local administrator. For missing routes, check mod_rewrite, AllowOverride and the document root. For a blank/error page, check PHP version/extensions, Composer installation, `app/logs/log.txt` and the web-server error log. Receipt failures usually require reviewing upload limits and directory ownership. Real SMTP delivery needs separately configured credentials.

The application source retains the client-feedback implementation and PHP 8.2.5 compatibility. This development repository includes tests, fixtures, reports and isolated Docker tools. Private exports/configuration, receipts, logs and generated dependencies remain excluded. It has fresh Git history; the separate original Caj Pemajuan repository and its existing historical exposure are unchanged. Historical reports describe earlier snapshots and are not claims that this repository contains private deployment data.

Built on miniPHP; retain [LICENSE](LICENSE). Third-party dependencies retain their licenses when Composer installs them.

## Development verification

`tests/` contains the PHP integration/migration/HTTP regressions, strict compatibility and startup smoke checks, calculator fixtures and JavaScript checks. Run financial/receipt fixtures ONLY against disposable databases and receipt storage. The latest baseline is **844 integration assertions**, including F-01/F-02. Requirements 3 and 4 retain documented client policy boundaries for individual PIC/claim semantics, interest and form-issuance timing; automated implementation results do not imply unconditional client acceptance.

The digest-pinned `docker/php825/` environment verifies exactly PHP **8.2.5** on Linux arm64 with MariaDB **10.4.27**, separate from XAMPP. Docker Desktop must run, and Node is needed for JavaScript CLI tests. The Docker source helper exports the reviewed Git index, so stage reviewed changes before refreshing. Container-generated dependencies stay in `/workspace`; no host `vendor/` or private configuration is copied.

From a clone, prepare a private environment file outside Git without printing its generated password:

```sh
umask 077
mkdir -p "$HOME/.config/mpkl"
chmod 700 "$HOME/.config/mpkl"
MPKL_PRIVATE_ENV="$HOME/.config/mpkl/php825.env"
PHP825_SOURCE_ROOT="$(./scripts/php825-stage)"
php -r '$p=$argv[1]; if (file_exists($p)) { fwrite(STDERR,"Private environment already exists; preserve its credentials.\n"); exit(1); } umask(0077); if (file_put_contents($p,"PHP825_SOURCE_ROOT=".$argv[2]."\nPHP825_DB_PASSWORD=".bin2hex(random_bytes(32))."\n")===false) { exit(1); }' "$MPKL_PRIVATE_ENV" "$PHP825_SOURCE_ROOT"
docker compose --env-file "$MPKL_PRIVATE_ENV" -f docker/php825/compose.yaml build php
docker compose --env-file "$MPKL_PRIVATE_ENV" -f docker/php825/compose.yaml up -d --wait db
docker compose --env-file "$MPKL_PRIVATE_ENV" -f docker/php825/compose.yaml run --rm --no-deps -T php php docker/php825/verify.php
node tests/problem4-installments.js
node tests/stage2a-project-confirmation.js
```

Keep using the same private password for an initialized volume; replacing an environment value does not rotate MariaDB credentials. To refresh source, rerun `scripts/php825-stage` and update only `PHP825_SOURCE_ROOT` in that private file. For an independent parallel rehearsal use a different Compose project name and fresh volumes; never remove another project's volumes.

Optional local HTTP endpoint: `docker compose --env-file "$MPKL_PRIVATE_ENV" -f docker/php825/compose.yaml up -d --wait`, then visit `http://127.0.0.1:8082/`. Choose an unused loopback port in a reviewed local override if 8082 is occupied. This PHP built-in server is for development, not active production deployment. Never copy Linux binaries into macOS XAMPP or replace Apache modules blindly. The historical exact patch pin is for compatibility reproduction, not a current-security production recommendation.

Run `php -d error_reporting=-1 tests/modules-smoke.php` natively only when private local configuration targets a disposable database; the suite creates/drops randomized schemas and writes fixture receipts. Under PHP 7.4, add `-d track_errors=0` to avoid the obsolete host INI directive. `tests/problem4-calculator.php`, `tests/compatibility-smoke.php` and `tests/startup-smoke.php` provide additional checks. Never run database smoke scripts against client records.

Implementation and audit history is in [docs/](docs/), with current publication results in [docs/mpkl-development-publication.md](docs/mpkl-development-publication.md). Audit copies omit private operational snapshots and client integrity fingerprints; originals remain private. Only synthetic financial examples and fictional fixture accounts belong in tests.
