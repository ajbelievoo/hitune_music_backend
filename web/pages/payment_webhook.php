<?php
/**
 * Payment Webhook Handler - Cashfree (signature-verified, server-to-server confirmed)
 *
 * Cashfree sends x-webhook-signature = base64(HMAC-SHA256(rawBody, secret)).
 * We verify the signature, then re-fetch the order via the Cashfree API before
 * marking anything paid. Safe to receive multiple times (idempotent).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/payment_config.php';

$gateway = $_GET['gateway'] ?? 'cashfree';
$rawBody = file_get_contents('php://input');

if ($gateway !== 'cashfree') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
    exit;
}

$credentials = getCashfreeCredentials();
$signature   = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (empty($credentials['secret_key']) || !verifyCashfreeWebhookSignature($rawBody, $signature, $credentials['secret_key'])) {
    http_response_code(401);
    error_log('CASHFREE WEBHOOK: invalid signature or missing secret');
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

$payload = json_decode($rawBody, true);
$type    = $payload['type'] ?? '';
$orderId = $payload['data']['order']['order_id'] ?? '';

if (empty($orderId)) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
    exit;
}

// Confirm with the API — never trust the webhook payload alone
$order = getCashfreeOrder($orderId);
if (!$order) {
    // Temporary failure — tell Cashfree to retry
    http_response_code(500);
    echo json_encode(['status' => 'retry']);
    exit;
}

if (($order['order_status'] ?? '') !== 'PAID') {
    http_response_code(200);
    echo json_encode(['status' => 'not_paid']);
    exit;
}

// Idempotent completion
$stmt = $conn->prepare("SELECT id, user_id, plan_name, plan_amount, payment_status FROM payments WHERE payment_order_id = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$paymentRecord = $stmt->get_result()->fetch_assoc();

if (!$paymentRecord) {
    error_log("CASHFREE WEBHOOK: unknown order $orderId");
    http_response_code(200);
    echo json_encode(['status' => 'unknown_order']);
    exit;
}

if ($paymentRecord['payment_status'] === 'completed') {
    http_response_code(200);
    echo json_encode(['status' => 'already_completed']);
    exit;
}

$referenceId = '';
$payments = getCashfreeOrderPayments($orderId);
foreach ($payments as $p) {
    if (($p['payment_status'] ?? '') === 'SUCCESS') {
        $referenceId = $p['cf_payment_id'] ?? '';
        break;
    }
}

$amountPaid   = $order['order_amount'] ?? $paymentRecord['plan_amount'];
$responseJson = json_encode(['webhook' => $payload, 'order' => $order]);

$stmt = $conn->prepare("UPDATE payments SET payment_id = ?, payment_status = 'completed', amount_paid = ?, payment_response = ?, completed_at = NOW() WHERE id = ? AND payment_status != 'completed'");
$stmt->bind_param("sdsi", $referenceId, $amountPaid, $responseJson, $paymentRecord['id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    activateSubscription((int)$paymentRecord['user_id'], getPlanIdByName($paymentRecord['plan_name']), (int)$paymentRecord['id']);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
