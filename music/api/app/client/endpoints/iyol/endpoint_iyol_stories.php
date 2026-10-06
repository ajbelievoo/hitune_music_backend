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

  // Keep only stories authored by verified HiTune artist accounts.
  // IyolMe users report their linked HiTune username; a user counts as a
  // verified artist when they manage >= 1 artist page (s_managed_artists,
  // set after an approved m_artist verification request), hold the Artist
  // Managers role (5), or have an approved distribution artist profile.
  $usernames = array();
  foreach ( $stories as $st ){
    $hn = !empty( $st["user"]["hitune_username"] ) ? trim( (string)$st["user"]["hitune_username"] ) : "";
    if ( $hn !== "" ) $usernames[ $hn ] = true;
  }

  if ( !$usernames ){
    $stories = array();
  } else {
    $verified = array();
    $in = "'" . implode( "','", array_map( function( $u ){ return addslashes( $u ); }, array_keys( $usernames ) ) ) . "'";
    $r = $loader->db->query(
      "SELECT username FROM `_u_list` WHERE username IN ({$in}) AND (
         s_managed_artists = 1
         OR FIND_IN_SET( '5', role_ids )
         OR ID IN ( SELECT user_id FROM `_dist_artist_profiles` )
       )"
    );
    if ( $r ) while ( $row = $r->fetch_assoc() ) $verified[ $row["username"] ] = true;

    $stories = array_values( array_filter( $stories, function( $st ) use ( $verified ){
      $hn = !empty( $st["user"]["hitune_username"] ) ? trim( (string)$st["user"]["hitune_username"] ) : "";
      return $hn !== "" && isset( $verified[ $hn ] );
    } ) );

    foreach ( $stories as &$st ) $st["user"]["is_artist_verified"] = true;
    unset( $st );
  }

  return $loader->api->set_message( "ok", array(
    "stories" => $stories,
    "count"   => count( $stories ),
    "configured" => true, "reachable" => true
  ) );

}

?>
