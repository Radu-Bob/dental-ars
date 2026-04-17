SET SESSION sql_mode = '';

-- ─────────────────────────────────────────────────────────────────────────────
-- 01 — Tanya patients sync: dtdc2 → 792088_tanya_decrypted
-- updateOrCreate: dtdc2 wins on all columns (no ID cutoff).
-- p_reserved1 → date_of_birth, 0000-00-00 handled, name/gender uppercased.
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `792088_tanya_decrypted`.`patients` (
    `patient_id`, `name`, `acc_no`, `opened`, `closed`, `location`,
    `pobox`, `town`, `tel`, `active`, `email`, `occupation`,
    `remarks`, `image`, `date_of_birth`, `p_reserved2`, `gender`,
    `created_at`, `updated_at`
)
SELECT
    (p.`patient_id` + 1000000),
    UPPER(p.`name`),
    p.`acc_no`,
    CASE WHEN p.`opened` = '0000-00-00' OR p.`opened` IS NULL THEN '1970-01-01' ELSE CAST(p.`opened` AS CHAR) END,
    CASE WHEN p.`closed` = '0000-00-00' OR p.`closed` IS NULL THEN NULL ELSE p.`closed` END,
    p.`location`, p.`pobox`, p.`town`, p.`tel`, p.`active`, p.`email`,
    p.`occupation`, p.`remarks`, p.`image`,
    CASE WHEN p.`p_reserved1` = '0000-00-00' OR p.`p_reserved1` IS NULL THEN '1900-01-01' ELSE CAST(p.`p_reserved1` AS CHAR) END,
    p.`p_reserved2`,
    UPPER(p.`gender`),
    CASE WHEN p.`opened` = '0000-00-00' OR p.`opened` IS NULL THEN '2026-01-01 00:00:00' ELSE p.`opened` END,
    CASE WHEN p.`opened` = '0000-00-00' OR p.`opened` IS NULL THEN '2026-01-01 00:00:00' ELSE p.`opened` END
FROM `792088_dtdc2`.`patients` AS p
WHERE (p.`active` = 1 OR p.`active` <> 2)
ON DUPLICATE KEY UPDATE
    `name`          = VALUES(`name`),
    `acc_no`        = VALUES(`acc_no`),
    `opened`        = VALUES(`opened`),
    `closed`        = VALUES(`closed`),
    `location`      = VALUES(`location`),
    `pobox`         = VALUES(`pobox`),
    `town`          = VALUES(`town`),
    `tel`           = VALUES(`tel`),
    `active`        = VALUES(`active`),
    `email`         = VALUES(`email`),
    `occupation`    = VALUES(`occupation`),
    `remarks`       = VALUES(`remarks`),
    `image`         = VALUES(`image`),
    `date_of_birth` = VALUES(`date_of_birth`),
    `p_reserved2`   = VALUES(`p_reserved2`),
    `gender`        = VALUES(`gender`),
    `updated_at`    = NOW();
