<?php
/**
 * web.hitune.in - Artist Profile
 * TuneCore-like artist profile feature
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$userId = $user['id'];

// Get database connection
$db = $conn;

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $artistName = trim($_POST['artist_name'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Update user profile (simplified - in production, create separate artist_profiles table)
        $updateStmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
        $updateStmt->bind_param("si", $artistName, $userId);
        if ($updateStmt->execute()) {
            $success = 'Profile updated successfully!';
            $_SESSION['name'] = $artistName;
            $user['name'] = $artistName;
        } else {
            $error = 'Failed to update profile.';
        }
        $updateStmt->close();
    }
}

// Get connected artist accounts
$accountsStmt = $db->prepare("SELECT platform, artist_name, spotify_url, apple_music_url, youtube_channel_url, is_claimed, status FROM artist_accounts WHERE user_id = ? ORDER BY platform");
$accountsStmt->bind_param("i", $userId);
$accountsStmt->execute();
$accountsResult = $accountsStmt->get_result();
$connectedAccounts = [];
while ($row = $accountsResult->fetch_assoc()) {
    $connectedAccounts[$row['platform']] = $row;
}
$accountsStmt->close();

$pageTitle = 'Artist Profile - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 40px 30px;
    }
    .profile-header {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
    }
    .profile-info {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: 700;
    }
    .profile-details h2 {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 10px;
    }
    .profile-details p {
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 5px;
    }
    .form-section {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 20px;
    }
    .form-section h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 500;
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: #fff;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #00b7ff;
    }
    .btn-save {
        padding: 12px 30px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
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
    /* Connected Accounts Styles */
    .connected-accounts {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .platform-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s;
    }
    .platform-row:hover {
        background: rgba(255,255,255,0.05);
        transform: translateX(5px);
    }
    .platform-row.connected {
        border-color: rgba(0,200,83,0.3);
        background: rgba(0,200,83,0.05);
    }
    .platform-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .platform-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        flex-shrink: 0;
    }
    .platform-icon.spotify {
        background: #1db954;
        color: #fff;
    }
    .platform-icon.apple {
        background: #fa243c;
        color: #fff;
    }
    .platform-icon.youtube {
        background: #ff0000;
        color: #fff;
    }
    .platform-details h4 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .status-badge.claimed {
        background: rgba(0,200,83,0.2);
        color: #00c853;
    }
    .status-badge.not-connected {
        background: rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.5);
    }
    .platform-url {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
        margin: 0;
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .platform-action {
        padding: 10px 20px;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        white-space: nowrap;
    }
    .platform-action:hover {
        background: rgba(255,255,255,0.2);
        border-color: #fff;
    }
    @media (max-width: 768px) {
        .platform-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        .platform-action {
            width: 100%;
            justify-content: center;
        }
        .platform-url {
            max-width: 250px;
        }
    }
</style>

<div class="profile-container">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="profile-header">
        <div class="profile-info">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
            </div>
            <div class="profile-details">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <p>Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Connected Artist Accounts Section -->
    <div class="form-section">
        <h3><i class="mdi mdi-link-variant"></i> Connected Artist Accounts</h3>
        <p style="font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 20px;">
            Manage your artist profiles on major streaming platforms.
        </p>
        
        <div class="connected-accounts">
            <!-- Spotify -->
            <div class="platform-row <?php echo isset($connectedAccounts['spotify']) ? 'connected' : ''; ?>">
                <div class="platform-info">
                    <div class="platform-icon spotify">
                        <i class="mdi mdi-spotify"></i>
                    </div>
                    <div class="platform-details">
                        <h4>Spotify for Artists</h4>
                        <?php if (isset($connectedAccounts['spotify'])): ?>
                            <span class="status-badge claimed">CLAIMED</span>
                            <p class="platform-url"><?php echo htmlspecialchars($connectedAccounts['spotify']['spotify_url'] ?? ''); ?></p>
                        <?php else: ?>
                            <span class="status-badge not-connected">Not Connected</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="/index.php?q=spotify_for_artists" class="platform-action">
                    <?php echo isset($connectedAccounts['spotify']) ? 'Manage' : 'Connect'; ?> <i class="mdi mdi-chevron-right"></i>
                </a>
            </div>
            
            <!-- Apple Music -->
            <div class="platform-row <?php echo isset($connectedAccounts['apple_music']) ? 'connected' : ''; ?>">
                <div class="platform-info">
                    <div class="platform-icon apple">
                        <i class="mdi mdi-apple"></i>
                    </div>
                    <div class="platform-details">
                        <h4>Apple Music for Artists</h4>
                        <?php if (isset($connectedAccounts['apple_music'])): ?>
                            <span class="status-badge claimed">CONNECTED</span>
                            <p class="platform-url"><?php echo htmlspecialchars($connectedAccounts['apple_music']['apple_music_url'] ?? ''); ?></p>
                        <?php else: ?>
                            <span class="status-badge not-connected">Not Connected</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="/index.php?q=apple_music_for_artists" class="platform-action">
                    <?php echo isset($connectedAccounts['apple_music']) ? 'Manage' : 'Connect'; ?> <i class="mdi mdi-chevron-right"></i>
                </a>
            </div>
            
            <!-- YouTube OAC -->
            <div class="platform-row <?php echo isset($connectedAccounts['youtube_oac']) ? 'connected' : ''; ?>">
                <div class="platform-info">
                    <div class="platform-icon youtube">
                        <i class="mdi mdi-youtube"></i>
                    </div>
                    <div class="platform-details">
                        <h4>YouTube Official Artist Channel</h4>
                        <?php if (isset($connectedAccounts['youtube_oac'])): ?>
                            <span class="status-badge claimed">ELIGIBLE</span>
                            <?php if ($connectedAccounts['youtube_oac']['youtube_channel_url']): ?>
                                <p class="platform-url"><?php echo htmlspecialchars($connectedAccounts['youtube_oac']['youtube_channel_url']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-badge not-connected">Check Eligibility</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="/index.php?q=youtube_oac" class="platform-action">
                    <?php echo isset($connectedAccounts['youtube_oac']) ? 'Manage' : 'Check'; ?> <i class="mdi mdi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3>Artist Information</h3>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-group">
                <label>Artist Name</label>
                <input type="text" name="artist_name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Genre</label>
                <select name="genre">
                    <option value="">Select Genre</option>
                    <option value="pop">Pop</option>
                    <option value="rock">Rock</option>
                    <option value="hiphop">Hip Hop</option>
                    <option value="electronic">Electronic</option>
                    <option value="rnb">R&B</option>
                    <option value="country">Country</option>
                    <option value="jazz">Jazz</option>
                    <option value="classical">Classical</option>
                </select>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4" placeholder="Tell us about yourself..."></textarea>
            </div>
            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>

    <div class="form-section">
        <h3>Social Links</h3>
        <form method="POST">
            <div class="form-group">
                <label><i class="mdi mdi-spotify" style="color: #1db954;"></i> Spotify Artist URL</label>
                <input type="url" name="spotify_url" placeholder="https://open.spotify.com/artist/..." value="<?php echo htmlspecialchars($connectedAccounts['spotify']['spotify_url'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="mdi mdi-apple" style="color: #fa243c;"></i> Apple Music Artist URL</label>
                <input type="url" name="apple_url" placeholder="https://music.apple.com/..." value="<?php echo htmlspecialchars($connectedAccounts['apple_music']['apple_music_url'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="mdi mdi-youtube" style="color: #ff0000;"></i> YouTube Channel URL</label>
                <input type="url" name="youtube_url" placeholder="https://youtube.com/..." value="<?php echo htmlspecialchars($connectedAccounts['youtube_oac']['youtube_channel_url'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="mdi mdi-instagram" style="color: #e4405f;"></i> Instagram URL</label>
                <input type="url" name="instagram_url" placeholder="https://instagram.com/...">
            </div>
            <button type="submit" class="btn-save">Save Links</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
