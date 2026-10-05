<?php
/**
 * HiTune Music Distribution - Apple Music for Artists
 * TuneCore-like Apple Music for Artists feature
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
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'apple_music_preview_image' AND setting_group = 'artist_previews'");
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

// Get existing Apple Music artist account (joined with user_artists)
$appleAccount = null;
$accountStmt = $db->prepare("
    SELECT aa.*, ua.artist_name as user_artist_name 
    FROM artist_accounts aa 
    LEFT JOIN user_artists ua ON aa.user_id = ua.user_id AND aa.artist_name = ua.artist_name 
    WHERE aa.user_id = ? AND aa.platform = 'apple_music' AND aa.status = 'claimed'
    LIMIT 1
");
$accountStmt->bind_param("i", $userId);
$accountStmt->execute();
$accountResult = $accountStmt->get_result();
if ($accountResult->num_rows > 0) {
    $appleAccount = $accountResult->fetch_assoc();
}
$accountStmt->close();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['get_access'])) {
        $artistName = trim($_POST['artist_name'] ?? '');
        $adminArtistId = intval($_POST['admin_artist_id'] ?? 0);
        
        if (empty($artistName)) {
            $error = 'Please select an artist name';
        } elseif ($adminArtistId === 0) {
            $error = 'Invalid artist selection';
        } else {
            // Get admin artist details for Apple Music URL if available
            $adminStmt = $db->prepare("SELECT apple_music_url FROM admin_artists WHERE id = ?");
            $adminStmt->bind_param("i", $adminArtistId);
            $adminStmt->execute();
            $adminResult = $adminStmt->get_result();
            $adminArtist = $adminResult->fetch_assoc();
            $adminStmt->close();
            
            // Use admin's Apple Music URL or generate one
            $appleMusicUrl = $adminArtist['apple_music_url'] ?? ('https://music.apple.com/artist/' . urlencode($artistName) . '/' . rand(100000000, 999999999));
            
            if ($appleAccount) {
                // Update existing
                $updateStmt = $db->prepare("UPDATE artist_accounts SET admin_artist_id = ?, artist_name = ?, apple_music_url = ?, status = 'claimed', is_claimed = 1, claimed_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("issi", $adminArtistId, $artistName, $appleMusicUrl, $appleAccount['id']);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Insert new
                $insertStmt = $db->prepare("INSERT INTO artist_accounts (user_id, admin_artist_id, platform, artist_name, apple_music_url, is_claimed, status, claimed_at) VALUES (?, ?, 'apple_music', ?, ?, 1, 'claimed', NOW())");
                $insertStmt->bind_param("iiss", $userId, $adminArtistId, $artistName, $appleMusicUrl);
                $insertStmt->execute();
                $insertStmt->close();
            }
            
            $success = 'Access granted! Your Apple Music for Artists account is now active.';
            
            // Refresh account data
            $accountStmt = $db->prepare("
                SELECT aa.*, ua.artist_name as user_artist_name 
                FROM artist_accounts aa 
                LEFT JOIN user_artists ua ON aa.user_id = ua.user_id AND aa.artist_name = ua.artist_name 
                WHERE aa.user_id = ? AND aa.platform = 'apple_music' AND aa.status = 'claimed'
                LIMIT 1
            ");
            $accountStmt->bind_param("i", $userId);
            $accountStmt->execute();
            $accountResult = $accountStmt->get_result();
            $appleAccount = $accountResult->fetch_assoc();
            $accountStmt->close();
        }
    }
}

$pageTitle = 'Apple Music for Artists - HiTune Music Distribution';
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
    
    .apple-logo {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #fa243c, #00b7ff);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 60px;
        color: #fff;
    }
    
    .service-title {
        font-size: 32px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .service-preview {
        background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 20px;
    }
    
    .preview-image {
        width: 100%;
        border-radius: 12px;
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        aspect-ratio: 16/10;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,0.5);
        font-size: 18px;
    }
    
    .info-section {
        max-width: 600px;
        margin: 0 auto 40px;
        text-align: center;
    }
    
    .info-text {
        font-size: 14px;
        color: rgba(255,255,255,0.7);
        line-height: 1.7;
        margin-bottom: 30px;
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
        margin: 0 0 30px 0;
        text-align: left;
        display: inline-block;
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
        color: #fa243c;
        font-weight: bold;
    }
    
    .access-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 40px;
        max-width: 500px;
        margin: 0 auto;
        text-align: center;
    }
    
    .artist-input {
        width: 100%;
        padding: 14px 16px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .artist-input:focus {
        outline: none;
        border-color: #fa243c;
    }
    
    .access-btn {
        width: 100%;
        padding: 14px 30px;
        background: transparent;
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .access-btn:hover {
        background: rgba(255,255,255,0.1);
        border-color: #fff;
    }
    
    .access-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .faq-link {
        font-size: 12px;
        color: #fa243c;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        display: block;
        margin-top: 25px;
    }
    
    .faq-link:hover {
        text-decoration: underline;
    }
    
    .claimed-info {
        background: linear-gradient(135deg, rgba(250,36,60,0.1), rgba(250,36,60,0.05));
        border: 1px solid rgba(250,36,60,0.3);
        border-radius: 12px;
        padding: 25px;
        margin-top: 25px;
        text-align: left;
    }
    
    .claimed-badge {
        background: #fa243c;
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
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
    }
    
    .url-input-group input:focus {
        outline: none;
    }
    
    .url-btn {
        padding: 12px 16px;
        background: transparent;
        border: none;
        color: rgba(255,255,255,0.6);
        cursor: pointer;
        font-size: 16px;
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
        
        .access-section {
            padding: 25px;
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
        <div class="service-preview">
            <?php if ($previewImage): ?>
                <img src="<?php echo htmlspecialchars($previewImage); ?>" alt="Apple Music Dashboard Preview" style="width: 100%; border-radius: 12px;">
            <?php else: ?>
            <div class="preview-image">
                <i class="mdi mdi-laptop" style="font-size: 80px; opacity: 0.3;"></i>
                <span style="position: absolute;">Apple Music Analytics Dashboard</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="service-branding">
            <div class="apple-logo">
                <i class="mdi mdi-apple-music"></i>
            </div>
            <h1 class="service-title">Apple Music for Artists</h1>
        </div>
    </div>
    
    <div class="info-section">
        <p class="info-text">
            HiTune artists can now get their Apple Music for Artists account verified faster — Just use your HiTune email and password when signing up.
        </p>
        
        <div class="benefits-title">With Apple Music for Artists, you can:</div>
        <ul class="benefits-list">
            <li>Identify music milestones and all-time bests</li>
            <li>Find out who your listeners are and where they're located</li>
            <li>Upload an artist image to express your personality</li>
        </ul>
    </div>
    
    <div class="access-section">
        <?php if ($appleAccount && $appleAccount['is_claimed']): ?>
            <!-- Show claimed artist info -->
            <div class="claimed-info">
                <span class="claimed-badge">ACCESS GRANTED: <?php echo htmlspecialchars($appleAccount['user_artist_name'] ?? $appleAccount['artist_name']); ?></span>
                <div class="url-field">
                    <label>Apple Music Artist URL</label>
                    <div class="url-input-group">
                        <input type="text" value="<?php echo htmlspecialchars($appleAccount['apple_music_url']); ?>" readonly id="appleUrl">
                        <button class="url-btn" onclick="copyToClipboard('appleUrl')" title="Copy">
                            <i class="mdi mdi-content-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Show access form -->
            <?php if (empty($artists)): ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 48px; color: #ffc107; margin-bottom: 15px;">
                        <i class="mdi mdi-account-off"></i>
                    </div>
                    <h3 style="font-size: 18px; margin-bottom: 10px;">No Approved Artists</h3>
                    <p style="font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 20px;">
                        You don't have any approved artists yet.<br>Request an artist in Artist Manager first.
                    </p>
                    <button class="access-btn" onclick="location.href='/index.php?q=artist_manager'">
                        <i class="mdi mdi-account-music"></i> Go to Artist Manager
                    </button>
                </div>
            <?php else: ?>
            <form method="POST" action="">
                <select name="artist_name" id="artist_select" class="artist-input" required>
                    <option value="">Choose your artist</option>
                    <?php foreach ($artists as $artistName): 
                        $adminId = $adminArtistMap[$artistName] ?? 0;
                    ?>
                        <option value="<?php echo htmlspecialchars($artistName); ?>" data-admin-id="<?php echo $adminId; ?>">
                            <?php echo htmlspecialchars($artistName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="admin_artist_id" id="admin_artist_id" value="">
                <button type="submit" name="get_access" class="access-btn">
                    Get Access on Apple Music for Artists
                </button>
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
            const btn = event.target.closest('.url-btn');
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="mdi mdi-check"></i>';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
            }, 2000);
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
