<?php
/**
 * Admin Panel - User Artist Requests Management
 * Approve/Reject user artist requests
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$success = '';
$error = '';

// Handle Approve
if (isset($_GET['approve'])) {
    $requestId = intval($_GET['approve']);
    
    // Get the user artist request
    $getStmt = $conn->prepare("SELECT * FROM user_artists WHERE id = ?");
    $getStmt->bind_param("i", $requestId);
    $getStmt->execute();
    $result = $getStmt->get_result();
    $userArtist = $result->fetch_assoc();
    $getStmt->close();
    
    if ($userArtist && $userArtist['status'] === 'pending') {
        // Add to admin_artists table
        $insertAdminStmt = $conn->prepare("INSERT INTO admin_artists (artist_name, spotify_url, apple_music_url, youtube_channel_url, source, source_id, created_by) VALUES (?, ?, ?, ?, 'user_request', ?, ?) ON DUPLICATE KEY UPDATE is_active = 1");
        $adminId = 1; // Default admin ID
        $insertAdminStmt->bind_param("ssssii", $userArtist['artist_name'], $userArtist['spotify_url'], $userArtist['apple_music_url'], $userArtist['youtube_channel_url'], $userArtist['id'], $adminId);
        $insertAdminStmt->execute();
        $adminArtistId = $conn->insert_id;
        $insertAdminStmt->close();
        
        // Update user_artists status
        $updateStmt = $conn->prepare("UPDATE user_artists SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
        $adminId = 1; // Default admin ID
        $updateStmt->bind_param("ii", $adminId, $requestId);
        
        if ($updateStmt->execute()) {
            $success = 'Artist request approved! User can now claim this artist.';
        } else {
            $error = 'Failed to approve request';
        }
        $updateStmt->close();
    }
    
    header("Location: user_artists.php");
    exit;
}

// Handle Reject
if (isset($_GET['reject'])) {
    $requestId = intval($_GET['reject']);
    $reason = isset($_GET['reason']) ? trim($_GET['reason']) : '';
    
    $updateStmt = $conn->prepare("UPDATE user_artists SET status = 'rejected', admin_notes = ? WHERE id = ?");
    $updateStmt->bind_param("si", $reason, $requestId);
    
    if ($updateStmt->execute()) {
        $success = 'Artist request rejected.';
    } else {
        $error = 'Failed to reject request';
    }
    $updateStmt->close();
    
    header("Location: user_artists.php");
    exit;
}

// Get all user artist requests
$requests = [];
$statusFilter = $_GET['status'] ?? 'all';

$sql = "SELECT ua.*, u.name as user_name, u.email as user_email 
        FROM user_artists ua 
        JOIN users u ON ua.user_id = u.id";

if ($statusFilter !== 'all') {
    $sql .= " WHERE ua.status = ?";
}
$sql .= " ORDER BY ua.requested_at DESC";

$stmt = $conn->prepare($sql);
if ($statusFilter !== 'all') {
    $stmt->bind_param("s", $statusFilter);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

// Get counts
$counts = [
    'pending' => $conn->query("SELECT COUNT(*) as count FROM user_artists WHERE status = 'pending'")->fetch_assoc()['count'],
    'approved' => $conn->query("SELECT COUNT(*) as count FROM user_artists WHERE status = 'approved'")->fetch_assoc()['count'],
    'rejected' => $conn->query("SELECT COUNT(*) as count FROM user_artists WHERE status = 'rejected'")->fetch_assoc()['count'],
    'total' => $conn->query("SELECT COUNT(*) as count FROM user_artists")->fetch_assoc()['count']
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Artist Requests - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        .header h1 { font-size: 28px; display: flex; align-items: center; gap: 10px; }
        .back-link { color: #1db954; text-decoration: none; display: flex; align-items: center; gap: 5px; }
        .back-link:hover { text-decoration: underline; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(29,185,84,0.2); border: 1px solid #1db954; color: #1db954; }
        .alert-error { background: rgba(255,82,82,0.2); border: 1px solid #ff5252; color: #ff5252; }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card.pending h3 { color: #ffc107; }
        .stat-card.approved h3 { color: #1db954; }
        .stat-card.rejected h3 { color: #ff5252; }
        .stat-card p { font-size: 13px; color: #888; }
        
        /* Filter */
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        .filter-btn {
            padding: 10px 20px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .filter-btn:hover { background: #252525; }
        .filter-btn.active {
            background: #1db954;
            color: #000;
            border-color: #1db954;
        }
        
        /* Table */
        .requests-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }
        .requests-table { width: 100%; border-collapse: collapse; }
        .requests-table th {
            text-align: left;
            padding: 15px;
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
        }
        .requests-table td {
            padding: 15px;
            border-bottom: 1px solid #222;
            font-size: 14px;
        }
        .requests-table tr:hover { background: rgba(255,255,255,0.02); }
        
        .artist-info { display: flex; flex-direction: column; gap: 5px; }
        .artist-name { font-weight: 500; color: #fff; }
        .artist-urls { font-size: 12px; color: #666; }
        .artist-urls a { color: #1db954; text-decoration: none; }
        .artist-urls a:hover { text-decoration: underline; }
        
        .user-info { display: flex; flex-direction: column; gap: 3px; }
        .user-name { font-weight: 500; }
        .user-email { font-size: 12px; color: #888; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
        .status-approved { background: rgba(29,185,84,0.2); color: #1db954; }
        .status-rejected { background: rgba(255,82,82,0.2); color: #ff5252; }
        
        .date-info { font-size: 13px; color: #888; }
        
        .action-btns { display: flex; gap: 8px; }
        .action-btn {
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-approve {
            background: #1db954;
            color: #000;
        }
        .btn-approve:hover { background: #1ed760; }
        .btn-reject {
            background: transparent;
            color: #ff5252;
            border: 1px solid #ff5252;
        }
        .btn-reject:hover { background: rgba(255,82,82,0.1); }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        
        .admin-notes {
            font-size: 12px;
            color: #ff5252;
            margin-top: 5px;
        }
        
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="mdi mdi-account-music"></i> User Artist Requests</h1>
            <a href="index.php" class="back-link"><i class="mdi mdi-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <h3><?php echo $counts['pending']; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card approved">
                <h3><?php echo $counts['approved']; ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card rejected">
                <h3><?php echo $counts['rejected']; ?></h3>
                <p>Rejected</p>
            </div>
            <div class="stat-card">
                <h3 style="color: #fff;"><?php echo $counts['total']; ?></h3>
                <p>Total</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-bar">
            <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=approved" class="filter-btn <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status=rejected" class="filter-btn <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>
        
        <!-- Requests Table -->
        <div class="requests-card">
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="mdi mdi-account-music-outline"></i>
                    <p>No artist requests found.</p>
                </div>
            <?php else: ?>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Artist</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <div class="artist-info">
                                    <span class="artist-name"><?php echo htmlspecialchars($req['artist_name']); ?></span>
                                    <div class="artist-urls">
                                        <?php if ($req['spotify_url']): ?>
                                            <a href="<?php echo htmlspecialchars($req['spotify_url']); ?>" target="_blank"><i class="mdi mdi-spotify"></i> Spotify</a>
                                        <?php endif; ?>
                                        <?php if ($req['apple_music_url']): ?>
                                            <a href="<?php echo htmlspecialchars($req['apple_music_url']); ?>" target="_blank"><i class="mdi mdi-apple"></i> Apple</a>
                                        <?php endif; ?>
                                        <?php if ($req['youtube_channel_url']): ?>
                                            <a href="<?php echo htmlspecialchars($req['youtube_channel_url']); ?>" target="_blank"><i class="mdi mdi-youtube"></i> YouTube</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-info">
                                    <span class="user-name"><?php echo htmlspecialchars($req['user_name']); ?></span>
                                    <span class="user-email"><?php echo htmlspecialchars($req['user_email']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $req['status']; ?>">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                                <?php if ($req['admin_notes']): ?>
                                    <div class="admin-notes"><?php echo htmlspecialchars($req['admin_notes']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="date-info">
                                    <div>Requested: <?php echo date('M d, Y', strtotime($req['requested_at'])); ?></div>
                                    <?php if ($req['approved_at']): ?>
                                        <div>Approved: <?php echo date('M d, Y', strtotime($req['approved_at'])); ?></div>
                                        <div style="font-size: 11px; color: #666;">by Admin</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <div class="action-btns">
                                        <a href="?approve=<?php echo $req['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Approve this artist request?')">
                                            <i class="mdi mdi-check"></i> Approve
                                        </a>
                                        <a href="?reject=<?php echo $req['id']; ?>&reason=Artist+not+found" class="action-btn btn-reject" onclick="return confirm('Reject this request?')">
                                            <i class="mdi mdi-close"></i> Reject
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
