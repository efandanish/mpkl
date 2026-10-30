# PHP 8.2.5 compatibility and isolated runtime verification

Date: 2026-10-30. Project: Sistem Akaun Caj Pemajuan / MiniPHP.

**Latest verification:** see [final PHP 8.2.5 and publication preparation](php825-final-publication-preparation.md): the sanitized source including F-01/F-02 now passes **844 assertions** on exact PHP 8.2.5. The 723-assertion results and older Docker commands below are historical; current commands require a private password and reviewed indexed source export. Active XAMPP remains PHP 7.4.33.

**Historical provisioning status: exact PHP 8.2.5 is provisioned and verified in an isolated native-arm64 Docker environment.** CLI `PHP_VERSION` and the separate web runtime both report `8.2.5`. The full 723-assertion integration suite passed on that runtime after the additional fixes recorded below. XAMPP Apache still uses PHP 7.4.33. No active Apache runtime, client records/schema/receipts, Homebrew PHP or `.php-version` selection was changed. No commit or push was made.

The original preparation findings below are retained as historical context. See **Exact-version provisioning and verification** for the current installation, test results, commands and remaining boundaries.

## Environment inspected

- XAMPP CLI: `/Applications/XAMPP/xamppfiles/bin/php`, PHP **7.4.33**, NTS, x86_64.
- XAMPP Apache: **2.4.54**, x86_64; configured worker user/group `daemon`.
- Host: macOS arm64. Homebrew CLI `/opt/homebrew/bin/php`: **8.5.3**, arm64. The `php@8.4` opt path also resolves to 8.5.3 here; a directory/formula label is not evidence of a runtime version.
- XAMPP INI: `/Applications/XAMPP/xamppfiles/etc/php.ini`.
- Database deployment supplied by the user: MariaDB **10.4.27**. Existing tests use the configured local MariaDB; no database upgrade is proposed.
- At initial preparation Docker Desktop was stopped. During exact-version verification it was started, and the isolated stack below was provisioned. Docker Desktop reports 4.94.0, Engine 29.8.2, linux/arm64.
- Project `.php-version` remains `7.4.33`, intentionally preserving the current CLI selection.
- XAMPP contains and enables `mod_proxy` and `mod_proxy_fcgi`, supporting a separately installed FPM runtime without replacing its PHP module.

## Issues discovered and changes

1. **Explicit PHP rejection.** Composer required `>=7.4 <8.1`; `Startup::check()` and the setup checker also rejected 8.1/8.2. They now permit `>=7.4 <8.3`. This is a compatibility range, not a claim that every version in that range has been runtime-tested. `scripts/check-setup.php --target-php825` additionally fails unless `PHP_VERSION` is exactly `8.2.5`.
2. **Dynamic controller properties.** MiniPHP's `loadModel()` and `loadComponents()` assign arbitrary object properties. PHP 8.2 deprecates creating those properties. A declared `loadedObjects` array plus magic accessors retains model/component access, cached instance identity, aliases, `isset`, and `unset` without creating dynamic properties. No `AllowDynamicProperties` attribute or warning suppression was added.
3. **NULL values passed to string functions.** Nullable charge references, project descriptions/procurement/dates, and expense descriptions were passed directly to `htmlspecialchars()`. PHP 8.1+ deprecates NULL for these internal string parameters; MiniPHP converts unsuppressed errors into exceptions. Affected views now explicitly cast these nullable values to string, preserving their previous empty output. Stored values and financial formulas are unchanged.
4. **CAPTCHA lossy integer conversion.** Locked `gregwar/captcha` 1.1.9 calculates fractional random bounds and glyph/pixel coordinates. Passing these to `mt_rand()`, `imagettftext()`, and `imagecolorat()` can emit PHP 8.1+ deprecations. `CompatibleCaptchaBuilder` extends the retained package and explicitly converts integer-only arguments, preserving its algorithm and fingerprint replay. Use this adapter for application CAPTCHA construction. The active Caj Pemajuan login has no CAPTCHA construction; the retained legacy login template references a `getCaptcha()` API outside the active authentication path. No legacy authentication feature was restored or replaced. Direct construction of the upstream builder remains an upstream compatibility limitation; the smoke test now exercises the application adapter.
5. **Test diagnostics.** HTTP subprocesses now explicitly use `E_ALL` and log errors; integration log inspection also rejects deprecated/parse errors. The compatibility smoke test turns unsuppressed warnings/deprecations into exceptions. Reflection accessibility is changed only on PHP <8.1, where that operation is required.
6. **Existing XAMPP INI warning.** Enabling `E_ALL` on the supplied PHP 7.4 configuration reveals the deprecated `track_errors` directive at startup. Tests use `-d track_errors=0`; the HTTP test process does likewise. No INI was edited. Do not copy this obsolete directive into PHP 8.2's new INI. This test override disables a removed feature, not error reporting.

PHP migration references: [8.2 dynamic properties](https://www.php.net/manual/en/migration82.deprecated.php), [8.1 NULL and lossy integer conversions](https://www.php.net/manual/en/migration81.deprecated.php).

## Files modified in this task

Pre-existing work in the checkout was retained. This list describes only this preparation:

- `composer.json`: widen the PHP requirement; retain the PHP 7.4.33 resolution platform.
- `composer.lock`: regenerated content hash and root PHP constraint only; every package entry is unchanged.
- `app/core/Startup.php`: permit PHP 8.2 and improve version diagnostic.
- `app/core/Controller.php`: declared model/component registry and accessors.
- `app/views/charge/index.php`, `app/views/charge/edit.php`: nullable charge references.
- `app/views/project/index.php`, `app/views/project/edit.php`, `app/views/project/finance.php`: nullable project/expense output.
- `app/views/report/charges.php`: nullable reference output.
- `scripts/check-setup.php`: compatible range and optional exact-target check.
- `tests/compatibility-smoke.php`: strict diagnostics, controller registry checks, CAPTCHA adapter/render/replay checks.
- `tests/modules-smoke.php`: strict child-process diagnostics and nullable rendering coverage.

New files:

- `app/utility/CompatibleCaptchaBuilder.php`.
- `tests/php825-nullable.php` (guarded against non-disposable databases).
- `docs/php825-upgrade.md` (this report).

No database model queries, schema files, migration files, financial calculation logic, production configuration, or package source files were changed by this task.

## Composer and dependency results

`config.platform.php` **exists and remains `7.4.33`**. This intentionally keeps dependency resolution compatible with the retained fallback runtime. It does not select the web runtime and does not prove PHP 8.2 compatibility. Validate the real interpreter with `check-platform-reqs`, which ignores the simulated platform. See [Composer platform configuration](https://getcomposer.org/doc/06-config.md#platform) and [platform checks](https://getcomposer.org/doc/03-cli.md#check-platform-reqs).

| Locked package | Version | PHP declaration | Result |
|---|---|---|---|
| phpmailer/phpmailer | 6.12.0 | >=5.5.0 | Permits 8.2.5; recipient smoke check passes on 7.4. SMTP delivery not exercised. |
| gregwar/captcha | 1.1.9 | >=5.3.0 | Permits installation; runtime float conversions require application adapter above. |
| symfony/finder | 5.4.45 | >=7.2.5 | Permits 8.2.5; no declaration conflict. |
| symfony/deprecation-contracts | 2.5.4 | >=7.1 | Permits 8.2.5. |
| symfony/polyfill-php80 | 1.43.0 | >=7.2 | Permits 8.2.5. |

No package was removed, replaced, upgraded, or downgraded. The CAPTCHA issue and adapter resolution were reported before implementing the adapter. Current upstream CAPTCHA master requires PHP ^8.2, so replacing the old release indiscriminately would sacrifice PHP 7.4 compatibility; it was not selected. [Upstream manifest](https://github.com/Gregwar/Captcha/blob/master/composer.json).

Executed:

- `composer update --lock --no-install --no-scripts --no-plugins --no-audit`: exit 0, no package changes. All `packages` metadata verified identical before/after.
- `composer prohibits php 8.2.5 --locked`: exit 0, no locked package prohibits 8.2.5.
- `composer validate --no-check-publish`: exit 0, existing empty PSR-4 namespace performance warning remains. Strict validation returns a warning status for that pre-existing design; autoloading was not redesigned.
- PHP 7.4 invocation of `composer check-platform-reqs --lock`: exit 0, all 10 requirements pass (PHP plus nine extensions).

Dependency constraints are not runtime proof. Neither SMTP transport, optional CAPTCHA OCR/background-file paths, nor every Symfony Finder operation was runtime-tested on 8.2.5. No security audit is claimed.

## Initial preparation tests and results (historical PHP 7.4 run)

All fixture database mutations used randomly named `cajpemajuan_modules_<hex>_test` databases, with separate session/receipt directories and the real front controller under an isolated HTTP server. Existing migration rehearsals operated only on those disposable schemas. Client schema/row fingerprints and receipt fingerprints were identical before/after; temporary databases were dropped.

| Check | Runtime | Result |
|---|---|---|
| Syntax lint, all 262 PHP files in app/scripts/tests/public/vendor | 7.4.33 | 262 pass; zero syntax errors or diagnostic warnings |
| Compatibility smoke | 7.4.33 | 30 PASS, exit 0 |
| Startup smoke | 7.4.33 | 6 PASS, exit 0; intentional error-handling fixtures behaved as expected |
| Full HTTP/module/migration regressions | 7.4.33 | 641 PASS, exit 0; existing 636 plus five nullable rendering checks |
| Integration HTTP error-log check | 7.4.33 | No PHP warning/notice/deprecation/parse/fatal errors |
| Live schema/row and receipt fingerprints | 7.4.33 | Unchanged |
| Git whitespace check | n/a | `git diff --check` passed |
| Syntax, application, deprecation and runtime tests | **8.2.5** | **NOT RUN — exact executable unavailable** |

Integration coverage includes login/logout and session invalidation, CSRF, authorization/routing, dashboard/year filtering, charge/project CRUD, receipt upload/view/cancellation, payment schedules/cancellations, financial totals/variations/report calculations, department notification visibility/read authorization, legacy notifications, bell/history agreement, and reminder deduplication. The suite also exercises the previous security, financial validation, reporting, and migration regressions.

A supplementary smoke attempt using 8.5.3 and a temporary startup override stopped at PHP 8.4+'s implicit-nullable-parameter deprecation in `Controller::__construct()`. This does not occur in PHP 8.2 and was not treated as a target defect or a target test pass. Production startup continues to reject 8.3+. No global diagnostic mask was loosened to make that attempt pass.

Local logs from this session:

- `/tmp/php825-lint.log`
- `/tmp/php825-compatibility.log`
- `/tmp/php825-startup.log`
- `/tmp/php825-modules-php74.log`
- `/tmp/php825-diagnostic85.log` (failed supplementary newer-runtime attempt)

## Optional native source-build requirements — not executed

The native source-build steps in this section were **not executed**; the Docker approach below was used instead. If choosing a native installation later, install separately; retain `/Applications/XAMPP` intact. A current Homebrew `php@8.2` install does not guarantee patch **8.2.5**. Verify the executable, not the formula name. PHP 8.2.5 is an historical patch release and does not include fixes from later 8.2 patches; the exact pin here follows the requested target.

Use either a verified macOS distribution explicitly identifying PHP 8.2.5, or build the official 8.2.5 source into a separate prefix. For this arm64 host, a separate arm64 CLI/FPM build can communicate with x86_64 XAMPP Apache over loopback. Never copy an arm64 `libphp` or extension into x86_64 XAMPP.

Source requirements:

- Xcode Command Line Tools / compatible C toolchain, `make`, `pkg-config`, tar/xz, and source-compatible libraries.
- libxml2, SQLite3 development files (default CLI build), OpenSSL development files compatible with PHP 8.2.5, zlib, Oniguruma, libpng, libjpeg, FreeType. Use a build environment whose dependency versions are known to compile this historical PHP source; current Homebrew library versions may require a separate compatible dependency prefix.
- Enable CLI and FPM; PDO/mysqlnd and `pdo_mysql`; mbstring; GD with JPEG and FreeType; OpenSSL; fileinfo; session; ctype; filter; hash; JSON; SPL. OpenSSL supports existing encryption/SMTP; FreeType is necessary for CAPTCHA font rendering.
- Do not reuse PHP 7.4 extension binaries or its entire INI. PHP 8.2 extensions must match the interpreter's architecture, ABI, and thread-safety build. Check GD's FreeType support explicitly.

Official [8.2.5 release metadata](https://www.php.net/releases/index.php?json&version=8.2.5) gives SHA-256 for `php-8.2.5.tar.xz`:

```text
800738c359b7f1e67e40c22713d2d90276bc85ba1c21b43d99edd43c254c5f76
```

Example source-build outline, after providing compatible libraries and their pkg-config paths:

```sh
curl --fail --location --output php-8.2.5.tar.xz https://www.php.net/distributions/php-8.2.5.tar.xz
shasum -a 256 php-8.2.5.tar.xz
# Stop unless the checksum matches exactly.
tar -xf php-8.2.5.tar.xz
cd php-8.2.5
./configure --prefix="$HOME/opt/php/8.2.5" \
  --with-config-file-path="$HOME/opt/php/8.2.5/etc" \
  --enable-fpm --enable-opcache --enable-mbstring \
  --with-pdo-mysql=mysqlnd --with-openssl --with-zlib \
  --enable-gd --with-jpeg --with-freetype
make
make test
make install
```

Check every exit status. Install into the separate prefix only. Start with the source's `php.ini-production` in that prefix's `etc/php.ini`, then explicitly configure timezone `Asia/Kuala_Lumpur`, `error_reporting=E_ALL`, `log_errors=On`, a writable error log, and production `display_errors=Off`. Retain file uploads; carry forward the reviewed upload/post size limits, writable upload temp directory, and application memory/time limits. Use a dedicated writable session directory with `session.serialize_handler=php`. Remove obsolete directives such as `track_errors`; do not add blanket exclusions for deprecated warnings. Point `pdo_mysql.default_socket` to the actual MariaDB socket if keeping `DB_HOST=localhost`; alternatively use reviewed TCP credentials. Do not assume the Homebrew default socket matches XAMPP.

Verification before any switch:

```sh
PHP825="$HOME/opt/php/8.2.5/bin/php"
"$PHP825" -r 'if (PHP_VERSION !== "8.2.5") { fwrite(STDERR, "Wrong PHP version\n"); exit(1); } echo PHP_VERSION, "\n";'
"$PHP825" --ini
"$PHP825" -m
"$PHP825" -r 'print_r(gd_info());'
"$PHP825" /path/to/composer.phar check-platform-reqs --lock
"$PHP825" -d error_reporting=-1 tests/compatibility-smoke.php
"$PHP825" -d error_reporting=-1 tests/startup-smoke.php
"$PHP825" -d error_reporting=-1 tests/modules-smoke.php
"$PHP825" scripts/check-setup.php --target-php825
```

Run from the project root. Use a Composer PHAR supporting both runtimes (Composer 2.2 LTS is an option for PHP 7.4); do not rely on its shell shebang to choose PHP. The module suite creates/drops its own disposable DB and checks client fingerprints. For the setup checker, use a separate staging checkout/configuration pointing to an already provisioned isolated database. The checker performs SELECT/metadata inspection only. Repeat syntax lint with the exact PHP 8.2.5 executable. Review HTTP and application error logs with E_ALL; require the full regression suite to pass on the target before approving deployment.

In Antigravity, select the explicit new CLI executable in the relevant PHP validator/debugger/terminal configuration after installation; an IDE/terminal selection does not switch Apache. Keep the existing PHP 7.4 executable available. No IDE setting was changed here.

## Optional native FPM transition procedure — requires separate approval

Do not replace the XAMPP installation or install a different bundled MariaDB. A CLI symlink, PATH change, or `.php-version` edit does not change Apache's loaded PHP. A stock XAMPP installer labelled 8.2.x is not proof of exactly 8.2.5.

For a future native PHP installation, one possible route is separate **PHP 8.2.5 FPM** behind the current Apache. The currently verified deployment is the standalone Docker development server described below; no FPM deployment was installed or tested. [Apache's FastCGI documentation](https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html) describes the handler. This avoids an Apache module ABI/architecture replacement; it still requires configuration review and an approved switch.

1. Obtain approval after exact-runtime CLI and isolated HTTP tests pass. Preserve a private deployment snapshot of application files, `composer.json`/lock/vendor, Apache includes/vhosts, PHP/FPM INIs, and current PHP configuration. Keep the existing PHP 7.4 runtime. Record the current application URL, document root, rewrites, socket, permissions and receipt location. A database backup is operational protection; no database migration is part of this upgrade.
2. Create a separate FPM pool configured to listen only on `127.0.0.1:9082`. Use an OS account with the required project/read/log/session/receipt permissions. Bind a staging vhost only to loopback on an unused port, with a separate staging checkout and isolated DB; avoid two deployments writing to the client DB during rehearsal. Run `php-fpm -t` with the new prefix's explicit pool/config path. Verify its binary reports exactly 8.2.5, and start that FPM process using the separate configuration.
3. Review current `mod_php` handlers and apply the following handler within the specific staging/application vhost so it wins over existing handlers. Keep other XAMPP applications on their existing runtime. Preserve `public/` as the document root and the current front-controller rewrite rules. Example handler only (adapt paths/listener in the full vhost):

   ```apache
   ProxyRequests Off
   <FilesMatch "\.php$">
       SetHandler "proxy:fcgi://127.0.0.1:9082"
   </FilesMatch>
   ```

   `mod_proxy` and `mod_proxy_fcgi` are already enabled in this checkout's XAMPP configuration. Inspect before adding duplicate LoadModule entries. Apache does not launch FPM itself. Protect FPM from public access; ensure PHP source is never served as a static fallback.
4. Run `/Applications/XAMPP/xamppfiles/bin/httpd -t` before any Apache restart. Stop if invalid. After approval, restart only Apache through XAMPP Manager or the deployment's existing Apache service procedure; leave MariaDB running unchanged.
5. Through the staging vhost, use a temporary loopback-only diagnostic that reports only `PHP_VERSION`, `PHP_SAPI` and the loaded INI path; require `8.2.5`, `fpm-fcgi`, and the new INI. Do not publish a full `phpinfo()` page. Remove the diagnostic after verification. Repeat browser login/logout, dashboard, CRUD/uploads, financial reports and bell/history/read-authorization checks on staging fixtures, and review both Apache and FPM logs.
6. During the approved production window, apply the reviewed handler/pool configuration to the production application vhost and verify its runtime with a protected read-only diagnostic. Do not create test client projects/users/payments. Receipt/session permissions must be verified under the FPM account. Separate session storage can require users to log in again; retain existing storage for rollback. Restart/reload as appropriate and monitor errors. No schema migration or notification reassignment is required.

For an Apache-module deployment instead, build PHP against XAMPP's own `apxs`, Apache ABI, x86_64 architecture and appropriate thread-safety model. This is a separate engineering task, not a binary-copy procedure. See [PHP's Apache build instructions](https://www.php.net/manual/en/install.unix.apache2.php).

## Rollback procedure

Before any runtime switch, preparation alone needs no server rollback: Apache is still using the original runtime and the application retains PHP 7.4 compatibility.

If a later approved FPM switch fails:

1. Restrict application traffic during rollback. Restore the saved application-vhost handlers/configuration that routed to XAMPP PHP 7.4.33, run `httpd -t`, then restart Apache through the same service procedure. Stop only the new FPM pool when nothing uses it. Leave MariaDB and existing XAMPP PHP binaries installed.
2. Verify the protected web diagnostic reports the old runtime/INI; restore the old IDE/CLI selection if changed. Restore the prior session configuration where necessary and check login/logout and receipt access.
3. The prepared application can remain deployed on PHP 7.4.33. If application rollback is also needed, restore the exact pre-upgrade deployment snapshot, including lock/vendor and all pre-existing uncommitted changes. Do not use a broad `git reset` or overwrite unrelated work. Re-run the PHP 7.4 smoke/regression checks in isolation.
4. Preserve all subsequent client records and receipts. No schema/data rollback is required by these changes, and restoring an old database backup over newer activity is not part of this procedure. Retain logs for diagnosis and do not reattempt the switch until the exact 8.2.5 failures are resolved.

## Exact-version provisioning and verification

Updated 2026-10-30 after Problem 4. **This section supersedes the earlier missing-runtime limitation.**

### Installation selected and prerequisites

Installed an isolated **linux/arm64** runtime using the Docker Desktop already present on this M1 Mac. Docker Desktop was started; no Homebrew package, system PHP, Apache module or XAMPP installation was replaced. The architecture checks show:

| Component | Observed version / architecture |
|---|---|
| macOS host | arm64 |
| Homebrew PHP | 8.5.3, arm64; retained |
| XAMPP CLI PHP | 7.4.33, NTS, x86_64; retained |
| XAMPP Apache | 2.4.54, x86_64, prefork MPM, non-threaded |
| XAMPP loaded PHP module configuration | `php7_module`, `modules/libphp7.so`, x86_64 |
| Docker Desktop / Engine | 4.94.0 / 29.8.2, linux/arm64 |
| New CLI executable inside container | `/usr/local/bin/php`, **PHP_VERSION = 8.2.5**, aarch64, NTS (`PHP_ZTS=0`) |
| New standalone web runtime | **8.2.5**, `cli-server`, aarch64 |
| Native MariaDB, read-only connection | 10.4.27-MariaDB |
| Isolated MariaDB container | 10.4.27-MariaDB-1:10.4.27+maria~ubu2004, arm64 |

The exact official `php:8.2.5-cli-bullseye` image was available for native arm64. Its executable reported `8.2.5` before tests. No native PHP core source build was necessary. Only the missing GD/PDO MySQL extensions were compiled inside the image using the official helper scripts. The host did not receive a compiler/library installation.

Prerequisites for reuse: running Docker Desktop with Linux arm64 support, Docker Compose, network access for first image build, and free loopback port 8082. Host `tar`, a POSIX shell, and the existing project/vendor files are used. Node is needed only for the separate JavaScript tests.

The base image is pinned to:

```text
php:8.2.5-cli-bullseye@sha256:979521db4cc92c31e8b69bdaeb6185bb41b714db3ad7e1d9d980d97f316d540c
```

MariaDB is pinned to:

```text
mariadb:10.4.27@sha256:0a9aae6f3de4ec173ea77b6df64dd2002135f06ee496eabb08a23be68fdc92e4
```

Composer 2.2.25 is copied as a PHAR from its digest-pinned official image; its PHP executable is not copied. Existing application packages remain installed at their prior locked versions. Official image extension tooling is documented in the [Docker PHP image documentation](https://github.com/docker-library/docs/blob/master/php/README.md).

The initial extension build failed when current Debian security indexes referenced package downloads returning 404, including libpng and FreeType. Both HTTP and HTTPS repository attempts encountered this. The final Dockerfile uses dated **2023-04-15 Debian and Debian-security snapshots**, with Release signature verification retained. `check-valid-until=no` is scoped to these historical repositories, as required for expired snapshot metadata; no host repository configuration or package authentication was disabled. See [Debian snapshot usage](https://snapshot.debian.org/#Usage). This creates a historical verification environment, not a claim that the old PHP patch or operating-system packages contain current security fixes.

### Isolation and reusable files

New files:

- `docker/php825/Dockerfile`: exact PHP image, missing extensions, pinned Composer PHAR.
- `docker/php825/sources.list`: dated build-dependency repositories.
- `docker/php825/php.ini`: E_ALL, error logging, timezone and upload/session settings.
- `docker/php825/compose.yaml`: loopback-only standalone server and private MariaDB service.
- `docker/php825/entrypoint.sh`: exact-version gate and container-local application copy.
- `docker/php825/local.php`: development database/storage override, copied only inside containers.
- `docker/php825/router.php`: standalone router and limited `/__runtime` diagnostic.
- `docker/php825/verify.php`: exact-runtime, extension, PDO, lint, Composer and regression runner.
- `scripts/php825-stage`: sanitized host staging copy under `/private/tmp`.
- `scripts/php825`: exact PHP CLI launcher through Docker Compose.

Docker Desktop initially refused the `/Applications/XAMPP/...` bind mount because that directory was not shared. Rather than editing sharing settings, `php825-stage` creates a private staging directory under `/private/tmp`. It excludes `.git`, `.env`, credential/agent directories, host `app/config/local.php`, logs, uploads, backups and client receipts. Docker mounts this sanitized source read-only at `/source`; each PHP container copies it into its writable `/workspace` and adds the development configuration there. Neither the launcher nor the entrypoint writes a host application configuration file.

The database hostname inside this environment is `db`, not the host MariaDB. Its schema is `cajpemajuan_php825_dev_test`. The service imports the existing fresh-install schema into this **new private database only**. Its simple development root password is confined to the Docker project network; no database port is published. Never reuse these credentials for a deployed database.

The database and standalone development receipts use dedicated Docker volumes:

```text
cajpemajuan-php825_php825-db
cajpemajuan-php825_php825-receipts
```

Development receipts are stored in the container's `public/uploads/receipts` volume, so the existing receipt URLs work and recreating PHP does not discard them. Client receipts were neither copied nor mounted into that volume. The integration suite continues to use its own per-run temporary receipt directory and randomly named test database, dropping only that disposable database afterward.

### Commands to use the installed runtime

From the project root:

```sh
# Docker Desktop must be running. Stage source in an already shared host location.
export PHP825_SOURCE_ROOT="$(./scripts/php825-stage)"

# Rebuild if runtime configuration changes; no system-wide installation occurs.
docker compose -f docker/php825/compose.yaml build php
docker compose -f docker/php825/compose.yaml up -d --wait

./scripts/php825 -v
./scripts/php825 -r 'echo PHP_VERSION, "\n"; if (PHP_VERSION !== "8.2.5") { exit(1); }'
./scripts/php825 -m
./scripts/php825 /usr/local/bin/composer check-platform-reqs --lock
./scripts/php825 docker/php825/verify.php
./scripts/php825 scripts/check-setup.php --target-php825
```

The launcher uses `/usr/local/bin/php` in the container. It does not change shell PATH, `.php-version`, Antigravity's PHP executable, Homebrew links or Apache. If `PHP825_SOURCE_ROOT` is unset, each launcher invocation stages a fresh source snapshot automatically. If it is exported, restage after source changes:

```sh
export PHP825_SOURCE_ROOT="$(./scripts/php825-stage)"
docker compose -f docker/php825/compose.yaml up -d --no-deps php
```

The web service is a snapshot copy rather than a live file-sharing development mount. Refresh it after changes. The named database/receipt volumes preserve development data across recreation; the fresh-install SQL runs only when initializing an empty database volume. It is not a migration mechanism.

Development URL: **http://127.0.0.1:8082/**. Version diagnostic: **http://127.0.0.1:8082/__runtime**. Both are confined to the Mac's loopback interface by the Compose port binding. The diagnostic reports only PHP version, SAPI, architecture and INI paths.

A development administrator, `php825-admin@example.test`, was created only in the isolated database. Its generated password is in the owner-readable `/tmp/php825-dev-admin.txt`; it is not included in this report or source. To create another isolated administrator:

```sh
./scripts/php825 scripts/create-admin.php another-admin@example.test 'Development Admin'
```

Do not run fixture/manual payment tests against `http://localhost/cajpemajuan`; that remains the unchanged XAMPP application.

### Real runtime defects found and fixed

Exact-version testing found issues that PHP 7.4 preparation could not prove absent:

1. **CAPTCHA dynamic property:** `gregwar/captcha` 1.1.9 assigns `$this->background` during rendering, creating a deprecated dynamic property. Added an explicit public `$background` declaration in `CompatibleCaptchaBuilder`, preserving the upstream public property behavior. Vendor files and package versions remain unchanged; no attribute or diagnostic mask was introduced.
2. **Query-string NULL prefix:** `Request::fullUrl()` and `Redirector::to()` passed NULL as `http_build_query()`'s numeric prefix. PHP 8.2 emits a deprecation, and MiniPHP's handler turned a year-filter dashboard request into an error page. Changed the prefix to `''`, preserving query encoding and redirect behavior.
3. **Missing CLI HTTP context:** framework request construction in the transaction regression passed a missing URI to `strpos()` and a missing host to `trim()`. The nullable URI API is retained; the string-operation boundaries now explicitly cast to string. The script-name boundary in `getBaseUrl()` is also cast. Existing host validation remains in place.

Added focused compatibility checks for encoded/year query URLs, query redirects and construction without HTTP context. The integration harness now prints relevant HTTP errors before disposable logs are removed on failure and treats `ErrorException` log entries as failures. This exposed the actual runtime cause instead of leaving only a downstream dashboard assertion.

Files modified during this runtime verification, in addition to the new Docker/launcher files:

- `app/utility/CompatibleCaptchaBuilder.php`
- `app/core/Request.php`
- `app/core/Redirector.php`
- `tests/compatibility-smoke.php`
- `tests/modules-smoke.php`
- `docs/php825-upgrade.md`

No financial formula, charge principal/interest rule, database model query, schema/migration source, authentication permission, package source, or Composer manifest/lock was changed in this verification task. Earlier uncommitted project work remains present.

### Extension, PDO and dependency results

| Required extension | Exact PHP 8.2.5 result |
|---|---|
| pdo | Loaded; native PDO connection succeeds |
| pdo_mysql | Built and loaded; local and isolated MariaDB connections succeed |
| mbstring | Loaded |
| openssl | Loaded |
| fileinfo | Loaded; existing receipt MIME tests pass |
| gd | Built and loaded; FreeType, JPEG and PNG available; CAPTCHA tests pass |

A separate exact-PHP-8.2.5 probe connected to `host.docker.internal:3306`, started a **READ ONLY** transaction, and selected only `VERSION()` and `CURRENT_USER()`. It returned `10.4.27-MariaDB` and `root@localhost`. It did not select or mutate client records. Regression writes were directed exclusively to `db`, the private container.

`config.platform.php` remains `7.4.33` to preserve dependency resolution for the fallback runtime. Composer's real `check-platform-reqs --lock` ran using `/usr/local/bin/php` **8.2.5**, ignoring the simulated platform, and passed all ten checks. `composer validate --no-check-publish` exited 0, retaining the pre-existing empty PSR-4 namespace performance warning. No package is prohibited by the actual platform check. PHPMailer recipient handling and the application CAPTCHA adapter passed the strict runtime smoke test; direct construction of the unadapted legacy CAPTCHA package remains outside the supported application path. SMTP delivery and optional OCR/background-file workflows were not exercised.

### Final exact PHP 8.2.5 tests

Every PHP command in the verification runner used `PHP_BINARY = /usr/local/bin/php`, and the runner refused any version other than exactly `8.2.5`.

| Check | Final result under PHP 8.2.5 |
|---|---|
| Exact CLI version / architecture / thread safety | 8.2.5 / aarch64 / NTS |
| Exact standalone web diagnostic | 8.2.5 / cli-server / aarch64 |
| Required extensions plus GD FreeType/JPEG/PNG | PASS |
| PDO connection to private MariaDB 10.4.27 | PASS |
| PDO read-only connection to native MariaDB 10.4.27 | PASS |
| PHP syntax validation | **269 PHP files; zero errors/diagnostic warnings** |
| Composer validate | PASS, existing PSR-4 performance warning only |
| Composer actual platform requirements | **10 successful requirements** |
| Problem 4 calculator | **40 PASS; exit 0** |
| Compatibility smoke, including new query/CLI checks | **33 PASS; exit 0** |
| Startup smoke | **6 PASS; exit 0** |
| Full HTTP/module/migration regression suite | **723 PASS; exit 0** |
| Exact setup checker `--target-php825` | **38 PASS; exit 0** |
| Runtime warning/deprecation/fatal check | No PHP warning, notice, deprecation, parse or fatal output in the final verification; HTTP logs also checked for ErrorException |
| Disposable database/receipt baseline fingerprints | Unchanged |
| Native client database and receipt fingerprints | Independently compared before/after; unchanged |

The 269-file count includes application, scripts, tests, public PHP, vendor, Docker PHP helpers and the container-only `app/config/local.php`. The full regression suite includes Stage 1 security, Stage 2A schema migration and receipt tests, Stage 2B dashboards, Problem 2 permissions, Stage 3A user departments, Problem 3B notification migrations/routing and Problem 4 HTTP persistence/tampering checks. Login/logout, charge/project CRUD, receipt upload/cancellation, reports, principal calculations and notification read authorization are covered.

The initial failed runs described above are diagnostic findings, not passing results. A run that passed HTTP assertions but emitted CLI NULL-conversion deprecations was also rejected; only the subsequent clean complete runner was accepted. No PHP warnings were globally disabled to produce these results. `display_errors=Off` keeps errors out of pages while E_ALL and error logging remain enabled; the verification runner captures subprocess errors, and the application handler logs HTTP exceptions.

Additional regression checks on the retained host PHP 7.4.33: **33 compatibility checks and 6 startup checks passed** after the framework/adapter fixes. Node's Problem 4 tests passed **42 assertions**, and the existing project confirmation checks passed. JavaScript has no PHP interpreter dependency.

Logs:

```text
/tmp/php825-exact-verification.log
/tmp/php825-exact-setup.log
/tmp/php825-host-pdo.log
/tmp/php825-host-before.json
/tmp/php825-host-after.json
/tmp/php825-fixed-compat-php74.log
/tmp/php825-fixed-startup-php74.log
/tmp/php825-image-build.log
/tmp/php825-stack-final.log
```

### Safest current transition and rollback

Use the verified standalone server at `127.0.0.1:8082` for development. It has its own PHP 8.2.5 runtime, private database and receipt storage. XAMPP still serves the original URL with Apache 2.4.54 / PHP 7.4.33, as confirmed by the final read-only response headers.

Do **not** copy this image's executable/extensions into XAMPP or replace `libphp7.so`: these are Linux aarch64 NTS artifacts, whereas XAMPP's Apache/module are macOS x86_64. Even though XAMPP currently uses non-threaded prefork, that does not establish ABI/platform compatibility with an arbitrary PHP module. A native Apache module would require an appropriately built macOS x86_64 PHP 8.2.5 module matched to XAMPP's own Apache/APXS and thread-safety configuration; none was built.

A later FPM-based Apache transition would require a separately provisioned exact-version FPM runtime, loopback-only FastCGI connectivity and validated SCRIPT_FILENAME/path mapping. Host `/Applications/...` filenames do not exist at the container's `/workspace/...` path; do not blindly apply the earlier native FPM handler example to a container. No FPM/Apache deployment was validated here. Keep that transition as a separately approved task after the standalone environment is accepted.

To stop only this development stack while retaining its data:

```sh
# In a fresh shell, create a valid staging path for Compose interpolation if needed.
export PHP825_SOURCE_ROOT="$(./scripts/php825-stage)"
docker compose -f docker/php825/compose.yaml stop php db
```

Stopping preserves the database/receipt volumes and installed image. No prune, volume removal, system-wide uninstall, database restoration or receipt deletion is needed. Restart with `up -d --wait` using the commands above. The private staging directories can be retained; none is used by XAMPP.

There is no production-runtime rollback to perform: active XAMPP PHP, Apache configuration, MariaDB configuration and Homebrew PHP were never switched. If the new code fixes must be reverted, restore just the listed files from a pre-task copy while preserving all unrelated uncommitted work; do not reset the whole checkout or restore the client database.

### Remaining boundaries

- Exact-version CLI and standalone HTTP verification is complete. **PHP 8.2.5 under the existing XAMPP Apache module is not installed or tested.**
- The isolated runtime is Linux arm64; a native macOS PHP build could have different OS/library behavior. No native source build was attempted.
- This historical patch/image is for exact-version development verification; it is not a current-security production upgrade recommendation.
- Graphical browser acceptance and real SMTP delivery remain manual checks. Automated HTTP tests used the real router and covered the requested regressions.
- Docker Desktop must be running. If port 8082 is occupied, choose another loopback host port in Compose before starting; do not change XAMPP's port.
- Source snapshots require explicit refresh. Client data is intentionally absent from the development database.

No blocker remains for the requested isolated exact-version verification. Any active Apache transition still requires a separate instruction and approval.

### Final installed-image and host-integrity evidence

The running development PHP image was inspected after the completed verification:

```text
Image: cajpemajuan-php:8.2.5-arm64
Image ID: sha256:06f0a773e16f5224dcdbbce395f59d0c139c8338538f6b33506114d7145a5d70
Executable: /usr/local/bin/php
PHP_VERSION: 8.2.5
Architecture: aarch64
PHP_ZTS: 0
Published listener: 127.0.0.1:8082 -> container 8082
Database: healthy; no published host database port
```

`cmp /tmp/php825-host-before.json /tmp/php825-host-after.json` exited **0**. Independently captured native client fingerprints, identical before and after:

```text
Database version: 10.4.27-MariaDB
Client schema: caj_pemajuan
Schema/rows SHA-256: [private integrity fingerprint omitted]
Receipt paths/content SHA-256: [private integrity fingerprint omitted]
```

Final shell syntax checks and `git diff --check` passed. `.php-version` still contains `7.4.33`. Both development containers remain available; no XAMPP runtime switch, commit or push was performed.
