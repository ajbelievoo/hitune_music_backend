# Implementation Plan: HiTune Music Distribution Platform Redesign

## Overview

Incremental implementation of the complete HiTune platform redesign in pure PHP + MySQL (no framework). Each task builds on the previous, starting with the shared design system and security foundation, then page-by-page feature work, finishing with property-based tests. All code targets PHP 8.1+ with MySQLi prepared statements.

## Tasks

- [ ] 1. Design system foundation
  - [ ] 1.1 Rewrite `assets/premium-theme.css` with full CSS variable design tokens
    - Define CSS variables: `--primary-gradient`, `--accent-green`, `--bg-dark: #0a0a0f`, `--glass-bg: rgba(255,255,255,0.03)`, `--glass-blur: blur(20px)`, typography scale, spacing scale
    - Add base reset, `body` dark background, Poppins font-face import
    - Add `.glass-card` utility class with `backdrop-filter: blur(20px)`, border, hover `transform: translateY(-8px)` and glow `box-shadow`
    - Add `.btn-primary`, `.btn-secondary`, `.badge-*` (draft/submitted/in_progress/ready/rejected) utility classes
    - Add responsive grid utilities and breakpoint variables
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [ ] 1.2 Rewrite `includes/header_premium.php` as the single canonical header
    - Accept PHP variables: `$pageTitle`, `$metaDescription`, `$metaKeywords`, `$ogImage`, `$canonicalUrl`, `$jsonLd`, `$path`
    - Output full `<!DOCTYPE html>` through `<main class="main-content">` opening tag
    - Include Google Fonts (Poppins 400/500/600/700/800), MDI 6.5.95 CSS, `assets/premium-theme.css`
    - Output all SEO meta tags: title, description, keywords, canonical, OG (5 tags), Twitter Card (4 tags)
    - Output JSON-LD `<script type="application/ld+json">` block when `$jsonLd` is set
    - Generate CSRF token: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))` if not already set
    - Render fixed glassmorphism navbar with logo, nav links, auth buttons, active link highlighting via `$path`
    - _Requirements: 1.1, 1.5, 3.1, 3.2, 3.3, 3.4_

  - [ ] 1.3 Rewrite `includes/footer_premium.php` as the single canonical footer
    - Output `</main>`, full footer HTML with link groups (About, Pricing, Services, Stores, Help, Contact, Privacy, Terms), social links
    - Conditionally load Chart.js CDN only when `$loadCharts = true`
    - Include Intersection Observer scroll-animation script (fade-in on `.animate-on-scroll` elements)
    - Include hamburger menu toggle script for mobile drawer
    - Output `</body></html>`
    - _Requirements: 1.1, 1.6, 1.7_

  - [ ] 1.4 Create `includes/csrf.php` with CSRF helper functions
    - Implement `generateCsrfToken(): string` — generates and stores token in session
    - Implement `validateCsrfToken(string $token): bool` — compares with session token using `hash_equals()`
    - Implement `csrfField(): string` — returns `<input type="hidden" name="csrf_token" value="...">` HTML string
    - _Requirements: 15.9_

  - [ ] 1.5 Update `includes/auth.php` with enhanced helper functions
    - Implement `isLoggedIn(): bool`, `requireLogin(): void`, `loginUser(int $id, string $email, string $name): void`, `logoutUser(): void`
    - Implement `getCurrentUser(): ?array` — fetches user row plus active subscription from DB
    - Implement `hasActiveSubscription(): bool`, `getUserPlan(): ?string`, `getUserPlanFeatures(): array`
    - _Requirements: 4.6, 4.7, 11.7, 11.10_

  - [ ] 1.6 Run schema migrations for new and altered columns
    - Add `label_name VARCHAR(255)` and `scheduled_release_date DATE` columns to `releases` table (using `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`)
    - Add `email_verified TINYINT(1) DEFAULT 0` and `verification_token VARCHAR(64)` columns to `users` table
    - Create `payouts` table as specified in design document
    - Insert default `settings` rows: `min_payout_threshold=500`, `support_email`, `platform_name`, `og_image_url`
    - Write all DDL into `setup_database.sql` additions (append, do not replace existing)
    - _Requirements: 4.4, 10.3, 11.4_


- [ ] 2. SEO infrastructure
  - [ ] 2.1 Create `pages/sitemap.php` — dynamic XML sitemap generator
    - Output `Content-Type: application/xml` header
    - List all public routes with hardcoded `<changefreq>` and `<priority>` values; set `<lastmod>` to file `filemtime()`
    - Include: home, pricing, stores, help, contact, about, careers, sell, services, privacy, terms
    - _Requirements: 3.7_

  - [ ] 2.2 Create `pages/robots.php` — robots.txt generator
    - Output `Content-Type: text/plain` header
    - Disallow `/admin/`, `/pages/`, `/uploads/`; include `Sitemap:` directive pointing to `https://web.hitune.in/sitemap.xml`
    - _Requirements: 3.8_

  - [ ] 2.3 Add `.htaccess` rewrite rules for sitemap and robots, and HTTPS redirect
    - Add `RewriteRule ^sitemap\.xml$ pages/sitemap.php [L]`
    - Add `RewriteRule ^robots\.txt$ pages/robots.php [L]`
    - Add HTTPS redirect block: `RewriteCond %{HTTPS} off` → `RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]`
    - Add `Cache-Control` headers for static assets (`*.css`, `*.js`, `*.png`, `*.jpg`, `*.woff2`) with `max-age=86400`
    - _Requirements: 3.7, 3.8, 15.1, 15.10_

  - [ ]* 2.4 Write property test for SEO meta tags presence (Property 1)
    - **Property 1: SEO meta tags are present on every public page**
    - **Validates: Requirements 3.1, 3.4**
    - Class: `SeoMetaTagsPropertyTest`, use Eris `elements()` generator over public route list
    - Assert rendered HTML contains non-empty `<title>` (≤60 chars), `<meta name="description">` (≤160 chars), `<link rel="canonical">`

  - [ ]* 2.5 Write property test for OG and Twitter Card tags (Property 2)
    - **Property 2: Open Graph and Twitter Card tags are present on every public page**
    - **Validates: Requirements 3.2, 3.3**
    - Class: `SeoMetaTagsPropertyTest`, assert all 5 OG tags and all 4 Twitter Card tags present

  - [ ]* 2.6 Write property test for heading hierarchy (Property 3)
    - **Property 3: Heading hierarchy is never skipped on any page**
    - **Validates: Requirements 3.5**
    - Class: `SeoMetaTagsPropertyTest`, parse heading sequence from rendered HTML, assert no level is skipped

  - [ ]* 2.7 Write property test for image alt attributes (Property 4)
    - **Property 4: All images have non-empty alt attributes**
    - **Validates: Requirements 3.10, 15.2**
    - Class: `SeoMetaTagsPropertyTest`, assert every `<img>` in rendered HTML has non-empty `alt`


- [ ] 3. Homepage redesign
  - [ ] 3.1 Rewrite `pages/home.php` — hero section and stat counters
    - Set `$pageTitle`, `$metaDescription`, `$canonicalUrl`, `$jsonLd` (Organization + WebSite schema) before including header
    - Render full-viewport hero with animated headline (CSS keyframe), subtitle, two CTA buttons
    - Render four animated stat counters (150+ Platforms, 100K+ Artists, 1B+ Streams, 100% Royalties) using `data-target` attributes and a JS counter animation triggered by Intersection Observer
    - _Requirements: 2.1, 2.8, 3.1, 3.6_

  - [ ] 3.2 Add DSP marquee section to `pages/home.php`
    - Render scrolling marquee with 12+ DSP logos using CSS `animation: marquee linear infinite`
    - Include "View all 150+ stores" link to `?q=stores`
    - Use `loading="lazy"` on all logo images; add descriptive `alt` attributes
    - _Requirements: 2.2, 3.10, 15.2_

  - [ ] 3.3 Add features grid, testimonials, pricing preview, and final CTA to `pages/home.php`
    - Render 6-card features grid with MDI icons, headings, descriptions; add `.animate-on-scroll` class for Intersection Observer fade-in
    - Render 3 testimonial cards with name, genre, quote
    - Render pricing preview section with three plan cards and "View Full Pricing" CTA linking to `?q=pricing`
    - Render final CTA section with gradient background card and "Start Distributing Now" button
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7_


- [ ] 4. Authentication pages
  - [ ] 4.1 Rewrite `pages/signup.php` — registration form and handler
    - Render signup form: Full Name, Email, Password (min 8 chars), Confirm Password; include `csrfField()`
    - On POST: validate CSRF, validate fields, check duplicate email with prepared statement
    - Hash password with `password_hash($password, PASSWORD_BCRYPT)`, insert user record
    - Generate `verification_token`, call `email_helper.php` to send verification email
    - Display inline errors preserving form values; redirect to login on success with flash message
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [ ] 4.2 Rewrite `pages/verify_email.php` — email verification handler
    - Read `token` from query string, look up user by `verification_token` with prepared statement
    - Set `email_verified = 1`, clear `verification_token`, redirect to login with success message
    - Display error if token is invalid or already used
    - _Requirements: 11.5_

  - [ ] 4.3 Rewrite `pages/login.php` — login form and handler
    - Render login form: Email, Password, "Forgot Password" link; include `csrfField()`
    - On POST: validate CSRF, fetch user by email, verify password with `password_verify()`
    - On success: call `loginUser()`, redirect to dashboard
    - On failure: display "Invalid email or password." (same message regardless of which field failed)
    - _Requirements: 11.6, 11.7, 11.8_

  - [ ] 4.4 Wire Google OAuth callback in `pages/google_callback.php`
    - Ensure `config/google_oauth.php` reads client ID/secret from `settings` table or `config.php`
    - On successful OAuth: upsert user record, call `loginUser()`, redirect to dashboard
    - _Requirements: 11.9_

  - [ ] 4.5 Implement logout route in `index.php`
    - Handle `?q=logout`: call `logoutUser()`, redirect to `?q=login`
    - _Requirements: 11.10_

  - [ ]* 4.6 Write property test for password hashing (Property 18)
    - **Property 18: Passwords are always stored as bcrypt hashes**
    - **Validates: Requirements 11.2**
    - Class: `AuthSecurityPropertyTest`, generate arbitrary password strings, assert stored value starts with `$2y$` and `password_verify()` returns true

  - [ ]* 4.7 Write property test for valid login session completeness (Property 19)
    - **Property 19: Valid login always creates a complete session**
    - **Validates: Requirements 11.7**
    - Class: `AuthSecurityPropertyTest`, assert session contains `user_id`, `email`, `name`, `login_time` with non-null values

  - [ ]* 4.8 Write property test for login error message uniformity (Property 20)
    - **Property 20: Invalid login never reveals which field is incorrect**
    - **Validates: Requirements 11.8**
    - Class: `AuthSecurityPropertyTest`, assert error message is identical for wrong-email vs wrong-password attempts and does not contain identifying field names


- [ ] 5. Subscription and payment system
  - [ ] 5.1 Rewrite `pages/pricing.php` — pricing page with plan cards
    - Set SEO variables and `$jsonLd` (Organization schema)
    - Render three plan cards: Rising Artist (₹1,599/yr), Breakout Artist (₹2,799/yr), Professional (₹4,499/yr)
    - Each card lists features, highlights recommended plan, links "Get Started" to `?q=checkout&plan=<plan_id>`
    - Include FAQ accordion section; add JSON-LD `FAQPage` schema for pricing FAQs
    - _Requirements: 4.1, 3.6_

  - [ ] 5.2 Rewrite `pages/checkout.php` — checkout page and order creation
    - Call `requireLogin()`; read `plan` from query string, validate against allowed plan IDs
    - Display plan name, price, feature summary
    - Render payment method selector (Razorpay / Cashfree) and "Pay Now" button; include `csrfField()`
    - On POST: validate CSRF, call `createRazorpayOrder()` or `createCashfreeOrder()` from `payment_config.php`, call `recordPayment()` with `status='pending'`, redirect to gateway or render gateway JS
    - _Requirements: 4.2, 4.3_

  - [ ] 5.3 Implement payment callbacks in `pages/payment_callback.php`
    - Handle Razorpay callback: call `verifyRazorpayPayment()`, on success call `activateSubscription()` and update payment to `completed`, on failure update payment to `failed`
    - Handle Cashfree webhook: call `verifyCashfreeWebhook()`, same success/failure logic
    - Check `payments.payment_order_id` uniqueness before processing (idempotency)
    - Redirect artist to dashboard with success/failure flash message
    - _Requirements: 4.3, 4.4, 4.5_

  - [ ]* 5.4 Write property test for successful payment creates active subscription (Property 5)
    - **Property 5: Successful payment always creates active subscription and completed payment records**
    - **Validates: Requirements 4.4**
    - Class: `PaymentFlowPropertyTest`, generate arbitrary plan/user/gateway combinations, assert `subscriptions.status='active'` and `end_date = start_date + 1 YEAR`

  - [ ]* 5.5 Write property test for failed payment creates failed record only (Property 6)
    - **Property 6: Failed payment always creates a failed payment record**
    - **Validates: Requirements 4.5**
    - Class: `PaymentFlowPropertyTest`, assert `payments.payment_status='failed'` and no subscription record created/activated

- [ ] 6. Checkpoint — ensure design system, auth, and payment tests pass
  - Ensure all tests pass, ask the user if questions arise.


- [ ] 7. Release submission wizard
  - [ ] 7.1 Rewrite `pages/release_create.php` — Step 1: Release Details
    - Call `requireLogin()` and `hasActiveSubscription()` (redirect to pricing if no active sub)
    - Render form: Release Title, Artist Name, Release Type (single/EP/album), Genre, Language, Release Date, UPC (optional), ISRC (optional)
    - Show "Custom Label Name" field only when plan is `breakout_artist` or `professional`
    - Show "Scheduled Release Date" picker only when plan is `professional`
    - Include `csrfField()`; on POST validate CSRF, validate required fields, INSERT or UPDATE release record with `step_completed=1`, `progress_percent=25`, `status='draft'`
    - Render progress bar showing Step 1 of 4 as active
    - _Requirements: 5.1, 5.2, 5.3, 5.9, 4.9, 4.10_

  - [ ] 7.2 Rewrite `pages/release_step2.php` — Step 2: Store Selection
    - Load all platforms from `platforms` table; pre-check all by default
    - Render platform grid with checkboxes; include "Select All / Deselect All" toggle
    - Include `csrfField()`; on POST validate CSRF, DELETE existing `release_platforms` rows for this release, INSERT selected platforms, update `step_completed=2`, `progress_percent=50`
    - Render progress bar showing Step 2 of 4 as active
    - _Requirements: 5.1, 5.2, 5.4, 5.11_

  - [ ] 7.3 Rewrite `pages/release_step3.php` — Step 3: Track Upload
    - Render track list with "Add Track" button; each track row has: Track Title, Artist, ISRC, audio upload widget
    - Wire audio upload widget to `pages/upload_chunked.php` via AJAX (chunked upload)
    - On POST: validate CSRF, validate at least one track with uploaded audio file exists, update `step_completed=3`, `progress_percent=75`
    - Display inline error if no valid track audio uploaded
    - Render progress bar showing Step 3 of 4 as active
    - _Requirements: 5.1, 5.2, 5.5, 5.8_

  - [ ] 7.4 Rewrite `pages/release_step4.php` — Step 4: Artwork Upload and Final Submit
    - Render cover art upload dropzone with preview
    - On upload: validate MIME type (`image/jpeg`, `image/png`), validate dimensions ≥ 3000×3000 using `getimagesize()`, validate size ≤ 50MB; return JSON error on failure
    - Include `csrfField()`; on POST "Submit Release": validate CSRF, confirm cover art uploaded, update `step_completed=4`, `progress_percent=100`, `status='submitted'`, `submitted_at=NOW()`, redirect to `?q=releases`
    - Display inline error if cover art missing or invalid
    - Render progress bar showing Step 4 of 4 as active
    - _Requirements: 5.1, 5.2, 5.6, 5.7_

  - [ ] 7.5 Enhance `pages/upload_chunked.php` — MIME type and size enforcement
    - Add MIME type whitelist check: `audio/mpeg`, `audio/wav`, `audio/x-wav`, `audio/flac`, `audio/aiff`
    - Enforce 200MB max assembled file size; return `{"success":false,"error":"File size exceeds the 200MB limit."}` on violation
    - Clean up temp chunks on failure
    - _Requirements: 15.6, 15.7_

  - [ ]* 7.6 Write property test for wizard step monotonic progression (Property 7)
    - **Property 7: Release wizard step completion is monotonically non-decreasing**
    - **Validates: Requirements 5.3, 5.4, 5.5, 5.6**
    - Class: `ReleaseWizardPropertyTest`, generate arbitrary step sequences, assert `step_completed` and `progress_percent` never decrease

  - [ ]* 7.7 Write property test for draft save preserves status=draft (Property 8)
    - **Property 8: Draft saves at any step preserve status=draft**
    - **Validates: Requirements 5.9**
    - Class: `ReleaseWizardPropertyTest`, assert `status='draft'` after save-as-draft at any step 1–4

  - [ ]* 7.8 Write property test for file upload validation (Property 23)
    - **Property 23: File upload validation rejects invalid MIME types and oversized files**
    - **Validates: Requirements 15.6, 15.7, 15.8**
    - Class: `SecurityPropertyTest`, generate invalid MIME types and oversized file sizes, assert upload rejected and file not saved to `uploads/`


- [ ] 8. Release status lifecycle and release list
  - [ ] 8.1 Rewrite `pages/releases.php` — artist releases list
    - Call `requireLogin()`; fetch all releases for current user with prepared statement
    - Render release cards with cover art thumbnail, title, type, status badge (color-coded per status), submitted date, "View Details" link
    - Status badge CSS classes: `badge-draft`, `badge-submitted`, `badge-in-progress`, `badge-ready`, `badge-rejected` (defined in `premium-theme.css`)
    - _Requirements: 6.1_

  - [ ] 8.2 Rewrite `pages/release_view.php` — artist release detail page
    - Fetch release by ID, verify ownership with prepared statement
    - Display all metadata, track list, cover art, status badge, status-specific message:
      - `submitted`: "Your release is under review. This typically takes 1–3 business days."
      - `in_progress`: "Your release is being distributed to stores. This may take 3–7 days."
      - `ready`: "Your release is live on all selected platforms!" with green checkmark
      - `rejected`: admin rejection notes + "Resubmit" button
    - Show admin notes if present (no indication they are manually set)
    - _Requirements: 6.2, 6.3, 6.4, 6.5, 6.8, 6.9_

  - [ ]* 8.3 Write property test for status badge correctness (Property 9)
    - **Property 9: Release status badge always matches the release's database status**
    - **Validates: Requirements 6.1**
    - Class: `ReleaseWizardPropertyTest`, generate all 5 valid status values, assert rendered badge label and CSS class match


- [ ] 9. Artist dashboard
  - [ ] 9.1 Rewrite `pages/dashboard.php` — stat cards and subscription info
    - Call `requireLogin()`; query: available balance (royalties minus approved payouts), lifetime streams, this month's earnings, active releases count — all with prepared statements
    - Render four stat cards with MDI icons and values
    - Render subscription info card: plan name, days remaining, next billing date, slot usage (e.g., "2/3 profiles used")
    - When no active subscription: render "Upgrade Plan" CTA card linking to `?q=pricing`
    - _Requirements: 8.1, 8.2, 8.3_

  - [ ] 9.2 Add recent releases, artist accounts, quick actions, and earnings chart to `pages/dashboard.php`
    - Render "Recent Releases" section: last 5 releases with cover art thumbnail, title, status badge, "View Details" link
    - Render "Artist Accounts" section: Spotify for Artists, Apple Music for Artists, YouTube OAC cards with links
    - Render "Quick Actions" section: "New Release", "View Analytics", "Request Payout", "Manage Artists" buttons
    - Show "Withdraw Earnings" button only when available balance ≥ `min_payout_threshold` from settings
    - Set `$loadCharts = true`; render monthly earnings bar chart (last 6 months) using Chart.js with data from `track_royalties` grouped by month
    - _Requirements: 8.4, 8.5, 8.6, 8.7, 8.8_

  - [ ]* 9.3 Write property test for withdraw button visibility (Property 12)
    - **Property 12: Withdraw button appears if and only if balance ≥ minimum threshold**
    - **Validates: Requirements 8.7**
    - Class: `DashboardPropertyTest`, generate arbitrary balance and threshold values, assert button visibility matches `balance >= threshold`


- [ ] 10. Royalties and payouts pages
  - [ ] 10.1 Rewrite `pages/royalties.php` — royalties table and balance display
    - Call `requireLogin()`; fetch all `track_royalties` rows for current user's tracks with prepared statement (JOIN releases, release_tracks)
    - Compute available balance: SUM(royalty_amount) minus SUM(approved payout amounts)
    - Render balance prominently at top; render royalties table: Track Title, Release, Platform, Reporting Period, Plays, Revenue, Royalty Amount
    - _Requirements: 10.1, 10.2_

  - [ ] 10.2 Rewrite `pages/payouts.php` — payout request form and history
    - Call `requireLogin()`; read `min_payout_threshold` from `settings` table
    - Render payout request form: Amount, Payment Method (Bank Transfer / UPI / PayPal), Account Details; include `csrfField()`
    - On POST: validate CSRF, validate amount ≥ threshold (error: "Insufficient balance. Your available balance is ₹[amount]." or minimum threshold error), validate amount ≤ available balance, INSERT into `payouts` with `status='pending'`, display confirmation message
    - Render payout history table: Date, Amount, Method, Status
    - _Requirements: 10.3, 10.4, 10.6, 10.7, 10.8_

  - [ ]* 10.3 Write property test for artist balance calculation (Property 13)
    - **Property 13: Artist balance equals total royalties minus approved payouts**
    - **Validates: Requirements 10.2**
    - Class: `DashboardPropertyTest`, generate arbitrary royalty and payout sets, assert computed balance equals sum(royalties) - sum(approved_payouts)

  - [ ]* 10.4 Write property test for payout request creates pending record (Property 14)
    - **Property 14: Payout request always creates a pending record**
    - **Validates: Requirements 10.4**
    - Class: `PayoutPropertyTest`, generate valid payout requests, assert exactly one `payouts` row with `status='pending'` inserted

  - [ ]* 10.5 Write property test for payout approval deducts balance (Property 15)
    - **Property 15: Payout approval always deducts from artist balance**
    - **Validates: Requirements 10.5**
    - Class: `PayoutPropertyTest`, assert balance_after = balance_before - approved_amount

  - [ ]* 10.6 Write property test for payout exceeding balance is rejected (Property 16)
    - **Property 16: Payout request exceeding balance is always rejected**
    - **Validates: Requirements 10.7**
    - Class: `PayoutPropertyTest`, generate amounts > available balance, assert request rejected with balance in error message

  - [ ]* 10.7 Write property test for payout below threshold is rejected (Property 17)
    - **Property 17: Payout request below minimum threshold is always rejected**
    - **Validates: Requirements 10.8**
    - Class: `PayoutPropertyTest`, generate amounts < configured threshold, assert request rejected with minimum amount in error message

- [ ] 11. Checkpoint — ensure wizard, dashboard, royalties, and payout tests pass
  - Ensure all tests pass, ask the user if questions arise.


- [ ] 12. Analytics page
  - [ ] 12.1 Rewrite `pages/analytics.php` — lifetime stats and charts
    - Call `requireLogin()`; query lifetime streams, lifetime revenue, total royalties from `track_royalties` with prepared statements
    - Render three summary stat cards at top
    - Set `$loadCharts = true`; build `$chartData` JSON for: monthly streams line chart (last 12 months), earnings per release bar chart (top 5 releases)
    - Render platform breakdown table: DSP name, streams, revenue (from `track_royalties` grouped by platform)
    - Render release-level breakdown table: Release Title, Artist, Total Streams, Total Revenue, Total Royalties
    - Render empty state message when no data exists
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_


- [ ] 13. Admin panel redesign
  - [ ] 13.1 Rewrite `admin/index.php` — admin dashboard with real-time stats
    - Call `requireAdmin()`; query: Total Users, Total Releases, Pending Review (status=submitted), In Progress, Published (status=ready), Total Revenue (SUM payments), Pending Payouts (SUM pending payout amounts)
    - Render stat cards with MDI icons using dark glassmorphism design
    - Render persistent left sidebar with all navigation links as specified in Requirement 7.2
    - _Requirements: 7.1, 7.2, 7.3, 7.11_

  - [ ] 13.2 Rewrite `admin/submissions.php` — releases management with status update
    - Call `requireAdmin()`; fetch all releases with JOIN to users, sorted by status priority: `submitted → in_progress → ready → rejected → draft`, ties broken by `created_at DESC`
    - Render status filter tab buttons (All, Submitted, In Progress, Published, Rejected, Draft)
    - Render releases table: ID, Title, Artist, Type, User, Status badge, Submitted Date, Actions (View, Update Status)
    - Inline status update form: dropdown (`submitted`, `in_progress`, `ready`, `rejected`) + "Update" button + notes textarea; include `csrfField()`
    - On POST: validate CSRF, UPDATE release status, set corresponding timestamp column (`in_progress_at`, `ready_at`, `rejected_at`), INSERT row into `activity_log`
    - _Requirements: 7.4, 7.5, 6.6, 6.7_

  - [ ] 13.3 Rewrite `admin/release_detail.php` — release detail with audio playback
    - Call `requireAdmin()`; fetch release with all tracks and platforms
    - Render all metadata, cover art preview, track list with HTML5 `<audio>` player for each track
    - Render status update form with notes field; include `csrfField()`
    - _Requirements: 7.6, 6.8_

  - [ ] 13.4 Rewrite `admin/royalties.php` — add royalty entries via modal
    - Call `requireAdmin()`; render royalties table grouped by release/track
    - Render "Add Royalty" button that opens a modal form: Track (dropdown), Platform, Reporting Period, Plays, Revenue, Royalty Amount; include `csrfField()`
    - On POST: validate CSRF, INSERT into `track_royalties`
    - _Requirements: 7.7_

  - [ ] 13.5 Rewrite `admin/payouts.php` — approve/reject payout requests
    - Call `requireAdmin()`; fetch all pending payout requests with artist name, amount, method, account details
    - Render approve/reject buttons per row; include `csrfField()` on each action form
    - On approve POST: validate CSRF, UPDATE `payouts.status='approved'`, set `processed_at=NOW()`
    - On reject POST: validate CSRF, UPDATE `payouts.status='rejected'`, set `processed_at=NOW()`
    - _Requirements: 7.8, 10.5_

  - [ ] 13.6 Rewrite `admin/users.php` and `admin/user_edit.php` — user management
    - Call `requireAdmin()`; render searchable users table: ID, Name, Email, Plan, Subscription Status, Joined Date, Actions
    - `user_edit.php`: render edit form for user fields; allow admin to manually assign or extend subscription (UPDATE `subscriptions` table); include `csrfField()`
    - _Requirements: 7.9_

  - [ ] 13.7 Rewrite `admin/settings.php` — platform settings form
    - Call `requireAdmin()`; load all settings from `settings` table
    - Render form: Razorpay Key/Secret, Cashfree App ID/Secret, Platform Name, Support Email, Min Payout Threshold, OG Image URL; include `csrfField()`
    - On POST: validate CSRF, UPDATE each setting key in `settings` table with prepared statements
    - _Requirements: 7.10_

  - [ ] 13.8 Rewrite `admin/analytics.php` — platform-wide aggregate stats
    - Call `requireAdmin()`; query: total streams across all artists, total revenue, total royalties paid, top 10 releases by stream count
    - Set `$loadCharts = true`; render aggregate charts using Chart.js
    - _Requirements: 9.7_

  - [ ]* 13.9 Write property test for admin releases sort order (Property 11)
    - **Property 11: Admin releases list is always sorted by status priority**
    - **Validates: Requirements 7.4**
    - Class: `ReleaseWizardPropertyTest`, generate mixed-status release sets, assert rendered order matches `submitted → in_progress → ready → rejected → draft`

  - [ ]* 13.10 Write property test for admin status update records timestamp and log (Property 10)
    - **Property 10: Admin status update always records timestamp and activity log entry**
    - **Validates: Requirements 6.7**
    - Class: `ReleaseWizardPropertyTest`, generate arbitrary release/status combinations, assert timestamp column set and exactly one `activity_log` row inserted


- [ ] 14. Stores, Help, and Contact pages
  - [ ] 14.1 Rewrite `pages/stores.php` — DSP directory page
    - Set SEO variables and `$jsonLd` (ItemList schema listing all platform names)
    - Render total platform count prominently ("150+ Platforms")
    - Render DSPs grouped by category: Global Streaming, Indian Platforms, Asian Markets, Social Platforms, Download Stores
    - Each DSP card: logo/icon, platform name, brief description; use `loading="lazy"` on logos
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [ ] 14.2 Rewrite `pages/help.php` — FAQ page with accordion and search
    - Set SEO variables and `$jsonLd` (FAQPage schema with all Q&A pairs)
    - Render at least 15 FAQ items in sections: Getting Started, Releases, Royalties & Payouts, Subscriptions, Technical Issues
    - Implement accordion expand/collapse using CSS `max-height` transition and a small inline JS toggle (no library)
    - Render real-time search input; add inline JS that filters `.faq-item` elements by matching `data-question` and `data-answer` attributes (case-insensitive) on `input` event
    - Render "Contact Support" CTA at bottom linking to `?q=contact`
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

  - [ ]* 14.3 Write property test for FAQ search filter (Property 24)
    - **Property 24: FAQ search filter shows only matching items**
    - **Validates: Requirements 13.3**
    - Class: `UiFilterPropertyTest`, generate arbitrary search query strings, assert only FAQ items containing the query (case-insensitive) are visible

  - [ ] 14.4 Rewrite `pages/contact.php` — contact form with email sending
    - Set SEO variables; render contact form: Name, Email, Subject (dropdown: General Inquiry, Technical Support, Billing, Partnership), Message; include `csrfField()`
    - Display support email, office address, social media links
    - On POST: validate CSRF, validate email format with `filter_var($email, FILTER_VALIDATE_EMAIL)`, call `email_helper.php` to send to support email from settings
    - On success: display "Your message has been sent. We'll get back to you within 24 hours."
    - On invalid email: display inline validation error
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_

  - [ ]* 14.5 Write property test for contact form email validation (Property 25)
    - **Property 25: Contact form rejects invalid email addresses**
    - **Validates: Requirements 14.4**
    - Class: `UiFilterPropertyTest`, generate strings that do not match RFC 5322 email format, assert form rejected with inline error and no email sent


- [ ] 15. Security hardening
  - [ ] 15.1 Audit all POST form handlers for CSRF token validation
    - Search all `pages/*.php` and `admin/*.php` files for POST handlers
    - Add `require_once '../includes/csrf.php'` and `validateCsrfToken($_POST['csrf_token'])` call at the top of every POST handler that does not already have it
    - Return HTTP 403 and stop processing on invalid token
    - _Requirements: 15.9_

  - [ ] 15.2 Audit all HTML output for `htmlspecialchars()` coverage
    - Search all `pages/*.php` and `admin/*.php` for `echo` and `<?=` statements that output user-supplied data
    - Wrap any unescaped user data with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`
    - _Requirements: 15.4_

  - [ ] 15.3 Audit all database queries for prepared statement usage
    - Search all PHP files for raw `$conn->query()` calls that interpolate user input
    - Replace each with `$conn->prepare()` + `bind_param()` + `execute()`
    - _Requirements: 15.5_

  - [ ]* 15.4 Write property test for XSS prevention (Property 21)
    - **Property 21: XSS prevention — all user-supplied output is HTML-escaped**
    - **Validates: Requirements 15.4**
    - Class: `SecurityPropertyTest`, generate strings containing `<`, `>`, `"`, `'`, `&`, assert rendered HTML contains only HTML-encoded equivalents

  - [ ]* 15.5 Write property test for CSRF protection (Property 22)
    - **Property 22: CSRF protection rejects requests without valid token**
    - **Validates: Requirements 15.9**
    - Class: `SecurityPropertyTest`, generate arbitrary POST payloads with missing or invalid `csrf_token`, assert HTTP 403 returned and no DB records modified

- [ ] 16. Checkpoint — ensure security and admin tests pass
  - Ensure all tests pass, ask the user if questions arise.


- [ ] 17. Property-based test infrastructure setup
  - [ ] 17.1 Create `phpunit.xml` configuration file
    - Configure PHPUnit 10.x test suite pointing to `tests/` directory
    - Set environment variable `ERIS_ITERATIONS=100`
    - Configure bootstrap file `tests/bootstrap.php`
    - _Requirements: (testing infrastructure)_

  - [ ] 17.2 Create `tests/bootstrap.php` — test bootstrap
    - Require `config.php` with test database credentials (read from environment variables)
    - Require `includes/auth.php`, `includes/csrf.php`, `includes/payment_config.php`
    - Define test database setup/teardown helpers: `createTestUser()`, `createTestRelease()`, `truncateTestTables()`
    - _Requirements: (testing infrastructure)_

  - [ ] 17.3 Create `tests/PaymentFlowPropertyTest.php`
    - Implement Properties 5 and 6 using Eris generators
    - Mock gateway calls; use test database for subscription and payment record assertions
    - _Requirements: 4.4, 4.5_

  - [ ] 17.4 Create `tests/ReleaseWizardPropertyTest.php`
    - Implement Properties 7, 8, 9, 10, 11 using Eris generators
    - Use test database; assert step progression, draft status, badge rendering, timestamp recording, sort order
    - _Requirements: 5.3–5.9, 6.1, 6.7, 7.4_

  - [ ] 17.5 Create `tests/DashboardPropertyTest.php`
    - Implement Properties 12 and 13 using Eris generators
    - Generate arbitrary royalty and payout amounts; assert balance formula and button visibility
    - _Requirements: 8.7, 10.2_

  - [ ] 17.6 Create `tests/PayoutPropertyTest.php`
    - Implement Properties 14, 15, 16, 17 using Eris generators
    - Assert pending record creation, balance deduction, over-balance rejection, below-threshold rejection
    - _Requirements: 10.4, 10.5, 10.7, 10.8_

  - [ ] 17.7 Create `tests/AuthSecurityPropertyTest.php`
    - Implement Properties 18, 19, 20 using Eris generators
    - Assert bcrypt storage, complete session keys, uniform error messages
    - _Requirements: 11.2, 11.7, 11.8_

  - [ ] 17.8 Create `tests/SecurityPropertyTest.php`
    - Implement Properties 21, 22, 23 using Eris generators
    - Assert HTML escaping, CSRF rejection, file upload rejection
    - _Requirements: 15.4, 15.6, 15.7, 15.8, 15.9_

  - [ ] 17.9 Create `tests/SeoMetaTagsPropertyTest.php`
    - Implement Properties 1, 2, 3, 4 using Eris `elements()` generator over public route list
    - Render each page via PHP output buffering; parse HTML with DOMDocument; assert tag presence and constraints
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.10_

  - [ ] 17.10 Create `tests/UiFilterPropertyTest.php`
    - Implement Properties 24 and 25 using Eris generators
    - Property 24: generate search strings, assert FAQ filter correctness
    - Property 25: generate invalid email strings, assert contact form rejection
    - _Requirements: 13.3, 14.4_

- [ ] 18. Final checkpoint — full test suite green
  - Ensure all PHPUnit and Eris property tests pass, ask the user if questions arise.


## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP build
- Each task references specific requirements for full traceability
- Three checkpoints (tasks 6, 11, 16, 18) ensure incremental validation throughout the build
- All 25 correctness properties from the design document are covered by property test sub-tasks
- Property tests use PHPUnit 10.x + Eris 0.11.x; run with `./vendor/bin/phpunit --testdox`
- No framework — all PHP is procedural MySQLi with prepared statements
- `includes/header_premium.php` and `includes/footer_premium.php` are the only layout files; all other header/footer variants are deprecated after Task 1
