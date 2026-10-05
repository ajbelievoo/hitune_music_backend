<?php
/**
 * HiTune Music Distribution - YouTube Official Artist Channel
 * TuneCore-like YouTube OAC feature
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
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'youtube_preview_image' AND setting_group = 'artist_previews'");
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

// Check if any OAC exists (joined with user_artists to get artist_name)
$oacStmt = $db->prepare("
    SELECT aa.*, ua.artist_name as user_artist_name 
    FROM artist_accounts aa 
    LEFT JOIN user_artists ua ON aa.user_id = ua.user_id AND aa.artist_name = ua.artist_name 
    WHERE aa.user_id = ? AND aa.platform = 'youtube_oac' AND aa.status = 'claimed'
    LIMIT 1
");
$oacStmt->bind_param("i", $userId);
$oacStmt->execute();
$oacResult = $oacStmt->get_result();
$oacAccount = $oacResult->num_rows > 0 ? $oacResult->fetch_assoc() : null;
$oacStmt->close();

$db->close();

$pageTitle = 'YouTube Official Artist Channel - HiTune Music Distribution';
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
    
    .service-content h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .youtube-logo {
        width: 60px;
        height: 40px;
        background: #ff0000;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: #fff;
    }
    
    .intro-text {
        font-size: 14px;
        color: rgba(255,255,255,0.7);
        line-height: 1.7;
        margin-bottom: 25px;
    }
    
    .steps-list {
        list-style: none;
        padding: 0;
        margin: 0 0 25px 0;
        counter-reset: step;
    }
    
    .steps-list li {
        padding: 8px 0 8px 35px;
        font-size: 14px;
        color: rgba(255,255,255,0.8);
        position: relative;
        counter-increment: step;
    }
    
    .steps-list li::before {
        content: counter(step) ".";
        position: absolute;
        left: 0;
        color: #ff0000;
        font-weight: bold;
        font-size: 14px;
    }
    
    .terms-text {
        font-size: 13px;
        color: rgba(255,255,255,0.5);
        line-height: 1.6;
    }
    
    .terms-text a {
        color: rgba(255,255,255,0.8);
        text-decoration: underline;
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
        background: linear-gradient(135deg, #0f0f0f, #1a1a1a);
        aspect-ratio: 16/10;
        display: flex;
        flex-direction: column;
        color: rgba(255,255,255,0.5);
        font-size: 18px;
        position: relative;
        overflow: hidden;
    }
    
    .preview-banner {
        width: 100%;
        height: 40%;
        background: linear-gradient(135deg, #ff0000, #cc0000);
        position: relative;
    }
    
    .preview-profile {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: 3px solid #0f0f0f;
        position: absolute;
        top: 32%;
        left: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: #fff;
        font-weight: 700;
    }
    
    .preview-info {
        margin-top: 50px;
        padding: 0 20px;
    }
    
    .preview-name {
        font-size: 18px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 5px;
    }
    
    .preview-stats {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 15px;
    }
    
    .preview-video {
        background: rgba(255,255,255,0.05);
        border-radius: 8px;
        padding: 10px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .preview-video-thumb {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #333, #555);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .preview-video-info {
        flex: 1;
    }
    
    .preview-video-title {
        font-size: 12px;
        color: #fff;
        margin-bottom: 3px;
    }
    
    .preview-video-views {
        font-size: 10px;
        color: rgba(255,255,255,0.5);
    }
    
    .eligibility-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 40px;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .eligibility-header {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #fff;
    }
    
    .eligibility-text {
        font-size: 14px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 25px;
        line-height: 1.6;
    }
    
    .faq-link {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
        text-decoration: underline;
    }
    
    .faq-link:hover {
        color: #fff;
    }
    
    .eligible-card {
        background: linear-gradient(135deg, rgba(0,200,83,0.1), rgba(0,200,83,0.05));
        border: 1px solid rgba(0,200,83,0.3);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
    }
    
    .eligible-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .eligible-badge {
        background: #00c853;
        color: #000;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .channel-info {
        background: rgba(255,255,255,0.05);
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }
    
    .channel-label {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .channel-value {
        font-size: 14px;
        color: #fff;
        word-break: break-all;
    }
    
    .not-eligible {
        background: rgba(255,193,7,0.1);
        border: 1px solid rgba(255,193,7,0.3);
        border-radius: 12px;
        padding: 25px;
        text-align: center;
    }
    
    .not-eligible-icon {
        font-size: 48px;
        color: #ffc107;
        margin-bottom: 15px;
    }
    
    .not-eligible h3 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #ffc107;
    }
    
    .not-eligible p {
        font-size: 14px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 20px;
    }
    
    .action-btn {
        padding: 12px 25px;
        background: linear-gradient(135deg, #ff0000, #cc0000);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(255,0,0,0.3);
    }
    
    .artist-selector {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .artist-dropdown {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    
    .artist-dropdown select {
        width: 100%;
        padding: 12px 40px 12px 16px;
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
    
    .submit-btn {
        padding: 12px 25px;
        background: #ff0000;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .submit-btn:hover {
        background: #cc0000;
    }
    
    @media (max-width: 768px) {
        .service-hero {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .service-content h1 {
            font-size: 28px;
        }
        
        .eligibility-section {
            padding: 25px;
        }
        
        .artist-selector {
            flex-direction: column;
        }
    }
</style>

<div class="service-container">
    <div class="service-hero">
        <div class="service-content">
            <h1>
                <span class="youtube-logo">
                    <i class="mdi mdi-youtube"></i>
                </span>
                YouTube Official Artist Channel
            </h1>
            
            <p class="intro-text">
                Ready to take control of your YouTube presence?<br><br>
                With YouTube's Official Artist Channel, you can display your discography, music videos, vlogs and behind the scenes content on one centralized channel. Only artists with at least 1 YouTube Music track are eligible.
            </p>
            
            <ol class="steps-list">
                <li>Select an Artist from your account below.</li>
                <li>Submit the form you will be taken to a Google site.</li>
                <li>Select the Google account associated with your YouTube account.</li>
                <li>If your YouTube Account has only 1 channel, you can skip to 6.</li>
                <li>If your YouTube Account has multiple channels, choose the channel for this Artist based in step 1.</li>
                <li>Allow HiTune the permissions described.</li>
                <li>You will be redirected back to HiTune to confirm your selection.</li>
                <li>After confirmation, it may take up to 2-3 weeks for the OAC to register on YouTube.</li>
            </ol>
            
            <p class="terms-text">
                By delivering your content to YouTube via this service (YouTube API Client) users are bound by the <a href="https://www.youtube.com/t/terms" target="_blank">YouTube Terms of Service</a>. More info here on the <a href="https://policies.google.com/privacy" target="_blank">Google Privacy Policy</a>.
            </p>
        </div>
        
        <div class="service-preview">
            <?php if ($previewImage): ?>
                <img src="<?php echo htmlspecialchars($previewImage); ?>" alt="YouTube Channel Preview" style="width: 100%; border-radius: 12px;">
            <?php else: ?>
            <div class="preview-image">
                <div class="preview-banner"></div>
                <div class="preview-profile">
                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                </div>
                <div class="preview-info">
                    <div class="preview-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="preview-stats">
                        <span style="margin-right: 15px;"><i class="mdi mdi-account-multiple"></i> 125K subscribers</span>
                        <span><i class="mdi mdi-video"></i> 48 videos</span>
                    </div>
                    <div class="preview-video">
                        <div class="preview-video-thumb">
                            <i class="mdi mdi-play"></i>
                        </div>
                        <div class="preview-video-info">
                            <div class="preview-video-title">Your Latest Release</div>
                            <div class="preview-video-views">1.2M views • 2 weeks ago</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="eligibility-section">
        <?php if (!empty($artists)): ?>
            <?php if ($oacAccount): ?>
                <!-- Show claimed OAC info -->
                <div class="eligible-card">
                    <div class="eligible-header">
                        <span class="eligible-badge">ELIGIBLE FOR YOUTUBE OAC</span>
                    </div>
                    
                    <div class="channel-info">
                        <div class="channel-label">Selected Artist</div>
                        <div class="channel-value"><?php echo htmlspecialchars($oacAccount['user_artist_name'] ?? $oacAccount['artist_name']); ?></div>
                    </div>
                    
                    <?php if ($oacAccount['youtube_channel_url']): ?>
                    <div class="channel-info">
                        <div class="channel-label">YouTube Channel URL</div>
                        <div class="channel-value"><?php echo htmlspecialchars($oacAccount['youtube_channel_url']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px;">
                        <span style="font-size: 13px; color: rgba(255,255,255,0.5);">
                            <i class="mdi mdi-information-outline"></i> 
                            It may take 2-3 weeks for your OAC to be fully activated on YouTube.
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show selection form -->
                <div class="eligible-card">
                    <div class="eligible-header">
                        <span class="eligible-badge">ELIGIBLE FOR YOUTUBE OAC</span>
                    </div>
                    
                    <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin-bottom: 15px;">
                        Select an artist from your approved list to proceed with OAC setup:
                    </p>
                    
                    <form method="POST" action="/index.php?q=youtube_oac_submit">
                        <div class="artist-selector">
                            <div class="artist-dropdown">
                                <select name="artist_name" required>
                                    <option value="">Select an Artist</option>
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
                            <button type="submit" class="submit-btn">
                                <i class="mdi mdi-send"></i> Submit
                            </button>
                        </div>
                    </form>
                    <script>
                        document.querySelector('select[name=\"artist_name\"]').addEventListener('change', function() {
                            var selectedOption = this.options[this.selectedIndex];
                            document.getElementById('admin_artist_id').value = selectedOption.getAttribute('data-admin-id') || '';
                        });
                    </script>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Not eligible -->
            <div class="not-eligible">
                <div class="not-eligible-icon">
                    <i class="mdi mdi-account-off"></i>
                </div>
                <h3>No Approved Artists</h3>
                <p>You don't have any approved artists yet.<br>Request an artist in Artist Manager and wait for admin approval.</p>
                <button class="action-btn" onclick="location.href='/index.php?q=artist_manager'">
                    <i class="mdi mdi-account-music"></i> Go to Artist Manager
                </button>
            </div>
        <?php endif; ?>
        
        <a href="/help" class="faq-link">Visit our FAQ for more info.</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
