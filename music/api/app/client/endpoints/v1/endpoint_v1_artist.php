<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/artists/{hash}
 * Artist metadata + top tracks + albums. Auth: publishable key or Bearer.
 */

function endpoint_v1_artist( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  if ( !preg_match( "/^v1\/artists\/([a-zA-Z0-9\-_]{32})\/?$/", $loader->request->get_requested_url(), $m ) )
    return $loader->developer_api->v1_error( "not_found", 404, "Not found" );

  $artist = $loader->object->m_artist->select(
    array( "hash" => $m[1] ),
    array( "_eq" => array( "cover" => array() ) )
  );
  if ( !$artist )
    return $loader->developer_api->v1_error( "not_found", 404, "Artist not found" );

  $artist_id = (int)$artist["ID"];

  $top_tracks = array();
  $r = $loader->db->query( "SELECT ID FROM `_c_m_tracks` WHERE artist_id = {$artist_id} ORDER BY s_plays DESC LIMIT 10" );
  $ids = array();
  while ( $r && ( $row = $r->fetch_assoc() ) ) $ids[] = (int)$row["ID"];
  if ( $ids ){
    $items = $loader->object->m_track->select(
      array( "ID_in" => $ids ),
      array( "single" => false, "limit" => false, "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
    );
    if ( $items ){
      $by_id = array();
      foreach( $items as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
      foreach( $ids as $_id )
        if ( !empty( $by_id[ $_id ] ) )
          $top_tracks[] = $loader->developer_api->track_payload( $by_id[ $_id ] );
    }
  }

  $albums = array();
  $r = $loader->db->query( "SELECT ID FROM `_c_m_albums` WHERE artist_id = {$artist_id} ORDER BY time_release DESC LIMIT 20" );
  $aids = array();
  while ( $r && ( $row = $r->fetch_assoc() ) ) $aids[] = (int)$row["ID"];
  if ( $aids ){
    $items = $loader->object->m_album->select(
      array( "ID_in" => $aids ),
      array( "single" => false, "limit" => false, "_eq" => array( "cover" => array(), "artist" => array() ) )
    );
    if ( $items ){
      $by_id = array();
      foreach( $items as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
      foreach( $aids as $_id )
        if ( !empty( $by_id[ $_id ] ) )
          $albums[] = $loader->developer_api->album_payload( $by_id[ $_id ] );
    }
  }

  $loader->developer_api->v1_send( $loader->developer_api->artist_payload( $artist, $top_tracks, $albums ) );

}

?>
