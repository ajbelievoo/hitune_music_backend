<?php
/**
 * web.hitune.in Admin Panel - Users Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Handle search
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = "WHERE name LIKE ? OR email LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term];
    $types = 'ss';
}

// Get total count
$count_sql = "SELECT COUNT(*) as count FROM users $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $per_page);

// Get users with pagination
$sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
$users = $stmt->get_result();

// Get stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$new_today = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$active_releases = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM releases WHERE status = 'ready'")->fetch_assoc()['count'];

// Handle delete
if (isset($_POST['delete_user'])) {
    $delete_id = intval($_POST['delete_user']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        header('Location: users.php?deleted=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Panel</title>
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
        .content-section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
        }
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        .search-bar input {
            flex: 1;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
        }
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
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
        .action-btn {
            padding: 6px 12px;
            background: rgba(0, 183, 255, 0.2);
            border: 1px solid rgba(0, 183, 255, 0.3);
            border-radius: 6px;
            color: #00b7ff;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
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
                <h1><span class="mdi mdi-account-group"></span> Users Management</h1>
                <a href="index.php" class="btn"><span class="mdi mdi-view-dashboard"></span> Dashboard</a>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
            <div style="background: rgba(255,82,82,0.2); border: 1px solid rgba(255,82,82,0.3); border-radius: 10px; padding: 15px; margin-bottom: 25px; color: #ff5252; display: flex; align-items: center; gap: 10px;">
                <span class="mdi mdi-check-circle"></span> User deleted successfully
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 32px; font-weight: 700; color: #fff; margin-bottom: 5px;"><?php echo number_format($total_users); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">Total Users</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 32px; font-weight: 700; color: #00c853; margin-bottom: 5px;"><?php echo number_format($new_today); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">New Today</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; text-align: center;">
                    <h3 style="font-size: 32px; font-weight: 700; color: #4facfe; margin-bottom: 5px;"><?php echo number_format($active_releases); ?></h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6);">Active Artists</p>
                </div>
            </div>

            <div class="content-section">
                <form method="GET" class="search-bar">
                    <input type="text" name="search" placeholder="Search users by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn"><span class="mdi mdi-magnify"></span> Search</button>
                    <?php if ($search): ?>
                    <a href="users.php" class="btn" style="background: rgba(255,255,255,0.1);"><span class="mdi mdi-refresh"></span> Clear</a>
                    <?php endif; ?>
                </form>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="user_view.php?id=<?php echo $user['id']; ?>" class="action-btn" title="View"><span class="mdi mdi-eye"></span></a>
                                <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="action-btn" title="Edit" style="background: rgba(79,172,254,0.2); border-color: rgba(79,172,254,0.3); color: #4facfe;"><span class="mdi mdi-pencil"></span></a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="delete_user" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="action-btn" title="Delete" style="background: rgba(255,82,82,0.2); border-color: rgba(255,82,82,0.3); color: #ff5252; cursor: pointer;">
                                        <span class="mdi mdi-delete"></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 10px; margin-top: 25px;">
                    <a href="?page=<?php echo max(1, $page-1); ?>&search=<?php echo urlencode($search); ?>" 
                       style="padding: 10px 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; <?php echo $page <= 1 ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <span class="mdi mdi-chevron-left"></span>
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       style="padding: 10px 15px; background: <?php echo $i == $page ? 'linear-gradient(135deg, #00b7ff, #8b5cf6)' : 'rgba(255,255,255,0.1)'; ?>; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    <a href="?page=<?php echo min($total_pages, $page+1); ?>&search=<?php echo urlencode($search); ?>" 
                       style="padding: 10px 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; <?php echo $page >= $total_pages ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                        <span class="mdi mdi-chevron-right"></span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($users->num_rows == 0): ?>
                <div style="text-align: center; padding: 60px 20px; color: rgba(255,255,255,0.5);">
                    <span class="mdi mdi-account-off" style="font-size: 48px; margin-bottom: 15px; display: block;"></span>
                    <p>No users found</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
