<?php
/**
 * HiTune Admin Panel - User Details View
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    header('Location: users.php');
    exit;
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get user stats
$releases_count = $conn->query("SELECT COUNT(*) as count FROM releases WHERE user_id = $user_id")->fetch_assoc()['count'];
$published_count = $conn->query("SELECT COUNT(*) as count FROM releases WHERE user_id = $user_id AND status = 'ready'")->fetch_assoc()['count'];
$pending_count = $conn->query("SELECT COUNT(*) as count FROM releases WHERE user_id = $user_id AND status = 'submitted'")->fetch_assoc()['count'];

// Get user's releases
$releases = $conn->query("SELECT * FROM releases WHERE user_id = $user_id ORDER BY created_at DESC");

// Get user's payments
$payments = $conn->query("SELECT * FROM payments WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");

// Get artist accounts
$artist_accounts = $conn->query("SELECT * FROM artist_accounts WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
        }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .sidebar-header .logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .sidebar-header p { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 5px; }
        .nav-menu { padding: 20px 0; }
        .nav-item {
            display: flex; align-items: center; gap: 15px;
            padding: 15px 30px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(0, 183, 255, 0.1);
            color: #00b7ff;
            border-left-color: #00b7ff;
        }
        .nav-item span { font-size: 20px; }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 { font-size: 28px; font-weight: 700; }
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-danger {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff5252;
        }
        
        /* User Profile Card */
        .user-profile {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .user-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            flex-shrink: 0;
        }
        .user-info {
            flex: 1;
        }
        .user-info h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .user-info p {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 5px;
        }
        .user-meta {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        .user-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Content Sections */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        .content-section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
        }
        .content-section h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            text-align: left;
            padding: 12px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-ready { background: rgba(0, 200, 83, 0.2); color: #00c853; }
        .status-submitted { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-draft { background: rgba(255, 255, 255, 0.1); color: rgba(255,255,255,0.7); }
        .status-completed { background: rgba(0, 200, 83, 0.2); color: #00c853; }
        .status-pending { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.5);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .action-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 1200px) {
            .content-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
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
                <a href="users.php" class="nav-item active"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
                <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="manage_artists.php" class="nav-item"><span class="mdi mdi-account-music"></span>Manage Artists</a>
                <a href="user_artists.php" class="nav-item"><span class="mdi mdi-account-question"></span>Artist Requests</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><span class="mdi mdi-account"></span> User Details</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="users.php" class="btn btn-secondary"><span class="mdi mdi-arrow-left"></span> Back to Users</a>
                    <a href="user_edit.php?id=<?php echo $user_id; ?>" class="btn"><span class="mdi mdi-pencil"></span> Edit User</a>
                </div>
            </div>

            <!-- User Profile -->
            <div class="user-profile">
                <div class="user-avatar">
                    <span class="mdi mdi-account"></span>
                </div>
                <div class="user-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><span class="mdi mdi-email"></span> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><span class="mdi mdi-phone"></span> <?php echo htmlspecialchars($user['phone'] ?? 'No phone'); ?></p>
                    <div class="user-meta">
                        <div class="user-meta-item">
                            <span class="mdi mdi-calendar"></span>
                            Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div class="user-meta-item">
                            <span class="mdi mdi-identifier"></span>
                            ID: #<?php echo $user['id']; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 style="color: #fff;"><?php echo $releases_count; ?></h3>
                    <p>Total Releases</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #00c853;"><?php echo $published_count; ?></h3>
                    <p>Published</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #ffc107;"><?php echo $pending_count; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #4facfe;"><?php echo $payments->num_rows; ?></h3>
                    <p>Payments</p>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Releases -->
                <div class="content-section">
                    <h2><span class="mdi mdi-music"></span> Recent Releases</h2>
                    <?php if ($releases->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($release = $releases->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($release['title']); ?></td>
                                <td><?php echo ucfirst($release['release_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $release['status']; ?>">
                                        <?php echo ucfirst($release['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j', strtotime($release['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <span class="mdi mdi-music-off"></span>
                        <p>No releases yet</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Payments -->
                <div class="content-section">
                    <h2><span class="mdi mdi-credit-card"></span> Recent Payments</h2>
                    <?php if ($payments->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                <td>₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j', strtotime($payment['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <span class="mdi mdi-credit-card-off"></span>
                        <p>No payments yet</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Artist Accounts -->
                <div class="content-section">
                    <h2><span class="mdi mdi-account-music"></span> Artist Accounts</h2>
                    <?php if ($artist_accounts->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Platform</th>
                                <th>Artist Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($account = $artist_accounts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo ucfirst(str_replace('_', ' ', $account['platform'])); ?></td>
                                <td><?php echo htmlspecialchars($account['artist_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $account['status']; ?>">
                                        <?php echo ucfirst($account['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <span class="mdi mdi-account-off"></span>
                        <p>No artist accounts</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Activity -->
                <div class="content-section">
                    <h2><span class="mdi mdi-history"></span> Recent Activity</h2>
                    <?php
                    $activity = $conn->query("SELECT * FROM activity_log WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
                    if ($activity->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($act = $activity->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($act['action']); ?></td>
                                <td><?php echo date('M j, H:i', strtotime($act['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <span class="mdi mdi-history"></span>
                        <p>No recent activity</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
