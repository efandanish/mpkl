# Stage 3A: user departments

Adds nullable `users.department VARCHAR(150)` without assigning existing users. The live preflight used read-only `SHOW CREATE TABLE users/projects`: users had no department column and projects had `VARCHAR(150) NOT NULL`. No client migration or record changes were performed.

## Behaviour

- `Departments::options()` holds the four existing stored values, including their `Jabatan` prefix. User and project dropdowns use it; no role or department table is added.
- Admin-created ordinary users require a department. Admin registration may leave it NULL. Only the four listed values are accepted when a department is supplied.
- Existing users can remain NULL on edit. An omitted department preserves the saved value; an explicit empty selection stores NULL. The list shows `Belum ditetapkan` for NULL.
- User detail/list queries and both password/no-password update paths include department. Password hashing, existing roles, own-admin-role safeguards, CSRF, login and account-management restrictions are retained.
- Project creation/changed departments validate against the shared list. An unchanged pre-existing nonstandard department remains accepted on update for compatibility; no existing project is rewritten.
- No notification code or routing changes. No financial calculation changes.

## Validation

PHP 7.4.33 lint passed for all 12 changed/new PHP files. The isolated `tests/modules-smoke.php` suite passed 571 assertions, including Stage 1, Stage 2A migration/functionality, Stage 2B Problem 1, Problem 2, and Stage 3A. New migration tests exercise down/up/down/up exclusively in the generated disposable database. They verify original user ID/password/role/timestamps remain identical and the added assignment is NULL. New HTTP tests cover required/invalid/array-shaped departments, persistence, NULL editing, both password paths, listing, shared forms, login, direct ordinary-user denial, CSRF and project department validation. Client row/schema and receipt fingerprints were unchanged. JavaScript project confirmation tests and `git diff --check` passed.

## Explicit deployment on XAMPP/macOS

The migration has **not** been applied to client data. Updated user queries require the column. Keep the application unavailable during the code/database deployment window; do not expose the updated code against the old schema.

Run from `/Applications/XAMPP/xamppfiles/htdocs/cajpemajuan`. Confirm the actual database name, user, host/socket and overrides in your environment; examples use `caj_pemajuan` and `DB_USER`. Commands below are manual instructions, not an automatic startup migration.

1. Pause application writes and retain the previous code artifact. Inspect:

```sh
/Applications/XAMPP/xamppfiles/bin/mysql -u DB_USER -p caj_pemajuan -e "SHOW CREATE TABLE users; SHOW COLUMNS FROM users LIKE 'department'; SELECT COUNT(*) AS users_before FROM users;"
```

If department already exists, stop and inspect its definition/deployment history rather than rerunning ALTER. Record baseline counts and protected field/timestamp checks privately; do not expose password hashes in shared logs.

2. Back up the database and check the command exit status:

```sh
umask 077
STAGE3A_BACKUP_DIR="$HOME/cajpemajuan-backups/stage3a-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$STAGE3A_BACKUP_DIR"
/Applications/XAMPP/xamppfiles/bin/mysqldump --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan > "$STAGE3A_BACKUP_DIR/before-stage3a.sql"
shasum -a 256 "$STAGE3A_BACKUP_DIR/before-stage3a.sql"
```

Keep the dump private. Verify a restore in a separate database before proceeding; never rehearse by restoring over client records. No full schema import is needed.

3. Apply **only** this additive migration, once, after approval:

```sh
/Applications/XAMPP/xamppfiles/bin/mysql --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan < database/migrations/20261027_003_user_department.up.sql
/Applications/XAMPP/xamppfiles/bin/mysql -u DB_USER -p caj_pemajuan -e "SHOW COLUMNS FROM users LIKE 'department'; SELECT COUNT(*) AS users_after, SUM(department IS NOT NULL) AS assigned FROM users;"
```

Before any assignments are entered, count must match baseline and `assigned` must be zero. Check original values/timestamps against the backup and verify login before reopening access. Do not automatically backfill departments or rerun previous migrations.

## Rollback

Prefer restoring the prior code while keeping the harmless nullable column. If column removal is required, pause writes, export IDs/departments securely first, and restore the old code before removing the column. The down script deliberately loses all department assignments while preserving original user columns:

```sh
/Applications/XAMPP/xamppfiles/bin/mysql --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan < database/migrations/20261027_003_user_department.down.sql
```

MariaDB ALTER implicitly commits. Do not use an old full dump to roll back over subsequent client activity. Do not run the down script while this Stage 3A code is serving requests.

## Manual checks after migration

1. Log in as admin at `http://localhost/cajpemajuan`. Verify existing users show `Belum ditetapkan` and their edit pages work without assigning a department.
2. In a disposable environment, create an ordinary user with each listed department. Verify missing/invalid departments are rejected and saved assignments appear in the list and edit dropdown.
3. Update a department with the password blank; confirm the old password still works. Repeat with a new password and verify the replacement works.
4. Create an admin without a department and confirm it remains supported. Confirm roles are unchanged.
5. Log in as an ordinary user: account management and direct assignment POSTs must remain denied. Charge/project creation permissions must still work.
6. Verify project forms show the same four options and notifications behave as before.

Stop at Stage 3A. PIC assignments and department notification routing remain separate work.
