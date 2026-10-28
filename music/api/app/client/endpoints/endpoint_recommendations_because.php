<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname(__FILE__) . "/endpoint_recommendations.php" );

function endpoint_recommendations_because( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  $items = array();
  $seed_title = null;

  // Explicit seed track takes priority (object_hash/hash + object_type)
  $seed_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  if ( !$seed_hash )
  $seed_hash = $loader->nest->user_input( "post", "hash", "md5" );
  if ( !$seed_hash )
  $seed_hash = $loader->nest->user_input( "post", "object", "md5" );

  $seed = null;
  if ( $seed_hash )
  $seed = $loader->object->m_track->select(
    array( "hash" => $seed_hash ),
    array( "clean" => false )
  );

  if ( !$seed && $userID ){

    // Seed from the most recently played track
    $recent = $loader->object->ugc_action->select(
      array(
        "user_id" => $userID,
        "type" => "stream",
        "object_name" => "m_track"
      ),
      array(
        "single" => false,
        "limit" => 5,
        "order_by" => "time_add",
        "order" => "DESC",
        "clean" => false
      )
    );

    if ( $recent )
    $seed = $loader->object->m_track->select(
      array( "ID" => $recent[0]["object_id"] ),
      array( "clean" => false )
    );

  }

  if ( $seed ){

    $seed_title = $seed["title"];

    $where = array(
      [ "ID", "!=", $seed["ID"] ]
    );

    if ( !empty( $seed["artist_id"] ) )
    $where[] = [ "artist_id", "=", $seed["artist_id"] ];

    $items = __bof_rec_track_items( $loader, $where );

    // widen to same-genre tracks when the artist has too few
    if ( count( $items ) < 10 ){
      $genres = $loader->db->_select( array(
        "table" => "_c_m_tracks_relations",
        "where" => array(
          [ "track_id", "=", $seed["ID"] ],
          [ "type", "=", "genre" ]
        ),
        "columns" => "target_id",
        "single" => false,
        "limit" => false
      ) );
      if ( $genres ){
        $genre_ids = array();
        foreach( $genres as $g ) $genre_ids[] = $g["target_id"];
        $more = __bof_rec_track_items( $loader, array(
          [ "ID", "IN", "SELECT track_id FROM `_c_m_tracks_relations` WHERE target_id IN (" . implode( ",", array_map( "intval", $genre_ids ) ) . ") AND type = 'genre'", true ],
          [ "ID", "!=", $seed["ID"] ]
        ) );
        $seen = array();
        foreach( $items as $_i ) $seen[ $_i["hash"] ] = true;
        foreach( $more as $_i ){
          if ( empty( $seen[ $_i["hash"] ] ) ){
            $items[] = $_i;
            $seen[ $_i["hash"] ] = true;
          }
        }
      }
    }

  }

  if ( empty( $items ) )
  $items = __bof_rec_track_items( $loader );

  $loader->api->set_message( "ok", array(
    "items" => $items,
    "seed" => $seed_title
  ) );

}

?>
