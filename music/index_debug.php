<?php
// Simple debug - log all requests
$log = date('Y-m-d H:i:s') . ' - URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' - Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
file_put_contents('/tmp/index_debug.log', $log, FILE_APPEND);
echo json_encode(['debug' => 'index.php was called', 'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown']);
?>
