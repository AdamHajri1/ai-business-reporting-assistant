# Project Progress

## Current step: Phase 8 completed — Project Packaged & Portfolio-Ready
Last confirmed result: `start.ps1`, `samples/demo_sales.csv`, product-name guardrail fix, and `README.md` verified; all 18 `pytest` tests passing and PHP lint clean.
Blocking issue (if any): None.

### Phase 0 — Environment
- [x] PHP: exists / version / works (PHP 8.5.6)
- [x] PHP extensions needed verified (`pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `openssl`, `json`, `session`)
- [x] MySQL: exists / version / connection works (MySQL 8.0.46, service `MySQL80`)
- [x] Python: exists / version / pip works (Python 3.14.2, pip 25.3)
- [x] Git: exists / version / identity configured (Git 2.56.0)
- [x] IDE terminal ready; decisions on gaps recorded (PHP extensions enabled in `php.ini`)

### Phase 1 — Foundation
- [x] Repository + `.gitignore` + `.env.example`
- [x] Folder structure created
- [x] PHP front controller runs (`php-app/public/index.php`)
- [x] Config loads from environment (`php-app/config/config.php`)
- [x] Python venv created; FastAPI `/health` OK (FastAPI 0.165.0, Uvicorn 0.54.0)
- [x] MySQL connection verified from PHP (`php-app/app/Core/Database.php`)

### Phase 2 — Database & CSV import
- [x] Schema scripts applied (`database/schema/001_initial_schema.sql`)
- [x] Registration/login (basic) works
- [x] Upload (limits, private storage) works
- [x] `/validate` works; preview shown; confirm works
- [x] Invalid-file cases tested

### Phase 3 — Analytics engine
- [x] `pytest` (9.2.0) and `httpx` (0.28.1) installed and pinned in `requirements.txt`
- [x] Pandas KPI engine built (KPIs, monthly trends, categories, top products, period comparison)
- [x] `POST /analyze` exposed and verified with 9 passing `pytest` tests
- [x] PHP analytics view and date-range filtering verified in the browser

### Phase 4 — AI executive summaries & saved reports
- [x] Deterministic executive summary engine (`summary_engine.py`) and `POST /summarize` built
- [x] 12 `pytest` tests passing (KPI engine, `/analyze`, `/summarize`)
- [x] `Report.php` model, `ReportController.php`, and `/reports/show` implemented
- [x] Report generation, MySQL persistence, and dashboard listing verified in the browser

### Phase 5 — SaaS UI, charts & polish
- [x] Shared SaaS stylesheet (`php-app/public/assets/css/app.css`) and layout partials (`header.php`, `footer.php`) built
- [x] Auth pages (`login.php`, `register.php`), Dashboard (`home.php`), and Validation Preview (`dataset_preview.php`) styled
- [x] Analytics (`dataset_analytics.php`) and Report (`report_show.php`) upgraded with KPI cards and Chart.js visualizations
- [x] Print / Save PDF styling (`@media print`) and duplicate-report prevention with warning alert verified

### Phase 6 — LLM
- [x] API key in environment only
- [x] Prompt + grounding rules
- [x] AI panel with facts / interpretations / recommendations
- [x] Number-check against verified metrics

- [x] **Phase 7 — Security Hardening, Reports Refinement & Responsive Polish**
  - Added HTTP security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy` with `cdn.jsdelivr.net` support) in `php-app/public/index.php`.
  - Enforced strict session cookie flags (`use_strict_mode`, `HttpOnly`, `SameSite=Lax`) in `php-app/app/Core/Session.php`.
  - Audited all controllers and models to confirm CSRF verification on every `POST` action and `user_id` scoping on every `Dataset` and `Report` query.
  - Refined `@media print` (A4 layout, color preservation, `break-inside: avoid`, hidden UI chrome) and added `@media (max-width: 768px)` responsive layout rules in `php-app/public/assets/css/app.css`.

- [x] **Phase 8 — Packaging, Guardrail Refinement & Portfolio Documentation**
  - Added one-click Windows launcher (`start.ps1`) to boot FastAPI (`:8000`) and PHP (`:8080`) together.
  - Created a realistic 4-month demo dataset (`samples/demo_sales.csv`) matching the 6-column validator schema.
  - Patched `verify_numbers_against_metrics()` in `analytics-service/app/services/llm_service.py` to strip known product/category labels before numeric extraction, eliminating false positives on names like `ProBook 14 Laptop` and `27-inch 4K Monitor`.
  - Wrote a complete, portfolio-ready `README.md` covering architecture, grounded AI guardrails, security hardening, setup steps, and testing.