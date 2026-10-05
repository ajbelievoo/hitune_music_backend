<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/catalog?q=&type=track,album,artist,playlist&limit=&offset=
 * Auth: publishable key or Bearer token.
 */

function endpoint_v1_search( $loader, $excuter, $args ){

  // CORS preflight
  if ( $_SERVER["REQUEST_METHOD"] === "OPTIONS" ) {
    $origin = !empty( $_SERVER["HTTP_ORIGIN"] ) ? $_SERVER["HTTP_ORIGIN"] : "*";
    header( "Access-Control-Allow-Origin: {$origin}" );
    header( "Vary: Origin" );
    header( "Access-Control-Allow-Headers: x-api-key, authorization, content-type" );
    header( "Access-Control-Allow-Methods: GET, POST, OPTIONS" );
    header( "Access-Control-Max-Age: 86400" );
    http_response_code( 204 );
    return;
  }

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ){
    // v1_auth already emitted the error JSON and HTTP code
    return;
  }

  // Check query parameter first (from query string), then q
  // Note: 'q' may be overwritten by URL rewrite, so 'query' takes precedence
  $q = $loader->nest->user_input( "get", "query", "string" );
  if ( !$q ) $q = $loader->nest->user_input( "get", "q", "string" );
  // If q contains the URL path (api/v1/search), it's the rewrite parameter, not search query
  if ( $q && strpos( $q, 'api/v1/' ) === 0 ) $q = null;
  if ( !$q || strlen( trim( $q ) ) < 1 )
    return $loader->developer_api->v1_error( "invalid_request", 400, "Missing query parameter (use 'query' or 'q')" );

  $q = trim( $q );

  $types_in = $loader->nest->user_input( "get", "type", "string" );
  $types = $types_in ? array_intersect(
    array_map( "trim", explode( ",", strtolower( $types_in ) ) ),
    array( "track", "album", "artist", "playlist" )
  ) : array( "track", "album", "artist", "playlist" );
  if ( !$types ) $types = array( "track", "album", "artist", "playlist" );

  $limit  = $loader->nest->user_input( "get", "limit", "int", [ "min" => 1, "max" => 50 ], 20 );
  $offset = $loader->nest->user_input( "get", "offset", "int", [ "min" => 0 ], 0 );
  $limit = $limit ? $limit : 20;
  $offset = $offset ? $offset : 0;

  $db = $loader->db;
  $esc = $db->real_escape_string( $q );

  // type => [ table, name column, order column, object, formatter ]
  $map = array(
    "track"    => array( "_c_m_tracks",  "title", "s_plays",       "m_track",      "track_payload" ),
    "album"    => array( "_c_m_albums",  "title", "s_popularity",  "m_album",      "album_payload" ),
    "artist"   => array( "_c_m_artists", "name",  "s_popularity",  "m_artist",     "artist_payload" ),
    "playlist" => array( "_u_playlists", "name",  "s_subscribers", "ugc_playlist", "playlist_payload" )
  );

  $data = array( "query" => $q );
  foreach( $types as $type ){

    list( $table, $col, $ord, $object_name, $formatter ) = $map[ $type ];

    $extra_where = $type === "playlist" ? " AND `private` = 0" : "";
    $r = $db->query( "SELECT ID FROM `{$table}` WHERE `{$col}` LIKE '%{$esc}%'{$extra_where}
      ORDER BY `{$ord}` DESC LIMIT {$offset}, {$limit}" );
    if ( !$r ) continue;

    $ids = array();
    while ( $r && ( $row = $r->fetch_assoc() ) ) $ids[] = (int)$row["ID"];

    $items = array();
    if ( $ids ){
      $the_object = $loader->object->__get( $object_name );
      $selected = $the_object->select(
        array( "ID_in" => $ids ),
        array(
          "single" => false,
          "limit" => false,
          "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() )
        )
      );
      if ( $selected ){
        // keep search order (select re-indexes by ID)
        $by_id = array();
        foreach( $selected as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
        foreach( $ids as $_id )
          if ( !empty( $by_id[ $_id ] ) )
            $items[] = $loader->developer_api->$formatter( $by_id[ $_id ] );
      }
    }

    $data[ $type === "track" ? "tracks" : ( $type === "playlist" ? "playlists" : $type . "s" ) ] = $items;
  }

  $loader->developer_api->v1_send( $data );

}

?>
