SET SESSION sql_mode = '';

-- ─────────────────────────────────────────────────────────────────────────────
-- 08 — Tanya live sync: 792088_tanya_new → 792088_tanya (future runs)
-- Both databases are encrypted with the same APP_KEY so ciphertext
-- copies directly and decrypts correctly in the application.
-- Patients: updateOrCreate by patient_id (PK).
-- Clinical: insert new records only, dedup by (patient_id, date, time_stamp).
-- ─────────────────────────────────────────────────────────────────────────────

-- ── Patients ─────────────────────────────────────────────────────────────────

INSERT INTO `792088_tanya`.`patients` (
    `patient_id`, `name`, `acc_no`, `opened`, `closed`, `location`,
    `pobox`, `town`, `tel`, `active`, `email`, `occupation`,
    `remarks`, `image`, `date_of_birth`, `p_reserved2`, `gender`,
    `created_at`, `updated_at`
)
SELECT
    `patient_id`, `name`, `acc_no`, `opened`, `closed`, `location`,
    `pobox`, `town`, `tel`, `active`, `email`, `occupation`,
    `remarks`, `image`, `date_of_birth`, `p_reserved2`, `gender`,
    `created_at`, `updated_at`
FROM `792088_tanya_new`.`patients`
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
    `updated_at`    = VALUES(`updated_at`);

-- ── Clinical records ─────────────────────────────────────────────────────────

INSERT INTO `792088_tanya`.`patients_clinical` (
    `patient_id`, `patient_id_ver`, `acc_no`, `date`, `diagnostic`, `description`,
    `tooth`, `amount`, `paid`, `balance`, `estimate_description`, `estimate`,
    `estimate_cost`, `estimate_paid`, `estimate_balance`, `notes`, `remarks`, `time_stamp`
)
SELECT
    `patient_id`, `patient_id_ver`, `acc_no`, `date`, `diagnostic`, `description`,
    `tooth`, `amount`, `paid`, `balance`, `estimate_description`, `estimate`,
    `estimate_cost`, `estimate_paid`, `estimate_balance`, `notes`, `remarks`, `time_stamp`
FROM `792088_tanya_new`.`patients_clinical` AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `792088_tanya`.`patients_clinical` AS dest
    WHERE dest.`patient_id` = src.`patient_id`
      AND dest.`date`       = src.`date`
      AND (dest.`time_stamp` = src.`time_stamp`
           OR (dest.`time_stamp` IS NULL AND src.`time_stamp` IS NULL))
);
