<?php

if ( !defined( "bof_root" ) ) die;

/**
 * Per-play analytics log for the Artist Panel (strategy doc: "Real-time
 * Analytics — which city / country plays my song most").
 *
 * Called from endpoint_muse_record for EVERY play (guests included, unlike
 * `_u_actions` which only stores logged-in listeners and has no geo).
 * Nothing in here may ever throw or slow playback noticeably.
 */

function htx_plays_ensure_table( $db ){
  static $done = false;
  if ( $done ) return;
  $db->query( "CREATE TABLE IF NOT EXISTS `_htx_plays` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `track_id` INT NOT NULL,
    `artist_id` INT NULL,
    `user_id` INT NULL,
    `platform` VARCHAR(10) NOT NULL DEFAULT 'web',
    `cc` CHAR(2) NULL,
    `region` VARCHAR(60) NULL,
    `city` VARCHAR(80) NULL,
    `time_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_artist_time` (`artist_id`,`time_add`),
    KEY `idx_track_time` (`track_id`,`time_add`),
    KEY `idx_cc` (`cc`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
  $db->query( "CREATE TABLE IF NOT EXISTS `_htx_geo_cache` (
    `ip_hash` CHAR(32) NOT NULL,
    `cc` CHAR(2) NULL,
    `region` VARCHAR(60) NULL,
    `city` VARCHAR(80) NULL,
    `time_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ip_hash`),
    KEY `idx_time` (`time_add`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
  $done = true;
}

/** Real visitor IP (site sits behind Cloudflare). */
function htx_client_ip(){
  foreach ( array( "HTTP_CF_CONNECTING_IP", "HTTP_X_REAL_IP", "HTTP_X_FORWARDED_FOR", "REMOTE_ADDR" ) as $k ){
    if ( empty( $_SERVER[$k] ) ) continue;
    $ip = trim( explode( ",", (string)$_SERVER[$k] )[0] );
    if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
  }
  return "";
}

/**
 * [country code, region, city] for the current request.
 * Cloudflare country header first; city/region from CF headers when the
 * "visitor location headers" transform is on, otherwise a cached ip-api
 * lookup (1.5s timeout, 30-day cache keyed by a salted IP hash — the raw IP
 * is never stored in the analytics tables).
 */
function htx_geo( $db ){

  $cc = !empty( $_SERVER["HTTP_CF_IPCOUNTRY"] ) ? strtoupper( substr( (string)$_SERVER["HTTP_CF_IPCOUNTRY"], 0, 2 ) ) : "";
  if ( $cc === "XX" || $cc === "T1" || !preg_match( '/^[A-Z]{2}$/', $cc ) ) $cc = "";
  $city   = !empty( $_SERVER["HTTP_CF_IPCITY"] ) ? mb_substr( (string)$_SERVER["HTTP_CF_IPCITY"], 0, 80 ) : "";
  $region = !empty( $_SERVER["HTTP_CF_REGION"] ) ? mb_substr( (string)$_SERVER["HTTP_CF_REGION"], 0, 60 ) : "";

  if ( $city !== "" && $cc !== "" ) return array( $cc, $region, $city );

  $ip = htx_client_ip();
  if ( !$ip || !filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) )
    return array( $cc, $region, $city );

  $h = md5( $ip . "|htx-geo" );
  $r = $db->query( "SELECT cc, region, city FROM `_htx_geo_cache` WHERE ip_hash = '{$h}'
    AND time_add > DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 1" );
  if ( $r && $r->num_rows ){
    $g = $r->fetch_assoc();
    return array( $g["cc"] ?: $cc, $g["region"] ?: $region, $g["city"] ?: $city );
  }

  $g_cc = $cc; $g_region = $region; $g_city = $city; $ok = false;

  // two keyless providers, first one that answers wins
  $providers = array(
    array( "https://ipwho.is/" . rawurlencode( $ip ) . "?fields=success,country_code,region,city",
      function( $j ){ return ( $j["success"] ?? false ) ? array( $j["country_code"] ?? "", $j["region"] ?? "", $j["city"] ?? "" ) : null; } ),
    array( "http://ip-api.com/json/" . rawurlencode( $ip ) . "?fields=status,countryCode,regionName,city",
      function( $j ){ return ( $j["status"] ?? "" ) === "success" ? array( $j["countryCode"] ?? "", $j["regionName"] ?? "", $j["city"] ?? "" ) : null; } ),
  );
  foreach ( $providers as $p ){
    try {
      $ch = curl_init( $p[0] );
      curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_HTTPHEADER => array( "Accept: application/json" ) ) );
      $raw = curl_exec( $ch );
      curl_close( $ch );
      $j = json_decode( (string)$raw, true );
      $hit = is_array( $j ) ? $p[1]( $j ) : null;
      if ( $hit ){
        $ok = true;
        $g_cc     = $hit[0] !== "" ? strtoupper( substr( (string)$hit[0], 0, 2 ) ) : $cc;
        $g_region = mb_substr( (string)$hit[1], 0, 60 );
        $g_city   = mb_substr( (string)$hit[2], 0, 80 );
        break;
      }
    } catch ( \Throwable $e ) {}
  }

  // failures are cached too, but only for ~10 minutes, so a provider outage
  // can't add latency to every single play yet still self-heals quickly
  $stamp = $ok ? "NOW()" : "DATE_ADD(DATE_SUB(NOW(), INTERVAL 30 DAY), INTERVAL 10 MINUTE)";
  $e_cc = $db->real_escape_string( $g_cc ); $e_rg = $db->real_escape_string( $g_region ); $e_ct = $db->real_escape_string( $g_city );
  $db->query( "REPLACE INTO `_htx_geo_cache` (ip_hash, cc, region, city, time_add)
    VALUES ('{$h}', " . ( $g_cc !== "" ? "'{$e_cc}'" : "NULL" ) . ", " . ( $g_region !== "" ? "'{$e_rg}'" : "NULL" ) . ", "
    . ( $g_city !== "" ? "'{$e_ct}'" : "NULL" ) . ", {$stamp})" );

  return array( $g_cc, $g_region, $g_city );
}

/** Record one play of a catalog track. Safe to call on every play. */
function htx_log_play( $loader, $track, $user_id = null ){
  try {
    $db = $loader->db;
    htx_plays_ensure_table( $db );
    list( $cc, $region, $city ) = htx_geo( $db );
    $tid = (int)$track["ID"];
    $aid = !empty( $track["artist_id"] ) ? (int)$track["artist_id"] : null;
    $uid = $user_id ? (int)$user_id : null;
    $platform = ( isset( $_SERVER["HTTP_X_BOF_PLATFORM"] ) && $_SERVER["HTTP_X_BOF_PLATFORM"] !== "web" ) ? "app" : "web";
    $cc = $cc !== "" ? $cc : null; $region = $region !== "" ? $region : null; $city = $city !== "" ? $city : null;
    $stmt = $db->prepare( "INSERT INTO `_htx_plays` (track_id, artist_id, user_id, platform, cc, region, city) VALUES (?,?,?,?,?,?,?)" );
    if ( !$stmt ) return;
    $stmt->bind_param( "iiissss", $tid, $aid, $uid, $platform, $cc, $region, $city );
    $stmt->execute();
    $stmt->close();
  } catch ( \Throwable $e ) {}
}

?>
