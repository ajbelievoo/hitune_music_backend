# HiTune ↔ IyolMe — Backend Integration Spec

**Audience:** IyolMe backend team (iyolme backend runs on a separate server).
**What this document is:** the complete contract IyolMe must implement to plug into the
HiTune ecosystem — SSO login, reel ingestion from HiTune, sound registry sync, and
audio attribution. HiTune side is **already live** at `https://music.hitune.in`.

All traffic is server-to-server or browser redirect over HTTPS. There is **no shared
database** — do not assume direct DB access.

---

## 1. Roles

| Direction | Meaning |
|---|---|
| **HiTune → IyolMe** | We call YOUR API: push reels, register/takedown sounds. Signed with HMAC. |
| **IyolMe → HiTune** | You call OUR API: OAuth token exchange, userinfo, attribution, webhooks. Bearer JWT or HMAC. |

You will receive out-of-band (never in git/chat logs):

| Credential | Purpose |
|---|---|
| `client_id` (`iyol_live_…`) | OAuth client identifier + `X-HT-Client` header value we send you |
| `client_secret` (`iyol_sec_…`) | OAuth secret; also verifies `X-HT-Signature` on calls we make to you |
| `webhook_secret` | Secret YOU use to sign `X-IYOL-Signature` when calling our webhook (may equal client_secret) |
| `iyol_api_base` | We need **your** base URL from you, e.g. `https://api.iyolme.com` — send it back to us |

> Send us: your `iyol_api_base` and the redirect URI(s) you want registered
> (e.g. `https://iyolme.com/auth/hitune/callback` and/or `iyolme://auth/hitune`).

---

## 2. SSO — "Login with HiTune" (OAuth 2.0 + JWT)

HiTune is the identity provider. Access tokens are **HS256 JWTs**, 1-hour TTL;
refresh tokens are opaque, 30-day TTL, rotating.

### 2a. Browser / web flow (authorization_code)

```
1. Your app/web redirects the browser to:

   GET https://music.hitune.in/api/oauth/authorize
       ?response_type=code
       &client_id=iyol_live_xxx
       &redirect_uri=https://iyolme.com/auth/hitune/callback   (must be pre-registered)
       &scope=profile%20email%20reels
       &state=<random nonce you verify on callback>

2. User sees a branded HiTune consent screen ("Authorize IyolMe").
   If not logged in on music.hitune.in they are asked to log in first.
   On approve we 302 to:
       {redirect_uri}?code=htc_...&state=<your state>
   On deny:  {redirect_uri}?error=access_denied&state=...

3. Your BACKEND exchanges the code (never from the browser):

   POST https://music.hitune.in/api/v1/oauth/token
   Content-Type: application/x-www-form-urlencoded

   grant_type=authorization_code
   &client_id=iyol_live_xxx
   &client_secret=iyol_sec_xxx
   &code=htc_...
   &redirect_uri=https://iyolme.com/auth/hitune/callback

   → 200 {
       "access_token":  "eyJhbGciOi…",          // JWT, 1h
       "token_type":    "Bearer",
       "expires_in":    3600,
       "scope":         "profile email reels",
       "refresh_token": "htr_...",               // 30d, rotates on use
       "user": {
         "sub": "u:584628cb…", "uid": 1,
         "username": "admin", "name": "…",
         "email": "…", "email_verified": true,
         "avatar": "https://…",
         "profile_url": "https://music.hitune.in/@admin"
       }
     }
```

Authorization codes are **single-use, 10-minute TTL**. Reuse returns
`{"error":"invalid_grant"}`.

### 2b. In-app flow (HiTune app → IyolMe app, no browser)

When a logged-in HiTune app user taps a "continue to IyolMe" / "publish as reel"
action, our app mints a one-time code via `POST /api/iyol_sso_link` and hands it to
IyolMe (deep link param or your init API). Your backend exchanges it with the **same**
`oauth/token` call (`grant_type=authorization_code`, `code=htc_…`, no redirect_uri
check for codes minted this way).

### 2c. Token refresh

```
POST /api/v1/oauth/token
grant_type=refresh_token&client_id=…&client_secret=…&refresh_token=htr_…
→ new access_token + NEW refresh_token (old one is revoked — store the new one)
```

### 2d. Verify the user later

```
GET /api/v1/oauth/userinfo
Authorization: Bearer eyJhbGciOi…
→ { "sub":"u:…", "uid":1, "username":"…", "email":"…", "email_verified":true, … }
```

`sub` is the stable cross-platform identity (`u:{32-hex user hash}`). Map it to your
local user — do NOT key accounts on email alone.

### 2e. JWT self-verification (optional)

Tokens are HS256. If you want to verify without a network call, ask us for the JWT
verification secret; otherwise call `userinfo`/introspect. Claims: `iss` =
`https://music.hitune.in/`, `aud` = your client_id, `sub`, `uid`, `username`,
`scope`, `iat`, `exp`, `jti`.

### 2f. Revocation

```
POST /api/v1/oauth/revoke
client_id=…&client_secret=…&token=<access_or_refresh_token>
```

---

## 3. What IyolMe must HOST — endpoints we call

All our calls carry these headers:

```
X-HT-Client:    iyol_live_xxx
X-HT-Timestamp: <unix seconds>
X-HT-Signature: hex( hmac_sha256( "{timestamp}.{raw_json_body}", client_secret ) )
Content-Type:   application/json
```

**You must verify** on every inbound call:
1. `X-HT-Client` matches the issued client_id
2. `abs(now - X-HT-Timestamp) <= 300` seconds (replay protection)
3. `hash_equals( expected_sig, X-HT-Signature )` computed over the **raw** body

Respond `401 {"error":"invalid_signature"}` on mismatch.

### 3a. `POST {iyol_api_base}/hitune/v1/reels/create-from-hitune`

Ingest a rendered clip (karaoke take / short preview) from HiTune into your feed.
**This is the endpoint the strategy doc names** — implemented on YOUR side.

```json
{
  "reel": {
    "media_url":   "https://music.hitune.in/files/…/clip.mp4",
    "media_type":  "video",
    "thumb_url":   "https://…",
    "caption":     "user caption",
    "duration_ms": 28000,
    "source":      "hitune"
  },
  "attribution": {
    "track_hash":  "3caf3fc8fea2b88ac83529b0984d7b3f",
    "title":       "Hawayein (From \"Jab Harry Met Sejal\")",
    "artist":      "Pritam & Arijit Singh",
    "hitune_url":  "https://music.hitune.in/music/track/hawayeinfrom…",
    "label":       "Original Sound by @username on HiTune Music"
  },
  "creator": {
    "sub": "u:584628cb…", "username": "admin",
    "name": "…", "avatar": "https://…",
    "profile_url": "https://music.hitune.in/@admin"
  },
  "sso_code": "htc_…",
  "ai_disclosure": { "ai_generated": false, "ai_percent": null }
}
```

Notes:
- `media_url` is a public URL on our CDN — fetch it server-side and store your own copy.
- `sso_code` lets you bind this post to a HiTune identity even if the IyolMe account
  session isn't linked yet — exchange it via `oauth/token` (authorization_code grant).
- `attribution` may be `null` for user-original audio (e.g. own karaoke with no
  backing track). When present it is **mandatory to render** (§5).
- `ai_disclosure` must be honored (§6).

Expected response:

```json
{ "ok": true, "reel_id": "…", "reel_url": "https://iyolme.com/r/…", "sound_id": "…" }
```

Return `4xx` + `{"error":"…"}` for validation failures — we surface them to the user.

### 3b. `POST {iyol_api_base}/hitune/v1/sounds`

Register an officially-published track in your sound library (fires when a
HiTune Distribution release goes live on HiTune Music):

```json
{
  "sound": {
    "hitune_track_hash": "3caf3fc8…",
    "title": "Song", "artist": "Artist", "artist_hash": "…",
    "album": "Album", "duration_ms": 290000,
    "cover": "https://…", "explicit": false,
    "ai_pct": 75, "ai_badge": "AI Original",
    "hitune_url": "https://music.hitune.in/music/track/…",
    "attribution_label": "Original Sound by @artist on HiTune Music",
    "uploader": { "sub": "u:…", "username": "artist", "name": "…" }
  }
}
```

`ai_pct`/`ai_badge` (strategy doc §3 — "AI Original" badge) are also present in the
`/api/v1/audio/attribution` response under `track.ai_pct` / `track.ai_badge`.
When `ai_badge` is non-null you MUST display it on the sound/reel per the AI
tagging policy. `ai_pct` is `0`–`100`; `0` means fully human-created.

Make this sound selectable in your reel-creation "add audio" flow, keyed by
`hitune_track_hash`. Response: `{ "ok": true, "sound_id": "…" }` — keep the mapping
`sound_id ↔ hitune_track_hash`, you will need it for §4 attribution lookups.

### 3c. `POST {iyol_api_base}/hitune/v1/sounds/takedown`

```json
{ "sound": { "hitune_track_hash": "3caf3fc8…" } }
```

A release was taken down by admin/rights action. Remove/disable the sound and stop
new reels from using it (existing reels: mute or flag per your policy). Acknowledge
by sending us `sound.takedown_ack` (§7).

---

## 4. What HiTune hosts — endpoints you call

Base: `https://music.hitune.in`

| Endpoint | Auth | Purpose |
|---|---|---|
| `POST /api/v1/oauth/token` | client_secret | §2 grants |
| `GET /api/v1/oauth/userinfo` | Bearer JWT | current user profile |
| `POST /api/v1/oauth/revoke` | client_secret | revoke a token |
| `GET /api/v1/audio/attribution?track={hash}` | Bearer JWT (client_credentials ok) | metadata + rights when a sound is reused |
| `GET /api/v1/iyol/my_music?sub=u:{hash}` (or `?uid={id}`) | Bearer JWT (client_credentials ok) | the user's own sounds for the reel picker: AI Studio `song_gen` outputs (`hitune_track_hash` `ai{id}`, `audio_url`, `ai_pct:100`, `ai_badge:"AI Original"`) + distribution releases (`dist{id}` pseudo-hash or the real catalog hash when published, `origin:"dist"`) |
| `POST /api/v1/iyol/webhook` | HMAC headers | send us events (§7) |

Non-catalog items in `my_music` carry a direct `audio_url` — register them
in `tbl_sound` with `sound` = that URL (still keyed by `hitune_track_hash`).
Real catalog tracks have no `audio_url`; keep the `HITUNE:{hash}` convention.

### `POST {iyol_api_base}/hitune/v1/stories`  *(IyolMe hosts this)*

HiTune calls this (signed like every hitune/v1 endpoint) to power the IyolMe
stories rail inside the HiTune Music app. Body `{ "limit": 24 }`; respond:

```json
{ "ok": true, "stories": [
  { "id": 41, "type": 1, "content": "<abs url>", "thumbnail": "<abs url>",
    "hls_url": null, "duration": 12, "created_at": 1759…,
    "user": {"id":7,"username":"k","fullname":"K","avatar":"<abs url>"},
    "music": {"id":3,"title":"S","artist":"A","hitune_url":"…","open_in_hitune":"…"} }
] }
```

Only public stories (`audience_type=0`) younger than 24h. HiTune exposes a
public mirror at `POST /api/iyol/stories` for the app.

### `iyolme://` app deep links

| Link | Behaviour |
|---|---|
| `iyolme://reel/create?hitune_track_hash=..&title=..&artist=..&cover=..&duration_ms=..&hitune_url=..&ai_pct=..&ai_badge=..&audio_url=..` | resolve/register sound → open camera with song attached |
| `iyolme://story/{id}` | open the story viewer |
| `iyolme://auth/hitune?token=..&user_id=..` | SSO login callback (§2b) |

For non-user calls get a client token first:

```
POST /api/v1/oauth/token
grant_type=client_credentials&client_id=…&client_secret=…&scope=attribution
→ { "access_token": "eyJ…", "expires_in": 3600 }   // no refresh for client grants
```

### `GET /api/v1/audio/attribution?track=3caf3fc8…`

```json
{
  "track":  { "hash":"…","title":"…","artists":[…],"album":{…},
              "duration_ms":290000,"explicit":false },
  "attribution": {
    "label":          "Original Sound by @artist on HiTune Music",
    "hitune_url":     "https://music.hitune.in/music/track/…",
    "open_in_hitune": "https://music.hitune.in/track/3caf3fc8…"
  },
  "rights": {
    "source": "hitune_distribution",
    "submission_id": 12, "status": "launched", "isrc": "…", "label": "…",
    "rights_holder": { "sub":"u:…","username":"artist","name":"…" },
    "royalty_splits": [ { "party":"artist", "share":100, "note":"…" } ]
  }
}
```

Call this whenever your sound registry needs to refresh metadata/rights, and before
rendering attribution on reused sounds.

---

## 5. Mandatory attribution UI (growth loop)

Every reel built on a HiTune sound — whether pushed from HiTune or created in
IyolMe from a registered `hitune_track_hash` sound — must render:

1. **Attribution line:** `attribution.label` →
   *"Original Sound by @username on HiTune Music"*
2. **Smart button:** *"Listen Full Song on HiTune"* → open
   `attribution.hitune_url` (universal link — opens the HiTune app if installed,
   else the web player). For app deep-linking use
   `open_in_hitune` = `https://music.hitune.in/track/{hash}`.

This is the traffic-conversion half of the deal — please don't bury it.

---

## 6. AI & content policy requirements

HiTune enforces: no unauthorized voice cloning, no uncleared copyrighted samples,
no bulk spam uploads. On your side:

- Honor `ai_disclosure.ai_generated` / `ai_percent` — render an **"AI Original"**
  badge on those reels.
- Honor `explicit: true` on registered sounds per your content policy.
- On `sound.takedown` — act within 24h and ack with a webhook event.

---

## 7. Webhooks you send us

```
POST https://music.hitune.in/api/v1/iyol/webhook
Content-Type: application/json
X-IYOL-Timestamp: <unix seconds>
X-IYOL-Signature: hex( hmac_sha256( "{timestamp}.{raw_body}", webhook_secret ) )

{ "event": "<name>", "data": { … } }
```

| event | data | What we do |
|---|---|---|
| `reel.published` | `reel_id`, `reel_url`, `hitune_user_sub` (`u:hash`) or `hitune_uid` | notify the user their reel is live |
| `reel.removed` | `reel_id` | logged |
| `sound.used` | `hitune_track_hash`, `reel_id` | engagement/royalty loop input |
| `sound.takedown_ack` | `hitune_track_hash` | closes our takedown outbox row |

We reply `200 {"received":true}` — retry on 5xx with backoff; we may replay-check
via the 5-minute timestamp window.

---

## 8. Errors & conventions

- OAuth endpoints return RFC 6749 errors:
  `{"error":"invalid_client|invalid_grant|unsupported_grant_type|temporarily_unavailable","error_description":"…"}`
- Other HiTune API errors:
  `{"error":{"code":"invalid_token|not_found|disabled|invalid_request","message":"…"}}`
- All JSON, all HTTPS. Token TTLs: auth code 10 min, access JWT 1 h, refresh 30 d.
- Rate limits: be reasonable (< 60 rpm on `attribution`); cache track metadata.

## 9. Go-live checklist

- [ ] You: send `iyol_api_base` + redirect URI(s) → we register them
- [ ] You: receive `client_id` / `client_secret` / `webhook_secret` (secure channel)
- [ ] You: implement §3 endpoints + HMAC verification
- [ ] You: "Login with HiTune" (authorize → token → userinfo) — web + app
- [ ] We:  enable `iyol_api_base`, run a signed ping test from admin settings
- [ ] Joint test: code exchange → userinfo; reel push → webhook `reel.published`;
      sound register → `attribution` lookup → takedown + ack

---

*HiTune side implemented 2026-09-29 — endpoints live on `music.hitune.in`.
Integration toggle + credentials: HiTune admin → HiTune Tools → IyolMe Integration.*
