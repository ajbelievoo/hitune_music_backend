<?php
/**
 * HiTune Music Distribution - Release View (Read-Only)
 * For viewing submitted/in_progress/ready/rejected releases
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/email_helper.php';
require_once __DIR__ . '/../includes/ecosystem_sync.php';
requireLogin();

$user = getCurrentUser();
$release_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($release_id === 0) {
    header('Location: /index.php?q=releases');
    exit;
}

// Fetch release by ID, verify ownership with prepared statement
$stmt = $conn->prepare("SELECT r.*, COUNT(rt.id) as track_count 
    FROM releases r 
    LEFT JOIN release_tracks rt ON r.id = rt.release_id 
    WHERE r.id = ? AND r.user_id = ?
    GROUP BY r.id");
$stmt->bind_param("ii", $release_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$release = $result->fetch_assoc();

if (!$release) {
    header('Location: /index.php?q=releases');
    exit;
}

// Handle royalty splits (owner only, CSRF-protected) — doc §1 split royalty
require_once __DIR__ . '/../includes/splits.php';

if (isset($_POST['add_split'])) {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $splitTrack = (int)($_POST['split_track_id'] ?? 0);
    $splitEmail = trim($_POST['split_email'] ?? '');
    $splitPct   = (float)($_POST['split_pct'] ?? 0);
    // the track must belong to this release
    $stmt = $conn->prepare("SELECT id FROM release_tracks WHERE id = ? AND release_id = ?");
    $stmt->bind_param("ii", $splitTrack, $release_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $err = split_add($conn, $splitTrack, $release_id, $splitEmail, $splitPct);
        header("Location: /index.php?q=release-view&id=$release_id" . ($err ? "&split_error=" . urlencode($err) : "&split=added"));
        exit;
    }
    header("Location: /index.php?q=release-view&id=$release_id&split_error=" . urlencode('Unknown track'));
    exit;
}

if (isset($_POST['remove_split'])) {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $splitId = (int)($_POST['split_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE track_splits SET status='removed' WHERE id = ? AND release_id = ?");
    $stmt->bind_param("ii", $splitId, $release_id);
    $stmt->execute();
    header("Location: /index.php?q=release-view&id=$release_id&split=removed");
    exit;
}

// Handle takedown request (owner only, CSRF-protected)
if (isset($_POST['request_takedown'])) {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $reason = trim($_POST['takedown_reason'] ?? '');
    $allowedFrom = ['submitted', 'in_progress', 'ready', 'live'];
    if (in_array($release['status'], $allowedFrom, true) && $reason !== '') {
        $stmt = $conn->prepare("UPDATE releases SET status = 'takedown_requested', takedown_reason = ?, takedown_requested_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $reason, $release_id, $user['id']);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, ?, 'Takedown Requested', 'takedown_requested', ?, 'user')");
        $stmt->bind_param("iis", $release_id, $user['id'], $reason);
        $stmt->execute();

        // Confirm to the artist
        sendReleaseStatusEmail($user['email'], $user['name'], $release['title'], 'takedown_requested', '');

        header("Location: /index.php?q=release-view&id=$release_id&takedown=requested");
        exit;
    }
    header("Location: /index.php?q=release-view&id=$release_id");
    exit;
}

// Get tracks with prepared statement
$tracks = [];
$stmt = $conn->prepare("SELECT * FROM release_tracks WHERE release_id = ? ORDER BY track_number");
$stmt->bind_param("i", $release_id);
$stmt->execute();
$tracksResult = $stmt->get_result();
while ($row = $tracksResult->fetch_assoc()) {
    $tracks[] = $row;
}

// Get platforms with prepared statement
$platforms = [];
$stmt = $conn->prepare("SELECT * FROM release_platforms WHERE release_id = ? AND is_selected = 1");
$stmt->bind_param("i", $release_id);
$stmt->execute();
$platformsResult = $stmt->get_result();
while ($row = $platformsResult->fetch_assoc()) {
    $platforms[] = $row;
}

// Collaborator royalty splits for this release (doc §1)
$splits = splits_for_release($conn, $release_id);

// Count live platforms (delivery_status based)
$public_count = 0;
$live_count = 0;
foreach ($platforms as $p) {
    if ($p['is_public']) $public_count++;
    if (($p['delivery_status'] ?? 'pending') === 'live') $live_count++;
}

// Status badge config using premium-theme.css classes
$status_config = [
    'draft'              => ['label' => 'Draft',              'badge_class' => 'badge badge-draft',       'icon' => 'mdi-pencil'],
    'submitted'          => ['label' => 'Submitted',          'badge_class' => 'badge badge-submitted',   'icon' => 'mdi-send'],
    'in_progress'        => ['label' => 'In Progress',        'badge_class' => 'badge badge-in-progress', 'icon' => 'mdi-progress-clock'],
    'ready'              => ['label' => 'Ready',              'badge_class' => 'badge badge-ready',       'icon' => 'mdi-check-circle'],
    'live'               => ['label' => 'Live on Stores',     'badge_class' => 'badge badge-live',        'icon' => 'mdi-broadcast'],
    'takedown_requested' => ['label' => 'Takedown Requested', 'badge_class' => 'badge badge-rejected',    'icon' => 'mdi-timer-off'],
    'taken_down'         => ['label' => 'Taken Down',         'badge_class' => 'badge badge-draft',       'icon' => 'mdi-cancel'],
    'rejected'           => ['label' => 'Rejected',           'badge_class' => 'badge badge-rejected',    'icon' => 'mdi-close-circle'],
];

$status = $status_config[$release['status']] ?? $status_config['draft'];
// ecosystem-aware label: distinguishes "Live on HiTune" from "Globally
// Distributed" and "Live on HiTune Music | Not Distributed Globally"
$status['label'] = eco_dashboard_status($release['status'], $platforms);

$pageTitle       = 'Release Details - ' . htmlspecialchars($release['title']) . ' | HiTune';
$metaDescription = 'View release details, distribution status, and track information for ' . htmlspecialchars($release['title']) . ' on HiTune Music Distribution.';
$canonicalUrl    = 'https://web.hitune.in/index.php?q=release-view&id=' . $release_id;
$path            = 'releases';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .view-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 100px 20px 60px;
    }
    .status-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    .status-header h1 {
        font-size: 28px;
        font-weight: 700;
    }
    .status-header h1 span {
        color: rgba(255,255,255,0.5);
        font-size: 14px;
        font-weight: 500;
        display: block;
        margin-top: 5px;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 600;
        background: rgba(255,255,255,0.1);
    }
    .status-badge i {
        font-size: 18px;
    }
    
    .checklist-sidebar {
        position: fixed;
        right: 40px;
        top: 120px;
        width: 280px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 15px;
        padding: 25px;
    }
    @media (max-width: 1400px) {
        .checklist-sidebar { display: none; }
    }
    .checklist-sidebar h3 {
        font-size: 14px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .checklist-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        font-size: 13px;
        color: rgba(255,255,255,0.6);
    }
    .checklist-item.completed {
        color: #00c853;
    }
    .checklist-item i {
        font-size: 18px;
    }
    
    .section-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 25px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .section-header h2 {
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-header h2 i {
        color: #00d4aa;
    }
    .status-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        background: rgba(0, 200, 83, 0.2);
        color: #00c853;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-indicator.pending {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    @media (max-width: 600px) {
        .info-grid { grid-template-columns: 1fr; }
    }
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .info-item.full-width {
        grid-column: 1 / -1;
    }
    .info-item label {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-item span {
        font-size: 15px;
        font-weight: 500;
    }
    
    .cover-preview {
        width: 200px;
        height: 200px;
        border-radius: 12px;
        overflow: hidden;
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cover-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .cover-preview i {
        font-size: 48px;
        color: rgba(255,255,255,0.3);
    }
    
    .platforms-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .platform-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: rgba(0, 212, 170, 0.1);
        border: 1px solid rgba(0, 212, 170, 0.3);
        border-radius: 20px;
        font-size: 13px;
    }
    .platform-tag i {
        color: #00d4aa;
    }
    
    .track-item {
        background: rgba(0,0,0,0.2);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .track-item:last-child {
        margin-bottom: 0;
    }
    .track-number {
        width: 28px;
        height: 28px;
        background: rgba(0, 212, 170, 0.2);
        color: #00d4aa;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        margin-right: 12px;
    }
    .track-title {
        font-size: 16px;
        font-weight: 600;
    }
    .track-meta {
        margin-top: 10px;
        padding-left: 40px;
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    
    .timeline {
        margin-top: 20px;
    }
    .timeline-item {
        display: flex;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .timeline-item:last-child {
        border-bottom: none;
    }
    .timeline-icon {
        width: 36px;
        height: 36px;
        background: rgba(0, 212, 170, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #00d4aa;
        flex-shrink: 0;
    }
    .timeline-content h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 3px;
    }
    .timeline-content p {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
    }
    
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 25px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        margin-top: 30px;
        transition: all 0.3s;
    }
    .btn-back:hover {
        background: rgba(255,255,255,0.15);
    }
</style>

<div class="view-container">
    <!-- Status Header -->
    <div class="status-header">
        <div>
            <h1>
                <?php echo htmlspecialchars($release['title']); ?>
                <span>by <?php echo htmlspecialchars($release['primary_artist']); ?></span>
            </h1>
        </div>
        <span class="<?php echo $status['badge_class']; ?>" style="font-size: 14px; padding: 10px 20px;">
            <i class="mdi <?php echo $status['icon']; ?>"></i>
            <?php echo $status['label']; ?>
        </span>
    </div>

    <!-- Status-specific message banner -->
    <?php if ($release['status'] === 'submitted'): ?>
    <div class="alert alert-info" style="margin-bottom: 25px;">
        <span class="mdi mdi-information"></span>
        Your release is under review. This typically takes 1–3 business days.
    </div>
    <?php elseif ($release['status'] === 'in_progress'): ?>
    <div class="alert alert-warning" style="margin-bottom: 25px;">
        <span class="mdi mdi-progress-clock"></span>
        Your release is being distributed to stores. This may take 3–7 days.
    </div>
    <?php elseif ($release['status'] === 'ready'): ?>
    <div class="alert alert-success" style="margin-bottom: 25px;">
        <span class="mdi mdi-check-circle"></span>
        Your release passed review and is being delivered to the selected stores.
    </div>
    <?php elseif ($release['status'] === 'live'): ?>
    <div class="alert alert-success" style="margin-bottom: 25px;">
        <span class="mdi mdi-broadcast"></span>
        Your release is live! Check the "Stores" tab below for per-platform status and store links.
    </div>
    <?php elseif ($release['status'] === 'takedown_requested'): ?>
    <div class="alert alert-warning" style="margin-bottom: 25px;">
        <span class="mdi mdi-timer-off"></span>
        A takedown has been requested for this release. It is pending review by our team.
    </div>
    <?php elseif ($release['status'] === 'taken_down'): ?>
    <div class="alert alert-error" style="margin-bottom: 25px;">
        <span class="mdi mdi-cancel"></span>
        This release has been taken down from stores.
    </div>
    <?php elseif ($release['status'] === 'rejected'): ?>
    <div class="alert alert-error" style="margin-bottom: 25px; flex-direction: column; align-items: flex-start; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="mdi mdi-close-circle"></span>
            <strong>Your release was not approved.</strong>
        </div>
        <?php if (!empty($release['admin_notes'])): ?>
        <p style="margin: 0; font-size: 14px; line-height: 1.6;">
            <?php echo nl2br(htmlspecialchars($release['admin_notes'])); ?>
        </p>
        <?php endif; ?>
        <a href="/index.php?q=release-create&id=<?php echo $release['id']; ?>&step=1"
           style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: linear-gradient(135deg, #ff5252, #00b7ff); color: #fff; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;">
            <i class="mdi mdi-refresh"></i> Resubmit Release
        </a>
    </div>
    <?php endif; ?>

    <!-- Step Tabs -->
    <div class="step-tabs" style="display: flex; gap: 10px; margin-bottom: 30px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1); overflow-x: auto;">
        <button type="button" class="tab-btn active" data-tab="step1" style="flex: 1; min-width: 140px; padding: 15px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); color: #000; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap;">
            <i class="mdi mdi-information-circle"></i> Step 1: Details
        </button>
        <button type="button" class="tab-btn" data-tab="step2" style="flex: 1; min-width: 140px; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap;">
            <i class="mdi mdi-store"></i> Step 2: Stores <?php if($live_count > 0): ?><span style="background: #00c853; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;"><?php echo $live_count; ?> Live</span><?php endif; ?>
        </button>
        <button type="button" class="tab-btn" data-tab="step3" style="flex: 1; min-width: 140px; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap;">
            <i class="mdi mdi-music"></i> Step 3: Tracks
        </button>
        <button type="button" class="tab-btn" data-tab="step4" style="flex: 1; min-width: 140px; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap;">
            <i class="mdi mdi-image"></i> Step 4: Artwork
        </button>
    </div>

    <!-- Progress Checklist Sidebar -->
    <div class="checklist-sidebar">
        <h3>Progress</h3>
        <div class="checklist-item completed">
            <i class="mdi mdi-check-circle"></i>
            <span>Release Details</span>
        </div>
        <div class="checklist-item completed">
            <i class="mdi mdi-check-circle"></i>
            <span>Stores & Platforms</span>
        </div>
        <div class="checklist-item completed">
            <i class="mdi mdi-check-circle"></i>
            <span>Tracks Information</span>
        </div>
        <div class="checklist-item completed">
            <i class="mdi mdi-check-circle"></i>
            <span>Artwork Uploaded</span>
        </div>
        
        <h3 style="margin-top: 25px;">History</h3>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="mdi mdi-plus"></i>
                </div>
                <div class="timeline-content">
                    <h4>Release Created</h4>
                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['created_at'])); ?></p>
                </div>
            </div>
            <?php if ($release['submitted_at']): ?>
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="mdi mdi-send"></i>
                </div>
                <div class="timeline-content">
                    <h4>Submitted for Review</h4>
                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['submitted_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($release['in_progress_at']): ?>
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="mdi mdi-progress-clock"></i>
                </div>
                <div class="timeline-content">
                    <h4>Distribution Started</h4>
                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['in_progress_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($release['ready_at']): ?>
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="mdi mdi-check"></i>
                </div>
                <div class="timeline-content">
                    <h4>Published</h4>
                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['ready_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($release['rejected_at']): ?>
            <div class="timeline-item">
                <div class="timeline-icon" style="background: rgba(255, 82, 82, 0.2); color: #ff5252;">
                    <i class="mdi mdi-close-circle"></i>
                </div>
                <div class="timeline-content">
                    <h4>Rejected - Needs Changes</h4>
                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['rejected_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Release Details Section -->
    <div id="step1" class="section-card tab-content">
        <div class="section-header">
            <h2><i class="mdi mdi-information-circle"></i> Release Details</h2>
            <span class="status-indicator"><i class="mdi mdi-check"></i> Complete</span>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <label>Release Title</label>
                <span><?php echo htmlspecialchars($release['title']); ?></span>
            </div>
            <div class="info-item">
                <label>Title Version</label>
                <span><?php echo htmlspecialchars($release['title_version'] ?: 'None'); ?></span>
            </div>
            <div class="info-item">
                <label>Primary Artist</label>
                <span><?php echo htmlspecialchars($release['primary_artist']); ?></span>
            </div>
            <div class="info-item">
                <label>Language</label>
                <span><?php echo htmlspecialchars($release['language']); ?></span>
            </div>
            <div class="info-item">
                <label>Primary Genre</label>
                <span><?php echo htmlspecialchars($release['primary_genre'] ?: 'Not specified'); ?></span>
            </div>
            <div class="info-item">
                <label>Secondary Genre</label>
                <span><?php echo htmlspecialchars($release['secondary_genre'] ?: 'None'); ?></span>
            </div>
            <div class="info-item">
                <label>Release Date</label>
                <span><?php echo $release['release_date'] ? date('F j, Y', strtotime($release['release_date'])) : 'Not set'; ?></span>
            </div>
            <div class="info-item">
                <label>Release Type</label>
                <span><?php echo ucfirst($release['release_type']); ?></span>
            </div>
            <?php if ($release['upc_code']): ?>
            <div class="info-item">
                <label>UPC Code</label>
                <span><?php echo htmlspecialchars($release['upc_code']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($release['recording_location']): ?>
            <div class="info-item full-width">
                <label>Recording Location</label>
                <span><?php echo htmlspecialchars($release['recording_location']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stores Section -->
    <div id="step2" class="section-card tab-content" style="display: none;">
        <div class="section-header">
            <h2><i class="mdi mdi-store"></i> Selected Stores & Live Status</h2>
            <span class="status-indicator"><i class="mdi mdi-check"></i> <?php echo count($platforms); ?> Selected</span>
        </div>

        <?php
            $delivery_map = [
                'live'       => ['Live', '#00c853', 'mdi-check-circle'],
                'processing' => ['Processing', '#4facfe', 'mdi-progress-clock'],
                'failed'     => ['Failed', '#ff5252', 'mdi-alert-circle'],
                'taken_down' => ['Taken Down', '#9e9e9e', 'mdi-cancel'],
            ];
        ?>

        <?php if ($live_count > 0): ?>
        <div style="background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.3); border-radius: 12px; padding: 20px; margin-bottom: 25px;">
            <h4 style="color: #00c853; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="mdi mdi-check-circle"></i> Your release is now LIVE on <?php echo $live_count; ?> platform(s)!
            </h4>
            <p style="color: rgba(255,255,255,0.7); font-size: 14px;">
                Great news! Your release is live on the platforms marked below — click the store link to see it. We will continue updating as more platforms go live.
            </p>
        </div>
        <?php else: ?>
        <div style="background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.3); border-radius: 12px; padding: 20px; margin-bottom: 25px;">
            <h4 style="color: #ffc107; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="mdi mdi-clock-outline"></i> Distribution in Progress
            </h4>
            <p style="color: rgba(255,255,255,0.7); font-size: 14px;">
                Your release is being distributed to all selected platforms. This page will update as each platform goes live. This usually takes 24-48 hours after your release is ready.
            </p>
        </div>
        <?php endif; ?>

        <div class="platforms-list" style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($platforms as $platform):
                $dstatus = $platform['delivery_status'] ?? 'pending';
                $is_live = ($dstatus === 'live');
                $surl = trim($platform['store_url'] ?? '');
                $dinfo = $delivery_map[$dstatus] ?? ['Pending', 'rgba(255,255,255,0.6)', 'mdi-clock-outline'];
            ?>
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px 20px; background: <?php echo $is_live ? 'rgba(0,200,83,0.1)' : 'rgba(255,255,255,0.03)'; ?>; border: 1px solid <?php echo $is_live ? 'rgba(0,200,83,0.3)' : 'rgba(255,255,255,0.1)'; ?>; border-radius: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: <?php echo $is_live ? 'linear-gradient(135deg, #00c853, #00d4aa)' : 'rgba(255,255,255,0.1)'; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; color: <?php echo $is_live ? '#000' : 'rgba(255,255,255,0.5)'; ?>;">
                        <i class="mdi <?php echo $dinfo[2]; ?>"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 15px;"><?php echo htmlspecialchars($platform['platform_name_display'] ?? $platform['platform_name']); ?></div>
                        <div style="font-size: 12px; color: <?php echo $dinfo[1]; ?>; margin-top: 3px;">
                            <i class="mdi <?php echo $dinfo[2]; ?>"></i> <?php echo $dinfo[0]; ?>
                            <?php if ($is_live && $surl): ?>
                                — <a href="<?php echo htmlspecialchars($surl); ?>" target="_blank" rel="noopener" style="color:#00c853;"><i class="mdi mdi-open-in-new"></i> Open on store</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($is_live): ?>
                    <span style="background: #00c853; color: #000; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="mdi mdi-check-circle" style="margin-right: 4px;"></i>LIVE
                    </span>
                    <?php else: ?>
                    <span style="background: rgba(255,255,255,0.1); color: <?php echo $dinfo[1]; ?>; padding: 6px 14px; border-radius: 20px; font-size: 12px;">
                        <?php echo $dinfo[0]; ?>
                    </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tracks Section -->
    <div id="step3" class="section-card tab-content" style="display: none;">
        <div class="section-header">
            <h2><i class="mdi mdi-music"></i> Tracks</h2>
            <span class="status-indicator"><i class="mdi mdi-check"></i> Complete</span>
        </div>
        <?php foreach ($tracks as $track): ?>
        <div class="track-item">
            <span class="track-number"><?php echo $track['track_number']; ?></span>
            <span class="track-title"><?php echo htmlspecialchars($track['song_title']); ?></span>
            <div class="track-meta">
                <?php if ($track['version_info']): ?>
                    <?php echo htmlspecialchars($track['version_info']); ?> • 
                <?php endif; ?>
                <?php echo htmlspecialchars($track['language']); ?>
                <?php if ($track['is_instrumental']): ?> • Instrumental<?php endif; ?>
                <?php if ($track['has_explicit_lyrics']): ?> • Explicit<?php endif; ?>
                <?php if ($track['isrc_code']): ?> • ISRC: <?php echo htmlspecialchars($track['isrc_code']); ?><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Collaborators & Royalty Splits (doc §1) -->
    <div class="section-card" style="margin-top: 30px;">
        <div class="section-header">
            <h2><i class="mdi mdi-account-group"></i> Royalty Splits</h2>
        </div>
        <p style="color: rgba(255,255,255,0.6); font-size: 14px; line-height: 1.6; margin-bottom: 15px;">
            Share this track's royalties with collaborators — e.g. 50/50 with a co-producer. Each collaborator gets their % of earnings automatically in their payout balance.
        </p>
        <?php if (isset($_GET['split_error'])): ?>
            <div class="alert alert-error" style="margin-bottom:15px;"><?php echo htmlspecialchars($_GET['split_error']); ?></div>
        <?php elseif (isset($_GET['split'])): ?>
            <div class="alert alert-success" style="margin-bottom:15px;">Split <?php echo htmlspecialchars($_GET['split']); ?>.</div>
        <?php endif; ?>

        <?php if ($splits): ?>
        <div style="margin-bottom:18px;">
            <?php foreach ($splits as $s): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.06);flex-wrap:wrap;">
                <i class="mdi mdi-account-music" style="color:#00b7ff;"></i>
                <strong><?php echo htmlspecialchars($s['song_title']); ?></strong>
                <span style="color:rgba(255,255,255,0.55);font-size:13px;">→ <?php echo htmlspecialchars($s['email']); ?><?php echo $s['user_name'] ? ' (' . htmlspecialchars($s['user_name']) . ')' : ' <em style="color:#ffb74d">invite pending</em>'; ?></span>
                <span class="badge badge-live" style="margin-left:auto;"><?php echo rtrim(rtrim(number_format($s['pct'],2), '0'), '.'); ?>%</span>
                <form method="POST" onsubmit="return confirm('Remove this split?');" style="margin:0;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="split_id" value="<?php echo (int)$s['id']; ?>">
                    <button type="submit" name="remove_split" class="btn btn-sm" style="padding:4px 10px;font-size:12px;">Remove</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <?php echo csrfField(); ?>
            <div style="flex:1;min-width:160px;">
                <label style="font-size:12px;color:rgba(255,255,255,0.55);display:block;margin-bottom:4px;">Track</label>
                <select name="split_track_id" required style="width:100%;">
                    <?php foreach ($tracks as $track): ?>
                    <option value="<?php echo (int)$track['id']; ?>"><?php echo $track['track_number'] . '. ' . htmlspecialchars($track['song_title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;min-width:180px;">
                <label style="font-size:12px;color:rgba(255,255,255,0.55);display:block;margin-bottom:4px;">Collaborator email</label>
                <input type="email" name="split_email" required placeholder="artist@example.com" style="width:100%;">
            </div>
            <div style="width:100px;">
                <label style="font-size:12px;color:rgba(255,255,255,0.55);display:block;margin-bottom:4px;">Share %</label>
                <input type="number" name="split_pct" required min="1" max="100" step="0.01" placeholder="50" style="width:100%;">
            </div>
            <button type="submit" name="add_split" class="btn">Add Split</button>
        </form>
        <p style="color:rgba(255,255,255,0.4);font-size:12px;margin-top:10px;">
            <i class="mdi mdi-information-outline"></i> Splits apply to royalties recorded after they're added. The collaborator sees their share in their own payout balance once they have a HiTune Distribution account.
        </p>
    </div>

    <!-- Artwork Section -->
    <div id="step4" class="section-card tab-content" style="display: none;">
        <div class="section-header">
            <h2><i class="mdi mdi-image"></i> Cover Art</h2>
            <span class="status-indicator"><i class="mdi mdi-check"></i> Complete</span>
        </div>
        <div class="cover-preview">
            <?php if ($release['cover_art']): ?>
                <img src="<?php echo htmlspecialchars($release['cover_art_path']); ?>" alt="Cover Art">
            <?php else: ?>
                <i class="mdi mdi-music-note"></i>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Notes Section (if has notes and not rejected — rejected is shown in banner above) -->
    <?php if (!empty($release['admin_notes']) && $release['status'] !== 'rejected'): ?>
    <div class="section-card" style="border-color: rgba(0,212,170,0.4);">
        <div class="section-header">
            <h2><i class="mdi mdi-message-text" style="color: #00d4aa;"></i> Notes</h2>
        </div>
        <p style="color: rgba(255,255,255,0.8); line-height: 1.6;">
            <?php echo nl2br(htmlspecialchars($release['admin_notes'])); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Takedown request -->
    <?php if (in_array($release['status'], ['submitted', 'in_progress', 'ready', 'live'], true)): ?>
    <div class="section-card" style="border-color: rgba(255,82,82,0.25); margin-top: 30px;">
        <div class="section-header">
            <h2><i class="mdi mdi-delete-alert" style="color: #ff5252;"></i> Request Takedown</h2>
        </div>
        <p style="color: rgba(255,255,255,0.6); font-size: 14px; line-height: 1.6; margin-bottom: 15px;">
            Need this release removed from stores? Submit a takedown request — our team will process it across all platforms. This cannot be undone.
        </p>
        <form method="POST" onsubmit="return confirm('Request takedown of this release from all stores?');" style="display:flex;flex-direction:column;gap:12px;">
            <?php echo csrfField(); ?>
            <textarea name="takedown_reason" required rows="3" placeholder="Reason for takedown (required)" style="width:100%;padding:12px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.15);border-radius:10px;color:#fff;font-size:14px;resize:vertical;"></textarea>
            <button type="submit" name="request_takedown" style="align-self:flex-start;padding:10px 24px;background:rgba(255,82,82,0.15);border:1px solid rgba(255,82,82,0.5);color:#ff5252;border-radius:10px;font-weight:600;cursor:pointer;">
                <i class="mdi mdi-delete-alert"></i> Request Takedown
            </button>
        </form>
    </div>
    <?php elseif ($release['status'] === 'takedown_requested' && !empty($release['takedown_reason'])): ?>
    <div class="section-card" style="border-color: rgba(255,152,0,0.3); margin-top: 30px;">
        <div class="section-header">
            <h2><i class="mdi mdi-timer-off" style="color: #ff9800;"></i> Takedown Requested</h2>
        </div>
        <p style="color: rgba(255,255,255,0.6); font-size: 13px; margin-bottom: 8px;">
            Requested on <?php echo date('M j, Y', strtotime($release['takedown_requested_at'] ?? 'now')); ?>:
        </p>
        <p style="color: rgba(255,255,255,0.8); font-size: 14px; line-height: 1.6;">
            <?php echo nl2br(htmlspecialchars($release['takedown_reason'])); ?>
        </p>
    </div>
    <?php endif; ?>

    <a href="/index.php?q=releases" class="btn-back">
        <i class="mdi mdi-arrow-left"></i>
        Back to My Releases
    </a>
</div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                // Remove active from all tabs
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active');
                    b.style.background = 'rgba(255,255,255,0.1)';
                    b.style.color = '#fff';
                });

                // Hide all content
                document.querySelectorAll('.tab-content').forEach(c => {
                    c.style.display = 'none';
                });

                // Activate clicked tab
                this.classList.add('active');
                this.style.background = 'linear-gradient(135deg, #00b7ff, #8b5cf6)';
                this.style.color = '#000';

                // Show corresponding content
                document.getElementById(tabId).style.display = 'block';
            });
        });
    </script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
