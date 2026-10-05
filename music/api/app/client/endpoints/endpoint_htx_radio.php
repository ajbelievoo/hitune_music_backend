<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname(__FILE__) . "/endpoint_recommendations.php" );

/**
 * POST /api/htx/radio   (group: api — BOF signed app call)
 *
 * Strategy doc §8 — 24/7 artist radio stations for the app.
 * App-shaped variant of /api/v1/radio (no developer key needed).
 *
 * Params (pick one):
 *   mode=list                     -> station directory (chart stations)
 *   seed=<track_hash>             -> "<Title> Radio" similarity queue
 *   artist=<artist_hash>          -> artist station
 *   genre=<genre_hash>            -> genre station
 *   chart=viral|indie|top_ai      -> chart station
 *   limit=<n>                     -> queue size (default 30)
 */
function endpoint_htx_radio( $loader, $excuter, $args ){

  $db    = $loader->db;
  $limit = $loader->nest->user_input( "post", "limit", "int", [ "min" => 1, "max" => 100 ] ) ?: 30;
  $mode  = $loader->nest->user_input( "post", "mode", "string" );

  if ( $mode === "list" ){
    // station directory — the three chart stations plus a generic mix
    return $loader->api->set_message( "ok", array(
      "stations" => array(
        array( "type" => "chart", "seed" => "viral",  "name" => "Viral Radio",   "desc" => "This week's hottest tracks" ),
        array( "type" => "chart", "seed" => "indie",  "name" => "Indie Radio",   "desc" => "Independent artists on HiTune" ),
        array( "type" => "chart", "seed" => "top_ai", "name" => "AI Radio",      "desc" => "Top AI Originals" ),
        array( "type" => "chart", "seed" => "mixed",  "name" => "HiTune Radio",  "desc" => "Popularity-weighted mix" ),
      ),
    ) );
  }

  $seed   = $loader->nest->user_input( "post", "seed",   "string" );
  $artist = $loader->nest->user_input( "post", "artist", "string" );
  $genre  = $loader->nest->user_input( "post", "genre",  "string" );
  $chart  = $loader->nest->user_input( "post", "chart",  "in_array", [ "values" => [ "viral", "indie", "top_ai", "mixed" ] ] );

  $station = array( "type" => "mixed", "name" => "HiTune Radio" );
  $ids = array();

  if ( $seed ){
    $hash = $db->escape( $seed );
    $r = $db->query( "SELECT ID, artist_id, title FROM `_c_m_tracks` WHERE hash = '{$hash}' LIMIT 1" );
    if ( !$r || !$r->num_rows )
      return $loader->api->set_error( "invalid_input", array( "code" => "seed_not_found" ) );
    $t = $r->fetch_assoc();
    $station = array( "type" => "track", "name" => $t["title"] . " Radio", "seed" => $seed );
    $ids = htx_radio_pick( $db, "
      SELECT DISTINCT t2.ID, t2.s_popularity FROM `_c_m_tracks` t2
      LEFT JOIN `_c_m_tracks_relations` r ON r.track_id = t2.ID AND r.type = 'genre'
      WHERE t2.ID != " . (int)$t["ID"] . " AND ( r.target_id IN (
          SELECT target_id FROM `_c_m_tracks_relations` WHERE track_id = " . (int)$t["ID"] . " AND type = 'genre'
        ) OR t2.artist_id = " . (int)$t["artist_id"] . " )", (int)$t["ID"], $limit );
  }
  elseif ( $artist ){
    $hash = $db->escape( $artist );
    $r = $db->query( "SELECT ID, name FROM `_c_m_artists` WHERE hash = '{$hash}' LIMIT 1" );
    if ( !$r || !$r->num_rows )
      return $loader->api->set_error( "invalid_input", array( "code" => "artist_not_found" ) );
    $a = $r->fetch_assoc();
    $station = array( "type" => "artist", "name" => $a["name"] . " Radio", "seed" => $artist );
    $ids = htx_radio_pick( $db, "SELECT ID, s_popularity FROM `_c_m_tracks` WHERE artist_id = " . (int)$a["ID"], null, $limit );
  }
  elseif ( $genre ){
    $hash = $db->escape( $genre );
    $r = $db->query( "SELECT ID, name FROM `_c_m_genres` WHERE hash = '{$hash}' LIMIT 1" );
    if ( !$r || !$r->num_rows )
      return $loader->api->set_error( "invalid_input", array( "code" => "genre_not_found" ) );
    $g = $r->fetch_assoc();
    $station = array( "type" => "genre", "name" => $g["name"] . " Radio", "seed" => $genre );
    $ids = htx_radio_pick( $db, "SELECT t.ID, t.s_popularity FROM `_c_m_tracks` t
      JOIN `_c_m_tracks_relations` r ON r.track_id = t.ID AND r.type = 'genre'
      WHERE r.target_id = " . (int)$g["ID"], null, $limit );
  }
  elseif ( $chart && $chart !== "mixed" ){
    $station = array( "type" => "chart", "name" => ucfirst( str_replace( "_", " ", $chart ) ) . " Radio", "seed" => $chart );
    $where = "1=1"; $order = "s_plays DESC";
    if ( $chart === "top_ai" ) $where = "ai_pct > 0";
    if ( $chart === "indie" )  $where = "uploader_id IS NOT NULL AND uploader_id > 0";
    if ( $chart === "viral" ){ $where = "time_play >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; $order = "s_views_unique DESC"; }
    $ids = htx_radio_pick( $db, "SELECT ID, s_popularity FROM `_c_m_tracks` WHERE {$where} ORDER BY {$order} LIMIT " . ($limit*3), null, $limit, true );
    // Chart pools are often empty (time_play/ai_pct are rarely populated)
    // — fall back to all-time most played, then most viewed, so a station
    // never opens on dead air.
    if ( !$ids ){
      $ids = htx_radio_pick( $db, "SELECT ID, s_plays AS s_popularity FROM `_c_m_tracks` WHERE s_plays > 0 ORDER BY s_plays DESC LIMIT " . ($limit*3), null, $limit, true );
      if ( $ids ) $station["note"] = "playing_all_time_popular";
    }
    if ( !$ids )
      $ids = htx_radio_pick( $db, "SELECT ID, s_views AS s_popularity FROM `_c_m_tracks` ORDER BY s_views DESC LIMIT 500", null, $limit, true );
  }
  else {
    $ids = htx_radio_pick( $db, "SELECT ID, s_plays AS s_popularity FROM `_c_m_tracks` ORDER BY s_plays DESC, s_views DESC LIMIT 500", null, $limit, true );
  }

  $items = array();
  if ( $ids ){
    $items = __bof_rec_track_items( $loader, array( "ID_in" => $ids ), array( "limit" => $limit ) );
    // keep the shuffled pick order
    $pos = array_flip( $ids );
    usort( $items, function( $a, $b ) use ( $pos ){
      $ia = $pos[ (int)( $a["raw"]["ID"] ?? $a["raw"]["id"] ?? 0 ) ] ?? PHP_INT_MAX;
      $ib = $pos[ (int)( $b["raw"]["ID"] ?? $b["raw"]["id"] ?? 0 ) ] ?? PHP_INT_MAX;
      return $ia <=> $ib;
    } );
  }

  $loader->api->set_message( "ok", array(
    "station" => $station,
    "items"   => $items,
    "count"   => count( $items ),
  ) );

}

// Popularity-weighted shuffle pick (same algorithm as the v1 dev API).
function htx_radio_pick( $db, $sql, $exclude_id=null, $limit=30, $presorted=false ){

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
