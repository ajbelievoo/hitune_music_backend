<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/ping
 * Simple health check - no auth required
 */

function endpoint_v1_ping( $loader, $excuter, $args ){
  $loader->api->set_message( "ok", array(
    "status" => "ok",
    "service" => "HiTune Developer API v1",
    "timestamp" => time()
  ) );
}

?>
