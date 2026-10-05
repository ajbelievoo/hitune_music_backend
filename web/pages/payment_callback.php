<?php
/**
 * Payment Callback Handler
 * Handles successful/failed payment callbacks from Razorpay and Cashfree
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/payment_config.php';

$gateway = $_GET['gateway'] ?? '';
$message = '';
$status = 'pending';

// -------------------------------------------------------
// Idempotency check — don't process an already-completed payment
// -------------------------------------------------------
function getOrderIdFromRequest(string $gateway): string {
    if ($gateway === 'razorpay') {
        return $_POST['razorpay_order_id'] ?? '';
    }
    if ($gateway === 'cashfree') {
        return $_GET['order_id'] ?? '';
    }
    return '';
}

$checkOrderId = getOrderIdFromRequest($gateway);
if (!empty($checkOrderId)) {
    global $conn;
    $idempotencyStmt = $conn->prepare(
        "SELECT id FROM payments WHERE payment_order_id = ? AND payment_status = 'completed' LIMIT 1"
    );
    $idempotencyStmt->bind_param("s", $checkOrderId);
    $idempotencyStmt->execute();
    $idempotencyStmt->store_result();
    if ($idempotencyStmt->num_rows > 0) {
        $idempotencyStmt->close();
        $_SESSION['flash_success'] = 'Your subscription is now active!';
        header('Location: /index.php?q=dashboard');
        exit;
    }
    $idempotencyStmt->close();
}

if ($gateway === 'razorpay') {
    // Handle Razorpay Callback
    $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
    $razorpaySignature = $_POST['razorpay_signature'] ?? '';
    $planId = $_POST['plan_id'] ?? '';
    
    if ($razorpayPaymentId && $razorpayOrderId && $razorpaySignature) {
        // Verify signature
        $credentials = getRazorpayCredentials();
        $isValid = verifyRazorpaySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature, $credentials['key_secret']);
        
        if ($isValid) {
            // Fetch payment details from Razorpay
            $apiUrl = 'https://api.razorpay.com/v1/payments/' . $razorpayPaymentId;
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode($credentials['key_id'] . ':' . $credentials['key_secret'])
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $paymentData = json_decode($response, true);
            
            if ($paymentData && $paymentData['status'] === 'captured') {
                // Update payment record to completed
                $stmt = $conn->prepare("UPDATE payments SET payment_id = ?, payment_status = 'completed', payment_signature = ?, amount_paid = ?, payment_response = ?, completed_at = NOW() WHERE payment_order_id = ?");
                $amountPaid = $paymentData['amount'] / 100;
                $stmt->bind_param("ssdss", $razorpayPaymentId, $razorpaySignature, $amountPaid, $response, $razorpayOrderId);
                $stmt->execute();
                
                // Get payment record
                $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_order_id = ?");
                $stmt->bind_param("s", $razorpayOrderId);
                $stmt->execute();
                $paymentRecord = $stmt->get_result()->fetch_assoc();
                
                if ($paymentRecord) {
                    // Resolve plan from the payment record (Razorpay does not POST plan_id back)
                    $resolvedPlanId = $planId ?: getPlanIdByName($paymentRecord['plan_name']);
                    if (getPlanDetails($resolvedPlanId)) {
                        activateSubscription($paymentRecord['user_id'], $resolvedPlanId, $paymentRecord['id']);
                        $status = 'success';
                        $_SESSION['flash_success'] = 'Your subscription is now active!';
                    }
                }
            } else {
                // Payment failed — update record
                $stmt = $conn->prepare("UPDATE payments SET payment_status = 'failed', payment_response = ? WHERE payment_order_id = ?");
                $stmt->bind_param("ss", $response, $razorpayOrderId);
                $stmt->execute();
                
                $status = 'failed';
                $message = 'Payment failed. Please try again.';
            }
        } else {
            $status = 'failed';
            $message = 'Invalid payment signature. Please contact support.';
        }
    } else {
        $status = 'failed';
        $message = 'Invalid payment data received.';
    }
    
} elseif ($gateway === 'cashfree') {
    // Handle Cashfree Callback — verify order status server-to-server (never trust GET params)
    $orderId = $_GET['order_id'] ?? '';

    if ($orderId) {
        // Get payment record
        $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_order_id = ?");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $paymentRecord = $stmt->get_result()->fetch_assoc();

        if ($paymentRecord) {
            $order = getCashfreeOrder($orderId);
            $orderStatus = $order['order_status'] ?? '';

            if ($orderStatus === 'PAID') {
                // Grab reference id from the payments list
                $referenceId = '';
                $payments = getCashfreeOrderPayments($orderId);
                foreach ($payments as $p) {
                    if (($p['payment_status'] ?? '') === 'SUCCESS') {
                        $referenceId = $p['cf_payment_id'] ?? '';
                        break;
                    }
                }
                $responseJson = json_encode(['order' => $order, 'payments' => $payments]);
                $amountPaid = $order['order_amount'] ?? $paymentRecord['plan_amount'];
                $stmt = $conn->prepare("UPDATE payments SET payment_id = ?, payment_status = 'completed', amount_paid = ?, payment_response = ?, completed_at = NOW() WHERE payment_order_id = ?");
                $stmt->bind_param("sdss", $referenceId, $amountPaid, $responseJson, $orderId);
                $stmt->execute();

                // Activate subscription using helper
                activateSubscription($paymentRecord['user_id'], getPlanIdByName($paymentRecord['plan_name']), $paymentRecord['id']);

                $status = 'success';
                $_SESSION['flash_success'] = 'Your subscription is now active!';
            } elseif ($orderStatus === 'ACTIVE' || $order === null) {
                // Order still unpaid or API unreachable — keep pending, don't mark failed
                $status = 'failed';
                $message = 'Payment is not completed yet. If you already paid, it will reflect shortly — otherwise please try again.';
            } else {
                // EXPIRED / TERMINATED etc.
                $responseJson = json_encode(['order' => $order]);
                $stmt = $conn->prepare("UPDATE payments SET payment_status = 'failed', payment_response = ? WHERE payment_order_id = ?");
                $stmt->bind_param("ss", $responseJson, $orderId);
                $stmt->execute();

                $status = 'failed';
                $message = 'Payment failed. Please try again.';
            }
        } else {
            $status = 'failed';
            $message = 'Payment record not found.';
        }
    } else {
        $status = 'failed';
        $message = 'Invalid order ID.';
    }
}

// Redirect to dashboard on success
if ($status === 'success') {
    header('Location: /index.php?q=dashboard');
    exit;
}

$pageTitle = 'Payment Status - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .status-container {
        max-width: 600px;
        margin: 150px auto 60px;
        padding: 0 20px;
        text-align: center;
    }
    
    .status-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 60px 40px;
    }
    
    .status-icon {
        font-size: 80px;
        margin-bottom: 30px;
    }
    
    .status-icon.success {
        color: #00c853;
    }
    
    .status-icon.failed {
        color: #00b7ff;
    }
    
    .status-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .status-message {
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 40px;
        font-size: 16px;
        line-height: 1.6;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 15px 30px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
    }
    
    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="status-container">
    <div class="status-card">
        <?php if ($status === 'success'): ?>
            <div class="status-icon success">
                <i class="mdi mdi-check-circle"></i>
            </div>
            <h1 class="status-title">Payment Successful!</h1>
            <p class="status-message"><?php echo htmlspecialchars($message); ?> You can now start releasing your music to 150+ streaming platforms worldwide.</p>
        <?php else: ?>
            <div class="status-icon failed">
                <i class="mdi mdi-close-circle"></i>
            </div>
            <h1 class="status-title">Payment Failed</h1>
            <p class="status-message"><?php echo htmlspecialchars($message); ?> Please try again or contact our support team for assistance.</p>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if ($status === 'success'): ?>
                <a href="/index.php?q=dashboard" class="btn btn-primary">
                    <i class="mdi mdi-view-dashboard"></i> Go to Dashboard
                </a>
                <a href="/index.php?q=create-release" class="btn btn-secondary">
                    <i class="mdi mdi-music-note-plus"></i> Create Release
                </a>
            <?php else: ?>
                <a href="/index.php?q=pricing" class="btn btn-primary">
                    <i class="mdi mdi-refresh"></i> Try Again
                </a>
                <a href="/index.php?q=contact" class="btn btn-secondary">
                    <i class="mdi mdi-help-circle"></i> Contact Support
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
