<?php
/**
 * HiTune Music Distribution - Multi-Step Release Creation
 * Step 1: Release Details
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$release_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Freemium quota (Admin → Settings → Freemium): free users get N
// submitted releases per calendar month; paid plans are unlimited.
$quota = function_exists('freeReleaseQuota') ? freeReleaseQuota() : ['enabled'=>false,'limit'=>0,'used'=>0,'remaining'=>0,'subscribed'=>true];
$quotaBlocked = !$quota['subscribed'] && $quota['enabled'] && $quota['remaining'] <= 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    if ($release_id === 0 && $quotaBlocked) {
        // Free releases for this month exhausted — the upgrade gate below
        // is shown instead of silently creating another draft.
    } elseif ($release_id === 0) {
        // Create new release
        $stmt = $conn->prepare("INSERT INTO releases (user_id, release_type, title, title_version, primary_artist, language,
            primary_genre, secondary_genre, release_date, original_release_date, recording_location,
            sell_worldwide, territory_restriction_type, territory_restrictions, upc_code,
            ai_pct, ai_tools, ai_declared, step_completed, progress_percent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 25)");

        $sell_worldwide = isset($_POST['sell_worldwide']) && $_POST['sell_worldwide'] === 'yes' ? 1 : 0;
        $territory_type = $_POST['territory_type'] ?? 'exclude';
        $territories = $sell_worldwide ? null : ($_POST['territories'] ?? null);
        $ai_pct = isset($_POST['ai_pct']) ? max(0, min(100, (int)$_POST['ai_pct'])) : 0;
        $ai_tools = !empty($_POST['ai_tools']) ? substr(trim($_POST['ai_tools']), 0, 255) : null;
        $ai_declared = !empty($_POST['ai_declaration']) ? 1 : 0;

        $stmt->bind_param("issssssssssisssisi",
            $user['id'],
            $_POST['release_type'],
            $_POST['title'],
            $_POST['title_version'],
            $_POST['primary_artist'],
            $_POST['language'],
            $_POST['primary_genre'],
            $_POST['secondary_genre'],
            $_POST['release_date'],
            $_POST['original_release_date'],
            $_POST['recording_location'],
            $sell_worldwide,
            $territory_type,
            $territories,
            substr($_POST['upc_code'] ?? '', 0, 20),
            $ai_pct,
            $ai_tools,
            $ai_declared
        );
        
        if ($stmt->execute()) {
            $release_id = $conn->insert_id;
            
            // Initialize all 80+ streaming platforms - all selected by default
            $platforms = [
                // HiTune ecosystem — instant placement, always on (strategy doc §4)
                'HiTune Music', 'IyolMe',

                // Major Global Platforms
                'Spotify', 'Apple Music', 'YouTube Music', 'Amazon Music', 'Tidal', 'Deezer',
                'Pandora', 'SoundCloud', 'iTunes', 'Shazam', 'Napster', 'iHeartRadio',
                'TuneIn', 'Audiomack', 'Bandcamp', 'Jamendo', '8tracks', 'Qobuz',
                'Rhapsody', 'Beatport', 'Traxsource', 'JunoDownload', '7digital',
                
                // India Specific
                'JioSaavn', 'Wynk', 'Gaana', 'Hungama', 'Resso', 'Times Music',
                'Bhartiya Music', 'Adda247', 'JioTV', 'Voot', 'MX Player',
                
                // Asian Markets
                'Line Music', 'KKBox', 'AWA', 'QQ Music', 'NetEase Cloud Music', 
                'Kugou Music', 'Kuwo Music', 'Xiami Music', 'Moov', 'Joox',
                'Genie', 'Melon', 'Bugs', 'Soribada', 'Flo', 'Vibe',
                'Anghami', 'Yandex Music', 'Zvooq', 'Boomplay', 'Mdundo',
                
                // Europe
                'Yandex Music', 'Deezer', 'Spotify', 'Tidal', 'Qobuz', 'Apple Music',
                'YouTube Music', 'Amazon Music', 'Deezer', 'Napster', 'SoundCloud',
                'Shazam', 'Musixmatch', 'Genius', 'Songkick', 'Bandsintown',
                
                // Latin America
                'Spotify', 'Apple Music', 'YouTube Music', 'Amazon Music', 'Deezer',
                'Tidal', 'SoundCloud', 'Napster', 'iHeartRadio', 'TuneIn',
                'Claro Musica', 'Movistar Musica', 'UOL Musica', 'Terra Music',
                
                // Africa & Middle East
                'Boomplay', 'Mdundo', 'Anghami', 'Spotify', 'Apple Music', 'YouTube Music',
                'Amazon Music', 'Deezer', 'Tidal', 'SoundCloud', 'Rotana',
                
                // In-Car & Apps
                'Peloton', 'Waze', 'Uber', 'Lyft', 'Twitch',
                
                // Social Platforms
                'TikTok', 'Instagram', 'Facebook', 'YouTube Shorts', 'Snapchat',
                'Triller', 'Likee', 'Trebel', 'Twitter / X'
            ];
            
            // Remove duplicates and sort
            $platforms = array_unique($platforms);
            sort($platforms);
            
            $platform_stmt = $conn->prepare("INSERT INTO release_platforms (release_id, platform_name, is_selected) VALUES (?, ?, 1)");
            foreach ($platforms as $platform) {
                $platform_stmt->bind_param("is", $release_id, $platform);
                $platform_stmt->execute();
            }
            
            if ($action === 'save_continue') {
                header("Location: /index.php?q=release-create&id=$release_id&step=2");
            } else {
                header("Location: /index.php?q=releases");
            }
            exit;
        }
    } else {
        // Update existing release
        $stmt = $conn->prepare("UPDATE releases SET
            release_type = ?, title = ?, title_version = ?, primary_artist = ?, language = ?,
            primary_genre = ?, secondary_genre = ?, release_date = ?, original_release_date = ?,
            recording_location = ?, sell_worldwide = ?, territory_restriction_type = ?,
            territory_restrictions = ?, upc_code = ?, ai_pct = ?, ai_tools = ?, ai_declared = ?,
            step_completed = GREATEST(step_completed, 1), progress_percent = GREATEST(progress_percent, 25)
            WHERE id = ? AND user_id = ? AND status = 'draft'");

        $sell_worldwide = isset($_POST['sell_worldwide']) && $_POST['sell_worldwide'] === 'yes' ? 1 : 0;
        $territory_type = $_POST['territory_type'] ?? 'exclude';
        $territories = $sell_worldwide ? null : ($_POST['territories'] ?? null);
        $ai_pct = isset($_POST['ai_pct']) ? max(0, min(100, (int)$_POST['ai_pct'])) : 0;
        $ai_tools = !empty($_POST['ai_tools']) ? substr(trim($_POST['ai_tools']), 0, 255) : null;
        $ai_declared = !empty($_POST['ai_declaration']) ? 1 : 0;

        $stmt->bind_param("ssssssssssisssisiii",
            $_POST['release_type'],
            $_POST['title'],
            $_POST['title_version'],
            $_POST['primary_artist'],
            $_POST['language'],
            $_POST['primary_genre'],
            $_POST['secondary_genre'],
            $_POST['release_date'],
            $_POST['original_release_date'],
            $_POST['recording_location'],
            $sell_worldwide,
            $territory_type,
            $territories,
            substr($_POST['upc_code'] ?? '', 0, 20),
            $ai_pct,
            $ai_tools,
            $ai_declared,
            $release_id,
            $user['id']
        );
        
        if ($stmt->execute()) {
            if ($action === 'save_continue') {
                header("Location: /index.php?q=release-create&id=$release_id&step=2");
            } else {
                header("Location: /index.php?q=releases");
            }
            exit;
        }
    }
}

// Load existing release data
$release = null;
if ($release_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM releases WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $release_id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $release = $result->fetch_assoc();
    
    if (!$release) {
        header('Location: /index.php?q=releases');
        exit;
    }
    
    // Check if can edit
    if ($release['status'] !== 'draft' && $release['status'] !== 'rejected') {
        header("Location: /index.php?q=release-view&id=$release_id");
        exit;
    }
}

$pageTitle = 'Create Release - Step 1: Release Details';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .release-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 100px 20px 60px;
    }
    .progress-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 15px;
        padding: 25px 30px;
        margin-bottom: 30px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .progress-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .progress-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }
    .progress-bar::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: rgba(255,255,255,0.1);
        transform: translateY(-50%);
        z-index: 0;
    }
    .progress-line {
        position: absolute;
        top: 50%;
        left: 0;
        height: 2px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        transform: translateY(-50%);
        z-index: 0;
        transition: width 0.3s;
    }
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        position: relative;
        z-index: 1;
    }
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        background: rgba(255,255,255,0.1);
        border: 2px solid rgba(255,255,255,0.2);
        transition: all 0.3s;
    }
    .progress-step.active .step-number {
        background: linear-gradient(135deg, #00d4aa, #00c853);
        border-color: #00d4aa;
        color: #000;
    }
    .progress-step.completed .step-number {
        background: #00c853;
        border-color: #00c853;
        color: #000;
    }
    .step-label {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
        font-weight: 500;
    }
    .progress-step.active .step-label {
        color: #00d4aa;
    }
    
    .form-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 40px;
    }
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section-title i {
        color: #00d4aa;
    }
    .form-group {
        margin-bottom: 25px;
    }
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255,255,255,0.9);
    }
    .form-group label .required {
        color: #00b7ff;
        margin-left: 3px;
    }
    .form-group label .optional {
        color: rgba(255,255,255,0.4);
        font-size: 12px;
        margin-left: 5px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 14px 18px;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
        transition: all 0.3s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #00d4aa;
        background: rgba(0,0,0,0.4);
    }
    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: rgba(255,255,255,0.3);
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .progress-bar { flex-wrap: wrap; gap: 15px; }
        .progress-bar::before { display: none; }
    }
    
    /* Release Type Selector */
    .type-selector {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }
    .type-option {
        padding: 25px 20px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .type-option:hover {
        border-color: rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.05);
    }
    .type-option.selected {
        border-color: #00d4aa;
        background: rgba(0, 212, 170, 0.1);
    }
    .type-option i {
        font-size: 32px;
        margin-bottom: 12px;
        color: rgba(255,255,255,0.6);
    }
    .type-option.selected i {
        color: #00d4aa;
    }
    .type-option h4 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .type-option p {
        font-size: 12px;
        color: rgba(255,255,255,0.4);
    }
    
    /* Radio buttons */
    .radio-group {
        display: flex;
        gap: 20px;
    }
    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 10px 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.3s;
    }
    .radio-option:hover {
        background: rgba(255,255,255,0.08);
    }
    .radio-option input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: #00d4aa;
    }
    .radio-option.selected {
        border-color: #00d4aa;
        background: rgba(0, 212, 170, 0.1);
    }
    
    /* Territory restrictions */
    .territory-section {
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
        padding: 20px;
        margin-top: 15px;
    }
    .territory-section.hidden {
        display: none;
    }
    
    /* Form actions */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .btn {
        padding: 14px 30px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.15);
    }
    .btn-primary {
        background: linear-gradient(135deg, #00d4aa, #00c853);
        color: #000;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 200, 83, 0.3);
    }
    .help-text {
        font-size: 12px;
        color: rgba(255,255,255,0.4);
        margin-top: 5px;
    }
    .info-icon {
        color: rgba(255,255,255,0.4);
        margin-left: 5px;
        cursor: help;
    }
</style>

<div class="release-container">
    <!-- Progress Header -->
    <div class="progress-header">
        <h1><?php echo $release_id > 0 ? 'Edit Release' : 'Create New Release'; ?></h1>
        <div class="progress-bar">
            <div class="progress-line" style="width: 12.5%;"></div>
            <div class="progress-step active">
                <div class="step-number">1</div>
                <span class="step-label">Release Details</span>
            </div>
            <div class="progress-step">
                <div class="step-number">2</div>
                <span class="step-label">Stores</span>
            </div>
            <div class="progress-step">
                <div class="step-number">3</div>
                <span class="step-label">Tracks</span>
            </div>
            <div class="progress-step">
                <div class="step-number">4</div>
                <span class="step-label">Artwork</span>
            </div>
        </div>
    </div>

    <?php if ($quotaBlocked): ?>
        <div style="background:rgba(139,92,246,.12);border:1px solid rgba(139,92,246,.4);border-radius:14px;padding:26px;text-align:center;margin-bottom:24px">
            <i class="mdi mdi-star-circle" style="font-size:42px;color:#8b5cf6"></i>
            <h3 style="margin:10px 0 8px;font-size:19px">Free releases for this month are used up</h3>
            <p style="color:var(--text-secondary,#9d9db8);font-size:13.5px;max-width:480px;margin:0 auto 18px">
                Free plan includes <b><?php echo (int)$quota['limit']; ?></b> releases per month — you have already used
                <?php echo (int)$quota['used']; ?>. Upgrade for unlimited releases, more platforms and faster review.
            </p>
            <a href="/index.php?q=pricing" class="btn btn-primary" style="display:inline-block;padding:13px 34px;border-radius:12px;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#fff;font-weight:700;text-decoration:none">
                <i class="mdi mdi-rocket-launch"></i> Upgrade Plan
            </a>
            <a href="/index.php?q=releases" style="display:inline-block;margin-left:12px;color:var(--text-secondary,#9d9db8);font-size:13px;text-decoration:none">or wait until next month →</a>
        </div>
    <?php elseif (!$quota['subscribed'] && $quota['enabled']): ?>
        <div style="display:flex;align-items:center;gap:12px;background:rgba(0,183,255,.08);border:1px solid rgba(0,183,255,.25);border-radius:12px;padding:13px 18px;margin-bottom:22px;font-size:13px">
            <i class="mdi mdi-gift-outline" style="font-size:22px;color:#00b7ff;flex:none"></i>
            <div style="flex:1">
                Free plan: <b><?php echo (int)$quota['remaining']; ?> of <?php echo (int)$quota['limit']; ?></b> free releases left this month.
            </div>
            <a href="/index.php?q=pricing" style="color:#00b7ff;font-weight:700;text-decoration:none;white-space:nowrap">Go unlimited →</a>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <form method="POST" class="form-card">
        <input type="hidden" name="action" id="formAction" value="save">
        
        <!-- Release Type -->
        <div class="form-section-title">
            <i class="mdi mdi-album"></i>
            Release Type
        </div>
        <div class="type-selector">
            <div class="type-option <?php echo ($release && $release['release_type'] === 'single') || !$release ? 'selected' : ''; ?>" onclick="selectType('single', this)">
                <i class="mdi mdi-music-note"></i>
                <h4>Single</h4>
                <p>1 track release</p>
            </div>
            <div class="type-option <?php echo $release && $release['release_type'] === 'ep' ? 'selected' : ''; ?>" onclick="selectType('ep', this)">
                <i class="mdi mdi-album"></i>
                <h4>EP</h4>
                <p>2-6 tracks</p>
            </div>
            <div class="type-option <?php echo $release && $release['release_type'] === 'album' ? 'selected' : ''; ?>" onclick="selectType('album', this)">
                <i class="mdi mdi-disc"></i>
                <h4>Album</h4>
                <p>7+ tracks</p>
            </div>
        </div>
        <input type="hidden" name="release_type" id="releaseType" value="<?php echo $release ? htmlspecialchars($release['release_type']) : 'single'; ?>">

        <!-- Release Details -->
        <div class="form-section-title" style="margin-top: 40px;">
            <i class="mdi mdi-information-circle"></i>
            Release Details
        </div>

        <div class="form-group">
            <label>Release Title <span class="required">*</span></label>
            <input type="text" name="title" required 
                   value="<?php echo $release ? htmlspecialchars($release['title']) : ''; ?>"
                   placeholder="Enter your release title">
        </div>

        <div class="form-group">
            <label>Title Version <span class="optional">(optional)</span></label>
            <input type="text" name="title_version" 
                   value="<?php echo $release ? htmlspecialchars($release['title_version'] ?? '') : ''; ?>"
                   placeholder="e.g., Remix, Live, Acoustic">
            <p class="help-text">Add version info like 'Remix', 'Live', 'Acoustic', etc.</p>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Primary Artist <span class="required">*</span></label>
                <input type="text" name="primary_artist" required 
                       value="<?php echo $release ? htmlspecialchars($release['primary_artist']) : htmlspecialchars($user['name']); ?>"
                       placeholder="Main artist name">
            </div>
            <div class="form-group">
                <label>Language <span class="required">*</span></label>
                <select name="language" required>
                    <option value="Hindi" <?php echo ($release && $release['language'] === 'Hindi') || !$release ? 'selected' : ''; ?>>Hindi</option>
                    <option value="English" <?php echo $release && $release['language'] === 'English' ? 'selected' : ''; ?>>English</option>
                    <option value="Punjabi" <?php echo $release && $release['language'] === 'Punjabi' ? 'selected' : ''; ?>>Punjabi</option>
                    <option value="Tamil" <?php echo $release && $release['language'] === 'Tamil' ? 'selected' : ''; ?>>Tamil</option>
                    <option value="Telugu" <?php echo $release && $release['language'] === 'Telugu' ? 'selected' : ''; ?>>Telugu</option>
                    <option value="Malayalam" <?php echo $release && $release['language'] === 'Malayalam' ? 'selected' : ''; ?>>Malayalam</option>
                    <option value="Kannada" <?php echo $release && $release['language'] === 'Kannada' ? 'selected' : ''; ?>>Kannada</option>
                    <option value="Bengali" <?php echo $release && $release['language'] === 'Bengali' ? 'selected' : ''; ?>>Bengali</option>
                    <option value="Marathi" <?php echo $release && $release['language'] === 'Marathi' ? 'selected' : ''; ?>>Marathi</option>
                    <option value="Gujarati" <?php echo $release && $release['language'] === 'Gujarati' ? 'selected' : ''; ?>>Gujarati</option>
                    <option value="Other" <?php echo $release && $release['language'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Primary Genre <span class="required">*</span></label>
                <select name="primary_genre" required>
                    <option value="">Select Genre</option>
                    <option value="Pop" <?php echo $release && $release['primary_genre'] === 'Pop' ? 'selected' : ''; ?>>Pop</option>
                    <option value="Hip-Hop" <?php echo $release && $release['primary_genre'] === 'Hip-Hop' ? 'selected' : ''; ?>>Hip-Hop</option>
                    <option value="Rock" <?php echo $release && $release['primary_genre'] === 'Rock' ? 'selected' : ''; ?>>Rock</option>
                    <option value="R&B" <?php echo $release && $release['primary_genre'] === 'R&B' ? 'selected' : ''; ?>>R&B</option>
                    <option value="Electronic" <?php echo $release && $release['primary_genre'] === 'Electronic' ? 'selected' : ''; ?>>Electronic</option>
                    <option value="Classical" <?php echo $release && $release['primary_genre'] === 'Classical' ? 'selected' : ''; ?>>Classical</option>
                    <option value="Jazz" <?php echo $release && $release['primary_genre'] === 'Jazz' ? 'selected' : ''; ?>>Jazz</option>
                    <option value="Country" <?php echo $release && $release['primary_genre'] === 'Country' ? 'selected' : ''; ?>>Country</option>
                    <option value="Latin" <?php echo $release && $release['primary_genre'] === 'Latin' ? 'selected' : ''; ?>>Latin</option>
                    <option value="Indian Classical" <?php echo $release && $release['primary_genre'] === 'Indian Classical' ? 'selected' : ''; ?>>Indian Classical</option>
                    <option value="Bollywood" <?php echo $release && $release['primary_genre'] === 'Bollywood' ? 'selected' : ''; ?>>Bollywood</option>
                    <option value="Folk" <?php echo $release && $release['primary_genre'] === 'Folk' ? 'selected' : ''; ?>>Folk</option>
                    <option value="Devotional" <?php echo $release && $release['primary_genre'] === 'Devotional' ? 'selected' : ''; ?>>Devotional</option>
                    <option value="Ghazal" <?php echo $release && $release['primary_genre'] === 'Ghazal' ? 'selected' : ''; ?>>Ghazal</option>
                    <option value="Qawwali" <?php echo $release && $release['primary_genre'] === 'Qawwali' ? 'selected' : ''; ?>>Qawwali</option>
                </select>
            </div>
            <div class="form-group">
                <label>Secondary Genre <span class="optional">(optional)</span></label>
                <select name="secondary_genre">
                    <option value="">Select Genre</option>
                    <option value="Pop" <?php echo $release && $release['secondary_genre'] === 'Pop' ? 'selected' : ''; ?>>Pop</option>
                    <option value="Hip-Hop" <?php echo $release && $release['secondary_genre'] === 'Hip-Hop' ? 'selected' : ''; ?>>Hip-Hop</option>
                    <option value="Rock" <?php echo $release && $release['secondary_genre'] === 'Rock' ? 'selected' : ''; ?>>Rock</option>
                    <option value="R&B" <?php echo $release && $release['secondary_genre'] === 'R&B' ? 'selected' : ''; ?>>R&B</option>
                    <option value="Electronic" <?php echo $release && $release['secondary_genre'] === 'Electronic' ? 'selected' : ''; ?>>Electronic</option>
                    <option value="Classical" <?php echo $release && $release['secondary_genre'] === 'Classical' ? 'selected' : ''; ?>>Classical</option>
                    <option value="Jazz" <?php echo $release && $release['secondary_genre'] === 'Jazz' ? 'selected' : ''; ?>>Jazz</option>
                    <option value="Country" <?php echo $release && $release['secondary_genre'] === 'Country' ? 'selected' : ''; ?>>Country</option>
                    <option value="Latin" <?php echo $release && $release['secondary_genre'] === 'Latin' ? 'selected' : ''; ?>>Latin</option>
                    <option value="Indian Classical" <?php echo $release && $release['secondary_genre'] === 'Indian Classical' ? 'selected' : ''; ?>>Indian Classical</option>
                    <option value="Bollywood" <?php echo $release && $release['secondary_genre'] === 'Bollywood' ? 'selected' : ''; ?>>Bollywood</option>
                    <option value="Folk" <?php echo $release && $release['secondary_genre'] === 'Folk' ? 'selected' : ''; ?>>Folk</option>
                    <option value="Devotional" <?php echo $release && $release['secondary_genre'] === 'Devotional' ? 'selected' : ''; ?>>Devotional</option>
                    <option value="Ghazal" <?php echo $release && $release['secondary_genre'] === 'Ghazal' ? 'selected' : ''; ?>>Ghazal</option>
                    <option value="Qawwali" <?php echo $release && $release['secondary_genre'] === 'Qawwali' ? 'selected' : ''; ?>>Qawwali</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Release Date <span class="required">*</span></label>
                <input type="date" name="release_date" required 
                       value="<?php echo $release ? $release['release_date'] : ''; ?>"
                       min="<?php echo date('Y-m-d'); ?>">
                <p class="help-text">When should your music be available on stores?</p>
            </div>
            <div class="form-group">
                <label>Original Release Date <span class="optional">(optional)</span></label>
                <input type="date" name="original_release_date" 
                       value="<?php echo $release ? $release['original_release_date'] : ''; ?>">
                <p class="help-text">If this was released before, enter that date</p>
            </div>
        </div>

        <!-- Advanced Features -->
        <div class="form-section-title" style="margin-top: 40px;">
            <i class="mdi mdi-cog"></i>
            Advanced Features <span class="optional">(optional)</span>
        </div>

        <div class="form-group">
            <label>Label Name <span class="optional">(optional)</span></label>
            <input type="text" name="label_name" 
                   value="<?php echo $release ? htmlspecialchars($release['label_name'] ?? '') : ''; ?>"
                   placeholder="Your record label name">
        </div>

        <div class="form-group">
            <label>Recording Location <span class="optional">(optional)</span></label>
            <input type="text" name="recording_location" 
                   value="<?php echo $release ? htmlspecialchars($release['recording_location'] ?? '') : ''; ?>"
                   placeholder="City, Country">
        </div>

        <div class="form-group">
            <label>Sell this release worldwide? <span class="required">*</span></label>
            <div class="radio-group">
                <label class="radio-option <?php echo ($release && $release['sell_worldwide']) || !$release ? 'selected' : ''; ?>">
                    <input type="radio" name="sell_worldwide" value="yes" 
                           <?php echo ($release && $release['sell_worldwide']) || !$release ? 'checked' : ''; ?> 
                           onchange="toggleTerritory(this.value)">
                    <span>Yes</span>
                </label>
                <label class="radio-option <?php echo $release && !$release['sell_worldwide'] ? 'selected' : ''; ?>">
                    <input type="radio" name="sell_worldwide" value="no" 
                           <?php echo $release && !$release['sell_worldwide'] ? 'checked' : ''; ?> 
                           onchange="toggleTerritory(this.value)">
                    <span>No</span>
                </label>
            </div>
        </div>

        <div id="territorySection" class="territory-section <?php echo ($release && $release['sell_worldwide']) || !$release ? 'hidden' : ''; ?>">
            <div class="form-row">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Territory Restriction Type</label>
                    <select name="territory_type">
                        <option value="exclude" <?php echo ($release && $release['territory_restriction_type'] === 'exclude') || !$release ? 'selected' : ''; ?>>Exclude (Don't sell in selected)</option>
                        <option value="include" <?php echo $release && $release['territory_restriction_type'] === 'include' ? 'selected' : ''; ?>>Include (Only sell in selected)</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0; margin-top: 15px;">
                <label>Territories <span class="optional">(comma separated)</span></label>
                <input type="text" name="territories" 
                       value="<?php echo $release ? htmlspecialchars($release['territory_restrictions'] ?? '') : ''; ?>"
                       placeholder="e.g., India, USA, UK, Canada">
            </div>
        </div>

        <div class="form-group" style="margin-top: 25px;">
            <label>UPC/EAN Code <span class="optional">(optional)</span></label>
            <input type="text" name="upc_code" 
                   value="<?php echo $release ? htmlspecialchars($release['upc_code'] ?? '') : ''; ?>"
                   placeholder="Enter UPC/EAN if you have one">
            <p class="help-text">If you have one, please enter it above. Otherwise, we will generate one for you.</p>
        </div>

        <div class="section-title">
            <i class="mdi mdi-robot"></i>
            AI Content Declaration <span class="required">*</span>
        </div>
        <p class="help-text" style="margin-bottom: 15px;">
            Per our AI music policy, every release must declare how much AI was used in its creation.
            Original AI compositions and commercially-licensed AI vocals (e.g. Suno/Udio paid tier) are allowed;
            unauthorized voice cloning and copyright infringement are prohibited.
        </p>

        <div class="form-row">
            <div class="form-group">
                <label>AI-generated portion <span class="required">*</span></label>
                <select name="ai_pct">
                    <?php $ai_pct_val = $release ? (int)($release['ai_pct'] ?? 0) : 0; ?>
                    <option value="0"   <?php echo $ai_pct_val === 0 ? 'selected' : ''; ?>>0% — Fully human-created</option>
                    <option value="25"  <?php echo $ai_pct_val === 25 ? 'selected' : ''; ?>>25% — Some AI elements</option>
                    <option value="50"  <?php echo $ai_pct_val === 50 ? 'selected' : ''; ?>>50% — Half AI-assisted</option>
                    <option value="75"  <?php echo $ai_pct_val === 75 ? 'selected' : ''; ?>>75% — Mostly AI-generated</option>
                    <option value="100" <?php echo $ai_pct_val === 100 ? 'selected' : ''; ?>>100% — Fully AI-generated (requires commercial license)</option>
                </select>
                <p class="help-text">Tracks with AI content display an "AI Original" badge on HiTune Music and IyolMe.</p>
            </div>
            <div class="form-group">
                <label>AI tools used <span class="optional">(if any)</span></label>
                <input type="text" name="ai_tools"
                       value="<?php echo $release ? htmlspecialchars($release['ai_tools'] ?? '') : ''; ?>"
                       placeholder="e.g., Suno AI, Udio, Midjourney">
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-option" style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                <input type="checkbox" name="ai_declaration" value="1" style="margin-top:4px;"
                       <?php echo ($release && !empty($release['ai_declared'])) || !$release ? 'checked' : ''; ?>>
                <span>I confirm this release follows the HiTune AI policy — no unauthorized voice cloning of public
                figures or artists, no uncleared copyrighted samples, and I hold commercial rights for any AI-generated
                portions declared above.</span>
            </label>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="/index.php?q=releases" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i>
                Back to Releases
            </a>
            <div style="display: flex; gap: 15px;">
                <button type="submit" class="btn btn-secondary" onclick="document.getElementById('formAction').value='save'">
                    <i class="mdi mdi-content-save"></i>
                    Save as Draft
                </button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('formAction').value='save_continue'">
                    Save & Continue
                    <i class="mdi mdi-arrow-right"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function selectType(type, element) {
        document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        document.getElementById('releaseType').value = type;
    }

    function toggleTerritory(value) {
        const section = document.getElementById('territorySection');
        const radioOptions = document.querySelectorAll('.radio-option');
        
        radioOptions.forEach(opt => opt.classList.remove('selected'));
        event.target.closest('.radio-option').classList.add('selected');
        
        if (value === 'yes') {
            section.classList.add('hidden');
        } else {
            section.classList.remove('hidden');
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
