<?php
/**
 * HiTune Music Distribution - Spotify for Artists
 * TuneCore-like Spotify for Artists feature
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$userId = $user['id'];

// Get database connection
$db = $conn;

// Get preview image from settings
$previewImage = '';
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'spotify_preview_image' AND setting_group = 'artist_previews'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $previewImage = $result->fetch_assoc()['setting_value'];
}
$stmt->close();

// Get user's APPROVED artists from user_artists table
// User must request artist first in Artist Manager, admin approves, then it appears here
$userArtistsStmt = $db->prepare("
    SELECT ua.*, aa.id as admin_artist_id 
    FROM user_artists ua 
    LEFT JOIN admin_artists aa ON ua.artist_name = aa.artist_name 
    WHERE ua.user_id = ? AND ua.status = 'approved' 
    ORDER BY ua.artist_name
");
$userArtistsStmt->bind_param("i", $userId);
$userArtistsStmt->execute();
$artistsResult = $userArtistsStmt->get_result();
$artists = [];
$adminArtistMap = []; // Map artist_name to admin_artist_id
while ($row = $artistsResult->fetch_assoc()) {
    $artists[] = $row['artist_name'];
    $adminArtistMap[$row['artist_name']] = $row['admin_artist_id'];
}
$userArtistsStmt->close();

// Get existing Spotify artist account (joined with user_artists)
$spotifyAccount = null;
$accountStmt = $db->prepare("
    SELECT aa.*, ua.artist_name as user_artist_name 
    FROM artist_accounts aa 
    LEFT JOIN user_artists ua ON aa.user_id = ua.user_id AND aa.artist_name = ua.artist_name 
    WHERE aa.user_id = ? AND aa.platform = 'spotify' AND aa.status = 'claimed'
    LIMIT 1
");
$accountStmt->bind_param("i", $userId);
$accountStmt->execute();
$accountResult = $accountStmt->get_result();
if ($accountResult->num_rows > 0) {
    $spotifyAccount = $accountResult->fetch_assoc();
}
$accountStmt->close();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['claim_artist'])) {
        $artistName = trim($_POST['artist_name'] ?? '');
        $adminArtistId = intval($_POST['admin_artist_id'] ?? 0);
        
        if (empty($artistName)) {
            $error = 'Please select an artist name';
        } elseif ($adminArtistId === 0) {
            $error = 'Invalid artist selection';
        } else {
            // Get admin artist details for Spotify URL if available
            $adminStmt = $db->prepare("SELECT spotify_url FROM admin_artists WHERE id = ?");
            $adminStmt->bind_param("i", $adminArtistId);
            $adminStmt->execute();
            $adminResult = $adminStmt->get_result();
            $adminArtist = $adminResult->fetch_assoc();
            $adminStmt->close();
            
            // Use admin's Spotify URL or generate one
            $spotifyUrl = $adminArtist['spotify_url'] ?? ('https://open.spotify.com/artist/' . bin2hex(random_bytes(11)));
            $spotifyUri = 'spotify:artist:' . substr($spotifyUrl, strrpos($spotifyUrl, '/') + 1);
            
            if ($spotifyAccount) {
                // Update existing
                $updateStmt = $db->prepare("UPDATE artist_accounts SET admin_artist_id = ?, artist_name = ?, spotify_url = ?, spotify_uri = ?, status = 'claimed', is_claimed = 1, claimed_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("isssi", $adminArtistId, $artistName, $spotifyUrl, $spotifyUri, $spotifyAccount['id']);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Insert new
                $insertStmt = $db->prepare("INSERT INTO artist_accounts (user_id, admin_artist_id, platform, artist_name, spotify_url, spotify_uri, is_claimed, status, claimed_at) VALUES (?, ?, 'spotify', ?, ?, ?, 1, 'claimed', NOW())");
                $insertStmt->bind_param("iisss", $userId, $adminArtistId, $artistName, $spotifyUrl, $spotifyUri);
                $insertStmt->execute();
                $insertStmt->close();
            }
            
            $success = 'Artist claimed successfully! Your Spotify for Artists profile is now active.';
            
            // Refresh account data
            $accountStmt = $db->prepare("
                SELECT aa.*, ua.artist_name as user_artist_name 
                FROM artist_accounts aa 
                LEFT JOIN user_artists ua ON aa.user_id = ua.user_id AND aa.artist_name = ua.artist_name 
                WHERE aa.user_id = ? AND aa.platform = 'spotify' AND aa.status = 'claimed'
                LIMIT 1
            ");
            $accountStmt->bind_param("i", $userId);
            $accountStmt->execute();
            $accountResult = $accountStmt->get_result();
            $spotifyAccount = $accountResult->fetch_assoc();
            $accountStmt->close();
        }
    }
}


$pageTitle = 'Spotify for Artists - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .service-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 100px 30px 60px;
    }
    
    .service-hero {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        margin-bottom: 60px;
    }
    
    .service-branding {
        text-align: center;
    }
    
    .spotify-logo {
        width: 100px;
        height: 100px;
        background: #1db954;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 60px;
        color: #fff;
    }
    
    .service-title {
        font-size: 42px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .service-title span {
        font-weight: 400;
        color: rgba(255,255,255,0.8);
    }
    
    .service-preview {
        background: linear-gradient(135deg, rgba(29,185,84,0.1), rgba(29,185,84,0.05));
        border: 1px solid rgba(29,185,84,0.3);
        border-radius: 16px;
        padding: 20px;
    }
    
    .spotify-dashboard {
        background: #121212;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .spotify-header {
        background: linear-gradient(135deg, #1db954, #191414);
        padding: 25px 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .spotify-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        color: #fff;
    }
    
    .spotify-artist-info h3 {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    
    .spotify-listeners {
        color: #1db954;
        font-size: 14px;
        font-weight: 500;
    }
    
    .spotify-content {
        padding: 20px;
    }
    
    .spotify-stat-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .spotify-stat-box {
        background: #181818;
        border-radius: 8px;
        padding: 15px;
    }
    
    .spotify-stat-label {
        font-size: 11px;
        color: #b3b3b3;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .spotify-stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    
    .spotify-stat-trend {
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .trend-up {
        color: #1db954;
    }
    
    .trend-down {
        color: #e91429;
    }
    
    .spotify-section-title {
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 15px;
    }
    
    .spotify-song-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        background: #181818;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .spotify-song-thumb {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #333, #555);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .spotify-song-info {
        flex: 1;
    }
    
    .spotify-song-title {
        font-size: 14px;
        font-weight: 500;
        color: #fff;
        margin-bottom: 3px;
    }
    
    .spotify-song-listeners {
        font-size: 12px;
        color: #b3b3b3;
    }
    
    .benefits-section {
        max-width: 600px;
        margin: 0 auto 50px;
    }
    
    .benefits-title {
        font-size: 14px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .benefits-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .benefits-list li {
        padding: 8px 0;
        font-size: 14px;
        color: rgba(255,255,255,0.8);
        position: relative;
        padding-left: 20px;
    }
    
    .benefits-list li::before {
        content: '•';
        position: absolute;
        left: 0;
        color: #1db954;
        font-weight: bold;
    }
    
    .benefits-list li strong {
        color: #fff;
        font-weight: 600;
    }
    
    .notice-box {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        margin: 30px 0;
        font-size: 13px;
        color: rgba(255,255,255,0.7);
        line-height: 1.6;
    }
    
    .notice-box a {
        color: #1db954;
        text-decoration: underline;
    }
    
    .claim-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 40px;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .refresh-link {
        font-size: 13px;
        color: rgba(255,255,255,0.5);
        margin-bottom: 20px;
        display: block;
    }
    
    .refresh-link a {
        color: #1db954;
        text-decoration: underline;
    }
    
    .artist-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    
    .artist-dropdown {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    
    .artist-dropdown select {
        width: 100%;
        padding: 14px 40px 14px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        appearance: none;
        cursor: pointer;
    }
    
    .artist-dropdown::after {
        content: '▼';
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255,255,255,0.5);
        font-size: 10px;
        pointer-events: none;
    }
    
    .claim-btn {
        padding: 14px 30px;
        background: transparent;
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        white-space: nowrap;
    }
    
    .claim-btn:hover {
        background: rgba(255,255,255,0.1);
        border-color: #fff;
    }
    
    .claim-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .faq-link {
        font-size: 12px;
        color: #1db954;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        display: block;
        margin-top: 20px;
    }
    
    .faq-link:hover {
        text-decoration: underline;
    }
    
    .claimed-info {
        background: linear-gradient(135deg, rgba(29,185,84,0.1), rgba(29,185,84,0.05));
        border: 1px solid rgba(29,185,84,0.3);
        border-radius: 12px;
        padding: 25px;
        margin-top: 25px;
    }
    
    .claimed-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .claimed-badge {
        background: #1db954;
        color: #000;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .clear-btn {
        background: transparent;
        border: none;
        color: rgba(255,255,255,0.5);
        font-size: 12px;
        cursor: pointer;
        padding: 0;
    }
    
    .clear-btn:hover {
        color: #fff;
    }
    
    .url-field {
        margin-bottom: 15px;
    }
    
    .url-field label {
        display: block;
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .url-input-group {
        display: flex;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .url-input-group input {
        flex: 1;
        padding: 12px 16px;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 13px;
        font-family: monospace;
    }
    
    .url-input-group input:focus {
        outline: none;
    }
    
    .url-actions {
        display: flex;
        padding: 4px;
    }
    
    .url-btn {
        padding: 8px 12px;
        background: transparent;
        border: none;
        color: rgba(255,255,255,0.6);
        cursor: pointer;
        font-size: 16px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .url-btn:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
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
    
    @media (max-width: 768px) {
        .service-hero {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .service-title {
            font-size: 32px;
        }
        
        .claim-section {
            padding: 25px;
        }
        
        .artist-selector {
            flex-direction: column;
        }
    }
</style>

<div class="service-container">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="service-hero">
        <div class="service-branding">
            <div class="spotify-logo">
                <i class="mdi mdi-spotify"></i>
            </div>
            <h1 class="service-title">Spotify <span>for Artists</span></h1>
        </div>
        
        <div class="service-preview">
            <?php if ($previewImage): ?>
                <img src="<?php echo htmlspecialchars($previewImage); ?>" alt="Spotify Dashboard Preview" style="width: 100%; border-radius: 12px;">
            <?php else: ?>
            <div class="spotify-dashboard">
                <div class="spotify-header">
                    <div class="spotify-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="spotify-artist-info">
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <div class="spotify-listeners">
                            <i class="mdi mdi-account-multiple"></i> 34,346 fans listening now
                        </div>
                    </div>
                </div>
                
                <div class="spotify-content">
                    <div class="spotify-stat-row">
                        <div class="spotify-stat-box">
                            <div class="spotify-stat-label">Listeners</div>
                            <div class="spotify-stat-value">29.1K</div>
                            <div class="spotify-stat-trend trend-up">
                                <i class="mdi mdi-arrow-up"></i> +12%
                            </div>
                        </div>
                        <div class="spotify-stat-box">
                            <div class="spotify-stat-label">Streams</div>
                            <div class="spotify-stat-value">45.2K</div>
                            <div class="spotify-stat-trend trend-down">
                                <i class="mdi mdi-arrow-down"></i> -3%
                            </div>
                        </div>
                        <div class="spotify-stat-box">
                            <div class="spotify-stat-label">Followers</div>
                            <div class="spotify-stat-value">1,203</div>
                            <div class="spotify-stat-trend trend-up">
                                <i class="mdi mdi-arrow-up"></i> +8%
                            </div>
                        </div>
                    </div>
                    
                    <div class="spotify-section-title">Top Songs</div>
                    
                    <div class="spotify-song-item">
                        <div class="spotify-song-thumb">
                            <i class="mdi mdi-music"></i>
                        </div>
                        <div class="spotify-song-info">
                            <div class="spotify-song-title">Your Latest Release</div>
                            <div class="spotify-song-listeners">29.1K listeners</div>
                        </div>
                    </div>
                    
                    <div class="spotify-song-item">
                        <div class="spotify-song-thumb">
                            <i class="mdi mdi-music"></i>
                        </div>
                        <div class="spotify-song-info">
                            <div class="spotify-song-title">Popular Track</div>
                            <div class="spotify-song-listeners">18.5K listeners</div>
                        </div>
                    </div>
                    
                    <div class="spotify-song-item">
                        <div class="spotify-song-thumb">
                            <i class="mdi mdi-music"></i>
                        </div>
                        <div class="spotify-song-info">
                            <div class="spotify-song-title">Fan Favorite</div>
                            <div class="spotify-song-listeners">12.3K listeners</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="benefits-section">
        <div class="benefits-title">Claim and Verify Your Spotify Profile:</div>
        <ul class="benefits-list">
            <li><strong>Manage</strong> your Spotify profile</li>
            <li><strong>Get deeper</strong> music and audience data</li>
            <li><strong>Pitch</strong> your new music to playlists</li>
            <li><strong>Highlight</strong> key songs, concerts, and playlists with Artist Pick</li>
            <li>Spotify allows profiles to be claimed for <strong>Primary Artists, Featured Artists, and Remixer.</strong> Other roles are not supported.</li>
            <li>An artist profile can be claimed <strong>after their first Spotify release has gone live.</strong></li>
        </ul>
    </div>
    
    <div class="notice-box">
        If you don't see your artist name listed below, <a href="/index.php?q=dashboard">click here</a> to refresh the list
    </div>
    
    <div class="claim-section">
        <?php if ($spotifyAccount && $spotifyAccount['is_claimed']): ?>
            <!-- Show claimed artist info -->
            <div class="claimed-info">
                <div class="claimed-header">
                    <span class="claimed-badge">CLAIMED: <?php echo htmlspecialchars($spotifyAccount['user_artist_name'] ?? $spotifyAccount['artist_name']); ?></span>
                    <span style="flex:1;"></span>
                    <button type="button" class="clear-btn" onclick="clearClaimedArtist()">CLEAR</button>
                </div>
                
                <div class="url-field">
                    <label>Spotify URL</label>
                    <div class="url-input-group">
                        <input type="text" value="<?php echo htmlspecialchars($spotifyAccount['spotify_url']); ?>" readonly id="spotifyUrl">
                        <div class="url-actions">
                            <button class="url-btn" onclick="copyToClipboard('spotifyUrl')" title="Copy">
                                <i class="mdi mdi-content-copy"></i>
                            </button>
                            <a href="<?php echo htmlspecialchars($spotifyAccount['spotify_url']); ?>" target="_blank" class="url-btn" title="Open">
                                <i class="mdi mdi-open-in-new"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="url-field">
                    <label>Spotify URI</label>
                    <div class="url-input-group">
                        <input type="text" value="<?php echo htmlspecialchars($spotifyAccount['spotify_uri']); ?>" readonly id="spotifyUri">
                        <div class="url-actions">
                            <button class="url-btn" onclick="copyToClipboard('spotifyUri')" title="Copy">
                                <i class="mdi mdi-content-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Show claim form -->
            <?php if (empty($artists)): ?>
                <div class="not-eligible" style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; color: #ffc107; margin-bottom: 15px;">
                        <i class="mdi mdi-account-off"></i>
                    </div>
                    <h3 style="font-size: 18px; margin-bottom: 10px;">No Approved Artists</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 20px;">
                        You don't have any approved artists yet.<br>Request an artist in Artist Manager first.
                    </p>
                    <button class="claim-btn" onclick="location.href='/index.php?q=artist_manager'" style="display: inline-flex;">
                        <i class="mdi mdi-account-music"></i> Go to Artist Manager
                    </button>
                </div>
            <?php else: ?>
            <span class="refresh-link">Select an artist from your approved list below</span>
            
            <form method="POST" action="">
                <div class="artist-selector">
                    <div class="artist-dropdown">
                        <select name="artist_name" id="artist_select" required>
                            <option value="">Choose your artist name</option>
                            <?php foreach ($artists as $artistName): 
                                $adminId = $adminArtistMap[$artistName] ?? 0;
                            ?>
                                <option value="<?php echo htmlspecialchars($artistName); ?>" data-admin-id="<?php echo $adminId; ?>">
                                    <?php echo htmlspecialchars($artistName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="admin_artist_id" id="admin_artist_id" value="">
                    </div>
                    <button type="submit" name="claim_artist" class="claim-btn">
                        CLAIM ARTIST <i class="mdi mdi-chevron-right"></i>
                    </button>
                </div>
            </form>
            <script>
                document.getElementById('artist_select').addEventListener('change', function() {
                    var selectedOption = this.options[this.selectedIndex];
                    document.getElementById('admin_artist_id').value = selectedOption.getAttribute('data-admin-id') || '';
                });
            </script>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="/help" class="faq-link">VISIT OUR FAQ FOR MORE INFO.</a>
    </div>
</div>

<script>
    function copyToClipboard(elementId) {
        const input = document.getElementById(elementId);
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(function() {
            // Show toast or feedback
            const btn = event.target.closest('.url-btn');
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="mdi mdi-check"></i>';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
            }, 2000);
        });
    }
    
    function clearClaimedArtist() {
        if (confirm('Are you sure you want to clear this claimed artist?')) {
            // Submit form to clear or redirect with clear parameter
            window.location.href = '/index.php?q=spotify_for_artists&clear=1';
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
