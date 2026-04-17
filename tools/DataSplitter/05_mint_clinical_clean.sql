SET SESSION sql_mode = '';

-- ─────────────────────────────────────────────────────────────────────────────
-- 05 — Mint clinical sanitation: 792088_mint_decrypted
-- Moves estimate_description to remarks where a real description exists
-- alongside garbage estimate fields. Clears the estimate columns.
-- Runs on plaintext data so REGEXP and NULL checks work correctly.
-- ─────────────────────────────────────────────────────────────────────────────

UPDATE `792088_mint_decrypted`.`patients_clinical`
SET
    -- Preserve the text by merging into remarks
    `remarks` = IF(`remarks` IS NULL OR `remarks` = '', `estimate_description`, CONCAT(`remarks`, ' | ', `estimate_description`)),

    -- Sanitise the estimate fields
    `estimate_description` = NULL,
    `estimate`             = NULL,
    `estimate_cost`        = NULL,
    `estimate_paid`        = NULL,
    `estimate_balance`     = NULL
WHERE
    -- Real clinical data exists in the record
    (
        (`description`  IS NOT NULL AND `description` != '') OR
        (`amount`       IS NOT NULL AND `amount`      != '') OR
        (`paid`         IS NOT NULL AND `paid`        != '') OR
        (`balance`      IS NOT NULL AND `balance`     != '')
    )

    -- estimate_description has actual content (not just backslashes/spaces)
    AND (`estimate_description` IS NOT NULL AND `estimate_description` != '')
    AND `estimate_description` REGEXP '[^\\\\ ]'

    -- Numeric estimate fields are empty or contain only backslash/space garbage
    AND (`estimate`         IS NULL OR `estimate`         = '' OR `estimate`         REGEXP '^[\\\\ ]*$')
    AND (`estimate_cost`    IS NULL OR `estimate_cost`    = '' OR `estimate_cost`    REGEXP '^[\\\\ ]*$')
    AND (`estimate_paid`    IS NULL OR `estimate_paid`    = '' OR `estimate_paid`    REGEXP '^[\\\\ ]*$')
    AND (`estimate_balance` IS NULL OR `estimate_balance` = '' OR `estimate_balance` REGEXP '^[\\\\ ]*$');
