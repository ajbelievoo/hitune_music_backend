<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: x-api-key, authorization, loadertype, content-type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

// Simple health check - no dependencies
$uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($uri, PHP_URL_PATH);

if ($uri === '/api/v1/ping' || $uri === '/v1/ping') {
    echo json_encode(['status' => 'ok', 'service' => 'HiTune Developer API v1', 'timestamp' => time()]);
    exit;
}

echo json_encode(['error' => 'not_implemented', 'uri' => $uri]);
exit;

?>
