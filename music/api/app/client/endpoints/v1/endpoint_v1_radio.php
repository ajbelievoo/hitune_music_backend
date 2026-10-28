<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/radio?seed=<track_hash>|artist=<artist_hash>|genre=<genre_hash>|chart=viral|indie|top_ai&limit=
 * 24/7 radio stations + listening-party queues (strategy doc §8).
 * Auth: publishable key or Bearer token.
 *
 * Returns a generated station queue of formatted track payloads.
 */

function endpoint_v1_radio( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  $db = $loader->db;
  $limit = $loader->nest->user_input( "get", "limit", "int", [ "min" => 1, "max" => 100 ], 30 );
  $limit = $limit ? $limit : 30;

  $seed   = $loader->nest->user_input( "get", "seed",   "string" );
  $artist = $loader->nest->user_input( "get", "artist", "string" );
  $genre  = $loader->nest->user_input( "get", "genre",  "string" );
  $chart  = $loader->nest->user_input( "get", "chart",  "in_array", [ "values" => [ "viral", "indie", "top_ai" ] ] );

  $station = array( "type" => "mixed", "name" => "HiTune Radio" );
  $ids = array();

  if ( $seed ){
    $hash = $db->escape( $seed );
    $r = $db->query( "SELECT ID, artist_id, title FROM `_c_m_tracks` WHERE hash = '{$hash}' LIMIT 1" );
    if ( !$r || !$r->num_rows )
      return $loader->developer_api->v1_error( "not_found", 404, "Seed track not found" );
    $t = $r->fetch_assoc();
    $station = array( "type" => "track", "name" => $t["title"] . " Radio", "seed" => $seed );
    // Same genres + same artist, popularity-weighted shuffle
    $ids = dev_v1_radio_pick( $db, "
      SELECT DISTINCT t2.ID, t2.s_popularity FROM `_c_m_tracks` t2
      LEFT JOIN `_c_m_tracks_relations` r ON r.track_id = t2.ID AND r.type = 'genre'
      WHERE t2.ID != " . (int)$t["ID"] . " AND ( r.target_id IN (
          SELECT target_id FROM `_c_m_tracks_relations` WHERE track_id = " . (int)$t["ID"] . " AND type = 'genre'
        ) OR t2.artist_id = " . (int)$t["artist_id"] . " )", $t["ID"], $limit );
  }
  elseif ( $artist ){
    $hash = $db->escape( $artist );
    $r = $db->query( "SELECT ID, name FROM `_c_m_artists` WHERE hash = '{$hash}' LIMIT 1" );
    if ( !$r || !$r->num_rows )
      return $loader->developer_api->v1_error( "not_found", 404, "Artist not found" );
    $a = $r->fetch_assoc();
    $station = array( "type" => "artist", "name" => $a["name"] . " Radio", "seed" => $artist );
    $ids = dev_v1_radio_pick( $db, "SELECT ID, s_popularity FROM `_c_m_tracks` WHERE artist_id = " . (int)$a["ID"], null, $limit );
  }
  elseif ( $genre ){
    $hash = $db->escape( $genre );
    $r = $db->query( "SELECT ID, name FROM `_c_m_genres` WHERE hash = '{$hash}' LIMIT 1" );
    if ( !$r || !$r->num_rows )
      return $loader->developer_api->v1_error( "not_found", 404, "Genre not found" );
    $g = $r->fetch_assoc();
    $station = array( "type" => "genre", "name" => $g["name"] . " Radio", "seed" => $genre );
    $ids = dev_v1_radio_pick( $db, "SELECT t.ID, t.s_popularity FROM `_c_m_tracks` t
      JOIN `_c_m_tracks_relations` r ON r.track_id = t.ID AND r.type = 'genre'
      WHERE r.target_id = " . (int)$g["ID"], null, $limit );
  }
  elseif ( $chart ){
    $station = array( "type" => "chart", "name" => ucfirst( str_replace( "_", " ", $chart ) ) . " Radio", "seed" => $chart );
    $where = "1=1"; $order = "s_plays DESC";
    if ( $chart === "top_ai" ) $where = "ai_pct > 0";
    if ( $chart === "indie" )  $where = "uploader_id IS NOT NULL AND uploader_id > 0";
    if ( $chart === "viral" ){ $where = "time_play >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; $order = "s_views_unique DESC"; }
    $ids = dev_v1_radio_pick( $db, "SELECT ID, s_popularity FROM `_c_m_tracks` WHERE {$where} ORDER BY {$order} LIMIT " . ($limit*3), null, $limit, true );
  }
  else {
    $ids = dev_v1_radio_pick( $db, "SELECT ID, s_popularity FROM `_c_m_tracks` ORDER BY s_popularity DESC LIMIT 500", null, $limit, true );
  }

  $items = array();
  if ( $ids ){
    $selected = $loader->object->__get( "m_track" )->select(
      array( "ID_in" => $ids ),
      array( "single" => false, "limit" => false, "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
    );
    if ( $selected ){
      $by_id = array();
      foreach( $selected as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
      foreach( $ids as $_id )
        if ( !empty( $by_id[ $_id ] ) )
          $items[] = $loader->developer_api->track_payload( $by_id[ $_id ] );
    }
  }

  $loader->developer_api->v1_send( array(
    "station" => $station,
    "queue"   => $items
  ) );
}

// Popularity-weighted shuffle pick: gather candidate IDs, shuffle biased by s_popularity.
function dev_v1_radio_pick( $db, $sql, $exclude_id=null, $limit=30, $presorted=false ){

  $cands = array();
  $r = $db->query( $sql . ( $presorted ? "" : " LIMIT 800" ) );
  if ( !$r ) return array();
  while ( $row = $r->fetch_assoc() ){
    $id = (int)$row["ID"];
    if ( $exclude_id && $id === (int)$exclude_id ) continue;
    $w = 1 + max( 0, (int)$row["s_popularity"] );
    $cands[] = array( $id, $w );
  }
  if ( !$cands ) return array();

  // Weighted shuffle
  $picked = array();
  while ( $cands && count( $picked ) < $limit ){
    $total = 0; foreach ( $cands as $c ) $total += $c[1];
    $x = mt_rand( 1, max(1,$total) );
    $acc = 0;
    foreach ( $cands as $i => $c ){
      $acc += $c[1];
      if ( $x <= $acc ){ $picked[] = $c[0]; unset( $cands[$i] ); break; }
    }
    $cands = array_values( $cands );
  }
  return $picked;
}

?>
