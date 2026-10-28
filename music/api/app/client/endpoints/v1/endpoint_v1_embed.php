<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/embed/{track|album|playlist|artist}/{hash}?key=ht_pub_...
 * Validates the publishable key, counts usage, then 302s to the existing
 * branded iframe player (/api/muse_embed/{object}/{hash}/). Playback bytes
 * flow through HiTune's own player — no stream URL is ever exposed.
 */

function endpoint_v1_embed( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth( array( "modes" => array( "publishable", "token" ) ) );
  if ( !$auth ) return;

  if ( !preg_match( "/^v1\/embed\/(track|album|playlist|artist)\/([a-zA-Z0-9\-_]{32})\/?$/", $loader->request->get_requested_url(), $m ) )
    return $loader->developer_api->v1_error( "not_found", 404, "Not found" );

  $map = array(
    "track" => "m_track",
    "album" => "m_album",
    "playlist" => "ugc_playlist",
    "artist" => "m_artist"
  );
  $object = $map[ $m[1] ];
  $hash = $m[2];

  // verify the object exists before bouncing
  $item = $loader->object->__get( $object )->select(
    array( "hash" => $hash ),
    array( "columns" => "ID,hash" )
  );
  if ( !$item )
    return $loader->developer_api->v1_error( "not_found", 404, ucfirst( $m[1] ) . " not found" );

  $target = web_address . "api/muse_embed/{$object}/{$hash}/";

  // forward cosmetic params the muse_embed player understands
  $qs = array();
  foreach( array( "_dark", "_m_color", "_type" ) as $_p )
    if ( isset( $_GET[ $_p ] ) ) $qs[] = $_p . "=" . urlencode( $_GET[ $_p ] );
  if ( $qs ) $target .= "?" . implode( "&", $qs );

  header( "Location: {$target}" );
  http_response_code( 302 );
  exit;

}

?>
