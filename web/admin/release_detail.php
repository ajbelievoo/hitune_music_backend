<?php
/**
 * HiTune Music Distribution - Admin Release Detail View
 * Full detailed view of a release with all fields
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_helper.php';
require_once __DIR__ . '/../includes/code_assign.php';
require_once __DIR__ . '/../includes/ecosystem_sync.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$release_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($release_id === 0) {
    header('Location: submissions.php');
    exit;
}

// Get full release details with user info
$stmt = $conn->prepare("SELECT r.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
    FROM releases r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?");
$stmt->bind_param("i", $release_id);
$stmt->execute();
$result = $stmt->get_result();
$release = $result->fetch_assoc();

// DEBUG: Log loaded release data
error_log("DEBUG ADMIN: Loaded release $release_id, status=" . ($release['status'] ?? 'NULL') . ", cover_art=" . ($release['cover_art'] ?? 'NULL') . ", path=" . ($release['cover_art_path'] ?? 'NULL'));

if (!$release) {
    header('Location: submissions.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $allowed_statuses = ['draft', 'submitted', 'in_progress', 'ready', 'live', 'rejected', 'takedown_requested', 'taken_down'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        header("Location: release_detail.php?id=$release_id&error=invalid_status");
        exit;
    }
    
    $timestamp_fields = [];
    if ($new_status === 'submitted') $timestamp_fields[] = "submitted_at = NOW()";
    elseif ($new_status === 'in_progress') $timestamp_fields[] = "in_progress_at = NOW()";
    elseif ($new_status === 'ready') $timestamp_fields[] = "ready_at = NOW()";
    elseif ($new_status === 'live') $timestamp_fields[] = "live_at = NOW()";
    elseif ($new_status === 'rejected') $timestamp_fields[] = "rejected_at = NOW()";
    elseif ($new_status === 'taken_down') $timestamp_fields[] = "taken_down_at = NOW()";

    $sql = "UPDATE releases SET status = ?, admin_notes = ?";
    if (!empty($timestamp_fields)) $sql .= ", " . implode(", ", $timestamp_fields);
    $sql .= " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_status, $admin_notes, $release_id);
    $result = $stmt->execute();

    // Log activity
    $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, 0, 'Admin Status Update', ?, ?, 'admin')");
    $stmt->bind_param("iss", $release_id, $new_status, $admin_notes);
    $stmt->execute();

    // Auto-assign ISRC/UPC when a release is approved for delivery
    if ($result && in_array($new_status, ['ready', 'live'], true)) {
        assignReleaseCodes($release_id);
    }

    // Notify the artist by email
    if ($result && $new_status !== $release['status']) {
        sendReleaseStatusEmail($release['user_email'], $release['user_name'], $release['title'], $new_status, $admin_notes);
    }

    // Ecosystem sync — approval goes live on HiTune Music + IyolMe instantly;
    // full rejection / takedown pulls it back off (strategy doc §7)
    if ($result && in_array($new_status, ['ready', 'live'], true)) {
        $eco = ecosystem_publish_release($conn, $release_id);
        if (empty($eco['ok'])) error_log("ECO publish failed for release {$release_id}: " . json_encode($eco));
    } elseif ($result && in_array($new_status, ['rejected', 'taken_down'], true)) {
        $eco = ecosystem_takedown_release($conn, $release_id);
        if (empty($eco['ok'])) error_log("ECO takedown failed for release {$release_id}: " . json_encode($eco));
    }

    header("Location: release_detail.php?id=$release_id&updated=1");
    exit;
}

// Handle full release details update
if (isset($_POST['update_release_details'])) {
    $title = $_POST['title'] ?? '';
    $title_version = $_POST['title_version'] ?? '';
    $primary_artist = $_POST['primary_artist'] ?? '';
    $language = $_POST['language'] ?? '';
    $primary_genre = $_POST['primary_genre'] ?? '';
    $secondary_genre = $_POST['secondary_genre'] ?? '';
    $release_date = $_POST['release_date'] ?? '';
    $release_type = $_POST['release_type'] ?? 'single';
    $upc_code = $_POST['upc_code'] ?? '';
    $recording_location = $_POST['recording_location'] ?? '';
    $sell_worldwide = isset($_POST['sell_worldwide']) ? 1 : 0;
    $territory_restriction_type = $_POST['territory_restriction_type'] ?? 'exclude';
    $territory_restrictions = $_POST['territory_restrictions'] ?? '';
    
    $stmt = $conn->prepare("UPDATE releases SET title = ?, title_version = ?, primary_artist = ?, language = ?, primary_genre = ?, secondary_genre = ?, release_date = ?, release_type = ?, upc_code = ?, recording_location = ?, sell_worldwide = ?, territory_restriction_type = ?, territory_restrictions = ? WHERE id = ?");
    $stmt->bind_param("ssssssssssisis", $title, $title_version, $primary_artist, $language, $primary_genre, $secondary_genre, $release_date, $release_type, $upc_code, $recording_location, $sell_worldwide, $territory_restriction_type, $territory_restrictions, $release_id);
    $stmt->execute();
    
    // Log activity
    $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, 0, 'Admin Edit: Release Details', ?, ?, 'admin')");
    $status = $release['status'];
    $note = "Updated release details by admin";
    $stmt->bind_param("iss", $release_id, $status, $note);
    $stmt->execute();
    
    header("Location: release_detail.php?id=$release_id&details_updated=1");
    exit;
}

// Handle track update
if (isset($_POST['update_tracks'])) {
    if (isset($_POST['tracks']) && is_array($_POST['tracks'])) {
        foreach ($_POST['tracks'] as $track_id => $track_data) {
            $song_title = $track_data['song_title'] ?? '';
            $language = $track_data['language'] ?? '';
            $version_info = $track_data['version_info'] ?? '';
            $isrc_code = $track_data['isrc_code'] ?? '';
            $has_explicit_lyrics = isset($track_data['has_explicit_lyrics']) ? 1 : 0;
            $is_instrumental = isset($track_data['is_instrumental']) ? 1 : 0;
            $is_cover = isset($track_data['is_cover']) ? 1 : 0;
            $lyrics = $track_data['lyrics'] ?? '';
            
            $stmt = $conn->prepare("UPDATE release_tracks SET song_title = ?, language = ?, version_info = ?, isrc_code = ?, has_explicit_lyrics = ?, is_instrumental = ?, is_cover = ?, lyrics = ? WHERE id = ? AND release_id = ?");
            $stmt->bind_param("ssssiiiisi", $song_title, $language, $version_info, $isrc_code, $has_explicit_lyrics, $is_instrumental, $is_cover, $lyrics, $track_id, $release_id);
            $stmt->execute();
            
            // Update credits if provided
            if (isset($track_data['credits']) && is_array($track_data['credits'])) {
                foreach ($track_data['credits'] as $credit_id => $credit_data) {
                    $artist_name = $credit_data['artist_name'] ?? '';
                    $role = $credit_data['role'] ?? '';
                    
                    $stmt = $conn->prepare("UPDATE track_credits SET artist_name = ?, role = ? WHERE id = ? AND track_id = ?");
                    $stmt->bind_param("ssii", $artist_name, $role, $credit_id, $track_id);
                    $stmt->execute();
                }
            }
        }
    }
    
    // Log activity
    $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, 0, 'Admin Edit: Tracks', ?, ?, 'admin')");
    $status = $release['status'];
    $note = "Updated track details by admin";
    $stmt->bind_param("iss", $release_id, $status, $note);
    $stmt->execute();
    
    header("Location: release_detail.php?id=$release_id&tracks_updated=1");
    exit;
}

// Handle platform public status update
if (isset($_POST['update_platform_public'])) {
    if (isset($_POST['platforms']) && is_array($_POST['platforms'])) {
        foreach ($_POST['platforms'] as $platform_id => $is_public) {
            $public_status = $is_public ? 1 : 0;
            $stmt = $conn->prepare("UPDATE release_platforms SET is_public = ? WHERE id = ? AND release_id = ?");
            $stmt->bind_param("iii", $public_status, $platform_id, $release_id);
            $stmt->execute();
        }
    }
    
    // Log activity
    $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, 0, 'Admin Edit: Platform Public Status', ?, ?, 'admin')");
    $status = $release['status'];
    $note = "Updated platform public status by admin";
    $stmt->bind_param("iss", $release_id, $status, $note);
    $stmt->execute();
    
    header("Location: release_detail.php?id=$release_id&platforms_updated=1");
    exit;
}

// Handle per-platform delivery status + store URL update
if (isset($_POST['update_platform_delivery'])) {
    if (isset($_POST['delivery_status']) && is_array($_POST['delivery_status'])) {
        $allowed = ['pending', 'processing', 'live', 'failed', 'taken_down'];
        foreach ($_POST['delivery_status'] as $platform_id => $status) {
            $platform_id = intval($platform_id);
            if (!in_array($status, $allowed, true)) $status = 'pending';
            $store_url = trim($_POST['store_url'][$platform_id] ?? '');
            if ($status === 'live') {
                $stmt = $conn->prepare("UPDATE release_platforms SET delivery_status = ?, store_url = ?, delivered_at = COALESCE(delivered_at, NOW()) WHERE id = ? AND release_id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE release_platforms SET delivery_status = ?, store_url = ?, delivered_at = NULL WHERE id = ? AND release_id = ?");
            }
            $stmt->bind_param("ssii", $status, $store_url, $platform_id, $release_id);
            $stmt->execute();
        }
        $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, new_status, notes, created_by) VALUES (?, 0, 'Admin Edit: Platform Delivery', ?, ?, 'admin')");
        $status = $release['status'];
        $note = "Updated per-platform delivery status / store links";
        $stmt->bind_param("iss", $release_id, $status, $note);
        $stmt->execute();
    }

    // If admin marked HiTune Music / IyolMe live manually, push the release
    // through the ecosystem bridge (idempotent on the music side)
    $eco_touched = $conn->query("SELECT COUNT(*) c FROM release_platforms
        WHERE release_id = {$release_id} AND platform_name IN ('HiTune Music','IyolMe') AND delivery_status = 'live'");
    if ($eco_touched && (int)$eco_touched->fetch_assoc()['c'] > 0) {
        $eco = ecosystem_publish_release($conn, $release_id);
        if (empty($eco['ok'])) error_log("ECO publish (manual) failed for release {$release_id}: " . json_encode($eco));
    }

    header("Location: release_detail.php?id=$release_id&delivery_updated=1");
    exit;
}

// Check edit mode
$edit_mode = isset($_GET['edit']) ? $_GET['edit'] : '';

// Get tracks with credits
$tracks = [];
$result = $conn->query("SELECT * FROM release_tracks WHERE release_id = $release_id ORDER BY track_number");
$track_count = $result->num_rows;
error_log("DEBUG ADMIN: Found $track_count tracks for release $release_id");
while ($row = $result->fetch_assoc()) {
    error_log("DEBUG ADMIN: Track id={$row['id']}, title={$row['song_title']}, audio_file=" . ($row['audio_file'] ?? 'NULL') . ", path=" . ($row['audio_file_path'] ?? 'NULL'));
    // Get credits for each track
    $credits = [];
    $credit_result = $conn->query("SELECT * FROM track_credits WHERE track_id = {$row['id']}");
    while ($credit = $credit_result->fetch_assoc()) {
        $credits[] = $credit;
    }
    $row['credits'] = $credits;
    $tracks[] = $row;
}

// Get selected platforms
$platforms = [];
$result = $conn->query("SELECT * FROM release_platforms WHERE release_id = $release_id AND is_selected = 1");
while ($row = $result->fetch_assoc()) {
    $platforms[] = $row;
}

// Get activity log
$activities = [];
$result = $conn->query("SELECT * FROM activity_log WHERE release_id = $release_id ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

$status_config = [
    'draft' => ['label' => 'Draft', 'color' => '#fff', 'bg' => 'rgba(255,255,255,0.1)'],
    'submitted' => ['label' => 'Submitted', 'color' => '#ffc107', 'bg' => 'rgba(255,193,7,0.2)'],
    'in_progress' => ['label' => 'In Progress', 'color' => '#4facfe', 'bg' => 'rgba(79,172,254,0.2)'],
    'ready' => ['label' => 'Published', 'color' => '#00c853', 'bg' => 'rgba(0,200,83,0.2)'],
    'rejected' => ['label' => 'Rejected', 'color' => '#ff5252', 'bg' => 'rgba(255,82,82,0.2)']
];

$current_status = $status_config[$release['status']] ?? $status_config['draft'];

// Platform logos mapping
$platform_logos = [
    'spotify' => ['icon' => 'spotify', 'color' => '#1DB954'],
    'apple_music' => ['icon' => 'apple', 'color' => '#FA243C'],
    'youtube_music' => ['icon' => 'youtube', 'color' => '#FF0000'],
    'amazon_music' => ['icon' => 'amazon', 'color' => '#00C6FF'],
    'tidal' => ['icon' => 'music', 'color' => '#000'],
    'deezer' => ['icon' => 'music-box', 'color' => '#00b7ff'],
    'jiosaavn' => ['icon' => 'music', 'color' => '#2BC47C'],
    'wynk' => ['icon' => 'music-note', 'color' => '#FF6B00'],
    'gaana' => ['icon' => 'music-circle', 'color' => '#E91E63'],
    'hungama' => ['icon' => 'play-circle', 'color' => '#FF4081'],
    'soundcloud' => ['icon' => 'soundcloud', 'color' => '#FF5500'],
    'pandora' => ['icon' => 'radio', 'color' => '#3668FF'],
    'itunes' => ['icon' => 'itunes', 'color' => '#FBBC05'],
    'facebook' => ['icon' => 'facebook', 'color' => '#1877F2'],
    'instagram' => ['icon' => 'instagram', 'color' => '#E1306C'],
    'tiktok' => ['icon' => 'music-note', 'color' => '#000'],
    'twitter' => ['icon' => 'twitter', 'color' => '#1DA1F2'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release #<?php echo $release_id; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
        }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .sidebar-header .logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .nav-menu { padding: 20px 0; }
        .nav-item {
            display: flex; align-items: center; gap: 15px;
            padding: 15px 30px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(0, 183, 255, 0.1);
            color: #00b7ff;
            border-left-color: #00b7ff;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 { 
            font-size: 24px; 
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-back { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-back:hover { background: rgba(255,255,255,0.15); }
        .btn-edit { background: linear-gradient(135deg, #00d4aa, #00c853); color: #000; }
        
        /* Status Banner */
        .status-banner {
            background: <?php echo $current_status['bg']; ?>;
            border: 1px solid <?php echo $current_status['color']; ?>;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .status-info h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .status-info p {
            font-size: 14px;
            opacity: 0.8;
        }
        .status-badge-large {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 700;
            background: <?php echo $current_status['bg']; ?>;
            color: <?php echo $current_status['color']; ?>;
            border: 2px solid <?php echo $current_status['color']; ?>;
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        @media (max-width: 1200px) {
            .content-grid { grid-template-columns: 1fr; }
        }
        
        /* Section Cards */
        .section-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i {
            color: #00d4aa;
        }
        .edit-link {
            color: #00d4aa;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Info Grid */
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
        .info-label {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 15px;
            font-weight: 500;
        }
        .info-value.empty {
            color: rgba(255,255,255,0.3);
            font-style: italic;
        }
        
        /* Cover Art */
        .cover-container {
            width: 100%;
            max-width: 300px;
            aspect-ratio: 1;
            border-radius: 15px;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cover-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cover-placeholder {
            font-size: 48px;
            color: rgba(255,255,255,0.3);
        }
        
        /* Tracks */
        .track-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .track-item {
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .track-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .track-number {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #00d4aa, #00c853);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #000;
        }
        .track-title {
            flex: 1;
        }
        .track-title h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        .track-title p {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
        }
        .track-badges {
            display: flex;
            gap: 8px;
        }
        .track-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-explicit { background: rgba(255,82,82,0.2); color: #ff5252; }
        .badge-instrumental { background: rgba(0,212,170,0.2); color: #00d4aa; }
        .badge-cover { background: rgba(255,193,7,0.2); color: #ffc107; }
        
        /* Credits Table */
        .credits-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }
        .credits-table th {
            text-align: left;
            padding: 10px;
            color: rgba(255,255,255,0.5);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .credits-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        /* Platforms Grid */
        .platforms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        .platform-item {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        .platform-item:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(0,212,170,0.3);
        }
        .platform-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
        }
        .platform-name {
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Sidebar Card */
        .sidebar-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .sidebar-card h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Status Form */
        .status-form select {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .status-form textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            min-height: 100px;
            margin-bottom: 15px;
            resize: vertical;
        }
        .btn-update {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00d4aa, #00c853);
            border: none;
            border-radius: 10px;
            color: #000;
            font-weight: 700;
            cursor: pointer;
            font-size: 15px;
        }
        
        /* Edit Mode Styles */
        .btn-edit { background: linear-gradient(135deg, #4facfe, #00f2fe); color: #000; }
        .btn-edit:hover { opacity: 0.9; }
        .btn-cancel { background: rgba(255,82,82,0.2); color: #ff5252; }
        .btn-cancel:hover { background: rgba(255,82,82,0.3); }
        
        .edit-form input, .edit-form select, .edit-form textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
        }
        .edit-form input:focus, .edit-form select:focus, .edit-form textarea:focus {
            outline: none;
            border-color: #00d4aa;
            background: rgba(255,255,255,0.15);
        }
        .edit-form select option {
            background: #1a1a2e;
            color: #fff;
        }
        .edit-form textarea {
            resize: vertical;
            min-height: 60px;
        }
        .edit-form .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .edit-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .edit-form .form-group.full-width {
            grid-column: 1 / -1;
        }
        .edit-form label {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .edit-form .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .edit-form .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        .edit-form .checkbox-group label {
            text-transform: none;
            font-size: 14px;
            color: #fff;
        }
        .edit-form .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .edit-form .form-actions button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .edit-form .form-actions .btn-save {
            background: linear-gradient(135deg, #00d4aa, #00c853);
            color: #000;
        }
        .edit-form .form-actions .btn-cancel-form {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        /* Track Edit Styles */
        .track-edit-form {
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .track-edit-form h4 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .track-edit-form .track-checkboxes {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }
        .credit-edit-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        /* User Info */
        .user-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .user-info-item:last-child {
            border-bottom: none;
        }
        .user-info-item i {
            font-size: 20px;
            color: #00d4aa;
        }
        .user-info-item div {
            flex: 1;
        }
        .user-info-item .label {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        .user-info-item .value {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Timeline */
        .timeline {
            padding-left: 10px;
        }
        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-left: 2px solid rgba(255,255,255,0.1);
            margin-left: 15px;
            padding-left: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 20px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #00d4aa;
            border: 2px solid #0a0a0a;
        }
        .timeline-content h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .timeline-content p {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .alert-success {
            background: rgba(0,200,83,0.2);
            border: 1px solid #00c853;
            color: #00c853;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo"><span class="mdi mdi-shield-account"></span></div>
                <h2>Admin Panel</h2>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
                <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item active"><span class="mdi mdi-music"></span>Releases</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="analytics.php" class="nav-item"><span class="mdi mdi-chart-line"></span>Analytics</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>
                        <span class="mdi mdi-album"></span>
                        Release #<?php echo $release_id; ?>: <?php echo htmlspecialchars($release['title']); ?>
                    </h1>
                    <p style="color: rgba(255,255,255,0.5); margin-top: 5px;">
                        by <?php echo htmlspecialchars($release['primary_artist']); ?> • 
                        <?php echo ucfirst($release['release_type']); ?> • 
                        Submitted <?php echo date('M j, Y', strtotime($release['submitted_at'] ?: $release['created_at'])); ?>
                    </p>
                </div>
                <div class="header-actions">
                    <a href="submissions.php" class="btn btn-back">
                        <span class="mdi mdi-arrow-left"></span> Back to List
                    </a>
                    <a href="analytics.php?release_id=<?php echo $release_id; ?>" class="btn" style="background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;">
                        <span class="mdi mdi-chart-line"></span> Analytics
                    </a>
                    <?php if ($edit_mode != 'details'): ?>
                    <a href="release_detail.php?id=<?php echo $release_id; ?>&edit=details" class="btn btn-edit">
                        <span class="mdi mdi-pencil"></span> Edit All Fields
                    </a>
                    <?php else: ?>
                    <a href="release_detail.php?id=<?php echo $release_id; ?>" class="btn btn-cancel">
                        <span class="mdi mdi-close"></span> Cancel Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Status updated successfully!
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['details_updated'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Release details updated successfully!
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['tracks_updated'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Tracks updated successfully!
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['platforms_updated'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Platform public status updated successfully!
            </div>
            <?php endif; ?>

            <!-- Status Banner -->
            <div class="status-banner">
                <div class="status-info">
                    <h2>Current Status</h2>
                    <p>This release is currently <strong><?php echo $current_status['label']; ?></strong></p>
                </div>
                <div class="status-badge-large">
                    <span class="mdi mdi-<?php 
                        echo $release['status'] === 'draft' ? 'pencil' : 
                            ($release['status'] === 'submitted' ? 'send' : 
                            ($release['status'] === 'in_progress' ? 'progress-clock' : 
                            ($release['status'] === 'ready' ? 'check-circle' : 'close-circle'))); 
                    ?>"></span>
                    <?php echo $current_status['label']; ?>
                </div>
            </div>

            <!-- Step Tabs -->
            <div class="step-tabs" style="display: flex; gap: 10px; margin-bottom: 30px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);">
                <button type="button" class="tab-btn active" data-tab="step1" style="flex: 1; padding: 15px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); color: #000; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="mdi mdi-information-circle"></span> Step 1: Details
                </button>
                <button type="button" class="tab-btn" data-tab="step2" style="flex: 1; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="mdi mdi-store"></span> Step 2: Platforms
                </button>
                <button type="button" class="tab-btn" data-tab="step3" style="flex: 1; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="mdi mdi-music"></span> Step 3: Tracks
                </button>
                <button type="button" class="tab-btn" data-tab="step4" style="flex: 1; padding: 15px; background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="mdi mdi-image"></span> Step 4: Artwork
                </button>
            </div>

            <div class="content-grid">
                <!-- Left Column - Main Content -->
                <div class="main-column">
                    <!-- Release Details -->
                    <div id="step1" class="section-card tab-content active">
                        <div class="section-header">
                            <h3 class="section-title">
                                <span class="mdi mdi-information-circle"></span>
                                Release Details (Step 1)
                            </h3>
                            <span style="color: #00c853;"><span class="mdi mdi-check-circle"></span> Complete</span>
                        </div>
                        <?php if ($edit_mode === 'details'): ?>
                        <!-- Edit Mode -->
                        <form method="POST" class="edit-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Release Title *</label>
                                    <input type="text" name="title" value="<?php echo htmlspecialchars($release['title']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Title Version</label>
                                    <input type="text" name="title_version" value="<?php echo htmlspecialchars($release['title_version'] ?? ''); ?>" placeholder="e.g., Live, Remix, Radio Edit">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Primary Artist *</label>
                                    <input type="text" name="primary_artist" value="<?php echo htmlspecialchars($release['primary_artist']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Language *</label>
                                    <select name="language" required>
                                        <option value="">Select Language</option>
                                        <?php 
                                        $languages = ['Hindi', 'English', 'Punjabi', 'Tamil', 'Telugu', 'Bengali', 'Marathi', 'Gujarati', 'Malayalam', 'Kannada', 'Urdu', 'Other'];
                                        foreach ($languages as $lang): 
                                        ?>
                                        <option value="<?php echo $lang; ?>" <?php echo ($release['language'] == $lang) ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Primary Genre *</label>
                                    <select name="primary_genre" required>
                                        <option value="">Select Genre</option>
                                        <?php 
                                        $genres = ['Pop', 'Rock', 'Hip-Hop/Rap', 'R&B/Soul', 'Electronic', 'Classical', 'Jazz', 'Blues', 'Country', 'Folk', 'Latin', 'World', 'Devotional', ' Bollywood', 'Regional', 'Alternative', 'Metal', 'Punk', 'Funk', 'Disco', 'House', 'Techno', 'Trance', 'Dubstep', 'Drum & Bass', 'Ambient', 'New Age', 'Gospel', 'Christian', 'Islamic', 'Hindu', 'Sikh', 'Buddhist', 'Other'];
                                        foreach ($genres as $genre): 
                                        ?>
                                        <option value="<?php echo $genre; ?>" <?php echo ($release['primary_genre'] == $genre) ? 'selected' : ''; ?>><?php echo $genre; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Secondary Genre</label>
                                    <select name="secondary_genre">
                                        <option value="">None</option>
                                        <?php foreach ($genres as $genre): ?>
                                        <option value="<?php echo $genre; ?>" <?php echo ($release['secondary_genre'] == $genre) ? 'selected' : ''; ?>><?php echo $genre; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Release Date *</label>
                                    <input type="date" name="release_date" value="<?php echo $release['release_date']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Release Type *</label>
                                    <select name="release_type" required>
                                        <option value="single" <?php echo ($release['release_type'] == 'single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="ep" <?php echo ($release['release_type'] == 'ep') ? 'selected' : ''; ?>>EP</option>
                                        <option value="album" <?php echo ($release['release_type'] == 'album') ? 'selected' : ''; ?>>Album</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>UPC Code</label>
                                    <input type="text" name="upc_code" value="<?php echo htmlspecialchars($release['upc_code'] ?? ''); ?>" placeholder="12-digit code">
                                </div>
                                <div class="form-group">
                                    <label>Recording Location</label>
                                    <input type="text" name="recording_location" value="<?php echo htmlspecialchars($release['recording_location'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="sell_worldwide" name="sell_worldwide" value="1" <?php echo $release['sell_worldwide'] ? 'checked' : ''; ?>>
                                        <label for="sell_worldwide">Sell Worldwide (All territories)</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row" id="territory_restrictions_row" style="<?php echo $release['sell_worldwide'] ? 'display:none;' : ''; ?>">
                                <div class="form-group">
                                    <label>Territory Restriction Type</label>
                                    <select name="territory_restriction_type">
                                        <option value="exclude" <?php echo ($release['territory_restriction_type'] == 'exclude') ? 'selected' : ''; ?>>Exclude (Don't sell in)</option>
                                        <option value="include" <?php echo ($release['territory_restriction_type'] == 'include') ? 'selected' : ''; ?>>Include (Only sell in)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Territories (comma-separated)</label>
                                    <input type="text" name="territory_restrictions" value="<?php echo htmlspecialchars($release['territory_restrictions'] ?? ''); ?>" placeholder="e.g., US, UK, CA">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_release_details" class="btn-save">
                                    <span class="mdi mdi-content-save"></span> Save Changes
                                </button>
                                <a href="release_detail.php?id=<?php echo $release_id; ?>" class="btn-cancel-form" style="text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;">
                                    <span class="mdi mdi-close"></span> Cancel
                                </a>
                            </div>
                        </form>
                        <script>
                            document.getElementById('sell_worldwide').addEventListener('change', function() {
                                document.getElementById('territory_restrictions_row').style.display = this.checked ? 'none' : 'grid';
                            });
                        </script>
                        <?php else: ?>
                        <!-- View Mode -->
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Release Title</span>
                                <span class="info-value"><?php echo htmlspecialchars($release['title']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Title Version</span>
                                <span class="info-value"><?php echo $release['title_version'] ? htmlspecialchars($release['title_version']) : '<span class="empty">None</span>'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Primary Artist</span>
                                <span class="info-value"><?php echo htmlspecialchars($release['primary_artist']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Language</span>
                                <span class="info-value"><?php echo htmlspecialchars($release['language']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Primary Genre</span>
                                <span class="info-value"><?php echo $release['primary_genre'] ? htmlspecialchars($release['primary_genre']) : '<span class="empty">Not set</span>'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Secondary Genre</span>
                                <span class="info-value"><?php echo $release['secondary_genre'] ? htmlspecialchars($release['secondary_genre']) : '<span class="empty">None</span>'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Release Date</span>
                                <span class="info-value"><?php echo $release['release_date'] ? date('F j, Y', strtotime($release['release_date'])) : '<span class="empty">Not set</span>'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Release Type</span>
                                <span class="info-value"><?php echo ucfirst($release['release_type']); ?></span>
                            </div>
                            <?php if ($release['upc_code']): ?>
                            <div class="info-item">
                                <span class="info-label">UPC Code</span>
                                <span class="info-value"><?php echo htmlspecialchars($release['upc_code']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($release['recording_location']): ?>
                            <div class="info-item full-width">
                                <span class="info-label">Recording Location</span>
                                <span class="info-value"><?php echo htmlspecialchars($release['recording_location']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-item full-width">
                                <span class="info-label">Territory Restrictions</span>
                                <span class="info-value">
                                    <?php if ($release['sell_worldwide']): ?>
                                        <span class="mdi mdi-earth"></span> Worldwide (All territories)
                                    <?php else: ?>
                                        <span class="mdi mdi-map-marker-off"></span> 
                                        <?php echo $release['territory_restriction_type'] === 'include' ? 'Only in: ' : 'Except: '; ?>
                                        <?php echo htmlspecialchars($release['territory_restrictions']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Selected Platforms -->
                    <div id="step2" class="section-card tab-content" style="display: none;">
                        <div class="section-header">
                            <h3 class="section-title">
                                <span class="mdi mdi-store"></span>
                                Selected Platforms (Step 2)
                            </h3>
                            <span style="color: #00c853;"><span class="mdi mdi-check-circle"></span> <?php echo count($platforms); ?> Selected</span>
                        </div>
                        <?php if (count($platforms) > 0): ?>
                        <?php
                            $delivery_labels = [
                                'pending'    => ['Pending', 'rgba(255,255,255,0.4)'],
                                'processing' => ['Processing', '#4facfe'],
                                'live'       => ['Live', '#00c853'],
                                'failed'     => ['Failed', '#ff5252'],
                                'taken_down' => ['Taken Down', '#9e9e9e'],
                            ];
                        ?>
                        <form method="POST" id="platform_public_form">
                            <div class="platforms-list">
                                <?php foreach ($platforms as $platform):
                                    $logo = $platform_logos[$platform['platform_name']] ?? ['icon' => 'music', 'color' => '#666'];
                                    $is_public = $platform['is_public'] ?? 0;
                                    $dstatus = $platform['delivery_status'] ?? 'pending';
                                    $surl = $platform['store_url'] ?? '';
                                ?>
                                <div class="platform-list-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                                    <input type="checkbox" id="public_<?php echo $platform['id']; ?>" name="platforms[<?php echo $platform['id']; ?>]" value="1" <?php echo $is_public ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; flex-shrink: 0;" title="Visible to public">
                                    <div class="platform-icon" style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: <?php echo $logo['color']; ?>20; color: <?php echo $logo['color']; ?>; flex-shrink: 0;">
                                        <span class="mdi mdi-<?php echo $logo['icon']; ?>"></span>
                                    </div>
                                    <div class="platform-name" style="min-width: 120px; font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($platform['platform_name_display'] ?? $platform['platform_name']); ?></div>
                                    <select name="delivery_status[<?php echo $platform['id']; ?>]" style="background:#1a1a2e;color:#fff;border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:7px 10px;font-size:12px;">
                                        <?php foreach ($delivery_labels as $k => $v): ?>
                                        <option value="<?php echo $k; ?>" <?php echo $dstatus === $k ? 'selected' : ''; ?>><?php echo $v[0]; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="url" name="store_url[<?php echo $platform['id']; ?>]" value="<?php echo htmlspecialchars($surl); ?>" placeholder="Store link (e.g. open.spotify.com/...)" style="flex:1;min-width:200px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:7px 12px;color:#fff;font-size:12px;">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top: 25px; text-align: center; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                                <button type="submit" name="update_platform_public" class="btn-update" style="max-width: 280px;">
                                    <span class="mdi mdi-content-save"></span> Save Public Status
                                </button>
                                <button type="submit" name="update_platform_delivery" class="btn-update" style="max-width: 280px; background: linear-gradient(135deg,#00c853,#00d4aa);">
                                    <span class="mdi mdi-truck-delivery"></span> Save Delivery Status
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <p style="color: rgba(255,255,255,0.5); text-align: center; padding: 20px;">No platforms selected</p>
                        <?php endif; ?>
                    </div>

                    <!-- Tracks -->
                    <div id="step3" class="section-card tab-content" style="display: none;">
                        <div class="section-header">
                            <h3 class="section-title">
                                <span class="mdi mdi-music"></span>
                                Tracks (Step 3)
                            </h3>
                            <span style="color: #00c853;"><span class="mdi mdi-check-circle"></span> <?php echo count($tracks); ?> Track(s)</span>
                        </div>
                        <?php if ($edit_mode === 'details'): ?>
                        <!-- Edit Mode -->
                        <form method="POST" class="edit-form">
                            <?php if (count($tracks) > 0): ?>
                            <?php foreach ($tracks as $track): ?>
                            <div class="track-edit-form">
                                <h4><span class="mdi mdi-music-note"></span> Track <?php echo $track['track_number']; ?>: <?php echo htmlspecialchars($track['song_title']); ?></h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Song Title *</label>
                                        <input type="text" name="tracks[<?php echo $track['id']; ?>][song_title]" value="<?php echo htmlspecialchars($track['song_title']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Language</label>
                                        <select name="tracks[<?php echo $track['id']; ?>][language]">
                                            <option value="">Select Language</option>
                                            <?php 
                                            $track_langs = ['Hindi', 'English', 'Punjabi', 'Tamil', 'Telugu', 'Bengali', 'Marathi', 'Gujarati', 'Malayalam', 'Kannada', 'Urdu', 'Other'];
                                            foreach ($track_langs as $lang): 
                                            ?>
                                            <option value="<?php echo $lang; ?>" <?php echo ($track['language'] == $lang) ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Version Info</label>
                                        <input type="text" name="tracks[<?php echo $track['id']; ?>][version_info]" value="<?php echo htmlspecialchars($track['version_info'] ?? ''); ?>" placeholder="e.g., Live, Remix">
                                    </div>
                                    <div class="form-group">
                                        <label>ISRC Code</label>
                                        <input type="text" name="tracks[<?php echo $track['id']; ?>][isrc_code]" value="<?php echo htmlspecialchars($track['isrc_code'] ?? ''); ?>" placeholder="XX-XXX-XX-XXXXX">
                                    </div>
                                </div>
                                <div class="track-checkboxes">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="explicit_<?php echo $track['id']; ?>" name="tracks[<?php echo $track['id']; ?>][has_explicit_lyrics]" value="1" <?php echo $track['has_explicit_lyrics'] ? 'checked' : ''; ?>>
                                        <label for="explicit_<?php echo $track['id']; ?>">Explicit Lyrics</label>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="instrumental_<?php echo $track['id']; ?>" name="tracks[<?php echo $track['id']; ?>][is_instrumental]" value="1" <?php echo $track['is_instrumental'] ? 'checked' : ''; ?>>
                                        <label for="instrumental_<?php echo $track['id']; ?>">Instrumental</label>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="cover_<?php echo $track['id']; ?>" name="tracks[<?php echo $track['id']; ?>][is_cover]" value="1" <?php echo $track['is_cover'] ? 'checked' : ''; ?>>
                                        <label for="cover_<?php echo $track['id']; ?>">Cover Song</label>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label>Lyrics</label>
                                        <textarea name="tracks[<?php echo $track['id']; ?>][lyrics]" rows="4" placeholder="Song lyrics..."><?php echo htmlspecialchars($track['lyrics'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <?php if (count($track['credits']) > 0): ?>
                                <div style="margin-top: 15px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                    <p style="font-size: 12px; color: rgba(255,255,255,0.7); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Credits</p>
                                    <?php foreach ($track['credits'] as $credit): ?>
                                    <div class="credit-edit-row">
                                        <input type="text" name="tracks[<?php echo $track['id']; ?>][credits][<?php echo $credit['id']; ?>][artist_name]" value="<?php echo htmlspecialchars($credit['artist_name']); ?>" placeholder="Artist Name">
                                        <input type="text" name="tracks[<?php echo $track['id']; ?>][credits][<?php echo $credit['id']; ?>][role]" value="<?php echo htmlspecialchars($credit['role']); ?>" placeholder="Role">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <div class="form-actions">
                                <button type="submit" name="update_tracks" class="btn-save">
                                    <span class="mdi mdi-content-save"></span> Save Track Changes
                                </button>
                                <a href="release_detail.php?id=<?php echo $release_id; ?>" class="btn-cancel-form" style="text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;">
                                    <span class="mdi mdi-close"></span> Cancel
                                </a>
                            </div>
                            <?php else: ?>
                            <p style="color: rgba(255,255,255,0.5); text-align: center; padding: 20px;">No tracks to edit</p>
                            <?php endif; ?>
                        </form>
                        <?php else: ?>
                        <!-- View Mode -->
                        <?php if (count($tracks) > 0): ?>
                        <div class="track-list">
                            <?php foreach ($tracks as $track): ?>
                            <div class="track-item">
                                <div class="track-header">
                                    <div class="track-number"><?php echo $track['track_number']; ?></div>
                                    <div class="track-title">
                                        <h4><?php echo htmlspecialchars($track['song_title']); ?></h4>
                                        <p>
                                            <?php echo htmlspecialchars($track['language']); ?>
                                            <?php if ($track['version_info']) echo ' • ' . htmlspecialchars($track['version_info']); ?>
                                            <?php if ($track['isrc_code']) echo ' • ISRC: ' . htmlspecialchars($track['isrc_code']); ?>
                                        </p>
                                    </div>
                                    <div class="track-badges">
                                        <?php if ($track['has_explicit_lyrics']): ?>
                                        <span class="track-badge badge-explicit">EXPLICIT</span>
                                        <?php endif; ?>
                                        <?php if ($track['is_instrumental']): ?>
                                        <span class="track-badge badge-instrumental">INSTRUMENTAL</span>
                                        <?php endif; ?>
                                        <?php if ($track['is_cover']): ?>
                                        <span class="track-badge badge-cover">COVER</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Audio Player -->
                                <?php if (!empty($track['audio_file'])): 
                                    $audioPath = ltrim($track['audio_file_path'] ?? $track['audio_file'], '/');
                                ?>
                                <div class="audio-section" style="margin: 15px 0; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                        <span class="mdi mdi-music-box" style="color: #00d4aa; font-size: 20px;"></span>
                                        <span style="font-size: 13px; color: rgba(255,255,255,0.8);">Audio File: <?php echo htmlspecialchars(basename($track['audio_file'])); ?></span>
                                        <span style="font-size: 11px; color: rgba(255,255,255,0.5);">(<?php echo !empty($track['audio_file_size']) ? number_format($track['audio_file_size'] / 1024 / 1024, 2) . ' MB' : 'N/A'; ?>)</span>
                                    </div>
                                    <audio controls style="width: 100%; height: 40px; border-radius: 8px;">
                                        <source src="/<?php echo htmlspecialchars($audioPath); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                </div>
                                <?php else: ?>
                                <div class="audio-section" style="margin: 15px 0; padding: 15px; background: rgba(255,193,7,0.1); border-radius: 10px; border: 1px solid rgba(255,193,7,0.3);">
                                    <div style="display: flex; align-items: center; gap: 10px; color: #ffc107;">
                                        <span class="mdi mdi-alert" style="font-size: 20px;"></span>
                                        <span style="font-size: 13px;">No audio file uploaded for this track</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (count($track['credits']) > 0): ?>
                                <table class="credits-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Split</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($track['credits'] as $credit): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($credit['artist_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($credit['role'] ?? 'N/A'); ?></td>
                                            <td><?php echo $credit['split_percentage'] ?? 0; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                                
                                <?php if ($track['lyrics']): ?>
                                <div style="margin-top: 15px; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 10px;">
                                    <p style="font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 5px;">Lyrics Preview:</p>
                                    <p style="font-size: 13px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars(substr($track['lyrics'], 0, 200))) . (strlen($track['lyrics']) > 200 ? '...' : ''); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color: rgba(255,255,255,0.5); text-align: center; padding: 20px;">No tracks added</p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Artwork -->
                    <div id="step4" class="section-card tab-content" style="display: none;">
                        <div class="section-header">
                            <h3 class="section-title">
                                <span class="mdi mdi-image"></span>
                                Cover Art (Step 4)
                            </h3>
                            <span style="color: <?php echo $release['cover_art'] ? '#00c853' : '#ffc107'; ?>">
                                <span class="mdi mdi-<?php echo $release['cover_art'] ? 'check-circle' : 'alert'; ?>"></span>
                                <?php echo $release['cover_art'] ? 'Uploaded' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="cover-container">
                            <?php if ($release['cover_art'] && $release['cover_art_path']): 
                                $coverPath = ltrim($release['cover_art_path'], '/');
                            ?>
                            <img src="/<?php echo htmlspecialchars($coverPath); ?>" alt="Cover Art">
                            <?php else: ?>
                            <span class="cover-placeholder mdi mdi-image-off"></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($release['cover_art']): ?>
                        <p style="margin-top: 15px; font-size: 13px; color: rgba(255,255,255,0.5);">
                            <span class="mdi mdi-file-image"></span> 
                            <?php echo htmlspecialchars($release['cover_art']); ?> • 
                            <?php echo $release['cover_art_mime'] ?? 'image/jpeg'; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column - Sidebar -->
                <div class="sidebar-column">
                    <!-- Update Status -->
                    <div class="sidebar-card">
                        <h3><span class="mdi mdi-cog"></span> Update Status</h3>
                        <form method="POST" class="status-form">
                            <select name="status">
                                <option value="draft" <?php echo $release['status'] == 'draft' ? 'selected' : ''; ?>>📝 Draft</option>
                                <option value="submitted" <?php echo $release['status'] == 'submitted' ? 'selected' : ''; ?>>📤 Submitted</option>
                                <option value="in_progress" <?php echo $release['status'] == 'in_progress' ? 'selected' : ''; ?>>⏳ In Progress</option>
                                <option value="ready" <?php echo $release['status'] == 'ready' ? 'selected' : ''; ?>>✅ Ready / Published</option>
                                <option value="live" <?php echo $release['status'] == 'live' ? 'selected' : ''; ?>>🟢 Live on Stores</option>
                                <option value="takedown_requested" <?php echo $release['status'] == 'takedown_requested' ? 'selected' : ''; ?>>⏸ Takedown Requested</option>
                                <option value="taken_down" <?php echo $release['status'] == 'taken_down' ? 'selected' : ''; ?>>⛔ Taken Down</option>
                                <option value="rejected" <?php echo $release['status'] == 'rejected' ? 'selected' : ''; ?>>❌ Rejected</option>
                            </select>
                            <?php if ($release['status'] === 'takedown_requested' && !empty($release['takedown_reason'])): ?>
                            <div style="background:rgba(255,152,0,0.1);border:1px solid rgba(255,152,0,0.3);border-radius:8px;padding:12px;margin-bottom:12px;font-size:13px;color:#ff9800;">
                                <b>Takedown requested by artist</b> on <?php echo date('M j, Y', strtotime($release['takedown_requested_at'] ?? 'now')); ?>:
                                <div style="color:rgba(255,255,255,0.8);margin-top:6px;"><?php echo nl2br(htmlspecialchars($release['takedown_reason'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <textarea name="admin_notes" placeholder="Add notes for the user (visible in their dashboard)..."><?php echo htmlspecialchars($release['admin_notes'] ?? ''); ?></textarea>
                            <button type="submit" name="update_status" class="btn-update">
                                <span class="mdi mdi-content-save"></span> Update Status
                            </button>
                        </form>
                    </div>

                    <!-- User Info -->
                    <div class="sidebar-card">
                        <h3><span class="mdi mdi-account"></span> Artist Info</h3>
                        <div class="user-info-item">
                            <span class="mdi mdi-account-circle"></span>
                            <div>
                                <div class="label">Artist Name</div>
                                <div class="value"><?php echo htmlspecialchars($release['user_name']); ?></div>
                            </div>
                        </div>
                        <div class="user-info-item">
                            <span class="mdi mdi-email"></span>
                            <div>
                                <div class="label">Email</div>
                                <div class="value"><?php echo htmlspecialchars($release['user_email']); ?></div>
                            </div>
                        </div>
                        <?php if ($release['user_phone']): ?>
                        <div class="user-info-item">
                            <span class="mdi mdi-phone"></span>
                            <div>
                                <div class="label">Phone</div>
                                <div class="value"><?php echo htmlspecialchars($release['user_phone']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Timeline -->
                    <div class="sidebar-card">
                        <h3><span class="mdi mdi-clock-outline"></span> Timeline</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h4>Release Created</h4>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['created_at'])); ?></p>
                                </div>
                            </div>
                            <?php if ($release['submitted_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h4>Submitted for Review</h4>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['submitted_at'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($release['in_progress_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h4>Distribution Started</h4>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['in_progress_at'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($release['ready_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h4>Published</h4>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['ready_at'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($release['rejected_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h4 style="color: #ff5252;">Rejected</h4>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($release['rejected_at'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="sidebar-card">
                        <h3><span class="mdi mdi-lightning-bolt"></span> Quick Actions</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="/index.php?q=release-view&id=<?php echo $release_id; ?>" target="_blank" class="btn" style="background: rgba(0,212,170,0.2); color: #00d4aa; justify-content: center;">
                                <span class="mdi mdi-eye"></span> View as User
                            </a>
                            <?php if ($release['user_email']): ?>
                            <a href="mailto:<?php echo $release['user_email']; ?>?subject=Regarding your release: <?php echo urlencode($release['title']); ?>" class="btn" style="background: rgba(255,255,255,0.1); color: #fff; justify-content: center;">
                                <span class="mdi mdi-email-send"></span> Email Artist
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
</body>
</html>
