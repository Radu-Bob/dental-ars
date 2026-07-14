# Session 06 — 2026-06-28 (Xeon dev environment setup)

## Brought Forward

From session_05 Remaining / Next Session (all unchanged):

- [ ] Deploy to Dar production (HP EliteBook, `/var/www/html/dental-mint`)
- [ ] Deploy to Arusha production (Lenovo ideapad-slim, `/var/www/html/dental-ars`)
- [ ] Full end-to-end test on Arusha — Invoice print + Clinical Report print
- [ ] Apply `insurance_provider_id` SQL on Arusha phpMyAdmin (if not already done)
- [ ] Test Statistics Month report on Arusha
- [ ] Swappiness — apply permanently on Arusha
- [ ] Prescription print layout decision
- [ ] Cross-clinic DB sync (Skopje hub)
- [ ] Awardspace mirror — decide purpose
- [ ] Backup/remote-access plan — implementation still pending
- [ ] Appointment module — SMS reminders / auto-link new patient (deferred v1 scope cuts)

## Done This Session

Xeon server setup only — no application code changed in this repo.

- `.env` `APP_URL` updated to Xeon Tailscale IP (`http://100.93.166.88:8001`); localhost line kept commented
- Caches cleared, `storage:link` run, permissions fixed
- DB connection verified (`DB OK`)
- `node_modules` binary permissions restored; `npm run build` completed successfully
- `tools/logs/` folder created
- Both apps confirmed accessible from Inspiron via Tailscale at the end of the session

## Remaining / Next Session

Carried forward (unchanged from session_05):
- [ ] Deploy to Dar production
- [ ] Deploy to Arusha production
- [ ] Full end-to-end test on Arusha — Invoice print + Clinical Report print
- [ ] Apply `insurance_provider_id` SQL on Arusha phpMyAdmin
- [ ] Test Statistics Month report on Arusha
- [ ] Swappiness — apply permanently on Arusha
- [ ] Prescription print layout decision
- [ ] Cross-clinic DB sync (Skopje hub)
- [ ] Awardspace mirror — decide purpose
- [ ] Backup/remote-access plan — implementation still pending
- [ ] Appointment module — SMS reminders / auto-link (deferred)

Newly tracked (see dental-data-refactored session_06 for details):
- [ ] Tablet/phone UI — dedicated mobile blades
- [ ] Appointments → Baikal CalDAV backend (replaces current in-app module; failsafe on Awardspace)
