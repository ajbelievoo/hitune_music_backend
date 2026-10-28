<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/tracks/{hash}/stream
 * Bearer token only, plan must have allow_stream. Returns a signed,
 * expiring proxy URL (raw file/CDN paths are never exposed).
 */

function endpoint_v1_track_stream( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth( array( "modes" => array( "token" ) ) );
  if ( !$auth ) return;

  if ( empty( $auth["plan"]["allow_stream"] ) )
    return $loader->developer_api->v1_error( "plan_required", 403, "Direct streaming requires the Pro plan or above", array(
      "plan" => $auth["plan"]["plan_key"],
      "upgrade_url" => web_address . "subscription_plans"
    ) );

  if ( !preg_match( "/^v1\/tracks\/([a-zA-Z0-9\-_]{32})\/stream\/?$/", $loader->request->get_requested_url(), $m ) )
    return $loader->developer_api->v1_error( "not_found", 404, "Not found" );

  $track = $loader->developer_api->load_track( $m[1], false );
  if ( !$track )
    return $loader->developer_api->v1_error( "not_found", 404, "Track not found" );

  $ttl = 4 * 3600;
  $token = $loader->developer_api->sign_stream_token( (int)$auth["app"]["ID"], $track["hash"], $ttl );

  $out = array(
    "url" => web_address . "api/v1/stream/" . $token,
    "expires_at" => time() + $ttl,
    "expires_in" => $ttl,
    "quality" => !empty( $auth["plan"]["stream_quality"] ) ? $auth["plan"]["stream_quality"] : "192kbps",
    "track" => $loader->developer_api->track_payload( $track )
  );

  if ( !empty( $track["lufs"] ) || !empty( $track["peak_db"] ) )
    $out["loudness"] = array(
      "lufs" => $track["lufs"] !== null ? (float)$track["lufs"] : null,
      "peak_db" => $track["peak_db"] !== null ? (float)$track["peak_db"] : null
    );

  $loader->developer_api->v1_send( $out );

}

?>
