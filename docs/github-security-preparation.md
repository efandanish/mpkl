# GitHub security preparation

Date: 30 October 2026. Branch: `fix/security-data-integrity-v1`.

**Subsequent owner decision:** retain the ORIGINAL GitHub repository. The earlier new-repository recommendation below is superseded by [the original-repository cleanup plan](github-original-history-cleanup.md). Disposable-database testing is now explicitly authorized and the latest exact PHP 8.2.5 suite passes **844 assertions**; see [fresh verification](php825-final-publication-preparation.md). Earlier not-run results remain as historical evidence. No history rewrite or publication is authorized by that testing approval.
Repository: https://github.com/efandanish/cajpemajuan

## Decision and scope

Prepared a sanitized index for review only. No commit, push, history rewrite, database mutation, Apache configuration change or runtime switch was performed. The current branch still inherits sensitive history and MUST NOT be pushed as a safe publication merely because its current tree is sanitized.

## Findings and private retention

- `database/caj_pemajuan.sql`: populated development export with financial/project/notification/user rows and password hashes. Not needed for installation.
- `_installation/04 users.sql`: legacy framework demo account seed with named users, email addresses and working password hashes. Not required by the current Caj Pemajuan schema.
- `app/config/config.php`: embedded cookie/encryption/hash keys. Treat these as exposed if used by any deployment; source defaults are not proof of their operational use. SMTP strings are template values and the default DB password is empty.
- `docker/php825/local.php` and the previous Compose configuration: shared development database password, removed from publication candidates. It must be changed before any future use of the development environment; no live credentials were changed here.
- `public/uploads/receipts/`: five private receipt files found, retained and excluded. Individual receipt names and contents are intentionally omitted.
- `app/logs/log.txt`: tracked empty runtime log, now untracked and ignored.

Private backup location: `${HOME}/cajpemajuan-private-checkpoints/20261030-222213`.
Backup contains 11 files, including original SQL/configuration/Compose files, log and five receipts, with a SHA-256 manifest. Every directory is mode 0700 and every file is mode 0600. Original private files also remain in the working tree. No dump or receipt was deleted. Backup must be kept off public hosting and protected according to the owner's retention policy.

Initial inspection mistakenly rendered the legacy seed content in tool output. Treat that transcript as sensitive; do not publish it or attach it to an issue. Subsequent scans report paths/categories/counts only.

## Safe installation and development configuration

- Keep `database/schema.sql` (schema only) and all reviewed `database/migrations/*.sql`. The three legacy framework database/table/trigger scripts contain no INSERT statements or password hashes, but are not the application installation route.
- Remove both sensitive SQL paths from the index, retaining originals privately. `_installation/04 users.example.sql` contains comments only and points to local administrator creation; it has no accounts, hashes or usable password.
- Remove `app/config/config.php` from the index, retaining the active local file unchanged. Publish `app/config/config.example.php` with empty security-key slots. Fresh installations must copy it to `config.php`, generate unique keys privately, then configure the ignored local override. README explains this requirement. Existing local configuration and runtime behavior are preserved.
- Keep `scripts/create-admin.php`: generates credentials locally instead of shipping seeded accounts. It was reviewed but NOT executed. Its output must not be logged or committed.
- Publish Docker `local.example.php` using `getenv('PHP825_DB_PASSWORD')`, require that variable in Compose, and use it in a healthcheck without a literal password. Dockerfile and entrypoint use the example; private `local.php` is excluded. No containers were started/recreated.
- Snapshot scripts explicitly omit the private SQL paths, host configuration and Docker override. Container initialization generates fresh private security keys from the example. The Docker build context also excludes private overrides/environment files.
- `.gitignore` excludes SQL exports except schema/migrations/reviewed legacy structure and the comment-only example, local configuration, environment files, backups, logs, receipt uploads, caches, dependencies/build artifacts, and private Docker overrides. Ignoring does not remove historical objects.

## Audit method and limits

Enumerated repository files including ignored runtime/private files and checked tracked paths independently of ignore rules. Scans covered password hashes, private-key markers, common access-token patterns, SQL INSERT statements, credential assignments, email-like strings and exact matching of the discovered security keys/development password. Reviewed assignment hits in source/templates/tests as code or fictional fixtures; author/license/dependency contact metadata is attribution, not copied client-account data. Tests use synthetic records and `.test` addresses; fixture passwords are disposable test constants, not deployment credentials.

Scanned 900 reachable historical blobs across all local branches, tags and known remote references for hashes, SQL records, private keys, common token patterns and embedded security configuration. Automated pattern scans cannot prove the absence of every possible secret or identify every real-world person from arbitrary prose. Unreachable objects, other people's clones, forks, hidden GitHub PR refs, caches, artifacts and external backups were not exhaustively accessible.

## Git history and GitHub exposure

Read-only `git ls-remote --heads --tags origin` succeeded after sandbox network escalation. GitHub advertises only `refs/heads/main`, commit `ecce8cec0dce52762118462d42f21b22817c1185`, and no tags. That exact commit is available locally and contains both SQL paths with password hashes, plus the embedded configuration. This confirms exposure in the current GitHub branch tree, not merely an outdated local remote-tracking reference. Repository visibility and access/download history were not established.

Local `main`, current development branch and `origin/main` have historical copies of both SQL paths and configuration. Locally retained `origin/master`, symbolic `origin/HEAD`, and tags `v2.0`/`v3.0` have the legacy account seed/configuration. Tag `v1.0` has historical configuration. `origin/gh-pages` has no matching sensitive paths. These stale remote references/tags were inspected without deleting or changing them. Historical log content also requires removal in any cleanup even though the current log is empty.

## Validation

PHP executable: `/Applications/XAMPP/xamppfiles/bin/php` (7.4.33). Checks used `-d error_reporting=-1 -d track_errors=0`.

- Calculator: 40 PASS.
- Compatibility smoke: 33 PASS.
- Startup smoke: 6 PASS.
- JavaScript installment calculator: 42 assertions PASS.
- JavaScript project confirmation: fallback, cancellation and repeated-submit checks PASS.
- PHP syntax: 272 files, zero failures (includes retained private local files and new examples).
- Shell syntax: Docker entrypoint and both PHP 8.2 helper scripts pass.
- Full integration/migration/HTTP suite: NOT RUN. It explicitly creates/drops temporary databases and exercises record mutation. This task forbids deleting tables or records. Previous acceptance result remains 844 PASS, not newly verified and not a changed assertion baseline.
- Docker runtime/build tests: not run; configuration changes reviewed statically only.
- Staged snapshot audit: 391 indexed files at the first complete scan, zero detected sensitive-content findings; final counts are recorded below. Audited complete resulting blobs, including unchanged tracked files, rather than deleted lines that necessarily describe the removed private data. A trailing blank line in the migration README was corrected after the initial staged whitespace check.

## Recommended publication and cleanup

Prefer a NEW sanitized repository with a single fresh root commit created from an explicitly reviewed file export, without copying `.git`, private files, backups or receipts. This avoids publishing inherited objects and avoids rewriting this working repository. Do not push the existing development branch to the new repository. Keep this repository and private backup restricted for recovery; a new repository does not erase the old exposure.

For cleaning the existing GitHub repository, follow [GitHub's official sensitive-data removal guidance](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository). First revoke/rotate exposed credentials and assess account-password resets where hashes were exposed. Coordinate a maintenance window and collaborators. In a separate fresh clone, use `git-filter-repo --sensitive-data-removal` to remove all sensitive paths/renamed variants and replace exposed literals throughout every retained ref. Review the rewritten result, then obtain explicit approval for the required remote history replacement. Account for changed commit IDs, branch protections, pull requests/signatures, forks and collaborator clones. Ask GitHub Support about cached views and PR references when applicable; only GitHub can determine support eligibility. Never merge old history back into the cleaned repository. Rotation cannot remediate private financial records; access containment and historical removal remain necessary.

## Exact next steps requiring approval

1. Owner decides whether to restrict access to the existing repository immediately; no remote settings were changed.
2. Approve credential/key rotation and affected account-password handling in the appropriate environments. No operational keys or passwords were changed here.
3. Authorize disposable-database creation, record mutation and teardown if a fresh 844-assertion integration run is required. Real client data must remain untouched.
4. Choose a new sanitized repository (recommended), name/visibility, and authorize its creation and a fresh-root export. Alternatively explicitly authorize planning/executing history cleanup in a separate clone; this task grants no rewrite/force-push permission.
5. Review the staged file inventory and final audit results, then separately authorize any commit and publication. No commit or push is made by this task.

## Final staged review

Complete staged snapshot: 393 files. Sensitive SQL/configuration/log removals remain staged; all private originals remain ignored on disk. Complete indexed blob scan found no password hashes, private-key markers, common access tokens, SQL INSERT records or exact discovered security-key/development-password literals. No receipt/log content is indexed. Staged and working-tree whitespace checks pass after the documentation whitespace correction.

Additional isolated configuration-generation check: three unique-format generated keys, configuration mode 0600, no host configuration changes. Added initializer PHP syntax and updated helper shell syntax pass. Docker runtime compatibility remains unverified.
