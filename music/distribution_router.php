<?php
/**
 * Music Distribution Router (legacy) — canonical host is distribution.hitune.in
 */

$requestUri = $_SERVER['REQUEST_URI'];
$path = strtok(parse_url($requestUri, PHP_URL_PATH), '?');

$map = [
    '/distribution'           => '/',
    '/distribution/'          => '/',
    '/distribution/dashboard' => '/dashboard',
    '/distribution/submit'    => '/submit',
    '/distribution/admin'     => '/admin',
];

if (isset($map[rtrim($path, '/') ?: '/'])) {
    $q = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: https://distribution.hitune.in' . $map[rtrim($path, '/') ?: '/'] . $q, true, 301);
    exit;
}

http_response_code(404);
echo '<h1>Page Not Found</h1>';
