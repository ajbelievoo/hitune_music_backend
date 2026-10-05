<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/iyol/stories
 * Public mirror for the app/web: proxies the signed IyolMe
 * /hitune/v1/stories endpoint and returns recent public stories
 * (last 24h) so the HiTune app can render an IyolMe stories rail.
 * Graceful empty result when IyolMe is not configured/reachable.
 */
function endpoint_iyol_stories( $loader, $excuter, $args ){

  $iyol = bof()->iyolme;

  if ( !$iyol->enabled() )
    return $loader->api->set_message( "ok", array( "stories" => array(), "configured" => false ) );

  $limit = (int) $loader->nest->user_input( "request", "limit", "int" );
  if ( $limit < 1 || $limit > 50 ) $limit = 24;

  $res = $iyol->api_request( "POST", "/hitune/v1/stories", array( "limit" => $limit ) );

  if ( !is_array( $res ) || !empty( $res["error"] ) )
    return $loader->api->set_message( "ok", array(
      "stories" => array(), "configured" => true, "reachable" => false
    ) );

  $stories = !empty( $res["stories"] ) && is_array( $res["stories"] ) ? $res["stories"] : array();

  return $loader->api->set_message( "ok", array(
    "stories" => $stories,
    "count"   => count( $stories ),
    "configured" => true, "reachable" => true
  ) );

}

?>
