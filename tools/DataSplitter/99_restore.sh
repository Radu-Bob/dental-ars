#!/bin/bash

# ─────────────────────────────────────────────────────────────────────────────
# 99_restore.sh — Restore _mint and _tanya from a plaintext backup
#
# Takes a plaintext SQL backup (produced by 00_backup.sh) and rebuilds the
# live encrypted databases that Laravel reads.
#
# Usage:
#   bash 99_restore.sh <timestamp>
#   bash 99_restore.sh 20260404_143022
#
# If no timestamp is given, available backups are listed.
#
# What this does:
#   1. Validates the backup folder and SQL files exist
#   2. Asks for confirmation (this overwrites live databases)
#   3. Drops and rebuilds 792088_mint and 792088_tanya from the SQL dumps
#   4. Runs encrypt:existing on both — result is encrypted and Laravel-readable
# ─────────────────────────────────────────────────────────────────────────────

DB_MINT="792088_mint"
DB_TANYA="792088_tanya"
PROJECT_ROOT="$(realpath "$PWD/../..")"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="restore_${TIMESTAMP}.log"

# ─────────────────────────────────────────────────────────────────────────────
# Argument check — list available backups if none given
# ─────────────────────────────────────────────────────────────────────────────

if [ -z "$1" ]; then
    echo ""
    echo "Usage: bash 99_restore.sh <timestamp>"
    echo ""
    echo "Available backups:"
    if [ -d "backups" ] && ls backups/ 2>/dev/null | grep -q .; then
        for dir in backups/*/; do
            ts="$(basename "$dir")"
            echo "  $ts"
        done
    else
        echo "  (none found in backups/)"
    fi
    echo ""
    exit 1
fi

BACKUP_TS="$1"
BACKUP_DIR="backups/${BACKUP_TS}"
MINT_SQL="${BACKUP_DIR}/792088_mint_plaintext.sql"
TANYA_SQL="${BACKUP_DIR}/792088_tanya_plaintext.sql"

# ─────────────────────────────────────────────────────────────────────────────
# Validate backup files exist
# ─────────────────────────────────────────────────────────────────────────────

if [ ! -d "$BACKUP_DIR" ]; then
    echo "ERROR: Backup folder not found: $BACKUP_DIR"
    exit 1
fi
if [ ! -f "$MINT_SQL" ]; then
    echo "ERROR: Missing file: $MINT_SQL"
    exit 1
fi
if [ ! -f "$TANYA_SQL" ]; then
    echo "ERROR: Missing file: $TANYA_SQL"
    exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# Header
# ─────────────────────────────────────────────────────────────────────────────

echo "" | tee -a "$LOG_FILE"
echo "╔══════════════════════════════════════════════╗" | tee -a "$LOG_FILE"
echo "║           DENTAL DATABASE RESTORE            ║" | tee -a "$LOG_FILE"
echo "╚══════════════════════════════════════════════╝" | tee -a "$LOG_FILE"
echo "  Started    : $(date)" | tee -a "$LOG_FILE"
echo "  Backup     : $BACKUP_DIR" | tee -a "$LOG_FILE"
echo "  Restoring  : $DB_MINT and $DB_TANYA" | tee -a "$LOG_FILE"
echo "  Log        : $LOG_FILE" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# Confirmation — this overwrites live databases
# ─────────────────────────────────────────────────────────────────────────────

echo "  ══════════════════════════════════════════════" | tee -a "$LOG_FILE"
echo "  WARNING: This will DROP and rebuild:" | tee -a "$LOG_FILE"
echo "    $DB_MINT" | tee -a "$LOG_FILE"
echo "    $DB_TANYA" | tee -a "$LOG_FILE"
echo "  from backup: $BACKUP_TS" | tee -a "$LOG_FILE"
echo "  ══════════════════════════════════════════════" | tee -a "$LOG_FILE"
echo ""
read -r -p "  Type YES to continue, anything else to abort: " CONFIRM
echo ""

if [ "$CONFIRM" != "YES" ]; then
    echo "Aborted. No databases were changed." | tee -a "$LOG_FILE"
    exit 0
fi

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 1 — IMPORT plaintext SQL dumps into fresh databases
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 1 — IMPORT                            │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"

for DB in "$DB_MINT" "$DB_TANYA"; do
    echo "  Recreating $DB ..." | tee -a "$LOG_FILE"
    mysql --defaults-extra-file="$PWD/.my.cnf" \
        -e "DROP DATABASE IF EXISTS \`${DB}\`; CREATE DATABASE \`${DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "  FATAL: Could not recreate $DB. Aborting." | tee -a "$LOG_FILE"
        exit 1
    fi
    echo "  Database $DB ready." | tee -a "$LOG_FILE"
done

echo "  Importing $MINT_SQL → $DB_MINT ..." | tee -a "$LOG_FILE"
mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_MINT" < "$MINT_SQL" >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "  FATAL: Import failed for $DB_MINT. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi
echo "  Done: $DB_MINT imported." | tee -a "$LOG_FILE"

echo "  Importing $TANYA_SQL → $DB_TANYA ..." | tee -a "$LOG_FILE"
mysql --defaults-extra-file="$PWD/.my.cnf" "$DB_TANYA" < "$TANYA_SQL" >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "  FATAL: Import failed for $DB_TANYA. Aborting." | tee -a "$LOG_FILE"
    exit 1
fi
echo "  Done: $DB_TANYA imported." | tee -a "$LOG_FILE"

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 1 COMPLETE — plaintext data imported" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# STAGE 2 — ENCRYPT both databases so Laravel can read them
# ─────────────────────────────────────────────────────────────────────────────

echo "┌──────────────────────────────────────────────┐" | tee -a "$LOG_FILE"
echo "│  STAGE 2 — ENCRYPT                           │" | tee -a "$LOG_FILE"
echo "└──────────────────────────────────────────────┘" | tee -a "$LOG_FILE"

echo "  Encrypting $DB_MINT ..." | tee -a "$LOG_FILE"
php "$PROJECT_ROOT/artisan" encrypt:existing >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "  FATAL: encrypt:existing failed for $DB_MINT." | tee -a "$LOG_FILE"
    exit 1
fi
echo "  Done: $DB_MINT encrypted." | tee -a "$LOG_FILE"

echo "  Encrypting $DB_TANYA ..." | tee -a "$LOG_FILE"
php "$PROJECT_ROOT/artisan" encrypt:existing --connection=partner >> "$LOG_FILE" 2>&1
if [ $? -ne 0 ]; then
    echo "  FATAL: encrypt:existing failed for $DB_TANYA." | tee -a "$LOG_FILE"
    exit 1
fi
echo "  Done: $DB_TANYA encrypted." | tee -a "$LOG_FILE"

echo "" | tee -a "$LOG_FILE"
echo "  ✓ STAGE 2 COMPLETE — databases encrypted and Laravel-ready" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────────────────────────────────────
# Done
# ─────────────────────────────────────────────────────────────────────────────

echo "╔══════════════════════════════════════════════╗" | tee -a "$LOG_FILE"
echo "║             RESTORE COMPLETE                 ║" | tee -a "$LOG_FILE"
echo "╚══════════════════════════════════════════════╝" | tee -a "$LOG_FILE"
echo "  Finished : $(date)" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
echo "  $DB_MINT and $DB_TANYA are restored and encrypted." | tee -a "$LOG_FILE"
echo "  The Laravel app should read them normally." | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"
