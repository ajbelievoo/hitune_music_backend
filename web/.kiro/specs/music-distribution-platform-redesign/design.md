# Design Document: HiTune Music Distribution Platform Redesign

## Overview

This document describes the technical design for the complete redesign and enhancement of the HiTune Music Distribution Platform (web.hitune.in). The platform is a pure PHP application with a MySQL database, using PDO-style MySQLi for all database access. No framework is used.

The redesign consolidates a fragmented multi-header/footer codebase into a single unified design system, introduces a polished dark glassmorphism UI, strengthens the subscription and payment flows, hardens security, and adds comprehensive SEO and analytics capabilities — all while preserving the existing admin-controlled release lifecycle that appears automated to artists.

### Key Design Decisions

1. **Single canonical header/footer**: `includes/header_premium.php` and `includes/footer_premium.php` become the only layout files. All other header/footer variants are deprecated.
2. **CSS-variable-driven design system**: `assets/premium-theme.css` is the single source of truth for all design tokens. Pages include it via the canonical header.
3. **No framework**: All PHP is procedural, using MySQLi with prepared statements. No Composer, no ORM.
4. **Front-controller routing**: `index.php` remains the single entry point for all user-facing pages via `?q=` query parameter.
5. **Admin panel isolation**: `/admin/` has its own session namespace and authentication guard, completely separate from the user session.
6. **Payment gateway abstraction**: `includes/payment_config.php` provides a thin abstraction over Razorpay and Cashfree, reading all credentials from the `settings` database table.
7. **Security-first**: CSRF tokens on every POST form, `htmlspecialchars()` on all output, prepared statements everywhere, MIME-type validation on all uploads.

---

## Architecture

The platform follows a simple front-controller pattern with file-based page includes.

```
Browser
  │
  ▼
index.php  ──── ?q=<route> ────► pages/<page>.php
  │                                    │
  │                                    ├── includes/header_premium.php
  │                                    ├── [page logic + HTML]
  │                                    └── includes/footer_premium.php
  │
  ├── config.php  (DB connection via MySQLi)
  ├── includes/auth.php  (session-based auth helpers)
  ├── includes/payment_config.php  (gateway abstraction)
  └── includes/email_helper.php  (PHPMailer wrapper)

/admin/
  │
  ├── admin/config.php  (requireAdmin() guard)
  ├── admin/index.php  (dashboard)
  ├── admin/submissions.php  (release management)
  ├── admin/users.php
  ├── admin/royalties.php
  ├── admin/payouts.php
  ├── admin/payments.php
  ├── admin/analytics.php
  └── admin/settings.php

/assets/
  ├── premium-theme.css  (global design system)
  ├── logo.png
  └── [other static assets]

/uploads/
  ├── artwork/   (cover art, 3000×3000 min)
  ├── audio/     (WAV/MP3/FLAC, 200MB max)
  └── temp/      (chunked upload staging)
```

### Request Lifecycle

```
HTTP Request
  │
  ├─► .htaccess  ──► HTTPS redirect + URL rewrite
  │
  ▼
index.php
  ├── session_start()
  ├── require config.php  (MySQLi $conn)
  ├── require includes/auth.php
  ├── switch($path) → include pages/<page>.php
  │
  └── pages/<page>.php
        ├── requireLogin() / requireAdmin()  [if protected]
        ├── Business logic (prepared statements)
        ├── include header_premium.php  (outputs <head>, <nav>)
        ├── Page HTML
        └── include footer_premium.php  (outputs <footer>, scripts)
```

### Admin Request Lifecycle

```
/admin/<page>.php
  ├── require ../config.php
  ├── require admin/config.php  (requireAdmin())
  ├── Business logic
  └── Self-contained HTML (no shared header/footer)
```

---

## Components and Interfaces

### 1. Unified Layout System

**`includes/header_premium.php`**

The single canonical header. Accepts PHP variables set by the including page:

| Variable | Type | Purpose |
|---|---|---|
| `$pageTitle` | string | `<title>` tag content (max 60 chars) |
| `$metaDescription` | string | `<meta name="description">` (max 160 chars) |
| `$metaKeywords` | string | `<meta name="keywords">` |
| `$ogImage` | string | Open Graph image URL |
| `$canonicalUrl` | string | `<link rel="canonical">` href |
| `$jsonLd` | array | JSON-LD structured data object (encoded in head) |
| `$path` | string | Current route for active nav link highlighting |

Outputs:
- Full `<!DOCTYPE html>` through `<main class="main-content">` opening tag
- Google Fonts (Poppins 400/500/600/700/800)
- MDI 6.5.95 CSS
- `assets/premium-theme.css`
- All SEO meta tags
- Fixed glassmorphism navbar with hamburger menu for mobile
- CSRF token generation: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`

**`includes/footer_premium.php`**

Outputs:
- `</main>` closing tag
- Footer with links (About, Pricing, Services, Stores, Help, Contact, Privacy, Terms)
- Social media links
- Chart.js CDN (loaded only when `$loadCharts = true` is set by the page)
- Intersection Observer scroll animation script
- Particle animation script
- `</body></html>`

### 2. CSRF Protection Helper

**`includes/csrf.php`** (new file)

```php
function generateCsrfToken(): string
function validateCsrfToken(string $token): bool
function csrfField(): string  // outputs <input type="hidden" name="csrf_token" value="...">
```

All POST form handlers call `validateCsrfToken($_POST['csrf_token'])` before processing. Invalid tokens return HTTP 403.

### 3. Authentication System

**`includes/auth.php`** (enhanced)

```php
function isLoggedIn(): bool
function requireLogin(): void          // redirects to /index.php?q=login
function loginUser(int $id, string $email, string $name): void
function logoutUser(): void
function getCurrentUser(): ?array      // fetches from DB, includes subscription status
function hasActiveSubscription(): bool
function getUserPlan(): ?string        // 'rising_artist' | 'breakout_artist' | 'professional' | null
function getUserPlanFeatures(): array  // returns plan feature flags
```

**`admin/config.php`** (enhanced)

```php
function requireAdmin(): void  // checks $_SESSION['admin_id'], redirects to admin/login.php
function isAdmin(): bool
```

### 4. Payment Gateway Interface

**`includes/payment_config.php`** (existing, enhanced)

New functions added:

```php
function createRazorpayOrder(int $amountPaise, string $orderId): array
function verifyRazorpayPayment(string $orderId, string $paymentId, string $signature): bool
function createCashfreeOrder(int $amountPaise, string $orderId, array $customerData): array
function verifyCashfreeWebhook(array $payload, string $signature): bool
function activateSubscription(int $userId, string $planId, int $paymentId): int
function recordPayment(int $userId, string $planId, string $gateway, string $orderId, string $status): int
```

### 5. Release Wizard Steps

Each step is a separate PHP file included by `index.php`:

| Step | File | Saves |
|---|---|---|
| 1 | `pages/release_create.php` | Release metadata, `step_completed=1`, `progress_percent=25` |
| 2 | `pages/release_step2.php` | Platform selections in `release_platforms`, `step_completed=2`, `progress_percent=50` |
| 3 | `pages/release_step3.php` | Track records + chunked audio upload, `step_completed=3`, `progress_percent=75` |
| 4 | `pages/release_step4.php` | Cover art upload + final submission, `step_completed=4`, `progress_percent=100`, `status='submitted'` |

### 6. File Upload Handler

**`pages/upload_chunked.php`** (existing, enhanced)

Handles chunked audio uploads (up to 200MB). Enhanced with:
- MIME type whitelist: `audio/mpeg`, `audio/wav`, `audio/x-wav`, `audio/flac`, `audio/aiff`
- File size enforcement: reject if total assembled size > 200MB
- Temp file cleanup on failure

**Cover art upload** (inline in `release_step4.php`):
- MIME type whitelist: `image/jpeg`, `image/png`
- Minimum dimensions: 3000×3000 pixels (checked with `getimagesize()`)
- Max file size: 50MB

### 7. SEO Infrastructure

**`sitemap.xml`** — generated by `pages/sitemap.php`, served via `.htaccess` rewrite:

```
RewriteRule ^sitemap\.xml$ pages/sitemap.php [L]
RewriteRule ^robots\.txt$ pages/robots.php [L]
```

**`pages/sitemap.php`** outputs XML with all public routes, `<lastmod>` set to file modification time, `<changefreq>` and `<priority>` hardcoded per page type.

**`pages/robots.php`** outputs:
```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /pages/
Disallow: /uploads/
Sitemap: https://web.hitune.in/sitemap.xml
```

### 8. Analytics Charts

Pages that display charts set `$loadCharts = true` before including the footer. Chart.js is loaded conditionally. Chart data is passed from PHP to JavaScript via `json_encode()` in an inline `<script>` block.

```php
// In analytics.php
$chartData = json_encode([
    'labels' => $monthLabels,
    'streams' => $monthlyStreams,
    'earnings' => $monthlyEarnings,
]);
```

```html
<!-- In page HTML -->
<canvas id="streamsChart"></canvas>
<script>
const chartData = <?= $chartData ?>;
// Chart.js initialization
</script>
```

---

## Data Models

### Existing Tables (unchanged schema, enhanced usage)

**`users`** — existing schema. Enhanced: `email_verified` column added if not present.

**`releases`** — existing schema. Enhanced: `label_name` column added for Breakout/Professional plans; `scheduled_release_date` for Professional plan.

**`release_tracks`** — existing schema, unchanged.

**`release_platforms`** — existing schema, unchanged.

**`subscriptions`** — existing schema, unchanged.

**`payments`** — existing schema, unchanged.

**`track_royalties`** — existing schema, unchanged.

**`activity_log`** — existing schema, unchanged.

**`settings`** — existing schema. New keys added:

| Key | Default | Description |
|---|---|---|
| `min_payout_threshold` | `500` | Minimum payout amount in INR |
| `support_email` | `support@hitune.in` | Support contact email |
| `platform_name` | `HiTune Music` | Platform display name |
| `og_image_url` | `/assets/og-image.jpg` | Default Open Graph image |

### New Tables

**`payouts`** — withdrawal requests by artists:

```sql
CREATE TABLE IF NOT EXISTS `payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('bank_transfer','upi','paypal') NOT NULL,
  `account_details` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `payouts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**`user_artists`** — artist profile requests (already exists in codebase):

```sql
-- Already exists; ensure columns: id, user_id, artist_name, platform, status, created_at
```

### Schema Additions to Existing Tables

```sql
-- Add to releases table
ALTER TABLE releases
  ADD COLUMN IF NOT EXISTS `label_name` varchar(255) DEFAULT NULL AFTER `upc_code`,
  ADD COLUMN IF NOT EXISTS `scheduled_release_date` date DEFAULT NULL AFTER `label_name`;

-- Add to users table
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS `email_verified` tinyint(1) DEFAULT 0 AFTER `profile_image`,
  ADD COLUMN IF NOT EXISTS `verification_token` varchar(64) DEFAULT NULL AFTER `email_verified`;
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

This feature involves a mix of UI rendering, business logic, security validation, and data persistence. Property-based testing is applicable to the pure logic layers: input validation, data transformation, access control rules, and balance calculations. UI rendering requirements are covered by example-based tests.

The PHP property-based testing library used is **[eris](https://github.com/giorgiosironi/eris)** (a PHPUnit extension for property-based testing).

---

### Property 1: SEO meta tags are present on every public page

*For any* page route in the platform's public route list, the rendered HTML output SHALL contain a non-empty `<title>` tag (≤60 characters), a `<meta name="description">` tag (≤160 characters), and a `<link rel="canonical">` tag.

**Validates: Requirements 3.1, 3.4**

---

### Property 2: Open Graph and Twitter Card tags are present on every public page

*For any* public page route, the rendered HTML SHALL contain all five Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`) and all four Twitter Card tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`).

**Validates: Requirements 3.2, 3.3**

---

### Property 3: Heading hierarchy is never skipped on any page

*For any* page in the platform, the sequence of heading levels (H1 through H6) in the rendered HTML SHALL never skip a level — no H3 appears without a preceding H2, and no H2 appears without a preceding H1.

**Validates: Requirements 3.5**

---

### Property 4: All images have non-empty alt attributes

*For any* page in the platform, every `<img>` element in the rendered HTML SHALL have a non-empty `alt` attribute.

**Validates: Requirements 3.10, 15.2**

---

### Property 5: Successful payment always creates active subscription and completed payment records

*For any* valid payment completion callback (any plan, any user, any gateway), the system SHALL create a `subscriptions` record with `status = 'active'` and `end_date = start_date + 1 YEAR`, and a `payments` record with `payment_status = 'completed'`.

**Validates: Requirements 4.4**

---

### Property 6: Failed payment always creates a failed payment record

*For any* payment failure callback (any plan, any user, any gateway), the system SHALL create a `payments` record with `payment_status = 'failed'` and SHALL NOT create or activate a subscription record.

**Validates: Requirements 4.5**

---

### Property 7: Release wizard step completion is monotonically non-decreasing

*For any* release and any step submission, the `step_completed` and `progress_percent` values in the database SHALL never decrease — `GREATEST(step_completed, new_step)` is always used on update.

**Validates: Requirements 5.3, 5.4, 5.5, 5.6**

---

### Property 8: Draft saves at any step preserve status=draft

*For any* release at any wizard step (1–4), clicking "Save as Draft" SHALL result in the release record having `status = 'draft'` in the database, regardless of the current step or previously saved data.

**Validates: Requirements 5.9**

---

### Property 9: Release status badge always matches the release's database status

*For any* release with any valid status value (`draft`, `submitted`, `in_progress`, `ready`, `rejected`), the rendered status badge on the releases list and release detail pages SHALL display the correct label and CSS class corresponding to that status.

**Validates: Requirements 6.1**

---

### Property 10: Admin status update always records timestamp and activity log entry

*For any* admin status update on any release to any valid target status, the system SHALL set the corresponding timestamp column (`submitted_at`, `in_progress_at`, `ready_at`, or `rejected_at`) to `NOW()` and insert exactly one row into the `activity_log` table.

**Validates: Requirements 6.7**

---

### Property 11: Admin releases list is always sorted by status priority

*For any* set of releases with mixed statuses in the database, the admin releases page SHALL display them in the order: `submitted` → `in_progress` → `ready` → `rejected` → `draft`, with ties broken by `created_at DESC`.

**Validates: Requirements 7.4**

---

### Property 12: Withdraw button appears if and only if balance ≥ minimum threshold

*For any* artist with a computed available balance, the "Withdraw Earnings" button SHALL be visible on the dashboard if and only if the balance is greater than or equal to the configured minimum payout threshold (default ₹500).

**Validates: Requirements 8.7**

---

### Property 13: Artist balance equals total royalties minus approved payouts

*For any* artist, the displayed available balance SHALL equal the sum of all `royalty_amount` values in `track_royalties` for that artist's tracks, minus the sum of all `amount` values in `payouts` where `status = 'approved'` for that artist.

**Validates: Requirements 10.2**

---

### Property 14: Payout request always creates a pending record

*For any* valid payout request (amount ≥ minimum threshold, amount ≤ available balance), the system SHALL insert exactly one record into the `payouts` table with `status = 'pending'`.

**Validates: Requirements 10.4**

---

### Property 15: Payout approval always deducts from artist balance

*For any* approved payout of amount A for artist U, the artist's computed available balance after approval SHALL equal the balance before approval minus A.

**Validates: Requirements 10.5**

---

### Property 16: Payout request exceeding balance is always rejected

*For any* payout request where the requested amount exceeds the artist's available balance, the system SHALL reject the request and display the error message containing the artist's current available balance.

**Validates: Requirements 10.7**

---

### Property 17: Payout request below minimum threshold is always rejected

*For any* payout request where the requested amount is less than the configured minimum threshold, the system SHALL reject the request and display an error message specifying the minimum amount.

**Validates: Requirements 10.8**

---

### Property 18: Passwords are always stored as bcrypt hashes

*For any* password string submitted during user registration, the value stored in the `users.password` column SHALL be a valid bcrypt hash (starting with `$2y$`) that returns `true` when verified with `password_verify($plaintext, $hash)`.

**Validates: Requirements 11.2**

---

### Property 19: Valid login always creates a complete session

*For any* user with valid credentials (correct email and password), a successful login SHALL create a PHP session containing `user_id`, `email`, `name`, and `login_time` keys with non-null values.

**Validates: Requirements 11.7**

---

### Property 20: Invalid login never reveals which field is incorrect

*For any* login attempt with invalid credentials (wrong email, wrong password, or both), the error message displayed SHALL be identical regardless of which field is incorrect — it SHALL NOT contain the words "email", "password", "user", or "account" in a way that identifies which field failed.

**Validates: Requirements 11.8**

---

### Property 21: XSS prevention — all user-supplied output is HTML-escaped

*For any* string containing HTML special characters (`<`, `>`, `"`, `'`, `&`) stored in the database as user-supplied data, when that string is rendered in any HTML page, the output SHALL contain the HTML-encoded equivalents (`&lt;`, `&gt;`, `&quot;`, `&#039;`, `&amp;`) and SHALL NOT contain the raw characters.

**Validates: Requirements 15.4**

---

### Property 22: CSRF protection rejects requests without valid token

*For any* POST request to any form handler that does not include a valid `csrf_token` matching the session token, the system SHALL return HTTP 403 and SHALL NOT process the form data or modify any database records.

**Validates: Requirements 15.9**

---

### Property 23: File upload validation rejects invalid MIME types and oversized files

*For any* file upload attempt, if the file's MIME type is not in the allowed whitelist OR the file size exceeds the configured maximum, the system SHALL reject the upload, return an error message, and SHALL NOT save the file to the `uploads/` directory.

**Validates: Requirements 15.6, 15.7, 15.8**

---

### Property 24: FAQ search filter shows only matching items

*For any* search query string entered in the Help page search input, only FAQ items whose question or answer text contains the query string (case-insensitive) SHALL be visible; all other FAQ items SHALL be hidden.

**Validates: Requirements 13.3**

---

### Property 25: Contact form rejects invalid email addresses

*For any* contact form submission where the email field contains a string that does not match a valid email format (RFC 5322), the system SHALL display an inline validation error and SHALL NOT send the email or display the success message.

**Validates: Requirements 14.4**

---

## Error Handling

### HTTP Error Responses

| Condition | HTTP Status | Handler |
|---|---|---|
| Route not found | 404 | `pages/404.php` |
| Unauthenticated access to protected page | 302 | Redirect to `/index.php?q=login` |
| Unauthenticated access to admin | 302 | Redirect to `/admin/login.php` |
| Invalid CSRF token | 403 | Inline error message |
| File upload MIME type violation | 400 | JSON error response (AJAX) |
| File upload size exceeded | 400 | JSON error response (AJAX) |
| Payment gateway error | 200 | User-facing error page with retry option |

### Database Error Handling

All database operations use MySQLi prepared statements. Errors are caught and logged to PHP error log. User-facing pages display a generic "Something went wrong" message — never raw SQL errors.

```php
// Pattern used throughout
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("DB prepare error: " . $conn->error);
    // show user-friendly error
}
$stmt->bind_param(...);
if (!$stmt->execute()) {
    error_log("DB execute error: " . $stmt->error);
    // show user-friendly error
}
```

### Payment Error Handling

- **Gateway timeout**: Display "Payment gateway is temporarily unavailable. Please try again." with a retry button.
- **Signature verification failure**: Log the mismatch, return 400, do not activate subscription.
- **Duplicate webhook**: Check `payments.payment_order_id` for uniqueness before processing.

### File Upload Error Handling

All upload errors return JSON for AJAX handlers:

```json
{ "success": false, "error": "File size exceeds the 200MB limit." }
{ "success": false, "error": "Cover art must be at least 3000×3000 pixels." }
{ "success": false, "error": "Invalid file type. Allowed: MP3, WAV, FLAC, AIFF." }
```

### Form Validation Error Handling

All server-side validation errors are stored in a `$errors` array and displayed inline next to the relevant field. The form is re-rendered with previously entered values preserved.

---

## Testing Strategy

### Dual Testing Approach

The testing strategy combines example-based unit tests for specific behaviors and property-based tests for universal correctness guarantees.

### Unit Tests (PHPUnit)

Unit tests cover:
- Specific UI rendering examples (correct status badge for each status value)
- Integration points (payment callback processing with mock gateway responses)
- Edge cases (empty cover art, zero-byte audio file, expired subscription)
- Error conditions (duplicate email on signup, invalid CSRF token)

Key test classes:
- `PaymentCallbackTest` — verifies subscription and payment record creation
- `ReleaseWizardTest` — verifies step progression and draft saving
- `AuthTest` — verifies login, logout, session creation
- `FileUploadTest` — verifies MIME type and size validation
- `BalanceCalculationTest` — verifies royalty balance computation
- `PayoutValidationTest` — verifies payout threshold and balance checks

### Property-Based Tests (Eris + PHPUnit)

Each property from the Correctness Properties section is implemented as a single property-based test using the [eris](https://github.com/giorgiosironi/eris) library. Each test runs a minimum of 100 iterations with randomly generated inputs.

**Configuration**: `phpunit.xml` sets `ERIS_ITERATIONS=100` as an environment variable.

**Tag format**: Each property test is tagged with a comment:
```php
// Feature: music-distribution-platform-redesign, Property N: <property_text>
```

Key property test classes:
- `SeoMetaTagsPropertyTest` — Properties 1, 2, 3, 4
- `PaymentFlowPropertyTest` — Properties 5, 6
- `ReleaseWizardPropertyTest` — Properties 7, 8, 9, 10, 11
- `DashboardPropertyTest` — Properties 12, 13
- `PayoutPropertyTest` — Properties 14, 15, 16, 17
- `AuthSecurityPropertyTest` — Properties 18, 19, 20
- `SecurityPropertyTest` — Properties 21, 22, 23
- `UiFilterPropertyTest` — Properties 24, 25

### Integration Tests

Integration tests verify external service wiring with 1–3 representative examples:
- Payment gateway credential loading from `settings` table
- Email sending via `email_helper.php` (using a test SMTP server)
- Google OAuth callback flow

### Test Environment

- PHP 8.1+ with MySQLi extension
- MySQL 8.0 test database (separate from production)
- PHPUnit 10.x
- Eris 0.11.x
- No external HTTP calls in unit/property tests (all gateway calls mocked)
