<?php

if ( !defined( "bof_root" ) ) die;

if ( !function_exists( "__bof_rec_track_items" ) ){
function __bof_rec_track_items( $loader, $whereArgs=[], $selectArgs=[] ){

  $defaults = array(
    "single" => false,
    "limit" => 20,
    "as_widget" => true,
    "empty_select" => true,
    "order_by" => "s_plays",
    "order" => "DESC"
  );

  $items = $loader->object->m_track->select(
    $whereArgs,
    array_merge( $defaults, $selectArgs )
  );

  if ( empty( $items ) ) return array();

  $out = array();
  foreach( $items as $item ){

    $hash = !empty( $item["raw"]["hash"] ) ? $item["raw"]["hash"] : ( !empty( $item["hash"] ) ? $item["hash"] : null );
    if ( !$hash ) continue;

    $out[] = array(
      "hash" => $hash,
      "ID" => $hash,
      "title" => $item["title"],
      "sub_title" => !empty( $item["sub_data"] ) ? $item["sub_data"] : null,
      "sub_data" => !empty( $item["sub_data"] ) ? $item["sub_data"] : null,
      "cover" => !empty( $item["cover_url"] ) ? $item["cover_url"] : ( !empty( $item["cover"]["image_thumb"] ) ? $item["cover"]["image_thumb"] : null ),
      "object_type" => "m_track",
      "ot" => "m_track",
      "duration" => !empty( $item["raw"]["duration"] ) ? $item["raw"]["duration"] : null,
      "loudness" => array(
        "lufs" => isset( $item["raw"]["lufs"] ) ? $item["raw"]["lufs"] : null,
        "peak_db" => isset( $item["raw"]["peak_db"] ) ? $item["raw"]["peak_db"] : null
      ),
      "raw" => $item["raw"]
    );

  }

  return $out;

}
}

function endpoint_recommendations( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  $items = array();

  if ( $userID ){

    // Seed from genres/artists of recently played tracks
    $recent = $loader->object->ugc_action->select(
      array(
        "user_id" => $userID,
        "type" => "stream",
        "object_name" => "m_track"
      ),
      array(
        "single" => false,
        "limit" => 10,
        "order_by" => "time_add",
        "order" => "DESC",
        "clean" => false
      )
    );

    if ( $recent ){

      $track_ids = array();
      foreach( $recent as $r ) $track_ids[] = $r["object_id"];

      $genre_ids = array();
      $artist_ids = array();
      foreach( $track_ids as $tid ){
        $track = $loader->object->m_track->select(
          array( "ID" => $tid ),
          array(
            "clean" => false,
            "_eq" => array(
              "genres" => array( "clean" => false )
            )
          )
        );
        if ( !$track ) continue;
        if ( !empty( $track["artist_id"] ) ) $artist_ids[] = $track["artist_id"];
        if ( !empty( $track["bof_rel_genres"] ) ){
          foreach( $track["bof_rel_genres"] as $g ) $genre_ids[] = $g["ID"];
        }
      }

      $genre_ids = array_unique( $genre_ids );
      $artist_ids = array_unique( $artist_ids );

      if ( !empty( $artist_ids ) )
      $items = __bof_rec_track_items( $loader, array(
        [ "artist_id", "IN", implode( ",", array_map( "intval", $artist_ids ) ), true ]
      ) );

      if ( !empty( $genre_ids ) ){

        $genre_items = __bof_rec_track_items( $loader, array(
          [ "ID", "IN", "SELECT track_id FROM `_c_m_tracks_relations` WHERE target_id IN (" . implode( ",", array_map( "intval", $genre_ids ) ) . ") AND type = 'genre'", true ]
        ) );

        $seen = array();
        foreach( $items as $_i ) $seen[ $_i["hash"] ] = true;
        foreach( $genre_items as $_i ){
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
    "items" => $items
  ) );

}

?>
