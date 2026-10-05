<?php
/**
 * HiTune Music Distribution - Multi-Step Release Creation
 * Step 3: Tracks Management
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fingerprint.php';
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    // Process each track
    $track_count = intval($_POST['track_count'] ?? 1);
    
    for ($i = 1; $i <= $track_count; $i++) {
        $track_id = intval($_POST["track_id_$i"] ?? 0);
        $song_title = $_POST["song_title_$i"] ?? '';
        
        if (empty($song_title)) continue;
        
        if ($track_id > 0) {
            // Update existing track (including track_number to maintain proper order)
            $stmt = $conn->prepare("UPDATE release_tracks SET 
                track_number = ?, song_title = ?, version_info = ?, isrc_code = ?, language = ?, lyrics = ?,
                has_explicit_lyrics = ?, is_instrumental = ?, tiktok_clip_start_min = ?,
                tiktok_clip_start_sec = ?, is_cover = ?, musical_composition_owner = ?, sound_recording_owner = ?
                WHERE id = ? AND release_id = ?");

            $has_explicit = isset($_POST["explicit_$i"]) ? 1 : 0;
            $is_instrumental = isset($_POST["instrumental_$i"]) ? 1 : 0;
            $is_cover = isset($_POST["is_cover_$i"]) ? 1 : 0;
            $musical_owner = isset($_POST["musical_owner_$i"]) ? 1 : 0;
            $sound_owner = isset($_POST["sound_owner_$i"]) ? 1 : 0;

            $stmt->bind_param("isssssiiiiiiiii",
                $i,
                $song_title,
                $_POST["version_info_$i"],
                $_POST["isrc_code_$i"],
                $_POST["language_$i"],
                $_POST["lyrics_$i"],
                $has_explicit,
                $is_instrumental,
                $_POST["tiktok_min_$i"],
                $_POST["tiktok_sec_$i"],
                $is_cover,
                $musical_owner,
                $sound_owner,
                $track_id,
                $release_id
            );
            $stmt->execute();
        } else {
            // Insert new track
            $stmt = $conn->prepare("INSERT INTO release_tracks 
                (release_id, track_number, song_title, version_info, isrc_code, language, lyrics,
                has_explicit_lyrics, is_instrumental, tiktok_clip_start_min, tiktok_clip_start_sec,
                is_cover, musical_composition_owner, sound_recording_owner) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $has_explicit = isset($_POST["explicit_$i"]) ? 1 : 0;
            $is_instrumental = isset($_POST["instrumental_$i"]) ? 1 : 0;
            $is_cover = isset($_POST["is_cover_$i"]) ? 1 : 0;
            $musical_owner = isset($_POST["musical_owner_$i"]) ? 1 : 0;
            $sound_owner = isset($_POST["sound_owner_$i"]) ? 1 : 0;
            
            $stmt->bind_param("iisssssiiiiiii",
                $release_id,
                $i,
                $song_title,
                $_POST["version_info_$i"],
                $_POST["isrc_code_$i"],
                $_POST["language_$i"],
                $_POST["lyrics_$i"],
                $has_explicit,
                $is_instrumental,
                $_POST["tiktok_min_$i"],
                $_POST["tiktok_sec_$i"],
                $is_cover,
                $musical_owner,
                $sound_owner
            );
            $stmt->execute();
            $track_id = $conn->insert_id;
        }
        
        // Process songwriters
        if (isset($_POST["songwriters_$i"])) {
            $conn->query("DELETE FROM track_credits WHERE track_id = $track_id AND credit_type = 'songwriter'");
            $songwriters = $_POST["songwriters_$i"];
            $stmt = $conn->prepare("INSERT INTO track_credits (track_id, release_id, artist_name, role, credit_type) VALUES (?, ?, ?, 'Songwriter', 'songwriter')");
            foreach ($songwriters as $writer) {
                if (!empty($writer)) {
                    $stmt->bind_param("iis", $track_id, $release_id, $writer);
                    $stmt->execute();
                }
            }
        }
        
        // Process performing artists
        if (isset($_POST["artists_$i"]) && isset($_POST["artist_roles_$i"])) {
            $conn->query("DELETE FROM track_credits WHERE track_id = $track_id AND credit_type = 'performing_artist'");
            $artists = $_POST["artists_$i"];
            $roles = $_POST["artist_roles_$i"];
            $stmt = $conn->prepare("INSERT INTO track_credits (track_id, release_id, artist_name, role, credit_type) VALUES (?, ?, ?, ?, 'performing_artist')");
            for ($j = 0; $j < count($artists); $j++) {
                if (!empty($artists[$j])) {
                    $role = !empty($roles[$j]) ? $roles[$j] : 'Main Artist';
                    $stmt->bind_param("iiss", $track_id, $release_id, $artists[$j], $role);
                    $stmt->execute();
                }
            }
        }
        
        // Process audio file upload
        if (isset($_FILES["audio_$i"]) && $_FILES["audio_$i"]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES["audio_$i"];
            $max_size = 200 * 1024 * 1024; // 200MB
            
            if ($file['size'] <= $max_size) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $allowed = ['wav', 'mp3', 'flac', 'm4a', 'ogg'];
                
                if (in_array(strtolower($ext), $allowed)) {
                    $upload_dir = __DIR__ . '/../uploads/audio/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $filename = 'track_' . $track_id . '_' . time() . '.' . $ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Update track with audio info
                        $audio_path = 'uploads/audio/' . $filename;
                        $duration = '00:00'; // TODO: Get actual duration
                        $stmt = $conn->prepare("UPDATE release_tracks SET audio_file = ?, audio_file_path = ?, audio_duration = ?, audio_uploaded = 1, fingerprint = ? WHERE id = ?");
                        $fp = web_audio_fingerprint($audio_path);
                        $stmt->bind_param("ssssi", $filename, $audio_path, $duration, $fp, $track_id);
                        $stmt->execute();
                        web_flag_duplicate_audio($conn, $track_id);
                    }
                }
            }
        }
    }
    
    // Delete tracks that were removed from the form
    // Collect all track IDs that were in the form submission
    $submitted_track_ids = [];
    for ($i = 1; $i <= $track_count; $i++) {
        $tid = intval($_POST["track_id_$i"] ?? 0);
        if ($tid > 0) {
            $submitted_track_ids[] = $tid;
        }
    }
    
    // Delete tracks not in the submission (with audio file cleanup)
    if (!empty($submitted_track_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($submitted_track_ids), '?'));
        $stmt = $conn->prepare("SELECT id, audio_file_path FROM release_tracks WHERE release_id = ? AND id NOT IN ($ids_placeholder)");
        $types = str_repeat('i', count($submitted_track_ids) + 1);
        $params = array_merge([$release_id], $submitted_track_ids);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Delete audio file if exists
            if (!empty($row['audio_file_path'])) {
                $full_path = '/www/wwwroot/web/' . ltrim($row['audio_file_path'], '/');
                if (file_exists($full_path)) {
                    @unlink($full_path);
                }
            }
            // Delete track credits
            $conn->query("DELETE FROM track_credits WHERE track_id = {$row['id']}");
            // Delete track
            $conn->query("DELETE FROM release_tracks WHERE id = {$row['id']}");
        }
    }
    
    // Update progress
    $stmt = $conn->prepare("UPDATE releases SET step_completed = GREATEST(step_completed, 3), progress_percent = GREATEST(progress_percent, 75) WHERE id = ?");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    
    if ($action === 'save_continue') {
        header("Location: /index.php?q=release-create&id=$release_id&step=4");
    } else {
        header("Location: /index.php?q=releases");
    }
    exit;
}

// Get existing tracks
$tracks = [];
$result = $conn->query("SELECT * FROM release_tracks WHERE release_id = $release_id ORDER BY track_number");
while ($row = $result->fetch_assoc()) {
    $track_id = $row['id'];
    
    // Get songwriters
    $writers_result = $conn->query("SELECT artist_name FROM track_credits WHERE track_id = $track_id AND credit_type = 'songwriter'");
    $row['songwriters'] = [];
    while ($w = $writers_result->fetch_assoc()) {
        $row['songwriters'][] = $w['artist_name'];
    }
    
    // Get performing artists
    $artists_result = $conn->query("SELECT artist_name, role FROM track_credits WHERE track_id = $track_id AND credit_type = 'performing_artist'");
    $row['artists'] = [];
    while ($a = $artists_result->fetch_assoc()) {
        $row['artists'][] = $a;
    }
    
    $tracks[] = $row;
}

// Determine track count
$track_count = max(1, count($tracks));
if ($release['release_type'] === 'single') {
    $track_count = 1;
}

$pageTitle = 'Create Release - Step 3: Tracks';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .release-container {
        max-width: 1000px;
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
    
    .track-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        margin-bottom: 30px;
        overflow: hidden;
    }
    .track-header {
        background: rgba(0,0,0,0.2);
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .track-header h3 {
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .track-number {
        width: 30px;
        height: 30px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: #000;
    }
    .track-body {
        padding: 30px;
    }
    
    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(255,255,255,0.9);
    }
    .form-section-title i {
        color: #00d4aa;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 500;
        color: rgba(255,255,255,0.8);
    }
    .form-group label .required {
        color: #00b7ff;
        margin-left: 3px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        transition: all 0.3s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #00d4aa;
    }
    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: rgba(255,255,255,0.3);
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
    }
    
    .section-divider {
        height: 1px;
        background: rgba(255,255,255,0.1);
        margin: 30px 0;
    }
    
    /* Artist Credits */
    .credit-row {
        display: grid;
        grid-template-columns: 2fr 1fr auto;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }
    .credit-row input,
    .credit-row select {
        padding: 10px 12px;
    }
    .btn-add {
        padding: 8px 15px;
        background: rgba(0, 212, 170, 0.2);
        border: 1px solid #00d4aa;
        color: #00d4aa;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 10px;
    }
    .btn-add:hover {
        background: rgba(0, 212, 170, 0.3);
    }
    .btn-remove {
        padding: 8px;
        background: rgba(255, 82, 82, 0.2);
        border: none;
        color: #ff5252;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }
    .btn-remove:hover {
        background: rgba(255, 82, 82, 0.3);
    }
    
    /* Checkbox styling */
    .checkbox-group {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .checkbox-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .checkbox-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #00d4aa;
    }
    
    /* Copyright section */
    .copyright-section {
        background: rgba(0,0,0,0.2);
        border-radius: 12px;
        padding: 20px;
    }
    .copyright-warning {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 12px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 15px;
        line-height: 1.5;
    }
    
    /* TikTok clip */
    .tiktok-input {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .tiktok-input input {
        width: 60px;
        text-align: center;
    }
    .tiktok-input span {
        color: rgba(255,255,255,0.5);
        font-size: 13px;
    }
    
    /* Form actions */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
        padding: 30px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 15px;
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
    
    /* Cover song section */
    .cover-section {
        display: none;
        margin-top: 15px;
    }
    .cover-section.visible {
        display: block;
    }
    
    .audio-upload-box {
        border: 2px dashed rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    .audio-upload-box:hover {
        border-color: #00d4aa;
        background: rgba(0, 212, 170, 0.05);
    }
    .audio-upload-box i {
        font-size: 40px;
        color: rgba(255,255,255,0.3);
        margin-bottom: 15px;
    }
    .audio-upload-box p {
        color: rgba(255,255,255,0.6);
        margin-bottom: 5px;
    }
    .audio-upload-box small {
        color: rgba(255,255,255,0.4);
        font-size: 12px;
    }
    .audio-requirements {
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    .audio-requirements h4 {
        font-size: 14px;
        margin-bottom: 12px;
        color: rgba(255,255,255,0.8);
    }
    .audio-requirements ul {
        list-style: none;
        font-size: 13px;
        color: rgba(255,255,255,0.6);
    }
    .audio-requirements li {
        padding: 4px 0;
        padding-left: 20px;
        position: relative;
    }
    .audio-requirements li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #00d4aa;
    }
    
    /* Audio Upload Section Styles */
    .audio-section {
        margin-bottom: 25px;
    }
    .audio-uploaded {
        background: rgba(0, 212, 170, 0.1);
        border: 2px solid rgba(0, 212, 170, 0.3);
        border-radius: 12px;
        padding: 20px;
    }
    .audio-info {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    .audio-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: rgba(255,255,255,0.5);
    }
    .audio-icon.uploaded {
        background: rgba(0, 212, 170, 0.2);
        color: #00d4aa;
    }
    .audio-details {
        flex: 1;
    }
    .audio-filename {
        font-size: 14px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 4px;
        word-break: break-all;
    }
    .audio-status {
        font-size: 12px;
        color: #00d4aa;
    }
    .audio-size {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
    }
    audio.audio-player {
        width: 100%;
        height: 40px;
        margin-bottom: 15px;
        border-radius: 20px;
    }
    .audio-actions {
        display: flex;
        gap: 10px;
    }
    .btn-change-audio, .btn-remove-audio {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-change-audio {
        background: rgba(0, 212, 170, 0.2);
        color: #00d4aa;
    }
    .btn-change-audio:hover {
        background: rgba(0, 212, 170, 0.3);
    }
    .btn-remove-audio {
        background: rgba(255, 82, 82, 0.2);
        color: #ff5252;
    }
    .btn-remove-audio:hover {
        background: rgba(255, 82, 82, 0.3);
    }
    .audio-selected {
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        margin-top: 10px;
    }
    .upload-progress {
        margin-top: 15px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
        padding: 15px;
        border: 1px solid rgba(0, 212, 170, 0.2);
    }
    .progress-bar {
        width: 100%;
        height: 12px;
        background: rgba(255,255,255,0.1);
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 12px;
        position: relative;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        border-radius: 6px;
        transition: width 0.2s ease;
        box-shadow: 0 0 10px rgba(0, 212, 170, 0.3);
    }
    .progress-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .progress-percent {
        font-size: 24px;
        font-weight: 700;
        color: #00d4aa;
    }
    .progress-stats {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: rgba(255,255,255,0.6);
    }
    .progress-stat {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .progress-stat i {
        color: #00d4aa;
        font-size: 14px;
    }
    .upload-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 15px 20px;
        background: linear-gradient(135deg, rgba(0, 212, 170, 0.15), rgba(0, 200, 83, 0.1));
        border: 2px solid rgba(0, 212, 170, 0.4);
        border-radius: 12px;
        margin-bottom: 15px;
        animation: pulse-upload 1.5s ease-in-out infinite;
    }
    @keyframes pulse-upload {
        0%, 100% { border-color: rgba(0, 212, 170, 0.4); box-shadow: 0 0 0 0 rgba(0, 212, 170, 0.3); }
        50% { border-color: rgba(0, 212, 170, 0.8); box-shadow: 0 0 20px rgba(0, 212, 170, 0.4); }
    }
    .upload-spinner {
        width: 28px;
        height: 28px;
        border: 3px solid rgba(0, 212, 170, 0.2);
        border-top-color: #00d4aa;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .upload-status-text {
        font-size: 16px;
        font-weight: 600;
        color: #00d4aa;
    }
    .upload-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }
    .upload-overlay.active {
        display: flex;
    }
    .upload-overlay-content {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        border: 2px solid rgba(0, 212, 170, 0.3);
        border-radius: 20px;
        padding: 40px 50px;
        text-align: center;
        min-width: 400px;
    }
    .upload-overlay-content .progress-bar {
        height: 16px;
        margin: 30px 0;
    }
    .upload-overlay-content .progress-percent {
        font-size: 48px;
        margin-bottom: 10px;
    }
    .upload-overlay-content .progress-stats {
        justify-content: center;
        font-size: 14px;
        gap: 25px;
    }
    .upload-filename {
        font-size: 16px;
        color: rgba(255,255,255,0.8);
        margin-bottom: 5px;
        word-break: break-all;
    }
    .upload-filesize {
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    .upload-error {
        margin-top: 10px;
        padding: 10px;
        background: rgba(255, 82, 82, 0.2);
        border: 1px solid rgba(255, 82, 82, 0.3);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #ff5252;
        font-size: 13px;
    }
    .upload-error i {
        font-size: 18px;
    }
</style>

<div class="release-container">
    <!-- Progress Header -->
    <div class="progress-header">
        <h1>Add Track Information</h1>
        <div class="progress-bar">
            <div class="progress-line" style="width: 62.5%;"></div>
            <div class="progress-step completed">
                <div class="step-number"><i class="mdi mdi-check"></i></div>
                <span class="step-label">Release Details</span>
            </div>
            <div class="progress-step completed">
                <div class="step-number"><i class="mdi mdi-check"></i></div>
                <span class="step-label">Stores</span>
            </div>
            <div class="progress-step active">
                <div class="step-number">3</div>
                <span class="step-label">Tracks</span>
            </div>
            <div class="progress-step">
                <div class="step-number">4</div>
                <span class="step-label">Artwork</span>
            </div>
        </div>
    </div>
    
    <!-- Audio Requirements -->
    <div class="audio-requirements">
        <h4><i class="mdi mdi-information" style="color: #00d4aa; margin-right: 8px;"></i>Audio File Requirements</h4>
        <ul>
            <li>Stereo WAV file</li>
            <li>16 bit with a 44.1 kHz sample rate (minimum)</li>
            <li>No profanity in the file name</li>
            <li>File should not exceed 200MB</li>
        </ul>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" id="formAction" value="save">
        <input type="hidden" name="track_count" id="track_count" value="<?php echo $track_count; ?>">
        <input type="hidden" id="release_id" value="<?php echo $release_id; ?>">
        
        <?php for ($i = 1; $i <= $track_count; $i++): 
            $track = $tracks[$i - 1] ?? null;
        ?>
        <div class="track-card" id="track_card_<?php echo $i; ?>">
            <div class="track-header">
                <h3>
                    <span class="track-number" id="track_num_badge_<?php echo $i; ?>"><?php echo $i; ?></span>
                    <span id="track_title_<?php echo $i; ?>">Track <?php echo $i; ?> Information</span>
                </h3>
                <?php if ($i > 1 || $release['release_type'] !== 'single'): ?>
                <button type="button" class="btn-remove" onclick="removeTrack(<?php echo $i; ?>)" title="Remove Track">
                    <i class="mdi mdi-delete"></i>
                </button>
                <?php endif; ?>
            </div>
            <div class="track-body">
                <input type="hidden" name="track_id_<?php echo $i; ?>" value="<?php echo $track ? $track['id'] : 0; ?>">
                
                <!-- Audio Upload Section -->
                <div class="audio-section" id="audio_section_<?php echo $i; ?>">
                    <?php if ($track && $track['audio_file']): ?>
                        <!-- Already Uploaded State -->
                        <div class="audio-uploaded">
                            <div class="audio-info">
                                <div class="audio-icon uploaded">
                                    <i class="mdi mdi-check-circle"></i>
                                </div>
                                <div class="audio-details">
                                    <p class="audio-filename"><?php echo htmlspecialchars($track['audio_file']); ?></p>
                                    <p class="audio-status">✓ Uploaded successfully</p>
                                </div>
                            </div>
                            <audio controls class="audio-player">
                                <?php $audioPathUser = ltrim($track['audio_file_path'] ?? $track['audio_file'], '/'); ?>
                                <source src="/<?php echo htmlspecialchars($audioPathUser); ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            <div class="audio-actions">
                                <button type="button" class="btn-change-audio" onclick="changeAudio(<?php echo $i; ?>)">
                                    <i class="mdi mdi-swap-horizontal"></i> Change File
                                </button>
                                <button type="button" class="btn-remove-audio" onclick="removeAudio(<?php echo $i; ?>, <?php echo $track['id']; ?>)">
                                    <i class="mdi mdi-delete"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Upload State -->
                        <div class="audio-upload-box" onclick="document.getElementById('audio_<?php echo $i; ?>').click()">
                            <i class="mdi mdi-cloud-upload"></i>
                            <p>Click to Upload Audio</p>
                            <small>WAV, FLAC, or MP3 320kbps • Max 200MB</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- File Input (hidden until change clicked) -->
                    <input type="file" id="audio_<?php echo $i; ?>" name="audio_<?php echo $i; ?>" accept="audio/wav,audio/mp3,audio/flac,audio/mpeg,audio/x-wav" 
                           style="display: none;" 
                           onchange="handleAudioSelect(this, <?php echo $i; ?>)">
                    
                    <!-- Selected File Preview (shown after selection) -->
                    <div class="audio-selected" id="audio_selected_<?php echo $i; ?>" style="display: none;">
                        <!-- Upload Status Indicator -->
                        <div class="upload-status" id="upload_status_<?php echo $i; ?>" style="display: none;">
                            <div class="upload-spinner"></div>
                            <span class="upload-status-text" id="upload_status_text_<?php echo $i; ?>">Uploading...</span>
                        </div>
                        <div class="audio-info">
                            <div class="audio-icon">
                                <i class="mdi mdi-music-note"></i>
                            </div>
                            <div class="audio-details">
                                <p class="audio-filename" id="filename_<?php echo $i; ?>"></p>
                                <p class="audio-size" id="filesize_<?php echo $i; ?>"></p>
                            </div>
                        </div>
                        <!-- Audio Preview Player -->
                        <div class="audio-preview-container" style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <audio controls id="audio_preview_<?php echo $i; ?>" style="width: 100%; height: 40px;">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                        <div class="upload-progress" id="progress_<?php echo $i; ?>">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill_<?php echo $i; ?>" style="width: 0%"></div>
                            </div>
                            <div class="progress-details">
                                <span class="progress-percent" id="progressPercent_<?php echo $i; ?>">0%</span>
                                <div class="progress-stats">
                                    <span class="progress-stat" id="progressSpeed_<?php echo $i; ?>">
                                        <i class="mdi mdi-speedometer"></i> -- MB/s
                                    </span>
                                    <span class="progress-stat" id="progressTime_<?php echo $i; ?>">
                                        <i class="mdi mdi-clock-outline"></i> -- remaining
                                    </span>
                                    <span class="progress-stat" id="progressSize_<?php echo $i; ?>">
                                        <i class="mdi mdi-harddisk"></i> 0 / 0 MB
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="upload-error" id="error_<?php echo $i; ?>" style="display: none;">
                            <i class="mdi mdi-alert-circle"></i>
                            <span class="error-text"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Song Title -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Song Title <span class="required">*</span></label>
                        <input type="text" name="song_title_<?php echo $i; ?>" required 
                               value="<?php echo $track ? htmlspecialchars($track['song_title']) : ''; ?>"
                               placeholder="Enter song title">
                    </div>
                    <div class="form-group">
                        <label>Version Info <span class="optional">(optional)</span></label>
                        <input type="text" name="version_info_<?php echo $i; ?>" 
                               value="<?php echo $track ? htmlspecialchars($track['version_info'] ?? '') : ''; ?>"
                               placeholder="e.g., Remix, Live">
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- Songwriters -->
                <div class="form-section-title">
                    <i class="mdi mdi-pencil"></i>
                    Songwriter(s) <span class="required">*</span>
                </div>
                <div id="songwriters_<?php echo $i; ?>">
                    <?php 
                    $songwriters = $track ? $track['songwriters'] : [''];
                    foreach ($songwriters as $j => $writer): 
                    ?>
                    <div class="credit-row">
                        <input type="text" name="songwriters_<?php echo $i; ?>[]" 
                               value="<?php echo htmlspecialchars($writer); ?>"
                               placeholder="Legal First Name and Last Name" required>
                        <?php if ($j === 0): ?>
                        <span style="color: rgba(255,255,255,0.4); font-size: 12px;">Songwriter</span>
                        <?php else: ?>
                        <button type="button" class="btn-remove" onclick="this.closest('.credit-row').remove()">
                            <i class="mdi mdi-close"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addCredit('songwriters_<?php echo $i; ?>', 'songwriter')">
                    <i class="mdi mdi-plus"></i> Add Songwriter
                </button>
                
                <div class="section-divider"></div>
                
                <!-- Performing Artists -->
                <div class="form-section-title">
                    <i class="mdi mdi-account-group"></i>
                    Performing Artist(s)
                </div>
                <div id="artists_<?php echo $i; ?>">
                    <?php 
                    $artists = $track ? $track['artists'] : [['artist_name' => $release['primary_artist'], 'role' => 'Main Artist']];
                    if (empty($artists)) $artists = [['artist_name' => '', 'role' => 'Main Artist']];
                    foreach ($artists as $j => $artist): 
                    ?>
                    <div class="credit-row">
                        <input type="text" name="artists_<?php echo $i; ?>[]" 
                               value="<?php echo htmlspecialchars($artist['artist_name']); ?>"
                               placeholder="Artist / Creative Name">
                        <select name="artist_roles_<?php echo $i; ?>[]">
                            <option value="Main Artist" <?php echo $artist['role'] === 'Main Artist' ? 'selected' : ''; ?>>Main Artist</option>
                            <option value="Featured Artist" <?php echo $artist['role'] === 'Featured Artist' ? 'selected' : ''; ?>>Featured Artist</option>
                            <option value="Producer" <?php echo $artist['role'] === 'Producer' ? 'selected' : ''; ?>>Producer</option>
                            <option value="Composer" <?php echo $artist['role'] === 'Composer' ? 'selected' : ''; ?>>Composer</option>
                        </select>
                        <?php if ($j === 0): ?>
                        <span></span>
                        <?php else: ?>
                        <button type="button" class="btn-remove" onclick="this.closest('.credit-row').remove()">
                            <i class="mdi mdi-close"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addCreditRow('artists_<?php echo $i; ?>')">
                    <i class="mdi mdi-plus"></i> Add Artist
                </button>
                
                <div class="section-divider"></div>
                
                <!-- Copyright -->
                <div class="form-section-title">
                    <i class="mdi mdi-copyright"></i>
                    Copyright Information
                </div>
                <div class="copyright-section">
                    <div class="copyright-warning">
                        You may be prompted to provide proof of this information before or after your release is delivered to digital stores.
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-option">
                                <input type="checkbox" name="musical_owner_<?php echo $i; ?>" 
                                       <?php echo $track && $track['musical_composition_owner'] ? 'checked' : ''; ?>>
                                <span>I am the Musical Composition Copyright Owner and/or have permission to distribute</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-option">
                                <input type="checkbox" name="sound_owner_<?php echo $i; ?>" 
                                       <?php echo $track && $track['sound_recording_owner'] ? 'checked' : ''; ?>>
                                <span>I am the Sound Recording Copyright Owner and/or have permission to distribute</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label>Is this a cover of another song? <a href="#" style="color: #00d4aa;">Learn more</a></label>
                        <div class="radio-group" style="display: flex; gap: 15px;">
                            <label class="checkbox-option">
                                <input type="radio" name="is_cover_<?php echo $i; ?>" value="1" 
                                       <?php echo $track && $track['is_cover'] ? 'checked' : ''; ?>
                                       onchange="toggleCover(<?php echo $i; ?>, true)">
                                <span>Yes</span>
                            </label>
                            <label class="checkbox-option">
                                <input type="radio" name="is_cover_<?php echo $i; ?>" value="0" 
                                       <?php echo !$track || !$track['is_cover'] ? 'checked' : ''; ?>
                                       onchange="toggleCover(<?php echo $i; ?>, false)">
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="cover_info_<?php echo $i; ?>" class="cover-section <?php echo $track && $track['is_cover'] ? 'visible' : ''; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Original Song Title</label>
                                <input type="text" name="original_title_<?php echo $i; ?>" 
                                       value="<?php echo $track ? htmlspecialchars($track['original_song_title'] ?? '') : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Original Artist</label>
                                <input type="text" name="original_artist_<?php echo $i; ?>" 
                                       value="<?php echo $track ? htmlspecialchars($track['original_artist'] ?? '') : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- Additional Info -->
                <div class="form-section-title">
                    <i class="mdi mdi-plus-circle"></i>
                    Additional Information
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <label class="checkbox-option">
                            <input type="checkbox" name="instrumental_<?php echo $i; ?>" 
                                   <?php echo $track && $track['is_instrumental'] ? 'checked' : ''; ?>>
                            <span>Instrumental - This song has no lyrics</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Does this song have explicit lyrics?</label>
                    <div class="checkbox-group">
                        <label class="checkbox-option">
                            <input type="radio" name="explicit_<?php echo $i; ?>" value="1" 
                                   <?php echo $track && $track['has_explicit_lyrics'] ? 'checked' : ''; ?>>
                            <span>Yes</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="radio" name="explicit_<?php echo $i; ?>" value="0" 
                                   <?php echo !$track || !$track['has_explicit_lyrics'] ? 'checked' : ''; ?>>
                            <span>No</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ISRC Code <span class="optional">(optional)</span></label>
                        <input type="text" name="isrc_code_<?php echo $i; ?>" 
                               value="<?php echo $track ? htmlspecialchars($track['isrc_code'] ?? '') : ''; ?>"
                               placeholder="If you don't have one, we'll generate for you">
                        <p class="help-text">International Standard Recording Code</p>
                    </div>
                    <div class="form-group">
                        <label>Language of Lyrics</label>
                        <select name="language_<?php echo $i; ?>">
                            <option value="<?php echo $release['language']; ?>" selected><?php echo $release['language']; ?></option>
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Punjabi">Punjabi</option>
                            <option value="Tamil">Tamil</option>
                            <option value="Telugu">Telugu</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Lyrics <span class="optional">(recommended)</span></label>
                    <textarea name="lyrics_<?php echo $i; ?>" rows="5" placeholder="Enter song lyrics"><?php echo $track ? htmlspecialchars($track['lyrics'] ?? '') : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>TikTok Clip Start Time <span class="optional">(optional)</span></label>
                        <div class="tiktok-input">
                            <input type="number" name="tiktok_min_<?php echo $i; ?>" min="0" max="59" 
                                   value="<?php echo $track ? $track['tiktok_clip_start_min'] : '00'; ?>" placeholder="00">
                            <span>:</span>
                            <input type="number" name="tiktok_sec_<?php echo $i; ?>" min="0" max="59" 
                                   value="<?php echo $track ? $track['tiktok_clip_start_sec'] : '00'; ?>" placeholder="00">
                        </div>
                        <p class="help-text">Minutes : Seconds</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endfor; ?>

        <?php if ($release['release_type'] !== 'single'): ?>
        <!-- Add Track Button (for Album/EP) -->
        <div style="text-align: center; margin: 30px 0;">
            <button type="button" class="btn btn-secondary" onclick="addNewTrack()">
                <i class="mdi mdi-plus"></i> Add Another Track
            </button>
        </div>
        <?php endif; ?>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="/index.php?q=release-create&id=<?php echo $release_id; ?>&step=2" class="btn btn-secondary">
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
    function addCredit(containerId, type) {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = 'credit-row';
        div.innerHTML = `
            <input type="text" name="${containerId}[]" placeholder="Legal First Name and Last Name" required>
            <span style="color: rgba(255,255,255,0.4); font-size: 12px;">${type}</span>
            <button type="button" class="btn-remove" onclick="this.closest('.credit-row').remove()">
                <i class="mdi mdi-close"></i>
            </button>
        `;
        container.appendChild(div);
    }
    
    function addCreditRow(containerId) {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.className = 'credit-row';
        div.innerHTML = `
            <input type="text" name="${containerId.replace('artists_', 'artists_')}[]" placeholder="Artist / Creative Name">
            <select name="${containerId.replace('artists_', 'artist_roles_')}[]">
                <option value="Main Artist">Main Artist</option>
                <option value="Featured Artist">Featured Artist</option>
                <option value="Producer">Producer</option>
                <option value="Composer">Composer</option>
            </select>
            <button type="button" class="btn-remove" onclick="this.closest('.credit-row').remove()">
                <i class="mdi mdi-close"></i>
            </button>
        `;
        container.appendChild(div);
    }
    
    function toggleCover(trackNum, show) {
        const section = document.getElementById('cover_info_' + trackNum);
        if (show) {
            section.classList.add('visible');
        } else {
            section.classList.remove('visible');
        }
    }
    
    // Audio Upload Functions
    function handleAudioSelect(input, trackNum) {
        const file = input.files[0];
        if (!file) return;
        
        // IMMEDIATELY show upload preview box with status
        const selectedDiv = document.getElementById('audio_selected_' + trackNum);
        const uploadStatus = document.getElementById('upload_status_' + trackNum);
        const uploadStatusText = document.getElementById('upload_status_text_' + trackNum);
        const filenameEl = document.getElementById('filename_' + trackNum);
        const filesizeEl = document.getElementById('filesize_' + trackNum);
        
        selectedDiv.style.display = 'block';
        uploadStatus.style.display = 'flex';
        uploadStatusText.textContent = 'Preparing upload...';
        uploadStatusText.style.color = '#00d4aa';
        filenameEl.textContent = file.name;
        filesizeEl.textContent = formatFileSize(file.size);
        
        // Get track ID and release ID
        const trackIdInput = document.querySelector(`input[name="track_id_${trackNum}"]`);
        const trackId = trackIdInput ? trackIdInput.value : 0;
        const releaseId = document.getElementById('release_id').value;
        
        // Validate file
        const maxSize = 200 * 1024 * 1024; // 200MB
        const allowedExts = ['wav', 'mp3', 'flac', 'm4a', 'ogg'];
        const ext = file.name.split('.').pop().toLowerCase();
        const errorDiv = document.getElementById('error_' + trackNum);
        const progressDiv = document.getElementById('progress_' + trackNum);
        const progressFill = document.getElementById('progressFill_' + trackNum);
        const progressPercent = document.getElementById('progressPercent_' + trackNum);
        const progressSpeed = document.getElementById('progressSpeed_' + trackNum);
        const progressTime = document.getElementById('progressTime_' + trackNum);
        const progressSize = document.getElementById('progressSize_' + trackNum);
        
        errorDiv.style.display = 'none';
        progressDiv.style.display = 'block';
        uploadStatusText.textContent = 'Uploading... 0%';
        
        // Validate
        let error = '';
        if (file.size > maxSize) {
            error = 'File too large. Maximum 200MB allowed.';
        } else if (!allowedExts.includes(ext)) {
            error = 'Invalid file type. Only WAV, MP3, FLAC, M4A, OGG allowed.';
        }
        
        if (error) {
            errorDiv.querySelector('.error-text').textContent = error;
            errorDiv.style.display = 'flex';
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';
            progressPercent.style.color = '#ff5252';
            uploadStatus.style.display = 'none';
            input.value = '';
            return;
        }
        
        // For large files, use chunked upload
        const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB chunks
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        
        if (totalChunks > 1) {
            // Use chunked upload for large files
            uploadChunked(file, trackId, releaseId, trackNum, trackIdInput, uploadStatusText, 
                progressFill, progressPercent, progressSpeed, progressTime, progressSize, 
                uploadStatus, errorDiv, input, selectedDiv);
            return;
        }
        
        // Small file - regular upload
        const formData = new FormData();
        formData.append('audio', file);
        formData.append('track_id', trackId);
        formData.append('release_id', releaseId);
        formData.append('track_num', trackNum);
        
        const startTime = Date.now();
        let lastLoaded = 0;
        let lastTime = startTime;
        
        progressPercent.textContent = '0%';
        progressPercent.style.color = '#00d4aa';
        progressFill.style.width = '0%';
        progressSize.innerHTML = `<i class="mdi mdi-harddisk"></i> 0 / ${(file.size / 1024 / 1024).toFixed(1)} MB`;
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                const now = Date.now();
                const elapsed = (now - startTime) / 1000;
                const loadedMB = e.loaded / 1024 / 1024;
                const totalMB = e.total / 1024 / 1024;
                
                // Calculate speed (MB/s)
                const timeDiff = (now - lastTime) / 1000;
                const loadedDiff = (e.loaded - lastLoaded) / 1024 / 1024;
                const speed = timeDiff > 0 ? (loadedDiff / timeDiff).toFixed(1) : '0.0';
                
                // Calculate remaining time
                const remainingBytes = e.total - e.loaded;
                const speedBytesPerSec = (e.loaded / 1024 / 1024) / elapsed;
                let remainingTime = speedBytesPerSec > 0 ? Math.round((remainingBytes / 1024 / 1024) / speedBytesPerSec) : 0;
                
                let timeText;
                if (remainingTime < 60) {
                    timeText = `${remainingTime}s remaining`;
                } else {
                    timeText = `${Math.ceil(remainingTime / 60)}m remaining`;
                }
                
                progressFill.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
                uploadStatusText.textContent = `Uploading... ${percent}%`;
                progressSpeed.innerHTML = `<i class="mdi mdi-speedometer"></i> ${speed} MB/s`;
                progressTime.innerHTML = `<i class="mdi mdi-clock-outline"></i> ${timeText}`;
                progressSize.innerHTML = `<i class="mdi mdi-harddisk"></i> ${loadedMB.toFixed(1)} / ${totalMB.toFixed(1)} MB`;
                
                lastLoaded = e.loaded;
                lastTime = now;
            }
        });
        
        xhr.addEventListener('load', () => {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    progressFill.style.width = '100%';
                    progressPercent.textContent = '100%';
                    progressPercent.style.color = '#00d4aa';
                    uploadStatusText.textContent = 'Upload Complete!';
                    uploadStatusText.style.color = '#00d4aa';
                    progressTime.innerHTML = `<i class="mdi mdi-check-circle"></i> Upload complete!`;
                    progressSpeed.innerHTML = `<i class="mdi mdi-content-save"></i> ${response.file_size_formatted} saved`;
                    
                    // Update track_id in hidden input if new track was created
                    if (response.track_id && trackIdInput) {
                        trackIdInput.value = response.track_id;
                    }
                    
                    // Reload page to show audio player
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    progressFill.style.width = '0%';
                    progressPercent.textContent = 'Failed';
                    progressPercent.style.color = '#ff5252';
                    uploadStatus.style.display = 'none';
                    progressTime.innerHTML = `<i class="mdi mdi-alert-circle"></i> ${response.error || 'Upload failed'}`;
                    errorDiv.querySelector('.error-text').textContent = response.error || 'Upload failed';
                    errorDiv.style.display = 'flex';
                    input.value = '';
                }
            } catch (e) {
                progressFill.style.width = '0%';
                progressPercent.textContent = 'Failed';
                progressPercent.style.color = '#ff5252';
                uploadStatus.style.display = 'none';
                progressTime.innerHTML = `<i class="mdi mdi-alert-circle"></i> Server error`;
                errorDiv.querySelector('.error-text').textContent = 'Server error';
                errorDiv.style.display = 'flex';
                input.value = '';
            }
        });
        
        xhr.addEventListener('error', () => {
            progressFill.style.width = '0%';
            progressPercent.textContent = 'Failed';
            progressPercent.style.color = '#ff5252';
            uploadStatus.style.display = 'none';
            progressTime.innerHTML = `<i class="mdi mdi-alert-circle"></i> Network error`;
            errorDiv.querySelector('.error-text').textContent = 'Network error';
            errorDiv.style.display = 'flex';
            input.value = '';
        });
        
        xhr.addEventListener('timeout', () => {
            progressFill.style.width = '0%';
            progressPercent.textContent = 'Timeout';
            progressPercent.style.color = '#ff5252';
            uploadStatus.style.display = 'none';
            progressTime.innerHTML = `<i class="mdi mdi-alert-circle"></i> Upload timed out`;
            errorDiv.querySelector('.error-text').textContent = 'Upload timed out. Please try again.';
            errorDiv.style.display = 'flex';
            input.value = '';
        });
        
        xhr.addEventListener('abort', () => {
            progressFill.style.width = '0%';
            progressPercent.textContent = 'Aborted';
            progressPercent.style.color = '#ff5252';
            uploadStatus.style.display = 'none';
            progressTime.innerHTML = `<i class="mdi mdi-alert-circle"></i> Upload aborted`;
            errorDiv.querySelector('.error-text').textContent = 'Upload was aborted.';
            errorDiv.style.display = 'flex';
        });
        
        xhr.open('POST', '/pages/upload_audio_ajax.php');
        xhr.timeout = 600000; // 10 minutes timeout for large uploads
        xhr.send(formData);
    }
    
    // Chunked Upload Function for large files
    function uploadChunked(file, trackId, releaseId, trackNum, trackIdInput, uploadStatusText,
        progressFill, progressPercent, progressSpeed, progressTime, progressSize,
        uploadStatus, errorDiv, input, selectedDiv) {
        
        const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        let currentChunk = 0;
        const startTime = Date.now();
        
        uploadStatusText.textContent = `Uploading chunk 1/${totalChunks}...`;
        progressSize.innerHTML = `<i class="mdi mdi-harddisk"></i> 0 / ${(file.size / 1024 / 1024).toFixed(1)} MB`;
        
        function uploadNextChunk() {
            const start = currentChunk * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);
            
            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('track_id', trackId);
            formData.append('release_id', releaseId);
            formData.append('track_num', trackNum);
            formData.append('chunk_index', currentChunk);
            formData.append('total_chunks', totalChunks);
            formData.append('filename', file.name);
            
            const xhr = new XMLHttpRequest();
            
            // Debug logging
            console.log(`Uploading chunk ${currentChunk + 1}/${totalChunks}...`);
            
            xhr.addEventListener('load', () => {
                console.log(`Chunk ${currentChunk} response:`, xhr.status, xhr.responseText);
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        if (response.chunk_received !== undefined) {
                            // More chunks to upload
                            currentChunk++;
                            const percent = Math.round((currentChunk / totalChunks) * 100);
                            const uploadedMB = (currentChunk * CHUNK_SIZE) / 1024 / 1024;
                            const totalMB = file.size / 1024 / 1024;
                            
                            progressFill.style.width = percent + '%';
                            progressPercent.textContent = percent + '%';
                            uploadStatusText.textContent = `Uploading chunk ${currentChunk + 1}/${totalChunks}...`;
                            progressSize.innerHTML = `<i class="mdi mdi-harddisk"></i> ${Math.min(uploadedMB, totalMB).toFixed(1)} / ${totalMB.toFixed(1)} MB`;
                            
                            // Calculate speed
                            const elapsed = (Date.now() - startTime) / 1000;
                            const speed = (uploadedMB / elapsed).toFixed(1);
                            progressSpeed.innerHTML = `<i class="mdi mdi-speedometer"></i> ${speed} MB/s`;
                            
                            // Upload next chunk
                            uploadNextChunk();
                        } else {
                            // All chunks uploaded
                            progressFill.style.width = '100%';
                            progressPercent.textContent = '100%';
                            uploadStatusText.textContent = 'Upload Complete!';
                            uploadStatusText.style.color = '#00d4aa';
                            progressTime.innerHTML = `<i class="mdi mdi-check-circle"></i> Upload complete!`;
                            progressSpeed.innerHTML = `<i class="mdi mdi-content-save"></i> ${response.file_size_formatted} saved`;
                            
                            if (response.track_id && trackIdInput) {
                                trackIdInput.value = response.track_id;
                            }
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 1200);
                        }
                    } else {
                        uploadStatus.style.display = 'none';
                        errorDiv.querySelector('.error-text').textContent = response.error || 'Upload failed';
                        errorDiv.style.display = 'flex';
                        input.value = '';
                    }
                } catch (e) {
                    uploadStatus.style.display = 'none';
                    errorDiv.querySelector('.error-text').textContent = 'Server error';
                    errorDiv.style.display = 'flex';
                    input.value = '';
                }
            });
            
            xhr.addEventListener('error', () => {
                console.error('Network error on chunk', currentChunk + 1);
                uploadStatus.style.display = 'none';
                errorDiv.querySelector('.error-text').textContent = 'Network error on chunk ' + (currentChunk + 1);
                errorDiv.style.display = 'flex';
                input.value = '';
            });
            
            xhr.addEventListener('timeout', () => {
                console.error('Timeout on chunk', currentChunk + 1);
                uploadStatus.style.display = 'none';
                errorDiv.querySelector('.error-text').textContent = 'Timeout on chunk ' + (currentChunk + 1);
                errorDiv.style.display = 'flex';
                input.value = '';
            });
            
            xhr.open('POST', '/pages/upload_chunked.php');
            xhr.timeout = 30000; // 30 second timeout per chunk
            xhr.send(formData);
        }
        
        // Start uploading first chunk
        uploadNextChunk();
    }
    
    function changeAudio(trackNum) {
        document.getElementById('audio_' + trackNum).click();
    }
    
    function removeAudio(trackNum, trackId) {
        if (!confirm('Are you sure you want to remove this audio file?')) return;
        
        // Send AJAX request to remove audio
        fetch('/pages/remove_audio.php?track_id=' + trackId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the audio section
                    const section = document.getElementById('audio_section_' + trackNum);
                    section.innerHTML = `
                        <div class="audio-upload-box" onclick="document.getElementById('audio_${trackNum}').click()">
                            <i class="mdi mdi-cloud-upload"></i>
                            <p>Click to Upload Audio</p>
                            <small>WAV, FLAC, or MP3 320kbps • Max 200MB</small>
                        </div>
                        <input type="file" id="audio_${trackNum}" name="audio_${trackNum}" accept="audio/wav,audio/mp3,audio/flac,audio/mpeg,audio/x-wav" 
                               style="display: none;" 
                               onchange="handleAudioSelect(this, ${trackNum})">
                        <div class="audio-selected" id="audio_selected_${trackNum}" style="display: none;">
                            <div class="audio-info">
                                <div class="audio-icon">
                                    <i class="mdi mdi-music-note"></i>
                                </div>
                                <div class="audio-details">
                                    <p class="audio-filename" id="filename_${trackNum}"></p>
                                    <p class="audio-size" id="filesize_${trackNum}"></p>
                                </div>
                            </div>
                            <div class="upload-progress" id="progress_${trackNum}">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <span class="progress-text">Ready to upload</span>
                            </div>
                            <div class="upload-error" id="error_${trackNum}" style="display: none;">
                                <i class="mdi mdi-alert-circle"></i>
                                <span class="error-text"></span>
                            </div>
                        </div>
                    `;
                } else {
                    alert('Failed to remove audio: ' + data.error);
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            });
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Track Management Functions
    let nextTrackNum = <?php echo $track_count + 1; ?>;

    function addNewTrack() {
        const trackCountInput = document.getElementById('track_count');
        const currentCount = parseInt(trackCountInput.value);
        const newTrackNum = nextTrackNum++;
        
        // Update track count
        trackCountInput.value = currentCount + 1;
        
        // Create new track card HTML
        const trackCard = document.createElement('div');
        trackCard.className = 'track-card';
        trackCard.id = 'track_card_' + newTrackNum;
        trackCard.innerHTML = getTrackCardHTML(newTrackNum);
        
        // Insert before the Add Track button
        const addTrackBtn = document.querySelector('[onclick="addNewTrack()"]').closest('div');
        addTrackBtn.parentNode.insertBefore(trackCard, addTrackBtn);
        
        // Scroll to new track
        trackCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function removeTrack(trackNum) {
        if (!confirm('Are you sure you want to remove this track?')) return;
        
        const trackCard = document.getElementById('track_card_' + trackNum);
        if (trackCard) {
            trackCard.remove();
            
            // Update track count
            const trackCountInput = document.getElementById('track_count');
            const currentCount = parseInt(trackCountInput.value);
            trackCountInput.value = currentCount - 1;
            
            // Re-number remaining tracks
            renumberTracks();
        }
    }

    function renumberTracks() {
        const trackCards = document.querySelectorAll('.track-card');
        let trackNum = 1;
        
        trackCards.forEach((card) => {
            const oldNum = card.id.replace('track_card_', '');
            
            // Update card ID
            card.id = 'track_card_' + trackNum;
            
            // Update badge and title
            const badge = card.querySelector('#track_num_badge_' + oldNum);
            const title = card.querySelector('#track_title_' + oldNum);
            if (badge) {
                badge.id = 'track_num_badge_' + trackNum;
                badge.textContent = trackNum;
            }
            if (title) {
                title.id = 'track_title_' + trackNum;
                title.textContent = 'Track ' + trackNum + ' Information';
            }
            
            // Update all input names and IDs inside this card
            const inputs = card.querySelectorAll('[name*="_' + oldNum + '"], [id*="_' + oldNum + '"], [id*="' + oldNum + '"], [onclick*="' + oldNum + '"]');
            inputs.forEach(input => {
                // Update name attributes
                if (input.name) {
                    input.name = input.name.replace(new RegExp('_' + oldNum + '(?![0-9])', 'g'), '_' + trackNum);
                }
                // Update id attributes
                if (input.id && input.id !== 'track_card_' + trackNum) {
                    input.id = input.id.replace(new RegExp('_' + oldNum + '$', 'g'), '_' + trackNum);
                    input.id = input.id.replace(new RegExp('^' + oldNum + '$', 'g'), trackNum);
                }
                // Update onclick handlers
                if (input.onclick) {
                    const onclickStr = input.getAttribute('onclick');
                    if (onclickStr) {
                        input.setAttribute('onclick', onclickStr.replace(new RegExp('\\b' + oldNum + '\\b', 'g'), trackNum));
                    }
                }
            });
            
            // Update remove button onclick
            const removeBtn = card.querySelector('[onclick^="removeTrack"]');
            if (removeBtn && trackNum > 1) {
                removeBtn.setAttribute('onclick', 'removeTrack(' + trackNum + ')');
            }
            
            trackNum++;
        });
        
        // Reset nextTrackNum
        nextTrackNum = trackNum;
    }

    function getTrackCardHTML(trackNum) {
        const primaryArtist = '<?php echo addslashes($release['primary_artist']); ?>';
        const releaseLanguage = '<?php echo addslashes($release['language']); ?>';
        
        return `
            <div class="track-header">
                <h3>
                    <span class="track-number" id="track_num_badge_${trackNum}">${trackNum}</span>
                    <span id="track_title_${trackNum}">Track ${trackNum} Information</span>
                </h3>
                <button type="button" class="btn-remove" onclick="removeTrack(${trackNum})" title="Remove Track">
                    <i class="mdi mdi-delete"></i>
                </button>
            </div>
            <div class="track-body">
                <input type="hidden" name="track_id_${trackNum}" value="0">
                
                <!-- Audio Upload Section -->
                <div class="audio-section" id="audio_section_${trackNum}">
                    <div class="audio-upload-box" onclick="document.getElementById('audio_${trackNum}').click()">
                        <i class="mdi mdi-cloud-upload"></i>
                        <p>Click to Upload Audio</p>
                        <small>WAV, FLAC, or MP3 320kbps • Max 200MB</small>
                    </div>
                    <input type="file" id="audio_${trackNum}" name="audio_${trackNum}" accept="audio/wav,audio/mp3,audio/flac,audio/mpeg,audio/x-wav" 
                           style="display: none;" 
                           onchange="handleAudioSelect(this, ${trackNum})">
                    <div class="audio-selected" id="audio_selected_${trackNum}" style="display: none;">
                        <div class="upload-status" id="upload_status_${trackNum}" style="display: none;">
                            <div class="upload-spinner"></div>
                            <span class="upload-status-text" id="upload_status_text_${trackNum}">Uploading...</span>
                        </div>
                        <div class="audio-info">
                            <div class="audio-icon">
                                <i class="mdi mdi-music-note"></i>
                            </div>
                            <div class="audio-details">
                                <p class="audio-filename" id="filename_${trackNum}"></p>
                                <p class="audio-size" id="filesize_${trackNum}"></p>
                            </div>
                        </div>
                        <div class="audio-preview-container" style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <audio controls id="audio_preview_${trackNum}" style="width: 100%; height: 40px;">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                        <div class="upload-progress" id="progress_${trackNum}">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill_${trackNum}" style="width: 0%"></div>
                            </div>
                            <div class="progress-details">
                                <span class="progress-percent" id="progressPercent_${trackNum}">0%</span>
                                <div class="progress-stats">
                                    <span class="progress-stat" id="progressSpeed_${trackNum}">
                                        <i class="mdi mdi-speedometer"></i> -- MB/s
                                    </span>
                                    <span class="progress-stat" id="progressTime_${trackNum}">
                                        <i class="mdi mdi-clock-outline"></i> -- remaining
                                    </span>
                                    <span class="progress-stat" id="progressSize_${trackNum}">
                                        <i class="mdi mdi-harddisk"></i> 0 / 0 MB
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="upload-error" id="error_${trackNum}" style="display: none;">
                            <i class="mdi mdi-alert-circle"></i>
                            <span class="error-text"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Song Title -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Song Title <span class="required">*</span></label>
                        <input type="text" name="song_title_${trackNum}" required 
                               value=""
                               placeholder="Enter song title">
                    </div>
                    <div class="form-group">
                        <label>Version Info <span class="optional">(optional)</span></label>
                        <input type="text" name="version_info_${trackNum}" 
                               value=""
                               placeholder="e.g., Remix, Live">
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- Songwriters -->
                <div class="form-section-title">
                    <i class="mdi mdi-pencil"></i>
                    Songwriter(s) <span class="required">*</span>
                </div>
                <div id="songwriters_${trackNum}">
                    <div class="credit-row">
                        <input type="text" name="songwriters_${trackNum}[]" 
                               value=""
                               placeholder="Legal First Name and Last Name" required>
                        <span style="color: rgba(255,255,255,0.4); font-size: 12px;">Songwriter</span>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addCredit('songwriters_${trackNum}', 'songwriter')">
                    <i class="mdi mdi-plus"></i> Add Songwriter
                </button>
                
                <div class="section-divider"></div>
                
                <!-- Performing Artists -->
                <div class="form-section-title">
                    <i class="mdi mdi-account-group"></i>
                    Performing Artist(s)
                </div>
                <div id="artists_${trackNum}">
                    <div class="credit-row">
                        <input type="text" name="artists_${trackNum}[]" 
                               value="${primaryArtist}"
                               placeholder="Artist / Creative Name">
                        <select name="artist_roles_${trackNum}[]">
                            <option value="Main Artist" selected>Main Artist</option>
                            <option value="Featured Artist">Featured Artist</option>
                            <option value="Producer">Producer</option>
                            <option value="Composer">Composer</option>
                        </select>
                        <span></span>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addCreditRow('artists_${trackNum}')">
                    <i class="mdi mdi-plus"></i> Add Artist
                </button>
                
                <div class="section-divider"></div>
                
                <!-- Copyright -->
                <div class="form-section-title">
                    <i class="mdi mdi-copyright"></i>
                    Copyright Information
                </div>
                <div class="copyright-section">
                    <div class="copyright-warning">
                        You may be prompted to provide proof of this information before or after your release is delivered to digital stores.
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-option">
                                <input type="checkbox" name="musical_owner_${trackNum}">
                                <span>I am the Musical Composition Copyright Owner and/or have permission to distribute</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-option">
                                <input type="checkbox" name="sound_owner_${trackNum}">
                                <span>I am the Sound Recording Copyright Owner and/or have permission to distribute</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label>Is this a cover of another song? <a href="#" style="color: #00d4aa;">Learn more</a></label>
                        <div class="radio-group" style="display: flex; gap: 15px;">
                            <label class="checkbox-option">
                                <input type="radio" name="is_cover_${trackNum}" value="1"
                                       onchange="toggleCover(${trackNum}, true)">
                                <span>Yes</span>
                            </label>
                            <label class="checkbox-option">
                                <input type="radio" name="is_cover_${trackNum}" value="0" checked
                                       onchange="toggleCover(${trackNum}, false)">
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="cover_info_${trackNum}" class="cover-section">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Original Song Title</label>
                                <input type="text" name="original_title_${trackNum}" value="">
                            </div>
                            <div class="form-group">
                                <label>Original Artist</label>
                                <input type="text" name="original_artist_${trackNum}" value="">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- Additional Info -->
                <div class="form-section-title">
                    <i class="mdi mdi-plus-circle"></i>
                    Additional Information
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <label class="checkbox-option">
                            <input type="checkbox" name="instrumental_${trackNum}">
                            <span>Instrumental - This song has no lyrics</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Does this song have explicit lyrics?</label>
                    <div class="checkbox-group">
                        <label class="checkbox-option">
                            <input type="radio" name="explicit_${trackNum}" value="1">
                            <span>Yes</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="radio" name="explicit_${trackNum}" value="0" checked>
                            <span>No</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ISRC Code <span class="optional">(optional)</span></label>
                        <input type="text" name="isrc_code_${trackNum}" 
                               value=""
                               placeholder="If you don't have one, we'll generate for you">
                        <p class="help-text">International Standard Recording Code</p>
                    </div>
                    <div class="form-group">
                        <label>Language of Lyrics</label>
                        <select name="language_${trackNum}">
                            <option value="${releaseLanguage}" selected>${releaseLanguage}</option>
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Punjabi">Punjabi</option>
                            <option value="Tamil">Tamil</option>
                            <option value="Telugu">Telugu</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Lyrics <span class="optional">(recommended)</span></label>
                    <textarea name="lyrics_${trackNum}" rows="5" placeholder="Enter song lyrics"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>TikTok Clip Start Time <span class="optional">(optional)</span></label>
                        <div class="tiktok-input">
                            <input type="number" name="tiktok_min_${trackNum}" min="0" max="59" value="00" placeholder="00">
                            <span>:</span>
                            <input type="number" name="tiktok_sec_${trackNum}" min="0" max="59" value="00" placeholder="00">
                        </div>
                        <p class="help-text">Minutes : Seconds</p>
                    </div>
                </div>
            </div>
        `;
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
