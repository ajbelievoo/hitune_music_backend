<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Debug: Log that v1.php was called
error_log("v1.php called: " . $_SERVER['REQUEST_URI']);

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

// Parse path
$path = $_SERVER['REQUEST_URI'];
if ( ($pos = strpos($path, '?')) !== false ) {
    $path = substr($path, 0, $pos);
}
// Remove /v1.php or /v1.php/ prefix
$path = preg_replace('#^/v1\.php(/|$)#', '/', $path);
$path = trim($path, '/');

// Debug: Log the parsed path
error_log("v1.php parsed path: " . $path);

// Load BOF framework
require_once( dirname(__FILE__) . "/api/app/config.php" );
require_once( bof_root . "/loader.php" );
require_once( root . "/app/client/loader.php" );

// Set JSON response type
bof()->response->set("json", array());

// Load developer API
require_once( root . "/../plugins/bof_tool_hitune_extras/classes/class_developer_api.php" );
bof()->object->developer_api = new developer_api();

// Route matching
$routes = array(
    'ping' => 'endpoint_v1_ping',
    'search' => 'endpoint_v1_search',
    'catalog' => 'endpoint_v1_search',  // alias for search
    'token' => 'endpoint_v1_token',
    'plans' => 'endpoint_v1_plans',
    'charts' => 'endpoint_v1_charts',
    'radio' => 'endpoint_v1_radio',
    'contests' => 'endpoint_v1_contests',
    'genres' => 'endpoint_v1_genres',
    'oembed' => 'endpoint_v1_oembed',
);

// Dynamic routes
if ( preg_match('/^tracks\/([a-zA-Z0-9\-_]{32})\/stream$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_track_stream';
    $endpoint_args = array('hash' => $m[1]);
}
elseif ( preg_match('/^tracks\/([a-zA-Z0-9\-_]{32})$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_track';
    $endpoint_args = array('hash' => $m[1]);
}
elseif ( preg_match('/^albums\/([a-zA-Z0-9\-_]{32})$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_album';
    $endpoint_args = array('hash' => $m[1]);
}
elseif ( preg_match('/^artists\/([a-zA-Z0-9\-_]{32})$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_artist';
    $endpoint_args = array('hash' => $m[1]);
}
elseif ( preg_match('/^playlists\/([a-zA-Z0-9\-_]{32})$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_playlist';
    $endpoint_args = array('hash' => $m[1]);
}
elseif ( preg_match('/^stream\/([a-zA-Z0-9\-_=\.]{40,200})$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_stream';
    $endpoint_args = array('token' => $m[1]);
}
elseif ( preg_match('/^embed\/(track|album|playlist|artist)\/([a-zA-Z0-9\-_]{32})$/', $path, $m) ) {
    $endpoint = 'endpoint_v1_embed';
    $endpoint_args = array('type' => $m[1], 'hash' => $m[2]);
}
elseif ( isset($routes[$path]) ) {
    $endpoint = $routes[$path];
    $endpoint_args = array();
}
else {
    http_response_code(404);
    echo json_encode(['error' => 'not_found', 'message' => 'Endpoint not found', 'path' => $path]);
    exit;
}

// Load and execute the endpoint
$endpoint_file = root . "/app/client/endpoints/v1/{$endpoint}.php";
if ( !file_exists($endpoint_file) ) {
    http_response_code(500);
    echo json_encode(['error' => 'internal_error', 'message' => 'Endpoint handler not found']);
    exit;
}

require_once($endpoint_file);

// Create a mock loader object with the required methods
$loader = new stdClass();
$loader->developer_api = bof()->object->developer_api;
$loader->nest = bof()->nest;
$loader->db = bof()->db;
$loader->object = bof()->object;
$loader->api = bof()->api;
$loader->general = bof()->general;
$loader->response = bof()->response;

$excuter = new stdClass();

// Execute the endpoint function
if ( function_exists($endpoint) ) {
    $endpoint($loader, $excuter, $endpoint_args);
    // Output the response
    bof()->response->display();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'internal_error', 'message' => 'Endpoint function not found']);
}
?>
