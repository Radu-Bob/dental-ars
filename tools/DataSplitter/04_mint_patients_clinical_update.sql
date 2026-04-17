SET SESSION sql_mode = '';

-- ─────────────────────────────────────────────────────────────────────────────
-- 04 — Mint clinical sync: dtdc2 → 792088_mint_decrypted
-- INSERT only — clinical records are immutable events.
-- Dedup by (patient_id, date, time_stamp) — no date cutoff.
-- reserved1 → estimate_description. 0000-00-00 → 1970-01-01.
-- Both sides are plaintext so all comparisons work correctly.
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `792088_mint_decrypted`.`patients_clinical` (
    `patient_id`, `patient_id_ver`, `acc_no`, `date`, `diagnostic`, `description`,
    `tooth`, `amount`, `paid`, `balance`, `estimate_description`, `estimate`,
    `estimate_cost`, `estimate_paid`, `estimate_balance`, `notes`, `remarks`, `time_stamp`
)
SELECT
    (src.`patient_id` + 2000000),
    src.`patient_id_ver`,
    src.`acc_no`,
    CASE WHEN src.`date` = '0000-00-00' THEN '1970-01-01' ELSE src.`date` END,
    src.`diagnostic`,
    src.`description`,
    src.`tooth`, src.`amount`, src.`paid`, src.`balance`,
    src.`reserved1`,
    src.`estimate`, src.`estimate_cost`, src.`estimate_paid`, src.`estimate_balance`,
    src.`notes`, src.`remarks`, src.`time_stamp`
FROM `792088_dtdc2`.`patients_clinical` AS src
JOIN `792088_dtdc2`.`patients` AS p ON src.`patient_id` = p.`patient_id`
WHERE p.`active` = 2
  AND NOT EXISTS (
      SELECT 1 FROM `792088_mint_decrypted`.`patients_clinical` AS dest
      WHERE dest.`patient_id` = (src.`patient_id` + 2000000)
        AND dest.`date` = CASE WHEN src.`date` = '0000-00-00' THEN '1970-01-01' ELSE src.`date` END
        AND (dest.`time_stamp` = src.`time_stamp`
             OR (dest.`time_stamp` IS NULL AND src.`time_stamp` IS NULL))
  );
