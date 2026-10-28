<?php
// Debug file to check if requests are reaching v1.php
$log = date('Y-m-d H:i:s') . ' - URI: ' . $_SERVER['REQUEST_URI'] . ' - Method: ' . $_SERVER['REQUEST_METHOD'] . ' - GET: ' . json_encode($_GET) . "\n";
file_put_contents('/tmp/v1_debug.log', $log, FILE_APPEND);
echo json_encode(['debug' => 'v1.php was called', 'uri' => $_SERVER['REQUEST_URI'], 'get' => $_GET]);
?>
