# Testing

Choosology did not historically ship a test suite. This tree adds:

- **PHPUnit** (PHP unit + integration)
- **Jest** (JS unit tests for pure helpers)

## Prerequisites

- PHP 8.1+ with `mysqli` (and `gd` for upload-related code paths)
- Composer (`composer` on PATH, or `php /path/to/composer`)
- Node.js 18+ and npm
- For integration tests: MariaDB with a `choosology_test` database (see below)
- For HTTP smoke tests: PHP built-in server on `0.0.0.0:8000`

## Install

```bash
composer install
npm install
```

## Run PHP tests

```bash
# All suites
./vendor/bin/phpunit

# Unit only (no DB required)
./vendor/bin/phpunit --testsuite Unit

# Integration (DB + optional HTTP)
bash tests/bin/prepare-test-db.sh
./vendor/bin/phpunit --testsuite Integration
```

Integration tests use database `choosology_test` (credentials from `connect.local.php` / `CHOOSOLOGY_DB_*`). They skip automatically if the DB is unreachable. HTTP smoke tests skip if nothing listens on port 8000.

## Multi-step workflows (Classic + Lite)

Workflow paths exercise end-to-end tasks over HTTP against the running app (same DB the PHP server uses). They record **experiential metrics** (`clicks`, `elapsed_ms`, per-step timings) and assert success for both modes.

```bash
# PHPUnit (skips if :8000 down or no public branching adventure)
./vendor/bin/phpunit --testsuite Workflow

# Or via Integration suite (includes WorkflowPathsTest)
./vendor/bin/phpunit --testsuite Integration --filter WorkflowPaths

# CLI metrics report (JSON)
php tests/bin/run-workflows.php
php tests/bin/run-workflows.php --out=/tmp/workflow-metrics.json
php tests/bin/run-workflows.php --only=lite_login_browse_play_ending,classic_login_play_ending
```

| Workflow id | Mode | Path |
| --- | --- | --- |
| `lite_login_browse_play_ending` | Lite | Login → Browse → open experiment → two choices → ending |
| `classic_login_play_ending` | Classic | AJAX login → shell → view → screenajax middle → ending |
| `lite_login_ledger` | Lite | Login → DataScrip Ledger (glyph image) |
| `classic_login_ledger` | Classic | AJAX login → My Office Ledger fragment |
| `mode_switch_roundtrip` | Both | Lite login → Classic switch → Lite switch |
| `lite_structure_inspect` | Lite | Login → play → ASCII structure → back |

Seed a public branching adventure first if needed (`php scripts/seed_demo_metrics.php`). Default credentials: `labtester` / `labpass`.

## Run JS tests

```bash
npm test
```

## What is covered

| Area | Suite |
| --- | --- |
| Font catalog / typography helpers | PHP Unit |
| DB/path config resolution | PHP Unit |
| Ending detection, HTML sanitize, email/password helpers | PHP Unit |
| Account sanitize, resource size/mime, pic filters, news/feed pure helpers | PHP Unit |
| Messages send/unread/report | PHP Integration |
| Ending finds (anon + logged-in) | PHP Integration |
| index.php + signup challenge + bad login | PHP HTTP smoke |
| Classic/Lite multi-step workflows + click/time metrics | PHP Workflow / Integration |
| `scripts/lib/choosology-utils.js` (count, stripTags, CLIC badge, URLs, titles) | Jest |
| `scripts/routes.js` route string presence | Jest |

## Out of scope (for now)

- Full Konva/graph editor (`vised/`)
- TinyMCE / CDN editor chrome
- Full Sammy SPA navigation in jsdom
- File-upload binary paths (require GD + multipart fixtures)

Pure helpers live under `lib/` (PHP) and `scripts/lib/` (JS) so they can be tested without bootstrapping `connect.php`.
