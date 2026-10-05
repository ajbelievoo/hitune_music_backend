<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/charts?type=tracks|albums|artists&limit=&offset=
 * Top/trending lists ordered by popularity. Auth: publishable key or Bearer.
 */

function endpoint_v1_charts( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  // Strategy-doc named leaderboards (§8): top_ai (Top 50 AI Songs),
  // indie (Top 100 Indie Stars - distribution-uploaded), viral (7-day unique views).
  $chart = $loader->nest->user_input( "get", "chart", "in_array", [ "values" => [ "top_ai", "indie", "viral" ] ] );
  if ( $chart ) return endpoint_v1_charts_named( $loader, $chart );

  $type   = $loader->nest->user_input( "get", "type", "in_array", [ "values" => [ "tracks", "albums", "artists" ] ], "tracks" );
  $limit  = $loader->nest->user_input( "get", "limit", "int", [ "min" => 1, "max" => 50 ], 20 );
  $offset = $loader->nest->user_input( "get", "offset", "int", [ "min" => 0 ], 0 );
  $limit = $limit ? $limit : 20;
  $offset = $offset ? $offset : 0;

  $map = array(
    "tracks"  => array( "_c_m_tracks",  "m_track",  "track_payload",  "s_plays" ),
    "albums"  => array( "_c_m_albums",  "m_album",  "album_payload",  "s_popularity" ),
    "artists" => array( "_c_m_artists", "m_artist", "artist_payload", "s_popularity" )
  );
  list( $table, $object_name, $formatter, $ord ) = $map[ $type ];

  $r = $loader->db->query( "SELECT ID FROM `{$table}` ORDER BY `{$ord}` DESC LIMIT {$offset}, {$limit}" );
  $ids = array();
  while ( $r && ( $row = $r->fetch_assoc() ) ) $ids[] = (int)$row["ID"];

  $items = array();
  if ( $ids ){
    $selected = $loader->object->__get( $object_name )->select(
      array( "ID_in" => $ids ),
      array( "single" => false, "limit" => false, "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
    );
    if ( $selected ){
      $by_id = array();
      foreach( $selected as $_i ) $by_id[ (int)$_i["ID"] ] = $_i;
      foreach( $ids as $_id )
        if ( !empty( $by_id[ $_id ] ) )
          $items[] = $loader->developer_api->$formatter( $by_id[ $_id ] );
    }
  }

  $loader->developer_api->v1_send( array(
    "type" => $type,
    "items" => $items
  ) );

}

function endpoint_v1_charts_named( $loader, $chart ){

  $db = $loader->db;
  $limit = min( 100, max( 1, (int)( $_GET["limit"] ?? ( $chart === "top_ai" ? 50 : 100 ) ) ) );

  $where = "1=1"; $order = "t.s_plays DESC";
  if ( $chart === "top_ai" ) $where = "t.ai_pct > 0";
  if ( $chart === "indie" )  $where = "t.uploader_id IS NOT NULL AND t.uploader_id > 0";
  if ( $chart === "viral" ){ $where = "t.time_play >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; $order = "t.s_views_unique DESC"; }

  $items = array();
  $r = $db->query( "SELECT t.ID, t.hash, t.title, t.s_plays, t.s_views, t.s_views_unique, t.s_likes,
      t.ai_pct, t.time_release, a.name AS artist, a.hash AS artist_hash, f.path AS cover_path
    FROM `_c_m_tracks` t
    LEFT JOIN `_c_m_artists` a ON a.ID = t.artist_id
    LEFT JOIN `_bof_files` f ON f.ID = t.cover_id
    WHERE {$where} ORDER BY {$order} LIMIT {$limit}" );
  if ( $r ){
    $rank = 1;
    while ( $t = $r->fetch_assoc() ){
      $items[] = array(
        "rank"         => $rank++,
        "hash"         => $t["hash"],
        "title"        => $t["title"],
        "artist"       => $t["artist"],
        "artist_hash"  => $t["artist_hash"],
        "plays"        => (int)$t["s_plays"],
        "views"        => (int)$t["s_views"],
        "unique_views" => (int)$t["s_views_unique"],
        "likes"        => (int)$t["s_likes"],
        "ai_pct"       => (int)$t["ai_pct"],
        "ai_badge"     => ( (int)$t["ai_pct"] > 0 ) ? "AI Original" : null,
        "cover"        => !empty($t["cover_path"]) ? web_address . ltrim( $t["cover_path"], "/" ) : null,
        "hitune_url"   => web_address . "track/" . $t["hash"],
        "released"     => $t["time_release"],
      );
    }
  }

  $loader->developer_api->v1_send( array(
    "chart" => $chart,
    "items" => $items
  ) );
}

?>
