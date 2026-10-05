<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/v1/dist/ecosystem
 *
 * Internal bridge — HiTune Distribution web portal (distribution.hitune.in,
 * the `web` app on this same server) pushes approved releases straight into
 * the HiTune Music catalog + IyolMe sound registry (strategy doc: "1-Click
 * Direct Ecosystem Publish").
 *
 * Auth: shared-secret HMAC (same convention as the IyolMe contract):
 *   X-HT-Client:    hitune_dist_portal
 *   X-HT-Timestamp: unix seconds (±300s)
 *   X-HT-Signature: hex hmac_sha256( "{ts}.{raw_body}", dist_bridge_secret )
 *
 * Body JSON:
 *   action        publish | takedown
 *   web_release_id  int            web.releases.id (idempotency key)
 *   release       object           required for publish:
 *     title, artist_name, type(single|album|ep), album_name, genre, language,
 *     release_date, isrc, upc, label_name, description,
 *     ai_pct (0-100), ai_tools, ai_declared,
 *     cover_path    uploads/...    relative to the web webroot
 *     user_email    string         resolves the music-side account
 *     tracks[]      {title, artist_name, isrc, duration(sec), audio_path, ai_pct}
 *
 * Response 200: { ok:true, submission_id, status, track_ids:[], track_urls:[] }
 */
function endpoint_dist_ecosystem( $loader, $excuter, $args ){

  $send = function( $data, $http=200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
      header( "Cache-Control: no-store" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES );
    exit;
  };

  if ( $_SERVER["REQUEST_METHOD"] !== "POST" )
    $send( array( "ok" => false, "error" => "post_required" ), 405 );

  $secret = (string) bof()->object->db_setting->get( "dist_bridge_secret" );
  if ( !$secret )
    $send( array( "ok" => false, "error" => "bridge_disabled" ), 503 );

  // ---- signature + replay checks ------------------------------------------
  $headers = array_change_key_case( (array) getallheaders(), CASE_LOWER );
  $sig = isset( $headers["x-ht-signature"] ) ? trim( $headers["x-ht-signature"] ) : "";
  $ts  = isset( $headers["x-ht-timestamp"] ) ? (int) $headers["x-ht-timestamp"] : 0;
  $raw = file_get_contents( "php://input" );

  if ( !$sig || !$ts || abs( time() - $ts ) > 300 )
    $send( array( "ok" => false, "error" => "invalid_signature" ), 401 );

  $expect = hash_hmac( "sha256", "{$ts}.{$raw}", $secret );
  if ( !hash_equals( $expect, $sig ) )
    $send( array( "ok" => false, "error" => "invalid_signature" ), 401 );

  $body = json_decode( (string)$raw, true );
  if ( !is_array($body) )
    $send( array( "ok" => false, "error" => "invalid_json" ), 400 );

  $db      = $loader->db;
  $action  = !empty( $body["action"] ) ? $body["action"] : "publish";
  $rel_id  = !empty( $body["web_release_id"] ) ? (int)$body["web_release_id"] : 0;
  if ( !$rel_id )
    $send( array( "ok" => false, "error" => "missing_web_release_id" ), 400 );

  // ---- resolve existing bridge row ----------------------------------------
  $r = $db->query( "SELECT id, status, catalog_published, catalog_track_ids FROM `_dist_submissions`
    WHERE web_release_id = {$rel_id} LIMIT 1" );
  $existing = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;

  if ( $action === "takedown" ){
    if ( !$existing )
      $send( array( "ok" => true, "note" => "nothing_published" ) );

    $sid = (int)$existing["id"];
    $tids = array_filter( array_map( "intval", (array) json_decode( (string)$existing["catalog_track_ids"], true ) ) );
    // queue takedowns BEFORE unpublish — the payload needs the live track hash
    try { foreach ( $tids as $_tid ) bof()->iyolme->queue_takedown( (int)$_tid ); } catch ( \Throwable $e ) {}
    if ( !empty( $existing["catalog_published"] ) && function_exists("dist_unpublish_catalog") )
      dist_unpublish_catalog( $db, $sid );
    $db->query( "UPDATE `_dist_submissions` SET status = 'taken_down' WHERE id = {$sid}" );
    if ( function_exists("dist_admin_log") )
      dist_admin_log( $db, null, $sid, 'ecosystem_takedown', $existing["status"], 'taken_down', "Bridge takedown for web release #{$rel_id}" );
    $send( array( "ok" => true, "submission_id" => $sid, "status" => "taken_down", "removed_tracks" => count($tids) ) );
  }

  if ( $action !== "publish" )
    $send( array( "ok" => false, "error" => "unknown_action" ), 400 );

  // ---- publish path --------------------------------------------------------
  if ( $existing ){
    if ( !empty($existing["catalog_published"]) ){
      $tids = array_filter( array_map( "intval", (array) json_decode( (string)$existing["catalog_track_ids"], true ) ) );
      $send( array( "ok" => true, "submission_id" => (int)$existing["id"], "status" => $existing["status"],
        "track_ids" => $tids, "track_urls" => dist_eco_track_urls( $db, $tids ), "dedup" => true ) );
    }
    // exists but not published (earlier partial failure) — retry publish only
    $sid = (int)$existing["id"];
    $pub = dist_publish_to_catalog( $db, $sid );
    if ( !empty($pub["error"]) )
      $send( array( "ok" => false, "error" => "publish_failed", "detail" => $pub["error"], "why" => $pub["detail"] ?? null, "submission_id" => $sid ), 500 );
    $db->query( "UPDATE `_dist_submissions` SET status = 'launched', launch_date = CURDATE() WHERE id = {$sid}" );
    $send( array( "ok" => true, "submission_id" => $sid, "status" => "launched",
      "track_ids" => $pub["track_ids"], "track_urls" => dist_eco_track_urls( $db, $pub["track_ids"] ) ) );
  }

  $rel = !empty( $body["release"] ) && is_array($body["release"]) ? $body["release"] : array();
  if ( empty($rel["title"]) || empty($rel["artist_name"]) || empty($rel["tracks"]) || !is_array($rel["tracks"]) )
    $send( array( "ok" => false, "error" => "invalid_release", "hint" => "title, artist_name, tracks[] required" ), 400 );

  // music-side account: web sends the user's email (sso_sync mirrors web.users
  // into _u_list), fall back to a system account so publishes never block
  $user_id = 0;
  if ( !empty($rel["user_email"]) ){
    $em = $db->real_escape_string( trim( $rel["user_email"] ) );
    $r2 = $db->query( "SELECT ID FROM `_u_list` WHERE email = '{$em}' LIMIT 1" );
    if ( $r2 && $r2->num_rows ) $user_id = (int)$r2->fetch_assoc()["ID"];
  }
  if ( !$user_id && !empty($rel["music_user_id"]) )
    $user_id = (int)$rel["music_user_id"];
  if ( !$user_id ){
    $r2 = $db->query( "SELECT ID FROM `_u_list` ORDER BY ID ASC LIMIT 1" );
    $user_id = ( $r2 && $r2->num_rows ) ? (int)$r2->fetch_assoc()["ID"] : 0;
  }
  if ( !$user_id )
    $send( array( "ok" => false, "error" => "no_user" ), 400 );

  // get-or-create a bridge subscription row (releases via the web portal are
  // already paid/entitled on that side — this row just satisfies the schema)
  $subscription_id = 0;
  $r2 = $db->query( "SELECT id FROM `_dist_subscriptions` WHERE user_id = {$user_id}
    AND status = 'active' ORDER BY id ASC LIMIT 1" );
  if ( $r2 && $r2->num_rows ){
    $subscription_id = (int)$r2->fetch_assoc()["id"];
  } else {
    $db->query( "INSERT INTO `_dist_subscriptions` (user_id, plan_id, status, payment_status, amount_paid, start_date, end_date, transaction_id)
      VALUES ({$user_id}, 1, 'active', 'paid', 0.00, CURDATE(), '2099-12-31', 'web-bridge')" );
    $subscription_id = (int)$db->insert_id;
  }
  if ( !$subscription_id )
    $send( array( "ok" => false, "error" => "subscription_failed" ), 500 );

  // copy media files out of the web webroot into music's dist storage —
  // only paths under /www/wwwroot/web/uploads/ are accepted
  $web_root  = "/www/wwwroot/web/";
  $dest_root = base_root . "/files/dist/bridge/" . date("Y/m") . "/";
  $copy = function( $rel_path ) use ( $web_root, $dest_root, $user_id ){

    $rel_path = ltrim( (string)$rel_path, "/" );
    if ( !$rel_path || strpos( $rel_path, ".." ) !== false ) return null;
    $src = realpath( $web_root . $rel_path );
    if ( !$src || strpos( $src, realpath($web_root) ) !== 0 || !is_file($src) ) return null;

    if ( !is_dir($dest_root) ) mkdir( $dest_root, 0755, true );
    $ext  = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
    $name = "w{$user_id}_" . time() . "_" . substr( md5($src . mt_rand()), 0, 8 ) . "." . $ext;
    $dst  = $dest_root . $name;
    if ( !@copy( $src, $dst ) ) return null;
    return "files/dist/bridge/" . date("Y/m") . "/" . $name;
  };

  $cover_rel = !empty($rel["cover_path"]) ? $copy( $rel["cover_path"] ) : null;

  $type    = in_array( $rel["type"] ?? "", array("single","album","ep"), true ) ? $rel["type"] : "single";
  $title   = $db->real_escape_string( $rel["title"] );
  $artist  = $db->real_escape_string( $rel["artist_name"] );
  $ai_pct  = max( 0, min( 100, (int)( $rel["ai_pct"] ?? 0 ) ) );
  $ai_tool = !empty($rel["ai_tools"]) ? $db->real_escape_string( substr($rel["ai_tools"],0,255) ) : null;
  $ai_decl = !empty($rel["ai_declared"]) ? 1 : 0;
  $album_n = !empty($rel["album_name"]) ? "'".$db->real_escape_string($rel["album_name"])."'" : "NULL";
  $genre   = !empty($rel["genre"]) ? "'".$db->real_escape_string($rel["genre"])."'" : "NULL";
  $lang    = !empty($rel["language"]) ? "'".$db->real_escape_string($rel["language"])."'" : "NULL";
  $descr   = !empty($rel["description"]) ? "'".$db->real_escape_string($rel["description"])."'" : "NULL";
  $isrc    = !empty($rel["isrc"]) ? "'".$db->real_escape_string($rel["isrc"])."'" : "NULL";
  $upc     = !empty($rel["upc"]) ? "'".$db->real_escape_string($rel["upc"])."'" : "NULL";
  $label   = !empty($rel["label_name"]) ? "'".$db->real_escape_string($rel["label_name"])."'" : "NULL";
  $rdate   = !empty($rel["release_date"]) && preg_match("/^\d{4}-\d{2}-\d{2}$/",$rel["release_date"])
             ? "'".$db->real_escape_string($rel["release_date"])."'" : "NULL";
  $cover_s = $cover_rel ? "'".$db->real_escape_string($cover_rel)."'" : "NULL";
  $platforms = $db->real_escape_string( json_encode( array("HiTune Music","IyolMe") ) );

  $db->query( "INSERT INTO `_dist_submissions`
    (user_id, subscription_id, type, title, artist_name, album_name, genre, release_date,
     description, language, isrc, upc, label_name, cover_art_path, platforms, countries,
     status, launch_date, ai_pct, ai_tools, ai_declared, source, web_release_id)
    VALUES ({$user_id}, {$subscription_id}, '{$type}', '{$title}', '{$artist}', {$album_n}, {$genre}, {$rdate},
     {$descr}, {$lang}, {$isrc}, {$upc}, {$label}, {$cover_s}, '{$platforms}', '[\"all\"]',
     'launched', CURDATE(), {$ai_pct}, " . ( $ai_tool ? "'{$ai_tool}'" : "NULL" ) . ", {$ai_decl}, 'web_portal', {$rel_id})" );

  $sid = (int)$db->insert_id;
  if ( !$sid )
    $send( array( "ok" => false, "error" => "submission_insert_failed" ), 500 );

  // track rows
  $tn = 1;
  foreach ( $rel["tracks"] as $t ){
    if ( empty($t["title"]) || empty($t["audio_path"]) ) { $tn++; continue; }
    $audio_rel = $copy( $t["audio_path"] );
    if ( !$audio_rel ) { $tn++; continue; }
    $tt  = $db->real_escape_string( $t["title"] );
    $ta  = $db->real_escape_string( !empty($t["artist_name"]) ? $t["artist_name"] : $rel["artist_name"] );
    $ti  = !empty($t["isrc"]) ? "'".$db->real_escape_string($t["isrc"])."'" : "NULL";
    $td  = !empty($t["duration"]) ? (int)$t["duration"] : "NULL";
    $tai = isset($t["ai_pct"]) ? max(0,min(100,(int)$t["ai_pct"])) : $ai_pct;
    $tfp = function_exists("dist_track_fingerprint") ? dist_track_fingerprint( $audio_rel ) : null;
    $db->query( "INSERT INTO `_dist_tracks` (submission_id, track_number, title, artist_name, duration, isrc, audio_file_path, ai_pct, fingerprint)
      VALUES ({$sid}, {$tn}, '{$tt}', '{$ta}', {$td}, {$ti}, '".$db->real_escape_string($audio_rel)."', {$tai}, " . ( $tfp ? "'{$tfp}'" : "NULL" ) . ")" );
    $tn++;
  }

  // §3 — surface duplicate audio to the review queue (note only; the admin
  // already chose to publish this release from the distribution portal)
  if ( function_exists("dist_flag_duplicate_fingerprint") ){
    $r0 = $db->query( "SELECT t.fingerprint FROM `_dist_tracks` t
      JOIN `_dist_tracks` o ON o.fingerprint = t.fingerprint AND o.submission_id != t.submission_id
      WHERE t.submission_id = {$sid} AND t.fingerprint IS NOT NULL AND t.fingerprint != '' LIMIT 1" );
    if ( $r0 && $r0->num_rows )
      $db->query( "UPDATE `_dist_submissions` SET admin_notes = CONCAT(COALESCE(admin_notes,''), '\n[review] duplicate audio fingerprint detected') WHERE id = {$sid}" );
  }
  // §3 policy notes (note-only — admin already approved on the portal side)
  if ( function_exists("dist_flag_ai_risk") ) dist_flag_ai_risk( $db, $sid, true );

  // publish to the streaming catalog (queues the IyolMe sound sync itself)
  $pub = dist_publish_to_catalog( $db, $sid );
  if ( !empty($pub["error"]) && $pub["error"] !== "already_published" ){
    if ( function_exists("dist_admin_log") )
      dist_admin_log( $db, null, $sid, 'ecosystem_publish_failed', null, null, "Bridge publish failed: {$pub["error"]} " . ($pub["detail"] ?? "") );
    $send( array( "ok" => false, "error" => "publish_failed", "detail" => $pub["error"], "why" => $pub["detail"] ?? null, "submission_id" => $sid ), 500 );
  }

  if ( function_exists("dist_admin_log") )
    dist_admin_log( $db, null, $sid, 'ecosystem_publish', null, 'launched',
      "Bridge publish for web release #{$rel_id}: " . count($pub["track_ids"]) . " track(s)" );

  $send( array(
    "ok"            => true,
    "submission_id" => $sid,
    "status"        => "launched",
    "track_ids"     => $pub["track_ids"],
    "track_urls"    => dist_eco_track_urls( $db, $pub["track_ids"] )
  ) );

}

function dist_eco_track_urls( $db, $track_ids ){
  $urls = array();
  $in = implode( ",", array_map( "intval", (array)$track_ids ) );
  if ( !$in ) return $urls;
  $r = $db->query( "SELECT hash FROM `_c_m_tracks` WHERE ID IN ({$in})" );
  while ( $r && ($t = $r->fetch_assoc()) )
    $urls[] = web_address . "track/" . $t["hash"];
  return $urls;
}

?>
