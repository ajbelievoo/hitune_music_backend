<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname(__FILE__) . "/endpoint_recommendations.php" );

/**
 * POST /api/htx/clips   (group: user — BOF signed app call)
 *
 * Strategy doc §4 — "Short Clip / Vertical Reels Discovery" feed.
 * Returns app-shaped m_track items (same payload family as
 * recommendations) each with a suggested 15-30s clip window the app
 * renders in a swipeable vertical feed.
 *
 * Params: page, limit, chart = viral|indie|top_ai
 */
function endpoint_htx_clips( $loader, $excuter, $args ){

  $limit = $loader->nest->user_input( "post", "limit", "int", [ "min" => 1, "max" => 50 ] );
  $limit = $limit ? $limit : 20;
  $page  = max( 1, (int)$loader->nest->user_input( "post", "page", "int" ) ?: 1 );
  $chart = $loader->nest->user_input( "post", "chart", "in_array", [ "values" => [ "viral", "indie", "top_ai" ] ] );

  $where = array();
  if ( $chart === "top_ai" ) $where[] = array( "ai_pct", ">", 0 );
  if ( $chart === "indie" )  $where[] = array( "uploader_id", ">", 0 );

  $order_by = "s_popularity";
  if ( $chart === "viral" ) $order_by = "s_views_unique";

  $items = __bof_rec_track_items( $loader, $where, array(
    "order_by" => $order_by,
    "order"    => "DESC",
    "limit"    => $limit,
    "offset"   => ( $page - 1 ) * $limit,
  ) );

  // attach the clip window each card should play (hook ~35% in, 15-30s)
  foreach ( $items as &$item ){
    $dur = (float)( $item["duration"] ?? ( $item["raw"]["duration"] ?? 0 ) );
    $start = $dur > 60 ? round( $dur * 0.35 ) : 0;
    $len   = $dur > 0 ? min( 30, max( 15, $dur - $start ) ) : 30;
    if ( $len < 5 ) $len = 15;
    $item["clip"]     = array( "start" => max(0,(int)$start), "duration" => (int)$len );
    $item["ai_pct"]   = isset( $item["raw"]["ai_pct"] ) ? (int)$item["raw"]["ai_pct"] : 0;
    $item["reel_hint"] = "publish_as_reel";
  }
  unset( $item );

  $loader->api->set_message( "ok", array(
    "items" => $items,
    "page"  => $page,
    "count" => count( $items ),
  ) );

}

?>
