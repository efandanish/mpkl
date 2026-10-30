# Existing-database migrations

These scripts upgrade existing Caj Pemajuan tables. Fresh imports of `database/schema.sql` or `database/caj_pemajuan.sanitized.sql` already include them: do not rerun the ALTERs.

Before changing an existing installation, coordinate a maintenance window, preserve private backups of the database and receipt storage, and rehearse restoration and migration in a disposable database. Never store backups in this repository or print private rows/credentials in deployment logs. Preserve configuration/security keys and financial history.

Apply ONLY migrations whose complete definitions are missing, in this order:

| Version | Up script | Added definitions |
| --- | --- | --- |
| 001 | `20261023_001_receipt_number.up.sql` | Nullable receipt_number VARCHAR(100) on development_charge_payments |
| 002 | `20261023_002_charge_record_year.up.sql` | Nullable record_year SMALLINT UNSIGNED, year index and NULL-or-1000–9999 check on development_charges |
| 003 | `20261027_003_user_department.up.sql` | Nullable department VARCHAR(150) on users |
| 004 | `20261028_004_notification_audience.up.sql` | Notification audience, department, reminder_key, unique reminder index and entity/action index |

Inspect columns, indexes and constraints before applying each file; if a migration was partly applied, review the exact state rather than rerunning it. There is no automatic migration/version table. Use password prompts, for example:

```sh
mysql --default-character-set=utf8mb4 -u DB_USER -p DATABASE_NAME < database/migrations/20261023_001_receipt_number.up.sql
```

Replace account/database placeholders and the selected migration filename. Apply later files only after their own prechecks. `SHOW CREATE TABLE` and information_schema metadata can establish current definitions without exposing rows. Run `php scripts/check-setup.php` afterward and compare protected record/file fingerprints privately.

Existing receipt references, record years and departments remain NULL where unknown; migration 004 retains legacy notification visibility. Do not invent historical dates, references or department assignments. Backfill/corrections require a separately approved mapping and preservation of timestamps and paid history.

Down scripts are provided for explicitly reviewed rollback in reverse order (004, 003, 002, 001). They permanently remove the added assignments/references/indexes unless exported privately first. Prefer reverting code while retaining compatible additive columns; never restore an old database over newer transactions as a routine code rollback.
