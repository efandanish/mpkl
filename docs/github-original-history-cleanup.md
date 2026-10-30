# Original GitHub repository cleanup and collaborator migration

Date: 30 October 2026. Selected destination: **https://github.com/efandanish/cajpemajuan**.

## Status: plan and isolated clone only

The owner selected retention of the original repository. This supersedes the earlier new-repository recommendation in `github-security-preparation.md`. No history has been rewritten, no commit made, no remote ref updated, no settings changed and no push attempted.

A fresh restrictive mirror was created at `/private/tmp/cajpemajuan-final825-evidence/history-review.git`. Its push URL is deliberately `no-push://approval-required`. It contains sensitive historical objects: do not publish/copy it into a clean source export. The live working checkout and its staged client-feedback implementation were not reset, rebased or checked out onto another branch. A private all-local-refs bundle, original index, and binary staged/working patches preserve recovery independently of the mirror. The backup location is recorded in the final PHP preparation report; all files are mode 0600 in a mode 0700 directory.

## Fresh remote inventory

Read-only GitHub checks during this preparation returned:

| Item | Observed state |
| --- | --- |
| Visibility | Public |
| Default/current remote branch | `main` |
| Advertised `main` object | `ecce8cec0dce52762118462d42f21b22817c1185` |
| Other advertised branches/tags | None |
| Pull requests, all states | None returned |
| Classic `main` protection | API 404: branch not protected |
| Repository rulesets | None returned |
| Listed forks | None returned |
| Listed collaborators | Owner account only |

These are observations at inspection time, not proof that nobody downloaded/cloned the repository or that no hidden/cached refs exist. The partner may have unpushed commits or unregistered access; do not assume their work is in the mirror. Recheck remote tips, PRs, protection/rulesets, tags and collaborator state immediately before any approved cleanup publication. New partner work invalidates the recorded-tip precondition.

The exact advertised main commit exists in the local object store and includes the populated SQL export, legacy hashed-user seed, and embedded cookie/encryption/hash configuration. Exposure is confirmed in the GitHub branch's current tree. Local nine-ref audit also finds sensitive paths in development/main histories and stale remote/tag histories. Retain the prior 900-blob scan findings. Locally retained `origin/master`, `origin/HEAD`, `origin/gh-pages`, tags `v1.0`, `v2.0`, `v3.0` are not currently advertised GitHub branches/tags. Do not republish them automatically.

## Scope to remove and review

Known historical removal paths:

- `database/caj_pemajuan.sql`
- `_installation/04 users.sql`
- `app/config/config.php`
- `app/config/local.php`, private alternate local config names and environment files if discovered
- `app/logs/log.txt` and other runtime logs/backups
- private receipt/upload/backup paths if discovered in additional history
- `docker/php825/local.php` and any private environment overrides if present in historical objects

Search all historical blobs and renamed variants before fixing the final path list. Also replace exposed credential/key literals wherever copied outside removed files, using a restrictive replacement file populated programmatically from private originals; never paste literal keys into a command, issue, report or terminal output. Do not remove legitimate schema-only `database/schema.sql`, reviewed migrations, fictional test fixtures, source or public attribution metadata indiscriminately.

The inspected private files are already excluded from the prepared index. The current source tree is not evidence that historical objects are safe. Do not use `git push` of the current development branch as a cleanup operation.

## Approval-gated procedure in the separate clone

Reference: [GitHub official sensitive-data removal guidance](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository).

1. Owner coordinates containment and exposed-key/password handling first. Public financial records cannot be made safe by rotating passwords alone. No credentials or remote visibility settings were changed by this preparation.
2. Freeze pushes during cleanup, obtain partner acknowledgement and preserve unpushed work as described below. Record the final remote refs and create a new restrictive backup/mirror if they changed. Do not delete the live working checkout or its private files.
3. Install/verify a current `git-filter-repo` supporting `--sensitive-data-removal` (GitHub specifies version 2.47 or later); it is not installed in this environment. Installation and actual rewriting have NOT been performed. Approve the exact removal paths, literal replacement rules and intended retained refs before execution.
4. Only after explicit local-cleanup approval, perform the reviewed rewrite in the separate fresh clone. Illustrative core command, NOT executed:

```sh
git filter-repo --sensitive-data-removal --invert-paths \
  --path database/caj_pemajuan.sql \
  --path '_installation/04 users.sql' \
  --path app/config/config.php \
  --path app/config/local.php \
  --path app/logs/ \
  --path app/backups/ \
  --path app/uploads/ \
  --path public/uploads/receipts/ \
  --path docker/php825/local.php \
  --path .env \
  --replace-text /private/path/reviewed-replacements.txt
```

This is an initial path list, not a complete generic secret remover. Expand it for any confirmed renamed paths, environment variants and additional historical findings. The replacement file must be verified without displaying values, kept outside the repo, and mode 0600. `git-filter-repo` may remove the origin remote; do not re-enable a push destination until the publication gate.

5. Audit all retained rewritten refs/blobs, inspect the tool's changed-ref/commit mapping, run `git fsck`, and verify no sensitive SQL/hash/config/receipt or exact exposed literal survives. Preserve necessary source and migrations. Rebuild the current reviewed source on the cleaned main ancestry; do not merge or cherry-pick old branches wholesale. Retain recovery bundles privately rather than adding backup refs to the clean repository.
6. Publish the cleaned original base history ONLY after separate approval of the rewritten ref inventory and exact remote changes. Prefer a single explicit main update with an expected-old-object lease, never a mirror push. The old-object lease must equal the freshly verified GitHub tip; if it changed, stop and reconcile partner work. A possible separately approved command is `git push --force-with-lease=refs/heads/main:EXPECTED_OLD_OBJECT origin refs/heads/main:refs/heads/main`. Replace the placeholder only after fresh inspection. This command is documentation, not authorization. Do not use an empty/bare `--force-with-lease` as a substitute for the recorded object.
7. Coordinate GitHub Support review for residual sensitive PR/cache refs if applicable. No PRs/forks were listed, but other clones/caches cannot be erased from here. Existing commit URLs/checks/signatures may change or become invalid. GitHub decides whether Support intervention is warranted. Do not treat a successful push or missing branch path as proof of global erasure.
8. Restore/review protection and establish the agreed PR workflow with the owner. Existing lack of protection is not permission to bypass review. Keep the current development branch local; use a fresh clone of cleaned original main for final development publication.

## Preserve and migrate the partner's work

Send these instructions manually to the partner; no messages were sent by this task.

**Before the freeze:** finish saving editor buffers; record branch names, HEADs, remotes and `git status --short`. In a private directory outside the checkout, mode 0700, preserve a mode 0600 `git bundle create ... --all` plus separate `git diff --binary` and `git diff --cached --binary` outputs. Preserve untracked files separately using an explicitly reviewed list. A bundle alone does not include uncommitted or untracked work. These backups may contain private data and must never be uploaded. A protected whole-checkout backup may be easier than reconstructing index state. Do not paste raw diffs into chat/issues.

Agree what unpushed source commits/changes need reapplication and who owns them. Keep all backups until both collaborators confirm the cleaned implementation and recovered work. Do not continue pushing old-history branches during or after cleanup.

**After the owner confirms cleanup:** keep the old clone offline as a private recovery archive, and disable/remove its push remote locally. Clone the SAME original URL into a NEW directory; do not `git pull`, merge, or rebase the old clone into the cleaned history. Verify main's new approved object and scan the new clone before creating a feature branch.

Recover required source changes as individually reviewed file changes/patches with sensitive paths, private contexts, credentials and copied records removed. Apply/test them on a feature branch based on cleaned main; resolve overlaps with this final staged implementation rather than blindly applying all old patches. Do not restore `.git`, old refs/tags, configuration, dumps, receipts or generated credentials from the old clone. Preserve meaningful author attribution through the agreed review process without importing old Git ancestry. Any attempted old-object push should stop for review.

## Final development commit and review

After cleaned main is established and verified, create `fix/client-feedback-php82-final` from it. Export the final reviewed index with an explicit manifest and deletion list into a separate staging directory, without copying `.git` or private files; overlay the complete reviewed tree onto this branch. Remove only manifest-approved obsolete tracked paths so an overlay does not accidentally retain old sensitive files. Re-audit all resulting blobs and ancestors, verify the runtime source manifest, and rerun relevant checks before a separately authorized commit:

```text
feat: complete caj pemajuan client feedback and PHP 8.2 compatibility
```

Push this development branch normally (no force) to the SAME original repository after approval, then open a PR against cleaned main. No automatic main merge. If a same-named branch appeared meanwhile, inspect it and preserve collaborator work rather than overwriting it. This preparation created no commit or proposed commit hash.

## Explicit approvals still required

- Containment/rotation/account-reset actions and partner freeze/migration acknowledgement.
- Installing the cleanup tool and executing the reviewed history rewrite in the separate clone.
- Publishing the exact cleaned refs to the original GitHub repository, including the precise expected-old-tip lease and any protection-setting changes.
- Committing the reviewed final implementation on cleaned ancestry, publishing its development branch, and creating the PR.

Runtime activation is an independent approval described in `php825-final-publication-preparation.md`. Neither approval implies the other.
