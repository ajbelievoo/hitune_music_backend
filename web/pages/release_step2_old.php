<?php
/**
 * HiTune Music Distribution - Multi-Step Release Creation
 * Step 2: Stores & Social Platforms
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$release_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify release exists and belongs to user
if ($release_id === 0) {
    header('Location: /index.php?q=releases');
    exit;
}

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    // Update selected platforms
    $selected_platforms = $_POST['platforms'] ?? [];
    
    // First, mark all as not selected
    $stmt = $conn->prepare("UPDATE release_platforms SET is_selected = 0 WHERE release_id = ?");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    
    // Then mark selected ones
    if (!empty($selected_platforms)) {
        $stmt = $conn->prepare("UPDATE release_platforms SET is_selected = 1 WHERE release_id = ? AND platform_name = ?");
        foreach ($selected_platforms as $platform) {
            $stmt->bind_param("is", $release_id, $platform);
            $stmt->execute();
        }
    }
    
    // Update release progress
    $stmt = $conn->prepare("UPDATE releases SET step_completed = GREATEST(step_completed, 2), progress_percent = GREATEST(progress_percent, 50) WHERE id = ?");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    
    if ($action === 'save_continue') {
        header("Location: /index.php?q=release-create&id=$release_id&step=3");
    } else {
        header("Location: /index.php?q=releases");
    }
    exit;
}

// Get current platforms
$platforms = [];
$result = $conn->query("SELECT * FROM release_platforms WHERE release_id = $release_id ORDER BY platform_name");
while ($row = $result->fetch_assoc()) {
    $platforms[] = $row;
}

$pageTitle = 'Create Release - Step 2: Stores & Platforms';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .release-container {
        max-width: 1100px;
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
    .form-section-title .badge {
        background: rgba(0, 212, 170, 0.2);
        color: #00d4aa;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .platforms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 40px;
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
    }
    .platforms-grid::-webkit-scrollbar {
        width: 8px;
    }
    .platforms-grid::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.05);
        border-radius: 4px;
    }
    .platforms-grid::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
        border-radius: 4px;
    }
    .platform-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 20px;
        background: rgba(255,255,255,0.03);
        border: 2px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .platform-card:hover {
        border-color: rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.05);
    }
    .platform-card.selected {
        border-color: #00d4aa;
        background: rgba(0, 212, 170, 0.1);
    }
    .platform-card input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #00d4aa;
        cursor: pointer;
    }
    .platform-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .platform-icon.spotify { background: rgba(29, 185, 84, 0.2); color: #1DB954; }
    .platform-icon.apple { background: rgba(250, 36, 60, 0.2); color: #FA243C; }
    .platform-icon.youtube { background: rgba(255, 0, 0, 0.2); color: #FF0000; }
    .platform-icon.amazon { background: rgba(0, 168, 225, 0.2); color: #00A8E1; }
    .platform-icon.jiosaavn { background: rgba(46, 204, 113, 0.2); color: #2ECC71; }
    .platform-icon.wynk { background: rgba(0, 183, 255, 0.2); color: #00b7ff; }
    .platform-icon.gaana { background: rgba(233, 30, 99, 0.2); color: #E91E63; }
    .platform-icon.hungama { background: rgba(255, 193, 7, 0.2); color: #FFC107; }
    .platform-icon.soundcloud { background: rgba(255, 85, 0, 0.2); color: #FF5500; }
    .platform-icon.tidal { background: rgba(0, 255, 255, 0.2); color: #00FFFF; }
    .platform-icon.deezer { background: rgba(255, 0, 153, 0.2); color: #FF0099; }
    .platform-icon.pandora { background: rgba(0, 150, 255, 0.2); color: #0096FF; }
    .platform-icon.itunes { background: rgba(251, 188, 5, 0.2); color: #FBBC05; }
    .platform-icon.facebook { background: rgba(24, 119, 242, 0.2); color: #1877F2; }
    .platform-icon.instagram { background: rgba(225, 48, 108, 0.2); color: #E1306C; }
    .platform-icon.tiktok { background: rgba(0, 0, 0, 0.5); color: #fff; }
    .platform-icon.twitter { background: rgba(29, 161, 242, 0.2); color: #1DA1F2; }
    .platform-icon.tidal { background: rgba(0, 0, 0, 0.3); color: #fff; }
    .platform-icon.deezer { background: linear-gradient(135deg, #ff0099, #4930e6); color: #fff; }
    .platform-icon.pandora { background: rgba(0, 102, 204, 0.2); color: #0066CC; }
    .platform-icon.itunes { background: linear-gradient(135deg, #FA57C1, #66F1F3); color: #fff; }
    .platform-icon.shazam { background: rgba(0, 204, 153, 0.2); color: #00CC99; }
    .platform-icon.napster { background: rgba(255, 153, 0, 0.2); color: #FF9900; }
    .platform-icon.iheartradio { background: rgba(193, 0, 0, 0.2); color: #C10000; }
    .platform-icon.soundcloud { background: linear-gradient(135deg, #ff8800, #ff3300); color: #fff; }
    .platform-icon.bandcamp { background: rgba(28, 160, 161, 0.2); color: #1CA0A1; }
    .platform-icon.qobuz { background: rgba(0, 0, 0, 0.3); color: #fff; }
    .platform-icon.beatport { background: rgba(158, 0, 255, 0.2); color: #9E00FF; }
    .platform-icon.resso { background: linear-gradient(135deg, #FF0050, #00E0FF); color: #fff; }
    .platform-icon.linemusic { background: rgba(6, 199, 85, 0.2); color: #06C755; }
    .platform-icon.kkbox { background: rgba(0, 153, 255, 0.2); color: #0099FF; }
    .platform-icon.anghami { background: linear-gradient(135deg, #00b7ff, #556270); color: #fff; }
    .platform-icon.yandex { background: rgba(255, 0, 0, 0.2); color: #FC0; }
    .platform-icon.boomplay { background: rgba(255, 215, 0, 0.2); color: #FFD700; }
    .platform-icon.mdundo { background: rgba(51, 153, 255, 0.2); color: #3399FF; }
    
    .platform-name {
        font-size: 14px;
        font-weight: 500;
        flex: 1;
    }
    
    .section-divider {
        height: 1px;
        background: rgba(255,255,255,0.1);
        margin: 30px 0;
    }
    
    .social-section {
        background: rgba(0,0,0,0.2);
        border-radius: 15px;
        padding: 25px;
    }
    .social-note {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px;
        background: rgba(255, 193, 7, 0.1);
        border-radius: 10px;
        border: 1px solid rgba(255, 193, 7, 0.3);
        margin-top: 20px;
    }
    .social-note i {
        color: #ffc107;
        font-size: 20px;
    }
    .social-note p {
        font-size: 13px;
        color: rgba(255,255,255,0.7);
        line-height: 1.5;
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
</style>

<div class="release-container">
    <!-- Progress Header -->
    <div class="progress-header">
        <h1>Select Stores & Platforms</h1>
        <div class="progress-bar">
            <div class="progress-line" style="width: 37.5%;"></div>
            <div class="progress-step completed">
                <div class="step-number"><i class="mdi mdi-check"></i></div>
                <span class="step-label">Release Details</span>
            </div>
            <div class="progress-step active">
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

    <form method="POST" class="form-card">
        <input type="hidden" name="action" id="formAction" value="save">
        
        <!-- Streaming Platforms -->
        <div class="form-section-title">
            <i class="mdi mdi-music-box"></i>
            Streaming Platforms <span style="color: rgba(255,255,255,0.5); font-size: 12px;">(<?php echo count($platforms); ?>+ stores)</span>
            <span class="badge">You keep 100% of streaming revenue</span>
            <button type="button" class="btn-select-all" onclick="toggleAllPlatforms(true)" style="margin-left: auto; font-size: 12px; padding: 5px 12px; background: rgba(0,212,170,0.2); border: 1px solid #00d4aa; color: #00d4aa; border-radius: 5px; cursor: pointer;">Select All</button>
            <button type="button" class="btn-deselect-all" onclick="toggleAllPlatforms(false)" style="margin-left: 8px; font-size: 12px; padding: 5px 12px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: rgba(255,255,255,0.8); border-radius: 5px; cursor: pointer;">None</button>
        </div>
        
        <div class="platforms-grid" id="streamingPlatforms">
            <?php foreach ($platforms as $platform): ?>
                <?php 
                $icon_class = strtolower(str_replace([' ', 'Music'], ['', ''], $platform['platform_name']));
                $icon_map = [
                    'spotify' => 'mdi-spotify',
                    'apple' => 'mdi-apple',
                    'applemusic' => 'mdi-apple',
                    'youtube' => 'mdi-youtube',
                    'youtubemusic' => 'mdi-youtube',
                    'amazon' => 'mdi-amazon',
                    'amazonmusic' => 'mdi-amazon',
                    'jiosaavn' => 'mdi-music',
                    'wynk' => 'mdi-music-note',
                    'gaana' => 'mdi-music-circle',
                    'hungama' => 'mdi-play-circle',
                    'soundcloud' => 'mdi-soundcloud',
                    'tidal' => 'mdi-wave',
                    'deezer' => 'mdi-music-note-eighth',
                    'pandora' => 'mdi-pandora',
                    'itunes' => 'mdi-itunes',
                    'shazam' => 'mdi-magnify',
                    'napster' => 'mdi-music-box',
                    'iheartradio' => 'mdi-heart',
                    'tunein' => 'mdi-radio',
                    'audiomack' => 'mdi-music',
                    'bandcamp' => 'mdi-bandcamp',
                    'qobuz' => 'mdi-music',
                    'rhapsody' => 'mdi-music',
                    'beatport' => 'mdi-music',
                    'napster' => 'mdi-music-box',
                    'resso' => 'mdi-music',
                    'linemusic' => 'mdi-music',
                    'kkbox' => 'mdi-music',
                    'qqmusic' => 'mdi-music',
                    'netease' => 'mdi-music',
                    'joox' => 'mdi-music',
                    'genie' => 'mdi-music',
                    'melon' => 'mdi-music',
                    'anghami' => 'mdi-music',
                    'yandex' => 'mdi-music',
                    'boomplay' => 'mdi-music',
                    'mdundo' => 'mdi-music',
                    'tiktok' => 'mdi-music',
                    'instagram' => 'mdi-instagram',
                    'facebook' => 'mdi-facebook',
                    'snapchat' => 'mdi-snapchat',
                    'twitch' => 'mdi-twitch',
                    'peloton' => 'mdi-bike',
                    'waze' => 'mdi-map-marker',
                    'uber' => 'mdi-car',
                    'lyft' => 'mdi-car'
                ];
                $icon = $icon_map[$icon_class] ?? 'mdi-music';
                ?>
                <label class="platform-card <?php echo $platform['is_selected'] ? 'selected' : ''; ?>">
                    <input type="checkbox" name="platforms[]" value="<?php echo htmlspecialchars($platform['platform_name']); ?>" 
                           <?php echo $platform['is_selected'] ? 'checked' : ''; ?>
                           onchange="this.closest('.platform-card').classList.toggle('selected', this.checked)">
                    <div class="platform-icon <?php echo $icon_class; ?>">
                        <i class="mdi <?php echo $icon; ?>"></i>
                    </div>
                    <span class="platform-name"><?php echo htmlspecialchars($platform['platform_name']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        
        <div class="section-divider"></div>
        
        <!-- Social Platforms -->
        <div class="form-section-title">
            <i class="mdi mdi-share-variant"></i>
            Social Platforms
            <button type="button" class="btn-select-all" onclick="toggleAllSocial(true)" style="margin-left: auto; font-size: 12px; padding: 5px 12px; background: rgba(0,212,170,0.2); border: 1px solid #00d4aa; color: #00d4aa; border-radius: 5px; cursor: pointer;">Select All</button>
            <button type="button" class="btn-deselect-all" onclick="toggleAllSocial(false)" style="margin-left: 8px; font-size: 12px; padding: 5px 12px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: rgba(255,255,255,0.8); border-radius: 5px; cursor: pointer;">None</button>
        </div>
        
        <div class="social-section">
            <div class="platforms-grid">
                <label class="platform-card selected">
                    <input type="checkbox" name="social[]" value="Facebook" checked
                           onchange="this.closest('.platform-card').classList.toggle('selected', this.checked)">
                    <div class="platform-icon facebook">
                        <i class="mdi mdi-facebook"></i>
                    </div>
                    <span class="platform-name">Facebook</span>
                </label>
                <label class="platform-card selected">
                    <input type="checkbox" name="social[]" value="Instagram" checked
                           onchange="this.closest('.platform-card').classList.toggle('selected', this.checked)">
                    <div class="platform-icon instagram">
                        <i class="mdi mdi-instagram"></i>
                    </div>
                    <span class="platform-name">Instagram</span>
                </label>
                <label class="platform-card selected">
                    <input type="checkbox" name="social[]" value="TikTok" checked
                           onchange="this.closest('.platform-card').classList.toggle('selected', this.checked)">
                    <div class="platform-icon tiktok">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                        </svg>
                    </div>
                    <span class="platform-name">TikTok</span>
                </label>
                <label class="platform-card selected">
                    <input type="checkbox" name="social[]" value="Twitter" checked
                           onchange="this.closest('.platform-card').classList.toggle('selected', this.checked)">
                    <div class="platform-icon twitter">
                        <i class="mdi mdi-twitter"></i>
                    </div>
                    <span class="platform-name">Twitter / X</span>
                </label>
            </div>
            
            <div class="social-note">
                <i class="mdi mdi-information"></i>
                <p>
                    Select social platforms where you want to promote your release. All platforms are selected by default.
                </p>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="/index.php?q=release-create&id=<?php echo $release_id; ?>&step=1" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i>
                Back
            </a>
            <div style="display: flex; gap: 15px;">
                <button type="submit" class="btn btn-secondary" onclick="document.getElementById('formAction').value='save'">
                    <i class="mdi mdi-content-save"></i>
                    Save & Exit
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
function toggleAllPlatforms(select) {
    const grid = document.getElementById('streamingPlatforms');
    const checkboxes = grid.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.checked = select;
        cb.closest('.platform-card').classList.toggle('selected', select);
    });
}

function toggleAllSocial(select) {
    const grid = document.querySelector('.social-section .platforms-grid');
    const checkboxes = grid.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.checked = select;
        cb.closest('.platform-card').classList.toggle('selected', select);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
