#!/bin/bash

# ─────────────────────────────────────────────────────────────────────────────
# dental_script.sh — Full database split ritual
#
# Usage:
#   bash dental_script.sh           — future runs: updateOrCreate into live DBs
#   bash dental_script.sh --fresh   — today only: drop and rebuild live DBs
#
# Stages:
#   1. BACKUP   — clone _mint/_tanya → _decrypted, decrypt in place (originals untouched)
#   2. SYNC     — updateOrCreate from dtdc2 into _decrypted (plain-to-plain)
#   3. SNAPSHOT — clone _decrypted → _updated (plaintext reference, pause for inspection)
#   4. ENCRYPT  — clone _updated → _new, encrypt _new in place
#   5. PUSH     — fresh: drop+rebuild live from _new | incremental: updateOrCreate _new → live
#
# Temp databases kept after ritual:
#   _decrypted — pre-ritual plaintext backup of live state
#   _updated   — post-sync plaintext reference (what live will contain)
#   _new       — encrypted copy (drop once verified)
# ─────────────────────────────────────────────────────────────────────────────

DB_MINT="792088_mint"
DB_TANYA="792088_tanya"
DB_MINT_DEC="${DB_MINT}_decrypted"
DB_TANYA_DEC="${DB_TANYA}_decrypted"
DB_MINT_UPD="${DB_MINT}_updated"
DB_TANYA_UPD="${DB_TANYA}_updated"
DB_MINT_NEW="${DB_MINT}_new"
DB_TANYA_NEW="${DB_TANYA}_new"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="ritual_${TIMESTAMP}.log"
PROJECT_ROOT="$(realpath "$PWD/../..")"

FRESH_START=false
for arg in "$@"; do
    [[ "$arg" == "--fresh" ]] && FRESH_START=true
done

# ─────────────────────────────────────────────────────────────────────────────
# Helpers
# ─────────────────────────────────────────────────────────────────────────────

db_size() {
    mysql --defaults-extra-file="$PWD/.my.cnf" -sNe \
        "SELECT CONCAT(ROUND(SUM(data_length + index_length) / 1024 / 1024, 1), ' MB')
         FROM information_schema.tables WHERE table_schema = '$1';" 2>/dev/null
}

clone_db() {
    local SRC="$1" DST="$2"
    echo "  Cloning $SRC → $DST ..." | tee -a "$LOG_FILE"
    mysql --defaults-extra-file="$PWD/.my.cnf" \
        -e "DROP DATABASE IF EXISTS \`${DST}\`; CREATE DATABASE \`${DST}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: Could not create $DST" | tee -a "$LOG_FILE"; exit 1
    fi
    mysqldump --defaults-extra-file="$PWD/.my.cnf" \
        --single-transaction --routines --triggers "$SRC" \
        | mysql --defaults-extra-file="$PWD/.my.cnf" "$DST" 2>> "$LOG_FILE"
    if [ $? -ne 0 ]; then
        echo "  FATAL: Clone failed $SRC → $DST" | tee -a "$LOG_FILE"; exit 1
    fi
    echo "  Done: $DST ($(db_size "$DST"))" | tee -a "$LOG_FILE"
}

run_sql() {
    local SCRIPT="$1"
    echo "  Running: $SCRIPT" | tee -a "$LOG_FILE"
    mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_MINT" -v < "$SCRIPT" >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: Error in $SCRIPT — check $LOG_FILE" | tee -a "$LOG_FILE"; exit 1
    fi
    echo "  Done: $SCRIPT" | tee -a "$LOG_FILE"
}

artisan_decrypt() {
    local DB_VAR="$1" DB_NAME="$2" CONN_FLAG="$3"
    echo "  Decrypting $DB_NAME ..." | tee -a "$LOG_FILE"
    eval "$DB_VAR=\"$DB_NAME\" php \"$PROJECT_ROOT/artisan\" decrypt:existing $CONN_FLAG --force" >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: decrypt:existing failed for $DB_NAME" | tee -a "$LOG_FILE"; exit 1
    fi
    echo "  Done: $DB_NAME decrypted" | tee -a "$LOG_FILE"
}

artisan_encrypt() {
    local DB_VAR="$1" DB_NAME="$2" CONN_FLAG="$3"
    echo "  Encrypting $DB_NAME ..." | tee -a "$LOG_FILE"
    eval "$DB_VAR=\"$DB_NAME\" php \"$PROJECT_ROOT/artisan\" encrypt:existing $CONN_FLAG" >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: encrypt:existing failed for $DB_NAME" | tee -a "$LOG_FILE"; exit 1
    fi
    echo "  Done: $DB_NAME encrypted" | tee -a "$LOG_FILE"
}

# ─────────────────────────────────────────────────────────────────────────────
# Header
# ─────────────────────────────────────────────────────────────────────────────

echo "" | tee -a "$LOG_FILE"
echo "╔══════════════════════════════════════════════╗" | tee -a "$LOG_FILE"
echo "║         DENTAL DATABASE SPLIT RITUAL         ║" | tee -a "$LOG_FILE"
echo "╚══════════════════════════════════════════════╝" | tee -a "$LOG_FILE"
echo "  Started : $(date)" | tee -a "$LOG_FILE"
echo "  Log     : $LOG_FILE" | tee -a "$LOG_FILE"
if [ "$FRESH_START" = true ]; then
    echo "  Mode    : FRESH START — live DBs will be dropped and rebuilt" | tee -a "$LOG_FILE"
else
    echo "  Mode    : INCREMENTAL — live DBs updated via updateOrCreate" | tee -a "$LOG_FILE"
fi
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 1 — BACKUP
# Clone live encrypted DBs into _decrypted copies, then decrypt those copies.
# The originals (_mint, _tanya) are never touched in this stage.
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 1 — BACKUP                            │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"
echo "  Cloning live encrypted databases..." | tee -a "$LOG_FILE"

clone_db "$DB_MINT"  "$DB_MINT_DEC"
clone_db "$DB_TANYA" "$DB_TANYA_DEC"

echo "  Decrypting clones (originals untouched)..." | tee -a "$LOG_FILE"

artisan_decrypt "DB_DATABASE"         "$DB_MINT_DEC"  ""
artisan_decrypt "DB_PARTNER_DATABASE" "$DB_TANYA_DEC" "--connection=partner"

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 1 COMPLETE" | tee -a "$LOG_FILE"
echo "    $DB_MINT_DEC  — plaintext snapshot of pre-ritual $DB_MINT" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_DEC — plaintext snapshot of pre-ritual $DB_TANYA" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 2 — SYNC
# Both sides are plaintext. All text comparisons, REGEXPs, and NULL checks
# work correctly. dtdc2 wins on all patient columns.
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 2 — SYNC (dtdc2 → _decrypted)         │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"

SYNC_SCRIPTS=(
    "01_tanya_patients_update.sql"
    "02_mint_patients_update.sql"
    "03_tanya_patients_clinical_update.sql"
    "04_mint_patients_clinical_update.sql"
    "05_mint_clinical_clean.sql"
    "06_slashes_cleanup.sql"
)

for SCRIPT in "${SYNC_SCRIPTS[@]}"; do
    run_sql "$SCRIPT"
done

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 2 COMPLETE — all records synced and sanitised" | tee -a "$LOG_FILE"
echo "    $DB_MINT_DEC  : $(db_size "$DB_MINT_DEC")" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_DEC : $(db_size "$DB_TANYA_DEC")" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 3 — SNAPSHOT
# Clone _decrypted → _updated as a plaintext reference of the final state.
# Script pauses here so you can inspect the data before encryption.
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 3 — SNAPSHOT (_decrypted → _updated)  │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"

clone_db "$DB_MINT_DEC"  "$DB_MINT_UPD"
clone_db "$DB_TANYA_DEC" "$DB_TANYA_UPD"

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 3 COMPLETE" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
echo "  ══════════════════════════════════════════════" | tee -a "$LOG_FILE"
echo "  INSPECTION PAUSE" | tee -a "$LOG_FILE"
echo "  You can now inspect the plaintext data:" | tee -a "$LOG_FILE"
echo "    $DB_MINT_UPD  — final mint data before encryption" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_UPD — final tanya data before encryption" | tee -a "$LOG_FILE"
echo "  Nothing has touched the live databases yet." | tee -a "$LOG_FILE"
echo "  Press ENTER to continue with encryption, or Ctrl+C to abort." | tee -a "$LOG_FILE"
echo "  ══════════════════════════════════════════════" | tee -a "$LOG_FILE"
read -r
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 4 — ENCRYPT
# Clone _updated → _new, then encrypt _new in place.
# _updated remains as a plaintext reference.
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 4 — ENCRYPT (_updated → _new)         │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"

clone_db "$DB_MINT_UPD"  "$DB_MINT_NEW"
clone_db "$DB_TANYA_UPD" "$DB_TANYA_NEW"

artisan_encrypt "DB_DATABASE"         "$DB_MINT_NEW"  ""
artisan_encrypt "DB_PARTNER_DATABASE" "$DB_TANYA_NEW" "--connection=partner"

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 4 COMPLETE" | tee -a "$LOG_FILE"
echo "    $DB_MINT_NEW  — encrypted, ready to push to live" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_NEW — encrypted, ready to push to live" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 5 — PUSH TO LIVE
# --fresh : drop and rebuild _mint/_tanya from _new (today only)
# default : updateOrCreate from _new into live (future runs)
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 5 — PUSH TO LIVE                      │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"

if [ "$FRESH_START" = true ]; then

    echo "  Fresh start: dropping and rebuilding $DB_MINT ..." | tee -a "$LOG_FILE"
    mysql --defaults-extra-file="$PWD/.my.cnf" \
        -e "DROP DATABASE IF EXISTS \`${DB_MINT}\`; CREATE DATABASE \`${DB_MINT}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: Could not recreate $DB_MINT — $DB_MINT_NEW is intact" | tee -a "$LOG_FILE"; exit 1
    fi
    mysqldump --defaults-extra-file="$PWD/.my.cnf" \
        --single-transaction --routines --triggers "$DB_MINT_NEW" \
        | mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_MINT" 2>> "$LOG_FILE"
    if [ $? -ne 0 ]; then
        echo "  FATAL: Import failed for $DB_MINT — restore from $DB_MINT_NEW manually" | tee -a "$LOG_FILE"; exit 1
    fi
    echo "  Done: $DB_MINT rebuilt ($(db_size "$DB_MINT"))" | tee -a "$LOG_FILE"

    echo "  Fresh start: dropping and rebuilding $DB_TANYA ..." | tee -a "$LOG_FILE"
    mysql --defaults-extra-file="$PWD/.my.cnf" \
        -e "DROP DATABASE IF EXISTS \`${DB_TANYA}\`; CREATE DATABASE \`${DB_TANYA}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: Could not recreate $DB_TANYA — $DB_TANYA_NEW is intact" | tee -a "$LOG_FILE"; exit 1
    fi
    mysqldump --defaults-extra-file="$PWD/.my.cnf" \
        --single-transaction --routines --triggers "$DB_TANYA_NEW" \
        | mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_TANYA" 2>> "$LOG_FILE"
    if [ $? -ne 0 ]; then
        echo "  FATAL: Import failed for $DB_TANYA — restore from $DB_TANYA_NEW manually" | tee -a "$LOG_FILE"; exit 1
    fi
    echo "  Done: $DB_TANYA rebuilt ($(db_size "$DB_TANYA"))" | tee -a "$LOG_FILE"

else

    echo "  Incremental: pushing encrypted _new data into live databases..." | tee -a "$LOG_FILE"
    run_sql "07_mint_live_sync.sql"
    run_sql "08_tanya_live_sync.sql"

fi

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 5 COMPLETE" | tee -a "$LOG_FILE"
echo "    $DB_MINT  : $(db_size "$DB_MINT")" | tee -a "$LOG_FILE"
echo "    $DB_TANYA : $(db_size "$DB_TANYA")" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# Done
# ─────────────────────────────────────────────────────────────────────────────

echo "╔══════════════════════════════════════════════╗" | tee -a "$LOG_FILE"
echo "║              RITUAL COMPLETE                 ║" | tee -a "$LOG_FILE"
echo "╚══════════════════════════════════════════════╝" | tee -a "$LOG_FILE"
echo "  Finished : $(date)" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
echo "  Temp databases (drop once you are satisfied):" | tee -a "$LOG_FILE"
echo "    $DB_MINT_DEC  — pre-ritual plaintext backup" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_DEC — pre-ritual plaintext backup" | tee -a "$LOG_FILE"
echo "    $DB_MINT_UPD  — post-sync plaintext reference" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_UPD — post-sync plaintext reference" | tee -a "$LOG_FILE"
echo "    $DB_MINT_NEW  — encrypted source (safe to drop)" | tee -a "$LOG_FILE"
echo "    $DB_TANYA_NEW — encrypted source (safe to drop)" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
echo "  To drop all temp databases:" | tee -a "$LOG_FILE"
echo "    mysql --defaults-extra-file=.my.cnf -e \"" | tee -a "$LOG_FILE"
echo "      DROP DATABASE IF EXISTS \`$DB_MINT_DEC\`;" | tee -a "$LOG_FILE"
echo "      DROP DATABASE IF EXISTS \`$DB_TANYA_DEC\`;" | tee -a "$LOG_FILE"
echo "      DROP DATABASE IF EXISTS \`$DB_MINT_UPD\`;" | tee -a "$LOG_FILE"
echo "      DROP DATABASE IF EXISTS \`$DB_TANYA_UPD\`;" | tee -a "$LOG_FILE"
echo "      DROP DATABASE IF EXISTS \`$DB_MINT_NEW\`;" | tee -a "$LOG_FILE"
echo "      DROP DATABASE IF EXISTS \`$DB_TANYA_NEW\`;\"" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
