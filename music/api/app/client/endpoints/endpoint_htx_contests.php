<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname(__FILE__) . "/endpoint_recommendations.php" );

/**
 * POST /api/htx/contests   (group: api — BOF signed app call)
 *
 * Strategy doc §8 — monthly collab competitions for the app:
 * "Best AI Song" / "Best Indie Track" with live leaderboards.
 * App-facing variant of /api/v1/contests (no developer key needed).
 *
 * Params: slug=<contest slug> (optional — single contest + full board),
 *         board_limit=<n>    (default 20)
 */
function endpoint_htx_contests( $loader, $excuter, $args ){

  $db   = $loader->db;
  $slug = $loader->nest->user_input( "post", "slug", "string" );
  $blim = $loader->nest->user_input( "post", "board_limit", "int", [ "min" => 1, "max" => 100 ] ) ?: 20;

  $where = "status = 'active' AND ( starts_at IS NULL OR starts_at <= NOW() ) AND ( ends_at IS NULL OR ends_at >= NOW() )";
  if ( $slug ) $where .= " AND slug = '" . $db->escape( $slug ) . "'";

  $r = $db->query( "SELECT * FROM `_htx_contests` WHERE {$where} ORDER BY id DESC" . ( $slug ? " LIMIT 1" : " LIMIT 20" ) );
  if ( !$r || !$r->num_rows )
    return $loader->api->set_error( "not_found", array( "code" => "no_active_contests" ) );

  $contests = array();
  while ( $c = $r->fetch_assoc() ){
    $contests[] = array(
      "slug"      => $c["slug"],
      "title"     => $c["title"],
      "chart"     => $c["chart"],
      "prize"     => $c["prize"],
      "starts_at" => $c["starts_at"],
      "ends_at"   => $c["ends_at"],
      "leaderboard" => htx_contests_board( $loader, $c["chart"], $slug ? 100 : $blim ),
    );
  }

  $loader->api->set_message( "ok", array( "contests" => $contests ) );
}

function htx_contests_board( $loader, $chart, $limit ){

  // Same track-item pipeline as /api/htx/charts — includes cover art,
  // artist subtitle and duration instead of the old raw-SQL rows that
  // rendered every leaderboard entry with a placeholder icon.
  $where = array();
  $order = "s_plays";
  if ( $chart === "top_ai" ) $where[] = array( "ai_pct", ">", 0 );
  if ( $chart === "indie" )  $where[] = array( "uploader_id", ">", 0 );
  if ( $chart === "viral" ){
    $where[] = array( "time_play", ">=", date( "Y-m-d H:i:s", time() - 7 * 86400 ) );
    $order   = "s_views_unique";
  }

  $rows = __bof_rec_track_items( $loader, $where, array(
    "order_by" => $order,
    "order"    => "DESC",
    "limit"    => (int)$limit,
  ) );

  $items = array();
  $rank  = 1;
  foreach ( (array)$rows as $t ){

    $raw = !empty( $t["raw"] ) ? $t["raw"] : array();
    $items[] = array(
      "rank"        => $rank++,
      "hash"        => $t["hash"],
      "title"       => $t["title"],
      "artist"      => !empty( $t["sub_title"] ) ? $t["sub_title"] : ( !empty( $t["sub_data"] ) ? $t["sub_data"] : null ),
      "cover"       => !empty( $t["cover"] ) ? $t["cover"] : null,
      "plays"       => isset( $raw["s_plays"] ) ? (int)$raw["s_plays"] : 0,
      "likes"       => isset( $raw["s_likes"] ) ? (int)$raw["s_likes"] : 0,
      "ai_pct"      => isset( $raw["ai_pct"] ) ? (int)$raw["ai_pct"] : 0,
      "ai_badge"    => ( isset( $raw["ai_pct"] ) && (int)$raw["ai_pct"] > 0 ) ? "AI Original" : null,
      "hitune_url"  => web_address . "track/" . $t["hash"],
    );
  }
  return $items;
}

?>
