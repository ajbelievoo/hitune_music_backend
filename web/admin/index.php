<?php
/**
 * web.hitune.in Admin Panel - Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Get statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_releases' => $conn->query("SELECT COUNT(*) as count FROM releases")->fetch_assoc()['count'],
    'pending_releases' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'submitted'")->fetch_assoc()['count'],
    'in_progress' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'in_progress'")->fetch_assoc()['count'],
    'published' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'ready'")->fetch_assoc()['count'],
];

// Get recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Get recent releases
$recent_releases = $conn->query("SELECT r.*, u.name as user_name FROM releases r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HiTune Distribution</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
        }
        
        /* Admin Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .sidebar-header .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
        }
        .sidebar-header p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Navigation */
        .nav-menu {
            padding: 20px 0;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 30px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover {
            background: rgba(0, 183, 255, 0.1);
            color: #00b7ff;
            border-left-color: #00b7ff;
        }
        .nav-item.active {
            background: rgba(0, 183, 255, 0.2);
            color: #00b7ff;
            border-left-color: #00b7ff;
        }
        .nav-item span {
            font-size: 20px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-menu a {
            padding: 10px 20px;
            background: rgba(0, 183, 255, 0.2);
            border: 1px solid rgba(0, 183, 255, 0.3);
            border-radius: 8px;
            color: #00b7ff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(0, 183, 255, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(0, 183, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 183, 255, 0.2);
        }
        .stat-card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stat-card h3 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }
        
        /* Content Section */
        .content-section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .content-section h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Table */
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
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-submitted { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-in_progress { background: rgba(79, 172, 254, 0.2); color: #4facfe; }
        .status-ready { background: rgba(0, 200, 83, 0.2); color: #00c853; }
        .status-rejected { background: rgba(255, 82, 82, 0.2); color: #ff5252; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="mdi mdi-shield-account"></span>
                </div>
                <h2>Admin Panel</h2>
                <p>HiTune Distribution</p>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item active">
                    <span class="mdi mdi-view-dashboard"></span>
                    Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <span class="mdi mdi-account-group"></span>
                    Users
                </a>
                <a href="submissions.php" class="nav-item">
                    <span class="mdi mdi-music"></span>
                    Releases
                </a>
                <a href="cover_art.php" class="nav-item">
                    <span class="mdi mdi-image"></span>
                    Cover Art
                </a>
                <a href="artist_accounts_settings.php" class="nav-item">
                    <span class="mdi mdi-image-multiple"></span>
                    Artist Previews
                </a>
                <a href="manage_artists.php" class="nav-item">
                    <span class="mdi mdi-account-music"></span>
                    Manage Artists
                </a>
                <a href="user_artists.php" class="nav-item">
                    <span class="mdi mdi-account-question"></span>
                    User Artist Requests
                    <?php 
                    $pendingRequests = $conn->query("SELECT COUNT(*) as count FROM user_artists WHERE status = 'pending'")->fetch_assoc()['count'];
                    if ($pendingRequests > 0): 
                    ?>
                    <span style="background: #00b7ff; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 10px;"><?php echo $pendingRequests; ?></span>
                    <?php endif; ?>
                </a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="revenue.php" class="nav-item">
                    <span class="mdi mdi-currency-usd"></span>
                    Revenue
                </a>
                <a href="payouts.php" class="nav-item">
                    <span class="mdi mdi-cash-multiple"></span>
                    Payouts
                </a>
                <a href="payments.php" class="nav-item">
                    <span class="mdi mdi-credit-card"></span>
                    Payments
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="mdi mdi-cog"></span>
                    Settings
                </a>
                <a href="logout.php" class="nav-item">
                    <span class="mdi mdi-logout"></span>
                    Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-menu">
                    <span>Welcome, Admin</span>
                    <a href="logout.php">Logout</a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <span class="mdi mdi-account-group"></span>
                    </div>
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <span class="mdi mdi-music"></span>
                    </div>
                    <h3><?php echo $stats['total_releases']; ?></h3>
                    <p>Total Releases</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <span class="mdi mdi-clock-alert"></span>
                    </div>
                    <h3><?php echo $stats['pending_releases']; ?></h3>
                    <p>Pending Review</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <span class="mdi mdi-check-circle"></span>
                    </div>
                    <h3><?php echo $stats['published']; ?></h3>
                    <p>Published</p>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="content-section">
                <h2>
                    <span class="mdi mdi-account-group"></span>
                    Recent Users
                </h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Releases -->
            <div class="content-section">
                <h2>
                    <span class="mdi mdi-music"></span>
                    Recent Releases
                </h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rel = $recent_releases->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rel['title']); ?></td>
                            <td><?php echo htmlspecialchars($rel['primary_artist']); ?></td>
                            <td><?php echo htmlspecialchars($rel['user_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $rel['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $rel['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($rel['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
