# dental-ars — Project Instructions

## System Overview

Two-repo Laravel 12 dental clinic system for Dr. Tanja:
- **dental-data-refactored** — Mint clinic, Dar es Salaam, MSI laptop
- **dental-ars** — Tanya clinic, Arusha, Lenovo laptop (this repo)

Both repos share the same codebase pattern. Features developed on Mint are synced to Tanya manually.

## Key Facts

- **Roles:** `admin`, `doctor`, `nurse` — no other roles are whitelisted in middleware
- **Encryption:** Patient fields use Laravel `encrypted` cast. Never use raw SQL on encrypted columns — always use Eloquent
- **Estimates password:** stored in `.env` as `ALL_RECORDS_KEY`, accessed via `config('app.all_records_key')` — never `env()` directly
- **After any deploy:** run `php artisan config:clear && php artisan view:clear && php artisan route:clear`
- **After CSS or new blade files:** run `npm run build` on the target machine
- **PHP version:** pin to 8.2 — PHP 8.5 has compatibility issues with this stack
- **Storage files:** `storage/app/reports/bank_details.md` and `signatures.md` are not git-tracked — must be created manually on each machine and owned by `www-data`

## Session Tracking — EXIT Command

When the user types **EXIT**, perform the following steps **in order** before confirming:

1. **Create a new session file** at `tools/sessions/session_XX.md` where XX is the next increment from the last file in that folder. Structure:
   - `## Brought Forward` — open tasks carried from the previous session file, each marked `[x]` done or `[ ]` still open
   - `## Done This Session` — everything completed during this session
   - `## Remaining / Next Session` — open items and new tasks for next time

2. **Update `log/changelog.md`** if any code changes were made this session that are not yet logged there.

3. **Update `version.md`** in the workbench root (`claude_workbench/version.md`) with a row for this session.

4. **Update memory files** in `.claude/projects/.../memory/` — add or update any project, feedback, or user memories that reflect new information from this session. Update `MEMORY.md` index if new files were added.

5. **Confirm to the user** that all updates are done and the session is ready to close.

Only after the user receives confirmation should they consider the session closed.

## Tools Folder Structure

```
tools/
  sessions/   — incremental session logs (session_01.md, session_02.md, ...)
```

## Git Rules

- Never force-push
- Always ask before committing
- Commit messages in plain English, present tense
- Run `git status` before starting work

## Cross-repo Sync

Changes made here should be reviewed for applicability to `dental-data-refactored` and applied in tandem where relevant. Check `dental-data-refactored/tools/sessions/` for shared infrastructure tasks.
