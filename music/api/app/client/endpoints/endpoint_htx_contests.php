<?php

if ( !defined( "bof_root" ) ) die;

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
      "leaderboard" => htx_contests_board( $db, $c["chart"], $slug ? 100 : $blim ),
    );
  }

  $loader->api->set_message( "ok", array( "contests" => $contests ) );
}

function htx_contests_board( $db, $chart, $limit ){

  $where = "1=1"; $order = "t.s_plays DESC";
  if ( $chart === "top_ai" ) $where = "t.ai_pct > 0";
  if ( $chart === "indie" )  $where = "t.uploader_id IS NOT NULL AND t.uploader_id > 0";
  if ( $chart === "viral" ){ $where = "t.time_play >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; $order = "t.s_views_unique DESC"; }

  $items = array();
  $r = $db->query( "SELECT t.hash, t.title, t.s_plays, t.s_views_unique, t.s_likes, t.ai_pct,
      a.name AS artist, a.hash AS artist_hash
    FROM `_c_m_tracks` t
    LEFT JOIN `_c_m_artists` a ON a.ID = t.artist_id
    WHERE {$where} ORDER BY {$order} LIMIT " . (int)$limit );
  if ( $r ){
    $rank = 1;
    while ( $t = $r->fetch_assoc() ){
      $items[] = array(
        "rank"        => $rank++,
        "hash"        => $t["hash"],
        "title"       => $t["title"],
        "artist"      => $t["artist"],
        "artist_hash" => $t["artist_hash"],
        "plays"       => (int)$t["s_plays"],
        "likes"       => (int)$t["s_likes"],
        "ai_pct"      => (int)$t["ai_pct"],
        "ai_badge"    => ( (int)$t["ai_pct"] > 0 ) ? "AI Original" : null,
        "hitune_url"  => web_address . "track/" . $t["hash"],
      );
    }
  }
  return $items;
}

?>
