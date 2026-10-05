<?php
/**
 * AJAX Audio Upload Handler
 * Uploads audio file immediately when selected
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fingerprint.php';

header('Content-Type: application/json');

// Debug logging
$debug_log = '/www/wwwroot/web/uploads/debug.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Upload started\n", FILE_APPEND);

requireLogin();

$user = getCurrentUser();
$track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
$release_id = isset($_POST['release_id']) ? intval($_POST['release_id']) : 0;
$track_num = isset($_POST['track_num']) ? intval($_POST['track_num']) : 1;

file_put_contents($debug_log, "track_id: $track_id, release_id: $release_id, track_num: $track_num\n", FILE_APPEND);

// If no release_id provided, try to get it from track
if ($release_id === 0 && $track_id > 0) {
    $stmt = $conn->prepare("SELECT release_id FROM release_tracks WHERE id = ?");
    $stmt->bind_param("i", $track_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $release_id = $row['release_id'];
    }
}

// Verify release belongs to user and is editable
if ($release_id === 0) {
    file_put_contents($debug_log, "Error: Invalid release ID\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Invalid release ID']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM releases WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $release_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$release = $result->fetch_assoc();

if (!$release) {
    file_put_contents($debug_log, "Error: Unauthorized - release not found\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

file_put_contents($debug_log, "Release found: {$release['id']}, status: {$release['status']}\n", FILE_APPEND);

// Only allow upload if release is draft or rejected
if ($release['status'] !== 'draft' && $release['status'] !== 'rejected') {
    file_put_contents($debug_log, "Error: Cannot modify submitted release\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Cannot modify submitted release']);
    exit;
}

// If track_id is 0 or doesn't exist, create a new track
if ($track_id === 0) {
    file_put_contents($debug_log, "Creating new track for release $release_id\n", FILE_APPEND);
    $stmt = $conn->prepare("INSERT INTO release_tracks (release_id, track_number, song_title, audio_uploaded) VALUES (?, ?, ?, 0)");
    $placeholder_title = 'Track ' . $track_num;
    $stmt->bind_param("iis", $release_id, $track_num, $placeholder_title);
    $stmt->execute();
    $track_id = $conn->insert_id;
    file_put_contents($debug_log, "New track created with ID: $track_id\n", FILE_APPEND);
} else {
    // Verify track belongs to this release
    file_put_contents($debug_log, "Verifying track $track_id\n", FILE_APPEND);
    $stmt = $conn->prepare("SELECT * FROM release_tracks WHERE id = ? AND release_id = ?");
    $stmt->bind_param("ii", $track_id, $release_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $track = $result->fetch_assoc();
    
    if (!$track) {
        file_put_contents($debug_log, "Error: Track not found\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => 'Track not found']);
        exit;
    }
}

// Process audio file upload
file_put_contents($debug_log, "Checking FILES: " . (isset($_FILES['audio']) ? 'audio present' : 'no audio') . "\n", FILE_APPEND);
if (isset($_FILES['audio'])) {
    file_put_contents($debug_log, "Error code: " . $_FILES['audio']['error'] . ", Size: " . $_FILES['audio']['size'] . "\n", FILE_APPEND);
}

if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['audio'];
    $max_size = 200 * 1024 * 1024; // 200MB
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum 200MB allowed.']);
        exit;
    }
    
    // Validate extension
    $allowed_exts = ['wav', 'mp3', 'flac', 'm4a', 'ogg'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only WAV, MP3, FLAC, M4A, OGG allowed.']);
        exit;
    }
    
    // Create upload directory
    $upload_dir = '/www/wwwroot/web/uploads/audio/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = 'track_' . $track_id . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old audio file if exists
        if ($track['audio_file_path'] && file_exists('/www/wwwroot/web/' . ltrim($track['audio_file_path'], '/'))) {
            @unlink('/www/wwwroot/web/' . ltrim($track['audio_file_path'], '/'));
        }
        
        // Update database
        $audio_path = 'uploads/audio/' . $filename;
        $duration = '00:00'; // TODO: Get actual duration
        $file_size = $file['size'];
        
        $stmt = $conn->prepare("UPDATE release_tracks SET audio_file = ?, audio_file_path = ?, audio_file_size = ?, audio_duration = ?, audio_uploaded = 1, fingerprint = ? WHERE id = ?");
        $fp = web_audio_fingerprint($audio_path);
        $stmt->bind_param("ssissi", $filename, $audio_path, $file_size, $duration, $fp, $track_id);
        $stmt->execute();
        web_flag_duplicate_audio($conn, $track_id);
        
        echo json_encode([
            'success' => true,
            'track_id' => $track_id,
            'filename' => $filename,
            'file_path' => $audio_path,
            'file_size' => $file_size,
            'file_size_formatted' => number_format($file_size / 1024 / 1024, 2) . ' MB'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    }
} else {
    $error = isset($_FILES['audio']) ? 'Upload error code: ' . $_FILES['audio']['error'] : 'No file received';
    echo json_encode(['success' => false, 'error' => $error]);
}
