# 99_restore.sh — Database Restore Guide

Restores `792088_mint` and `792088_tanya` from a plaintext SQL backup produced by `00_backup.sh`, then encrypts them so the Laravel app reads them normally.

---

## Prerequisites

### 1. The `.env` APP_KEY must be intact

This is the most critical requirement. The Laravel app encrypts patient data using the `APP_KEY` in `/home/radu/dental-data-refactored/.env`. When you restore, `encrypt:existing` will use that same key to re-encrypt the data.

- If the `APP_KEY` is the same as when the backup was taken — everything works.
- If the `APP_KEY` has changed or is missing — the app will not be able to read the restored data.

**Keep a copy of your `.env` file in a safe place, separate from the database backups.**

### 2. Run from the DataSplitter directory

```bash
cd /home/radu/dental-data-refactored/tools/DataSplitter
```

### 3. MySQL credentials must be available via `.my.cnf`

The script uses `--defaults-extra-file=.my.cnf` — the same credentials file used by all other DataSplitter scripts.

---

## Usage

### List available backups

```bash
bash 99_restore.sh
```

Output example:
```
Available backups:
  20260316_205214
  20260404_143022
```

### Restore from a specific backup

```bash
bash 99_restore.sh 20260404_143022
```

The script will show a warning and ask you to type `YES` before touching any database.

---

## What the script does

| Stage | Action |
|-------|--------|
| 1 | Drops and recreates `792088_mint` and `792088_tanya` |
| 1 | Imports plaintext SQL dumps from `backups/TIMESTAMP/` |
| 2 | Runs `encrypt:existing` on `_mint` (main connection) |
| 2 | Runs `encrypt:existing` on `_tanya` (partner connection) |

After completion, both databases are encrypted and the Laravel app reads them exactly as before.

---

## Backup file locations

Backups are created by `00_backup.sh` and stored locally under:

```
tools/DataSplitter/backups/
└── 20260404_143022/
    ├── 792088_mint_plaintext.sql    ← plaintext, human-readable
    ├── 792088_tanya_plaintext.sql   ← plaintext, human-readable
    └── backup_20260404_143022.log
```

These files are gitignored — they exist only on this machine.

---

## Restore log

Each run writes a `restore_YYYYMMDD_HHMMSS.log` file in the DataSplitter directory. Check it if anything goes wrong.

---

## Recovery scenario example

> Your `_mint` or `_tanya` database is corrupted or accidentally dropped.
> You have a backup from `00_backup.sh` dated `20260404_143022`.

```bash
cd /home/radu/dental-data-refactored/tools/DataSplitter
bash 99_restore.sh 20260404_143022
# Type YES when prompted
```

The app will be back to the state it was in at the time of that backup.
