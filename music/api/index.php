<?php

require_once( dirname(dirname(__FILE__)) . "/api/app/config.php" );

if (defined('maintenance') && maintenance) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'maintenance', 'message' => 'Music Hitune is under maintenance.']);
    exit;
}

require_once( bof_root . "/loader.php" );
require_once( root . "/app/client/loader.php" );

bof()->request->log();
bof()->execute->run();
bof()->response->display();

?>
