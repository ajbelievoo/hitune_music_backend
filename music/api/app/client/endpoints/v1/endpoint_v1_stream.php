<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/stream/{token}
 * Presigned-URL resolver: validates the HMAC token issued by
 * /v1/tracks/{hash}/stream, counts the stream against the app's monthly
 * stream quota, then 302-redirects to the real playable URL.
 * The token itself is the credential (like a signed CDN URL).
 */

function endpoint_v1_stream( $loader, $excuter, $args ){

  if ( !preg_match( "/^v1\/stream\/([a-zA-Z0-9\-_=\.]{40,200})\/?$/", $loader->request->get_requested_url(), $m ) )
    return $loader->developer_api->v1_error( "invalid_key", 401, "Invalid stream token" );

  $payload = $loader->developer_api->verify_stream_token( $m[1] );
  if ( !$payload )
    return $loader->developer_api->v1_error( "invalid_key", 401, "Stream token is invalid or expired" );

  $app = $loader->db->_select( array(
    "table" => "_dev_apps",
    "where" => array( array( "ID", "=", (int)$payload["a"] ) ),
    "limit" => 1,
    "single" => true
  ) );
  if ( !$app || $app["status"] !== "active" )
    return $loader->developer_api->v1_error( "forbidden", 403, "Application is not active" );

  $plan = $app["plan_id"] ? $loader->developer_api->plan( (int)$app["plan_id"] ) : $loader->developer_api->plan( "sandbox" );
  if ( !$plan || !$plan["allow_stream"] )
    return $loader->developer_api->v1_error( "plan_required", 403, "Streaming is not enabled for this plan" );

  $quota = $loader->developer_api->quota( $app, $plan, true );
  if ( !$quota["allowed"] )
    return $loader->developer_api->v1_error( "quota_exceeded", 402, "Monthly stream quota exhausted" );

  $track = $loader->developer_api->load_track( $payload["t"] );
  if ( !$track )
    return $loader->developer_api->v1_error( "not_found", 404, "Track not found" );

  $prefer = $loader->nest->user_input( "get", "quality", "in_array", [ "values" => [ "audio_hq", "audio_lq", "video_hq", "video_lq" ] ] );
  $playable = $loader->developer_api->resolve_stream( $track, $prefer );

  if ( !$playable || empty( $playable["url"] ) )
    return $loader->developer_api->v1_error( "not_found", 404, "No playable source for this track" );

  $loader->developer_api->usage_hit( (int)$app["ID"], true );

  header( "Location: " . $playable["url"] );
  header( "Cache-Control: private, max-age=0, no-store" );
  http_response_code( 302 );
  exit;

}

?>
