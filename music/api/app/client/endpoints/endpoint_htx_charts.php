<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname(__FILE__) . "/endpoint_recommendations.php" );

/**
 * POST /api/htx/charts   (group: api — BOF signed app call)
 *
 * Strategy doc §8 — weekly leaderboard charts for the app:
 * "Top 50 AI Songs", "Top 100 Indie Stars", "Viral HiTune Tracks".
 * App-shaped variant of /api/v1/charts (no developer key needed).
 *
 * Params: chart = top_ai|indie|viral, page, limit
 */
function endpoint_htx_charts( $loader, $excuter, $args ){

  $limit = $loader->nest->user_input( "post", "limit", "int", [ "min" => 1, "max" => 100 ] );
  $limit = $limit ? $limit : 50;
  $page  = max( 1, (int)$loader->nest->user_input( "post", "page", "int" ) ?: 1 );
  $chart = $loader->nest->user_input( "post", "chart", "in_array", [ "values" => [ "viral", "indie", "top_ai" ] ] ) ?: "viral";

  $where    = array();
  $order_by = "s_popularity";
  $title    = "Viral HiTune Tracks";

  if ( $chart === "top_ai" ){
    $where[] = array( "ai_pct", ">", 0 );
    $order_by = "s_plays";
    $title = "Top 50 AI Songs";
  }
  elseif ( $chart === "indie" ){
    $where[] = array( "uploader_id", ">", 0 );
    $order_by = "s_plays";
    $title = "Top 100 Indie Stars";
  }
  else {
    $order_by = "s_views_unique";
  }

  $items = __bof_rec_track_items( $loader, $where, array(
    "order_by" => $order_by,
    "order"    => "DESC",
    "limit"    => $limit,
    "offset"   => ( $page - 1 ) * $limit,
  ) );

  $rank = ( $page - 1 ) * $limit;
  foreach ( $items as &$item ){
    $item["chart_rank"] = ++$rank;
    $item["ai_pct"] = isset( $item["raw"]["ai_pct"] ) ? (int)$item["raw"]["ai_pct"] : 0;
  }
  unset( $item );

  $loader->api->set_message( "ok", array(
    "chart" => $chart,
    "title" => $title,
    "items" => $items,
    "page"  => $page,
    "count" => count( $items ),
  ) );

}

?>
