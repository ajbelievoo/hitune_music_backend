# music.hitune.in Project Notes

## Developer API Implementation (2026-09-25)

HiTune Developer API (Spotify-style third-party integration) has been implemented.

### What's implemented
- Database tables: `_dev_apps`, `_dev_plans`, `_dev_tokens`, `_dev_usage`, `_dev_rate`
- Developer API class: `plugins/bof_tool_hitune_extras/classes/class_developer_api.php`
- Signed portal endpoints: `developer_plans`, `developer_apps`, `developer_app_create`, etc.
- Public v1 endpoint files: `api/app/client/endpoints/v1/*.php` (all 14 endpoints)
- Admin endpoints: `be/developer_*` (8 endpoints)
- Client config flag: `setting.developer_portal`

### Routing status
- **Working:** `/api/v1/ping` — returns JSON health check via BOF with empty groups
- **Issue:** `/api/v1/catalog` and `/api/v1/search` return 403
  - Cause: legacy endpoint URL conflicts in BOF matcher
  - All endpoint code is correct; this is a framework-level routing issue
- **Workaround:** Currently using empty groups in endpoint registration to bypass BOF signature checks
- **Recommended:** Move public v1 API to a dedicated subdomain (e.g., `api-dev.hitune.in`) or refactor BOF endpoint matching to allow explicit opt-out

### Flutter side
- Developer portal module exists in `lib/features/developer/`
- Gated by `setting.developer_portal` from backend
- App creation, key management, plan selection, usage display all implemented
- Docs in `docs/DEVELOPER_API.md`

### Licensing reminder
Full-track streaming access is restricted to Pro/Enterprise plans. Do not enable unrestricted full-track access for all plans until content licensing is confirmed.

## Tech Stack

- Custom PHP framework **BusyOwlFramework (BOF)** in `/BOF/`.
- Entry points:
  - Public site: `index.php`
  - Admin panel: `admin/index.php`
  - API: `api/index.php` (if present) or routed through `index.php`
- Database: MySQL/MariaDB (`musicpro` database).
- Web server: Apache with `.htaccess` rewrites (root `/.htaccess`).
- PHP: FPM 8.2 (`/www/server/php/82`).

## Configuration

- Secrets live in `/.env` (root file). **Do not commit this file.**
- `/.env.example` is the template.
- `api/app/env_loader.php` parses `.env` into `$_ENV` / `getenv()`.
- `api/app/config_user.php` and `api/app/config_license.php` load constants from `.env`.
- Database, licensing, VAPID, and feature toggles are all env-driven.
- `.env` permissions should be `640`, group `www`.

## Security

- `.htaccess` denies access to `.env` files.
- `session.cookie_domain` is `.hitune.in`.
- `production` is now `true` by default (loaded from `.env`).
- `admin_ip_lock`, `session_ip_lock`, `admin_ua_lock` are configurable from `.env`.

## Important Files

- `api/app/config.php` — bootstrap constants.
- `api/app/config_user.php` — user/runtime settings.
- `api/app/config_license.php` — license and signing keys.
- `api/app/env_loader.php` — .env parser.
- `BOF/loader.php` — framework loader.
- `protector.php` — protected file delivery with DB access checks.

## Commands

- PHP syntax check: `/usr/bin/php82 -l file.php`
- Test env load: `/usr/bin/php82 -r "require 'api/app/config.php'; echo web_address;"`
- Health check: `curl https://music.hitune.in/health.php`
- Web root: `/www/wwwroot/music`
- Service/PHP-FPM: `php82` pool at `/www/server/php/82`.

## Health & Maintenance

- `/health.php` returns JSON with `php`, `env`, `database`, and `files` checks.
- Set `MAINTENANCE=true` in `/.env` to put public site, admin, and API into 503 maintenance mode.
- `maintenance.html` is the branded maintenance page.
- Database backups: `/usr/bin/php82 scripts/backup.php` creates a gzipped dump in `/www/wwwroot/backups/music/` with 14-day retention.
- Daily backup cron: `/etc/cron.d/believoo-ecosystem`.

- `/health.php` returns JSON with `php`, `env`, `database`, and `files` checks.
- Set `MAINTENANCE=true` in `/.env` to put public site, admin, and API into 503 maintenance mode.
- `maintenance.html` is the branded maintenance page.
- Database backups: `/usr/bin/php82 scripts/backup.php` creates a gzipped dump in `/www/wwwroot/backups/music/` with 14-day retention.

## Notes

- The previous `INVALID_COMMAND_TO_TEST_HTACCESS` line in `.htaccess` was removed to avoid 500 errors.
- Hardcoded DB password and license keys were moved from `config_user.php`/`config_license.php` into `/.env`.
- Do not put real secrets back into PHP config files.

## App feature endpoints (BACKEND_REQUIREMENTS.md)

New client endpoints registered in `api/app/client/_setup/endpoints.php`
(all POST, BOF-signed, under `/api/<name>`):

- `track_lyrics`, `radios`, `recommendations`, `daily_mix`,
  `recommendations_because`, `comments` (group `api`, guest-safe)
- `comment_add`, `comment_delete`, `playlist_add`, `playlist_rename`,
  `playlist_delete`, `playlist_reorder`, `playlist_collab`,
  `notifications`, `notification_mark_read`, `notification_mark_all`,
  `push_register`, `verify_purchase`, `user_delete` (group `user`, auth)
- `playlist_create` now returns `playlist_hash` and accepts creation
  without an initial object; `playlist_remove` is dual-mode
  (item removal with `object_type`+`object_hash`, whole-playlist delete
  otherwise — owner only).

Admin-config knobs (all `_bof_setting` / plan `data` JSON):

- `plan_features` (override map) or per-plan `features` JSON on
  `_u_subs_plans`; plan key comes from `data.plan_key`.
- `plan_features_free` — features granted to the free plan.
- `iap_enabled`, `default_plan` surface in `client_config`.
- IAP product mapping: `_u_subs_plans.data.iap` or
  `data.iap_product_id`; `iap_strict` setting rejects unverifiable
  receipts; Google verification needs `iap_google_package` + a service
  account JSON at `api/app/google-play-service-account.json`;
  Apple needs `iap_apple_shared_secret`.
- `radio_stations` — JSON array `[{name, url, image, sub_title}]`
  served by `/api/radios`.
- `subs_start_trial` endpoint (group `user`, POST `hash`=<plan hash>):
  grants `data.trial_days` of a paid plan once per user per plan
  (`_u_subs` row with `gateway_name=trial`, `subs_plan_time_range=trial`).
  Admin Features tab on a plan edits `plan_key`, `trial_days`,
  `iap_<period>` product IDs, and feature checkboxes — all stored in
  `_u_subs_plans.data` / `.features` JSON. `user_subs` payload exposes
  `plan_key`, `trial_days`, `features` per plan.
- New API error/message strings live in `_d_languages_items`
  (`lang_code2 = en`); `turn()` returns `false` for missing hooks,
  which surfaces as `"messages": [false]` — always add a hook.

Schema changes live in `scripts/migration_2026_features.sql`
(`_u_comments`, `_u_iap_receipts`, `_c_m_tracks.lufs`/`peak_db`).

Loudness: `music->track_loudness()` resolves LUFS/peak lazily (max 2
on-demand ffmpeg measurements per request); bulk backfill via
`/usr/bin/php82 scripts/measure_loudness.php [--limit=N] [--remeasure]`.

Testing trick: `x-bof-platform` must be `web` or `mobile`
(`supported_platforms`). With `mobile`/`android`/`ios`,
`request_extend::insert_log` requires all `x_bof_device_*` headers or it
`die`s silently (empty 200). For smoke tests use `platform: web` and a
signed POST (`bof_signature` = md5(hmac_sha256(query, SIGN_KEY))).
- # HiTune Music - Agent Notes

## Build & Run

This project uses Flutter 3.29.3 with Dart 3.7.2.

### Environment setup

1. Copy `.env.example` to `.env` and fill in real values:
   - `HITUNE_SIGN_KEY` and `HITUNE_ADMIN_SIGN_KEY` from the admin panel.
   - Optionally override AdMob IDs.
2. Run using the wrapper script or launch configuration:
   - Windows: `run.bat`
   - macOS/Linux: `run.sh`
   - VS Code: use the `hitune_music` launch configuration (already provided in `.vscode/launch.json`).
   - Manual: `flutter run --dart-define-from-file .env`

### Android build

The project uses Gradle 8.14 with the JDK at `C:\jdk21\jdk-21.0.5+11`. This is configured in `android/gradle.properties`.

```bash
flutter build apk --debug
```

### Important file layout

- `lib/core/config/app_config.dart` reads sign keys and feature flags from `--dart-define`.
- `lib/core/utils/app_logger.dart` is the central logger. It only emits logs in debug mode.
- `lib/core/network/api_service.dart` no longer logs full headers, cookies, or signatures.
- `.env` is gitignored and must not be committed.

### Feature gating & subscriptions

- `lib/features/subscription/subscription_service.dart` reads `plan_features`
  and the user's plan from `client_config`. Feature keys live in `AppFeatures`.
- `lib/features/subscription/feature_gate.dart` exposes `FeatureGate.require(...)`
  and `FeatureGateWidget` to lock premium features.
- Until the backend sends `plan_features`, the service is **fail-open** (all
  features allowed). Configure it before production.
- Backend contract for every new feature lives in `docs/BACKEND_REQUIREMENTS.md`.

### New feature modules

- `lib/features/downloads/` - offline downloads (path_provider + http stream).
- `lib/features/player/queue_sheet.dart`, `sleep_timer_sheet.dart`,
  `sound_controls_sheet.dart`, `lyrics_screen.dart`, `track_actions_sheet.dart`
  - player extras (speed/pitch via just_audio; EQ via `core/audio/audio_enhancer.dart`).
- `lib/features/notifications/` - in-app notification inbox.
- `lib/features/comments/` - comments sheet.
- `lib/features/home/made_for_you_section.dart` - recommendations rail
  (renders only when backend returns items).
- `lib/core/l10n/` - minimal localization (en/hi) via `LocaleService`.
- `lib/core/network/connectivity_service.dart` + `lib/core/ui/offline_banner.dart`
  - offline indicator in `app_shell.dart`.
- `lib/core/cast/cast_service.dart` - casting facade (native Cast SDK pending).
- `lib/features/premium/purchase_service.dart` - IAP facade (store products pending).

### Analysis

```bash
flutter analyze --no-pub
```

## Known remaining issues

- Some `BuildContext` async gaps are not fully guarded yet (`use_build_context_synchronously` warnings).
- Large widgets (`artist_screen.dart`, `home_screen.dart`, `player_service.dart`) should be split into smaller files over time.
- Several UI screens still hardcode colors instead of using `Theme.of(context).colorScheme`.
- Missing production features: offline downloads, lyrics, equalizer, sleep timer, Chromecast/AirPlay, CarPlay/Android Auto.
- `withOpacity` deprecation warnings remain in some screens; the most frequently used files have been migrated to `withValues`.


## Stream cache & instant play (added)

- `_bof_cache_streams` (migration: `scripts/migration_stream_cache.sql`)
  caches resolved googlevideo/piped URLs per `yt:<youtube_id>:<quality>`
  key; TTL comes from the URL's `expire` param, <15 min left = stale.
- `youtube_piped->get_stream_cached($id, ["allow_live"=>bool])` —
  cache-first, MySQL `GET_LOCK` stampede protection, falls back to
  `get_stream()` (Piped → yt-dlp).
- `youtube_piped->cache_stream_url($id,$url,args)` — stores a
  client-resolved URL after host/expiry validation (googlevideo/youtube/
  configured piped hosts only).
- `muse_request_source` serves a cached `address` + `duration` +
  `expires` inline (even with `solve=false`); `play` endpoint is the
  single-call variant (`POST /api/play`, object_type/object_hash/quality).
- `muse_solve_raaz` + `play` accept `resolved_url` reports from clients
  to feed the shared cache.
- `_c_m_tracks.youtube_id` column added; `set_track_youtube_id()` helper
  in `class_music.php`.

## muse_solve_raaz contract (updated 2026-09-22)

- `youtube_download` now tries `get_stream_cached()` (Piped → yt-dlp `-g`)
  BEFORE the full file download; on success it returns
  `type:[audio|video,{address,...}]` — a directly playable URL — and still
  queues a `youtube_pending` row so `_bgp` cron lands a local copy.
- On total failure it returns `success:false` (`cant_play` +
  `error_reason:resolve_failed`) instead of the old fake
  `success:true {type:[youtube,{youtube_id}]}` stub; the dedup row is
  stored `result_sta=0` so 2-min replays also fail loudly.
- `user_request_ini("youtube_dl", ...)` replay/in-flight responses are
  honoured — the endpoint now `return`s instead of overwriting the
  emitted error with the stub (this was the observed "success but no
  address" bug).

## YouTube IP bot-block (2026-09-22 status)

- This server's IPv4 is flagged by YouTube: `yt-dlp -g` fails with
  "Sign in to confirm you're not a bot" for ALL player clients
  (web/android/ios/tv_embedded/mweb/web_embedded). Latest yt-dlp
  (2026.08.19) does not help — it is IP reputation, not client version.
- All public Piped/Invidious/cobalt instances are dead or auth-walled.
- To restore real playback either:
  - drop a **logged-in** YouTube cookies export (Netscape format, must
    contain SID/HSID/__Secure-* — a GPS/PREF-only file is useless) at
    `files/protected/yt_cookies.txt` (auto-detected by both
    `get_stream()` and `youtube->download()`), or
  - set `_bof_setting ut_youtubedl_proxy` to a proxy URL **with scheme**
    (`http://host:port` or `socks5://host:port` — bare `host:port` fails
    `parse_url` validation in `youtube->download()`), e.g. a small VPS
    acting as a resolver.
- `get_stream()`'s yt-dlp `-g` fallback now retries once with
  `player_client=android` and honours `ut_youtubedl_path`.
- **bgutil POT provider** is installed: docker container `bgutil-pot`
  (`brainicism/bgutil-ytdlp-pot-provider:2.0.0`, `127.0.0.1:4416`,
  `--restart always`) + yt-dlp plugin at
  `/etc/yt-dlp/plugins/bgutil-ytdlp-pot-provider/`. POT alone did NOT
  bypass the hard block (429 + LOGIN_REQUIRED on the player API), but it
  is the standard first-line fix if the IP reputation recovers.

## JioSaavn fallback resolver (added 2026-09-22)

- `hitune_saavn` class (`plugins/bof_tool_hitune_extras/classes/
  class_hitune_saavn.php`, registered in `_handshake.php`) resolves
  `title + artist + duration` → `https://aac.saavncdn.com/..._320.mp4`
  via the unauthenticated `jiosaavn.com/api.php?__call=search.getResults`
  endpoint + `encrypted_media_url` DES-ECB decryption (key `38346591`,
  **no** `OPENSSL_ZERO_PADDING` — padding bytes would leak into the URL).
- Wired as fallback in `muse_request_source` (stub + last-resort blocks),
  `muse_solve_raaz` (youtube_download branch) and `play` — all return
  `type:[audio,{address,saavn:true}]` or `{url, saavn:true}`.
- Matching: title similar_text + artist ×0.5 + duration gate ±15s,
  score ≥55. `_320`/`_160` upgrades are HEAD-verified.
- saavn CDN URLs have no expiry/auth — they are NOT fed into
  `_bof_cache_streams` (`cache_stream_url` rejects non-googlevideo hosts);
  each resolution is a ~0.5s search instead.

## Gotcha: lazy-loaded classes vs empty()/isset()

`bof()` / `$loader` resolves classes via `__get` and has **no `__isset`**,
so `!empty( $loader->youtube_piped )` is ALWAYS false even though the
class loads fine. Never guard lazy class access with empty()/isset() —
call it directly inside try/catch (e.g. `bof()->youtube_piped->...`).

## Outbound proxy (cURL Proxy tool equivalent)

- The BusyOwl store returns no releases for pending tools
  (`get_release` → "Unkown error"), so store installs fail server-side.
- Instead, native proxy support is wired: set `_bof_setting`
  `curl_proxy` to `host:port` or JSON
  `{address,port,username,password,type}` — both client and admin
  loaders feed it into `curl->set_args()` so all outbound curl calls
  (incl. Piped) route through it. `boac` calls explicitly pass
  `proxy=>false` and are unaffected.
- `ut_youtubedl_proxy` (`host:port` string) adds `--proxy` to the
  yt-dlp command in `class_youtube_piped::get_stream()` — helps bypass
  YouTube bot-checks from the server IP.

## Payment gateways (PayU + Razorpay)

- Credentials live in `/www/wwwroot/.payment.env` and are loaded into
  `_bof_setting` by `/usr/bin/php82 scripts/load_payment_env.php`.
- PayU: `plugins/bof_extra_gateways/classes/class_pgt_payu.php` implements
  the BusyOwlFramework gateway contract (admin settings, hosted checkout,
  response-hash verification, server-side `verify_payment` reconciliation).
- Razorpay: `plugins/bof_extra_gateways/classes/class_pgt_razorpay.php`
  creates Razorpay payment links; the webhook endpoint is at
  `api/app/client/endpoints/endpoint_webhook_razorpay.php`.
- New or changed gateway files must pass `/usr/bin/php82 -l file.php`.
- Music health check: `curl https://music.hitune.in/health.php`.

## PHP/cURL TLS for payment APIs

- The PHP 8.2 build links libcurl against OpenSSL 1.1.1o (`/usr/local/openssl111`), which fails TLS handshake with Razorpay `api.razorpay.com`.
- Fix: `env[LD_LIBRARY_PATH] = /usr/lib/x86_64-linux-gnu` is set in `/www/server/php/82/etc/php-fpm.conf` and `LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu` is in `/etc/cron.d/believoo-ecosystem` so FPM/cron use system OpenSSL 3.x for cURL. Restart FPM with `sudo systemctl restart php-fpm-82` after config changes.

## Webroot hygiene

Debug `check_*.php` scripts with hardcoded DB creds were moved out of the
docroot to `/home/ubuntu/music_debug_archive/` — they were publicly
executable. Do not leave debug scripts in `/www/wwwroot/music/`.

## Subscription plan features UI (admin)

- `_u_subs_plans.features` is a JSON map `{"<feature_key>":1}` consumed by
  `client_config->get_plan_features()` (list shape also supported).
- Admin edit page (`/admin/user_subs_plan/<id>`) now shows a **Features**
  tab with per-feature checkboxes + a **Plan Key** field instead of a raw
  JSON textarea. Keys live in `object_user_subs_plan::feature_keys()`.
- `data.plan_key` maps the plan to its key in `plan_features`; it merges
  into `data` JSON via `object_be_renderer` — do not overwrite `data`
  wholesale (it also holds `iap` product mapping).
- Feature gating is fail-open: if NO plan has features configured,
  `user_has_feature()` returns true for everything.

## Local plugin: bof_tool_hitune_extras

BusyOwl store releases return "Release -> Failed to find" for all
pending tools, so their features were implemented locally in
`plugins/bof_tool_hitune_extras/` (active via `plugins` setting).
Admin UI: sidebar "HiTune Tools" page (Tools section) — each group
saves via the normal `bofAdmin/setting/htx` endpoint.

- **Outbound proxy**: `curl_proxy` setting (`host:port` or JSON
  `{address,port,username,password,type}`) routes all `curl->exe`
  calls; "test on save" stores result in `htx_proxy_last`.
  yt-dlp uses separate `ut_youtubedl_proxy`.
- **AdBlock blocker**: `htx_adblock` + `htx_adblock_mode`
  (overlay|block|redirect) + `htx_adblock_url`; injected in
  `index.php`, assets in plugin `assets/htx_adblock.js`, bait
  file `/ads.js`.
- **Fake users**: generates `@hitune.fake` users (role 2) via
  bundled fakerphp. Test artifacts, easy to spot/clean.
- **Demo importer**: imports real iTunes tracks (30s preview
  remote source + artwork + artist/album find-or-create).
  Dedupes on title+artist.
- **Archiver**: exports object rows to `files/exports/`
  (publicly linkable — treat exports as sensitive).
- **MixCloud**: `htx_mixcloud` registers a `mixcloud` source type
  (with `mixcloud_url` admin input via `add_type`'s `inputs`).
  `be_after` syncs `muse_available_sources` + role `*_player`
  lists so sources actually reach clients. `object_source::clean`
  has a generic `{type}_url` muse branch; `muse_request_source`
  converts it to `["iframe", {provider:mixcloud, address:
  mixcloud.com/widget/iframe?feed=...}]`.

Gotcha: `bof()->object->NAME` returns a bofProxy — `method_exists()`
and ReflectionMethod on it fail; call methods directly in try/catch.

## Mail/Google state (2026-09-28 audit)

- Chapar SMTP (settings `ma_*`): `mail.hitune.in:465` SSL, `noreply@hitune.in` — verified working end-to-end.
- `_u_roles` Guests `guest_signup_verify` flipped to `true` — signup verification emails now sent (was disabled).
- Google social login: `sl=1`, `sl_gg=1` (client `759752024135-...`, its own `sl_gg_secret`). `sl_gg_extra=1` requests sensitive `youtube.force-ssl` scope — needs Google verification or it may warn/block users; disable that flag if Google login errors appear.
- `sl_gg_off` is only a *button style* toggle, NOT a disable switch.

## IyolMe integration (HiTune side) — added 2026-09-29

HiTune is the OAuth 2.0 identity provider for the ecosystem; IyolMe's
backend lives on ANOTHER server, so everything is HTTP — no shared DB
like the web↔music `sso_sync.php` bridge.

- Hub class: `plugins/bof_tool_hitune_extras/classes/class_iyolme.php`
  (`bof()->iyolme`), registered in `_handshake.php`. Endpoints:
  `api/app/client/endpoints/iyol/` + `loader.php` required from
  `api/app/client/loader.php` (after dist loader).
- Settings (`_bof_setting`, admin UI under HiTune Tools → IyolMe
  Integration): `iyol_enabled`, `iyol_api_base`, `iyol_client_id`,
  `iyol_client_secret`, `iyol_webhook_secret`, `iyol_jwt_secret`,
  `iyol_redirect_uris` (JSON array). "Generate credentials" + "Test
  connection" actions exist on that settings page.
- Tables (lazy-created by `iyolme->ensure_tables()`, canonical DDL in
  `scripts/migration_iyolme.sql`): `_oauth_clients`, `_oauth_codes`,
  `_oauth_tokens`, `_iyol_outbox`, `_iyol_events`.
- Endpoints HiTune hosts:
  - `GET|POST /api/oauth/authorize` — branded consent page (login-aware)
  - `POST /api/v1/oauth/token` — authorization_code / refresh_token /
    client_credentials → HS256 JWT (RFC 6749 JSON errors)
  - `GET  /api/v1/oauth/userinfo` — Bearer JWT → sub/uid/username/name/
    email/email_verified/avatar/profile_url
  - `POST /api/v1/oauth/revoke` — RFC 7009
  - `GET  /api/v1/audio/attribution?track={hash}` — metadata +
    attribution label + rights (dist submission / uploader)
  - `POST /api/v1/iyol/webhook` — inbound events, HMAC via
    `X-IYOL-Timestamp`/`X-IYOL-Signature` = hmac256("{ts}.{raw_body}",
    webhook_secret), 5-min replay window. Handles reel.published
    (notifies user), sound.takedown_ack, sound.used, reel.removed.
- App endpoints (BOF signed, group `user`):
  - `POST /api/iyol_sso_link` → one-time code for app SSO handoff
  - `POST /api/iyol_publish` (media_url|file_id, track_hash, caption,
    ai_generated…) → forwards to `{iyol_api_base}/hitune/v1/reels/
    create-from-hitune` with signed payload + one-time `sso_code`.
- Outbound calls sign with `X-HT-Client`, `X-HT-Timestamp`,
  `X-HT-Signature` = hex hmac256("{ts}.{json_body}", client_secret).
- Catalog publish → `dist_publish_to_catalog_run` queues each new
  track to `_iyol_outbox` (`sound_register`) and posts it immediately
  (non-blocking, retry via `iyolme->process_outbox()`); `taken_down`
  status queues `sound_takedown`.
- Live client creds exist in `_bof_setting` (`iyol_client_id` /
  `iyol_client_secret`) — share ONLY via secure channel.

## Ecosystem bridge + AI tagging (strategy doc §3/§7) — added 2026-09-29

- `POST /api/v1/dist/ecosystem` — internal bridge used by the DISTRIBUTION
  WEB PORTAL (`/www/wwwroot/web`, same server, other codebase). HMAC auth:
  `X-HT-Client: hitune_dist_portal`, `X-HT-Timestamp`, `X-HT-Signature` =
  hex hmac256("{ts}.{raw_body}", `dist_bridge_secret` in `_bof_setting`).
  Actions: `publish` (import web release → `_dist_submissions` row with
  `source='web_portal'` + `web_release_id`, copy media files into
  `files/dist/bridge/`, `dist_publish_to_catalog`, auto IyolMe sound sync)
  and `takedown` (queue IyolMe takedown, `dist_unpublish_catalog`, status →
  `taken_down`). Idempotent on `web_release_id`. Endpoint file:
  `api/app/client/endpoints/dist/endpoint_dist_ecosystem.php`.
- AI metadata: `_dist_submissions`/`_dist_tracks`/`_c_m_tracks` have
  `ai_pct` (0-100); submissions also `ai_tools` + `ai_declared`.
  `dist_submit` accepts `ai_pct`, `ai_tools`, `ai_declaration` +
  `tracks[i][ai_pct]`. Publish propagates to `_c_m_tracks.ai_pct`;
  attribution + IyolMe sound payloads carry `ai_pct`/`ai_badge`="AI Original".
- `dist_dashboard_status($row)` (dist loader) → doc labels: "Pending Admin
  Review", "Live on HiTune", "Globally Distributed", "Live on HiTune Music |
  Not Distributed Globally" (tunecore_status declined/failed),
  "Rejected for All Platforms (Including HiTune Music)", "Taken Down".
  Returned as `dashboard_status` in dashboard/submission/admin payloads.
- `dist_unpublish_catalog($db,$sub_id)` removes catalog tracks/sources +
  orphan albums and resets `catalog_published` — invoked on `rejected` and
  `taken_down`. Queue IyolMe takedowns BEFORE unpublish (needs live hash).
- Direct song uploads disabled per doc: `endpoint_upload.php` returns
  `upload_redirect` + `https://distribution.hitune.in/` for `m_track_source`
  object_type. Other object types unaffected.
- Unique-key safety: `dist_unique_code()` + seo_url-aware lookups in
  `dist_catalog_genre`/`dist_catalog_artist` (name match alone missed
  case-variant rows and crashed on the `seo_url` unique key).
- Web side mirror: `/www/wwwroot/web/includes/ecosystem_sync.php`
  (`ecosystem_publish_release`, `ecosystem_takedown_release`,
  `eco_dashboard_status`, `eco_ensure_platforms`); hooked in
  `admin/release_detail.php` on ready/live + rejected/taken_down and on
  manual HiTune/IyolMe platform-delivery marks; auto-seeded platform rows
  'HiTune Music'/'IyolMe' at release create + forced selected at step 2;
  `releases`/`release_tracks` got `ai_pct`/`ai_tools`/`ai_declared` + an
  AI-declaration UI block in step 1.

## §7 upload redirect + §3/§9 additions (2026-09-30)

- `index.php` 301-redirects `/upload` (and `/upload/*`) →
  `https://distribution.hitune.in/submit` BEFORE BOF loads (nginx fronts the
  site — `.htaccess` is not processed; the rule there is a no-op left for
  Apache fallback).
- Review-queue fingerprinting (doc §3): `_dist_tracks.fingerprint` (sha256 of
  file bytes) set on every ingested track (`dist_track_fingerprint` in
  `dist/loader.php`). `dist_flag_duplicate_fingerprint()` marks a submission
  `in_review` + appends `[review]` admin_notes when its audio matches another
  submission; called from `dist_submit`; the bridge (`dist/ecosystem`) stores
  fingerprints and adds a note (admin already chose to publish, so it does not
  un-launch). Web mirror: `web/includes/fingerprint.php`
  (`web_audio_fingerprint` + `web_flag_duplicate_audio`) wired into
  `upload_audio_ajax.php`, `upload_chunked.php`, `release_step3.php`;
  `release_tracks.fingerprint` column added.
- AI tools admin group (doc §9): HiTune Tools → "AI Tools & Policy" —
  global switch + per-tool toggles (cover_art/mastering/karaoke/lyrics_sync),
  provider/model selects, API key/URL fields, per-plan daily quotas
  (`ai_quota_free`/`ai_quota_pro`). Runtime gates:
  `bof()->hitune_extras->ai_tool_enabled($tool)`, `ai_tool_config($tool)`,
  `ai_quota_left($uid,$isPro)`, `ai_quota_spend($uid)`.

## §4/§8 monetization & community backend (2026-09-30)

- **Tipping wallet** (`_htx_wallet`, `_htx_tips`, `_htx_topups`):
  `GET/POST /api/dist/tip` — `action=wallet|history|topup_status`,
  `POST {amount,note,track_hash|artist_hash}` sends a tip; recipient =
  track uploader else artist manager. Split: `tip_artist_pct` setting
  (default 80%), forced to 100% while `tip_event_until` (datetime) is in
  the future — this is the "Creator Day / 0% commission" window. Admin
  UI: HiTune Tools → "Monetization & Creator Events".
  `POST ?action=topup {amount}` creates a `_htx_topups` row + Razorpay
  payment link (`pgt_razorpay->get_link`, needs `gateway_razorpay*`
  settings); `?action=topup_status&topup_id=` verifies via
  `check_payment` and credits `_htx_wallet.balance`.
- **Charts / leaderboards**: `GET /api/v1/charts` extended with
  `chart=top_ai|indie|viral` (doc's "Top 50 AI Songs", "Top 100 Indie
  Stars", "Viral HiTune Tracks"); legacy `type=tracks|albums|artists`
  unchanged. Rows carry `ai_badge`, `hitune_url`, rank.
- **Radio**: `GET /api/v1/radio?seed=<track_hash>|artist=|genre=|chart=`
  — popularity-weighted station queues (doc §8 24/7 radio).
  Registered in `dev/loader.php` + `v1.php`/`router.php`.
- **Listening parties**: `_htx_party` + `_htx_party_listeners`;
  `/api/htx/party?action=list|state|create|join|leave|set_track|end`
  (host controls track/position; clients poll `state` for sync).
- **Contests**: `_htx_contests` + `GET /api/v1/contests[?slug=]` —
  admin-seeded contests with live leaderboards driven by the charts
  above (seeded: best-ai-song, best-indie-track).
- **Referrals (web side)**: `includes/referral.php`
  (`ref_code_for`/`ref_capture`/`ref_credit`), `referral_events` table +
  `users.referral_code`/`referral_credit`; captured in signup +
  google_callback; credited on first ecosystem publish
  (`ecosystem_sync.php`); dashboard card + copy-link UI on
  `pages/dashboard.php`; referral credit is withdrawable via
  `pages/payouts.php` (merged into available balance, consumed first
  when royalties don't cover the request); admin view at
  `admin/referrals.php` with `referral_bonus` setting.
- Migration SQL for all new tables appended to
  `scripts/migration_ecosystem.sql`.

## AI Creator Studio pipeline (doc §4/§6/§9) — added 2026-09-29

- `plugins/bof_tool_hitune_extras/classes/class_hitune_ai.php`
  (`bof()->hitune_ai`, registered in `_handshake.php`): job queue in
  `_htx_ai_jobs` + engine dispatch. Engine order per tool:
  karaoke: demucs → spleeter → ffmpeg vocal-cut (center-channel remove);
  master: matchering → configured API URL → ffmpeg loudnorm (-14 LUFS);
  lyrics: whisper → faster-whisper → API URL (SRT→LRC conversion);
  cover_art: provider API (OpenAI-compatible images endpoint, model from
  `ai_cover_provider`); clip_video: ffmpeg vertical 1080x1920 cover+audio
  mp4 for IyolMe reels. If a heavy ML binary is absent the ffmpeg fallback
  still satisfies the job; demucs/spleeter/matchering/whisper outputs get
  preferred automatically once installed on the host.
- IMPORTANT: `_exec()` returns `proc_get_status()["exitcode"]`, not
  `proc_close()` — PHP reports -1 after the exit code is reaped. Engines
  treat "output file exists and non-empty" as success, not the code.
- Endpoint: `POST /api/ai_studio` (group user) — `action=tools|quota|
  submit|status|list`, registered in `endpoints/dist/loader.php`. Quota
  spent at enqueue via `hitune_extras->ai_quota_spend`.
- Worker: `scripts/ai_worker.php [--limit=N]` — cron-capable queue drain
  (needs `app/client/loader.php` bootstrap, not just `BOF/loader.php`).
  `action=list` also processes 1 pending job per call (self-driving queue).
- Outputs land in `files/ai/YYYY/mm/` and are registered as `_bof_files`
  rows (`object_type`=`htx_ai_<type>`) so `result_url` serves publicly.
- "Publish as Reel" flow: `ai_studio` `type=clip_video` → `file_id` →
  `POST /api/iyol_publish` — implemented in the app by
  `lib/features/iyol/iyol_publish_service.dart`.
- Clips feed: `POST /api/htx/clips` (group api, app shape via
  `__bof_rec_track_items` + `clip{start,duration}` hook window) and
  `GET /api/v1/clips` (dev API, `endpoint_v1_clips.php`).
- `dist_flag_ai_risk($db,$sid,$noteOnly)` (dist/loader.php): review-queue
  heuristics — clone/free-tier tool keywords in `ai_tools`, declared-tools
  vs `ai_pct` contradiction, >=5 submissions/24h bulk spam. Wired into
  `endpoint_dist_submit` (flags to in_review) and bridge (note-only).

## AI engines runtime (added 2026-09-29, verified live)

- Python venv at `/opt/htx-ai` (owned by `www`): `demucs` 4.1, `whisper`
  (openai-whisper, `base` model cached at `/opt/htx-ai/whisper/`),
  `matchering` 2.0.6 as `/opt/htx-ai/bin/matchering` shim (python script —
  upstream ships no CLI). CPU-only torch.
- `_exec()` injects `HOME=/opt/htx-ai`, `TORCH_HOME`, `HF_HOME`,
  `XDG_CACHE_HOME` — model caches land under `/opt/htx-ai/**`, never the
  worker user's home. PATH includes `/opt/htx-ai/bin`.
- Matchering is reference-based: pass `reference_track_id`/
  `reference_file_id` in job params or set `ai_master_reference` (a path
  under `files/`); without one it falls through to ffmpeg loudnorm.
- Whisper model selectable via `ai_whisper_model` setting (default `base`;
  `small` is ~1GB and slow on CPU).
- Cron installed: `/etc/cron.d/hitune-ai-worker` —
  `* * * * * www php82 scripts/ai_worker.php` (verified running).
  `process_queue()` re-queues `processing` rows idle >30min (killed-worker
  recovery; `time_done` doubles as the processing-start marker).
- Per-engine failure tails append to `files/ai/engine_errors.log`.
- Live-tested on track 1: demucs karaoke (~3 min for 4.5-min track),
  matchering master (wav), whisper base lyrics→LRC, ffmpeg clip mp4.
- Disk is tight (~2G free); avoid installing more/larger models without
  cleaning. HF_TOKEN not set — demucs/whisper downloads are unauthenticated.

## App-facing community endpoints + split royalty (2026-09-29)

- `htx/charts`, `htx/radio`, `htx/contests` (group api, in
  `_setup/endpoints.php`) — app-shaped variants of the v1 dev endpoints;
  charts/radio reuse `__bof_rec_track_items` so items feed the app's
  `Track` model directly. Radio `mode=list` returns the station
  directory; `chart=viral|indie|top_ai|mixed` returns a shuffled queue.
- Web AI Studio page: `/ai-studio.php` on music.hitune.in — session via
  `distAuth` headers, calls `/api/ai_studio` (skip_key_check + group user).
- Split royalty (web portal): `track_splits` table (email+pct per track,
  resolved to `users.id`), `includes/splits.php` (`splits_for_release`,
  `user_net_royalties`, `split_add`), owner UI on `release_view.php`
  "Royalty Splits" card. `payouts.php`, `dashboard.php`, `earnings.php`
  top-line earnings are NET of splits (own remainder + incoming shares).
  Migration SQL appended to `scripts/migration_ecosystem.sql`.

## AI Studio full UI + song_gen + plan gating (2026-09-29)

- `/` now 301-redirects to `/home` (index.php) — the `_d_pages` "Landing
  page" row (ID 2) is deactivated; the music app home is the front door.
- Sidebar/user menus live in `_d_menus.structure` (JSON); "AI Studio"
  (href `ai-studio`, mdi `auto-fix`) was added to menus ID 1 + 3.
  `/ai-studio` is served by `ai-studio.php` via an index.php path include.
- `ai-studio.php` is a full studio UI: left tool rail, workspace per tool
  (song_gen prompt/genre chips/instrumental/seconds; cover_art prompt;
  clip_video track+window; karaoke/master/lyrics track+ref), right results
  panel with live polling + audio/video players.
- IMPORTANT FIX: `endpoint_ai_studio.php` was rewritten to
  `$loader->api->set_message("ok", $data)` — the earlier `api->set()` and
  `user_input(...,"id")` validator never existed, so the endpoint always
  500'd. Validators: `int`/`float`/`string`/`md5`, not `id`.
- New tool `song_gen` (prompt→song): engine `song_provider` supports
  `ai_song_gen_provider` = `stability` (Stable Audio, sync binary),
  `replicate` (version in `ai_song_model`, polls prediction), `suno`
  (`ai_song_api_url` base + `/generate` + `/get?ids=` polling). Needs
  `ai_song_gen_api_key`. `_htx_ai_jobs.type` ENUM extended.
- Plan gating: `hitune_ai->plan_features/plan_allows/quota_limit/
  quota_left_for`. Feature keys `ai_studio, ai_song_gen, ai_mastering,
  ai_karaoke, ai_lyrics_sync, ai_cover_art, ai_clip_video` added to
  `object_user_subs_plan::feature_keys()` (checkboxes on each plan →
  features JSON → plan_features map). `ai_quota` digit input → plan
  `data.ai_quota` (per-plan daily cap; non-subscribers read the `free=1`
  plan row). Fail-open only while NO plan defines any ai_* feature.
- Defaults seeded: Free plan gets karaoke/lyrics/clip/cover_art with
  `ai_quota=7`; Premium/Student/Duo/Family get all tools incl. song_gen.
  song_gen stays `enabled=false` until an API key is configured.

## HiTune Hub (community features) — 2026-09-29

- `/hub` → `hub.php` (index.php path include, same pattern as ai-studio):
  standalone dark page with tabs for Charts, Short Clips, Radio,
  Contests, Listening Parties, Tips & Wallet. Session actions use
  `/dist-auth.js` (x-bof-sess-id/key headers).
- Public web mirrors of the signed htx endpoints registered in
  `api/app/client/endpoints/dist/loader.php` as `hub/charts|clips|radio|
  contests|party` with `skip_key_check` + `groups:["v1_public"]`
  (read-only public data; `hub/party` executor still guards writes via
  `user->check()`). Tip/wallet stays session-gated at `dist/tip`.
- `_d_menus`: Sidebar (ID 1) gained a "Community" group (hub#anchors,
  `external:"1"`); Navbar Mobile (ID 2) + User Dropdown (ID 3) got "Hub".
  NOTE: menu ID 3 structure is FLAT items, not grouped `childs`.
- Endpoint group cheat-sheet: `groups:["api"]` needs signed POST +
  x-bof-* headers; `groups:["user"]` adds is_logged; `groups:["v1_public"]`
  or `[]` + `skip_key_check` = public (like `/api/v1/ping`).

## IyolMe pairing status — 2026-09-29

- Code side complete both ends (iyolme-backend commit 81d8587: signed
  `POST /api/hitune/v1/ping` + durable `tbl_hitune_outbox` webhooks with
  `hitune:flush-outbox` scheduler; iyolme-app eb49bb3: attribution +
  post-level AI badge fixes).
- **Blocked on config**: `_bof_setting iyol_api_base` is EMPTY and
  `api.iyolme.com` is unreachable from this server (CF 530 / timeouts —
  separate server). Set `iyol_api_base=https://api.iyolme.com/api` once
  the IyolMe backend is deployed, then run the admin "Test connection".
- IyolMe backend needs `HITUNE_CLIENT_ID` = `iyol_client_id`,
  `HITUNE_CLIENT_SECRET`, `HITUNE_WEBHOOK_SECRET`, `HITUNE_API_BASE=
  https://music.hitune.in` and `php artisan migrate` + scheduler cron.

## IyolMe cross-app loop — 2026-09-30

- `GET /api/v1/iyol/my_music?sub=u:{hash}` (Bearer client_credentials) —
  `endpoint_iyol_my_music.php`: the user's own sounds for IyolMe's "My
  Music" picker. AI Studio `song_gen` done-jobs → `hitune_track_hash`
  `ai{id}` + `audio_url=result_url` + `ai_pct:100`; `_dist_submissions`
  → real catalog hash when `catalog_track_ids` set, else `dist{id}` +
  direct `audio_url` (dist files are public under files/).
- `POST /api/iyol/stories` — public (v1_public+skip_key_check) mirror
  that proxies signed `POST {iyol_api_base}/hitune/v1/stories`; returns
  `stories:[]` + `configured/reachable` flags when IyolMe is down so the
  app rail can hide itself.
- Music app deep-links `iyolme://reel/create?hitune_track_hash=…&title…`
  (track metadata in query — IyolMe registers unknown sounds via its
  `post/resolveHituneSound`) and `iyolme://story/{id}`.
- IYOLME_INTEGRATION.md §4 documents my_music + stories + the deep-link
  table.

## Distribution freemium + Creator Day — 2026-09-29

- `web/includes/auth.php`: `distSetting()`, `freeReleaseQuota()`,
  `creatorDayInfo()` — settings table keys `free_releases_enabled`,
  `free_releases_per_month` (default 2), `creator_day_enabled`,
  `creator_day_date` (1–28). Count = releases `NOT IN ('draft','rejected')`
  created since month start; subscribers unlimited.
- Gates: `release_create.php` (new-draft block + quota banner +
  `?quota=exceeded` view) and `release_step4.php` (submit-time re-check;
  rejected resubmits don't consume quota).
- UI: dashboard quota progress bar + Creator Day banner + "AI Studio
  Tools" quick action → `music.hitune.in/ai-studio`; admin
  `settings.php` "Freemium & Growth" tab.
- Status matrix of the whole ecosystem lives in
  `music+disturubution+iyome.md` §10 — keep it updated honestly.

## Lyrics provider (added 2026-10-01)

- `lyrics_source` setting = `lrclib` — new provider class
  `plugins/bof_music/classes/class_lyric_lrclib.php` (LRCLIB, free, no key;
  prefers synced LRC, falls back to plain). Endpoint detects `[mm:ss]` tags
  and serves both `lyrics` (stripped) and `lrc`. Fetched lyrics persist to
  `_c_m_tracks.lyrics`.
- Register new `lyric_*` classes via `core_files->add_key` in
  `class_music.php::setup()` (~line 43).

## Monthly contests rollover (2026-10-01)

- `scripts/contest_rollover.php` (cron, every minute) pushes expired
  'active' `_htx_contests` into the next calendar-month window.
- Contests query requires `starts_at <= NOW() <= ends_at` — expired
  windows make `/api/htx/contests` return `no_active_contests`.

## AI Studio worker notes (2026-10-01)

- `class_hitune_ai.php`: remote-track clip_video falls back to
  `hitune_saavn->resolve()` + download (`_fetch_remote_audio`) when no
  local source file exists. Waveform fallback filtergraph: showwaves
  overlaid on black canvas (`color` + `overlay` — not chained inputs).
- `files/ai` must be writable by `www` (cron worker user).
- `process_queue` requeues `processing` rows stale >30min.
- Demucs installed at `/opt/htx-ai/bin/demucs` (python venv).

## AI Studio updates (2026-10-06)

- `engines_for("lyrics")`: **faster-whisper first** (`/opt/htx-ai/bin/faster-whisper`
  is a hand-written CLI shim → faster_whisper lib, ctranslate2 int8 CPU).
  openai-whisper `base` timed out at 1800s on ~5min audio; faster-whisper
  `tiny` finishes in ~20s. `ai_whisper_model` = `tiny`.
- `cover_art` works via free **pollinations.ai** provider
  (`ai_cover_art_provider=pollinations`, no key needed). DALL-E 3 is not
  available on the configured OpenAI key.
- `master`: `ai_master_reference` = a previously-mastered wav — required
  for matchering (no reference → falls back to ffmpeg_loudnorm).
- `song_gen` still needs a paid provider key (`ai_song_gen_provider` =
  stability|replicate|suno + `ai_song_gen_api_key`).
- ffmpeg `color` filter: always `color=c=black:...` — bare `color=black`
  errors on this build ("Too many inputs").
- Only ONE ai_worker cron entry lives in `/etc/cron.d/hitune-ai-worker`
  (a duplicate in believoo-ecosystem was removed 2026-10-06).

## Payments (2026-10-06)

- HTML-form gateways (PayU) now expose `pay_render`: `get_link` wraps the
  auto-submit form into `/api/pay_render/<num>/<hash>/` (endpoint
  `endpoint_pay_render.php`, group v1_public). `gateway_req_data` column is
  JSON-validated — always store `{"html": ...}` not a raw string.
- PayU is LIVE mode; Razorpay keys are rzp_test_* (test mode).
