<?php
/**
 * Google OAuth Callback Handler
 * Handles Google Sign-in/Sign-up callback
 */

// session handled by auth.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sso_sync.php';
require_once __DIR__ . '/../includes/referral.php';

// TEMP DEBUG - remove after OAuth verified

// Google OAuth Configuration - read from DB settings
$GOOGLE_CLIENT_ID = '';
$GOOGLE_CLIENT_SECRET = '';
$GOOGLE_REDIRECT_URI = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?q=google_callback';

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('google_client_id', 'google_client_secret', 'google_login_enabled')");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $oauthSettings = [];
        while ($row = $result->fetch_assoc()) {
            $oauthSettings[$row['setting_key']] = $row['setting_value'];
        }
        $stmt->close();
        if (($oauthSettings['google_login_enabled'] ?? '0') === '1') {
            $GOOGLE_CLIENT_ID     = $oauthSettings['google_client_id']     ?? '';
            $GOOGLE_CLIENT_SECRET = $oauthSettings['google_client_secret'] ?? '';
        }
    }
}

// Check if Google OAuth is configured
if (empty($GOOGLE_CLIENT_ID) || empty($GOOGLE_CLIENT_SECRET)) {
    header('Location: /index.php?q=login&error=google_not_configured');
    exit;
}

// Handle OAuth errors from Google (e.g. user denied access)
if (isset($_GET['error'])) {
    header('Location: /index.php?q=login&error=google_auth_denied');
    exit;
}

// Check if code is received from Google
if (!isset($_GET['code'])) {
    header('Location: /index.php?q=login&error=google_auth_failed');
    exit;
}

$code = $_GET['code'];

// Exchange authorization code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code'          => $code,
    'client_id'     => $GOOGLE_CLIENT_ID,
    'client_secret' => $GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => $GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$tokenResponse = curl_exec($ch);
$curlError     = curl_error($ch);
$httpCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError || $httpCode !== 200 || !$tokenResponse) {
    // Log for debugging
    error_log("Google OAuth token error: HTTP $httpCode | cURL: $curlError | Response: $tokenResponse");
    header('Location: /index.php?q=login&error=google_token_failed');
    exit;
}

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    error_log("Google OAuth no access_token: " . $tokenResponse);
    header('Location: /index.php?q=login&error=google_no_token');
    exit;
}

$accessToken = $tokenData['access_token'];

// Get user info from Google using Authorization header (more reliable)
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

$userInfoResponse = curl_exec($ch);
$httpCode         = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$userInfoResponse) {
    header('Location: /index.php?q=login&error=google_userinfo_failed');
    exit;
}

$userInfo = json_decode($userInfoResponse, true);

if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
    header('Location: /index.php?q=login&error=google_invalid_data');
    exit;
}

$googleId = $userInfo['id'];
$email    = filter_var($userInfo['email'], FILTER_SANITIZE_EMAIL);
$name     = $userInfo['name'] ?? explode('@', $email)[0];
$picture  = $userInfo['picture'] ?? null;

// Ensure required columns exist before any queries
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_type VARCHAR(20) DEFAULT 'email'");
$conn->query("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
// Add google_id without UNIQUE to avoid conflicts
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL");
// Drop unique constraint on google_id if exists (safe to ignore error)
@$conn->query("ALTER TABLE users DROP INDEX google_id");

// Check if user exists with this Google ID
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE google_id = ?");
if (!$stmt) {
    error_log("Google lookup prepare failed: " . $conn->error);
    header('Location: /index.php?q=login&error=google_token_failed');
    exit;
}
$stmt->bind_param("s", $googleId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $stmt->close();
    loginUser($user['id'], $user['email'], $user['name']);
    header('Location: /index.php?q=dashboard');
    exit;
}
$stmt->close();

// Check if email already exists (link Google to existing account)
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
if (!$stmt) {
    error_log("Email lookup prepare failed: " . $conn->error);
    header('Location: /index.php?q=login&error=google_token_failed');
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $stmt->close();

    $updateStmt = $conn->prepare("UPDATE users SET google_id = ?, auth_type = 'google', email_verified = 1 WHERE id = ?");
    if (!$updateStmt) {
        error_log("Update prepare failed: " . $conn->error);
        header('Location: /index.php?q=login&error=google_token_failed');
        exit;
    }
    $updateStmt->bind_param("si", $googleId, $user['id']);
    $updateStmt->execute();
    $updateStmt->close();

    loginUser($user['id'], $user['email'], $user['name']);
    header('Location: /index.php?q=dashboard');
    exit;
}
$stmt->close();

// New user - create account
// Ensure required columns exist (safe for repeated runs)
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_type VARCHAR(20) DEFAULT 'email'");
$conn->query("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL");
@$conn->query("ALTER TABLE users DROP INDEX google_id");

$stmt = $conn->prepare("INSERT INTO users (name, email, google_id, auth_type, email_verified, profile_image, created_at) VALUES (?, ?, ?, 'google', 1, ?, NOW())");
if (!$stmt) {
    error_log("Google signup prepare failed: " . $conn->error);
    header('Location: /index.php?q=login&error=google_create_failed');
    exit;
}
$stmt->bind_param("ssss", $name, $email, $googleId, $picture);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $stmt->close();

    // Referral link + personal code for the new account
    if (!empty($_SESSION['ref_code'])) {
        $referrer = ref_user_by_code($conn, $_SESSION['ref_code']);
        if ($referrer) ref_record_signup($conn, $referrer, $userId);
    }
    ref_code_for($conn, $userId);

    // Mirror to Hitune Music — Google accounts have no password,
    // so the music copy gets a random unusable hash (verified).
    $randHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    sso_music_create($conn, $name, $email, $randHash, true);

    loginUser($userId, $email, $name);
    header('Location: /index.php?q=dashboard&welcome=1');
    exit;
}

// Execute failed - check if it's a duplicate email/google_id issue
$executeError = $stmt->error;
$stmt->close();
error_log("Google INSERT failed: " . $executeError . " | email: $email | googleId: $googleId");

// If duplicate entry, try to find and login the existing user
if (strpos($executeError, 'Duplicate') !== false || strpos($executeError, 'duplicate') !== false) {
    $findStmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ? OR google_id = ? LIMIT 1");
    if ($findStmt) {
        $findStmt->bind_param("ss", $email, $googleId);
        $findStmt->execute();
        $findResult = $findStmt->get_result();
        if ($findResult->num_rows === 1) {
            $existingUser = $findResult->fetch_assoc();
            $findStmt->close();
            // Link google_id if not set
            $conn->query("UPDATE users SET google_id = '$googleId', auth_type = 'google', email_verified = 1 WHERE id = " . (int)$existingUser['id']);
            loginUser($existingUser['id'], $existingUser['email'], $existingUser['name']);
            header('Location: /index.php?q=dashboard');
            exit;
        }
        $findStmt->close();
    }
}

header('Location: /index.php?q=login&error=google_create_failed');
exit;
