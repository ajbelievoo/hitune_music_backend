<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/genres
 * Catalog genre list. Auth: publishable key or Bearer token.
 */

function endpoint_v1_genres( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  $genres = array();
  $r = $loader->db->query( "SELECT hash, name, s_tracks FROM `_c_m_genres` ORDER BY s_tracks DESC LIMIT 200" );
  while ( $r && ( $row = $r->fetch_assoc() ) ){
    $genres[] = array(
      "hash" => $row["hash"],
      "name" => $row["name"],
      "tracks" => (int)$row["s_tracks"]
    );
  }

  $loader->developer_api->v1_send( array( "genres" => $genres ) );

}

?>
