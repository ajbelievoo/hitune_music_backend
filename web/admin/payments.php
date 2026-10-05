<?php
/**
 * web.hitune.in Admin Panel - Payments
 * View all payment transactions
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$gateway_filter = $_GET['gateway'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($status_filter) {
    $where_clauses[] = 'p.payment_status = ?';
    $params[] = $status_filter;
    $types .= 's';
}

if ($gateway_filter) {
    $where_clauses[] = 'p.payment_gateway = ?';
    $params[] = $gateway_filter;
    $types .= 's';
}

if ($date_from) {
    $where_clauses[] = 'DATE(p.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where_clauses[] = 'DATE(p.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get payments with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count total
$count_sql = "SELECT COUNT(*) as total FROM payments p $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get payments
$sql = "SELECT p.*, u.name as user_name, u.email as user_email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        $where_sql 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$stats_sql = "SELECT 
    COUNT(*) as total_transactions,
    SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN payment_status = 'completed' THEN amount_paid ELSE 0 END) as total_revenue
FROM payments";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin Panel</title>
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
        }
        .stat-card h3 {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
        }
        .stat-card.revenue .value { color: #00c853; }
        .stat-card.completed .value { color: #00c853; }
        .stat-card.pending .value { color: #ff9800; }
        .stat-card.failed .value { color: #00b7ff; }
        
        /* Filters */
        .filters {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
        }
        .filter-group select, .filter-group input {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            min-width: 150px;
        }
        .filter-group select option {
            background: #1a1a2e;
        }
        .btn-filter, .btn-reset {
            padding: 10px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-filter {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff;
        }
        .btn-reset {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        /* Table */
        .table-container {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
        }
        tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-completed { background: rgba(0, 200, 83, 0.2); color: #00c853; }
        .status-pending { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .status-failed { background: rgba(0, 183, 255, 0.2); color: #00b7ff; }
        .status-refunded { background: rgba(156, 39, 176, 0.2); color: #9c27b0; }
        
        .gateway-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .gateway-razorpay { background: rgba(51, 149, 255, 0.2); color: #3395ff; }
        .gateway-cashfree { background: rgba(0, 212, 170, 0.2); color: #00d4aa; }
        
        .amount {
            font-weight: 700;
            font-size: 16px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        .user-info .name {
            font-weight: 600;
        }
        .user-info .email {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
        }
        .page-link {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        .page-link:hover, .page-link.active {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-color: transparent;
        }
        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.5);
        }
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        @media (max-width: 1200px) {
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
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
                <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Submissions</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item active"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><i class="mdi mdi-credit-card"></i> Payment Transactions</h1>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card revenue">
                    <h3>Total Revenue</h3>
                    <div class="value">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                </div>
                <div class="stat-card completed">
                    <h3>Completed</h3>
                    <div class="value"><?php echo number_format($stats['completed_count'] ?? 0); ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <div class="value"><?php echo number_format($stats['pending_count'] ?? 0); ?></div>
                </div>
                <div class="stat-card failed">
                    <h3>Failed</h3>
                    <div class="value"><?php echo number_format($stats['failed_count'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Gateway</label>
                    <select name="gateway">
                        <option value="">All Gateways</option>
                        <option value="razorpay" <?php echo $gateway_filter == 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                        <option value="cashfree" <?php echo $gateway_filter == 'cashfree' ? 'selected' : ''; ?>>Cashfree</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <button type="submit" class="btn-filter"><i class="mdi mdi-filter"></i> Filter</button>
                <a href="payments.php" class="btn-reset"><i class="mdi mdi-refresh"></i> Reset</a>
            </form>

            <!-- Table -->
            <div class="table-container">
                <?php if (count($payments) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Gateway</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>#<?php echo $payment['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <span class="name"><?php echo htmlspecialchars($payment['user_name'] ?? 'Unknown'); ?></span>
                                            <span class="email"><?php echo htmlspecialchars($payment['user_email'] ?? ''); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                    <td>
                                        <span class="gateway-badge gateway-<?php echo $payment['payment_gateway']; ?>">
                                            <?php echo ucfirst($payment['payment_gateway']); ?>
                                        </span>
                                    </td>
                                    <td class="amount">₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <a href="#" title="View Details" style="color: #fff;"><i class="mdi mdi-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="mdi mdi-credit-card-off"></i>
                        <p>No payment transactions found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $status_filter; ?>&gateway=<?php echo $gateway_filter; ?>" 
                       class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&gateway=<?php echo $gateway_filter; ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&status=<?php echo $status_filter; ?>&gateway=<?php echo $gateway_filter; ?>" 
                       class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
