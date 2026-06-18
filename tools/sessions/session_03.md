# Session 03 — next session

## Brought Forward

From session_02 Remaining / Next Session:

- [ ] Full end-to-end test on Arusha — Invoice print + Clinical Report print (permissions fixed; flows not yet fully tested)
- [ ] Apply `insurance_provider_id` SQL on Arusha phpMyAdmin (if not already done):
  ```sql
  ALTER TABLE `patients_clinical`
  ADD COLUMN `insurance_provider_id` INT NULL AFTER `is_insurance_claim`;
  ```
- [ ] Test Statistics Month report on Arusha
- [ ] Swappiness — apply permanently on Arusha:
  ```bash
  sudo sysctl vm.swappiness=15
  echo 'vm.swappiness=15' | sudo tee -a /etc/sysctl.conf
  ```
- [ ] Prescription print layout decision — different visual style vs Invoice?
- [ ] Cross-clinic DB sync (Skopje hub) — design and scripts
- [ ] Awardspace mirror — decide purpose (DR vs read-only portal)
- [ ] Task 2b — Reports: Patients Attending — design and build

## Done This Session

## Remaining / Next Session
