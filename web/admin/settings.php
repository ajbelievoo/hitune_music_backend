<?php
/**
 * web.hitune.in Admin Panel - Settings (Tabbed)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/csrf.php';

session_start();
requireAdmin();

$success = '';
$error   = '';

// Helper: get a single setting value
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    if (!$stmt) return $default;
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Helper: upsert a setting
function updateSetting($conn, $key, $value, $group = 'general') {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, setting_group = ?");
    if (!$stmt) return false;
    $stmt->bind_param("sssss", $key, $value, $group, $value, $group);
    return $stmt->execute();
}

// Handle POST saves
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $tab = $_POST['active_tab'] ?? 'branding';

    try {
        if ($tab === 'branding') {
            updateSetting($conn, 'site_title',        $_POST['site_title']        ?? '', 'general');
            updateSetting($conn, 'site_tagline',      $_POST['site_tagline']      ?? '', 'general');
            updateSetting($conn, 'support_email',     $_POST['support_email']     ?? '', 'general');
            updateSetting($conn, 'site_url',          $_POST['site_url']          ?? '', 'general');
            updateSetting($conn, 'logo_url',          $_POST['logo_url']          ?? '', 'general');
            updateSetting($conn, 'favicon_url',       $_POST['favicon_url']       ?? '', 'general');
            updateSetting($conn, 'footer_copyright',  $_POST['footer_copyright']  ?? '', 'general');
            $success = 'Branding settings saved.';

        } elseif ($tab === 'seo') {
            updateSetting($conn, 'seo_default_title',       $_POST['seo_default_title']       ?? '', 'seo');
            updateSetting($conn, 'seo_default_description', $_POST['seo_default_description'] ?? '', 'seo');
            updateSetting($conn, 'seo_default_keywords',    $_POST['seo_default_keywords']    ?? '', 'seo');
            updateSetting($conn, 'og_image_url',            $_POST['og_image_url']            ?? '', 'seo');
            updateSetting($conn, 'google_analytics_id',     $_POST['google_analytics_id']     ?? '', 'seo');
            updateSetting($conn, 'google_search_console',   $_POST['google_search_console']   ?? '', 'seo');
            updateSetting($conn, 'robots_txt_content',      $_POST['robots_txt_content']      ?? '', 'seo');
            $success = 'SEO settings saved.';

        } elseif ($tab === 'oauth') {
            updateSetting($conn, 'google_client_id',     $_POST['google_client_id']     ?? '', 'oauth');
            updateSetting($conn, 'google_client_secret', $_POST['google_client_secret'] ?? '', 'oauth');
            updateSetting($conn, 'google_login_enabled', isset($_POST['google_login_enabled']) ? '1' : '0', 'oauth');
            $success = 'Google OAuth settings saved.';

        } elseif ($tab === 'payment') {
            updateSetting($conn, 'razorpay_enabled',    isset($_POST['razorpay_enabled'])    ? '1' : '0', 'payment');
            updateSetting($conn, 'razorpay_test_mode',  isset($_POST['razorpay_test_mode'])  ? '1' : '0', 'payment');
            updateSetting($conn, 'razorpay_key_id',     $_POST['razorpay_key_id']            ?? '',        'payment');
            updateSetting($conn, 'razorpay_key_secret', $_POST['razorpay_key_secret']        ?? '',        'payment');
            updateSetting($conn, 'cashfree_enabled',    isset($_POST['cashfree_enabled'])    ? '1' : '0', 'payment');
            updateSetting($conn, 'cashfree_test_mode',  isset($_POST['cashfree_test_mode'])  ? '1' : '0', 'payment');
            updateSetting($conn, 'cashfree_app_id',     $_POST['cashfree_app_id']            ?? '',        'payment');
            updateSetting($conn, 'cashfree_secret_key', $_POST['cashfree_secret_key']        ?? '',        'payment');
            updateSetting($conn, 'min_payout_threshold',$_POST['min_payout_threshold']       ?? '500',     'payment');
            updateSetting($conn, 'currency',            $_POST['currency']                   ?? 'INR',     'payment');
            $success = 'Payment settings saved.';

        } elseif ($tab === 'freemium') {
            updateSetting($conn, 'free_releases_enabled',   isset($_POST['free_releases_enabled']) ? '1' : '0', 'freemium');
            updateSetting($conn, 'free_releases_per_month', (string) max(0, (int)($_POST['free_releases_per_month'] ?? 2)), 'freemium');
            updateSetting($conn, 'creator_day_enabled',     isset($_POST['creator_day_enabled']) ? '1' : '0', 'freemium');
            updateSetting($conn, 'creator_day_date',        (string) min(28, max(1, (int)($_POST['creator_day_date'] ?? 1))), 'freemium');
            $success = 'Freemium & Creator Day settings saved.';

        } elseif ($tab === 'email') {
            updateSetting($conn, 'smtp_host',     $_POST['smtp_host']     ?? '', 'email');
            updateSetting($conn, 'smtp_port',     $_POST['smtp_port']     ?? '587', 'email');
            updateSetting($conn, 'smtp_username', $_POST['smtp_username'] ?? '', 'email');
            updateSetting($conn, 'smtp_password', $_POST['smtp_password'] ?? '', 'email');
            updateSetting($conn, 'from_email',    $_POST['from_email']    ?? '', 'email');
            updateSetting($conn, 'from_name',     $_POST['from_name']     ?? '', 'email');
            updateSetting($conn, 'smtp_ssl',      isset($_POST['smtp_ssl']) ? '1' : '0', 'email');
            $success = 'Email settings saved.';
        }
    } catch (Exception $e) {
        $error = 'Error saving settings: ' . htmlspecialchars($e->getMessage());
    }
}

// Load all current settings
$s = [];
$allKeys = [
    'site_title','site_tagline','support_email','site_url','logo_url','favicon_url','footer_copyright',
    'seo_default_title','seo_default_description','seo_default_keywords','og_image_url',
    'google_analytics_id','google_search_console','robots_txt_content',
    'google_client_id','google_client_secret','google_login_enabled',
    'razorpay_enabled','razorpay_test_mode','razorpay_key_id','razorpay_key_secret',
    'cashfree_enabled','cashfree_test_mode','cashfree_app_id','cashfree_secret_key',
    'min_payout_threshold','currency',
    'free_releases_enabled','free_releases_per_month','creator_day_enabled','creator_day_date',
    'smtp_host','smtp_port','smtp_username','smtp_password','from_email','from_name','smtp_ssl',
];
$defaults = [
    'site_title'             => 'HiTune Music Distribution',
    'site_tagline'           => 'Distribute Your Music Worldwide',
    'support_email'          => 'support@hitune.in',
    'site_url'               => 'https://web.hitune.in',
    'footer_copyright'       => '© 2025 HiTune Music Distribution',
    'og_image_url'           => '/assets/og-image.jpg',
    'google_login_enabled'   => '0',
    'razorpay_enabled'       => '1',
    'razorpay_test_mode'     => '1',
    'razorpay_key_id'        => 'rzp_test_YOUR_KEY',
    'cashfree_enabled'       => '1',
    'cashfree_test_mode'     => '1',
    'cashfree_app_id'        => 'YOUR_APP_ID',
    'min_payout_threshold'   => '500',
    'currency'               => 'INR',
    'free_releases_enabled'  => '1',
    'free_releases_per_month'=> '2',
    'creator_day_enabled'    => '1',
    'creator_day_date'       => '1',
    'smtp_port'              => '587',
    'from_email'             => 'noreply@hitune.in',
    'from_name'              => 'HiTune Music',
    'smtp_ssl'               => '1',
];
foreach ($allKeys as $k) {
    $s[$k] = getSetting($conn, $k, $defaults[$k] ?? '');
}

$activeTab = $_POST['active_tab'] ?? ($_GET['tab'] ?? 'branding');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0a0a0a; color: #fff; min-height: 100vh; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); border-right: 1px solid rgba(255,255,255,0.1); position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; }
        .sidebar-header { padding: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header .logo { width: 60px; height: 60px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 28px; }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .nav-menu { padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 15px 30px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: rgba(0,183,255,0.1); color: #00b7ff; border-left-color: #00b7ff; }
        .main-content { flex: 1; margin-left: 280px; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .page-header h1 { font-size: 28px; font-weight: 700; }
        /* Tabs */
        .tabs-nav { display: flex; gap: 5px; background: rgba(255,255,255,0.05); padding: 6px; border-radius: 14px; margin-bottom: 30px; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; border: none; background: transparent; color: rgba(255,255,255,0.6); border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; display: flex; align-items: center; gap: 7px; }
        .tab-btn.active { background: rgba(0,183,255,0.2); color: #00b7ff; }
        .tab-btn:hover:not(.active) { background: rgba(255,255,255,0.07); color: #fff; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        /* Form */
        .settings-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 25px; margin-bottom: 20px; max-width: 820px; }
        .settings-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.9); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: rgba(255,255,255,0.75); font-size: 13px; font-weight: 500; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="number"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; color: #fff; font-size: 14px; font-family: inherit; transition: border-color 0.3s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #00b7ff; }
        .form-group input[readonly] { opacity: 0.6; cursor: default; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .toggle-switch { display: flex; align-items: center; gap: 12px; }
        .toggle-switch input[type="checkbox"] { width: 52px; height: 28px; -webkit-appearance: none; appearance: none; background: rgba(255,255,255,0.1); border-radius: 28px; position: relative; cursor: pointer; outline: none; transition: all 0.3s; flex-shrink: 0; }
        .toggle-switch input[type="checkbox"]:checked { background: linear-gradient(135deg, #00b7ff, #8b5cf6); }
        .toggle-switch input[type="checkbox"]::before { content: ''; position: absolute; width: 22px; height: 22px; background: #fff; border-radius: 50%; top: 3px; left: 3px; transition: all 0.3s; }
        .toggle-switch input[type="checkbox"]:checked::before { left: 27px; }
        .btn-save { padding: 13px 35px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); border: none; border-radius: 10px; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: opacity 0.3s; }
        .btn-save:hover { opacity: 0.9; }
        .alert-success { background: rgba(56,239,125,0.15); border: 1px solid rgba(56,239,125,0.3); color: #38ef7d; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: rgba(255,82,82,0.15); border: 1px solid rgba(255,82,82,0.3); color: #ff5252; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .info-box { background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.25); border-radius: 10px; padding: 15px; font-size: 13px; color: rgba(255,255,255,0.75); line-height: 1.7; margin-bottom: 20px; }
        .info-box ol { padding-left: 18px; }
    </style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><span class="mdi mdi-shield-account"></span></div>
            <h2>Admin Panel</h2>
            <p>HiTune Distribution</p>
        </div>
        <nav class="nav-menu">
            <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
            <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
            <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
            <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
            <a href="manage_artists.php" class="nav-item"><span class="mdi mdi-account-music"></span>Manage Artists</a>
            <a href="user_artists.php" class="nav-item"><span class="mdi mdi-account-question"></span>Artist Requests</a>
            <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
            <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
            <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
            <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
            <a href="settings.php" class="nav-item active"><span class="mdi mdi-cog"></span>Settings</a>
            <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><span class="mdi mdi-cog"></span> Settings</h1>
        </div>

        <?php if ($success): ?>
            <div class="alert-success" style="max-width:820px;"><span class="mdi mdi-check-circle"></span><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error" style="max-width:820px;"><span class="mdi mdi-alert-circle"></span><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tabs-nav">
            <button class="tab-btn <?php echo $activeTab==='branding'?'active':''; ?>" onclick="switchTab('branding')"><span class="mdi mdi-palette"></span>Branding</button>
            <button class="tab-btn <?php echo $activeTab==='seo'?'active':''; ?>" onclick="switchTab('seo')"><span class="mdi mdi-magnify"></span>SEO</button>
            <button class="tab-btn <?php echo $activeTab==='oauth'?'active':''; ?>" onclick="switchTab('oauth')"><span class="mdi mdi-google"></span>Social Login</button>
            <button class="tab-btn <?php echo $activeTab==='payment'?'active':''; ?>" onclick="switchTab('payment')"><span class="mdi mdi-credit-card"></span>Payments</button>
            <button class="tab-btn <?php echo $activeTab==='freemium'?'active':''; ?>" onclick="switchTab('freemium')"><span class="mdi mdi-rocket-launch"></span>Freemium &amp; Growth</button>
            <button class="tab-btn <?php echo $activeTab==='email'?'active':''; ?>" onclick="switchTab('email')"><span class="mdi mdi-email"></span>Email</button>
        </div>

        <!-- TAB: Branding -->
        <div id="tab-branding" class="tab-panel <?php echo $activeTab==='branding'?'active':''; ?>">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="active_tab" value="branding">
                <div class="settings-card">
                    <h3><span class="mdi mdi-palette-outline"></span> Branding &amp; General</h3>
                    <div class="form-group"><label>Website Title</label><input type="text" name="site_title" value="<?php echo htmlspecialchars($s['site_title']); ?>"></div>
                    <div class="form-group"><label>Website Tagline</label><input type="text" name="site_tagline" value="<?php echo htmlspecialchars($s['site_tagline']); ?>"></div>
                    <div class="form-group"><label>Support Email</label><input type="email" name="support_email" value="<?php echo htmlspecialchars($s['support_email']); ?>"></div>
                    <div class="form-group"><label>Website URL</label><input type="url" name="site_url" value="<?php echo htmlspecialchars($s['site_url']); ?>"></div>
                    <div class="form-group"><label>Logo URL</label><input type="text" name="logo_url" value="<?php echo htmlspecialchars($s['logo_url']); ?>" placeholder="/assets/logo.png"></div>
                    <div class="form-group"><label>Favicon URL</label><input type="text" name="favicon_url" value="<?php echo htmlspecialchars($s['favicon_url']); ?>" placeholder="/assets/favicon.ico"></div>
                    <div class="form-group"><label>Footer Copyright Text</label><input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($s['footer_copyright']); ?>"></div>
                </div>
                <button type="submit" class="btn-save"><span class="mdi mdi-content-save"></span> Save Branding</button>
            </form>
        </div>

        <!-- TAB: SEO -->
        <div id="tab-seo" class="tab-panel <?php echo $activeTab==='seo'?'active':''; ?>">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="active_tab" value="seo">
                <div class="settings-card">
                    <h3><span class="mdi mdi-magnify"></span> SEO Settings</h3>
                    <div class="form-group"><label>Default Meta Title</label><input type="text" name="seo_default_title" value="<?php echo htmlspecialchars($s['seo_default_title']); ?>"></div>
                    <div class="form-group"><label>Default Meta Description</label><textarea name="seo_default_description"><?php echo htmlspecialchars($s['seo_default_description']); ?></textarea></div>
                    <div class="form-group"><label>Default Meta Keywords</label><input type="text" name="seo_default_keywords" value="<?php echo htmlspecialchars($s['seo_default_keywords']); ?>"></div>
                    <div class="form-group"><label>OG Image URL</label><input type="text" name="og_image_url" value="<?php echo htmlspecialchars($s['og_image_url']); ?>" placeholder="/assets/og-image.jpg"></div>
                    <div class="form-group"><label>Google Analytics ID</label><input type="text" name="google_analytics_id" value="<?php echo htmlspecialchars($s['google_analytics_id']); ?>" placeholder="G-XXXXXXXXXX"></div>
                    <div class="form-group"><label>Google Search Console Verification</label><input type="text" name="google_search_console" value="<?php echo htmlspecialchars($s['google_search_console']); ?>" placeholder="verification code"></div>
                    <div class="form-group"><label>Robots.txt Content</label><textarea name="robots_txt_content" style="min-height:140px;font-family:monospace;"><?php echo htmlspecialchars($s['robots_txt_content']); ?></textarea></div>
                </div>
                <button type="submit" class="btn-save"><span class="mdi mdi-content-save"></span> Save SEO Settings</button>
            </form>
        </div>

        <!-- TAB: Social Login (Google OAuth) -->
        <div id="tab-oauth" class="tab-panel <?php echo $activeTab==='oauth'?'active':''; ?>">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="active_tab" value="oauth">
                <div class="settings-card">
                    <h3><span class="mdi mdi-google"></span> Google OAuth</h3>
                    <div class="info-box">
                        <strong>Setup Instructions:</strong>
                        <ol>
                            <li>Go to <a href="https://console.cloud.google.com/" target="_blank" style="color:#00b7ff;">Google Cloud Console</a></li>
                            <li>Create OAuth 2.0 credentials under APIs &amp; Services</li>
                            <li>Add the callback URL below as an authorized redirect URI</li>
                            <li>Copy the Client ID and Secret here and save</li>
                        </ol>
                    </div>
                    <div class="form-group"><label>Google Client ID</label><input type="text" name="google_client_id" value="<?php echo htmlspecialchars($s['google_client_id']); ?>" placeholder="xxxx.apps.googleusercontent.com"></div>
                    <div class="form-group"><label>Google Client Secret</label><input type="password" name="google_client_secret" value="<?php echo htmlspecialchars($s['google_client_secret']); ?>"></div>
                    <div class="form-group"><label>OAuth Callback URL (read-only)</label><input type="text" readonly value="https://web.hitune.in/index.php?q=google_callback"></div>
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="google_login_enabled" <?php echo $s['google_login_enabled']==='1'?'checked':''; ?>>
                            <span>Enable Google Login</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-save"><span class="mdi mdi-content-save"></span> Save OAuth Settings</button>
            </form>
        </div>

        <!-- TAB: Payment Gateways -->
        <div id="tab-payment" class="tab-panel <?php echo $activeTab==='payment'?'active':''; ?>">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="active_tab" value="payment">
                <div class="settings-card">
                    <h3><span class="mdi mdi-credit-card"></span> Razorpay</h3>
                    <div class="form-group"><label class="toggle-switch"><input type="checkbox" name="razorpay_enabled" <?php echo $s['razorpay_enabled']==='1'?'checked':''; ?>><span>Enable Razorpay</span></label></div>
                    <div class="form-group"><label class="toggle-switch"><input type="checkbox" name="razorpay_test_mode" <?php echo $s['razorpay_test_mode']==='1'?'checked':''; ?>><span>Test Mode (Sandbox)</span></label></div>
                    <div class="form-group"><label>Key ID</label><input type="text" name="razorpay_key_id" value="<?php echo htmlspecialchars($s['razorpay_key_id']); ?>" placeholder="rzp_test_..."></div>
                    <div class="form-group"><label>Key Secret</label><input type="password" name="razorpay_key_secret" value="<?php echo htmlspecialchars($s['razorpay_key_secret']); ?>"></div>
                </div>
                <div class="settings-card">
                    <h3><span class="mdi mdi-wallet"></span> Cashfree</h3>
                    <div class="form-group"><label class="toggle-switch"><input type="checkbox" name="cashfree_enabled" <?php echo $s['cashfree_enabled']==='1'?'checked':''; ?>><span>Enable Cashfree</span></label></div>
                    <div class="form-group"><label class="toggle-switch"><input type="checkbox" name="cashfree_test_mode" <?php echo $s['cashfree_test_mode']==='1'?'checked':''; ?>><span>Test Mode (Sandbox)</span></label></div>
                    <div class="form-group"><label>App ID</label><input type="text" name="cashfree_app_id" value="<?php echo htmlspecialchars($s['cashfree_app_id']); ?>"></div>
                    <div class="form-group"><label>Secret Key</label><input type="password" name="cashfree_secret_key" value="<?php echo htmlspecialchars($s['cashfree_secret_key']); ?>"></div>
                </div>
                <div class="settings-card">
                    <h3><span class="mdi mdi-cash-multiple"></span> Payout Settings</h3>
                    <div class="form-group"><label>Minimum Payout Threshold (INR)</label><input type="number" name="min_payout_threshold" value="<?php echo htmlspecialchars($s['min_payout_threshold']); ?>" min="0"></div>
                    <div class="form-group"><label>Currency</label><input type="text" name="currency" value="<?php echo htmlspecialchars($s['currency']); ?>" placeholder="INR"></div>
                </div>
                <button type="submit" class="btn-save"><span class="mdi mdi-content-save"></span> Save Payment Settings</button>
            </form>
        </div>

        <!-- TAB: Freemium & Growth -->
        <div id="tab-freemium" class="tab-panel <?php echo $activeTab==='freemium'?'active':''; ?>">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="active_tab" value="freemium">
                <div class="settings-card">
                    <h3><span class="mdi mdi-gift-outline"></span> Free Releases (Freemium)</h3>
                    <div class="info-box">
                        Users without an active subscription get a limited number of
                        <b>submitted releases per calendar month</b>. Drafts and rejected
                        releases do not count. Paid plans are always unlimited.
                    </div>
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="free_releases_enabled" <?php echo $s['free_releases_enabled']==='1'?'checked':''; ?>>
                            <span>Enable free monthly releases for unsubscribed users</span>
                        </label>
                    </div>
                    <div class="form-group"><label>Free releases per month</label><input type="number" name="free_releases_per_month" value="<?php echo htmlspecialchars($s['free_releases_per_month']); ?>" min="0" max="50"></div>
                </div>
                <div class="settings-card">
                    <h3><span class="mdi mdi-party-popper"></span> Creator Day (0% Commission)</h3>
                    <div class="info-box">
                        One day every month is <b>Creator Day</b> — dashboards show a
                        "100% royalties" banner. The actual 100% tip/royalty split is
                        applied on the HiTune Music side (Music admin → tip_artist_pct /
                        tip_event_until).
                    </div>
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="creator_day_enabled" <?php echo $s['creator_day_enabled']==='1'?'checked':''; ?>>
                            <span>Enable Creator Day banners</span>
                        </label>
                    </div>
                    <div class="form-group"><label>Day of month (1–28)</label><input type="number" name="creator_day_date" value="<?php echo htmlspecialchars($s['creator_day_date']); ?>" min="1" max="28"></div>
                </div>
                <button type="submit" class="btn-save"><span class="mdi mdi-content-save"></span> Save Freemium Settings</button>
            </form>
        </div>

        <!-- TAB: Email -->
        <div id="tab-email" class="tab-panel <?php echo $activeTab==='email'?'active':''; ?>">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="active_tab" value="email">
                <div class="settings-card">
                    <h3><span class="mdi mdi-email-outline"></span> SMTP / Email Settings</h3>
                    <div class="form-group"><label>SMTP Host</label><input type="text" name="smtp_host" value="<?php echo htmlspecialchars($s['smtp_host']); ?>" placeholder="smtp.gmail.com"></div>
                    <div class="form-group"><label>SMTP Port</label><input type="number" name="smtp_port" value="<?php echo htmlspecialchars($s['smtp_port']); ?>" placeholder="587"></div>
                    <div class="form-group"><label>SMTP Username</label><input type="text" name="smtp_username" value="<?php echo htmlspecialchars($s['smtp_username']); ?>"></div>
                    <div class="form-group"><label>SMTP Password</label><input type="password" name="smtp_password" value="<?php echo htmlspecialchars($s['smtp_password']); ?>"></div>
                    <div class="form-group"><label>From Email</label><input type="email" name="from_email" value="<?php echo htmlspecialchars($s['from_email']); ?>"></div>
                    <div class="form-group"><label>From Name</label><input type="text" name="from_name" value="<?php echo htmlspecialchars($s['from_name']); ?>"></div>
                    <div class="form-group"><label class="toggle-switch"><input type="checkbox" name="smtp_ssl" <?php echo $s['smtp_ssl']==='1'?'checked':''; ?>><span>Enable SSL/TLS</span></label></div>
                </div>
                <button type="submit" class="btn-save"><span class="mdi mdi-content-save"></span> Save Email Settings</button>
            </form>
        </div>

    </main>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>
