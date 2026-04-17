# dental-ars (Arusha / Tanya) Change Log

| date | time | area | files changed | description | notes |
|------|------|------|--------------|-------------|-------|
| 2026-04-15 | session | Estimate records visibility | config/app.php, app/Http/Controllers/PatientController.php, resources/views/patients/show.blade.php | Bug fix: Estimate records not showing when access key entered. Four issues: (1) ALL_RECORDS_KEY registered in config/app.php so it survives config cache; (2) controller showAllRecords() changed from env() to config() call; (3) flash error block added to show.blade.php; (4) $hasAnyEstimates and $hasEstimateData extended to include estimate and estimate_paid fields. Config cache rebuilt. | Primary cause: cached config breaking env() call |
| 2026-04-15 | session | Clock widget colours | resources/css/app.css, resources/views/layouts/app.blade.php | Changed clock background, border, digit text, toggle button and icon from green to blue shades matching Arusha palette (THEME_COLOR #3b82f6). Fixed hardcoded green Login button to blue. | 6 colour values across 2 files |
