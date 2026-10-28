<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/clips?page=&limit=&chart=viral|indie|top_ai
 *
 * Vertical short-clips discovery feed (strategy doc §4 — "Short Clip /
 * Vertical Reels Discovery"). Returns trending tracks with a suggested
 * 15-30s clip window (hook region) the app plays in a swipeable feed.
 *
 * Auth: publishable key or Bearer token.
 */
function endpoint_v1_clips( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  $db    = $loader->db;
  $limit = $loader->nest->user_input( "get", "limit", "int", [ "min" => 1, "max" => 50 ], 20 ) ?: 20;
  $page  = max( 1, (int) $loader->nest->user_input( "get", "page", "int" ) ?: 1 );
  $chart = $loader->nest->user_input( "get", "chart", "in_array", [ "values" => [ "viral", "indie", "top_ai" ] ] );

  $where = "t.duration >= 45"; // clips need a real body to cut a window from
  if ( $chart === "top_ai" ) $where .= " AND t.ai_pct > 0";
  if ( $chart === "indie" )  $where .= " AND t.uploader_id IS NOT NULL AND t.uploader_id > 0";
  $order = $chart === "viral" ? "t.s_views_unique DESC" : "t.s_popularity DESC, t.s_plays DESC";

  $offset = ( $page - 1 ) * $limit;
  $ids = array();
  $r = $db->query( "SELECT t.ID FROM `_c_m_tracks` t WHERE {$where} ORDER BY {$order} LIMIT {$offset}, " . (int)$limit );
  while ( $r && ( $row = $r->fetch_assoc() ) ) $ids[] = (int)$row["ID"];

  $items = array();
  if ( $ids ){
    $selected = $loader->object->__get( "m_track" )->select(
      array( "ID_in" => $ids ),
      array( "single" => false, "limit" => false, "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
    );
    $by_id = array();
    foreach ( (array)$selected as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
    foreach ( $ids as $_id ){
      if ( empty( $by_id[$_id] ) ) continue;
      $t = $by_id[$_id];
      $dur = (float)($t["duration"] ?? 0);
      // hook region: start ~35% in (typical chorus), 15-30s window
      $start = $dur > 60 ? round( $dur * 0.35 ) : 0;
      $len   = $dur > 0 ? min( 30, max( 15, min( 30, $dur - $start ) ) ) : 30;
      $items[] = array_merge( $loader->developer_api->track_payload( $t ), array(
        "clip" => array(
          "start"     => max( 0, $start ),
          "duration"  => max( 5, $len ),
          "reel_hint" => "publish_as_reel",   // app offers "Publish as Reel on IyolMe"
        )
      ) );
    }
  }

  $loader->developer_api->v1_send( array(
    "clips" => $items,
    "page"  => $page,
    "count" => count( $items ),
  ) );
}

?>
