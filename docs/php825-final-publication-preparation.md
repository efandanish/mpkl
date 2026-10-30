# Final PHP 8.2.5 verification and original GitHub publication preparation

Date: 30 October 2026. Repository: https://github.com/efandanish/cajpemajuan
Working branch: `fix/security-data-integrity-v1`.

## Outcome

The latest sanitized source, including both F-01 and F-02, passes the full **844-assertion integration suite under exactly PHP 8.2.5**. This equals the PHP 7.4.33 acceptance baseline: **0 added, 0 removed assertions**. Earlier 723-assertion PHP 8.2 results are historical and are superseded by this run. Original GitHub repository retention is the user's selected approach, superseding the earlier recommendation to publish a new repository.

No client records/schema/receipts were changed. No active XAMPP runtime, Apache include, runtime selection, or credentials were changed. No commit, push, remote history rewrite, branch deletion, remote settings change or merge occurred. Only disposable Docker databases and synthetic fixture accounts/financial records/receipts were mutated. Docker Desktop was started and an isolated development stack was created.

## Exact verified runtime and source

- PHP: **8.2.5**, `/usr/local/bin/php`, CLI and `cli-server`, Linux aarch64, NTS (`PHP_ZTS=0`).
- Image: `cajpemajuan-php:8.2.5-arm64`, rebuilt from digest-pinned official PHP and Composer images. New image manifest: `sha256:ccafff3c095b08ba44c17dd1634435eff6cd134c26ba639f0c0791f846b93090`.
- Development PHP container: `cajpemajuan-final825-20261030-php-1`.
- Required extensions verified: ctype, fileinfo, filter, gd, hash, json, mbstring, openssl, PDO, pdo_mysql, session, SPL. GD FreeType/JPEG/PNG also pass. Composer checks all ten locked platform requirements.
- MariaDB: **10.4.27-MariaDB-1:10.4.27+maria~ubu2004**, isolated Docker service `db`, no host database port published.
- Fresh Compose project: `cajpemajuan-final825-20261030`; dedicated `php825-db` and `php825-receipts` volumes, distinct from existing Docker volumes and XAMPP. No old volume was removed or repurposed. A new private password was generated for this disposable stack only.
- Staging listener: **http://127.0.0.1:8082/**. `/__runtime` confirms exact version/SAPI/INI; it is a development diagnostic, not for production exposure.
- Verified source snapshot: `/private/tmp/cajpemajuan-php825-source.4WWc5O`. Exported directly from the reviewed Git index, without `.git`, ignored host configuration, private exports, Docker overrides or client receipts. Runtime configuration and private keys are generated inside the container.
- Runtime source manifest SHA-256: `49b65914ce101bc300706375c47c27ecf102594092e68d46f32cda76385d43e5`. It includes indexed app/public/scripts/tests/database/docker/vendor/Composer files and other non-report indexed files; docs, README and .gitignore are excluded from this runtime-only identity. Later report changes do not invalidate tested application identity. Private evidence manifest records individual indexed blob hashes.

## Fresh validation results

| Check | Result |
| --- | --- |
| Full PHP 8.2.5 integration/migration/HTTP suite | **844 PASS**, exit 0 |
| F-01 financial dashboard regressions | Included, PASS |
| F-02 reconciliation/paid-history regressions | Included, PASS |
| PHP calculator | **40 PASS** |
| Strict compatibility smoke | **33 PASS** |
| Startup smoke | **6 PASS** |
| PHP syntax in sanitized runtime | **273 files**, zero failures |
| Composer validation | PASS; existing empty PSR-4 namespace performance warning remains |
| Composer actual platform requirements | **10 checks PASS** |
| Read-only exact-target setup checker | **37 PASS** |
| JavaScript installment calculator (host Node) | **42 assertions PASS** |
| JavaScript project confirmation (host Node) | Fallback/cancellation/repeated-submit PASS |
| Published staging endpoint additional HTTP smoke | **21 PASS**, including fixture cleanup |
| PHP 7.4.33 fallback calculator/compatibility/startup | **40 / 33 / 6 PASS** |
| Updated helper shell syntax | PASS |
| PHP diagnostics in verification and staging server logs | No PHP warning/notice/deprecation/parse/fatal error, Uncaught or ErrorException markers |

The full suite uses randomized disposable schemas plus separate session/receipt paths, rehearses migrations only there, and drops those schemas afterward. Its before/after baseline fingerprint in Docker refers to the isolated development database, not the actual client database. A separate native read-only transaction fingerprints the actual client schema/rows before and after this work; **the results are identical**. Host receipt paths/content, private configuration and `.php-version` fingerprints are also identical.

Additional HTTP smoke exercised the long-running 8082 endpoint itself: synthetic admin and ordinary-user login; session continuity; both roles' dashboard/charge/project/notification pages; notification visibility; multipart PDF receipt upload; stored paid status/file; matching dashboard collection; denied ordinary-user cancellation; admin cancellation and temporary receipt deletion; logout invalidation. Synthetic accounts and records were removed afterward. No fixture account password/session token is published. The wider 844 suite covers departmental access, payment management, CSRF and error cases. No graphical browser or SMTP delivery acceptance is claimed for this run.

Private evidence: `/private/tmp/cajpemajuan-final825-evidence/` (mode 0700). Includes verification/staging/setup logs, source manifests, private environment file (0600), and before/after integrity evidence. Do not commit this directory or print its environment file. Durable Git recovery backup: `${HOME}/cajpemajuan-private-checkpoints/pre-final825-20261030-223433` (0700 directories; 0600 bundle/index/patch files). Prior private SQL/configuration/receipt backup remains intact. Evidence is local, not a production deployment backup.

## Reproduce only in disposable development infrastructure

Use a fresh private environment file, mode 0600, outside the repository. Set `PHP825_DB_PASSWORD` privately and `PHP825_SOURCE_ROOT` to the result of `scripts/php825-stage`. Never use a real deployment password or print expanded Compose configuration. Stage reviewed source changes before running that export: it intentionally snapshots the index, not arbitrary working-tree or ignored files.

With that private env file and an explicitly selected disposable project, run:

```sh
docker compose --env-file /private/path/runtime.env --project-name cajpemajuan-final825-20261030 -f docker/php825/compose.yaml build php
docker compose --env-file /private/path/runtime.env --project-name cajpemajuan-final825-20261030 -f docker/php825/compose.yaml up -d --wait
docker compose --env-file /private/path/runtime.env --project-name cajpemajuan-final825-20261030 -f docker/php825/compose.yaml exec -T php php -d error_reporting=-1 docker/php825/verify.php
docker compose --env-file /private/path/runtime.env --project-name cajpemajuan-final825-20261030 -f docker/php825/compose.yaml exec -T php php scripts/check-setup.php --target-php825
node tests/problem4-installments.js
node tests/stage2a-project-confirmation.js
```

Do not reuse a persistent development database volume with a newly generated password: MariaDB initialization variables do not rotate an existing volume's accounts. Reuse the correct private credential or create a separate disposable Compose project. Stopping this project preserves its volumes; do not prune or remove unrelated volumes.

## Active runtime transition readiness

**Ready: exact-version CLI/development HTTP verification. Not ready: active Apache/FPM activation.** The running application remains at `http://localhost/cajpemajuan/`, Apache **2.4.54 / PHP 7.4.33**. XAMPP Apache and libphp7 are macOS **x86_64**, prefork, non-threaded; Docker PHP is Linux **aarch64**, NTS. Linux binaries/extensions cannot replace those macOS artifacts. The unchanged `.php-version` remains `7.4.33`.

XAMPP already loads `mod_proxy`, `mod_proxy_fcgi`, `mod_proxy_http`, `mod_rewrite` and `php7_module`; it runs as `daemon:daemon`. FPM can communicate across CPU architectures through loopback FastCGI; module ABI compatibility is not established by that. Apache does not manage FPM process startup. See [Apache proxy_fcgi](https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html) and [PHP FPM](https://www.php.net/manual/en/install.fpm.php).

Safest proposed active transition: a separately provisioned native macOS **PHP 8.2.5 FPM** installation with matching extensions, a dedicated pool on `127.0.0.1:9082`, and a narrowly scoped Apache handler for this application's `public/` directory. Native FPM preserves the existing filesystem paths, host MariaDB socket/TCP access, receipts and application URL without a Linux-host path translation or client-data bind mount. Keep all other XAMPP applications and the installed PHP 7.4 module intact. Exact-version native FPM is NOT installed/tested; its build/provisioning and staging rehearsal remain work before an activation request can be approved.

Concrete sequence before activation:

1. Provision exact native FPM separately; verify version, architecture, extensions and INI without modifying XAMPP. Re-run strict tests there against disposable data. Do not assume a current Homebrew formula provides patch 8.2.5.
2. Configure a separate native staging checkout and FPM pool with disposable DB, private keys, receipt storage and sessions. Use a dedicated unprivileged runtime account with reviewed permissions. Do not point staging at client records.
3. Start an independent loopback Apache staging instance on unused port 8083, loading the reviewed modules and separate config/PID/logs (no active XAMPP restart). Retain the `/cajpemajuan/` URL prefix and current rewrites when rehearsing the final URL. Preserve the checkout's root-to-public rewrite behavior and verify generated redirect/asset URLs.
4. Candidate application handler, for the staging `public/` path first, then the actual existing path only after approval:

```apache
ProxyRequests Off
<Directory "/Applications/XAMPP/xamppfiles/htdocs/cajpemajuan/public">
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9082"
    </FilesMatch>
</Directory>
```

This is the exact proposed handler change, NOT an installed/validated production include. Its precedence against XAMPP's current PHP handler must be tested in the independent staging instance. Keep FastCGI loopback-only; never expose it publicly. Run `php-fpm -t` and Apache `httpd -t`, verify `fpm-fcgi` via a restricted temporary diagnostic, and exercise login/logout, uploads, F-01/F-02, department notifications, payment management, errors and session permissions. Ensure PHP source is never returned as text if FPM is unavailable.

5. Before requesting activation approval, provide the final tested native FPM paths/pool/INI, exact Apache include diff, staging results, approved maintenance window and private backups of active Apache/PHP configuration. Preserve existing client security keys and all records/receipts; coordinate any required exposed-key rotation separately. A new session directory may require re-login; do not delete existing sessions. Verify the runtime user can read private config and write logs/session/receipts without broadening permissions on client files.
6. Only after explicit approval: enable the reviewed application-only handler and reload/restart Apache using the existing service process. Do not change MariaDB or apply migrations as part of that runtime switch. Use protected read-only verification first; authenticated dashboards can generate reminders, so coordinate those checks rather than treating them as read-only client-data probes.
7. Rollback: disable only the new application handler/include, validate Apache configuration and restore the previous PHP 7.4 handler through the same service process. Keep the old module, binary, INI and session storage installed throughout. Stop only the new FPM pool after traffic returns to 7.4. No database restore or receipt deletion is a runtime rollback step.

PHP 8.2.5 is the requested historical exact patch, not a current-security production recommendation. Active deployment requires an explicit decision about the historical runtime's security posture.

## Sanitized publication and proposed commit

Original remote is retained. See [original repository cleanup and partner migration](github-original-history-cleanup.md). The current development branch still inherits private history; do not create a supposedly publishable clean commit on it.

After a separately approved cleanup, create a development branch **`fix/client-feedback-php82-final` from the cleaned `main`**, export only the reviewed index files, and overlay that complete reviewed tree with its explicit deletion manifest. Preserve the normal PR/review workflow even though no current remote branch protection exists. Proposed commit:

```text
feat: complete caj pemajuan client feedback and PHP 8.2 compatibility
```

Includes application/client-feedback source, F-01/F-02, schema and migrations, tests and fictional fixtures, compatibility adapter, sanitized Docker/development files, setup instructions and reports. Excludes private SQL/accounts/hashes, active config/keys, local overrides, credentials, receipts, runtime logs/caches and temporary DB/evidence files. Final reviewed snapshot: **395 indexed files**, including two new preparation reports; **116 changed paths** staged. Complete indexed blobs, not removed diff lines, are audited. All known private paths are absent from the index and remain ignored on disk. Exact discovered credential literals, password hashes, private-key/common token markers, SQL INSERT records and runtime uploads are absent from the resulting indexed tree. Runtime-source manifest matches the tested source. `git diff --check` and `git diff --cached --check` pass. No unstaged tracked changes or nonignored untracked files remain after staging. No commit object/hash is created in this preparation phase.

## Approval gates

- Authorize the tested native-FPM/Apache staging design and, after its concrete results/configuration are reviewable, explicitly approve the application-only active handler switch during a maintenance window. No current activation approval is implied.
- Approve containment/credential rotation/password-reset measures for already public information, without assuming a Git rewrite revokes secrets.
- Coordinate partner backups and freeze old-history pushes. Separately approve history cleanup in the isolated clone, inspect the result, then separately approve exact branch updates to the ORIGINAL repository. No `--mirror` push, blanket force-push or old-history merge.
- Once cleaned history and runtime/source audit pass, authorize the proposed development commit and PR publication. Do not push current dirty-history branch.
