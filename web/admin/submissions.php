<?php
/**
 * HiTune Music Distribution - Admin Releases Management
 * Full workflow: draft → submitted → in_progress → ready
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_helper.php';
require_once __DIR__ . '/../includes/code_assign.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Handle status update with workflow timestamps
if (isset($_POST['update_status'])) {
    $release_id = intval($_POST['release_id']);
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $allowed_statuses = ['draft', 'submitted', 'in_progress', 'ready', 'live', 'rejected', 'takedown_requested', 'taken_down'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        header('Location: submissions.php?error=invalid_status');
        exit;
    }
    
    // Build update query with timestamps
    $timestamp_fields = [];
    if ($new_status === 'submitted') {
        $timestamp_fields[] = "submitted_at = NOW()";
    } elseif ($new_status === 'in_progress') {
        $timestamp_fields[] = "in_progress_at = NOW()";
    } elseif ($new_status === 'ready') {
        $timestamp_fields[] = "ready_at = NOW()";
    } elseif ($new_status === 'live') {
        $timestamp_fields[] = "live_at = NOW()";
    } elseif ($new_status === 'taken_down') {
        $timestamp_fields[] = "taken_down_at = NOW()";
    } elseif ($new_status === 'rejected') {
        $timestamp_fields[] = "rejected_at = NOW()";
    }

    $sql = "UPDATE releases SET status = ?, admin_notes = ?";
    if (!empty($timestamp_fields)) {
        $sql .= ", " . implode(", ", $timestamp_fields);
    }
    $sql .= " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_status, $admin_notes, $release_id);
    $stmt->execute();

    // Log activity
    $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, 0, 'Status Updated', ?, ?, 'admin')");
    $stmt->bind_param("iss", $release_id, $new_status, $admin_notes);
    $stmt->execute();

    // Auto-assign ISRC/UPC when a release is approved for delivery
    if (in_array($new_status, ['ready', 'live'], true)) {
        assignReleaseCodes($release_id);
    }

    // Notify the artist by email
    $ustmt = $conn->prepare("SELECT u.email, u.name, r.title, r.status FROM releases r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $ustmt->bind_param("i", $release_id);
    $ustmt->execute();
    if ($urow = $ustmt->get_result()->fetch_assoc()) {
        sendReleaseStatusEmail($urow['email'], $urow['name'], $urow['title'], $new_status, $admin_notes);
    }
    $ustmt->close();

    header('Location: submissions.php?updated=1');
    exit;
}

// Get all releases with user info
$releases = $conn->query("SELECT r.*, u.name as user_name, u.email as user_email 
    FROM releases r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY 
        CASE r.status 
            WHEN 'takedown_requested' THEN 0
            WHEN 'submitted' THEN 1 
            WHEN 'in_progress' THEN 2 
            WHEN 'ready' THEN 3 
            WHEN 'rejected' THEN 4 
            WHEN 'live' THEN 5
            WHEN 'taken_down' THEN 6
            WHEN 'draft' THEN 7 
        END, 
        r.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Management - Admin Panel</title>
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-draft { background: rgba(255, 255, 255, 0.1); color: rgba(255,255,255,0.7); }
        .status-submitted { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-in_progress { background: rgba(79, 172, 254, 0.2); color: #4facfe; }
        .status-ready { background: rgba(0, 200, 83, 0.2); color: #00c853; }
        .status-live { background: rgba(0, 212, 170, 0.25); color: #00d4aa; }
        .status-takedown_requested { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .status-taken_down { background: rgba(158, 158, 158, 0.2); color: #9e9e9e; }
        .status-rejected { background: rgba(255, 82, 82, 0.2); color: #ff5252; }
        select, textarea {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
        }
        textarea {
            width: 100%;
            min-height: 60px;
            resize: vertical;
        }
        .btn-update {
            padding: 8px 15px;
            background: #38ef7d;
            border: none;
            border-radius: 6px;
            color: #000;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-view {
            padding: 8px 15px;
            background: rgba(0, 212, 170, 0.2);
            border: none;
            border-radius: 6px;
            color: #00d4aa;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .action-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .action-form .row {
            display: flex;
            gap: 5px;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
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
                <a href="submissions.php" class="nav-item active"><span class="mdi mdi-music"></span>Releases</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="analytics.php" class="nav-item"><span class="mdi mdi-chart-line"></span>Analytics</a>
                <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Releases Management</h1>
                <a href="index.php" class="btn">Back to Dashboard</a>
            </div>
            
            <?php
            // Get stats
            $stats = [
                'total' => $conn->query("SELECT COUNT(*) as count FROM releases")->fetch_assoc()['count'],
                'submitted' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'submitted'")->fetch_assoc()['count'],
                'in_progress' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'in_progress'")->fetch_assoc()['count'],
                'ready' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'ready'")->fetch_assoc()['count'],
                'rejected' => $conn->query("SELECT COUNT(*) as count FROM releases WHERE status = 'rejected'")->fetch_assoc()['count'],
            ];
            ?>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3 style="color: #fff;"><?php echo $stats['total']; ?></h3>
                    <p>Total Releases</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #ffc107;"><?php echo $stats['submitted']; ?></h3>
                    <p>Pending Review</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #4facfe;"><?php echo $stats['in_progress']; ?></h3>
                    <p>In Progress</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #00c853;"><?php echo $stats['ready']; ?></h3>
                    <p>Published</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #ff5252;"><?php echo $stats['rejected']; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
            <div style="background: rgba(0,200,83,0.2); border: 1px solid #00c853; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #00c853;">
                <i class="mdi mdi-check-circle"></i> Status updated successfully!
            </div>
            <?php endif; ?>

            <div class="content-section">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Type</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($release = $releases->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $release['id']; ?></td>
                            <td><?php echo htmlspecialchars($release['title']); ?></td>
                            <td><?php echo htmlspecialchars($release['primary_artist']); ?></td>
                            <td><?php echo ucfirst($release['release_type']); ?></td>
                            <td><?php echo htmlspecialchars($release['user_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $release['status']; ?>">
                                    <i class="mdi mdi-<?php 
                                        echo $release['status'] === 'draft' ? 'pencil' : 
                                            ($release['status'] === 'submitted' ? 'send' : 
                                            ($release['status'] === 'in_progress' ? 'progress-clock' : 
                                            ($release['status'] === 'ready' ? 'check-circle' : 'close-circle'))); 
                                    ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $release['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $release['submitted_at'] ? date('M j, Y', strtotime($release['submitted_at'])) : '-'; ?></td>
                            <td>
                                <a href="release_detail.php?id=<?php echo $release['id']; ?>" class="btn-view" style="display: inline-flex; align-items: center; gap: 5px; padding: 8px 15px; background: rgba(0,212,170,0.2); color: #00d4aa; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; margin-bottom: 10px;">
                                    <span class="mdi mdi-eye"></span> View Full Details
                                </a>
                                <form method="POST" class="action-form" style="margin-top: 8px;">
                                    <input type="hidden" name="release_id" value="<?php echo $release['id']; ?>">
                                    <div class="row">
                                        <select name="status">
                                            <option value="draft" <?php echo $release['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="submitted" <?php echo $release['status'] == 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                            <option value="in_progress" <?php echo $release['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="ready" <?php echo $release['status'] == 'ready' ? 'selected' : ''; ?>>Ready/Published</option>
                                            <option value="live" <?php echo $release['status'] == 'live' ? 'selected' : ''; ?>>Live on Stores</option>
                                            <option value="takedown_requested" <?php echo $release['status'] == 'takedown_requested' ? 'selected' : ''; ?>>Takedown Requested</option>
                                            <option value="taken_down" <?php echo $release['status'] == 'taken_down' ? 'selected' : ''; ?>>Taken Down</option>
                                            <option value="rejected" <?php echo $release['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">Update</button>
                                    </div>
                                    <?php if ($release['status'] === 'submitted' || $release['status'] === 'rejected'): ?>
                                    <textarea name="admin_notes" placeholder="Add notes for user (visible in their dashboard)..."><?php echo htmlspecialchars($release['admin_notes'] ?? ''); ?></textarea>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
