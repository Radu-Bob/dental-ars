#!/bin/bash

# ─────────────────────────────────────────────────────────────────────────────
# 00_backup.sh — Clone both clinic databases into decrypted copies
#
# Creates 792088_mint_decrypted and 792088_tanya_decrypted.
# The originals (_mint, _tanya) are NEVER touched.
#
# Run this BEFORE dental_script.sh to have a clean, readable snapshot
# you can inspect and restore from if anything goes wrong.
#
# To restore _mint from the backup:
#   mysqldump --defaults-extra-file=.my.cnf 792088_mint_decrypted \
#     | mysql --defaults-extra-file=.my.cnf 792088_mint
#   php ../../artisan encrypt:existing
# ─────────────────────────────────────────────────────────────────────────────

DB_MINT="792088_mint"
DB_TANYA="792088_tanya"
DB_MINT_DEC="${DB_MINT}_decrypted"
DB_TANYA_DEC="${DB_TANYA}_decrypted"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="backup_${TIMESTAMP}.log"
BACKUP_DIR="backups/${TIMESTAMP}"
PROJECT_ROOT="$(realpath "$PWD/../..")"

echo "============================================" | tee -a "$LOG_FILE"
echo "Backup ritual — $(date)" | tee -a "$LOG_FILE"
echo "Cloning: $DB_MINT  →  $DB_MINT_DEC" | tee -a "$LOG_FILE"
echo "Cloning: $DB_TANYA →  $DB_TANYA_DEC" | tee -a "$LOG_FILE"
echo "Originals: untouched." | tee -a "$LOG_FILE"
echo "============================================" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# PHASE 1 — (RE)CREATE the decrypted target databases
# ─────────────────────────────────────────────────────────────────────────────
echo "-------------------------------------------" | tee -a "$LOG_FILE"
echo "PHASE 1: Preparing target databases..." | tee -a "$LOG_FILE"
echo "-------------------------------------------" | tee -a "$LOG_FILE"

mysql --defaults-extra-file="$PWD/.my.cnf" \
    -e "DROP DATABASE IF EXISTS \`${DB_MINT_DEC}\`; CREATE DATABASE \`${DB_MINT_DEC}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then
    echo "Database $DB_MINT_DEC ready." | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: Could not create $DB_MINT_DEC. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi

mysql --defaults-extra-file="$PWD/.my.cnf" \
    -e "DROP DATABASE IF EXISTS \`${DB_TANYA_DEC}\`; CREATE DATABASE \`${DB_TANYA_DEC}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then
    echo "Database $DB_TANYA_DEC ready." | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: Could not create $DB_TANYA_DEC. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# PHASE 2 — CLONE (dump source → import into decrypted target)
# The clones hold the same encrypted ciphertext as the originals at this point.
# ─────────────────────────────────────────────────────────────────────────────
echo "-------------------------------------------" | tee -a "$LOG_FILE"
echo "PHASE 2: Cloning databases..." | tee -a "$LOG_FILE"
echo "-------------------------------------------" | tee -a "$LOG_FILE"

mysqldump --defaults-extra-file="$PWD/.my.cnf" \
    --single-transaction --routines --triggers \
    "$DB_MINT" \
    | mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_MINT_DEC" \
    2>> "$LOG_FILE"
if [ $? -eq 0 ]; then
    echo "Cloned $DB_MINT → $DB_MINT_DEC." | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: Clone failed for $DB_MINT. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi

mysqldump --defaults-extra-file="$PWD/.my.cnf" \
    --single-transaction --routines --triggers \
    "$DB_TANYA" \
    | mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_TANYA_DEC" \
    2>> "$LOG_FILE"
if [ $? -eq 0 ]; then
    echo "Cloned $DB_TANYA → $DB_TANYA_DEC." | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: Clone failed for $DB_TANYA. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# PHASE 3 — DECRYPT the clones
# Override DB_DATABASE / DB_PARTNER_DATABASE via environment so artisan
# targets the _decrypted copies. The originals remain encrypted and untouched.
# ─────────────────────────────────────────────────────────────────────────────
echo "-------------------------------------------" | tee -a "$LOG_FILE"
echo "PHASE 3: Decrypting clones (originals untouched)..." | tee -a "$LOG_FILE"
echo "-------------------------------------------" | tee -a "$LOG_FILE"

DB_DATABASE="$DB_MINT_DEC" php "$PROJECT_ROOT/artisan" decrypt:existing --force >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then
    echo "$DB_MINT_DEC decrypted successfully." | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: decrypt:existing failed for $DB_MINT_DEC." | tee -a "$LOG_FILE"
    exit 1
fi

DB_PARTNER_DATABASE="$DB_TANYA_DEC" php "$PROJECT_ROOT/artisan" decrypt:existing --connection=partner --force >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then
    echo "$DB_TANYA_DEC decrypted successfully." | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: decrypt:existing failed for $DB_TANYA_DEC." | tee -a "$LOG_FILE"
    exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# PHASE 4 — DUMP decrypted databases to SQL files under backups/TIMESTAMP/
# These are plaintext dumps you can inspect or restore from without phpMyAdmin.
# The backups/ folder is gitignored — files stay local only.
# ─────────────────────────────────────────────────────────────────────────────
echo "-------------------------------------------" | tee -a "$LOG_FILE"
echo "PHASE 4: Dumping decrypted databases to files..." | tee -a "$LOG_FILE"
echo "-------------------------------------------" | tee -a "$LOG_FILE"

mkdir -p "$BACKUP_DIR"
echo "Backup folder: $BACKUP_DIR" | tee -a "$LOG_FILE"

mysqldump --defaults-extra-file="$PWD/.my.cnf" \
    --single-transaction --routines --triggers \
    "$DB_MINT_DEC" \
    > "$BACKUP_DIR/792088_mint_plaintext.sql" \
    2>> "$LOG_FILE"
if [ $? -eq 0 ]; then
    echo "Dumped $DB_MINT_DEC → $BACKUP_DIR/792088_mint_plaintext.sql" | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: Dump failed for $DB_MINT_DEC. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi

mysqldump --defaults-extra-file="$PWD/.my.cnf" \
    --single-transaction --routines --triggers \
    "$DB_TANYA_DEC" \
    > "$BACKUP_DIR/792088_tanya_plaintext.sql" \
    2>> "$LOG_FILE"
if [ $? -eq 0 ]; then
    echo "Dumped $DB_TANYA_DEC → $BACKUP_DIR/792088_tanya_plaintext.sql" | tee -a "$LOG_FILE"
else
    echo "FATAL ERROR: Dump failed for $DB_TANYA_DEC. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi

# Copy log into backup folder once dumps are done
cp "$LOG_FILE" "$BACKUP_DIR/$LOG_FILE" 2>/dev/null

echo "============================================" | tee -a "$LOG_FILE"
echo "Backup complete — $(date)" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
echo "Plaintext copies available:" | tee -a "$LOG_FILE"
echo "  MySQL : $DB_MINT_DEC" | tee -a "$LOG_FILE"
echo "  MySQL : $DB_TANYA_DEC" | tee -a "$LOG_FILE"
echo "  File  : $BACKUP_DIR/792088_mint_plaintext.sql" | tee -a "$LOG_FILE"
echo "  File  : $BACKUP_DIR/792088_tanya_plaintext.sql" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
echo "Originals ($DB_MINT, $DB_TANYA) are encrypted and unchanged." | tee -a "$LOG_FILE"
echo "============================================" | tee -a "$LOG_FILE"
echo ""
echo "You can now safely run dental_script.sh."
