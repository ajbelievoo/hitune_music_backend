# HiTune Music — Backend Requirements

Ye document backend team ke liye hai. Flutter app mein saare client-side
features implement ho chuke hain; unke sahi working ke liye neeche diye gaye
endpoints / payload fields chahiye. Saare endpoints `POST` hain aur existing
BOF signing (`bof_signature`) ke through jaate hain — `ApiService` session
keys (`sess_id`/`sess_key` POST fields + `x-bof-sess-key` header +
`PHPSESSID` cookie) automatically bhejta hai.

## 1. Subscription plans & feature gating (ADMIN CONTROLLED)

`client_config` response mein yeh fields add karein:

```json
{
  "user": {
    "plan": "free | premium | family | ...",
    "is_premium": 1
  },
  "setting": {
    "default_plan": "free",
    "iap_enabled": true
  },
  "plan_features": {
    "free":    ["sleep_timer", "lyrics", "comments", "playlists", "recommendations"],
    "premium": ["offline_downloads", "hq_audio", "no_ads", "equalizer",
                "sound_controls", "casting", "unlimited_skips",
                "collaborative_playlists", "voice_search"]
  }
}
```

Feature keys jo app samajhta hai (`AppFeatures`):
`offline_downloads`, `lyrics`, `sleep_timer`, `equalizer`, `sound_controls`,
`hq_audio`, `no_ads`, `casting`, `unlimited_skips`, `comments`, `playlists`,
`recommendations`, `push_notifications`, `voice_search`,
`collaborative_playlists`.

Notes:
- `plan_features` list ya map (`{"lyrics": true}`) — dono accept hain.
- Agar `plan_features` na bheja jaye to app **fail-open** hai (sab allowed)
  taaki existing users lock na hon. Jab tak admin plan mapping set nahi karta,
  premium features free rahenge — isliye production launch se pehle yeh map
  zaroor configure karein.
- User ka plan `user.plan` / `user.data.plan` / `user.extra.plan` /
  `is_premium` — koi bhi ek field chalega.

## 2. Offline downloads

`muse_request_source` response mein ek naya field add karein:

```json
{ "download_url": "https://.../file.mp3" }
```

- Direct audio file URL ho (mp3/m4a/aac/ogg/flac) — HLS (m3u8/ts) download
  nahi ho sakta, app usko reject karta hai.
- Sirf premium users ke liye `download_url` return karein (plan_features ke
  hisaab se). Free users ke liye field omit karein.
- Client `track_lyrics` style object fields pass karta hai:
  `object_type`, `object_hash`.

## 3. Lyrics

Endpoint: `track_lyrics`
Request: `object_type`, `object_hash`
Response:

```json
{ "lyrics": "plain text ya LRC", "lrc": "optional synced lrc" }
```

Upload wizard already `lyrics` field bhejta hai (`upload_wizard`), bas fetch
endpoint chahiye. LRC format diya jaye to app time-synced highlighting add
kar sakta hai.

## 4. Playlists management

| Endpoint            | Fields                                              |
|---------------------|-----------------------------------------------------|
| `playlist_create`   | `playlist` (name) → return `{ "playlist_hash": "..." }` |
| `playlist_add`      | `playlist`, `object_type`, `object`                 |
| `playlist_remove`   | `playlist`, `object_type`, `object`                 |
| `playlist_rename`   | `playlist`, `name`                                  |
| `playlist_delete`   | `playlist`                                          |
| `playlist_reorder`  | `playlist`, `order` (comma-separated track hashes)  |
| `playlist_collab`   | `playlist`, `collab` (0/1), `invite` (user_id)      |

`user_library?tab=playlists` items mein `hash` (ya `id`) aur `count`
fields dena zaroori hai.

## 5. Notifications

| Endpoint                  | Fields                        | Returns                        |
|---------------------------|-------------------------------|--------------------------------|
| `notifications`           | `page`                        | `{ "items": [ {...} ] }`       |
| `notification_mark_read`  | `notification_id`             | ok                             |
| `notification_mark_all`   | —                             | ok                             |
| `push_register`           | `fcm_token`, `platform`       | ok                             |

Notification item fields: `id`, `title`, `body`, `image`, `link`,
`is_read` (0/1), `created_at` (unix ya ISO).

Push delivery ke liye Firebase Cloud Messaging — app mein FCM wiring ke liye
`google-services.json` / `GoogleService-Info.plist` aur `firebase_messaging`
package chahiye (client-side ready hai via `push_register`).

## 6. Comments

| Endpoint          | Fields                                     |
|-------------------|--------------------------------------------|
| `comments`        | `object_type`, `object`, `page`            |
| `comment_add`     | `object_type`, `object`, `text`            |
| `comment_delete`  | `comment_id`                               |

Comment item: `id`, `author`/`user.name`, `avatar`, `text`, `created_at`,
`likes`.

## 7. Recommendations / Made for you

Endpoints (koi bhi ho to rail render hogi, warna hidden):

| Endpoint                     | Returns                                  |
|------------------------------|------------------------------------------|
| `recommendations`            | `{ "items": [...] }`                     |
| `daily_mix`                  | `{ "items": [...] }`                     |
| `recommendations_because`    | `{ "items": [...] }` (seeded by history) |

Item shape = standard widget item (`hash`, `title`, `sub_title`, `cover`,
`object_type`).

## 8. In-app purchases / subscriptions

| Endpoint          | Fields                                             |
|-------------------|-----------------------------------------------------|
| `verify_purchase` | `product_id`, `purchase_token`, `platform`         |

Backend receipt verify karke user ka `plan` set kare. Products Play Console
(`hitune_premium_monthly`, `hitune_premium_yearly`) / App Store Connect mein
banane honge. `setting.iap_enabled=true` aane par hi app native billing UI
dikhayegi; warna external checkout (`UpgradePlansScreen`) use hota hai.

## 9. Radio

`/radios` response items mein in fields ka kam se kam ek zaroor ho:
`url` / `stream_url` (playable stream), `name`/`title`, `image`/`logo`.

## 10. Casting (Chromecast / AirPlay)

Pure client/native work — Google Cast SDK integrate karna hai. Backend se sirf
content URLs publicly reachable hone chahiye (signed URLs Cast receiver pe
expire nahi hone chahiye).

## 11. CarPlay / Android Auto

Android: manifest mein `automotive_app_desc.xml` + `MediaBrowserService`
already wired hai (audio_service). iOS CarPlay ke liye `com.apple.developer.
carplay-audio` entitlement + Apple approval chahiye — backend se sirf
browseable lists (home/user_library) ka stable JSON kaafi hai.

## 12. Analytics / reporting

Optional endpoints for artist dashboards: `track_stats`,
`artist_earnings`, `payout_request`. App mein `analytics_screen` exist
karta hai — in endpoints ke data se rich banega.

## 13. Account deletion

`settings` mein delete flow hai; confirm endpoint `user_delete`
(`password` field) jo pehle se `user_edit` tab `delete` mein partially
wired hai — verify karein ki final deletion call sahi endpoint pe ja rahi hai.

## 14. Per-track loudness normalization (Spotify-style) — HIGH PRIORITY

App mein client-side audio engine ready hai (equalizer, compressor, bass
boost, stereo widening — Android `DynamicsProcessing` ke through). Lekin
**har gaane ka volume alag-alag** hona fix karne ke liye backend se loudness
metadata chahiye. Yeh Spotify/YouTube Music/Apple Music sab karte hain —
"volume normalization" feature ka asli engine yehi hai.

### Kya karna hai

Upload/transcode pipeline mein har track measure karein:

```bash
ffmpeg -i input.mp3 -af ebur128=peak=true -f null -
```

Output se do values nikaalein:
- `I:` (integrated loudness, LUFS) — e.g. `-9.4`
- `Peak:` (true peak, dBTP) — e.g. `-0.3`

### Response mein bhejein

`muse_request_source` (aur `muse_solve_raaz`) response mein kisi bhi level pe
yeh fields rakhein — app poora payload scan karta hai:

```json
{ "loudness": { "lufs": -9.4, "peak_db": -0.3 } }
```

ya flat fields bhi chalte hain: `lufs` / `loudness_lufs` / `integrated_loudness`,
`peak_db` / `true_peak_db`, ya seedha `replaygain_db` / `track_gain` (already
computed gain ho to).

### Client behaviour (already implemented)

- App target `-14 LUFS` use karta hai (Spotify standard): `gain = -14 - lufs`
- True peak clamp: gain aisa ho ki peak −1 dBTP se upar na jaye (no clipping)
- Metadata na mile to gain 0 (koi change nahi) — fully backward compatible
- iOS pe volume-based fallback, Android pe LoudnessEnhancer

### Alternative (heavy option)

Chahein to transcode time pe hi normalize kar dein
(`ffmpeg -af loudnorm=I=-14:TP=-1.5:LRA=11`, 2-pass) — tab metadata bhejne ki
zaroorat nahi, par gain-only approach lossless hota hai aur user toggle kar
sakta hai, isliye metadata route better hai.

## 15. Instant play — resolved stream in `muse_request_source` + `play` endpoint (IMPLEMENTED)

Ek gaana play karne ke liye ab client ko sirf **ek call** karna hai.
Server-side YouTube/raaz stream resolution + caching implement ho chuki hai.

### `muse_request_source` (updated)

- YouTube/raaz source milte hi server pehle **server-side stream cache**
  check karta hai. Cache hit pe source ka `type` seedha playable ho jaata hai:

```json
{ "type": [ "audio", {
    "address": "https://rr4---sn-....googlevideo.com/videoplayback?expire=...",
    "mime": "audio/webm",
    "type": "stream",
    "duration": 302,
    "expires": 1790041774
} ] }
```

- `solve=false` bhejne pe bhi cached URL serve hota hai (live resolution skip).
- Cache miss + `solve=false` → source `["youtube", {"youtube_id": ..., "raaz": true}]`
  rehta hai (purana client-side flow fallback ke liye).
- Response mein track ka `youtube_id`, `duration`, `loudness`
  (`lufs`/`peak_db`) aata hai jahan available — `muse_request_youtube_id`
  ka extra call zaroori nahi.

### `play` (new endpoint)

```
POST /api/play
object_type=m_track
object_hash=<md5>
quality=audio_hq | audio_lq | video_hq | video_lq   (optional)
```

Response:

```json
{
  "url": "https://....googlevideo.com/videoplayback?expire=...",
  "type": "audio",
  "mime": "audio/webm",
  "duration": 302,
  "expires": 1790041774,
  "cached": true,
  "youtube_id": "-7_MyOao-eE",
  "title": "...",
  "sub_title": "...",
  "cover": "...",
  "loudness": { "lufs": -13.0, "peak_db": -0.3 },
  "lufs": -13.0,
  "peak_db": -0.3,
  "success": true
}
```

- Local/free file ho to wahi direct `url` aata hai (protected ho to
  20-minute granted URL).
- Priority: local source → stream cache → live Piped → yt-dlp →
  iTunes preview → error.

### Client → server URL report (shared cache feed)

Ek device ne YouTube URL resolve kiya to baaki sabko free mein mile —
client resolved URL report kar sakta hai:

- `play` pe optional fields: `youtube_id`, `resolved_url`,
  `resolved_mime`, `resolved_type` (audio|video), `resolved_duration`.
- `muse_solve_raaz` pe `resolved_url` field bhi accept hota hai.
- Sirf `https` + `*.googlevideo.com` / `*.youtube.com` / configured piped
  host accept hote hain; expired/near-expired (`expire` param) reject.

### Server-side stream cache (`_bof_cache_streams`)

- Key: `yt:<youtube_id>:<quality>` (e.g. `yt:-7_MyOao-eE:audio_hq`).
- TTL URL ke `expire` timestamp se derive hota hai (~6h); <15 min bache
  to stale maan ke refresh hota hai.
- `GET_LOCK` se same track ki parallel live resolutions serialize hain.
- Schema: `scripts/migration_stream_cache.sql`.

### Seek / skip

- Local free files: 206 Partial Content (verified).
- Protected files (`protector.php`): HTTP Range → 206 + Content-Range.
- googlevideo URLs YouTube CDN se direct serve hote hain — Range upstream
  support karta hai.

## 16. Free trial — `subs_start_trial` (IMPLEMENTED)

Spotify-style "1 month free" — server-side trial without payment.

### Request

```
POST /api/  (group: user — login required)
bof_request=subs_start_trial
hash=<plan_hash>          # or object_hash
```

### Response

```json
{
  "success": true,
  "status": "trial_started",
  "plan": "premium",
  "plan_id": 5,
  "trial_days": 30,
  "expires": 1792828800
}
```

Errors: `plan_not_found`, `no_trial` (plan has no `trial_days`),
`trial_used` (user already held this plan — any `_u_subs` row),
`invalid_request`, `no_access`.

### Behaviour

- Trial days per plan live in `_u_subs_plans.data.trial_days`
  (admin edit page → Features tab → "Free Trial Days").
- Grants a `_u_subs` row: `subs_plan_time_range="trial"`,
  `subs_plan_price=0`, `gateway_name="trial"`,
  `time_expire = now + trial_days`. Once per user per plan.
- `user_subs` plan payload now also exposes `plan_key`,
  `trial_days`, `features` — client can render "30 days free" CTA and
  feature checkmarks straight from the plans list.

### Admin (plan edit → Features tab)

- **Plan Key** → `data.plan_key` (key in `client_config.plan_features`)
- **Free Trial Days** → `data.trial_days` (0 disables trial)
- **IAP Product ID** per period → `data.iap.<product_id>.period`
  (matched by `verify_purchase`)
- **Feature checkboxes** → `features` JSON map `{key:1}` used by
  `user_has_feature` / `plan_features`

## 17. HiTune Tools plugin (bof_tool_hitune_extras) — IMPLEMENTED

Local plugin replacing unavailable BusyOwl store tools. Admin page:
sidebar → Tools → "HiTune Tools" (settings group `htx`, saves via
`bofAdmin/setting/htx`).

| Tool | Setting / inputs | Behavior |
|---|---|---|
| Outbound proxy | `curl_proxy`, `htx_proxy_test` | Routes all `curl->exe` calls through proxy; test stores `htx_proxy_last`. yt-dlp uses separate `ut_youtubedl_proxy` |
| AdBlock blocker | `htx_adblock`, `htx_adblock_mode` (overlay/block/redirect), `htx_adblock_url` | Injects `plugins/bof_tool_hitune_extras/assets/htx_adblock.js` + `/ads.js` bait on public pages |
| Fake user generator | `htx_fu_count/gender/verified/run` | Creates `@hitune.fake` users (role 2) via fakerphp |
| Demo importer | `htx_demo_term/count/run` | iTunes import: artist/album/track find-or-create + 30s preview remote source (dedupes) |
| Archiver | `htx_exp_type/format/run` | Exports object rows to `files/exports/` (publicly linkable) |
| MixCloud | `htx_mixcloud` | Registers `mixcloud` source type + `mixcloud_url` admin input; syncs `muse_available_sources` + role `*_player` perms on save |

Client contract for MixCloud sources: `muse_request_source` returns
`type: ["iframe", {provider:"mixcloud", address:"https://www.mixcloud.com/widget/iframe/?hide_cover=1&feed=...", duration}]`.
App should render it in a WebView/iframe player card.

## 18. Cleartext-safe stream URLs — IMPLEMENTED

Backend never emits `http://` media/stream URLs — `general->https_url()`
upgrades scheme at every emission point (Android 9+/iOS block cleartext
anyway, so upgrading is strictly better than serving a dead URL):

- `youtube_piped->get_stream()` — Piped `audioStreams/videoStreams` url
  and yt-dlp resolved url
- `youtube_piped->get_stream_cached()` — cached rows normalized on read
  (old http rows upgrade automatically)
- `youtube_piped->cache_stream_url()` — client-reported http URLs are
  upgraded instead of rejected
- `muse_request_source` — any `source.type[1].address` normalized
- `play` — `url` normalized
- `object_source::clean()` — `remote_address` normalized when building
  `muse` payload

## 19. Developer API (IMPLEMENTED)

Backend implementation complete for HiTune Developer API (Spotify-style third-party integration).

### Database tables (scripts/migration_developer_api.sql)
- `_dev_apps` — developer app registrations (client_id, client_secret, publishable_key)
- `_dev_plans` — API plans (sandbox, starter, pro, enterprise) with quotas and limits
- `_dev_tokens` — OAuth Bearer tokens for server-side access
- `_dev_usage` — monthly request quotas per app
- `_dev_rate` — rate limiting per app/plan

### Developer API class
`plugins/bof_tool_hitune_extras/classes/class_developer_api.php`
- Key generation (publishable keys, client secrets, Bearer tokens)
- Plan lookup and validation
- App authentication (v1_auth for publishable key / Bearer token)
- Token issuance (client_credentials grant)
- Rate limiting
- Monthly quota checks
- Payload formatters (track_payload, album_payload, artist_payload, playlist_payload)
- Stream access control and signed URL resolution

### Signed developer portal endpoints (for in-app developer UI)
Registered in `api/app/client/endpoints/dev/loader.php`:
- `developer_plans` — list available plans
- `developer_apps` — list user's apps
- `developer_app_create` — create new app
- `developer_app_update` — update app details
- `developer_app_delete` — delete app
- `developer_key_regenerate` — regenerate publishable key / client secret
- `developer_usage` — view monthly usage
- `developer_subscribe` — initiate subscription
- `dev/billing` — Razorpay payment callback target

All portal endpoints use BOF signature + session auth (groups "api" or "user").

### Public REST API v1 endpoints
Registered in `api/app/client/endpoints/dev/loader.php` with empty groups to bypass BOF signature:
- `GET /api/v1/ping` — health check (works)
- `POST /api/v1/token` — OAuth token issuance (grant_type=client_credentials)
- `GET /api/v1/plans` — list plans
- `GET /api/v1/search` or `/api/v1/catalog` — search tracks/albums/artists/playlists
- `GET /api/v1/tracks/{hash}` — track metadata
- `GET /api/v1/tracks/{hash}/stream` — signed stream URL
- `GET /api/v1/albums/{hash}` — album metadata
- `GET /api/v1/artists/{hash}` — artist metadata
- `GET /api/v1/playlists/{hash}` — playlist metadata
- `GET /api/v1/charts` — trending tracks
- `GET /api/v1/genres` — genre list
- `GET /api/v1/stream/{signed-token}` — resolve signed stream URL
- `GET /api/v1/embed/{type}/{hash}` — embed player
- `GET /api/v1/oembed` — oEmbed discovery

**Routing status:**
- `/api/v1/ping` works correctly via BOF endpoint matching with empty groups
- `/api/v1/catalog` and `/api/v1/search` currently return 403 due to legacy endpoint URL conflicts
- All endpoint files are implemented correctly in `api/app/client/endpoints/v1/`
- Recommended workaround: use `/api/v1/ping`-style routing or move public API to a dedicated subdomain (e.g., `api-dev.hitune.in`)

### Admin endpoints
Registered in `api/app/admin/_setup/endpoints.php`:
- `be/developer_apps` — list all apps
- `be/developer_plans` — list all plans
- `be/developer_plans_update` — update plan config
- `be/developer_app_approve` — approve/reject apps
- `be/developer_app_suspend` — suspend apps
- `be/developer_app_delete` — delete apps
- `be/developer_usage_report` — usage analytics
- `be/developer_settings` — global developer API settings
- `be/developer_plan_set` — plan assignment

### Client config flag
`api/app/client/classes/class_client_config.php`:
- `setting.developer_portal` — gates the "Developer API" menu item in the Flutter app

### Licensing note
Full-track streaming access is restricted to Pro/Enterprise plans. Sandbox and Starter plans only allow metadata, previews, and embed player. Unrestricted full-track access should not be enabled until content licensing is confirmed.
