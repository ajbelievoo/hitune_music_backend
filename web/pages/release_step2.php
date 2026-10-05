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

// Verify release exists
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

// All 82+ platforms organized by category
$platforms = [
    'Major Streaming' => [
        ['name' => 'Spotify', 'icon' => 'mdi-spotify', 'color' => 'linear-gradient(135deg, #1DB954, #191414)'],
        ['name' => 'Apple Music', 'icon' => 'mdi-apple', 'color' => 'linear-gradient(135deg, #FA243C, #000000)'],
        ['name' => 'YouTube Music', 'icon' => 'mdi-youtube', 'color' => 'linear-gradient(135deg, #FF0000, #282828)'],
        ['name' => 'Amazon Music', 'icon' => 'mdi-amazon', 'color' => 'linear-gradient(135deg, #00C6FF, #0072FF)'],
        ['name' => 'Tidal', 'icon' => 'mdi-wave', 'color' => 'linear-gradient(135deg, #000000, #FFFFFF)'],
        ['name' => 'Deezer', 'icon' => 'mdi-music-box', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'Pandora', 'icon' => 'mdi-radio', 'color' => 'linear-gradient(135deg, #005483, #3668FF)'],
        ['name' => 'SoundCloud', 'icon' => 'mdi-soundcloud', 'color' => 'linear-gradient(135deg, #FF5500, #FF8800)'],
        ['name' => 'Napster', 'icon' => 'mdi-music-box', 'color' => 'linear-gradient(135deg, #FF9900, #FF5500)'],
        ['name' => 'iHeartRadio', 'icon' => 'mdi-heart', 'color' => 'linear-gradient(135deg, #C10000, #FF0000)'],
        ['name' => 'Shazam', 'icon' => 'mdi-magnify', 'color' => 'linear-gradient(135deg, #00CC99, #0088FF)'],
        ['name' => 'Qobuz', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #1A1A1A, #FF5500)'],
    ],
    'India Streaming' => [
        ['name' => 'JioSaavn', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #2BC47C, #1E8C5A)'],
        ['name' => 'Gaana', 'icon' => 'mdi-music-circle', 'color' => 'linear-gradient(135deg, #E91E63, #C2185B)'],
        ['name' => 'Wynk Music', 'icon' => 'mdi-music-note', 'color' => 'linear-gradient(135deg, #FF6B00, #FF8E00)'],
        ['name' => 'Hungama', 'icon' => 'mdi-play-circle', 'color' => 'linear-gradient(135deg, #FF4081, #C51162)'],
        ['name' => 'Resso', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF0050, #00E0FF)'],
        ['name' => 'Times Music', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF0000, #990000)'],
    ],
    'Asian Streaming' => [
        ['name' => 'QQ Music', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #31C27C, #00A651)'],
        ['name' => 'NetEase Cloud', 'icon' => 'mdi-cloud-music', 'color' => 'linear-gradient(135deg, #C20C0C, #8B0000)'],
        ['name' => 'KuGou', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00BFFF, #1E90FF)'],
        ['name' => 'Joox', 'icon' => 'mdi-music-box-outline', 'color' => 'linear-gradient(135deg, #00B894, #00CEC9)'],
        ['name' => 'Line Music', 'icon' => 'mdi-chat', 'color' => 'linear-gradient(135deg, #00B900, #00E676)'],
        ['name' => 'KKBox', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #0099FF, #0066CC)'],
        ['name' => 'AWA', 'icon' => 'mdi-waves', 'color' => 'linear-gradient(135deg, #00BCD4, #0097A7)'],
        ['name' => 'Genie', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
        ['name' => 'Melon', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
        ['name' => 'Bugs', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF1744, #D50000)'],
    ],
    'Social Media & Short Video' => [
        ['name' => 'TikTok', 'icon' => 'mdi-music-note', 'color' => 'linear-gradient(135deg, #000000, #FF0050)'],
        ['name' => 'Instagram', 'icon' => 'mdi-instagram', 'color' => 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)'],
        ['name' => 'Facebook', 'icon' => 'mdi-facebook', 'color' => 'linear-gradient(135deg, #1877F2, #4267B2)'],
        ['name' => 'Snapchat', 'icon' => 'mdi-snapchat', 'color' => 'linear-gradient(135deg, #FFFC00, #000000)'],
        ['name' => 'Triller', 'icon' => 'mdi-video', 'color' => 'linear-gradient(135deg, #FF0000, #000000)'],
        ['name' => 'Likee', 'icon' => 'mdi-heart', 'color' => 'linear-gradient(135deg, #FF0050, #00D4FF)'],
        ['name' => 'Trebel', 'icon' => 'mdi-download', 'color' => 'linear-gradient(135deg, #00C853, #00D4AA)'],
    ],
    'Europe & Russia' => [
        ['name' => 'Yandex Music', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF0000, #FC0)'],
        ['name' => 'Zvooq', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00b7ff, #556270)'],
        ['name' => 'Anghami', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00b7ff, #556270)'],
        ['name' => 'Musixmatch', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF5500, #FF9900)'],
        ['name' => 'Genius', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FFFF64, #000000)'],
    ],
    'Africa & Middle East' => [
        ['name' => 'Boomplay', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FFD700, #FFAA00)'],
        ['name' => 'Mdundo', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #3399FF, #0066CC)'],
        ['name' => 'Rotana', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF1744, #D50000)'],
    ],
    'DJ & Remix' => [
        ['name' => 'Beatport', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #9E00FF, #FF00FF)'],
        ['name' => 'Traxsource', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF5500, #FF9900)'],
        ['name' => 'JunoDownload', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
        ['name' => '7digital', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #0066CC, #0099FF)'],
    ],
    'In-Car & Apps' => [
        ['name' => 'Peloton', 'icon' => 'mdi-bike', 'color' => 'linear-gradient(135deg, #FF0000, #990000)'],
        ['name' => 'Waze', 'icon' => 'mdi-map-marker', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
        ['name' => 'Uber', 'icon' => 'mdi-car', 'color' => 'linear-gradient(135deg, #000000, #333333)'],
        ['name' => 'Lyft', 'icon' => 'mdi-car', 'color' => 'linear-gradient(135deg, #FF00BF, #FF66CC)'],
        ['name' => 'Twitch', 'icon' => 'mdi-twitch', 'color' => 'linear-gradient(135deg, #9146FF, #6441A5)'],
    ],
    'Social Platforms' => [
        ['name' => 'TikTok', 'icon' => 'mdi-music-note', 'color' => 'linear-gradient(135deg, #000000, #FF0050)'],
        ['name' => 'Instagram', 'icon' => 'mdi-instagram', 'color' => 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)'],
        ['name' => 'Facebook', 'icon' => 'mdi-facebook', 'color' => 'linear-gradient(135deg, #1877F2, #4267B2)'],
        ['name' => 'YouTube Shorts', 'icon' => 'mdi-youtube', 'color' => 'linear-gradient(135deg, #FF0000, #282828)'],
        ['name' => 'Snapchat', 'icon' => 'mdi-snapchat', 'color' => 'linear-gradient(135deg, #FFFC00, #000000)'],
        ['name' => 'Triller', 'icon' => 'mdi-video', 'color' => 'linear-gradient(135deg, #FF0000, #000000)'],
        ['name' => 'Likee', 'icon' => 'mdi-heart', 'color' => 'linear-gradient(135deg, #FF0050, #00D4FF)'],
        ['name' => 'Trebel', 'icon' => 'mdi-download', 'color' => 'linear-gradient(135deg, #00C853, #00D4AA)'],
        ['name' => 'Twitter / X', 'icon' => 'mdi-twitter', 'color' => 'linear-gradient(135deg, #1DA1F2, #14171A)'],
    ],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    // Update selected platforms (streaming + social)
    $selected_platforms = $_POST['platforms'] ?? [];
    
    // Ensure all platforms exist in database first
    $all_platforms = [];
    foreach ($platforms as $cat => $list) {
        foreach ($list as $p) {
            $all_platforms[] = $p['name'];
        }
    }
    
    // Get existing platforms for this release
    $existing = [];
    $res = $conn->query("SELECT platform_name FROM release_platforms WHERE release_id = $release_id");
    while ($row = $res->fetch_assoc()) {
        $existing[] = $row['platform_name'];
    }
    
    // Insert missing platforms
    $insert_stmt = $conn->prepare("INSERT INTO release_platforms (release_id, platform_name, is_selected) VALUES (?, ?, 0)");
    foreach ($all_platforms as $platform_name) {
        if (!in_array($platform_name, $existing)) {
            $insert_stmt->bind_param("is", $release_id, $platform_name);
            $insert_stmt->execute();
        }
    }
    
    // Clear all first
    $stmt = $conn->prepare("UPDATE release_platforms SET is_selected = 0 WHERE release_id = ?");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    
    // Mark selected
    if (!empty($selected_platforms)) {
        $stmt = $conn->prepare("UPDATE release_platforms SET is_selected = 1 WHERE release_id = ? AND platform_name = ?");
        foreach ($selected_platforms as $platform) {
            $stmt->bind_param("is", $release_id, $platform);
            $stmt->execute();
        }
    }

    // HiTune ecosystem placements are always-on — instant local publish per
    // the strategy doc, not subject to the user's DSP selection
    require_once __DIR__ . '/../includes/ecosystem_sync.php';
    eco_ensure_platforms($conn, $release_id);
    $conn->query("UPDATE release_platforms SET is_selected = 1 WHERE release_id = " . intval($release_id) . " AND platform_name IN ('HiTune Music','IyolMe')");

    // Update progress
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

// Get current selected platforms
$selected = [];
$result = $conn->query("SELECT platform_name FROM release_platforms WHERE release_id = $release_id AND is_selected = 1");
while ($row = $result->fetch_assoc()) {
    $selected[] = $row['platform_name'];
}

// Count total
$total_platforms = 0;
foreach ($platforms as $category => $list) {
    $total_platforms += count($list);
}

$pageTitle = 'Create Release - Step 2: Select Stores';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .release-container {
        max-width: 1200px;
        margin: 100px auto 60px;
        padding: 0 20px;
    }
    .progress-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .progress-header h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .form-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 40px;
    }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .section-title {
        font-size: 24px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title i {
        color: #00d4aa;
    }
    .platform-count {
        font-size: 14px;
        color: rgba(255,255,255,0.5);
    }
    .category-title {
        font-size: 18px;
        font-weight: 600;
        margin: 30px 0 15px;
        color: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .category-title::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 2px;
    }
    .platforms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .platform-card {
        position: relative;
        background: rgba(255, 255, 255, 0.03);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .platform-card:hover {
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-3px);
    }
    .platform-card.selected {
        border-color: #00d4aa;
        background: rgba(0, 212, 170, 0.08);
    }
    .platform-card input {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 20px;
        height: 20px;
        accent-color: #00d4aa;
    }
    .platform-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 12px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
    }
    .platform-name {
        font-size: 13px;
        font-weight: 500;
    }
    .select-all-btns {
        display: flex;
        gap: 10px;
    }
    .btn-select {
        padding: 8px 16px;
        font-size: 12px;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid;
        background: transparent;
        transition: all 0.3s;
    }
    .btn-select-all {
        border-color: #00d4aa;
        color: #00d4aa;
    }
    .btn-select-all:hover {
        background: rgba(0, 212, 170, 0.2);
    }
    .btn-select-none {
        border-color: rgba(255,255,255,0.3);
        color: rgba(255,255,255,0.7);
    }
    .btn-select-none:hover {
        background: rgba(255,255,255,0.1);
    }
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
    }
    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    .btn-primary {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 183, 255, 0.3);
    }
    .selected-count {
        background: linear-gradient(135deg, #00d4aa, #00b894);
        color: #000;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
</style>

<div class="release-container">
    <div class="progress-header">
        <h1>Select Distribution Stores</h1>
        <p>Choose where your music will be available (<?php echo $total_platforms; ?>+ platforms)</p>
    </div>

    <form method="POST" class="form-card">
        <input type="hidden" name="action" id="formAction" value="save">
        
        <div class="section-header">
            <div class="section-title">
                <i class="mdi mdi-store"></i>
                Streaming Platforms
                <span class="platform-count" id="selectedCount">0 selected</span>
            </div>
            <div class="select-all-btns">
                <button type="button" class="btn-select btn-select-all" onclick="toggleAll(true)">
                    <i class="mdi mdi-check-all"></i> Select All
                </button>
                <button type="button" class="btn-select btn-select-none" onclick="toggleAll(false)">
                    <i class="mdi mdi-close-circle"></i> None
                </button>
            </div>
        </div>

        <?php foreach ($platforms as $category => $list): ?>
            <div class="category-title"><?php echo htmlspecialchars($category); ?> <span style="color: rgba(255,255,255,0.4); font-size: 13px;">(<?php echo count($list); ?>)</span></div>
            <div class="platforms-grid">
                <?php foreach ($list as $platform): 
                    $is_checked = in_array($platform['name'], $selected);
                ?>
                    <label class="platform-card <?php echo $is_checked ? 'selected' : ''; ?>" onclick="toggleCard(this)">
                        <input type="checkbox" name="platforms[]" value="<?php echo htmlspecialchars($platform['name']); ?>" 
                               <?php echo $is_checked ? 'checked' : ''; ?> 
                               onchange="updateCount()">
                        <div class="platform-icon" style="background: <?php echo $platform['color']; ?>">
                            <i class="mdi <?php echo $platform['icon']; ?>"></i>
                        </div>
                        <div class="platform-name"><?php echo htmlspecialchars($platform['name']); ?></div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <a href="/index.php?q=release-create&id=<?php echo $release_id; ?>&step=1" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i> Back
            </a>
            <div style="display: flex; gap: 15px;">
                <button type="submit" class="btn btn-secondary" onclick="document.getElementById('formAction').value='save'">
                    <i class="mdi mdi-content-save"></i> Save
                </button>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('formAction').value='save_continue'">
                    Continue <i class="mdi mdi-arrow-right"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleCard(card) {
    const checkbox = card.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    card.classList.toggle('selected', checkbox.checked);
    updateCount();
}

function updateCount() {
    const checked = document.querySelectorAll('input[name="platforms[]"]:checked').length;
    document.getElementById('selectedCount').textContent = checked + ' selected';
}

function toggleAll(select) {
    const checkboxes = document.querySelectorAll('input[name="platforms[]"]');
    const cards = document.querySelectorAll('.platform-card');
    checkboxes.forEach((cb, i) => {
        cb.checked = select;
        cards[i].classList.toggle('selected', select);
    });
    updateCount();
}

// Init count
updateCount();
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
