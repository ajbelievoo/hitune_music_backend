# Requirements Document

## Introduction

HiTune Music Distribution (web.hitune.in) is a PHP-based music distribution platform that enables independent artists to distribute their music to 150+ streaming platforms including Spotify, Apple Music, YouTube Music, and more. The platform currently has a functional but inconsistent UI with multiple header/footer variants, a working multi-step release submission flow, a manual admin panel for managing release statuses and royalties, a subscription system, and basic analytics.

This spec covers a complete professional redesign and feature enhancement to bring the platform to world-class standard — comparable to DistroKid, TuneCore, and Believe Music — while preserving and improving all existing functionality. The admin panel must remain powerful and easy to use, with all status updates appearing automated to users even though they are performed manually by admins.

---

## Glossary

- **Platform**: The HiTune Music Distribution web application (web.hitune.in)
- **Artist**: A registered user who uploads and distributes music
- **Release**: A music submission (single, EP, or album) created by an Artist
- **Track**: An individual audio file within a Release
- **Admin**: A privileged operator who manages the Platform via the admin panel
- **Subscription**: A paid plan that grants an Artist access to distribution services
- **Royalty**: Earnings generated from streams and downloads of a Track
- **Payout**: A withdrawal of accumulated Royalty earnings by an Artist
- **Distribution_Status**: The lifecycle state of a Release: `draft → submitted → in_progress → ready → rejected`
- **DSP**: Digital Service Provider — a streaming platform such as Spotify, Apple Music, or YouTube Music
- **ISRC**: International Standard Recording Code — a unique identifier for a Track
- **UPC**: Universal Product Code — a unique identifier for a Release
- **Cover_Art**: The square image artwork associated with a Release
- **SEO**: Search Engine Optimization — techniques to improve organic search ranking
- **Glassmorphism**: A UI design style using frosted-glass backgrounds, blur effects, and translucency
- **EARS**: Easy Approach to Requirements Syntax — a structured requirements language
- **Label_Name**: A custom record label name an Artist can assign to their releases

---

## Requirements

---

### Requirement 1: Unified Design System

**User Story:** As an Artist, I want a consistent, world-class visual experience across every page, so that the platform feels professional and trustworthy.

#### Acceptance Criteria

1. THE Platform SHALL apply a single unified design system — using the existing `assets/premium-theme.css` as the foundation — across all user-facing pages, replacing the current inconsistent mix of `header.php`, `header_premium.php`, `header_pro.php`, and `header_v2.php` with one canonical `header_premium.php` and one canonical `footer_premium.php`.
2. THE Platform SHALL use a dark-mode-first color palette with CSS variables: primary gradient `#ff6b6b → #ff8e53`, accent green `#00c853 → #00e676`, background `#0a0a0f`, and glassmorphism card backgrounds `rgba(255,255,255,0.03)` with `backdrop-filter: blur(20px)`.
3. THE Platform SHALL load the Google Font `Poppins` (weights 400, 500, 600, 700, 800) as the primary typeface on all pages.
4. THE Platform SHALL render all interactive cards with a hover state that includes `transform: translateY(-8px)` and a colored `box-shadow` glow matching the card's accent color.
5. THE Platform SHALL display a fixed top navigation bar with glassmorphism background (`backdrop-filter: blur(20px)`) that remains visible during scroll on all pages.
6. WHEN the viewport width is 768px or less, THE Platform SHALL collapse the navigation links into a hamburger menu that opens a full-screen mobile drawer.
7. THE Platform SHALL include animated background radial gradients on all major pages to create visual depth without impacting page performance.
8. THE Platform SHALL use Material Design Icons (MDI) version 6.5.95 consistently for all iconography across user-facing and admin pages.

---

### Requirement 2: Homepage Redesign

**User Story:** As a visitor, I want to land on a visually stunning, conversion-optimised homepage, so that I immediately understand the platform's value and am compelled to sign up.

#### Acceptance Criteria

1. THE Platform SHALL display a full-viewport hero section with an animated headline, a subtitle, two CTA buttons ("Get Started" → `/index.php?q=pricing` and "How It Works" → `/index.php?q=sell`), and four animated stat counters (150+ Platforms, 100K+ Artists, 1B+ Streams, 100% Royalties).
2. THE Platform SHALL display a scrolling DSP logo marquee section showing at least 12 platform logos (Spotify, Apple Music, YouTube Music, Amazon Music, Tidal, Deezer, JioSaavn, Gaana, TikTok, Instagram, Wynk, Boomplay) with a "View all 150+ stores" link.
3. THE Platform SHALL display a features grid with at least 6 feature cards (Unlimited Releases, 100% Royalties, Detailed Analytics, Scheduled Releases, Music Publishing, Collaborator Splits), each with an animated icon, heading, and description.
4. THE Platform SHALL display a social proof section with at least 3 artist testimonials including name, genre, and quote.
5. THE Platform SHALL display a pricing preview section showing the three subscription tiers with a "View Full Pricing" CTA.
6. THE Platform SHALL display a final CTA section with a gradient background card and a prominent "Start Distributing Now" button.
7. WHEN a user scrolls past 50% of the page, THE Platform SHALL trigger scroll-based fade-in animations on feature cards and stat counters using the Intersection Observer API.
8. THE Platform SHALL include structured data (JSON-LD `Organization` and `WebSite` schema) in the homepage `<head>` for SEO.

---

### Requirement 3: SEO Optimization

**User Story:** As the Platform operator, I want every page to be fully SEO-optimised, so that the Platform ranks highly in search engines for music distribution keywords.

#### Acceptance Criteria

1. THE Platform SHALL include a unique `<title>` tag (max 60 characters), `<meta name="description">` (max 160 characters), and `<meta name="keywords">` on every page.
2. THE Platform SHALL include Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`, `og:type`) on every public-facing page.
3. THE Platform SHALL include Twitter Card meta tags (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`) on every public-facing page.
4. THE Platform SHALL include a `<link rel="canonical">` tag on every page pointing to the page's canonical URL.
5. THE Platform SHALL render all page headings in a strict H1 → H2 → H3 hierarchy with no skipped levels.
6. THE Platform SHALL include JSON-LD structured data of type `Organization` on the homepage and `FAQPage` on the pricing and help pages.
7. THE Platform SHALL generate a `sitemap.xml` file at `/sitemap.xml` listing all public pages with `<lastmod>`, `<changefreq>`, and `<priority>` values.
8. THE Platform SHALL serve a `robots.txt` file at `/robots.txt` that allows all public pages and disallows `/admin/` and `/pages/` direct access.
9. WHEN a page is not found, THE Platform SHALL return HTTP status 404 and render the custom 404 page at `pages/404.php`.
10. THE Platform SHALL ensure all images include descriptive `alt` attributes.

---

### Requirement 4: Subscription System

**User Story:** As an Artist, I want to purchase a subscription plan that unlocks distribution services, so that I can release my music to streaming platforms.

#### Acceptance Criteria

1. THE Platform SHALL offer three subscription tiers: Rising Artist (₹1,599/year, 50+ platforms, 1 artist profile), Breakout Artist (₹2,799/year, 100+ platforms, 3 artist profiles, YouTube Content ID, custom label name), and Professional (₹4,499/year, 150+ platforms, unlimited artist profiles, YouTube Content ID, custom label name, scheduled releases).
2. WHEN an Artist selects a plan on the pricing page, THE Platform SHALL redirect the Artist to a checkout page displaying the plan name, price, and a payment form.
3. THE Platform SHALL integrate with Razorpay and Cashfree payment gateways, reading credentials from the `settings` database table.
4. WHEN a payment is completed successfully, THE Platform SHALL create a record in the `subscriptions` table with `status = 'active'`, `start_date = NOW()`, and `end_date = NOW() + 1 YEAR`, and a record in the `payments` table with `payment_status = 'completed'`.
5. WHEN a payment fails, THE Platform SHALL create a record in the `payments` table with `payment_status = 'failed'` and display an error message to the Artist.
6. WHILE an Artist's subscription is active, THE Platform SHALL allow the Artist to create and submit Releases up to the plan's artist profile limit.
7. WHEN an Artist's subscription has expired, THE Platform SHALL display a subscription renewal banner on the dashboard and prevent new Release submissions until the subscription is renewed.
8. THE Platform SHALL display the current plan name, days remaining, next billing date, and artist slot usage on the Artist dashboard.
9. WHERE the Breakout Artist or Professional plan is active, THE Platform SHALL display a "Custom Label Name" field in the Release creation form (Step 1).
10. WHERE the Professional plan is active, THE Platform SHALL display a "Scheduled Release" date picker in the Release creation form (Step 1).

---

### Requirement 5: Release Submission Workflow

**User Story:** As an Artist, I want a clear, guided multi-step release submission process, so that I can submit my music correctly without confusion.

#### Acceptance Criteria

1. THE Platform SHALL present Release creation as a 4-step wizard: Step 1 (Release Details), Step 2 (Store Selection), Step 3 (Track Upload), Step 4 (Artwork Upload).
2. THE Platform SHALL display a visual progress bar at the top of each step showing completed, current, and upcoming steps.
3. WHEN an Artist completes Step 1 and clicks "Save & Continue", THE Platform SHALL save the release with `step_completed = 1`, `progress_percent = 25`, and redirect to Step 2.
4. WHEN an Artist completes Step 2 and clicks "Save & Continue", THE Platform SHALL save the selected platforms, update `step_completed = 2`, `progress_percent = 50`, and redirect to Step 3.
5. WHEN an Artist completes Step 3 and clicks "Save & Continue", THE Platform SHALL validate that at least one Track with an uploaded audio file exists, update `step_completed = 3`, `progress_percent = 75`, and redirect to Step 4.
6. WHEN an Artist completes Step 4 and clicks "Submit Release", THE Platform SHALL validate that a Cover Art image of at least 3000×3000 pixels in JPEG or PNG format has been uploaded, update `step_completed = 4`, `progress_percent = 100`, set `status = 'submitted'`, set `submitted_at = NOW()`, and redirect to the Releases list page.
7. IF an Artist attempts to submit a Release without a valid Cover Art image, THEN THE Platform SHALL display an inline error message specifying the minimum resolution requirement.
8. IF an Artist attempts to submit a Release without at least one uploaded Track audio file, THEN THE Platform SHALL display an inline error message.
9. THE Platform SHALL allow an Artist to save a Release as a draft at any step and return to it later.
10. WHEN a Release has `status = 'rejected'`, THE Platform SHALL allow the Artist to edit and resubmit the Release, displaying the admin's rejection notes prominently.
11. THE Platform SHALL auto-select all 80+ DSP platforms by default in Step 2, with the Artist able to deselect individual platforms.

---

### Requirement 6: Release Status Lifecycle (Admin-Controlled, Appears Automated)

**User Story:** As an Artist, I want to see my release progressing through clear status stages with informative messages, so that I feel confident my music is being distributed even though the process is manual.

#### Acceptance Criteria

1. THE Platform SHALL display one of five Release statuses to Artists: Draft, Submitted (Pending Review), In Progress (Being Distributed), Live (Published), Rejected — using consistent color-coded badges across all pages.
2. WHEN a Release has `status = 'submitted'`, THE Platform SHALL display the message "Your release is under review. This typically takes 1–3 business days." to the Artist.
3. WHEN a Release has `status = 'in_progress'`, THE Platform SHALL display the message "Your release is being distributed to stores. This may take 3–7 days." to the Artist.
4. WHEN a Release has `status = 'ready'`, THE Platform SHALL display the message "Your release is live on all selected platforms!" with a green checkmark to the Artist.
5. WHEN a Release has `status = 'rejected'`, THE Platform SHALL display the admin's rejection notes to the Artist and a "Resubmit" button.
6. THE Admin Panel SHALL allow an Admin to change a Release's status to any of: `submitted`, `in_progress`, `ready`, `rejected` via a dropdown and "Update" button on the Submissions management page.
7. WHEN an Admin updates a Release status, THE Platform SHALL record the timestamp in the corresponding column (`in_progress_at`, `ready_at`, `rejected_at`) and insert a row into the `activity_log` table.
8. THE Admin Panel SHALL allow an Admin to add notes to a Release that are visible to the Artist on their Release detail page.
9. THE Platform SHALL NOT expose any indication to the Artist that status changes are performed manually by an Admin.

---

### Requirement 7: Admin Panel

**User Story:** As an Admin, I want a powerful, well-organised admin panel, so that I can efficiently manage all users, releases, royalties, payouts, and platform settings.

#### Acceptance Criteria

1. THE Admin_Panel SHALL be accessible only at `/admin/` and require Admin authentication via a separate login at `/admin/login.php`.
2. THE Admin_Panel SHALL display a persistent left sidebar with navigation links to: Dashboard, Users, Releases, Royalties, Payouts, Payments, Analytics, Cover Art, Artist Previews, Manage Artists, User Artist Requests, Platforms, Settings, and Logout.
3. THE Admin_Panel Dashboard SHALL display real-time counts for: Total Users, Total Releases, Pending Review, In Progress, Published, Total Revenue, and Pending Payouts.
4. THE Admin_Panel Releases page SHALL display all releases sorted by status priority (submitted first, then in_progress, then ready, then rejected, then draft) with columns for ID, Title, Artist, Type, User, Status, Submitted Date, and an Actions column.
5. THE Admin_Panel Releases page SHALL allow an Admin to filter releases by status using tab buttons.
6. THE Admin_Panel SHALL provide a Release Detail page (`admin/release_detail.php`) showing all release metadata, track list with audio playback, cover art preview, and a status update form.
7. THE Admin_Panel Royalties page SHALL allow an Admin to add stream counts, download counts, revenue, and royalty amounts per track per reporting period via a modal form.
8. THE Admin_Panel Payouts page SHALL display all pending payout requests with Artist name, amount, payment method, and buttons to approve or reject each request.
9. THE Admin_Panel Users page SHALL allow an Admin to view, search, and edit user accounts, and manually assign or extend subscriptions.
10. THE Admin_Panel Settings page SHALL allow an Admin to configure Razorpay and Cashfree payment gateway credentials, platform name, support email, and minimum payout threshold.
11. THE Admin_Panel SHALL use the same dark glassmorphism design language as the user-facing platform for visual consistency.

---

### Requirement 8: Artist Dashboard

**User Story:** As an Artist, I want a comprehensive dashboard that shows my key metrics at a glance, so that I can quickly understand my performance and take action.

#### Acceptance Criteria

1. THE Dashboard SHALL display four stat cards: Available Balance (total royalties minus withdrawn), Lifetime Streams, This Month's Earnings, and Active Releases count.
2. THE Dashboard SHALL display the Artist's current subscription plan name, days remaining, next billing date, and artist slot usage (e.g., "2/3 profiles used").
3. WHEN an Artist has no active subscription, THE Dashboard SHALL display a prominent "Upgrade Plan" CTA card in place of the subscription info card.
4. THE Dashboard SHALL display a "Recent Releases" section showing the Artist's last 5 releases with cover art thumbnail, title, status badge, and a "View Details" link.
5. THE Dashboard SHALL display an "Artist Accounts" section with cards for Spotify for Artists, Apple Music for Artists, and YouTube Official Artist Channel, each linking to the respective claim/setup page.
6. THE Dashboard SHALL display a "Quick Actions" section with buttons: "New Release", "View Analytics", "Request Payout", and "Manage Artists".
7. WHEN the Artist's available balance is ₹500 or more, THE Dashboard SHALL display a "Withdraw Earnings" button that links to the Payouts page.
8. THE Dashboard SHALL display a monthly earnings chart (bar chart) for the last 6 months using Chart.js or a lightweight SVG-based chart.

---

### Requirement 9: Analytics

**User Story:** As an Artist, I want detailed analytics about my streams and earnings, so that I can understand where my audience is and how my music is performing.

#### Acceptance Criteria

1. THE Analytics_Page SHALL display total lifetime streams, total lifetime revenue, and total royalties earned for the Artist.
2. THE Analytics_Page SHALL display a line chart of monthly streams for the last 12 months.
3. THE Analytics_Page SHALL display a bar chart of earnings per release for the Artist's top 5 releases.
4. THE Analytics_Page SHALL display a platform breakdown table showing streams and revenue per DSP (Spotify, Apple Music, YouTube Music, etc.) sourced from the `track_royalties` table.
5. THE Analytics_Page SHALL display a release-level breakdown table with columns: Release Title, Artist, Total Streams, Total Revenue, Total Royalties.
6. WHEN no analytics data exists for an Artist, THE Analytics_Page SHALL display an empty state with the message "No analytics data yet. Your stats will appear here once your release goes live."
7. THE Admin_Panel Analytics page SHALL display platform-wide aggregate stats: total streams across all artists, total revenue, total royalties paid, and top 10 releases by stream count.

---

### Requirement 10: Royalties and Payouts

**User Story:** As an Artist, I want to see my royalty earnings clearly and request payouts easily, so that I can get paid for my music.

#### Acceptance Criteria

1. THE Royalties_Page SHALL display a table of all royalty entries for the Artist's tracks, with columns: Track Title, Release, Platform, Reporting Period, Plays, Revenue, Royalty Amount.
2. THE Royalties_Page SHALL display the Artist's total accumulated balance (sum of all `royalty_amount` values minus approved payout amounts).
3. THE Payouts_Page SHALL display a payout request form with fields: Amount (minimum ₹500), Payment Method (Bank Transfer, UPI, PayPal), and Account Details.
4. WHEN an Artist submits a payout request, THE Platform SHALL create a record in the `payouts` table with `status = 'pending'` and display a confirmation message.
5. WHEN an Admin approves a payout, THE Admin_Panel SHALL update the payout record to `status = 'approved'` and the Artist's balance SHALL reflect the deduction.
6. THE Payouts_Page SHALL display a history table of all past payout requests with columns: Date, Amount, Method, Status (Pending / Approved / Rejected).
7. IF an Artist requests a payout amount greater than their available balance, THEN THE Platform SHALL display an error message: "Insufficient balance. Your available balance is ₹[amount]."
8. IF an Artist requests a payout below the minimum threshold (₹500 by default, configurable in Admin Settings), THEN THE Platform SHALL display an error message specifying the minimum amount.

---

### Requirement 11: User Authentication

**User Story:** As a visitor, I want to register and log in securely, so that I can access my Artist account.

#### Acceptance Criteria

1. THE Platform SHALL provide a Sign Up page with fields: Full Name, Email, Password (min 8 characters), and Confirm Password.
2. WHEN a user submits the Sign Up form, THE Platform SHALL hash the password using `password_hash()` with `PASSWORD_BCRYPT` before storing it in the `users` table.
3. WHEN a user submits the Sign Up form with an email that already exists, THE Platform SHALL display the error: "An account with this email already exists."
4. THE Platform SHALL send an email verification link to the user's email address upon registration using the `includes/email_helper.php` helper.
5. WHEN a user clicks the email verification link, THE Platform SHALL set `email_verified = 1` on the user's record and redirect to the login page with a success message.
6. THE Platform SHALL provide a Login page with Email and Password fields and a "Forgot Password" link.
7. WHEN a user submits valid login credentials, THE Platform SHALL create a PHP session with `user_id`, `email`, `name`, and `login_time`, and redirect to the dashboard.
8. WHEN a user submits invalid login credentials, THE Platform SHALL display the error: "Invalid email or password." without specifying which field is incorrect.
9. THE Platform SHALL provide Google OAuth login via the existing `config/google_oauth.php` and `pages/google_callback.php` flow.
10. WHEN a logged-in user visits `/index.php?q=logout`, THE Platform SHALL destroy the session and redirect to the login page.

---

### Requirement 12: Stores / Platform Directory Page

**User Story:** As an Artist, I want to see all the streaming platforms my music will be distributed to, so that I can understand the reach of my distribution.

#### Acceptance Criteria

1. THE Stores_Page SHALL display all supported DSPs grouped by category: Global Streaming, Indian Platforms, Asian Markets, Social Platforms, and Download Stores.
2. THE Stores_Page SHALL display each DSP as a card with the platform logo/icon, platform name, and a brief description.
3. THE Stores_Page SHALL display the total count of supported platforms prominently (e.g., "150+ Platforms").
4. THE Stores_Page SHALL include JSON-LD structured data of type `ItemList` listing the platform names for SEO.

---

### Requirement 13: Help / FAQ Page

**User Story:** As an Artist, I want to find answers to common questions without contacting support, so that I can resolve issues quickly.

#### Acceptance Criteria

1. THE Help_Page SHALL display at least 15 FAQ items organised into sections: Getting Started, Releases, Royalties & Payouts, Subscriptions, and Technical Issues.
2. THE Help_Page SHALL implement accordion-style expand/collapse for each FAQ item using CSS transitions (no JavaScript library dependency).
3. THE Help_Page SHALL include a search input that filters visible FAQ items in real-time as the Artist types, using client-side JavaScript.
4. THE Help_Page SHALL include a "Contact Support" CTA at the bottom linking to the Contact page.
5. THE Help_Page SHALL include JSON-LD structured data of type `FAQPage` listing all questions and answers for SEO.

---

### Requirement 14: Contact Page

**User Story:** As a visitor or Artist, I want to contact the HiTune team easily, so that I can get support or ask questions.

#### Acceptance Criteria

1. THE Contact_Page SHALL display a contact form with fields: Name, Email, Subject (dropdown: General Inquiry, Technical Support, Billing, Partnership), and Message.
2. WHEN a user submits the contact form, THE Platform SHALL send the message to the support email address configured in Admin Settings using `includes/email_helper.php`.
3. WHEN the contact form is submitted successfully, THE Platform SHALL display a confirmation message: "Your message has been sent. We'll get back to you within 24 hours."
4. IF the contact form is submitted with an invalid email address, THEN THE Platform SHALL display an inline validation error.
5. THE Contact_Page SHALL display the support email address, a physical/virtual office address, and social media links.

---

### Requirement 15: Performance and Technical Standards

**User Story:** As an Artist, I want the platform to load quickly and work reliably, so that I have a smooth experience.

#### Acceptance Criteria

1. THE Platform SHALL serve all CSS and JavaScript assets with appropriate `Cache-Control` headers in production (max-age of at least 86400 seconds for static assets).
2. THE Platform SHALL lazy-load all images below the fold using the `loading="lazy"` attribute.
3. THE Platform SHALL minify inline CSS blocks on pages where the total inline CSS exceeds 5KB.
4. THE Platform SHALL use `htmlspecialchars()` on all user-supplied data rendered in HTML to prevent XSS attacks.
5. THE Platform SHALL use prepared statements with bound parameters for all database queries to prevent SQL injection.
6. THE Platform SHALL validate all file uploads (audio and artwork) for MIME type and file size before saving to the `uploads/` directory.
7. WHEN an audio file upload exceeds 200MB, THE Platform SHALL reject the upload and display the error: "File size exceeds the 200MB limit."
8. WHEN a Cover Art image does not meet the 3000×3000 pixel minimum, THE Platform SHALL reject the upload and display the error: "Cover art must be at least 3000×3000 pixels."
9. THE Platform SHALL implement CSRF protection on all POST forms using a session-based token.
10. THE Platform SHALL redirect all HTTP requests to HTTPS using the existing `.htaccess` configuration.
