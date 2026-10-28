<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/site_stats  (group: v1_public — no signature required)
 *
 * Public landing-page stats for hitune.in: live catalog counters and a
 * small trending-tracks rail. Cached ~2 min via a cheap setting row so
 * homepage traffic never hits the tables hard.
 */
function endpoint_site_stats( $loader, $excuter, $args ){

  // public read-only stats — allow the hitune.in landing page to fetch cross-origin
  if ( !headers_sent() )
    header( "Access-Control-Allow-Origin: https://hitune.in" );

    $db = $loader->db;

  // short-lived cache (120s) in _bof_setting
  $cached = $db->_select( array(
    "table" => "_bof_setting", "columns" => "val",
    "where" => array( array( "var", "=", "_site_stats_cache" ) ),
    "limit" => 1, "single" => true, "cache_load_rt" => false
  ) );
  if ( $cached && !empty($cached["val"]) ){
    $j = json_decode( $cached["val"], true );
    if ( is_array($j) && !empty($j["t"]) && ( time() - (int)$j["t"] < 120 ) )
      return $loader->api->set_message( "ok", $j["d"] );
  }

  $counts = array();
  foreach ( array( "_c_m_tracks" => "tracks", "_c_m_artists" => "artists", "_c_m_albums" => "albums", "_u_list" => "users" ) as $table => $key ){
    $r = $db->query( "SELECT COUNT(*) AS c FROM `{$table}`" );
    $counts[ $key ] = $r && ($row = $r->fetch_assoc()) ? (int)$row["c"] : 0;
  }
  $r = $db->query( "SELECT COALESCE(SUM(s_plays),0) AS p FROM `_c_m_tracks`" );
  $counts["plays"] = ( $r && ($row = $r->fetch_assoc()) ) ? (int)$row["p"] : 0;

  // trending: most played, include artist + cover for the rail
  $trending = array();
  $r = $db->query( "SELECT t.hash, t.title, t.s_plays, t.spotify_cover AS cover, a.name AS artist
      FROM `_c_m_tracks` t
      LEFT JOIN `_c_m_artists` a ON a.ID = t.artist_id
      ORDER BY t.s_plays DESC, t.s_views DESC
      LIMIT 12" );
  while ( $r && $row = $r->fetch_assoc() ){
    $cover = null;
    if ( !empty($row["cover"]) ){
      $c = trim( (string)$row["cover"] );
      if ( preg_match('/^https?:\/\//i', $c) ) $cover = $c;
    }
    $trending[] = array(
      "hash"   => $row["hash"],
      "title"  => $row["title"],
      "artist" => $row["artist"],
      "plays"  => (int)$row["s_plays"],
      "cover"  => $cover,
      "url"    => web_address . "track/" . $row["hash"],
    );
  }

  $payload = array(
    "stats"    => $counts,
    "trending" => $trending,
    "time"     => time(),
  );

  $db->query( "INSERT INTO `_bof_setting` (`var`,`val`) VALUES ('_site_stats_cache','" . $db->real_escape_string( json_encode( array( "t" => time(), "d" => $payload ) ) ) . "')
      ON DUPLICATE KEY UPDATE `val` = VALUES(`val`)" );

  return $loader->api->set_message( "ok", $payload );

}

?>
