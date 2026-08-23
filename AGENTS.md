# AGENTS.md

## Cursor Cloud specific instructions

Choosology is a legacy flat-file PHP web application (interactive "choose your own adventure" authoring site). Files are served directly (e.g. `index.php`, `home.php`, `view.php`); there is no build step, no framework, and no dependency manager (JS libraries are vendored under `scripts/`). Stack: PHP 8.3 CLI + `mysqli` + **GD** extensions, MariaDB 10.11 (MySQL 8 compatible), served in dev via PHP's built-in server.

**PHP GD (`php8.3-gd`) is required for image uploads.** Without it, `ajax/uploadresource.php` refuses uploads (older builds fell back to copying the full image into `thumbs/`, which made UI icons enormous). If thumbs were already created without GD, rebuild them with `php scripts/regenerate_thumbs.php` (optional `--user=SomeName`).

### Services

| Service | How to run (dev) | Notes |
| --- | --- | --- |
| MariaDB (database `choosology`) | Start via `sudo mysqld_safe` in a tmux session; connect check: `sudo mariadb -e "SELECT VERSION();"` | No systemd in the container, so `service`/`systemctl` do not work. The data dir and `choosology` DB (imported schema + a local `choosology`/`choosology` TCP user) persist in the VM snapshot; you only need to (re)start the daemon. |
| PHP dev web server | `php -S 0.0.0.0:8000 -t /workspace` in a tmux session, then open `http://127.0.0.1:8000/index.php` | Runs the whole app; no build needed. PHP fatal errors/warnings print to the server's stderr (the tmux pane), not a file. |

Both services are long-running: start each in its own tmux session (e.g. `mariadb`, `php-dev`) so they survive across commands. Do not put service startup in the update script.

### Database

- Connection config resolves in `db-config.php` as: defaults → `connect.local.php` (git-ignored) → `CHOOSOLOGY_DB_*` env vars. Local dev uses `connect.local.php` pointing at `127.0.0.1` with user/password `choosology`/`choosology`. If `connect.local.php` is missing, recreate it from `connect.local.php.example` (host `127.0.0.1`, user/pass `choosology`).
- `connect.php` dies with HTTP 503 if the DB is unreachable — if pages 503, MariaDB is not running.
- Schema lives in `choosology-schema.sql` but it is **UTF-16 LE**; import it converted, e.g. `iconv -f UTF-16LE -t UTF-8 choosology-schema.sql | sudo mariadb choosology`. Optional seeds: `sql/news_setup.sql` (news articles) and `sql/updates_setup.sql` (one-line patch notes for the Home/News feeds).
- Uploaded pictures/resources are written to the filesystem under `storage/pics` (configured via `pics_root` in `connect.local.php`); this dir is git-ignored.

### App behavior gotchas

- Signup is available from the login box via **Apply for lab access** (modal → `ajax/signupchallenge.php` + `ajax/signup.php`). Successful signup logs the user in immediately. Optional columns `newsletter` / `welcome_pending` are added automatically (or via `sql/signup_setup.sql`). Welcome mail uses PHP `mail()` when available; otherwise `welcome_pending` stays set for a later send.
- End screens (no valid outgoing choices) render a lab-styled **ending panel** (`choosology_build_ending_panel_html`) with rating + comments, a count of end screens catalogued this visit/account, and a yes/no note on whether more endings exist (total count is never shown). Logged-in finds persist in `ending_finds` (`sql/ending_finds_setup.sql`); anonymous finds use the PHP session.
- Passwords are stored as `md5("cYo" . password)` — legacy/insecure, but that is the current scheme.
- Core end-to-end flow to sanity-check the app: log in (posts to `ajax/authentajax.php`) → My Stuff → Experiments → create a new experiment (posts JSON to `ajax/newadventure.php`, which inserts an `advs` row + first `advscreens` row) → graph editor opens at `#/edit/<id>`.
- **Messages** use the existing `messages` table. Inbox UI: My Stuff → Messages (`mystuff/messages.php` + `ajax/messages.php`). Header **Msg** badge opens a slim recent-messages modal. Comment notifications fire for public adventures via `checkSendMessage()`. Optional per-adventure digests (`advs.digest_notify` = `off|daily|weekly`, migration `sql/messages_digest_setup.sql`) are sent opportunistically on inbox load and via `php scripts/run_digests.php`. Reports go to users with `usertype >= 1`.
- The editor and some UI pull JS/CSS from public CDNs (jQuery UI, jQuery Cycle, Konva, minicolors) at runtime; the page shell works offline but the editor needs outbound internet.

### Lint / test / build

- No linter, test suite, or build system is configured. For a syntax "lint", run `php -l` over the PHP files (all files outside `oldstuff/` currently pass), e.g. `find . -name '*.php' -not -path './oldstuff/*' -exec php -l {} \;`.
