<?php
/**
 * HiTune Music Distribution - Dashboard Page
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = $_SESSION['user_id'] ?? 0;

try {
// Fetch user's releases from database
$userReleases = [];
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT * FROM releases WHERE user_id = ? AND status IN ('submitted', 'in_progress', 'ready', 'live', 'takedown_requested', 'taken_down') ORDER BY created_at DESC LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $userReleases[] = $row;
        }
    }
}

// Fetch user's subscription data
$subscription = null;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $subResult = $stmt->get_result();
        $subscription = $subResult->fetch_assoc();
    }
}

// Fetch user's royalty earnings and streams from track_royalties
// Earnings are NET of royalty splits: own releases keep (100 - split%),
// plus shares credited to this user as a collaborator (doc §1).
require_once __DIR__ . '/../includes/splits.php';
$royaltyStats = ['total_earnings' => 0, 'total_plays' => 0];
if ($userId > 0) {
    $royaltyStats['total_earnings'] = user_net_royalties($conn, $userId);
    $stmt = $conn->prepare("SELECT
        COALESCE(SUM(tr.plays), 0) as total_plays
        FROM track_royalties tr
        JOIN release_tracks rt ON tr.track_id = rt.id
        JOIN releases r ON rt.release_id = r.id
        WHERE r.user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $royaltyResult = $stmt->get_result();
        $royaltyStats['total_plays'] = $royaltyResult->fetch_assoc()['total_plays'] ?? 0;
    }
}

// Compute available balance: total royalties minus approved payouts
$withdrawn = 0;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as withdrawn FROM payouts WHERE user_id = ? AND status = 'approved'");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $withdrawnResult = $stmt->get_result();
        $withdrawn = $withdrawnResult->fetch_assoc()['withdrawn'] ?? 0;
    }
}
$lifetimeStreams   = $royaltyStats['total_plays'] ?? 0;

// Referral program (§8): personal link + earned credit + referral count
$referralCode = null; $referralCredit = 0; $referralCount = 0;
if ($userId > 0 && file_exists(__DIR__ . '/../includes/referral.php')) {
    require_once __DIR__ . '/../includes/referral.php';
    $referralCode = ref_code_for($conn, $userId);
    $r = $conn->query("SELECT referral_credit FROM users WHERE id = " . (int)$userId);
    if ($r && $r->num_rows) $referralCredit = (float)$r->fetch_assoc()['referral_credit'];
    $r = $conn->query("SELECT COUNT(*) c FROM referral_events WHERE referrer_id = " . (int)$userId . " AND event = 'signup'");
    if ($r) $referralCount = (int)$r->fetch_assoc()['c'];
}
$availableBalance = ($royaltyStats['total_earnings'] ?? 0) + $referralCredit - $withdrawn;

// Get current month earnings
$thisMonthEarnings = 0;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(tr.royalty_amount), 0) as month_earnings
        FROM track_royalties tr
        JOIN release_tracks rt ON tr.track_id = rt.id
        JOIN releases r ON rt.release_id = r.id
        WHERE r.user_id = ? AND MONTH(tr.created_at) = MONTH(CURRENT_DATE()) AND YEAR(tr.created_at) = YEAR(CURRENT_DATE())");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $monthResult = $stmt->get_result();
        $thisMonthEarnings = $monthResult->fetch_assoc()['month_earnings'] ?? 0;
    }
}

// Get last month earnings for percentage calculation
$lastMonthEarnings = 0;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(tr.royalty_amount), 0) as month_earnings
        FROM track_royalties tr
        JOIN release_tracks rt ON tr.track_id = rt.id
        JOIN releases r ON rt.release_id = r.id
        WHERE r.user_id = ? AND MONTH(tr.created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(tr.created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $lastMonthResult = $stmt->get_result();
        $lastMonthEarnings = $lastMonthResult->fetch_assoc()['month_earnings'] ?? 0;
    }
}

// Calculate percentage change
$percentChange = 0;
if ($lastMonthEarnings > 0) {
    $percentChange = round((($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100);
}

// Active releases count
$activeReleasesCount = 0;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM releases WHERE user_id = ? AND status IN ('ready', 'live')");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $activeReleasesCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    }
}

// Read min_payout_threshold from settings
$minPayoutThreshold = 500;
$settingStmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'min_payout_threshold' LIMIT 1");
if ($settingStmt) {
    $settingStmt->execute();
    $settingRow = $settingStmt->get_result()->fetch_assoc();
    if ($settingRow) {
        $minPayoutThreshold = (float) $settingRow['setting_value'];
    }
}

// Monthly earnings chart data (last 6 months)
$monthlyData   = [];
$monthLabels   = [];
for ($i = 5; $i >= 0; $i--) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(tr.royalty_amount), 0) as earnings FROM track_royalties tr JOIN release_tracks rt ON tr.track_id = rt.id JOIN releases r ON rt.release_id = r.id WHERE r.user_id = ? AND YEAR(tr.created_at) = YEAR(DATE_SUB(NOW(), INTERVAL ? MONTH)) AND MONTH(tr.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL ? MONTH))");
    if ($stmt) {
        $stmt->bind_param("iii", $userId, $i, $i);
        $stmt->execute();
        $monthlyData[] = (float) ($stmt->get_result()->fetch_assoc()['earnings'] ?? 0);
    } else {
        $monthlyData[] = 0;
    }
    $monthLabels[] = date('M', strtotime("-$i months"));
}
$loadCharts = true;

// Format subscription data
$planName     = $subscription['plan_name'] ?? 'Free Plan';
$daysRemaining = 0;
$nextBilling  = 'N/A';
$artistSlots  = '0/0';

if ($subscription && !empty($subscription['end_date'])) {
    $endDate       = new DateTime($subscription['end_date']);
    $today         = new DateTime();
    $daysRemaining = max(0, $today->diff($endDate)->days);
    $nextBilling   = $endDate->format('M j, Y');
}

// Artist slots based on plan
$maxArtists = 1;
$__planFeatures = function_exists('getUserPlanFeatures') ? getUserPlanFeatures() : [];
if (!empty($__planFeatures['artist_profiles'])) {
    $maxArtists = ($__planFeatures['artist_profiles'] >= PHP_INT_MAX) ? 'Unlimited' : (int) $__planFeatures['artist_profiles'];
} elseif (stripos($planName, 'unlimited') !== false || stripos($planName, 'label') !== false || stripos($planName, 'professional') !== false) {
    $maxArtists = 'Unlimited';
} elseif (stripos($planName, 'artist pro') !== false || stripos($planName, 'breakout') !== false) {
    $maxArtists = 3;
}

// Count user's artist accounts
$artistCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as artist_count FROM artist_accounts WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $artistCount = $stmt->get_result()->fetch_assoc()['artist_count'] ?? 0;
}

if ($maxArtists === 'Unlimited') {
    $artistSlots = $artistCount . '/∞';
} else {
    $artistSlots = $artistCount . '/' . $maxArtists;
}

} catch (Exception $e) {
    // Non-fatal: tables may not exist yet; use safe defaults
    $userReleases        = $userReleases        ?? [];
    $subscription        = $subscription        ?? null;
    $availableBalance    = $availableBalance    ?? 0;
    $lifetimeStreams      = $lifetimeStreams      ?? 0;
    $thisMonthEarnings   = $thisMonthEarnings   ?? 0;
    $percentChange       = $percentChange       ?? 0;
    $activeReleasesCount = $activeReleasesCount ?? 0;
    $minPayoutThreshold  = $minPayoutThreshold  ?? 500;
    $monthlyData         = $monthlyData         ?? array_fill(0, 6, 0);
    $monthLabels         = $monthLabels         ?? [];
    $planName            = $planName            ?? 'Free Plan';
    $daysRemaining       = $daysRemaining       ?? 0;
    $nextBilling         = $nextBilling         ?? 'N/A';
    $artistSlots         = $artistSlots         ?? '0/0';
}

$pageTitle       = 'Dashboard - HiTune Music Distribution';
$metaDescription = 'Manage your music releases, track earnings, and monitor your distribution performance on HiTune.';
$canonicalUrl    = 'https://web.hitune.in/index.php?q=dashboard';
$path            = 'dashboard';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .dashboard {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    
    .dashboard::before {
        content: '';
        position: fixed;
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.15) 0%, transparent 60%);
        top: -300px;
        right: -300px;
        animation: bgGlow 8s ease-in-out infinite;
        z-index: -1;
        pointer-events: none;
    }
    
    @keyframes bgGlow {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
    }
    .dashboard-header h1 {
        font-size: 32px;
        font-weight: 800;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
    }
    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 30px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
    }
    
    .stat-card:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateY(-8px);
        border-color: rgba(0, 183, 255, 0.2);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    .stat-card-header span {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
    }
    .stat-card-header i {
        font-size: 24px;
        color: rgba(255, 255, 255, 0.3);
    }
    .stat-card .value {
        font-size: 40px;
        font-weight: 800;
        margin-bottom: 5px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stat-card .change {
        font-size: 14px;
        color: #00c853;
    }
    .stat-card .change.negative {
        color: #ff5252;
    }
    /* Balance Card Special */
    .balance-card {
        background: linear-gradient(135deg, rgba(0, 183, 255, 0.1), rgba(139, 92, 246, 0.05));
        border-color: rgba(0, 183, 255, 0.3);
    }
    
    .balance-card .value {
        background: linear-gradient(135deg, #00c853, #00e676);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .balance-card .withdraw-btn {
        margin-top: 15px;
        padding: 10px 20px;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .balance-card .withdraw-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: #fff;
    }
    /* Subscription Card */
    .subscription-card {
        background: linear-gradient(135deg, rgba(0, 183, 255, 0.1), rgba(255, 255, 255, 0.03));
        border: 1px solid rgba(0, 183, 255, 0.3);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .subscription-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .subscription-card-header h3 {
        font-size: 18px;
        font-weight: 700;
    }
    .plan-badge {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .subscription-info {
        display: flex;
        gap: 40px;
        margin-top: 20px;
    }
    .subscription-info div {
        font-size: 14px;
    }
    .subscription-info div strong {
        display: block;
        font-size: 20px;
        margin-top: 5px;
    }
    /* Artist Accounts */
    .section-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 25px;
    }
    .artist-accounts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .artist-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 25px;
        display: flex;
        gap: 20px;
        align-items: flex-start;
        transition: all 0.3s;
        cursor: pointer;
    }
    .artist-card:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateY(-5px);
    }
    .artist-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        flex-shrink: 0;
    }
    .artist-icon.spotify {
        background: #1db954;
    }
    .artist-icon.apple {
        background: #fa243c;
    }
    .artist-icon.youtube {
        background: #ff0000;
    }
    .artist-card h4 {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .artist-card p {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.5;
    }
    /* Releases Section */
    .releases-section {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .releases-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    .tabs {
        display: flex;
        gap: 5px;
        background: rgba(255, 255, 255, 0.05);
        padding: 5px;
        border-radius: 10px;
    }
    .tab {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.6);
    }
    .tab.active {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }
    .add-release-btn {
        padding: 12px 25px;
        background: linear-gradient(135deg, #00c853, #00e676);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    .add-release-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 200, 83, 0.3);
    }
    /* Release Items */
    .release-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .release-item {
        display: flex;
        gap: 20px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        transition: all 0.3s;
    }
    .release-item:hover {
        background: rgba(255, 255, 255, 0.06);
    }
    .release-cover {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #333, #555);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: rgba(255, 255, 255, 0.3);
        flex-shrink: 0;
    }
    .release-info {
        flex: 1;
    }
    .release-info h4 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .release-info p {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 10px;
    }
    .release-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-live {
        background: rgba(0, 200, 83, 0.2);
        color: #00c853;
    }
    .status-pending {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }
    .status-review {
        background: rgba(0, 154, 210, 0.2);
        color: rgb(0, 154, 210);
    }
</style>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
            <span>Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Artist'); ?>!</span>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 25px;">
        <span class="mdi mdi-check-circle"></span>
        <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 25px;">
        <span class="mdi mdi-alert-circle"></span>
        <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
    <?php endif; ?>

    <?php
    // Freemium quota + Creator Day (Admin → Settings → Freemium & Growth)
    $__quota = function_exists('freeReleaseQuota') ? freeReleaseQuota() : ['subscribed'=>true];
    $__cday  = function_exists('creatorDayInfo') ? creatorDayInfo() : ['active'=>false];
    if (!empty($__cday['active'])): ?>
    <div style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,rgba(255,176,32,.14),rgba(139,92,246,.14));border:1px solid rgba(255,176,32,.4);border-radius:14px;padding:16px 22px;margin-bottom:25px">
        <i class="mdi mdi-party-popper" style="font-size:30px;color:#ffb020;flex:none"></i>
        <div>
            <b style="font-size:15px">Creator Day is live!</b>
            <div style="color:rgba(255,255,255,.65);font-size:13px;margin-top:3px">Today artists keep <b style="color:#ffb020">100%</b> of tips &amp; royalties — share your HiTune Music profile now.</div>
        </div>
        <a href="/index.php?q=artist-panel" style="margin-left:auto;white-space:nowrap;color:#ffb020;font-weight:700;text-decoration:none;font-size:13px">Open Artist Panel →</a>
    </div>
    <?php endif; ?>

    <?php if (!$__quota['subscribed'] && !empty($__quota['enabled'])): ?>
    <div style="display:flex;align-items:center;gap:14px;background:rgba(0,183,255,.07);border:1px solid rgba(0,183,255,.25);border-radius:14px;padding:15px 22px;margin-bottom:25px">
        <i class="mdi mdi-gift-outline" style="font-size:26px;color:#00b7ff;flex:none"></i>
        <div style="flex:1">
            <b style="font-size:14px">Free plan — <?php echo (int)$__quota['remaining']; ?> of <?php echo (int)$__quota['limit']; ?> free releases left this month</b>
            <div style="height:6px;border-radius:5px;background:rgba(255,255,255,.08);margin-top:8px;overflow:hidden">
                <div style="height:100%;width:<?php echo $__quota['limit']>0 ? min(100, round($__quota['used']/$__quota['limit']*100)) : 0; ?>%;background:linear-gradient(90deg,#00b7ff,#8b5cf6);border-radius:5px"></div>
            </div>
        </div>
        <a href="/index.php?q=pricing" style="white-space:nowrap;color:#00b7ff;font-weight:700;text-decoration:none;font-size:13px">Go unlimited →</a>
    </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card balance-card">
            <div class="stat-card-header">
                <span>Available Balance</span>
                <i class="mdi mdi-wallet"></i>
            </div>
            <div class="value">₹<?php echo number_format($availableBalance, 2); ?></div>
            <div class="change">+ ₹<?php echo number_format($thisMonthEarnings, 2); ?> this month</div>
            <?php if ($availableBalance >= $minPayoutThreshold): ?>
            <a href="/index.php?q=payouts" class="withdraw-btn" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #fff; margin-top: 15px; padding: 10px 20px; background: transparent; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.3s;">
                <i class="mdi mdi-cash-multiple"></i> Withdraw Earnings
            </a>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <span>Lifetime Streams</span>
                <i class="mdi mdi-play-circle-outline"></i>
            </div>
            <div class="value"><?php echo number_format($lifetimeStreams); ?></div>
            <div class="change <?php echo $percentChange < 0 ? 'negative' : ''; ?>">
                <?php echo ($percentChange >= 0 ? '+' : '') . $percentChange; ?>% from last month
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <span>This Month's Earnings</span>
                <i class="mdi mdi-currency-inr"></i>
            </div>
            <div class="value">₹<?php echo number_format($thisMonthEarnings, 2); ?></div>
            <div class="change">Active releases: <?php echo $activeReleasesCount; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <span>Active Releases</span>
                <i class="mdi mdi-album"></i>
            </div>
            <div class="value"><?php echo $activeReleasesCount; ?></div>
            <div class="change">Live on platforms</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <span>Referral Credit</span>
                <i class="mdi mdi-account-multiple-plus"></i>
            </div>
            <div class="value">₹<?php echo number_format($referralCredit, 2); ?></div>
            <div class="change"><?php echo $referralCount; ?> artist(s) joined via your link</div>
        </div>
    </div>

    <?php if ($referralCode): ?>
    <!-- Referral Program -->
    <div class="subscription-card" style="margin-bottom:25px;">
        <div class="subscription-card-header">
            <h3><i class="mdi mdi-gift"></i> Referral Program — Invite Artists, Earn Credit</h3>
        </div>
        <div style="padding:15px 20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <input type="text" readonly id="refLink" value="https://distribution.hitune.in/index.php?q=signup&ref=<?php echo htmlspecialchars($referralCode); ?>"
                   style="flex:1; min-width:260px; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.15); color:#fff; padding:10px 14px; border-radius:8px; font-size:13px;">
            <button onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy Link',1500);"
                    style="background:#00b7ff; color:#001018; border:none; padding:10px 18px; border-radius:8px; font-weight:600; cursor:pointer;">Copy Link</button>
            <span style="color:rgba(255,255,255,.55); font-size:13px;">You earn credit when a referred artist's first release goes live.</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Subscription Card -->
    <?php if ($subscription): ?>
    <div class="subscription-card">
        <div class="subscription-card-header">
            <h3>Current Plan: <?php echo htmlspecialchars($planName); ?></h3>
            <span class="plan-badge">Active</span>
        </div>
        <div class="subscription-info">
            <div>
                Days Remaining
                <strong><?php echo $daysRemaining; ?> days</strong>
            </div>
            <div>
                Next Billing
                <strong><?php echo $nextBilling; ?></strong>
            </div>
            <div>
                Platforms
                <strong>100+</strong>
            </div>
            <div>
                Artist Slots
                <strong><?php echo $artistSlots; ?></strong>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="subscription-card" style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 15px;"><i class="mdi mdi-star-circle" style="color: #00b7ff;"></i></div>
        <h3 style="font-size: 22px; margin-bottom: 10px;">Upgrade Your Plan</h3>
        <p style="color: rgba(255,255,255,0.6); margin-bottom: 20px;">Get access to 150+ platforms, keep 100% of your royalties, and more.</p>
        <a href="/index.php?q=pricing" class="btn btn-primary" style="display: inline-flex;">
            <i class="mdi mdi-arrow-up-circle"></i> View Plans
        </a>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <h2 class="section-title">Quick Actions</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 40px;">
        <a href="/index.php?q=release-create" style="display: flex; align-items: center; gap: 12px; padding: 18px 22px; background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.3); border-radius: 14px; color: #00c853; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
            <i class="mdi mdi-plus-circle" style="font-size: 22px;"></i> New Release
        </a>
        <a href="/index.php?q=analytics" style="display: flex; align-items: center; gap: 12px; padding: 18px 22px; background: rgba(102,126,234,0.1); border: 1px solid rgba(102,126,234,0.3); border-radius: 14px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
            <i class="mdi mdi-chart-line" style="font-size: 22px;"></i> View Analytics
        </a>
        <a href="https://music.hitune.in/ai-studio" style="display: flex; align-items: center; gap: 12px; padding: 18px 22px; background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.35); border-radius: 14px; color: #a78bfa; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
            <i class="mdi mdi-auto-fix" style="font-size: 22px;"></i> AI Studio Tools
        </a>
        <a href="/index.php?q=payouts" style="display: flex; align-items: center; gap: 12px; padding: 18px 22px; background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.3); border-radius: 14px; color: #ffc107; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
            <i class="mdi mdi-cash-multiple" style="font-size: 22px;"></i> Request Payout
        </a>
        <a href="/index.php?q=artist-panel" style="display: flex; align-items: center; gap: 12px; padding: 18px 22px; background: rgba(0,183,255,0.1); border: 1px solid rgba(0,183,255,0.3); border-radius: 14px; color: #00b7ff; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s;">
            <i class="mdi mdi-account-music" style="font-size: 22px;"></i> Artist Panel
        </a>
    </div>

    <!-- Artist Accounts -->
    <h2 class="section-title">Your Artist Accounts</h2>
    <div class="artist-accounts">
        <div class="artist-card" onclick="location.href='/index.php?q=artist-panel'" style="cursor: pointer;">
            <div class="artist-icon" style="background: linear-gradient(135deg,#00b7ff,#8b5cf6); color:#fff;">
                <i class="mdi mdi-music-circle"></i>
            </div>
            <div>
                <h4>HiTune Artist Panel</h4>
                <p>Get verified on HiTune Music, see plays by country &amp; city, fan tips, releases and growth tools in one place.</p>
            </div>
        </div>
        <div class="artist-card" onclick="location.href='/index.php?q=artist-panel#verification'" style="cursor: pointer;">
            <div class="artist-icon spotify">
                <i class="mdi mdi-spotify"></i>
            </div>
            <div>
                <h4>Verify on Spotify</h4>
                <p>Link your Spotify artist profile — verified automatically when your HiTune release is found on it.</p>
            </div>
        </div>
        <div class="artist-card" onclick="location.href='/index.php?q=artist-panel#verification'" style="cursor: pointer;">
            <div class="artist-icon apple">
                <i class="mdi mdi-apple"></i>
            </div>
            <div>
                <h4>Verify on Apple Music</h4>
                <p>Link your Apple Music artist page so your catalogue and profile are confirmed as yours.</p>
            </div>
        </div>
        <div class="artist-card" onclick="location.href='/index.php?q=youtube_oac'" style="cursor: pointer;">
            <div class="artist-icon youtube">
                <i class="mdi mdi-youtube"></i>
            </div>
            <div>
                <h4>Official Artist Channel</h4>
                <p>All content &amp; subscribers from across your channels together in one place, plus Analytics for Artists.</p>
            </div>
        </div>
    </div>

    <!-- Recent Releases Section -->
    <div class="releases-section">
        <div class="releases-header">
            <h2 style="font-size: 20px; font-weight: 700;">Recent Releases</h2>
            <a href="/index.php?q=releases" class="add-release-btn" style="text-decoration: none;">
                <i class="mdi mdi-view-list"></i>
                View All
            </a>
        </div>

        <div class="release-list">
            <?php if (empty($userReleases)): ?>
            <div class="release-item" style="justify-content: center; text-align: center;">
                <div class="release-info">
                    <p style="color: rgba(255,255,255,0.6);">No releases yet. <a href="/index.php?q=release-create" style="color: #00b7ff;">Create your first release!</a></p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($userReleases as $release): ?>
            <div class="release-item" data-release-type="<?php echo htmlspecialchars($release['release_type']); ?>">
                <div class="release-cover">
                    <?php if (!empty($release['cover_art_path'])): ?>
                    <img src="<?php echo htmlspecialchars($release['cover_art_path']); ?>" alt="<?php echo htmlspecialchars($release['title']); ?> cover art" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;" loading="lazy">
                    <?php else: ?>
                    <i class="mdi mdi-music"></i>
                    <?php endif; ?>
                </div>
                <div class="release-info">
                    <h4><?php echo htmlspecialchars($release['title']); ?></h4>
                    <p><?php echo ucfirst($release['release_type']); ?> • <?php echo date('M j, Y', strtotime($release['release_date'] ?? $release['created_at'])); ?></p>
                    <?php if ($release['status'] === 'live' || $release['status'] === 'ready'): ?>
                    <span class="release-status status-live">
                        <i class="mdi mdi-check-circle"></i>
                        <?php echo $release['status'] === 'live' ? 'Live on platforms' : 'Ready'; ?>
                    </span>
                    <?php elseif ($release['status'] === 'submitted'): ?>
                    <span class="release-status status-pending">
                        <i class="mdi mdi-clock"></i>
                        Under Review
                    </span>
                    <?php elseif ($release['status'] === 'in_progress'): ?>
                    <span class="release-status status-review">
                        <i class="mdi mdi-progress-clock"></i>
                        In Progress
                    </span>
                    <?php elseif ($release['status'] === 'takedown_requested'): ?>
                    <span class="release-status status-pending">
                        <i class="mdi mdi-timer-off"></i>
                        Takedown Requested
                    </span>
                    <?php elseif ($release['status'] === 'taken_down'): ?>
                    <span class="release-status status-review">
                        <i class="mdi mdi-cancel"></i>
                        Taken Down
                    </span>
                    <?php endif; ?>
                </div>
                <a href="/index.php?q=release-view&id=<?php echo $release['id']; ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; text-decoration: none; font-size: 13px; font-weight: 600; white-space: nowrap; align-self: center;">
                    <i class="mdi mdi-eye"></i> View Details
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monthly Earnings Chart -->
    <div class="releases-section" style="margin-top: 30px;">
        <div class="releases-header">
            <h2 style="font-size: 20px; font-weight: 700;">Monthly Earnings (Last 6 Months)</h2>
        </div>
        <canvas id="earningsChart" height="100"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('earningsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [{
                label: 'Earnings (₹)',
                data: <?php echo json_encode($monthlyData); ?>,
                backgroundColor: 'rgba(0, 183, 255, 0.5)',
                borderColor: '#00b7ff',
                borderWidth: 2,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: 'rgba(255,255,255,0.7)' } }
            },
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: 'rgba(255,255,255,0.6)', callback: function(v) { return '₹' + v; } }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
