HiTune Ecosystem & IyolMe Integration Master Strategy Plan
1. Executive Summary
This strategy document outlines the unified blueprint for merging three distinct audio and social products into a single interconnected media powerhouse: HiTune Music (OTT Streaming & Creator App), HiTune Distribution (Global & Local Distribution Platform), and IyolMe (Reels & Social Media Platform).

By linking music creation, automated distribution, social content consumption, and direct monetization, the combined ecosystem delivers a complete end-to-end network for independent creators, listeners, and casual social app users.

Ecosystem Pillar
Primary Role
Core Objective
HiTune Music
Streaming OTT & Creator Platform
High-fidelity playback, Karaoke, AI tooling, discovery feeds
HiTune Distribution
Aggregator & Ingestion Engine
Direct platform streaming, global distribution, unified payouts
IyolMe
Social Media & Short-Form Video
Viral content distribution, audience conversion, discovery engine



2. Platforms Overview
HiTune Music
A next-generation streaming application combining classic OTT audio capabilities (similar to Spotify) with creator-centric production tools.

Key Components: On-demand full-track streaming, AI-assisted background playback, interactive Karaoke mode with real-time audio muting, and a dedicated vertical short music preview feed for rapid song discovery.
HiTune Distribution
A direct-to-platform and global distribution portal built for modern independent artists and digital labels.

Key Components: Automated ingestion into global DSPs (Apple Music, Spotify, Amazon Music) combined with instant, zero-delay local publishing directly onto HiTune Music and IyolMe. Offers multi-tier royalty splits and centralized financial analytics.
IyolMe
A high-engagement social media platform focused on short-form video reels, creator feeds, and real-time social engagement.

Key Components: Swipeable video feeds, interactive comment layers, audio-driven video challenges, and an embedded direct link architecture that converts social viewers into dedicated music streaming subscribers.


3. AI Music Policies & Guidelines
To foster ethical innovation while protecting original creators and maintaining platform trust, the ecosystem enforces clear parameters around artificial intelligence assets.
Policy Framework
Status
Category
Scope & Requirements
 Allowed
Original AI Compositions
Fully synthetic instrumental tracks where the creator holds commercial generation rights.
 Allowed
Commercial Voice Synthesis
Vocal tracks created via paid commercial tier platforms (e.g., Suno AI, Udio AI) with validated commercial licenses.
 Prohibited
Voice Cloning
Unauthorized emulation or vocal synthesis of existing public figures, artists, or celebrities.
 Prohibited
Copyright Infringement
Sampling, interpolation, or recreation of copyrighted master recordings without express written clearance.
 Prohibited
Bulk / Spam Uploads
Low-effort, automated mass uploads designed to game playback algorithms or farm royalty pools.

Safety & Compliance Architecture
Mandatory Disclaimers: All uploads require explicit metadata tagging regarding the presence and percentage of AI generation utilized during creation.
Automated & Manual Review Queues: Ingested audio passes through fingerprinting algorithms to detect copyrighted stems, followed by manual review for flagged edge cases.
Visual Tagging: Tracks utilizing AI vocals or full synth arrangements display a mandatory "AI Original" visual badge across HiTune Music and IyolMe.


4. Key Differentiating Features (USP)
1-Click Direct Ecosystem Publish
Distribution submissions bypass multi-day processing queues for internal platform placement. Artists publishing through HiTune Distribution can opt for instant availability across HiTune Music and IyolMe audio libraries, backed by unified payout wallet infrastructure.
AI Creator Studio & Tools
Integrated utility suites available directly inside the creator portal:

AI Cover Art Generator: High-resolution 3D and digital artwork generation tailored to audio mood metrics.
AI Audio Mastering: Automated loudness normalization, EQ profiling, and dynamic range optimization.
Smart Sync Lyrics: Automatic line-by-line and word-by-word lyric alignment for playback and karaoke modes.
Karaoke & Sing-Along Mode
Proprietary vocal isolation models allow real-time background vocal suppression on any uploaded or streaming track. Users can record video or audio sing-along takes with built-in pitch monitoring and custom vocal filters.
Direct Fan-to-Artist Monetization
A digital economy powered by unified micro-currency tokens:

Super-Likes & Tipping: Listeners can send virtual gifts and direct tips to creators during track playback or live video streams.
100% Royalty Events: Dedicated platform promotional windows where 100% of user streaming royalties go directly to independent artists.
Short Clip / Vertical Reels Discovery
A dedicated discovery tab inside HiTune Music featuring 15–30 second high-energy clips. Users can seamlessly swipe through trending fragments, jump into full-length track streaming, or immediately launch the clip as an audio stem inside IyolMe.


5. Cross-Platform Integration: HiTune Music + IyolMe
Seamless User Journey
[HiTune Music] User records Karaoke / Short Clip 

       │

       ▼

[Publish Action] Tap 'Publish as Reel on IyolMe'

       │

       ▼

[Auth Layer] Single Sign-On (SSO / OAuth 2.0) verifies account link

       │

       ▼

[IyolMe Feed] Video posts directly with mandatory Audio Attribution:

              "Original Sound by @username on HiTune Music"
Mutual Growth Loop
IyolMe Content Inflow: Receives a continuous, organic influx of user-generated content, high-quality karaoke performances, and viral audio snippets directly from HiTune creators.
HiTune Traffic Conversion: Every reel published on IyolMe contains an interactive smart button reading Listen Full Song on HiTune. Clicking this instantly opens the exact track within HiTune Music, driving continuous user acquisition and increasing streaming revenue.


6. Technical Architecture & Backend Roadmap
Authentication & Authorization
Unified Single Sign-On (SSO): Built on an OAuth 2.0 and JWT framework. A single security token allows frictionless navigation, profile synchronization, and asset sharing across HiTune Music, HiTune Distribution, and IyolMe.
Integration APIs & Microservices
Centralized API Architecture: Standardized RESTful endpoints and Webhooks manage cross-platform media creation.
POST /api/v1/reels/create-from-hitune: Ingests rendered video files, metadata tags, and user permissions from HiTune directly into IyolMe's media distribution network.
GET /api/v1/audio/attribution: Resolves underlying song metadata and royalty splits when tracks are reused.
Audio Attribution Sync
Automated media indexing registers every exported clip as an official audio stem within IyolMe's sound registry. When secondary creators build new reels using that sound, the system automatically preserves the original attribution link back to the primary HiTune artist profile, keeping royalty and engagement loops intact.


7. Updated Upload & Distribution Workflow Policy (HiTune Music <-> HiTune Distribution Integration)
Redirection Workflow
Removal of Direct Uploads: Direct song upload functionality inside the HiTune Music app and website has been disabled.
Automated Portal Redirection: Clicking 'Upload Song' anywhere in HiTune Music automatically redirects creators directly to the HiTune Distribution portal.
Authentication & Metadata Submission: Users must log in or authenticate on HiTune Distribution and complete all required metadata, including artist details, album art, ISRC codes, rights declarations, AI disclaimers, and target platform selections.
Distribution & Rejection Rules
HiTune Music-Only Approval: If a song fails global DSP distribution criteria (e.g., duplicate content, technical quality issues, or partial copyright concerns) but is approved by Admin for local streaming, the dashboard status displays: "Live on HiTune Music | Not Distributed Globally" along with the specific reason.
Full Platform Rejection: If a song is completely rejected by Admin (due to unauthorized copyright, voice cloning, or severe policy violations), the dashboard status displays: "Rejected for All Platforms (Including HiTune Music)" accompanied by explicit feedback.
Admin Controls & Dashboard Feedback
Granular Creator Dashboard Statuses: The HiTune Distribution creator dashboard clearly reflects real-time status updates including 'Live on HiTune', 'Globally Distributed', 'Pending Admin Review', and 'Rejected' with detailed feedback messages.
Independent Admin Override: Admins retain total authority to approve tracks exclusively for HiTune Music streaming even when third-party DSP distribution is declined.
8. Business Model, Marketing Strategies & Community Features
Community Features & Engagement
Artist Choice & Radio Stations: 24/7 online radio stations and live listening parties for creators and AI artists.
Leaderboards & Charts: Weekly charts ("Top 50 AI Songs", "Top 100 Indie Stars", "Viral HiTune Tracks") promoted via HiTune Distribution.
Business Models & Monetization Policies
Freemium Pro Uploads: Free uploads for initial tracks with "HiTune Creator Pro" subscription for unlimited AI tools and mastering.
0% Commission Days: Monthly "Creator Day" offering 100% royalty payout with zero distribution fee.
Referral Bonus Program: Referral rewards and credits for onboarding new artists to HiTune Distribution.
Marketing & Positioning Campaign
"Upload & Earn Instantly" Campaign: Direct targeting against slow DSP deployment times.
Collab Competitions: Monthly "Best AI Song" and "Best Indie Track" contests offering free distribution and homepage features.
Freemium Benefits: High-quality ad-free listening and downloads.
9. AI Architecture, Models & Admin Panel Management
AI Models & Engines Matrix
Cover Art Generation: Stable Diffusion XL (SDXL) / FLUX.1 / Midjourney API / DALL-E 3 for 3D/HD artwork.
Audio Mastering & Enhancement: Matchering (Open-source AI mastering) / DeepAFx / Masterchannel API for studio loudness normalization and EQ profiling.
Vocal Isolation & Karaoke: Demucs v4 (Meta AI) / Spleeter (Deezer) for real-time vocal muting and stem separation.
Lyrics Synchronization: OpenAI Whisper / Wav2Vec 2.0 for automatic LRC timecode alignment.
AI Music Generation (External Integrations): Suno AI API / Udio AI API for optional direct music generation inside creator studio.
Optional & Configurable AI Feature Toggles
Global & Plan-Based Toggles: Every AI tool (Cover Generator, Mastering, Karaoke, Lyrics Sync) can be turned ON/OFF globally or per user plan via the Admin Panel.
Manual Options: Creators can bypass AI tools and upload their own artwork/mastered audio manually.
Admin Panel Control & Dynamic AI Model Swapping
Dynamic API Key & Provider Switching: Admin can change API keys, models (e.g., switch from OpenAI Whisper to Wav2Vec), or endpoint URLs directly from the Admin Panel without changing code or redeploying apps.
Cost Control & Rate Limiting: Admin can set daily AI generation quotas per user/plan to control API usage costs.

---

## 10. Implementation Status Matrix (as of 2026-09-29)

Honest build status per feature. **Done** = implemented AND wired to UI.
**Partial** = exists but needs external config/provider or is incomplete.
**Config required** = code ready, production value must be set by admin.
**External dependency** = depends on another server/service being online.

### Creator & AI Tools

| Feature | Status | Where / Notes |
|---|---|---|
| AI Cover Art Generator | **Done (code)** / config for provider | `ai-studio.php` + `/api/ai_studio` + `scripts/ai_worker.php`; OpenAI-compatible image API key needed in Music admin for real generation; local placeholder otherwise |
| AI Audio Mastering | **Done** | Matchering engine installed (`/opt/htx-ai`), ffmpeg loudnorm fallback; AI Studio → Mastering |
| Smart Lyrics Sync | **Done** | Whisper/faster-whisper installed; API fallback configurable |
| Karaoke / Vocal Isolation | **Done** | Demucs installed; AI Studio → Karaoke; listener-facing record-over UI is app roadmap |
| AI Song Generation | **Partial** | Queue + UI + plan/quota gating done; **needs external provider key** (Suno-style API) configured in Music admin — without it jobs return provider_not_configured |
| Clip / Reel video maker | **Done** | ffmpeg cover+extract render → `iyol_publish` handoff |
| AI badges & policy enforcement | **Done** | `ai_pct`, `ai_badge`, `ai_declared` on releases/tracks; badge surfaces in app, IyolMe sounds + post-level `ai_badge` metadata |
| Per-plan AI quotas + admin toggles | **Done** | `_bof_setting` ai toggles, per-plan daily quotas, admin Features tab |

### Artist Experience

| Feature | Status | Where / Notes |
|---|---|---|
| Artist Panel (unified) | **Done** | `distribution.hitune.in/index.php?q=artist-panel` — verification (HiTune/Spotify/Apple), analytics, public page, growth |
| HiTune Music verification | **Done** | Panel → Music `v1/dist/artist` bridge → approved `user_request` + artist link (e2e tested) |
| Spotify verification | **Done** | Panel form + admin review at `web/admin/artist_verifications.php` |
| Apple Music verification | **Done** | Same flow as Spotify |
| Public artist page | **Done** | `?q=artist-public&slug=` (404 for unknown slugs — tested) |
| Real-time analytics + geo | **Done** | Per-play geo logging (`ipwho.is`) in `_u_analytics_events`; country/city breakdown in panel analytics |
| Split royalties | **Partial** | `includes/splits.php` + release-level splits UI exist; automated payout splitting is future automation |
| Tipping / wallet | **Done** | `_htx_tips/_htx_wallet/_htx_topups`, `/api/dist/tip` (wallet/history/topup via Razorpay), app Tip screens, web Hub wallet tab |

### Listener Engagement (Hub)

| Feature | Status | Where / Notes |
|---|---|---|
| Weekly charts (Viral / Top 50 AI / Indie) | **Done** | `/api/htx/charts` (app) + public `/api/hub/charts` + `music.hitune.in/hub#charts` + sidebar/navbar menus |
| Short Clips discovery | **Done** | `/api/htx/clips` + `/api/hub/clips` + `hub#clips` + app Clips screen |
| Radio stations | **Done** | `/api/htx/radio` (station directory + seed/artist/genre/chart queues) + `hub#radio` + app |
| Listening parties | **Done** | `/api/htx/party` (list/create/join/leave/set_track/end) + `hub#parties` + app Parties screen |
| Contests | **Done (engine)** | `_htx_contests` + `/api/htx/contests` + `hub#contests`; **admin must create contest rows** to go live |
| Karaoke for listeners | **Partial** | Engine + jobs exist; record-over listener UI is app roadmap |
| Voice / Jam rooms | **Missing** | Not built (parties are sync-listen, no live audio rooms) |

### Distribution & Workflow

| Feature | Status | Where / Notes |
|---|---|---|
| Upload redirect Music → Distribution | **Done** | Music `/upload` and app Upload both route to `distribution.hitune.in`; direct upload removed |
| 1-Click publish Distribution → HiTune Music | **Done** | `v1/dist/ecosystem` HMAC bridge publishes approved releases to catalog |
| Release status labels | **Done** | `ecosystem_sync.php` statuses: Pending Admin Review / Live on HiTune / Globally Distributed / Live on HiTune Music \| Not Distributed Globally / Rejected for All Platforms / Taken Down |
| Freemium free releases | **Done** | `settings.free_releases_enabled/per_month` (default 2/month), gates in `release_create` + `release_step4`, dashboard + release-flow banners, admin Settings → Freemium & Growth |
| Creator Day (0% commission) | **Done (banner + music-side uplift)** | `creator_day_enabled/date` settings + dashboard/Artist Panel banners; `tip_artist_pct`/`tip_event_until` on Music applies the 100% split |
| Referral bonuses | **Done** | `includes/referral.php`, referral events, admin `referrals.php` (credit on first live release) |
| Subscriptions & plans | **Done** | Artist ₹699 / Artist Pro ₹1,499 / Label ₹2,499 yearly + per-release Single/EP/Album; Razorpay checkout |
| Global DSP delivery | **External dependency** | Metadata/platforms prepared; actual DSP delivery pipeline is manual/third-party (no live DSP API contract yet) |

### IyolMe Integration

| Feature | Status | Where / Notes |
|---|---|---|
| OAuth SSO (Login with HiTune) | **Done (code)** / external dependency | HiTune `/api/oauth/authorize` + `v1/oauth/token` + IyolMe `hitune/login`,`hitune/callback`,`sso/exchange` + app button |
| Sound registry sync | **Done (code)** / external dependency | HiTune outbox → `POST /api/hitune/v1/sounds` on IyolMe (HMAC) |
| Sound takedown | **Done (code)** | takedown endpoint + `hitune_taken_down` flag + reel blocking + `sound.takedown_ack` |
| Reel publishing from HiTune | **Done (code)** / external dependency | `/api/iyol_publish` → signed `reels/create-from-hitune` w/ media download + attribution + `reel.published`/`sound.used` webhooks |
| Attribution UI + "Listen Full Song" | **Done** | IyolMe app: `ReelSoundAttribution`, `AudioSheet` link, `open_in_hitune` URL field |
| AI disclosure propagation | **Done** | `ai_disclosure` + `ai_badge` on reel `metadata`; app renders post-level badge |
| Webhook retry/durability | **Done** | Both sides have durable outboxes: HiTune `_iyol_outbox`, IyolMe `tbl_hitune_outbox` + `hitune:flush-outbox` scheduler |
| Signed connection test | **Done** | HiTune admin test → `POST /hitune/v1/ping` (added on IyolMe) |
| **Production pairing** | **Config required** | `iyol_api_base` empty on Music; IyolMe `HITUNE_CLIENT_ID` must equal `iyol_client_id`; IyolMe API host currently unreachable from the Music server — set `iyol_api_base=https://api.iyolme.com/api` once deployed |
| Web `reel/{id}` share page | **Done** | Public page with attribution + HiTune link |
| Song → reel deep link (`iyolme://reel/create`) | **Done (code)** | Music app "Create Reel on IyolMe" (player actions + collection rows) carries track metadata; IyolMe `HituneLinkService` → `post/resolveHituneSound` → camera with song attached |
| IyolMe stories inside HiTune app | **Done (code)** | IyolMe `hitune/v1/stories` (signed) → HiTune public mirror `POST /api/iyol/stories` → app `IyolStoriesRail` home row → tap opens `iyolme://story/{id}` |
| My Music picker (AI + dist songs in IyolMe) | **Done (code)** | HiTune `GET /api/v1/iyol/my_music` (Bearer) serves AI Studio songs + distribution releases; IyolMe `post/fetchMyHituneMusic` upserts them into `tbl_sound`; MusicSheet "+" → MyMusicScreen with AI Studio banner + HiTune-connect state |

### Admin & Platform

| Feature | Status | Notes |
|---|---|---|
| Admin feature toggles (AI tools, quotas) | **Done** | Music admin settings + per-plan features |
| Admin verification review | **Done** | `web/admin/artist_verifications.php` approve/reject/revoke |
| App release/build | **Partial** | `hitune-music-app` Flutter repo updated (Hub screens, Artist Panel link); no Flutter toolchain on this server — release APK must be built on a dev machine with `--dart-define-from-file .env` |
| IyolMe app release | **Partial** | Same — code pushed; build/deploy on IyolMe infra |
