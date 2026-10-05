<?php
/**
 * web.hitune.in Admin Panel - Payouts Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$success = '';
$error = '';

// Handle status update — real `payouts` table (user requests land here)
if (isset($_POST['update_status'])) {
    $withdrawal_id = intval($_POST['withdrawal_id']);
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    if (!in_array($new_status, ['pending', 'approved', 'rejected'], true)) {
        $error = 'Invalid status';
    } else {
        $stmt = $conn->prepare("UPDATE payouts SET status = ?, admin_notes = ?, processed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $admin_notes, $withdrawal_id);
    
        if ($stmt->execute()) {
            $success = 'Payout status updated successfully';
        } else {
            $error = 'Error updating status: ' . $conn->error;
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = '';
$params = [];
$types = '';

if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'rejected'], true)) {
    $where = "WHERE w.status = ?";
    $params[] = $status_filter;
    $types = 's';
}

// Get total count
$count_sql = "SELECT COUNT(*) as count FROM payouts w $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $per_page);

// Get withdrawals with user info
$sql = "SELECT w.*, u.name as user_name, u.email as user_email
        FROM payouts w
        JOIN users u ON w.user_id = u.id
        $where
        ORDER BY w.requested_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$withdrawals = $stmt->get_result();

// Get stats
$pending_count = $conn->query("SELECT COUNT(*) as count FROM payouts WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM payouts WHERE status = 'approved'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM payouts WHERE status = 'rejected'")->fetch_assoc()['count'];
$total_amount = $conn->query("SELECT SUM(amount) as total FROM payouts WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payouts Management - Admin Panel</title>
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
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-processing { background: rgba(79, 172, 254, 0.2); color: #4facfe; }
        .status-completed { background: rgba(0, 200, 83, 0.2); color: #00c853; }
        .status-rejected { background: rgba(255, 82, 82, 0.2); color: #ff5252; }
        .status-failed { background: rgba(255, 82, 82, 0.2); color: #ff5252; }
        .status-approved { background: rgba(156, 39, 176, 0.2); color: #9c27b0; }
        .btn-process {
            padding: 8px 16px;
            background: #38ef7d;
            border: none;
            border-radius: 6px;
            color: #000;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
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
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item active"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><span class="mdi mdi-cash-multiple"></span> Payouts Management</h1>
                <a href="index.php" class="btn"><span class="mdi mdi-view-dashboard"></span> Dashboard</a>
            </div>

            <?php if ($success): ?>
            <div style="background: rgba(0,200,83,0.2); border: 1px solid rgba(0,200,83,0.3); border-radius: 10px; padding: 15px; margin-bottom: 25px; color: #00c853; display: flex; align-items: center; gap: 10px;">
                <span class="mdi mdi-check-circle"></span> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div style="background: rgba(255,82,82,0.2); border: 1px solid rgba(255,82,82,0.3); border-radius: 10px; padding: 15px; margin-bottom: 25px; color: #ff5252; display: flex; align-items: center; gap: 10px;">
                <span class="mdi mdi-alert-circle"></span> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px;">
                <div style="background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.2); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 32px; font-weight: 700; color: #ffc107; margin-bottom: 5px;"><?php echo number_format($pending_count); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">Pending</p>
                </div>
                <div style="background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.2); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 32px; font-weight: 700; color: #00c853; margin-bottom: 5px;"><?php echo number_format($approved_count); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">Approved / Paid</p>
                </div>
                <div style="background: rgba(255,82,82,0.1); border: 1px solid rgba(255,82,82,0.2); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 32px; font-weight: 700; color: #ff5252; margin-bottom: 5px;"><?php echo number_format($rejected_count); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">Rejected</p>
                </div>
                <div style="background: rgba(56,239,125,0.1); border: 1px solid rgba(56,239,125,0.2); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 24px; font-weight: 700; color: #38ef7d; margin-bottom: 5px;">$<?php echo number_format($total_amount, 2); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">Total Paid</p>
                </div>
            </div>

            <!-- Filter -->
            <form method="GET" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 20px; margin-bottom: 25px; display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-size: 12px; color: rgba(255,255,255,0.6); text-transform: uppercase;">Status</label>
                    <select name="status" style="padding: 10px 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; min-width: 150px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved / Paid</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" style="padding: 10px 25px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); border: none; border-radius: 8px; color: #fff; font-weight: 600; cursor: pointer;">
                    <span class="mdi mdi-filter"></span> Filter
                </button>
                <a href="payouts.php" style="padding: 10px 25px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none;">
                    <span class="mdi mdi-refresh"></span> Reset
                </a>
            </form>

            <div class="content-section">
                <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;"><span class="mdi mdi-cash-multiple"></span> Payout Requests</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Method / Account</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($w = $withdrawals->fetch_assoc()):
                            $method_label = ucfirst(str_replace('_', ' ', $w['payment_method'] ?? 'unknown'));
                        ?>
                        <tr>
                            <td>#<?php echo $w['id']; ?></td>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($w['user_name']); ?></span>
                                    <span style="font-size: 12px; color: rgba(255,255,255,0.5);"><?php echo htmlspecialchars($w['user_email']); ?></span>
                                </div>
                            </td>
                            <td style="color: #38ef7d; font-weight: 700;">₹<?php echo number_format($w['amount'], 2); ?></td>
                            <td>
                                <div style="font-size: 12px; max-width: 220px;">
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($method_label); ?></div>
                                    <div style="color: rgba(255,255,255,0.5); white-space: pre-wrap;"><?php echo htmlspecialchars($w['account_details'] ?? ''); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $w['status']; ?>">
                                    <?php echo ucfirst($w['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($w['requested_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px; flex-wrap: wrap; align-items:center;">
                                    <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                    <select name="status" style="padding: 6px 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 12px;">
                                        <option value="pending" <?php echo $w['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $w['status'] == 'approved' ? 'selected' : ''; ?>>Approved/Paid</option>
                                        <option value="rejected" <?php echo $w['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                    <input type="text" name="admin_notes" placeholder="Note (optional)" style="padding:6px 10px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:6px;color:#fff;font-size:12px;width:140px;">
                                    <button type="submit" name="update_status" class="btn-process">Update</button>
                                </form>
                                <?php if ($w['admin_notes']): ?>
                                <div style="font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 4px;">
                                    Note: <?php echo htmlspecialchars($w['admin_notes']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($withdrawals->num_rows == 0): ?>
                <div style="text-align: center; padding: 60px 20px; color: rgba(255,255,255,0.5);">
                    <span class="mdi mdi-cash-off" style="font-size: 48px; margin-bottom: 15px; display: block;"></span>
                    <p>No withdrawal requests found</p>
                </div>
                <?php endif; ?>

                <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 10px; margin-top: 25px;">
                    <a href="?page=<?php echo max(1, $page-1); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       style="padding: 10px 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; <?php echo $page <= 1 ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <span class="mdi mdi-chevron-left"></span>
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>" 
                       style="padding: 10px 15px; background: <?php echo $i == $page ? 'linear-gradient(135deg, #00b7ff, #8b5cf6)' : 'rgba(255,255,255,0.1)'; ?>; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    <a href="?page=<?php echo min($total_pages, $page+1); ?>&status=<?php echo urlencode($status_filter); ?>" 
                       style="padding: 10px 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; <?php echo $page >= $total_pages ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <span class="mdi mdi-chevron-right"></span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
