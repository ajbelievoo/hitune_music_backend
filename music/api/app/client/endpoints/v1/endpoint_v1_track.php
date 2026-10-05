<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/tracks/{hash}
 * Track metadata + preview_url. Auth: publishable key or Bearer token.
 */

function endpoint_v1_track( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  if ( !preg_match( "/^v1\/tracks\/([a-zA-Z0-9\-_]{32})\/?$/", $loader->request->get_requested_url(), $m ) )
    return $loader->developer_api->v1_error( "not_found", 404, "Not found" );

  $track = $loader->developer_api->load_track( $m[1] );
  if ( !$track )
    return $loader->developer_api->v1_error( "not_found", 404, "Track not found" );

  $loader->developer_api->v1_send( $loader->developer_api->track_payload( $track ) );

}

?>
