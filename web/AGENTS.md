# HiTune Music Distribution (web app) — Project Notes

## Stack / Environment

- Custom PHP app (no framework). Webroot: `/www/wwwroot/web`. Served at `https://distribution.hitune.in` and `https://web.hitune.in`.
- PHP-FPM 7.4 via `php-cgi-74.sock` for this vhost, but PHP 8.2 CLI is available at `/usr/bin/php82` — use it for linting: `/usr/bin/php82 -l file.php`.
- DB: MySQL `web` (credentials in `config.php`).
- Routing: `index.php?q=<route>` → `pages/<route>.php`; admin panel at `/admin/` (separate auth via `admin/config.php` → `requireAdmin()`).

## Unified login

- `includes/sso_sync.php` bridges `web.users` ↔ `musicpro._u_list` (Hitune Music, bcrypt-compatible). Music users can log in here; signups mirror both ways; password reset + email verification sync.
- MySQL grants for user `web`: `SELECT, INSERT` + `UPDATE(password)` on `musicpro._u_list` only.

## Release workflow

- Statuses: `draft → submitted → in_progress → ready → live`, plus `rejected`, `takedown_requested`, `taken_down` (enum on `releases.status`; timestamps: `submitted_at`, `in_progress_at`, `ready_at`, `live_at`, `rejected_at`, `taken_down_at`).
- Admin changes status in `admin/submissions.php` or `admin/release_detail.php` → whitelisted, timestamps set, `activity_log` row inserted, artist emailed via `sendReleaseStatusEmail()` (`includes/email_helper.php`).
- Per-platform delivery: `release_platforms.delivery_status` (`pending|processing|live|failed|taken_down`) + `store_url` + `delivered_at` — admin edits in `release_detail.php` "Selected Platforms" section; artist sees status + store link in `pages/release_view.php` Stores tab.
- NOTE: there is NO automated DSP pipeline — delivery is a managed/manual workflow. Admin marks per-platform status honestly.

## Takedown flow

- Artist submits reason on `release_view.php` (statuses submitted/in_progress/ready/live, CSRF-protected) → status=`takedown_requested`, `takedown_reason` + `takedown_requested_at` set, activity log, confirm email. Admin reviews (takedown requests sort first in `admin/submissions.php`, reason shown in `release_detail.php`) → sets `taken_down` or back to previous status.

## Emails

- `includes/email_helper.php`: `sendEmail()` uses PHPMailer/SMTP if configured else `mail()`; subject is MIME-encoded (Dovecot LMTP rejects SMTPUTF8). Settings from `settings` table (`smtp_*`, `from_email`, `from_name`, `site_url`, `site_title`, `logo_url`). `site_url` = `https://distribution.hitune.in`.
- Mailboxes: noreply@, no-reply@, support@, artists@, business@, aj@hitune.in (Postfix/Dovecot via aaPanel mail plugin).

## Payments

- Gateways: Razorpay + Cashfree (PG v3, `x-api-version: 2023-08-01`, `payment_session_id` + checkout.js). Config from `settings` table (`*_enabled`, `*_test_mode`, keys).
- `pages/checkout.php` creates order + `payments` row (status pending).
- `pages/payment_callback.php`: Razorpay — signature verify + API fetch (`status=captured`); plan resolved from payments row via `getPlanIdByName()` (Razorpay doesn't POST plan_id). Cashfree — NEVER trusts return-URL params; re-fetches order via `GET /orders/{order_id}` and only completes on `order_status=PAID`.
- `pages/payment_webhook.php`: Cashfree webhook — verifies `x-webhook-signature` (base64 HMAC-SHA256 of raw body), then API-confirms before activating. Idempotent (skips already-completed).
- `activateSubscription($userId, $planId, $paymentId)` in `includes/payment_config.php` inserts `subscriptions` row + links payment.

## Royalties & payouts

- `admin/royalties.php`: manual per-track entry + **CSV import** (`royalty_imports` batch table, sha256 file-hash unique → duplicate file blocked; rows matched by `isrc`/`isrc_code` or `track`/`song_title` columns; exact-duplicate rows skipped; per-row error report). Columns recognized: `isrc, track/song_title, platform/store, plays/streams, downloads, revenue, royalty/amount, period`.
- `track_royalties.import_id` links rows to the import batch.
- User `pages/payouts.php`: balance = SUM(track_royalties.royalty_amount) − payouts(pending+approved). Min threshold from `settings.min_payout_threshold`. Inserts `payouts` rows (pending).
- `admin/payouts.php` manages the REAL `payouts` table (pending/approved/rejected + admin_notes). NOTE: `withdrawal_requests` table is legacy/orphaned — not used.

## ISRC/UPC auto-assign

- `includes/code_assign.php` — gated by `settings.auto_assign_codes` ('1' = on). Runs when admin sets status `ready`/`live`.
- ISRC: `isrc_prefix` setting (default `INHIT` = IN + HIT registrant) + year(2) + 5-digit designation, uniqueness-checked. Stored in `release_tracks.isrc_code`.
- UPC: random 12-digit UPC-A with valid check digit, uniqueness-checked. Stored in `releases.upc_code`.

## Security notes

- CSRF: `includes/csrf.php` (`csrfField()`, `validateCsrfToken()`) — use on every POST form.
- Debug/setup files removed from docroot → `/www/wwwroot/_removed_debug/` (debug_google.php, info.php, update_auth_schema.php, setup_*.sql). Keep new debug files OUT of the docroot.

## PHPMailer bundled (2026-09-28)

- PHPMailer 6.9.3 classes copied to `includes/phpmailer/` and auto-required at top of `email_helper.php` — SMTP path now actually works (previously always fell back to `mail()`).
- `settings.smtp_host` = `mail.believoo.com` (port 465, ssl, `no-reply@hitune.in`). `CharSet=UTF-8` set for emoji-safe subjects.
- Google login uses shared OAuth client `759752024135-...`; redirect URIs `https://distribution.hitune.in/index.php?q=google_callback` and `https://web.hitune.in/index.php?q=google_callback` must be whitelisted in Google console.

## Session cookie collision fix (2026-09-29)

- `music.hitune.in` sets `session.cookie_domain=.hitune.in` with default `PHPSESSID` name — that cookie is sent to ALL `*.hitune.in` subdomains. Browsers send both `PHPSESSID` cookies to distribution; PHP reads the first → web app login silently failed (session regenerated under a cookie the browser never used → infinite login loop).
- **Fix**: `/www/wwwroot/web/.user.ini` (aaPanel-managed, `chattr +i` — remove flag before editing, restore after) now sets `session.name=HWDIST_SESSID` + `secure` + `HttpOnly` + `SameSite=Lax`. This covers every `session_start()` in the app incl. `admin/`. Never revert to `PHPSESSID` on this host.
- After editing `.user.ini`, restart `php-fpm-74` (`systemctl restart php-fpm-74`) — `user_ini.cache_ttl=300`.
