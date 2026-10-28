<?php
/**
 * HiTune Developer API Dashboard
 */

// Share session across subdomains before BOF loads
if (!headers_sent()) {
    ini_set('session.cookie_domain', '.hitune.in');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '.hitune.in',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load BOF config to get session keys
require_once( dirname(__FILE__) . "/api/app/config.php" );

// Check if user is logged in via BOF session
$is_logged = false;
$user_id = null;

// Check for BOF session
if (isset($_SESSION['sess_id']) && !empty($_SESSION['sess_id'])) {
    $is_logged = true;
    $user_id = $_SESSION['user_id'] ?? null;
}

// If not logged in via BOF, check for simple session
if (!$is_logged && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $is_logged = true;
    $user_id = $_SESSION['user_id'];
}

if (!$is_logged) {
    header('Location: https://music.hitune.in/');
    exit;
}

// Load BOF framework to call developer API directly
require_once( bof_root . "/loader.php" );
require_once( root . "/app/client/loader.php" );

// Initialize developer API
require_once( root . "/../plugins/bof_tool_hitune_extras/classes/class_developer_api.php" );
bof()->object->developer_api = new developer_api();

// Get apps for user
$apps = [];
if ($user_id) {
    $apps_raw = bof()->developer_api->apps_for_user($user_id);
    foreach ($apps_raw as $app) {
        $apps[] = bof()->developer_api->clean_app($app);
    }
}

// Get plans
$plans = bof()->developer_api->plans(true);

// Get usage (simplified - for now show mock data)
$usage = [
    'total_requests' => 0,
    'limit' => 10000,
    'max_apps' => 1,
    'plan_name' => 'Sandbox',
    'streams_used' => 0,
    'streams_limit' => 0
];

// If user has apps, get their plan and usage
if (!empty($apps)) {
    $first_app = $apps[0];
    $plan = bof()->developer_api->plan($first_app['plan_hash'] ?? null);
    if ($plan) {
        $usage['plan_name'] = $plan['name'];
        $usage['limit'] = $plan['monthly_requests'] ?? 10000;
        $usage['max_apps'] = $plan['max_apps'] ?? 1;
        $usage['streams_limit'] = $plan['monthly_streams'] ?? 0;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Dashboard | Hitune</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Figtree', sans-serif;
            background: #0a0a1a;
            color: #fff;
            line-height: 1.6;
        }
        .topnav {
            background: rgba(10, 10, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 2rem;
        }
        .topnav-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-dot {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .nav-links a {
            color: #aaa;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: #00b7ff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-danger {
            background: rgba(255, 87, 87, 0.2);
            color: #ff5757;
            border: 1px solid rgba(255, 87, 87, 0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .stat-label {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #00b7ff;
        }
        .stat-bar {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            height: 8px;
            margin-top: 1rem;
            overflow: hidden;
        }
        .stat-bar-fill {
            background: linear-gradient(90deg, #00b7ff, #8b5cf6);
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .app-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .app-name {
            font-size: 1.2rem;
            font-weight: 600;
        }
        .app-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            background: rgba(0, 183, 255, 0.2);
            color: #00b7ff;
        }
        .app-status.inactive {
            background: rgba(255, 87, 87, 0.2);
            color: #ff5757;
        }
        .key-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            background: rgba(0,0,0,0.3);
            padding: 0.75rem;
            border-radius: 6px;
        }
        .key-label {
            color: #888;
            font-size: 0.8rem;
            min-width: 100px;
        }
        .key-value {
            font-family: monospace;
            font-size: 0.9rem;
            color: #00b7ff;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .key-actions {
            display: flex;
            gap: 0.5rem;
        }
        .key-actions button {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            padding: 0.25rem;
        }
        .key-actions button:hover {
            color: #fff;
        }
        .app-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: #151525;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
        }
        .modal-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: #888;
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #00b7ff;
        }
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .apps-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/developer">
                <span class="brand-dot"><span class="mdi mdi-music-note"></span></span>
                Hitune <small>Developer</small>
            </a>
            <div class="nav-links">
                <a href="/developer">API Docs</a>
                <a href="https://music.hitune.in/">Music App</a>
                <a href="https://music.hitune.in/admin/" class="nav-cta">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Developer Dashboard</h1>
            <button class="btn btn-primary" onclick="openCreateModal()">+ Create App</button>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Requests (This Month)</div>
                <div class="stat-value"><?php echo number_format($usage['total_requests'] ?? 0); ?></div>
                <div class="stat-bar">
                    <div class="stat-bar-fill" style="width: <?php echo min(100, ($usage['total_requests'] ?? 0) / max(1, $usage['limit'] ?? 100000) * 100); ?>%"></div>
                </div>
                <div style="color: #888; font-size: 0.8rem; margin-top: 0.5rem;">
                    <?php echo number_format($usage['limit'] ?? 100000); ?> limit
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Apps</div>
                <div class="stat-value"><?php echo count($apps); ?></div>
                <div style="color: #888; font-size: 0.8rem; margin-top: 0.5rem;">
                    Max: <?php echo $usage['max_apps'] ?? 3; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Current Plan</div>
                <div class="stat-value"><?php echo htmlspecialchars($usage['plan_name'] ?? 'Sandbox'); ?></div>
                <div style="color: #888; font-size: 0.8rem; margin-top: 0.5rem;">
                    <?php if (!empty($usage['plan_name']) && $usage['plan_name'] !== 'Sandbox'): ?>
                        <a href="#" onclick="openTopupModal()" style="color: #00b7ff;">Need more? Topup</a>
                    <?php else: ?>
                        <a href="/developer#plans" style="color: #00b7ff;">Upgrade Plan</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Streams Used</div>
                <div class="stat-value"><?php echo number_format($usage['streams_used'] ?? 0); ?></div>
                <div style="color: #888; font-size: 0.8rem; margin-top: 0.5rem;">
                    <?php echo number_format($usage['streams_limit'] ?? 0); ?> limit
                </div>
            </div>
        </div>

        <!-- Important API Notice -->
        <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
            <h4 style="color: #ffc107; margin-bottom: 1rem;">📋 API Usage Guide</h4>
            <ul style="color: #aaa; line-height: 1.6; margin: 0; padding-left: 1.5rem;">
                <li><strong>Browser/Mobile Apps:</strong> Use <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">x-api-key: ht_pub_...</code> header</li>
                <li><strong>Server-Side:</strong> Get Bearer token via <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">POST /api/v1/token</code> with client_id + client_secret</li>
                <li><strong>Search:</strong> Use <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">?query=</code> parameter (not <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">?q=</code>)</li>
                <li><strong>Stream URLs:</strong> Require Pro+ plan and Bearer token authentication</li>
            </ul>
        </div>

        <!-- Apps -->
        <h2 class="section-title">Your Apps</h2>
        <?php if (empty($apps)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><span class="mdi mdi-application"></span></div>
                <p>No apps yet. Create your first app to get started!</p>
            </div>
        <?php else: ?>
            <div class="apps-grid">
                <?php foreach ($apps as $app): ?>
                    <div class="app-card">
                        <div class="app-header">
                            <div class="app-name"><?php echo htmlspecialchars($app['name']); ?></div>
                            <span class="app-status <?php echo ($app['active'] ?? 0) ? '' : 'inactive'; ?>">
                                <?php echo ($app['active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <div class="key-row">
                            <span class="key-label">Client ID:</span>
                            <span class="key-value"><?php echo htmlspecialchars($app['client_id'] ?? ''); ?></span>
                            <div class="key-actions">
                                <button onclick="copyToClipboard('<?php echo htmlspecialchars($app['client_id'] ?? ''); ?>')"><span class="mdi mdi-content-copy"></span></button>
                            </div>
                        </div>
                        <div class="key-row">
                            <span class="key-label">Publishable:</span>
                            <span class="key-value"><?php echo htmlspecialchars($app['publishable_key'] ?? ''); ?></span>
                            <div class="key-actions">
                                <button onclick="copyToClipboard('<?php echo htmlspecialchars($app['publishable_key'] ?? ''); ?>')"><span class="mdi mdi-content-copy"></span></button>
                            </div>
                        </div>
                        <div class="key-row">
                            <span class="key-label">Client Secret:</span>
                            <span class="key-value"><?php echo !empty($app['client_secret']) ? 'ht_sec_••••••••••••' : 'Not generated'; ?></span>
                            <div class="key-actions">
                                <?php if (!empty($app['client_secret'])): ?>
                                    <button onclick="alert('Client secret is shown only once during creation. Regenerate if lost.')"><span class="mdi mdi-eye-off"></span></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="key-row">
                            <span class="key-label">Plan:</span>
                            <span class="key-value"><?php echo htmlspecialchars($app['plan_name'] ?? 'Sandbox'); ?></span>
                        </div>
                        <div class="app-actions">
                            <button class="btn btn-secondary" onclick="openEditModal('<?php echo htmlspecialchars($app['hash'] ?? ''); ?>')">Edit</button>
                            <button class="btn btn-secondary" onclick="regenerateKey('<?php echo htmlspecialchars($app['hash'] ?? ''); ?>')">Regenerate Keys</button>
                            <button class="btn btn-danger" onclick="deleteApp('<?php echo htmlspecialchars($app['hash'] ?? ''); ?>')">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create App Modal -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header">Create New App</div>
            <form onsubmit="createApp(event)">
                <div class="form-group">
                    <label>App Name</label>
                    <input type="text" name="name" required placeholder="My Awesome App">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="What will you build?">
                </div>
                <div class="form-group">
                    <label>Plan</label>
                    <select name="plan" required>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo htmlspecialchars($plan['hash'] ?? ''); ?>">
                                <?php echo htmlspecialchars($plan['name'] ?? ''); ?> - <?php echo htmlspecialchars($plan['price'] ?? 'Free'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create App</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Topup Modal -->
    <div class="modal" id="topupModal">
        <div class="modal-content">
            <div class="modal-header">Topup Requests</div>
            <form onsubmit="topup(event)">
                <div class="form-group">
                    <label>Extra Requests</label>
                    <select name="requests" required>
                        <option value="10000">10,000 requests - ₹99</option>
                        <option value="50000">50,000 requests - ₹399</option>
                        <option value="100000">100,000 requests - ₹699</option>
                        <option value="500000">500,000 requests - ₹2,999</option>
                        <option value="1000000">1,000,000 requests - ₹4,999</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('topupModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function openTopupModal() {
            document.getElementById('topupModal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('Copied to clipboard!');
        }

        function createApp(e) {
            e.preventDefault();
            alert('App creation requires the Flutter app. Please use the HiTune Music app → Profile → Developer API to create apps.');
        }

        function regenerateKey(hash) {
            if (!confirm('Are you sure you want to regenerate keys? This will invalidate your old keys.')) return;
            alert('Key regeneration requires the Flutter app. Please use the HiTune Music app → Profile → Developer API.');
        }

        function deleteApp(hash) {
            if (!confirm('Are you sure you want to delete this app? This cannot be undone.')) return;
            alert('App deletion requires the Flutter app. Please use the HiTune Music app → Profile → Developer API.');
        }

        function topup(e) {
            e.preventDefault();
            alert('Topup requires the Flutter app. Please use the HiTune Music app → Profile → Developer API → Subscribe.');
        }
    </script>
</body>
</html>
