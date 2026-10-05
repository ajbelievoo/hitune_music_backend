<?php
/**
 * HiTune Music Distribution - Releases Management
 * TuneCore-like release management with full workflow
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ecosystem_sync.php';
requireLogin();

$pageTitle       = 'My Releases - HiTune Music Distribution';
$metaDescription = 'Manage all your music releases on HiTune. Track distribution status, view analytics, and submit new releases.';
$canonicalUrl    = 'https://web.hitune.in/index.php?q=releases';
$path            = 'releases';
include __DIR__ . '/../includes/header_premium.php';

// Get user releases with analytics
$user = getCurrentUser();
$userId = $user['id'];

$stmt = $conn->prepare("SELECT * FROM releases WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$releases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Per-release platform delivery rows — needed for the ecosystem-aware labels
// (Live on HiTune / Globally Distributed / Not Distributed Globally)
$platform_rows_by_release = [];
$rel_ids = array_map(fn($r) => (int)$r['id'], $releases);
if ($rel_ids) {
    $in = implode(',', $rel_ids);
    $pr = $conn->query("SELECT release_id, platform_name, delivery_status, is_selected FROM release_platforms WHERE release_id IN ($in)");
    while ($pr && ($p = $pr->fetch_assoc())) $platform_rows_by_release[$p['release_id']][] = $p;
}

// Function to get release analytics
function getReleaseAnalytics($conn, $release_id) {
    $query = "SELECT COALESCE(SUM(streams), 0) as total_streams, COALESCE(SUM(revenue), 0) as total_revenue 
              FROM release_analytics WHERE release_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Status labels and colors — CSS classes from premium-theme.css
$status_config = [
    'draft'       => ['label' => 'Draft',       'class' => 'badge badge-draft',        'icon' => 'mdi-pencil'],
    'submitted'   => ['label' => 'Submitted',   'class' => 'badge badge-submitted',    'icon' => 'mdi-send'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'badge badge-in-progress',  'icon' => 'mdi-progress-clock'],
    'ready'       => ['label' => 'Ready',         'class' => 'badge badge-ready',        'icon' => 'mdi-check-circle'],
    'live'        => ['label' => 'Live on Stores','class' => 'badge badge-live',         'icon' => 'mdi-broadcast'],
    'takedown_requested' => ['label' => 'Takedown Req.', 'class' => 'badge badge-rejected', 'icon' => 'mdi-timer-off'],
    'taken_down'  => ['label' => 'Taken Down',    'class' => 'badge badge-draft',        'icon' => 'mdi-cancel'],
    'rejected'    => ['label' => 'Rejected',    'class' => 'badge badge-rejected',     'icon' => 'mdi-close-circle'],
];
?>

<style>
    .releases-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .releases-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
    }
    .releases-header h1 {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .btn-add-release {
        padding: 14px 30px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        color: #000;
        border: none;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    .btn-add-release:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 200, 83, 0.3);
    }
    .releases-grid {
        display: grid;
        gap: 25px;
    }
    .release-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 30px;
        transition: all 0.3s;
        display: flex;
        gap: 25px;
        align-items: flex-start;
    }
    .release-card:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateY(-3px);
        border-color: rgba(0, 212, 170, 0.3);
    }
    .release-cover {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        flex-shrink: 0;
        overflow: hidden;
    }
    .release-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .release-info {
        flex: 1;
    }
    .release-info h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .release-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 12px;
    }
    .release-meta span {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .release-meta span i {
        font-size: 16px;
    }
    .release-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-draft {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
    }
    .status-submitted {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }
    .status-inprogress {
        background: rgba(79, 172, 254, 0.2);
        color: #4facfe;
    }
    .status-ready {
        background: rgba(0, 200, 83, 0.2);
        color: #00c853;
    }
    .status-rejected {
        background: rgba(255, 82, 82, 0.2);
        color: #ff5252;
    }
    .release-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .btn-action {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
        border: none;
    }
    .btn-edit {
        background: rgba(0, 212, 170, 0.2);
        color: #00d4aa;
        border: 1px solid rgba(0, 212, 170, 0.3);
    }
    .btn-edit:hover {
        background: rgba(0, 212, 170, 0.3);
    }
    .btn-view {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }
    .btn-view:hover {
        background: rgba(255, 255, 255, 0.15);
    }
    .progress-bar-mini {
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        margin-top: 15px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #00d4aa, #00c853);
        border-radius: 2px;
        transition: width 0.3s;
    }
    .empty-state {
        text-align: center;
        padding: 100px 20px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
    }
    .empty-state span {
        font-size: 64px;
        color: rgba(255, 255, 255, 0.3);
        margin-bottom: 20px;
    }
    .empty-state h3 {
        font-size: 24px;
        margin-bottom: 10px;
    }
    .empty-state p {
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 25px;
    }
    .status-checklist {
        display: flex;
        gap: 15px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    .check-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
    }
    .check-item.completed {
        color: #00c853;
    }
    .check-item i {
        font-size: 14px;
    }
    
    /* Release Analytics */
    .release-analytics {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        padding: 12px 15px;
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .analytics-stat {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .analytics-stat i {
        font-size: 18px;
    }
    .analytics-stat.streams i {
        color: #4facfe;
    }
    .analytics-stat.revenue i {
        color: #00c853;
    }
    .analytics-stat span {
        font-size: 14px;
        font-weight: 600;
    }
    .analytics-stat.streams span {
        color: #4facfe;
    }
    .analytics-stat.revenue span {
        color: #00c853;
    }
    .analytics-stat small {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        margin-left: 4px;
    }
</style>

<div class="releases-container">
    <div class="releases-header">
        <h1>My Releases</h1>
        <a href="/index.php?q=release-create" class="btn-add-release">
            <i class="mdi mdi-plus"></i>
            New Release
        </a>
    </div>

    <?php if (count($releases) > 0): ?>
        <div class="releases-grid">
            <?php foreach ($releases as $release):
                $status = $status_config[$release['status']] ?? $status_config['draft'];
                // ecosystem-aware label (HiTune vs global DSP scope — strategy doc §7)
                $status['label'] = eco_dashboard_status($release['status'], $platform_rows_by_release[$release['id']] ?? []);
                $can_edit = in_array($release['status'], ['draft', 'rejected']);
                
                // Get checklist status
                $step = $release['step_completed'];
                $has_artwork = !empty($release['cover_art']);
            ?>
                <div class="release-card">
                    <div class="release-cover">
                        <?php if ($has_artwork): ?>
                            <img src="<?php echo htmlspecialchars($release['cover_art_path']); ?>" alt="Cover">
                        <?php else: ?>
                            <i class="mdi mdi-music-note"></i>
                        <?php endif; ?>
                    </div>
                    <div class="release-info">
                        <h3><?php echo htmlspecialchars($release['title']); ?></h3>
                        <div class="release-meta">
                            <span><i class="mdi mdi-account"></i> <?php echo htmlspecialchars($release['primary_artist']); ?></span>
                            <span><i class="mdi mdi-calendar"></i> <?php echo $release['release_date'] ? date('M j, Y', strtotime($release['release_date'])) : 'No date set'; ?></span>
                            <span><i class="mdi mdi-album"></i> <?php echo ucfirst($release['release_type']); ?></span>
                            <span><i class="mdi mdi-tag"></i> <?php echo htmlspecialchars($release['primary_genre'] ?? 'No genre'); ?></span>
                        </div>
                        <span class="release-status <?php echo $status['class']; ?>">
                            <i class="mdi <?php echo $status['icon']; ?>"></i>
                            <?php echo $status['label']; ?>
                        </span>
                        
                        <?php if ($release['status'] === 'submitted'): ?>
                        <p style="margin-top: 10px; font-size: 13px; color: rgba(255,255,255,0.6);">
                            <i class="mdi mdi-information" style="color: #667eea;"></i>
                            Your release is under review. This typically takes 1–3 business days.
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($release['status'] === 'draft'): ?>
                        <div class="progress-bar-mini">
                            <div class="progress-fill" style="width: <?php echo $release['progress_percent']; ?>%"></div>
                        </div>
                        <div class="status-checklist">
                            <span class="check-item <?php echo $step >= 1 ? 'completed' : ''; ?>">
                                <i class="mdi <?php echo $step >= 1 ? 'mdi-check-circle' : 'mdi-circle-outline'; ?>"></i> Details
                            </span>
                            <span class="check-item <?php echo $step >= 2 ? 'completed' : ''; ?>">
                                <i class="mdi <?php echo $step >= 2 ? 'mdi-check-circle' : 'mdi-circle-outline'; ?>"></i> Stores
                            </span>
                            <span class="check-item <?php echo $step >= 3 ? 'completed' : ''; ?>">
                                <i class="mdi <?php echo $step >= 3 ? 'mdi-check-circle' : 'mdi-circle-outline'; ?>"></i> Tracks
                            </span>
                            <span class="check-item <?php echo $step >= 4 ? 'completed' : ''; ?>">
                                <i class="mdi <?php echo $step >= 4 ? 'mdi-check-circle' : 'mdi-circle-outline'; ?>"></i> Artwork
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($release['status'] === 'in_progress'): ?>
                        <p style="margin-top: 10px; font-size: 13px; color: rgba(255,255,255,0.5);">
                            <i class="mdi mdi-information" style="color: #4facfe;"></i> 
                            Your release is being distributed to stores. This may take 3–7 days.
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($release['status'] === 'ready'): 
                            $analytics = getReleaseAnalytics($conn, $release['id']);
                            $hasAnalytics = $analytics['total_streams'] > 0 || $analytics['total_revenue'] > 0;
                        ?>
                        <p style="margin-top: 10px; font-size: 13px; color: rgba(255,255,255,0.5);">
                            <i class="mdi mdi-check-circle" style="color: #00c853;"></i> 
                            Your release is live on all selected platforms!
                        </p>
                        <?php if ($hasAnalytics): ?>
                        <div class="release-analytics">
                            <div class="analytics-stat streams">
                                <i class="mdi mdi-play-circle"></i>
                                <span><?php echo number_format($analytics['total_streams']); ?><small>streams</small></span>
                            </div>
                            <div class="analytics-stat revenue">
                                <i class="mdi mdi-currency-inr"></i>
                                <span>₹<?php echo number_format($analytics['total_revenue'], 2); ?><small>revenue</small></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($release['status'] === 'rejected'): ?>
                        <div style="margin-top: 12px; padding: 12px 15px; background: rgba(255,82,82,0.1); border: 1px solid rgba(255,82,82,0.3); border-radius: 10px;">
                            <?php if (!empty($release['admin_notes'])): ?>
                            <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin-bottom: 10px;">
                                <?php echo nl2br(htmlspecialchars($release['admin_notes'])); ?>
                            </p>
                            <?php endif; ?>
                            <a href="/index.php?q=release-create&id=<?php echo $release['id']; ?>&step=1"
                               style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: linear-gradient(135deg, #ff5252, #00b7ff); color: #fff; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none;">
                                <i class="mdi mdi-refresh"></i> Resubmit
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="release-actions">
                        <?php if ($can_edit): ?>
                            <a href="/index.php?q=release-create&id=<?php echo $release['id']; ?>&step=<?php echo min($step + 1, 4); ?>" class="btn-action btn-edit">
                                <i class="mdi mdi-pencil"></i>
                                <?php 
                                if ($release['status'] === 'rejected') {
                                    echo 'Resubmit';
                                } elseif ($step < 4) {
                                    echo 'Continue';
                                } else {
                                    echo 'Edit';
                                }
                                ?>
                            </a>
                        <?php else: ?>
                            <a href="/index.php?q=release-view&id=<?php echo $release['id']; ?>" class="btn-action btn-view">
                                <i class="mdi mdi-eye"></i>
                                View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <span class="mdi mdi-album"></span>
            <h3>No releases yet</h3>
            <p>Create your first release and get your music on 150+ platforms</p>
            <a href="/index.php?q=release-create" class="btn-add-release">
                <i class="mdi mdi-plus"></i>
                Create Release
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
