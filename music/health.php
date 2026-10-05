<?php
/**
 * music.hitune.in health / readiness probe
 */

header('Content-Type: application/json; charset=utf-8');

$status = 'ok';
$checks = [
    'php' => 'ok',
    'env' => 'ok',
    'database' => 'unknown',
    'files' => 'ok',
];

if (!file_exists(__DIR__ . '/.env')) {
    $checks['env'] = 'missing';
    $status = 'warn';
}

if (!file_exists(__DIR__ . '/api/app/config.php')) {
    $checks['env'] = 'missing_config';
    http_response_code(503);
    echo json_encode(['status' => 'fail', 'checks' => $checks], JSON_PRETTY_PRINT);
    exit;
}

require_once __DIR__ . '/api/app/config.php';

// Check DB connectivity with a short timeout
try {
    $pdo = new PDO(
        'mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8mb4;connect_timeout=5',
        db_user,
        db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
    $pdo->query('SELECT 1');
    $checks['database'] = 'ok';
} catch (Throwable $e) {
    $checks['database'] = 'unreachable';
    $status = 'fail';
}

// Basic filesystem check for the public files directory
if (!is_dir(__DIR__ . '/files')) {
    $checks['files'] = 'missing';
    $status = 'fail';
}

$code = ($status === 'ok') ? 200 : (($status === 'warn') ? 200 : 503);
http_response_code($code);

$payload = [
    'status' => $status,
    'timestamp' => date('c'),
    'checks' => $checks,
];

echo json_encode($payload, JSON_PRETTY_PRINT);
