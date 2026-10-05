<?php
/**
 * Admin Panel - Manage Claimable Artists
 * Admin adds artists here, users can claim them on frontend
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$success = '';
$error = '';

// Handle Add Artist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_artist'])) {
    $artistName = trim($_POST['artist_name'] ?? '');
    $spotifyUrl = trim($_POST['spotify_url'] ?? '');
    $appleMusicUrl = trim($_POST['apple_music_url'] ?? '');
    $youtubeUrl = trim($_POST['youtube_channel_url'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($artistName)) {
        $error = 'Please enter artist name';
    } else {
        // Check if artist already exists
        $checkStmt = $conn->prepare("SELECT id FROM admin_artists WHERE artist_name = ?");
        $checkStmt->bind_param("s", $artistName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Artist already exists in the list';
        } else {
            $insertStmt = $conn->prepare("INSERT INTO admin_artists (artist_name, spotify_url, apple_music_url, youtube_channel_url, notes, created_by, source) VALUES (?, ?, ?, ?, ?, ?, 'manual')");
            $insertStmt->bind_param("sssssi", $artistName, $spotifyUrl, $appleMusicUrl, $youtubeUrl, $notes, $_SESSION['admin_id']);
            
            if ($insertStmt->execute()) {
                $success = "Artist '$artistName' added successfully!";
            } else {
                $error = 'Failed to add artist: ' . $conn->error;
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $artistId = intval($_GET['toggle']);
    $newStatus = isset($_GET['status']) && $_GET['status'] == '1' ? 0 : 1;
    
    $toggleStmt = $conn->prepare("UPDATE admin_artists SET is_active = ? WHERE id = ?");
    $toggleStmt->bind_param("ii", $newStatus, $artistId);
    
    if ($toggleStmt->execute()) {
        $success = 'Artist status updated!';
    } else {
        $error = 'Failed to update status';
    }
    $toggleStmt->close();
    
    header("Location: manage_artists.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $artistId = intval($_GET['delete']);
    
    $deleteStmt = $conn->prepare("DELETE FROM admin_artists WHERE id = ?");
    $deleteStmt->bind_param("i", $artistId);
    
    if ($deleteStmt->execute()) {
        $success = 'Artist removed from list!';
    } else {
        $error = 'Failed to delete artist';
    }
    $deleteStmt->close();
    
    header("Location: manage_artists.php");
    exit;
}

// Get all admin artists
$artists = [];
$artistsStmt = $conn->prepare("SELECT * FROM admin_artists ORDER BY created_at DESC");
$artistsStmt->execute();
$artistsResult = $artistsStmt->get_result();
while ($row = $artistsResult->fetch_assoc()) {
    $artists[] = $row;
}
$artistsStmt->close();

// Get claimed counts per artist
$claimedCounts = [];
$countStmt = $conn->prepare("
    SELECT admin_artist_id, platform, COUNT(*) as count 
    FROM artist_accounts 
    WHERE admin_artist_id IS NOT NULL 
    GROUP BY admin_artist_id, platform
");
$countStmt->execute();
$countResult = $countStmt->get_result();
while ($row = $countResult->fetch_assoc()) {
    if (!isset($claimedCounts[$row['admin_artist_id']])) {
        $claimedCounts[$row['admin_artist_id']] = [];
    }
    $claimedCounts[$row['admin_artist_id']][$row['platform']] = $row['count'];
}
$countStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Claimable Artists - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: #0a0a0a; 
            color: #fff; 
            padding: 20px; 
        }
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
        
        .main-grid { display: grid; grid-template-columns: 400px 1fr; gap: 30px; }
        
        /* Add Artist Form */
        .add-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
            height: fit-content;
        }
        .add-card h2 { font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #aaa; margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #1db954;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-hint { font-size: 12px; color: #666; margin-top: 5px; }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #1db954;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .submit-btn:hover { background: #1ed760; }
        
        /* Artists List */
        .list-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }
        .list-card h2 { font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .artist-count { 
            background: #333; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 14px; 
            margin-left: auto;
        }
        
        .artists-table { width: 100%; border-collapse: collapse; }
        .artists-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
        }
        .artists-table td {
            padding: 15px;
            border-bottom: 1px solid #222;
            font-size: 14px;
        }
        .artists-table tr:hover { background: rgba(255,255,255,0.02); }
        
        .artist-name { font-weight: 500; color: #fff; }
        .artist-source { font-size: 12px; color: #666; text-transform: uppercase; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: rgba(29,185,84,0.2); color: #1db954; }
        .status-inactive { background: rgba(255,193,7,0.2); color: #ffc107; }
        
        .platform-badges { display: flex; gap: 5px; flex-wrap: wrap; }
        .platform-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        .badge-spotify { background: rgba(29,185,84,0.2); color: #1db954; }
        .badge-apple { background: rgba(250,36,60,0.2); color: #fa243c; }
        .badge-youtube { background: rgba(255,0,0,0.2); color: #ff0000; }
        .badge-count { background: #333; color: #fff; margin-left: 3px; padding: 1px 5px; border-radius: 3px; font-size: 10px; }
        
        .action-btns { display: flex; gap: 8px; }
        .action-btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-toggle { background: #333; color: #fff; }
        .btn-toggle:hover { background: #444; }
        .btn-delete { background: transparent; color: #ff5252; border: 1px solid #ff5252; }
        .btn-delete:hover { background: rgba(255,82,82,0.1); }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        
        .info-box {
            background: rgba(29,185,84,0.1);
            border: 1px solid rgba(29,185,84,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
        }
        .info-box i { color: #1db954; margin-right: 8px; }
        
        @media (max-width: 1024px) {
            .main-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="mdi mdi-account-music"></i> Manage Claimable Artists</h1>
            <a href="index.php" class="back-link"><i class="mdi mdi-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <i class="mdi mdi-information"></i>
            <strong>How it works:</strong> Artists added here will appear in user dropdowns on YouTube OAC, Spotify for Artists, and Apple Music for Artists pages. Users can claim these artists across all three platforms.
        </div>

        <div class="main-grid">
            <!-- Add Artist Form -->
            <div class="add-card">
                <h2><i class="mdi mdi-plus-circle"></i> Add New Artist</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="add_artist" value="1">
                    
                    <div class="form-group">
                        <label>Artist Name *</label>
                        <input type="text" name="artist_name" required placeholder="e.g. Arijit Singh">
                        <div class="form-hint">This exact name will appear in user dropdowns</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Spotify URL (Optional)</label>
                        <input type="url" name="spotify_url" placeholder="https://open.spotify.com/artist/...">
                    </div>
                    
                    <div class="form-group">
                        <label>Apple Music URL (Optional)</label>
                        <input type="url" name="apple_music_url" placeholder="https://music.apple.com/artist/...">
                    </div>
                    
                    <div class="form-group">
                        <label>YouTube Channel URL (Optional)</label>
                        <input type="url" name="youtube_channel_url" placeholder="https://youtube.com/channel/...">
                    </div>
                    
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" placeholder="Internal notes about this artist..."></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="mdi mdi-plus"></i> Add Artist to List
                    </button>
                </form>
            </div>
            
            <!-- Artists List -->
            <div class="list-card">
                <h2>
                    <i class="mdi mdi-account-group"></i> All Artists
                    <span class="artist-count"><?php echo count($artists); ?> total</span>
                </h2>
                
                <?php if (empty($artists)): ?>
                    <div class="empty-state">
                        <i class="mdi mdi-account-music-outline"></i>
                        <p>No artists added yet.<br>Add artists using the form on the left.</p>
                    </div>
                <?php else: ?>
                    <table class="artists-table">
                        <thead>
                            <tr>
                                <th>Artist</th>
                                <th>Status</th>
                                <th>Claims</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($artists as $artist): 
                                $claims = $claimedCounts[$artist['id']] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <div class="artist-name"><?php echo htmlspecialchars($artist['artist_name']); ?></div>
                                    <div class="artist-source"><?php echo ucfirst($artist['source']); ?></div>
                                    <?php if ($artist['notes']): ?>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            <i class="mdi mdi-note-text"></i> <?php echo htmlspecialchars(substr($artist['notes'], 0, 50)); ?><?php echo strlen($artist['notes']) > 50 ? '...' : ''; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $artist['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $artist['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="platform-badges">
                                        <?php if (isset($claims['spotify'])): ?>
                                            <span class="platform-badge badge-spotify">
                                                Spotify <span class="badge-count"><?php echo $claims['spotify']; ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($claims['apple_music'])): ?>
                                            <span class="platform-badge badge-apple">
                                                Apple <span class="badge-count"><?php echo $claims['apple_music']; ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($claims['youtube_oac'])): ?>
                                            <span class="platform-badge badge-youtube">
                                                YouTube <span class="badge-count"><?php echo $claims['youtube_oac']; ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (empty($claims)): ?>
                                            <span style="font-size: 12px; color: #666;">Not claimed yet</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($artist['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="?toggle=<?php echo $artist['id']; ?>&status=<?php echo $artist['is_active']; ?>" 
                                           class="action-btn btn-toggle" 
                                           title="<?php echo $artist['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="mdi mdi-<?php echo $artist['is_active'] ? 'pause' : 'play'; ?>"></i>
                                            <?php echo $artist['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="?delete=<?php echo $artist['id']; ?>" 
                                           class="action-btn btn-delete" 
                                           onclick="return confirm('Delete this artist? Users will no longer be able to claim it.')"
                                           title="Delete">
                                            <i class="mdi mdi-delete"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
