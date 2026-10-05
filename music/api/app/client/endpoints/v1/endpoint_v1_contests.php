<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/contests            -> active contests + live leaderboards
 * GET /api/v1/contests?slug=<s>   -> single contest + full leaderboard
 * Collab competitions (strategy doc §8): monthly "Best AI Song" / "Best Indie Track".
 * Auth: publishable key or Bearer token.
 */

function endpoint_v1_contests( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  $db = $loader->db;
  $slug = $loader->nest->user_input( "get", "slug", "string" );

  $where = "status = 'active' AND ( starts_at IS NULL OR starts_at <= NOW() ) AND ( ends_at IS NULL OR ends_at >= NOW() )";
  if ( $slug ) $where .= " AND slug = '" . $db->escape( $slug ) . "'";

  $contests = array();
  $r = $db->query( "SELECT * FROM `_htx_contests` WHERE {$where} ORDER BY id DESC" . ( $slug ? " LIMIT 1" : " LIMIT 20" ) );
  if ( !$r || !$r->num_rows )
    return $loader->developer_api->v1_error( 'not_found', 404, 'no active contests' );

  while ( $c = $r->fetch_assoc() ){
    $contests[] = array(
      'slug'      => $c['slug'],
      'title'     => $c['title'],
      'chart'     => $c['chart'],
      'prize'     => $c['prize'],
      'starts_at' => $c['starts_at'],
      'ends_at'   => $c['ends_at'],
      'leaderboard' => endpoint_v1_contests_board( $db, $c['chart'], $slug ? 100 : 10 ),
    );
  }

  $loader->developer_api->v1_send( array( 'contests' => $contests ) );
}

function endpoint_v1_contests_board( $db, $chart, $limit ){

  $where = "1=1"; $order = "t.s_plays DESC";
  if ( $chart === 'top_ai' ) $where = "t.ai_pct > 0";
  if ( $chart === 'indie' )  $where = "t.uploader_id IS NOT NULL AND t.uploader_id > 0";
  if ( $chart === 'viral' ){ $where = "t.time_play >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; $order = "t.s_views_unique DESC"; }

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
        'rank'        => $rank++,
        'hash'        => $t['hash'],
        'title'       => $t['title'],
        'artist'      => $t['artist'],
        'artist_hash' => $t['artist_hash'],
        'plays'       => (int)$t['s_plays'],
        'likes'       => (int)$t['s_likes'],
        'ai_badge'    => ( (int)$t['ai_pct'] > 0 ) ? 'AI Original' : null,
        'hitune_url'  => web_address . 'track/' . $t['hash'],
      );
    }
  }
  return $items;
}

?>
