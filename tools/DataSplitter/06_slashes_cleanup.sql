SET SESSION sql_mode = '';

-- ─────────────────────────────────────────────────────────────────────────────
-- 06 — Slashes cleanup: 792088_mint_decrypted
-- Nullifies estimate_cost/paid/balance fields that contain only
-- backslashes and/or spaces — leftover garbage from the old system.
-- Runs on plaintext data so REGEXP works correctly.
-- ─────────────────────────────────────────────────────────────────────────────

UPDATE `792088_mint_decrypted`.`patients_clinical`
SET
    `estimate_cost`    = NULL,
    `estimate_paid`    = NULL,
    `estimate_balance` = NULL
WHERE
    -- At least one field contains a backslash
    (`estimate_cost` LIKE '%\\%' OR `estimate_paid` LIKE '%\\%' OR `estimate_balance` LIKE '%\\%')

    -- Safety: each field contains ONLY backslashes or spaces (no real data)
    AND (`estimate_cost`    REGEXP '^[\ \\\\\ ]*$' OR `estimate_cost`    IS NULL)
    AND (`estimate_paid`    REGEXP '^[\ \\\\\ ]*$' OR `estimate_paid`    IS NULL)
    AND (`estimate_balance` REGEXP '^[\ \\\\\ ]*$' OR `estimate_balance` IS NULL);
