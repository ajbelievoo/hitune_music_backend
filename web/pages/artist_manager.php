<?php
/**
 * User Artist Manager
 * Users can request artists to be added for claiming
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$userId = $user['id'];

$db = $conn;

$success = '';
$error = '';

// Handle Add Artist Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_artist'])) {
    $artistName = trim($_POST['artist_name'] ?? '');
    $spotifyUrl = trim($_POST['spotify_url'] ?? '');
    $appleMusicUrl = trim($_POST['apple_music_url'] ?? '');
    $youtubeUrl = trim($_POST['youtube_channel_url'] ?? '');
    
    if (empty($artistName)) {
        $error = 'Please enter artist name';
    } else {
        // Check if user already requested this artist
        $checkStmt = $db->prepare("SELECT id FROM user_artists WHERE user_id = ? AND artist_name = ?");
        $checkStmt->bind_param("is", $userId, $artistName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'You have already requested this artist';
        } else {
            $insertStmt = $db->prepare("INSERT INTO user_artists (user_id, artist_name, spotify_url, apple_music_url, youtube_channel_url, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $insertStmt->bind_param("issss", $userId, $artistName, $spotifyUrl, $appleMusicUrl, $youtubeUrl);
            
            if ($insertStmt->execute()) {
                $success = "Artist '$artistName' requested successfully! Waiting for admin approval.";
            } else {
                $error = 'Failed to request artist: ' . $db->error;
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
}

// Get user's artists
$userArtists = [];
$artistsStmt = $db->prepare("SELECT * FROM user_artists WHERE user_id = ? ORDER BY requested_at DESC");
$artistsStmt->bind_param("i", $userId);
$artistsStmt->execute();
$artistsResult = $artistsStmt->get_result();
while ($row = $artistsResult->fetch_assoc()) {
    $userArtists[] = $row;
}
$artistsStmt->close();

// Get claim status for each approved artist
$claimedArtists = [];
$claimStmt = $db->prepare("SELECT admin_artist_id, platform FROM artist_accounts WHERE user_id = ?");
$claimStmt->bind_param("i", $userId);
$claimStmt->execute();
$claimResult = $claimStmt->get_result();
while ($row = $claimResult->fetch_assoc()) {
    $claimedArtists[$row['admin_artist_id']] = $claimedArtists[$row['admin_artist_id']] ?? [];
    $claimedArtists[$row['admin_artist_id']][] = $row['platform'];
}
$claimStmt->close();

$db->close();

$pageTitle = 'Artist Manager - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .artist-manager-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 100px 30px 60px;
    }
    
    .manager-header {
        margin-bottom: 40px;
    }
    .manager-header h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .manager-header p {
        font-size: 15px;
        color: rgba(255,255,255,0.6);
    }
    
    .main-grid {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 30px;
    }
    
    /* Request Form */
    .request-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 30px;
        height: fit-content;
    }
    .request-card h2 {
        font-size: 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-group { margin-bottom: 20px; }
    .form-group label {
        display: block;
        font-size: 12px;
        color: #aaa;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .form-group input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
    }
    .form-group input:focus {
        outline: none;
        border-color: #00b7ff;
    }
    .form-hint {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    .submit-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,183,255,0.3);
    }
    
    /* Artists List */
    .artists-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 30px;
    }
    .artists-card h2 {
        font-size: 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .artists-count {
        background: #333;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        margin-left: auto;
    }
    
    .artist-item {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    .artist-item:hover {
        border-color: rgba(255,255,255,0.2);
    }
    .artist-item.approved {
        border-color: rgba(29,185,84,0.3);
        background: rgba(29,185,84,0.05);
    }
    .artist-item.pending {
        border-color: rgba(255,193,7,0.3);
        background: rgba(255,193,7,0.05);
    }
    
    .artist-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    .artist-name {
        font-size: 18px;
        font-weight: 600;
        flex: 1;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    .status-approved { background: rgba(29,185,84,0.2); color: #1db954; }
    .status-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
    .status-rejected { background: rgba(255,82,82,0.2); color: #ff5252; }
    
    .artist-meta {
        display: flex;
        gap: 20px;
        font-size: 13px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 15px;
    }
    .artist-meta i { margin-right: 5px; }
    
    .claim-section {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .claim-btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .claim-btn.youtube {
        background: rgba(255,0,0,0.15);
        color: #ff0000;
        border: 1px solid rgba(255,0,0,0.3);
    }
    .claim-btn.youtube:hover { background: rgba(255,0,0,0.25); }
    .claim-btn.youtube.claimed {
        background: rgba(255,0,0,0.3);
        opacity: 0.7;
        cursor: default;
    }
    .claim-btn.spotify {
        background: rgba(29,185,84,0.15);
        color: #1db954;
        border: 1px solid rgba(29,185,84,0.3);
    }
    .claim-btn.spotify:hover { background: rgba(29,185,84,0.25); }
    .claim-btn.spotify.claimed {
        background: rgba(29,185,84,0.3);
        opacity: 0.7;
        cursor: default;
    }
    .claim-btn.apple {
        background: rgba(250,36,60,0.15);
        color: #fa243c;
        border: 1px solid rgba(250,36,60,0.3);
    }
    .claim-btn.apple:hover { background: rgba(250,36,60,0.25); }
    .claim-btn.apple.claimed {
        background: rgba(250,36,60,0.3);
        opacity: 0.7;
        cursor: default;
    }
    
    .pending-message {
        font-size: 13px;
        color: rgba(255,193,7,0.8);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        font-size: 14px;
    }
    .alert-success {
        background: rgba(29,185,84,0.15);
        border: 1px solid rgba(29,185,84,0.3);
        color: #1db954;
    }
    .alert-error {
        background: rgba(255,82,82,0.15);
        border: 1px solid rgba(255,82,82,0.3);
        color: #ff5252;
    }
    
    .info-box {
        background: rgba(29,185,84,0.1);
        border: 1px solid rgba(29,185,84,0.2);
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 25px;
        font-size: 14px;
        color: rgba(255,255,255,0.8);
        line-height: 1.6;
    }
    .info-box i {
        color: #1db954;
        margin-right: 8px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    @media (max-width: 1024px) {
        .main-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="artist-manager-container">
    <div class="manager-header">
        <h1>
            <i class="mdi mdi-account-music" style="color: #00b7ff;"></i>
            Artist Manager
        </h1>
        <p>Request artists to be added for claiming on YouTube, Spotify, and Apple Music</p>
    </div>
    
    <div class="info-box">
        <i class="mdi mdi-information"></i>
        <strong>How it works:</strong> Add your artist details below. Once admin approves your request, you can claim the artist on YouTube OAC, Spotify for Artists, and Apple Music for Artists. Approval usually takes 1-2 business days.
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="main-grid">
        <!-- Request Form -->
        <div class="request-card">
            <h2><i class="mdi mdi-plus-circle"></i> Request New Artist</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="request_artist" value="1">
                
                <div class="form-group">
                    <label>Artist Name *</label>
                    <input type="text" name="artist_name" required placeholder="e.g. Arijit Singh">
                    <div class="form-hint">Enter the exact artist name as it appears on streaming platforms</div>
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
                
                <button type="submit" class="submit-btn">
                    <i class="mdi mdi-send"></i> Submit Request
                </button>
            </form>
        </div>
        
        <!-- My Artists List -->
        <div class="artists-card">
            <h2>
                <i class="mdi mdi-account-group"></i> My Artists
                <span class="artists-count"><?php echo count($userArtists); ?> total</span>
            </h2>
            
            <?php if (empty($userArtists)): ?>
                <div class="empty-state">
                    <i class="mdi mdi-account-music-outline"></i>
                    <p>No artists yet.<br>Use the form to request your first artist.</p>
                </div>
            <?php else: ?>
                <?php foreach ($userArtists as $artist): 
                    $isClaimed = isset($claimedArtists[$artist['id']]) ? $claimedArtists[$artist['id']] : [];
                    $isYoutubeClaimed = in_array('youtube_oac', $isClaimed);
                    $isSpotifyClaimed = in_array('spotify', $isClaimed);
                    $isAppleClaimed = in_array('apple_music', $isClaimed);
                ?>
                <div class="artist-item <?php echo $artist['status']; ?>">
                    <div class="artist-header">
                        <div class="artist-name"><?php echo htmlspecialchars($artist['artist_name']); ?></div>
                        <span class="status-badge status-<?php echo $artist['status']; ?>">
                            <?php echo ucfirst($artist['status']); ?>
                        </span>
                    </div>
                    
                    <div class="artist-meta">
                        <span><i class="mdi mdi-calendar"></i> Requested: <?php echo date('M d, Y', strtotime($artist['requested_at'])); ?></span>
                        <?php if ($artist['approved_at']): ?>
                            <span><i class="mdi mdi-check-circle"></i> Approved: <?php echo date('M d, Y', strtotime($artist['approved_at'])); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($artist['status'] === 'approved'): ?>
                        <div class="claim-section">
                            <a href="/index.php?q=youtube_oac" class="claim-btn youtube <?php echo $isYoutubeClaimed ? 'claimed' : ''; ?>">
                                <i class="mdi mdi-youtube"></i>
                                <?php echo $isYoutubeClaimed ? 'YouTube Claimed' : 'Claim on YouTube'; ?>
                            </a>
                            <a href="/index.php?q=spotify_for_artists" class="claim-btn spotify <?php echo $isSpotifyClaimed ? 'claimed' : ''; ?>">
                                <i class="mdi mdi-spotify"></i>
                                <?php echo $isSpotifyClaimed ? 'Spotify Claimed' : 'Claim on Spotify'; ?>
                            </a>
                            <a href="/index.php?q=apple_music_for_artists" class="claim-btn apple <?php echo $isAppleClaimed ? 'claimed' : ''; ?>">
                                <i class="mdi mdi-apple"></i>
                                <?php echo $isAppleClaimed ? 'Apple Music Claimed' : 'Claim on Apple Music'; ?>
                            </a>
                        </div>
                    <?php elseif ($artist['status'] === 'pending'): ?>
                        <div class="pending-message">
                            <i class="mdi mdi-clock-outline"></i>
                            Waiting for admin approval. You'll be able to claim this artist once approved.
                        </div>
                    <?php elseif ($artist['status'] === 'rejected'): ?>
                        <div class="pending-message" style="color: #ff5252;">
                            <i class="mdi mdi-close-circle"></i>
                            Request rejected. <?php echo $artist['admin_notes'] ? 'Reason: ' . htmlspecialchars($artist['admin_notes']) : ''; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
