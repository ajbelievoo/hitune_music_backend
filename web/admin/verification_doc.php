<?php
/**
 * Streams an artist's proof document to an authenticated admin only.
 * Files live in uploads/verification_docs/ under random names.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$st = $conn->prepare("SELECT doc_path FROM artist_verifications WHERE id = ? LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

$base = realpath(__DIR__ . '/../uploads/verification_docs');
$file = ($row && $row['doc_path']) ? realpath(__DIR__ . '/../' . $row['doc_path']) : false;
if (!$base || !$file || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
    http_response_code(404);
    exit('Document not found');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
    http_response_code(415);
    exit('Unsupported file type');
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="proof-' . $id . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
