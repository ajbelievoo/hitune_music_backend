<?php
/**
 * HiTune Music Distribution - User Payment Methods Management
 * Bank Account & PayPal Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$user_id = $user['id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment_method'])) {
        $method_type = $_POST['method_type'] ?? '';
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if ($method_type === 'bank_transfer') {
            // Bank transfer validation
            $bank_account_name = trim($_POST['bank_account_name'] ?? '');
            $bank_name = trim($_POST['bank_name'] ?? '');
            $bank_account_number = trim($_POST['bank_account_number'] ?? '');
            $bank_ifsc_code = trim($_POST['bank_ifsc_code'] ?? '');
            $bank_branch = trim($_POST['bank_branch'] ?? '');
            $bank_country = trim($_POST['bank_country'] ?? 'India');
            
            if (empty($bank_account_name) || empty($bank_name) || empty($bank_account_number) || empty($bank_ifsc_code)) {
                $error_message = 'Please fill all required bank details.';
            } else {
                // If setting as default, unset other defaults
                if ($is_default) {
                    $conn->query("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = $user_id");
                }
                
                $stmt = $conn->prepare("INSERT INTO user_payment_methods 
                    (user_id, method_type, is_default, bank_account_name, bank_name, bank_account_number, 
                    bank_ifsc_code, bank_branch, bank_country) 
                    VALUES (?, 'bank_transfer', ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $user_id, $is_default, $bank_account_name, $bank_name, 
                    $bank_account_number, $bank_ifsc_code, $bank_branch, $bank_country);
                
                if ($stmt->execute()) {
                    $success_message = 'Bank account added successfully!';
                } else {
                    $error_message = 'Error adding bank account. Please try again.';
                }
            }
        } elseif ($method_type === 'paypal') {
            // PayPal validation
            $paypal_email = trim($_POST['paypal_email'] ?? '');
            
            if (empty($paypal_email) || !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid PayPal email address.';
            } else {
                // If setting as default, unset other defaults
                if ($is_default) {
                    $conn->query("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = $user_id");
                }
                
                $stmt = $conn->prepare("INSERT INTO user_payment_methods 
                    (user_id, method_type, is_default, paypal_email) 
                    VALUES (?, 'paypal', ?, ?)");
                $stmt->bind_param("iss", $user_id, $is_default, $paypal_email);
                
                if ($stmt->execute()) {
                    $success_message = 'PayPal account added successfully!';
                } else {
                    $error_message = 'Error adding PayPal account. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['delete_method'])) {
        $method_id = intval($_POST['method_id'] ?? 0);
        
        // Verify the method belongs to this user
        $stmt = $conn->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $method_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success_message = 'Payment method removed successfully!';
        } else {
            $error_message = 'Error removing payment method.';
        }
    } elseif (isset($_POST['set_default'])) {
        $method_id = intval($_POST['method_id'] ?? 0);
        
        // Unset all defaults first
        $conn->query("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = $user_id");
        
        // Set new default
        $stmt = $conn->prepare("UPDATE user_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $method_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'Default payment method updated!';
        } else {
            $error_message = 'Error updating default payment method.';
        }
    }
}

// Get user's payment methods
$payment_methods = $conn->query("SELECT * FROM user_payment_methods WHERE user_id = $user_id AND is_active = 1 ORDER BY is_default DESC, created_at DESC");

// Get withdrawal settings
$settings_result = $conn->query("SELECT * FROM withdrawal_settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Payment Methods - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .payment-methods-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .page-header {
        margin-bottom: 40px;
    }
    .page-header h1 {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }
    .page-header p {
        color: rgba(255,255,255,0.6);
        font-size: 16px;
    }
    
    /* Alert Messages */
    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .alert-success {
        background: rgba(0, 200, 83, 0.15);
        border: 1px solid #00c853;
        color: #00c853;
    }
    .alert-error {
        background: rgba(255, 82, 82, 0.15);
        border: 1px solid #ff5252;
        color: #ff5252;
    }
    
    /* Payment Methods Grid */
    .methods-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    .method-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 30px;
        transition: all 0.3s;
        position: relative;
    }
    .method-card:hover {
        background: rgba(255,255,255,0.05);
        border-color: rgba(0, 183, 255, 0.3);
    }
    .method-card.default {
        border-color: #00c853;
        background: rgba(0, 200, 83, 0.05);
    }
    .default-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #00c853;
        color: #000;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .method-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 20px;
    }
    .method-icon.bank {
        background: rgba(79, 172, 254, 0.2);
        color: #4facfe;
    }
    .method-icon.paypal {
        background: rgba(0, 112, 186, 0.2);
        color: #0070ba;
    }
    .method-card h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .method-details {
        color: rgba(255,255,255,0.7);
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 20px;
    }
    .method-details strong {
        color: #fff;
    }
    .masked-account {
        font-family: monospace;
        letter-spacing: 2px;
    }
    .method-actions {
        display: flex;
        gap: 10px;
    }
    .btn-method {
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
    }
    .btn-set-default {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    .btn-set-default:hover {
        background: rgba(0, 200, 83, 0.2);
        color: #00c853;
    }
    .btn-delete {
        background: rgba(255, 82, 82, 0.2);
        color: #ff5252;
    }
    .btn-delete:hover {
        background: rgba(255, 82, 82, 0.3);
    }
    
    /* Add New Section */
    .add-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 30px;
    }
    .add-section h2 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .method-tabs {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
    }
    .method-tab {
        padding: 15px 25px;
        background: rgba(255,255,255,0.05);
        border: 2px solid transparent;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 600;
        color: rgba(255,255,255,0.7);
        transition: all 0.3s;
    }
    .method-tab:hover {
        background: rgba(255,255,255,0.08);
    }
    .method-tab.active {
        border-color: #00b7ff;
        background: rgba(0, 183, 255, 0.1);
        color: #00b7ff;
    }
    .method-tab i {
        font-size: 22px;
    }
    
    /* Form Styles */
    .method-form {
        display: none;
    }
    .method-form.active {
        display: block;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 600px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255,255,255,0.8);
        margin-bottom: 8px;
    }
    .form-group label .required {
        color: #00b7ff;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 14px 18px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: #fff;
        font-size: 14px;
        transition: all 0.3s;
    }
    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: #00b7ff;
        background: rgba(255,255,255,0.08);
    }
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #00b7ff;
    }
    .checkbox-group span {
        font-size: 14px;
        color: rgba(255,255,255,0.8);
    }
    .btn-submit {
        padding: 15px 40px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border: none;
        border-radius: 12px;
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.4);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 64px;
        color: rgba(255,255,255,0.2);
        margin-bottom: 20px;
    }
    .empty-state h3 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    .empty-state p {
        color: rgba(255,255,255,0.5);
    }
    
    /* Info Box */
    .info-box {
        background: rgba(79, 172, 254, 0.1);
        border: 1px solid rgba(79, 172, 254, 0.2);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    .info-box i {
        font-size: 24px;
        color: #4facfe;
    }
    .info-box p {
        font-size: 14px;
        color: rgba(255,255,255,0.8);
        line-height: 1.6;
    }
    .info-box strong {
        color: #fff;
    }
</style>

<div class="payment-methods-container">
    <div class="page-header">
        <h1>Payment Methods</h1>
        <p>Manage your bank accounts and PayPal for royalty withdrawals</p>
    </div>
    
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <span class="mdi mdi-check-circle"></span>
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-error">
        <span class="mdi mdi-alert-circle"></span>
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="info-box">
        <span class="mdi mdi-information-outline"></span>
        <p>
            <strong>Minimum Withdrawal:</strong> $<?php echo $settings['min_withdrawal_amount'] ?? '1.00'; ?> | 
            <strong>Processing Time:</strong> <?php echo $settings['processing_time_days'] ?? '3-5'; ?> business days.<br>
            Ensure your payment details are correct to avoid delays.
        </p>
    </div>
    
    <!-- Existing Payment Methods -->
    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">
        <span class="mdi mdi-credit-card-multiple"></span> Your Payment Methods
    </h2>
    
    <?php if ($payment_methods->num_rows > 0): ?>
    <div class="methods-grid">
        <?php while ($method = $payment_methods->fetch_assoc()): ?>
        <div class="method-card <?php echo $method['is_default'] ? 'default' : ''; ?>">
            <?php if ($method['is_default']): ?>
            <span class="default-badge">Default</span>
            <?php endif; ?>
            
            <div class="method-icon <?php echo $method['method_type']; ?>">
                <span class="mdi mdi-<?php echo $method['method_type'] === 'bank_transfer' ? 'bank' : 'paypal'; ?>"></span>
            </div>
            
            <h3><?php echo $method['method_type'] === 'bank_transfer' ? 'Bank Account' : 'PayPal'; ?></h3>
            
            <div class="method-details">
                <?php if ($method['method_type'] === 'bank_transfer'): ?>
                    <strong><?php echo htmlspecialchars($method['bank_account_name']); ?></strong><br>
                    <?php echo htmlspecialchars($method['bank_name']); ?><br>
                    <span class="masked-account">****<?php echo substr($method['bank_account_number'], -4); ?></span><br>
                    IFSC: <?php echo htmlspecialchars($method['bank_ifsc_code']); ?>
                <?php else: ?>
                    <strong><?php echo htmlspecialchars($method['paypal_email']); ?></strong><br>
                    PayPal Account
                <?php endif; ?>
            </div>
            
            <div class="method-actions">
                <?php if (!$method['is_default']): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                    <button type="submit" name="set_default" class="btn-method btn-set-default">
                        <span class="mdi mdi-check-circle"></span> Set Default
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this payment method?');">
                    <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                    <button type="submit" name="delete_method" class="btn-method btn-delete">
                        <span class="mdi mdi-delete"></span> Remove
                    </button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state" style="background: rgba(255,255,255,0.03); border-radius: 20px; margin-bottom: 30px;">
        <span class="mdi mdi-credit-card-off-outline"></span>
        <h3>No Payment Methods</h3>
        <p>Add a bank account or PayPal to receive your royalty payouts.</p>
    </div>
    <?php endif; ?>
    
    <!-- Add New Payment Method -->
    <div class="add-section">
        <h2><span class="mdi mdi-plus-circle"></span> Add Payment Method</h2>
        
        <div class="method-tabs">
            <div class="method-tab active" onclick="switchTab('bank')">
                <span class="mdi mdi-bank"></span> Bank Account
            </div>
            <div class="method-tab" onclick="switchTab('paypal')">
                <span class="mdi mdi-paypal"></span> PayPal
            </div>
        </div>
        
        <!-- Bank Transfer Form -->
        <form method="POST" class="method-form active" id="bank-form">
            <input type="hidden" name="method_type" value="bank_transfer">
            <div class="form-grid">
                <div class="form-group">
                    <label>Account Holder Name <span class="required">*</span></label>
                    <input type="text" name="bank_account_name" placeholder="Full name as per bank" required>
                </div>
                <div class="form-group">
                    <label>Bank Name <span class="required">*</span></label>
                    <input type="text" name="bank_name" placeholder="e.g., State Bank of India" required>
                </div>
                <div class="form-group">
                    <label>Account Number <span class="required">*</span></label>
                    <input type="text" name="bank_account_number" placeholder="Enter account number" required>
                </div>
                <div class="form-group">
                    <label>IFSC Code <span class="required">*</span></label>
                    <input type="text" name="bank_ifsc_code" placeholder="e.g., SBIN0001234" required>
                </div>
                <div class="form-group">
                    <label>Branch Name</label>
                    <input type="text" name="bank_branch" placeholder="Branch location">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <select name="bank_country">
                        <option value="India" selected>India</option>
                        <option value="USA">USA</option>
                        <option value="UK">UK</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="checkbox-group">
                        <input type="checkbox" name="is_default" checked>
                        <span>Set as default payment method</span>
                    </label>
                </div>
                <div class="form-group full-width">
                    <button type="submit" name="add_payment_method" class="btn-submit">
                        <span class="mdi mdi-bank-plus"></span> Add Bank Account
                    </button>
                </div>
            </div>
        </form>
        
        <!-- PayPal Form -->
        <form method="POST" class="method-form" id="paypal-form">
            <input type="hidden" name="method_type" value="paypal">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>PayPal Email <span class="required">*</span></label>
                    <input type="email" name="paypal_email" placeholder="your.email@example.com" required>
                </div>
                <div class="form-group full-width">
                    <label class="checkbox-group">
                        <input type="checkbox" name="is_default" checked>
                        <span>Set as default payment method</span>
                    </label>
                </div>
                <div class="form-group full-width">
                    <button type="submit" name="add_payment_method" class="btn-submit">
                        <span class="mdi mdi-paypal"></span> Add PayPal Account
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(type) {
    // Update tabs
    document.querySelectorAll('.method-tab').forEach(tab => tab.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    // Update forms
    document.querySelectorAll('.method-form').forEach(form => form.classList.remove('active'));
    if (type === 'bank') {
        document.getElementById('bank-form').classList.add('active');
    } else {
        document.getElementById('paypal-form').classList.add('active');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
