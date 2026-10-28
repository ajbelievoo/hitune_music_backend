<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/albums/{hash}
 * Album metadata + track list. Auth: publishable key or Bearer token.
 */

function endpoint_v1_album( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  if ( !preg_match( "/^v1\/albums\/([a-zA-Z0-9\-_]{32})\/?$/", $loader->request->get_requested_url(), $m ) )
    return $loader->developer_api->v1_error( "not_found", 404, "Not found" );

  $album = $loader->object->m_album->select(
    array( "hash" => $m[1] ),
    array( "_eq" => array( "cover" => array(), "artist" => array() ) )
  );
  if ( !$album )
    return $loader->developer_api->v1_error( "not_found", 404, "Album not found" );

  $tracks = array();
  $album_id = (int)$album["ID"];
  $r = $loader->db->query( "SELECT ID FROM `_c_m_tracks` WHERE album_id = {$album_id} ORDER BY album_cd ASC, album_index ASC LIMIT 100" );
  $ids = array();
  while ( $r && ( $row = $r->fetch_assoc() ) ) $ids[] = (int)$row["ID"];

  if ( $ids ){
    $items = $loader->object->m_track->select(
      array( "ID_in" => $ids ),
      array( "single" => false, "limit" => false, "_eq" => array( "cover" => array(), "artist" => array() ) )
    );
    if ( $items ){
      $by_id = array();
      foreach( $items as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
      foreach( $ids as $_id )
        if ( !empty( $by_id[ $_id ] ) )
          $tracks[] = $loader->developer_api->track_payload( $by_id[ $_id ] );
    }
  }

  $loader->developer_api->v1_send( $loader->developer_api->album_payload( $album, $tracks ) );

}

?>
