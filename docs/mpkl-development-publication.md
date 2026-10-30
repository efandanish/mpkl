# Complete development publication to mpkl

Destination: https://github.com/efandanish/mpkl.git. Branch: `main`.

This repository is a fresh-history export of the Caj Pemajuan development project. It includes the application, routing/assets, Composer lockfile, schema/migrations, structure-only sanitized SQL export, tests/fixtures, development scripts, PHP 8.2.5 Docker setup and implementation/acceptance reports. It contains no original Git history, active configuration, populated dumps, live account records/hashes, client transactions, receipt files, logs, backups, caches or generated vendor dependencies.

Historical implementation and acceptance reports remain evidence of their dated snapshots. Original operational account/notification distributions, personal home paths and client integrity fingerprints have been omitted from publication copies. Earlier publication instructions referring to the original Caj Pemajuan repository are historical, not instructions to import that ancestry here. The original project and GitHub repository are preserved separately.

All eight requirements are represented in the implementation/reports and exercised where implemented. The final acceptance matrix remains **six PASS, two PARTIAL**: individual PIC/claim lifecycle, unattended notification generation, approved interest treatment and first-installment/form-issuance policy require client decisions. F-01 dashboard and F-02 principal reconciliation corrections are included. Do not describe partial policy semantics as unconditional acceptance.

The Docker setup installs locked dependencies inside the container when vendor is absent; its image is separately named `mpkl-php:8.2.5-arm64`. No existing XAMPP/PHP runtime is switched. Verification uses a separate Compose project/database and temporary receipt/session directories only.

## Fresh verification results

- Exact runtime: PHP **8.2.5**, CLI, Linux aarch64, NTS; GD FreeType/JPEG/PNG and required PDO/MySQL, mbstring, OpenSSL and fileinfo extensions pass.
- Isolated database: **MariaDB 10.4.27**, separate disposable Docker service, no published DB port.
- Full integration/migration/HTTP suite: **844 PASS**, including F-01/F-02; unchanged assertion baseline.
- PHP calculator: **40 PASS**; strict compatibility: **33 PASS**; startup: **6 PASS**.
- PHP syntax: **273 files**, zero failures, including dependencies freshly installed inside the container.
- Composer validation and all **10 actual platform requirements** pass; the existing empty-namespace performance warning remains.
- JavaScript installment calculator: **42 assertions PASS**; project-confirmation fallback, cancellation and repeated submission pass.
- Sanitized SQL and canonical schema both import into fresh disposable databases as **nine empty tables** with identical indexes/constraints. Required migrations are exercised by the full suite; no client migration or data changes occur.
- Final verification log: no PHP warnings, notices, deprecations, parse/fatal errors, Uncaught or ErrorException markers.

An initial verification attempt hit a Docker initialization race: a socket-based ping reported the temporary initialization server healthy before TCP was available. The export's healthcheck now requires TCP readiness; the complete rerun passed. Docker also installs archive extraction support and generates locked dependencies inside the container, so a fresh clone does not need committed vendor files.

The publication tree contains **237 files**. Its reviewed source excludes original Git objects, private data/configuration, receipts, logs, backups and generated dependencies. Harmless legacy trailing whitespace/indentation was normalized only in the separate export, with source tests rerun afterward. Client-data observations and personal home paths were removed only from report copies; original local reports/files remain unchanged.

A single root commit on `main` is prepared only after these checks. The destination was confirmed empty before preparation and is rechecked immediately before publication. Final remote commit/file-count and original-project preservation checks are performed after the normal push. No existing repository history is imported or rewritten.
