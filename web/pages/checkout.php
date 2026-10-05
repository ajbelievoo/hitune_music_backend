<?php
/**
 * Payment Checkout Page
 * Handles Razorpay and Cashfree payment flow
 */
$pageTitle = 'Checkout - HiTune Music Distribution';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/payment_config.php';
require_once __DIR__ . '/../includes/csrf.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $currentPlan = $_GET['plan'] ?? '';
    header('Location: /index.php?q=login&redirect=' . urlencode('/index.php?q=checkout&plan=' . $currentPlan));
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: /index.php?q=login');
    exit;
}

// Get plan details
$planId = $_GET['plan'] ?? '';
$gateway = $_GET['gateway'] ?? 'razorpay';
$planDetails = getPlanDetails($planId);

if (!$planDetails) {
    header('Location: /index.php?q=pricing');
    exit;
}

// Check which gateways are enabled
$razorpayEnabled = isRazorpayEnabled();
$cashfreeEnabled = isCashfreeEnabled();

// Set default gateway based on availability
if (!$razorpayEnabled && $cashfreeEnabled) {
    $gateway = 'cashfree';
} elseif ($razorpayEnabled && !$cashfreeEnabled) {
    $gateway = 'razorpay';
}

$error = '';
$paymentOrderId = '';
$cashfreeToken = '';

// Create payment order based on selected gateway
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($razorpayEnabled || $cashfreeEnabled)) {
    // CSRF validation
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $selectedGateway = $_POST['gateway'] ?? 'razorpay';
    
    // Check if selected gateway is enabled
    if ($selectedGateway === 'razorpay' && !$razorpayEnabled) {
        $error = 'Razorpay is currently unavailable.';
    } elseif ($selectedGateway === 'cashfree' && !$cashfreeEnabled) {
        $error = 'Cashfree is currently unavailable.';
    } elseif ($selectedGateway === 'razorpay') {
        // Create Razorpay Order
        $credentials = getRazorpayCredentials();
        
        // Check if API keys are configured
        if (empty($credentials['key_id']) || $credentials['key_id'] === 'rzp_test_YOUR_KEY' || empty($credentials['key_secret'])) {
            $error = 'Payment gateway not configured. Please contact admin.';
        } else {
            $orderId = generateOrderId('RZP');
            
            // Store payment record in database
            $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_name, plan_amount, payment_gateway, payment_order_id, payment_status, currency) VALUES (?, ?, ?, ?, ?, 'pending', 'INR')");
            $amountInRupees = $planDetails['amount_inr'];
            $stmt->bind_param("isdss", $currentUser['id'], $planDetails['name'], $amountInRupees, $selectedGateway, $orderId);
            $stmt->execute();
            $paymentRecordId = $conn->insert_id;
            
            // Create Razorpay Order via API
            $apiUrl = 'https://api.razorpay.com/v1/orders';
            
            $postData = [
                'amount' => $planDetails['amount'],
                'currency' => 'INR',
                'receipt' => $orderId,
                'notes' => [
                    'plan_id' => $planId,
                    'user_id' => $currentUser['id'],
                    'plan_name' => $planDetails['name']
                ]
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($credentials['key_id'] . ':' . $credentials['key_secret'])
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $orderData = json_decode($response, true);
                
                // Update payment record with Razorpay order ID
                $stmt = $conn->prepare("UPDATE payments SET payment_order_id = ? WHERE id = ?");
                $stmt->bind_param("si", $orderData['id'], $paymentRecordId);
                $stmt->execute();
                
                $paymentOrderId = $orderData['id'];
                $gateway = 'razorpay';
            } else {
                $error = 'Failed to create payment order. Please try again.';
                error_log("RAZORPAY ERROR: HTTP $httpCode, Response: $response");
            }
        }
    } elseif ($selectedGateway === 'cashfree') {
        // Create Cashfree Order
        $credentials = getCashfreeCredentials();
        
        // Check if API keys are configured
        if (empty($credentials['app_id']) || $credentials['app_id'] === 'YOUR_APP_ID' || empty($credentials['secret_key'])) {
            $error = 'Payment gateway not configured. Please contact admin.';
        } else {
            $orderId = generateOrderId('CF');
            
            // Store payment record in database
            $stmt = $conn->prepare("INSERT INTO payments (user_id, plan_name, plan_amount, payment_gateway, payment_order_id, payment_status, currency) VALUES (?, ?, ?, ?, ?, 'pending', 'INR')");
            $amountInRupees = $planDetails['amount_inr'];
            $stmt->bind_param("isdss", $currentUser['id'], $planDetails['name'], $amountInRupees, $selectedGateway, $orderId);
            $stmt->execute();
            $paymentRecordId = $conn->insert_id;
            
            // Create Cashfree Order via API
            $apiUrl = $credentials['api_url'] . '/orders';
            
            $postData = [
                'order_id' => $orderId,
                'order_amount' => $planDetails['amount_inr'],
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'user_' . $currentUser['id'],
                    'customer_name' => $currentUser['name'],
                    'customer_email' => $currentUser['email'],
                    'customer_phone' => $currentUser['phone'] ?? '9999999999'
                ],
                'order_meta' => [
                    'return_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?q=payment_callback&gateway=cashfree&order_id=' . $orderId,
                    'notify_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?q=payment_webhook&gateway=cashfree'
                ]
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-client-id: ' . $credentials['app_id'],
                'x-client-secret: ' . $credentials['secret_key'],
                'x-api-version: 2023-08-01'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $orderData = json_decode($response, true);
                
                // Update payment record with Cashfree order token
                $stmt = $conn->prepare("UPDATE payments SET payment_order_id = ?, payment_response = ? WHERE id = ?");
                $responseJson = json_encode($orderData);
                $stmt->bind_param("ssi", $orderId, $responseJson, $paymentRecordId);
                $stmt->execute();
                
                $paymentOrderId = $orderId;
                // Support both old order_token and new payment_session_id
                $cashfreeToken = $orderData['payment_session_id'] ?? $orderData['order_token'] ?? '';
                $gateway = 'cashfree';
            } else {
                $error = 'Failed to create payment order. Please try again.';
                error_log("CASHFREE ERROR: HTTP $httpCode, Response: $response");
            }
        }
    }
}

include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .checkout-container {
        max-width: 800px;
        margin: 120px auto 60px;
        padding: 0 20px;
    }
    
    .checkout-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 40px;
    }
    
    .checkout-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .checkout-header h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .checkout-header p {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .plan-summary {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
    }
    
    .plan-summary h2 {
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .plan-price-large {
        font-size: 48px;
        font-weight: 800;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
    }
    
    .plan-features-checkout {
        list-style: none;
    }
    
    .plan-features-checkout li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .plan-features-checkout li i {
        color: #00c853;
    }
    
    .gateway-selector {
        margin-bottom: 30px;
    }
    
    .gateway-selector h3 {
        font-size: 18px;
        margin-bottom: 20px;
    }
    
    .gateway-options {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .gateway-option {
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }
    
    .gateway-option:hover {
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .gateway-option.selected {
        border-color: #00b7ff;
        background: rgba(0, 183, 255, 0.1);
    }
    
    .gateway-option img {
        height: 40px;
        margin-bottom: 10px;
    }
    
    .gateway-option h4 {
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .gateway-option p {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
    }
    
    .pay-button {
        width: 100%;
        padding: 18px;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
    }
    
    .pay-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
    }
    
    .error-message {
        background: rgba(255, 0, 0, 0.1);
        border: 1px solid rgba(255, 0, 0, 0.3);
        color: #00b7ff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .secure-badge {
        text-align: center;
        margin-top: 20px;
        color: rgba(255, 255, 255, 0.5);
        font-size: 14px;
    }
    
    .secure-badge i {
        color: #00c853;
        margin-right: 5px;
    }
</style>

<div class="checkout-container">
    <div class="checkout-card">
        <div class="checkout-header">
            <h1>Complete Your Purchase</h1>
            <p>You're just one step away from releasing your music worldwide</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="plan-summary">
            <h2><?php echo htmlspecialchars($planDetails['name']); ?> Plan</h2>
            <div class="plan-price-large">₹<?php echo number_format($planDetails['amount_inr']); ?><span style="font-size: 18px; -webkit-text-fill-color: rgba(255,255,255,0.5);">/year</span></div>
            <ul class="plan-features-checkout">
                <?php foreach ($planDetails['features'] as $feature): ?>
                    <li><i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($feature); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php if (empty($paymentOrderId)): ?>
            <?php if ($razorpayEnabled || $cashfreeEnabled): ?>
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="gateway-selector">                        <h3>Select Payment Method</h3>
                        <div class="gateway-options">
                            <?php if ($razorpayEnabled): ?>
                            <div class="gateway-option <?php echo $gateway === 'razorpay' ? 'selected' : ''; ?>" onclick="selectGateway('razorpay')">
                                <h4><i class="mdi mdi-credit-card" style="font-size: 24px; color: #3395ff;"></i> Razorpay</h4>
                                <p>Cards, UPI, Net Banking, Wallets</p>
                                <input type="radio" name="gateway" value="razorpay" <?php echo $gateway === 'razorpay' ? 'checked' : ''; ?> style="display: none;">
                            </div>
                            <?php endif; ?>
                            <?php if ($cashfreeEnabled): ?>
                            <div class="gateway-option <?php echo $gateway === 'cashfree' ? 'selected' : ''; ?>" onclick="selectGateway('cashfree')">
                                <h4><i class="mdi mdi-wallet" style="font-size: 24px; color: #00d4aa;"></i> Cashfree</h4>
                                <p>UPI, Cards, Net Banking, EMI</p>
                                <input type="radio" name="gateway" value="cashfree" <?php echo $gateway === 'cashfree' ? 'checked' : ''; ?> style="display: none;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="pay-button">
                        <i class="mdi mdi-lock"></i> Pay ₹<?php echo number_format($planDetails['amount_inr']); ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($gateway === 'razorpay'): ?>
                <!-- Razorpay Checkout -->
                <button id="razorpay-btn" class="pay-button">
                    <i class="mdi mdi-lock"></i> Pay ₹<?php echo number_format($planDetails['amount_inr']); ?>
                </button>
                
                <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                <script>
                    var options = {
                        "key": "<?php echo getRazorpayCredentials()['key_id']; ?>",
                        "amount": "<?php echo $planDetails['amount']; ?>",
                        "currency": "INR",
                        "name": "HiTune Music Distribution",
                        "description": "<?php echo $planDetails['name']; ?> Plan Subscription",
                        "order_id": "<?php echo $paymentOrderId; ?>",
                        "prefill": {
                            "name": "<?php echo htmlspecialchars($currentUser['name']); ?>",
                            "email": "<?php echo htmlspecialchars($currentUser['email']); ?>"
                        },
                        "theme": {
                            "color": "#00b7ff"
                        },
                        "handler": function (response) {
                            var form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '/pages/payment_callback.php?gateway=razorpay';
                            
                            var fields = {
                                'razorpay_payment_id': response.razorpay_payment_id,
                                'razorpay_order_id': response.razorpay_order_id,
                                'razorpay_signature': response.razorpay_signature,
                                'plan_id': '<?php echo $planId; ?>'
                            };
                            
                            for (var key in fields) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = key;
                                input.value = fields[key];
                                form.appendChild(input);
                            }
                            
                            document.body.appendChild(form);
                            form.submit();
                        }
                    };
                    
                    var rzp = new Razorpay(options);
                    
                    document.getElementById('razorpay-btn').onclick = function(e) {
                        rzp.open();
                        e.preventDefault();
                    };
                </script>
                
            <?php elseif ($gateway === 'cashfree'): ?>
                <!-- Cashfree Checkout -->
                <button id="cashfree-btn" class="pay-button">
                    <i class="mdi mdi-lock"></i> Pay ₹<?php echo number_format($planDetails['amount_inr']); ?>
                </button>
                
                <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
                <script>
                    var cashfree = Cashfree({
                        mode: "<?php echo getCashfreeCredentials()['test_mode'] ? 'sandbox' : 'production'; ?>"
                    });
                    
                    document.getElementById('cashfree-btn').onclick = function(e) {
                        e.preventDefault();
                        
                        var checkoutOptions = {
                            paymentSessionId: "<?php echo htmlspecialchars($cashfreeToken ?? ''); ?>",
                            returnUrl: "https://<?php echo $_SERVER['HTTP_HOST']; ?>/index.php?q=payment_callback&gateway=cashfree&order_id=<?php echo urlencode($paymentOrderId); ?>"
                        };
                        
                        cashfree.checkout(checkoutOptions).then(function(result) {
                            if (result.error) {
                                alert('Payment error: ' + result.error.message);
                            }
                        }).catch(function(err) {
                            alert('Payment failed: ' + err.message);
                        });
                    };
                </script>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="secure-badge">
            <i class="mdi mdi-shield-check"></i> Secure SSL Encrypted Transaction
        </div>
    </div>
</div>

<script>
function selectGateway(gateway) {
    document.querySelectorAll('.gateway-option').forEach(function(el) {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    document.querySelector('input[name="gateway"][value="' + gateway + '"]').checked = true;
}
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
