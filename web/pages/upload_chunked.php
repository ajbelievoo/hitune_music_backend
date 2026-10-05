<?php
/**
 * Chunked File Upload Handler
 * Handles large file uploads by receiving chunks
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fingerprint.php';

header('Content-Type: application/json');

requireLogin();

$user = getCurrentUser();
$track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
$release_id = isset($_POST['release_id']) ? intval($_POST['release_id']) : 0;
$track_num = isset($_POST['track_num']) ? intval($_POST['track_num']) : 1;
$chunk_index = isset($_POST['chunk_index']) ? intval($_POST['chunk_index']) : 0;
$total_chunks = isset($_POST['total_chunks']) ? intval($_POST['total_chunks']) : 1;
$filename = isset($_POST['filename']) ? basename($_POST['filename']) : '';

// Debug log
$debug_log = '/www/wwwroot/web/uploads/debug_chunked.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - REQUEST RECEIVED: Chunk $chunk_index/$total_chunks for $filename, track=$track_id, release=$release_id\n", FILE_APPEND);

// Temp directory - use track_num instead of session_id
$temp_dir = '/www/wwwroot/web/uploads/temp/' . $release_id . '_' . $track_num . '/';
file_put_contents($debug_log, "Temp dir: $temp_dir\n", FILE_APPEND);

// If no release_id, try to get from track
if ($release_id === 0 && $track_id > 0) {
    $stmt = $conn->prepare("SELECT release_id FROM release_tracks WHERE id = ?");
    $stmt->bind_param("i", $track_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $release_id = $row['release_id'];
        file_put_contents($debug_log, "Got release_id from track: $release_id\n", FILE_APPEND);
    }
}

// Verify release
if ($release_id === 0) {
    file_put_contents($debug_log, "ERROR: Invalid release ID\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Invalid release ID']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM releases WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $release_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$release = $result->fetch_assoc();

if (!$release || ($release['status'] !== 'draft' && $release['status'] !== 'rejected')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized or cannot modify']);
    exit;
}

file_put_contents($debug_log, "User verified, release valid\n", FILE_APPEND);

// Create temp directory for chunks
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
    file_put_contents($debug_log, "Created temp dir: $temp_dir\n", FILE_APPEND);
}

// Debug $_FILES
file_put_contents($debug_log, "FILES check: " . (isset($_FILES['chunk']) ? 'chunk present' : 'NO CHUNK') . "\n", FILE_APPEND);
if (isset($_FILES['chunk'])) {
    file_put_contents($debug_log, "Chunk error code: " . $_FILES['chunk']['error'] . ", Size: " . $_FILES['chunk']['size'] . "\n", FILE_APPEND);
}

// Save chunk
if (isset($_FILES['chunk']) && $_FILES['chunk']['error'] === UPLOAD_ERR_OK) {
    $chunk_path = $temp_dir . 'chunk_' . $chunk_index;
    $moved = move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_path);
    
    file_put_contents($debug_log, "Chunk $chunk_index saved to $chunk_path (moved: " . ($moved ? 'yes' : 'no') . ")\n", FILE_APPEND);
    
    // If last chunk, reassemble file
    if ($chunk_index === $total_chunks - 1) {
        file_put_contents($debug_log, "Last chunk received, reassembling...\n", FILE_APPEND);
        
        // Get extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed_exts = ['wav', 'mp3', 'flac', 'm4a', 'ogg'];
        
        if (!in_array($ext, $allowed_exts)) {
            // Clean up temp
            array_map('unlink', glob($temp_dir . '*'));
            rmdir($temp_dir);
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
        }
        
        // Create or get track
        if ($track_id === 0) {
            $stmt = $conn->prepare("INSERT INTO release_tracks (release_id, track_number, song_title, audio_uploaded) VALUES (?, ?, ?, 0)");
            $placeholder_title = 'Track ' . $track_num;
            $stmt->bind_param("iis", $release_id, $track_num, $placeholder_title);
            $stmt->execute();
            $track_id = $conn->insert_id;
        }
        
        // Reassemble file
        $upload_dir = '/www/wwwroot/web/uploads/audio/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $final_filename = 'track_' . $track_id . '_' . time() . '.' . $ext;
        $final_path = $upload_dir . $final_filename;
        
        $out = fopen($final_path, 'wb');
        for ($i = 0; $i < $total_chunks; $i++) {
            $chunk_file = $temp_dir . 'chunk_' . $i;
            if (file_exists($chunk_file)) {
                fwrite($out, file_get_contents($chunk_file));
                unlink($chunk_file);
            }
        }
        fclose($out);
        rmdir($temp_dir);
        
        // Update database
        $audio_path = 'uploads/audio/' . $final_filename;
        $file_size = filesize($final_path);
        
        $stmt = $conn->prepare("UPDATE release_tracks SET audio_file = ?, audio_file_path = ?, audio_file_size = ?, audio_uploaded = 1, fingerprint = ? WHERE id = ?");
        $fp = web_audio_fingerprint($audio_path);
        $stmt->bind_param("ssisi", $final_filename, $audio_path, $file_size, $fp, $track_id);
        $stmt->execute();
        web_flag_duplicate_audio($conn, $track_id);
        
        file_put_contents($debug_log, "File assembled: $final_filename, size: $file_size\n", FILE_APPEND);
        
        echo json_encode([
            'success' => true,
            'track_id' => $track_id,
            'filename' => $final_filename,
            'file_size' => $file_size,
            'file_size_formatted' => number_format($file_size / 1024 / 1024, 2) . ' MB'
        ]);
        exit;
    }
    
    // Not last chunk
    echo json_encode(['success' => true, 'chunk_received' => $chunk_index]);
    exit;
}

file_put_contents($debug_log, "ERROR: No chunk received\n", FILE_APPEND);
echo json_encode(['success' => false, 'error' => 'No chunk received']);
?>
