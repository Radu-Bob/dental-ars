# Session 05 — 2026-06-19

## Brought Forward

From session_04 Remaining / Next Session (all still open):

- [ ] **Deploy to Dar production** (HP EliteBook, `/var/www/html/dental-mint`) — follow `deployment_runbook_2026-06-19.md`
- [ ] **Deploy to Arusha production** (Lenovo ideapad-slim, `/var/www/html/dental-ars`) — follow same runbook
- [ ] Full end-to-end test on Arusha — Invoice print + Clinical Report print
- [ ] Apply `insurance_provider_id` SQL on Arusha phpMyAdmin (if not already done)
- [ ] Test Statistics Month report on Arusha
- [ ] Swappiness — apply permanently on Arusha
- [ ] Prescription print layout decision — different visual style vs Invoice?
- [ ] Cross-clinic DB sync (Skopje hub) — design and scripts
- [ ] Awardspace mirror — decide purpose (DR vs read-only portal)
- [ ] Backup/remote-access plan — still just a plan, no implementation; open decisions: backup interval, retention policy, encryption tool, who else needs remote access
- [ ] Appointment module — decide if SMS/patient-facing reminders or one-click "register new patient → auto-link back to appointment" are wanted later (both explicitly deferred as v1 scope cuts)

## Tasks Added This Session

- [x] Diagnose `npm run build` EACCES failure on Dar (Mint) laptop during deploy
- [x] Update `deployment_runbook_2026-06-19.md` with the fix, for both Dar and Arusha
- [x] Record the fix in persistent memory (`project_deploy_checklist.md`)

## Done This Session

- Dar (Mint) deploy hit `npm run build` → `EACCES: permission denied, open '.../vite.config.js.timestamp-*.mjs'` after the runbook's `sudo chown -R www-data:www-data` step ran before the build, leaving the `clinic` login user without write access to the project root that vite needs for its temp config file.
- Fix applied conceptually (not run by me — guidance given for the user to run on the laptop): reclaim ownership for `clinic`, run `npm run build`, then chown only `storage/`/`bootstrap/cache/` back to `www-data`.
- Updated `claude.workbench/deployment_runbook_2026-06-19.md`:
  - Inline EACCES troubleshooting block added under the "Rebuild front-end assets" step for both Dar and Arusha.
  - Added a bullet to the "If anything goes wrong" section pointing at the fix.
  - Arusha block uses the actual login username `clinic` (confirmed same on both clinics, different passwords) rather than a placeholder.
- Updated memory `project_deploy_checklist.md` with a permanent entry: both clinics share the `clinic` login user; documented the EACCES root cause and recovery commands; cross-referenced the runbook.
- No application code was changed this session — no `log/changelog.md` entry needed (the existing uncommitted diff in that file is leftover from session_04, not from today).

## Remaining / Next Session

Carried forward unchanged from session_04 (none of today's work resolved or touched these):

- [ ] **Deploy to Dar production** (HP EliteBook, `/var/www/html/dental-mint`) — follow `deployment_runbook_2026-06-19.md`, including the new EACCES fix note if `npm run build` fails again
- [ ] **Deploy to Arusha production** (Lenovo ideapad-slim, `/var/www/html/dental-ars`) — follow same runbook
- [ ] Full end-to-end test on Arusha — Invoice print + Clinical Report print
- [ ] Apply `insurance_provider_id` SQL on Arusha phpMyAdmin (if not already done)
- [ ] Test Statistics Month report on Arusha
- [ ] Swappiness — apply permanently on Arusha
- [ ] Prescription print layout decision — different visual style vs Invoice?
- [ ] Cross-clinic DB sync (Skopje hub) — design and scripts
- [ ] Awardspace mirror — decide purpose (DR vs read-only portal)
- [ ] Backup/remote-access plan — still just a plan, no implementation
- [ ] Appointment module — SMS reminders / auto-link new patient registration to appointment (deferred v1 scope cuts)
