# Splitting the Main Database — Step by Step

This guide covers the full workflow for taking a fresh export of the online `792088_dtdc2` database and splitting it into the two encrypted clinic databases that the Laravel app uses:

- `792088_mint` — Mint Dental Clinic (main connection)
- `792088_tanya` — Tanya Clinic (partner connection)

---

## Requirements

- `792088_dtdc2` must already be imported into local MySQL (see Step 0)
- `.my.cnf` credentials file present in the DataSplitter directory
- Laravel `.env` with a valid `APP_KEY`
- Run all scripts from inside the DataSplitter directory:

```bash
cd /home/radu/dental-data-refactored/tools/DataSplitter
```

---

## Step 0 — Import the online database export

Download a fresh export of `792088_dtdc2` from the online server and import it into local MySQL:

```bash
mysql --defaults-extra-file=.my.cnf 792088_dtdc2 < /path/to/dtdc2_export.sql
```

<details>
<summary>ℹ️ Why this step exists</summary>

`792088_dtdc2` is the main online database where doctors continue entering data. It is always plaintext and contains records for both clinics mixed together. This step brings it locally so the split scripts can read from it without needing an internet connection during the ritual.

</details>

---

## Step 1 — Back up the current clinic databases

```bash
bash 00_backup.sh
```

This will take a few minutes. When it finishes you will see the path to the backup folder printed on screen.

<details>
<summary>ℹ️ What 00_backup.sh does</summary>

Runs four phases:

1. **Creates** empty `792088_mint_decrypted` and `792088_tanya_decrypted` databases in MySQL
2. **Clones** the live `_mint` and `_tanya` into those databases (encrypted copy at this point)
3. **Decrypts** the clones using `artisan decrypt:existing` — originals are never touched
4. **Dumps** the decrypted databases to SQL files:

```
backups/YYYYMMDD_HHMMSS/
├── 792088_mint_plaintext.sql
├── 792088_tanya_plaintext.sql
└── backup_YYYYMMDD_HHMMSS.log
```

These SQL files are your safety net. If anything goes wrong during the split, you can restore from them using `99_restore.sh`. See `readme-99_restore.md` for restore instructions.

The backup files are gitignored — they exist only on this machine.

</details>

---

## Step 2 — Run the split ritual

```bash
bash dental_script.sh
```

The script will pause midway for inspection. Press **ENTER** to continue or **Ctrl+C** to abort safely.

When it finishes, `_mint` and `_tanya` are fully updated and encrypted.

<details>
<summary>ℹ️ What dental_script.sh does — all 5 stages</summary>

### Stage 1 — Backup
Repeats the clone+decrypt from `00_backup.sh` to create a fresh `_decrypted` baseline inside the ritual. This ensures the sync in Stage 2 starts from a clean known state.

### Stage 2 — Sync
Runs SQL scripts 01–06 against the plaintext `_decrypted` databases, pulling data from `_dtdc2`:

| Script | What it does |
|--------|-------------|
| `01` | Tanya patients — updateOrCreate from dtdc2 (active ≠ 2) |
| `02` | Mint patients — updateOrCreate from dtdc2 (active = 2) |
| `03` | Tanya clinical records — INSERT new only, no overwrite |
| `04` | Mint clinical records — INSERT new only, no overwrite |
| `05` | Mint clinical cleanup — estimate_description / remarks sanitation |
| `06` | Mint slashes cleanup |

Both sides are plaintext at this stage, so all text comparisons and deduplication work correctly.

### Stage 3 — Snapshot + Inspection pause
Clones `_decrypted` → `_updated` as a plaintext reference of the final state.
**The script pauses here.** You can open phpMyAdmin and inspect `_mint_updated` and `_tanya_updated` before anything touches the live databases. Press ENTER to continue or Ctrl+C to abort — live databases are still untouched at this point.

### Stage 4 — Encrypt
Clones `_updated` → `_new`, then runs `artisan encrypt:existing` on `_new`. The `_updated` databases remain as a plaintext reference.

### Stage 5 — Push to live
Pushes `_new` into the live databases:

- `--fresh` flag (first-time / full rebuild): drops and rebuilds `_mint` and `_tanya` directly from `_new`
- Default (incremental): runs scripts `07` and `08` to updateOrCreate from `_new` into live

After this stage the live databases are encrypted and the Laravel app reads them normally.

### Temp databases left after the ritual
These stay in MySQL until you are satisfied and choose to drop them:

| Database | Contents |
|----------|----------|
| `_mint_decrypted` / `_tanya_decrypted` | Pre-ritual plaintext snapshot |
| `_mint_updated` / `_tanya_updated` | Post-sync plaintext reference |
| `_mint_new` / `_tanya_new` | Encrypted source — safe to drop |

</details>

---

## Quick reference — commands only

```bash
# 0. Import fresh dtdc2 export
mysql --defaults-extra-file=.my.cnf 792088_dtdc2 < /path/to/dtdc2_export.sql

# 1. Backup current clinic databases (creates SQL dump files)
bash 00_backup.sh

# 2. Run the split ritual
bash dental_script.sh
# → inspect data when paused, then press ENTER to continue
```

---

## If something goes wrong

Restore from the backup taken in Step 1:

```bash
bash 99_restore.sh
# lists available backups

bash 99_restore.sh YYYYMMDD_HHMMSS
# restores and re-encrypts
```

See `readme-99_restore.md` for full restore instructions.
