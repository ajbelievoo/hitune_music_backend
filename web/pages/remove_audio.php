<?php
/**
 * AJAX Handler for removing audio files
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$track_id = isset($_GET['track_id']) ? intval($_GET['track_id']) : 0;
$user = getCurrentUser();

if ($track_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid track ID']);
    exit;
}

// Verify track belongs to user
$stmt = $conn->prepare("SELECT rt.*, r.user_id, r.status FROM release_tracks rt JOIN releases r ON rt.release_id = r.id WHERE rt.id = ?");
$stmt->bind_param("i", $track_id);
$stmt->execute();
$result = $stmt->get_result();
$track = $result->fetch_assoc();

if (!$track || $track['user_id'] != $user['id']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only allow removal if release is draft or rejected
if ($track['status'] !== 'draft' && $track['status'] !== 'rejected') {
    echo json_encode(['success' => false, 'error' => 'Cannot modify submitted release']);
    exit;
}

// Delete physical file
if ($track['audio_file_path'] && file_exists(__DIR__ . '/../' . $track['audio_file_path'])) {
    unlink(__DIR__ . '/../' . $track['audio_file_path']);
}

// Update database
$stmt = $conn->prepare("UPDATE release_tracks SET audio_file = NULL, audio_file_path = NULL, audio_uploaded = 0 WHERE id = ?");
$stmt->bind_param("i", $track_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
