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

try {
    // Load config
    $config_path = dirname(dirname(dirname(dirname(__FILE__)))) . "/config.php";
    if (file_exists($config_path)) {
        require_once $config_path;
    }

    // Load BOF framework (full load, but we won't use endpoint matching)
    require_once( bof_root . "/loader.php" );
    require_once( root . "/app/client/_setup/endpoint_groups.php" );
    require_once( root . "/app/client/_setup/classes.php" );
    require_once( root . "/app/client/_setup/objects.php" );
    require_once( root . "/app/client/_setup/db.php" );
    require_once( root . "/app/client/_setup/plugins.php" );

    // Load BOF with plugins
    $bof_instance = new BusyOwlFramework(array(
      "name" => "bof_client",
      "plugins" => array(
        "youtube" => [],
        "soundcloud" => [],
        "ffmpeg" => [],
        "google" => [],
        "social_login" => [],
        "id3" => [],
        "chapar" => [],
        "pgt" => [],
        "ai" => [],
        "nodejs" => []
      )
    ));

    function bof(){
      global $bof_instance;
      return $bof_instance;
    }

    bof()->__setup();

    // Initialize developer API plugin (from plugin directory)
    require_once( root . "/../plugins/bof_tool_hitune_extras/classes/class_developer_api.php" );
    bof()->object->developer_api = new developer_api();

    // Define helper functions used by endpoint executers
    function dev_v1_preflight(){
        if ( $_SERVER["REQUEST_METHOD"] !== "OPTIONS" ) return false;
        $origin = !empty( $_SERVER["HTTP_ORIGIN"] ) ? $_SERVER["HTTP_ORIGIN"] : "*";
        header( "Access-Control-Allow-Origin: {$origin}" );
        header( "Vary: Origin" );
        header( "Access-Control-Allow-Headers: x-api-key, authorization, content-type" );
        header( "Access-Control-Allow-Methods: GET, POST, OPTIONS" );
        header( "Access-Control-Max-Age: 86400" );
        http_response_code( 204 );
        exit;
    }

    // Parse path - .htaccess strips /api/v1/, so we get the rest
    $path = $_SERVER['REQUEST_URI'];
    // Remove query string
    if ( ($pos = strpos($path, '?')) !== false ) {
        $path = substr($path, 0, $pos);
    }
    $path = rtrim($path, '/');
    $parts = explode('/', $path);
    $endpoint = $parts[0];

    $v1_dir = root . "/app/client/endpoints/v1/";

    $endpoint_file = null;
    switch ($endpoint) {
        case 'token':
            $endpoint_file = $v1_dir . "endpoint_v1_token.php";
            break;
        case 'plans':
            $endpoint_file = $v1_dir . "endpoint_v1_plans.php";
            break;
        case 'catalog':
        case 'search':
            $endpoint_file = $v1_dir . "endpoint_v1_search.php";
            break;
        case 'charts':
            $endpoint_file = $v1_dir . "endpoint_v1_charts.php";
            break;
        case 'radio':
            $endpoint_file = $v1_dir . "endpoint_v1_radio.php";
            break;
        case 'genres':
            $endpoint_file = $v1_dir . "endpoint_v1_genres.php";
            break;
        case 'tracks':
            if (isset($parts[1]) && $parts[1] === 'stream') {
                $endpoint_file = $v1_dir . "endpoint_v1_track_stream.php";
            } else {
                $endpoint_file = $v1_dir . "endpoint_v1_track.php";
            }
            break;
        case 'albums':
            $endpoint_file = $v1_dir . "endpoint_v1_album.php";
            break;
        case 'artists':
            $endpoint_file = $v1_dir . "endpoint_v1_artist.php";
            break;
        case 'playlists':
            $endpoint_file = $v1_dir . "endpoint_v1_playlist.php";
            break;
        case 'stream':
            $endpoint_file = $v1_dir . "endpoint_v1_stream.php";
            break;
        case 'embed':
            $endpoint_file = $v1_dir . "endpoint_v1_embed.php";
            break;
        case 'oembed':
            $endpoint_file = $v1_dir . "endpoint_v1_oembed.php";
            break;
        case 'ping':
            $endpoint_file = $v1_dir . "endpoint_v1_ping.php";
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'Endpoint not found']);
            exit;
    }

    if ($endpoint_file && file_exists($endpoint_file)) {
        // For BOF-style endpoints, they expect a loader and executer
        // We'll call them directly with stub executer
        $loader = bof();
        $excuter = $endpoint_file;
        // Endpoint files expect to define a function or be included directly
        // Most endpoints use function definition pattern
        require_once $endpoint_file;
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'internal_error', 'message' => 'endpoint file not found', 'file' => $endpoint_file]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'exception', 'message' => $e->getMessage()]);
    exit;
}

?>