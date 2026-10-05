<?php
/**
 * web.hitune.in Admin Panel - Revenue Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Get revenue stats from payments (for subscription revenue)
$total_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM payments WHERE payment_status = 'completed'")->fetch_assoc()['total'] ?? 0;
$this_month = $conn->query("SELECT SUM(amount_paid) as total FROM payments WHERE payment_status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'] ?? 0;
$pending = $conn->query("SELECT SUM(plan_amount) as total FROM payments WHERE payment_status = 'pending'")->fetch_assoc()['total'] ?? 0;

// Get artist earnings stats
$total_earnings = $conn->query("SELECT SUM(amount) as total FROM user_earnings")->fetch_assoc()['total'] ?? 0;
$paid_out = $conn->query("SELECT SUM(net_amount) as total FROM withdrawal_requests WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$available_balance = $conn->query("SELECT SUM(available_balance) as total FROM user_balances")->fetch_assoc()['total'] ?? 0;

// Get recent earnings
$recent_earnings = $conn->query("SELECT e.*, u.name as user_name, r.title as release_title, t.song_title as track_title 
    FROM user_earnings e 
    JOIN users u ON e.user_id = u.id 
    LEFT JOIN releases r ON e.release_id = r.id 
    LEFT JOIN release_tracks t ON e.track_id = t.id 
    ORDER BY e.created_at DESC LIMIT 20");

// Get earnings by platform
$platform_stats = $conn->query("SELECT platform, SUM(amount) as total FROM user_earnings GROUP BY platform ORDER BY total DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Management - Admin Panel</title>
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
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(0, 200, 83, 0.1), rgba(0, 230, 118, 0.1));
            border: 1px solid rgba(0, 200, 83, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 10px;
        }
        .stat-card .amount {
            font-size: 36px;
            font-weight: 800;
            color: #38ef7d;
        }
        .content-section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            text-align: left;
            padding: 15px;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
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
                <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
                <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="manage_artists.php" class="nav-item"><span class="mdi mdi-account-music"></span>Manage Artists</a>
                <a href="user_artists.php" class="nav-item"><span class="mdi mdi-account-question"></span>Artist Requests</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="revenue.php" class="nav-item active"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><span class="mdi mdi-currency-usd"></span> Revenue Management</h1>
                <a href="index.php" class="btn"><span class="mdi mdi-view-dashboard"></span> Dashboard</a>
            </div>

            <!-- Platform Revenue Stats -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                <div class="stat-card">
                    <h3>Total Subscription Revenue</h3>
                    <div class="amount">₹<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>This Month</h3>
                    <div class="amount">₹<?php echo number_format($this_month, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Payments</h3>
                    <div class="amount">₹<?php echo number_format($pending, 2); ?></div>
                </div>
            </div>

            <!-- Artist Earnings Stats -->
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><span class="mdi mdi-music"></span> Artist Earnings</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                <div style="background: rgba(0,200,83,0.05); border: 1px solid rgba(0,200,83,0.1); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 10px;">Total Earnings Generated</h3>
                    <div style="font-size: 32px; font-weight: 800; color: #00c853;">$<?php echo number_format($total_earnings, 2); ?></div>
                </div>
                <div style="background: rgba(56,239,125,0.05); border: 1px solid rgba(56,239,125,0.1); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 10px;">Paid Out to Artists</h3>
                    <div style="font-size: 32px; font-weight: 800; color: #38ef7d;">$<?php echo number_format($paid_out, 2); ?></div>
                </div>
                <div style="background: rgba(79,172,254,0.05); border: 1px solid rgba(79,172,254,0.1); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 10px;">Available in Balances</h3>
                    <div style="font-size: 32px; font-weight: 800; color: #4facfe;">$<?php echo number_format($available_balance, 2); ?></div>
                </div>
            </div>

            <!-- Platform Breakdown -->
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px;">
                <div class="content-section">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><span class="mdi mdi-poll"></span> By Platform</h2>
                    <?php if ($platform_stats->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Platform</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $platform_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['platform'] ?? 'Unknown'); ?></td>
                                <td style="color: #38ef7d; font-weight: 700;">$<?php echo number_format($p['total'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        <span class="mdi mdi-chart-bar" style="font-size: 48px; display: block; margin-bottom: 15px;"></span>
                        <p>No platform data available</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="content-section">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><span class="mdi mdi-history"></span> Recent Earnings</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Release/Track</th>
                                <th>Platform</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($e = $recent_earnings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($e['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($e['track_title'] ?? $e['release_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($e['platform'] ?? 'All'); ?></td>
                                <td style="color: #38ef7d; font-weight: 600;">$<?php echo number_format($e['amount'], 2); ?></td>
                                <td><?php echo date('M j', strtotime($e['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($recent_earnings->num_rows == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                                    <span class="mdi mdi-cash-off" style="font-size: 32px; display: block; margin-bottom: 10px;"></span>
                                    No earnings recorded yet
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
