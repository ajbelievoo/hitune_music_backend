<?php
/**
 * HiTune Music Distribution - Payouts Page
 * Payout request form and history.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
requireLogin();

$user   = getCurrentUser();
$userId = $user['id'];

// Read min_payout_threshold from settings table
$minPayoutThreshold = 500.0;
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'min_payout_threshold' LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $minPayoutThreshold = (float) $row['setting_value'];
    }
}

// Compute available balance: NET royalties (own remainder + incoming split
// shares from collaborations) minus approved payouts
require_once __DIR__ . '/../includes/splits.php';
$totalRoyalties = user_net_royalties($conn, $userId);

// Pending + approved payouts both lock the balance (prevent double requests)
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as withdrawn FROM payouts WHERE user_id = ? AND status IN ('pending', 'approved')");
$stmt->bind_param("i", $userId);
$stmt->execute();
$withdrawn = (float) ($stmt->get_result()->fetch_assoc()['withdrawn'] ?? 0);

// Referral credit is withdrawable alongside royalties (referral program, doc §8)
$referralCredit = 0;
$stmt = $conn->prepare("SELECT COALESCE(referral_credit, 0) AS rc FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $referralCredit = (float) $row['rc'];
}

$availableBalance = $totalRoyalties + $referralCredit - $withdrawn;
if ($availableBalance < 0) $availableBalance = 0;

// Handle POST: payout request
$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $amount         = (float) ($_POST['amount'] ?? 0);
    $paymentMethod  = trim($_POST['payment_method'] ?? '');
    $accountDetails = trim($_POST['account_details'] ?? '');

    $allowedMethods = ['bank_transfer', 'upi', 'paypal'];

    if ($amount < $minPayoutThreshold) {
        $errorMessage = 'Minimum payout amount is ₹' . number_format($minPayoutThreshold, 2) . '.';
    } elseif ($amount > $availableBalance) {
        $errorMessage = 'Insufficient balance. Your available balance is ₹' . number_format($availableBalance, 2) . '.';
    } elseif (!in_array($paymentMethod, $allowedMethods, true)) {
        $errorMessage = 'Please select a valid payment method.';
    } elseif (empty($accountDetails)) {
        $errorMessage = 'Please provide your account details.';
    } else {
        $stmt = $conn->prepare("INSERT INTO payouts (user_id, amount, payment_method, account_details, status, requested_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("idss", $userId, $amount, $paymentMethod, $accountDetails);
        if ($stmt->execute()) {
            // Consume referral credit beyond what royalties alone cover
            $royaltyBacked = max(0, $totalRoyalties - $withdrawn);
            $fromCredit = min($referralCredit, max(0, $amount - $royaltyBacked));
            if ($fromCredit > 0) {
                $conn->query("UPDATE users SET referral_credit = referral_credit - {$fromCredit} WHERE id = " . (int)$userId);
                $referralCredit -= $fromCredit;
            }
            $successMessage = 'Your payout request of ₹' . number_format($amount, 2) . ' has been submitted successfully. We will process it within 3–5 business days.';
            $withdrawn       += $amount;
            $availableBalance = $totalRoyalties + $referralCredit - $withdrawn;
            if ($availableBalance < 0) $availableBalance = 0;
        } else {
            $errorMessage = 'Something went wrong. Please try again.';
        }
    }
}

// Fetch payout history
$stmt = $conn->prepare("SELECT * FROM payouts WHERE user_id = ? ORDER BY requested_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$payoutsResult = $stmt->get_result();

$pageTitle       = 'Payouts - HiTune Music Distribution';
$metaDescription = 'Request and track your royalty payouts on HiTune Music Distribution.';
$canonicalUrl    = 'https://web.hitune.in/index.php?q=payouts';
$path            = 'payouts';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .payouts-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .payouts-container::before {
        content: '';
        position: fixed;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(0,183,255,0.1) 0%, transparent 60%);
        top: -200px;
        right: -200px;
        animation: bgGlow 8s ease-in-out infinite;
        z-index: -1;
        pointer-events: none;
    }
    @keyframes bgGlow {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    .payouts-header { margin-bottom: 40px; }
    .payouts-header h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .payouts-header p { color: rgba(255,255,255,0.6); font-size: 16px; }

    /* Balance card */
    .balance-card {
        background: linear-gradient(135deg, rgba(0,183,255,0.12), rgba(139,92,246,0.08));
        border: 1px solid rgba(0,183,255,0.3);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        margin-bottom: 35px;
    }
    .balance-card .bal-label {
        font-size: 13px;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    .balance-card .bal-amount {
        font-size: 52px;
        font-weight: 800;
        color: #38ef7d;
        margin-bottom: 8px;
    }
    .balance-card .bal-sub {
        display: flex;
        justify-content: center;
        gap: 30px;
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    .balance-card .bal-sub span strong {
        display: block;
        font-size: 17px;
        color: #fff;
    }

    /* Two-column layout */
    .payout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    @media (max-width: 900px) { .payout-grid { grid-template-columns: 1fr; } }

    /* Section cards */
    .section-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 30px;
    }
    .section-card h2 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Form */
    .form-group { margin-bottom: 20px; }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255,255,255,0.7);
        margin-bottom: 8px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 13px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        color: #fff;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.3s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #00b7ff;
        background: rgba(255,255,255,0.08);
    }
    .form-group select option { background: #1a1a2e; }
    .form-note { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 5px; }

    .btn-submit {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,183,255,0.4);
    }

    /* History table */
    .history-table { width: 100%; border-collapse: collapse; }
    .history-table th {
        padding: 12px 15px;
        text-align: left;
        font-size: 11px;
        color: rgba(255,255,255,0.4);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .history-table td {
        padding: 14px 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 13px;
    }
    .history-table tr:last-child td { border-bottom: none; }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-pending  { background: rgba(255,193,7,0.2);  color: #ffc107; }
    .status-approved { background: rgba(0,200,83,0.2);   color: #00c853; }
    .status-rejected { background: rgba(255,82,82,0.2);  color: #ff5252; }

    .empty-state { text-align: center; padding: 40px 20px; }
    .empty-state i { font-size: 40px; color: rgba(255,255,255,0.2); display: block; margin-bottom: 12px; }
    .empty-state p { color: rgba(255,255,255,0.4); font-size: 13px; }
</style>

<div class="payouts-container">
    <div class="payouts-header">
        <h1>Payouts</h1>
        <p>Request and track your royalty withdrawals</p>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success" style="margin-bottom: 25px;">
        <span class="mdi mdi-check-circle"></span>
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    <div class="alert alert-error" style="margin-bottom: 25px;">
        <span class="mdi mdi-alert-circle"></span>
        <?php echo htmlspecialchars($errorMessage); ?>
    </div>
    <?php endif; ?>

    <!-- Balance Card -->
    <div class="balance-card">
        <div class="bal-label">Available Balance</div>
        <div class="bal-amount">₹<?php echo number_format($availableBalance, 2); ?></div>
        <div class="bal-sub">
            <span><strong>₹<?php echo number_format($totalRoyalties, 2); ?></strong>Total Earnings</span>
            <span><strong>₹<?php echo number_format($referralCredit, 2); ?></strong>Referral Credit</span>
            <span><strong>₹<?php echo number_format($withdrawn, 2); ?></strong>Total Withdrawn</span>
            <span><strong>₹<?php echo number_format($minPayoutThreshold, 2); ?></strong>Min. Payout</span>
        </div>
    </div>

    <div class="payout-grid">
        <!-- Payout Request Form -->
        <div class="section-card">
            <h2><span class="mdi mdi-cash-multiple" style="color: #00b7ff;"></span> Request Payout</h2>

            <?php if ($availableBalance < $minPayoutThreshold): ?>
            <div class="alert alert-warning">
                <span class="mdi mdi-information-outline"></span>
                You need at least ₹<?php echo number_format($minPayoutThreshold, 2); ?> to request a payout. Keep earning from your music!
            </div>
            <?php else: ?>
            <form method="POST" action="">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="amount">Amount (₹)</label>
                    <input type="number" id="amount" name="amount" step="0.01"
                           min="<?php echo $minPayoutThreshold; ?>"
                           max="<?php echo $availableBalance; ?>"
                           placeholder="Enter amount"
                           value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
                           required>
                    <p class="form-note">Min: ₹<?php echo number_format($minPayoutThreshold, 2); ?> | Available: ₹<?php echo number_format($availableBalance, 2); ?></p>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select method...</option>
                        <option value="bank_transfer" <?php echo (($_POST['payment_method'] ?? '') === 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="upi" <?php echo (($_POST['payment_method'] ?? '') === 'upi') ? 'selected' : ''; ?>>UPI</option>
                        <option value="paypal" <?php echo (($_POST['payment_method'] ?? '') === 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="account_details">Account Details</label>
                    <textarea id="account_details" name="account_details" rows="3"
                              placeholder="Bank account number / UPI ID / PayPal email..."
                              required><?php echo htmlspecialchars($_POST['account_details'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <span class="mdi mdi-send"></span> Submit Payout Request
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Payout History -->
        <div class="section-card">
            <h2><span class="mdi mdi-history" style="color: #4facfe;"></span> Payout History</h2>

            <?php if ($payoutsResult->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payout = $payoutsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($payout['requested_at'])); ?></td>
                            <td style="font-weight: 700; color: #38ef7d;">₹<?php echo number_format((float)$payout['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $payout['payment_method']))); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($payout['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($payout['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="mdi mdi-cash-remove"></i>
                <p>No payout history yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
