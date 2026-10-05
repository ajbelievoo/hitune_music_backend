<?php
/**
 * Payment Gateway Configuration
 * Razorpay & Cashfree API Keys - Database Driven
 */

require_once __DIR__ . '/../config.php';

// ============================================
// DATABASE SETTINGS HELPER
// ============================================
function getPaymentSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// ============================================
// GATEWAY STATUS CHECK
// ============================================
function isRazorpayEnabled() {
    return getPaymentSetting('razorpay_enabled', '1') === '1';
}

function isCashfreeEnabled() {
    return getPaymentSetting('cashfree_enabled', '1') === '1';
}

// ============================================
// RAZORPAY CONFIGURATION
// ============================================
function getRazorpayCredentials() {
    $testMode = getPaymentSetting('razorpay_test_mode', '1') === '1';
    
    if ($testMode) {
        return [
            'key_id' => getPaymentSetting('razorpay_key_id', 'rzp_test_YOUR_KEY'),
            'key_secret' => getPaymentSetting('razorpay_key_secret', ''),
            'test_mode' => true
        ];
    }
    return [
        'key_id' => getPaymentSetting('razorpay_key_id', 'rzp_live_YOUR_KEY'),
        'key_secret' => getPaymentSetting('razorpay_key_secret', ''),
        'test_mode' => false
    ];
}

// ============================================
// CASHFREE CONFIGURATION
// ============================================
function getCashfreeCredentials() {
    $testMode = getPaymentSetting('cashfree_test_mode', '1') === '1';
    
    $apiUrl = $testMode ? 'https://sandbox.cashfree.com/pg' : 'https://api.cashfree.com/pg';
    
    return [
        'app_id' => getPaymentSetting('cashfree_app_id', 'YOUR_APP_ID'),
        'secret_key' => getPaymentSetting('cashfree_secret_key', ''),
        'api_url' => $apiUrl,
        'test_mode' => $testMode
    ];
}

// ============================================
// SUBSCRIPTION PLANS CONFIGURATION
// ============================================
function getSubscriptionPlans() {
    return [
        'rising_artist' => [
            'name' => 'Artist',
            'amount' => 69900, // Amount in paise (₹699 * 100)
            'amount_inr' => 699,
            'duration_days' => 365,
            'description' => 'Upload to 50+ platforms, Unlimited releases, Free ISRC & UPC, Basic analytics, 1 artist profile',
            'features' => ['50+ platforms', 'Unlimited releases', '100% royalties', 'Free ISRC & UPC', 'Basic analytics', '48h support', '1 artist profile']
        ],
        'breakout_artist' => [
            'name' => 'Artist Pro',
            'amount' => 149900, // Amount in paise (₹1499 * 100)
            'amount_inr' => 1499,
            'duration_days' => 365,
            'description' => 'Upload to 100+ platforms, YouTube Content ID, Custom label, Revenue splits, 3 artist profiles',
            'features' => ['100+ platforms', 'Unlimited releases', '100% royalties', 'Advanced analytics', 'YouTube Content ID', 'Custom label name', 'Revenue splits', '24h support', '3 artist profiles']
        ],
        'professional' => [
            'name' => 'Label',
            'amount' => 249900, // Amount in paise (₹2499 * 100)
            'amount_inr' => 2499,
            'duration_days' => 365,
            'description' => 'Upload to 150+ platforms, Scheduled releases, Playlist pitching, Unlimited artist profiles, VIP support',
            'features' => ['150+ platforms', 'Unlimited releases', '100% royalties', 'Professional analytics', 'YouTube Content ID', 'Custom label name', 'Revenue splits', 'Scheduled releases + pre-save', 'Playlist pitching', '12h support', 'Unlimited artist profiles']
        ]
    ];
}

// Get single plan details
function getPlanDetails($planId) {
    $plans = getSubscriptionPlans();
    return isset($plans[$planId]) ? $plans[$planId] : null;
}

// Resolve a plan id from a stored plan name (payments.plan_name stores the display name)
function getPlanIdByName($planName) {
    foreach (getSubscriptionPlans() as $id => $plan) {
        if ($plan['name'] === $planName || $id === $planName) return $id;
    }
    return $planName;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

// Generate unique order ID
function generateOrderId($prefix = 'ORD') {
    return $prefix . time() . rand(1000, 9999);
}

// Format amount for display
function formatAmount($amountInPaise) {
    return '₹' . number_format($amountInPaise / 100, 2);
}

// Verify Razorpay signature
function verifyRazorpaySignature($orderId, $paymentId, $signature, $secret) {
    $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
    return hash_equals($generatedSignature, $signature);
}

// Fetch Cashfree order details server-to-server (PG v3 API).
// Returns the decoded order array or null on failure.
function getCashfreeOrder($orderId) {
    $credentials = getCashfreeCredentials();
    if (empty($credentials['app_id']) || empty($credentials['secret_key'])) return null;

    $ch = curl_init($credentials['api_url'] . '/orders/' . urlencode($orderId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-client-id: ' . $credentials['app_id'],
        'x-client-secret: ' . $credentials['secret_key'],
        'x-api-version: 2023-08-01'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        error_log("CASHFREE ORDER FETCH ERROR: order=$orderId http=$httpCode response=$response");
        return null;
    }
    return json_decode($response, true);
}

// Fetch payment attempts for a Cashfree order (for reference id / cf_payment_id)
function getCashfreeOrderPayments($orderId) {
    $credentials = getCashfreeCredentials();
    if (empty($credentials['app_id']) || empty($credentials['secret_key'])) return [];

    $ch = curl_init($credentials['api_url'] . '/orders/' . urlencode($orderId) . '/payments');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-client-id: ' . $credentials['app_id'],
        'x-client-secret: ' . $credentials['secret_key'],
        'x-api-version: 2023-08-01'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return [];
    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

// Verify Cashfree webhook signature (x-webhook-signature = base64(HMAC-SHA256(rawBody, secret)))
function verifyCashfreeWebhookSignature($rawBody, $signatureHeader, $secret) {
    if (empty($signatureHeader) || empty($secret)) return false;
    $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
    return hash_equals($expected, $signatureHeader);
}

// ============================================
// SUBSCRIPTION & PAYMENT HELPERS
// ============================================

/**
 * Activate (or extend) a subscription for a user after successful payment.
 * Inserts a new subscriptions row with status='active', end_date = NOW() + 1 YEAR.
 * Returns the subscription id.
 */
function activateSubscription(int $userId, string $planId, int $paymentId): int
{
    global $conn;

    $planDetails = getPlanDetails($planId);
    $planName    = $planDetails ? $planDetails['name'] : $planId;
    $amount      = $planDetails ? $planDetails['amount_inr'] : 0;

    $stmt = $conn->prepare(
        "INSERT INTO subscriptions (user_id, plan_name, amount, status, start_date, end_date)
         VALUES (?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))"
    );
    $stmt->bind_param("isd", $userId, $planName, $amount);
    $stmt->execute();
    $subscriptionId = (int) $conn->insert_id;
    $stmt->close();

    // Link payment record to subscription
    if ($paymentId > 0 && $subscriptionId > 0) {
        $upd = $conn->prepare("UPDATE payments SET subscription_id = ? WHERE id = ?");
        $upd->bind_param("ii", $subscriptionId, $paymentId);
        $upd->execute();
        $upd->close();
    }

    return $subscriptionId;
}

/**
 * Record a payment entry in the payments table.
 * Returns the new payment id.
 */
function recordPayment(int $userId, string $planId, string $gateway, string $orderId, string $status): int
{
    global $conn;

    $planDetails = getPlanDetails($planId);
    $planName    = $planDetails ? $planDetails['name'] : $planId;
    $amount      = $planDetails ? $planDetails['amount_inr'] : 0;

    $stmt = $conn->prepare(
        "INSERT INTO payments (user_id, plan_name, plan_amount, payment_gateway, payment_order_id, payment_status, currency)
         VALUES (?, ?, ?, ?, ?, ?, 'INR')"
    );
    $stmt->bind_param("isdsss", $userId, $planName, $amount, $gateway, $orderId, $status);
    $stmt->execute();
    $paymentId = (int) $conn->insert_id;
    $stmt->close();

    return $paymentId;
}
?>
