<?php
/**
 * HiTune Authentication System
 * Enhanced with subscription helpers, plan detection, and feature flags.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Referral capture — any ?ref=CODE link stores the code for the signup flow
// (strategy doc §8 Referral Bonus Program).
if (!empty($_GET['ref'])) {
    $_SESSION['ref_code'] = substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $_GET['ref']), 0, 20);
}

// ============================================================
// Core Auth Helpers
// ============================================================

/**
 * Check whether a user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require the user to be logged in; redirect to login page otherwise.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /index.php?q=login');
        exit;
    }
}

/**
 * Create a session for the given user.
 */
function loginUser(int $id, string $email, string $name): void
{
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = $id;
    $_SESSION['email']      = $email;
    $_SESSION['name']       = $name;
    $_SESSION['login_time'] = time();
}

/**
 * Destroy the current session and redirect to login.
 */
function logoutUser(): void
{
    session_unset();
    session_destroy();
    header('Location: /index.php?q=login');
    exit;
}

// ============================================================
// User Data Helpers
// ============================================================

/**
 * Fetch the current user's row from the database, including their
 * active subscription (if any).
 *
 * @return array|null  Associative array with user + subscription data, or null.
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    global $conn;
    if (!isset($conn)) {
        return null;
    }

    $userId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT u.id, u.email, u.name, u.phone, u.profile_image,
                u.created_at, u.email_verified,
                s.id          AS sub_id,
                s.plan_name   AS sub_plan,
                s.status      AS sub_status,
                s.start_date  AS sub_start,
                s.end_date    AS sub_end
         FROM users u
         LEFT JOIN subscriptions s
               ON s.user_id = u.id
              AND s.status   = 'active'
              AND (s.end_date IS NULL OR s.end_date > NOW())
         WHERE u.id = ?
         ORDER BY s.end_date DESC
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

// ============================================================
// Subscription Helpers
// ============================================================

/**
 * Check whether the current user has an active, non-expired subscription.
 */
function hasActiveSubscription(): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    global $conn;
    if (!isset($conn)) {
        return false;
    }

    $userId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT id FROM subscriptions
         WHERE user_id = ?
           AND status   = 'active'
           AND (end_date IS NULL OR end_date > NOW())
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();

    return $found;
}

/**
 * Return the current user's active plan slug, or null if none.
 *
 * Normalises plan names to lowercase slugs:
 *   'rising_artist' | 'breakout_artist' | 'professional' | null
 */
function getUserPlan(): ?string
{
    if (!isLoggedIn()) {
        return null;
    }

    global $conn;
    if (!isset($conn)) {
        return null;
    }

    $userId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT plan_name FROM subscriptions
         WHERE user_id = ?
           AND status   = 'active'
           AND (end_date IS NULL OR end_date > NOW())
         ORDER BY end_date DESC
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($planName);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || empty($planName)) {
        return null;
    }

    // Normalise to slug
    $slug = strtolower(trim(str_replace([' ', '-'], '_', $planName)));

    // Plans were renamed (Rising Artist -> Artist, Breakout Artist -> Artist Pro,
    // Professional -> Label); internal slugs stay stable, old rows keep working.
    $aliases = ['artist' => 'rising_artist', 'artist_pro' => 'breakout_artist', 'label' => 'professional'];
    if (isset($aliases[$slug])) {
        $slug = $aliases[$slug];
    }

    $validPlans = ['rising_artist', 'breakout_artist', 'professional'];
    return in_array($slug, $validPlans, true) ? $slug : null;
}

/**
 * Return an array of feature flags for the current user's plan.
 *
 * Keys:
 *   platforms_count   int     Number of DSP platforms available
 *   artist_profiles   int     Max artist profiles (PHP_INT_MAX = unlimited)
 *   youtube_content_id bool   YouTube Content ID enabled
 *   custom_label_name  bool   Custom label name field in release form
 *   scheduled_releases bool   Scheduled release date picker
 */
function getUserPlanFeatures(): array
{
    $plan = getUserPlan();

    $defaults = [
        'platforms_count'    => 0,
        'artist_profiles'    => 0,
        'youtube_content_id' => false,
        'custom_label_name'  => false,
        'scheduled_releases' => false,
    ];

    switch ($plan) {
        case 'rising_artist':
            return array_merge($defaults, [
                'platforms_count'    => 50,
                'artist_profiles'    => 1,
                'youtube_content_id' => false,
                'custom_label_name'  => false,
                'scheduled_releases' => false,
            ]);

        case 'breakout_artist':
            return array_merge($defaults, [
                'platforms_count'    => 100,
                'artist_profiles'    => 3,
                'youtube_content_id' => true,
                'custom_label_name'  => true,
                'scheduled_releases' => false,
            ]);

        case 'professional':
            return array_merge($defaults, [
                'platforms_count'    => 150,
                'artist_profiles'    => PHP_INT_MAX,
                'youtube_content_id' => true,
                'custom_label_name'  => true,
                'scheduled_releases' => true,
            ]);

        default:
            return $defaults;
    }
}

/**
 * Read a key from the `settings` table (admin → Settings).
 * Returns $default when unset or stored as an empty string.
 */
function distSetting(string $key, string $default = ''): string
{
    global $conn;
    if (!isset($conn)) return $default;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) return $default;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && $row['setting_value'] !== null && $row['setting_value'] !== '')
        ? (string) $row['setting_value'] : $default;
}

/**
 * Freemium release quota for the current user (strategy doc §9
 * "Freemium Pro Uploads"): N free submitted releases per calendar
 * month; paid subscribers are unlimited. Drafts and rejected
 * releases do not consume quota.
 *
 * Returns [enabled, limit, used, remaining (PHP_INT_MAX = unlimited), subscribed]
 */
function freeReleaseQuota(): array
{
    $enabled = distSetting('free_releases_enabled', '1') === '1';
    $limit   = max(0, (int) distSetting('free_releases_per_month', '2'));

    $quota = ['enabled' => $enabled, 'limit' => $limit, 'used' => 0, 'remaining' => $limit, 'subscribed' => false];
    if (!isLoggedIn()) return $quota;

    if (hasActiveSubscription()) {
        $quota['subscribed'] = true;
        $quota['remaining']  = PHP_INT_MAX;
        return $quota;
    }
    if (!$enabled) {
        $quota['remaining'] = 0;
        return $quota;
    }

    global $conn;
    if (isset($conn)) {
        $uid = (int) $_SESSION['user_id'];
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS c FROM releases
             WHERE user_id = ? AND status NOT IN ('draft','rejected')
               AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        if ($stmt) {
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $quota['used'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
        }
    }
    $quota['remaining'] = max(0, $quota['limit'] - $quota['used']);
    return $quota;
}

/**
 * Creator Day (strategy doc §9 "0% Commission Days"): one day per
 * month when artists keep 100% of tips/royalties. Day-of-month is an
 * admin setting; the actual commission uplift is applied on the
 * HiTune Music side (tip_artist_pct / tip_event_until).
 */
function creatorDayInfo(): array
{
    $enabled = distSetting('creator_day_enabled', '1') === '1';
    $day     = min(28, max(1, (int) distSetting('creator_day_date', '1')));
    return [
        'enabled' => $enabled,
        'day'     => $day,
        'active'  => $enabled && ((int) date('j') === $day),
    ];
}
