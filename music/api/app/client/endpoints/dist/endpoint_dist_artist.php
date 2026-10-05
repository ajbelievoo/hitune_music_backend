<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname( __FILE__ ) . "/play_log.php" );

/**
 * POST /api/v1/dist/artist
 *
 * Artist Panel bridge — the HiTune Distribution portal (distribution.hitune.in)
 * is where artists verify themselves and read their analytics; the data lives
 * here on the HiTune Music side.
 *
 * Auth: same shared-secret HMAC as /v1/dist/ecosystem
 *   X-HT-Client / X-HT-Timestamp (±300s) / X-HT-Signature =
 *   hex hmac_sha256("{ts}.{raw_body}", dist_bridge_secret)
 *
 * Body JSON: { action, email, ... }
 *   overview        email, stage_names[], days(7|30|90)
 *                   -> managed artists + analytics, candidates, pending requests
 *   verify_request  email, stage_name, real_name, note
 *                   -> creates a native `_u_requests` (m_artist) row
 *   verify_approve  request_id, force(0|1)   -> native user_request->_approve
 *   verify_reject   request_id               -> native user_request->_reject
 */
function endpoint_dist_artist( $loader, $excuter, $args ){

  $send = function( $data, $http = 200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
      header( "Cache-Control: no-store" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    exit;
  };

  if ( $_SERVER["REQUEST_METHOD"] !== "POST" )
    $send( array( "ok" => false, "error" => "post_required" ), 405 );

  $secret = (string) bof()->object->db_setting->get( "dist_bridge_secret" );
  if ( !$secret )
    $send( array( "ok" => false, "error" => "bridge_disabled" ), 503 );

  $headers = array_change_key_case( (array) getallheaders(), CASE_LOWER );
  $sig = isset( $headers["x-ht-signature"] ) ? trim( $headers["x-ht-signature"] ) : "";
  $ts  = isset( $headers["x-ht-timestamp"] ) ? (int) $headers["x-ht-timestamp"] : 0;
  $raw = file_get_contents( "php://input" );

  if ( !$sig || !$ts || abs( time() - $ts ) > 300 )
    $send( array( "ok" => false, "error" => "invalid_signature" ), 401 );
  if ( !hash_equals( hash_hmac( "sha256", "{$ts}.{$raw}", $secret ), $sig ) )
    $send( array( "ok" => false, "error" => "invalid_signature" ), 401 );

  $body = json_decode( (string)$raw, true );
  if ( !is_array( $body ) )
    $send( array( "ok" => false, "error" => "invalid_json" ), 400 );

  $db     = $loader->db;
  $action = !empty( $body["action"] ) ? (string)$body["action"] : "overview";
  htx_plays_ensure_table( $db );

  // ---- native request review (no user lookup needed) -----------------------
  if ( $action === "verify_approve" || $action === "verify_reject" || $action === "verify_revoke" ){

    $rid = (int)( $body["request_id"] ?? 0 );
    $req = $rid ? $db->query( "SELECT ID, user_id, type, sta, extra_data FROM `_u_requests` WHERE ID = {$rid} LIMIT 1" ) : null;
    $row = ( $req && $req->num_rows ) ? $req->fetch_assoc() : null;
    if ( !$row || $row["type"] !== "m_artist" )
      $send( array( "ok" => false, "error" => "request_not_found" ), 404 );

    if ( $action === "verify_reject" ){
      bof()->object->user_request->_reject( $rid );
      $send( array( "ok" => true, "status" => "rejected", "request_id" => $rid ) );
    }

    if ( $action === "verify_revoke" ){
      // take the verified badge away: release the artist page from this manager
      $ruid  = (int)$row["user_id"];
      $extra = json_decode( (string)$row["extra_data"], true );
      $owner = dist_art_find_artist( $db, trim( (string)( $extra["stage_name"]["data"] ?? "" ) ) );
      if ( $owner && (int)$owner["manager_id"] === $ruid )
        $db->query( "UPDATE `_c_m_artists` SET manager_id = NULL WHERE ID = " . (int)$owner["ID"] );
      $left = $db->query( "SELECT COUNT(*) c FROM `_c_m_artists` WHERE manager_id = {$ruid}" );
      $left = ( $left && $left->num_rows ) ? (int)$left->fetch_assoc()["c"] : 0;
      $db->query( "UPDATE `_u_list` SET s_managed_artists = " . ( $left > 0 ? 1 : 0 ) . " WHERE ID = {$ruid}" );
      $db->query( "UPDATE `_u_requests` SET sta = -1, time_review = NOW() WHERE ID = {$rid}" );
      $send( array( "ok" => true, "status" => "revoked", "request_id" => $rid, "still_managing" => $left ) );
    }

    // never silently take over an artist that somebody else already manages
    $extra  = json_decode( (string)$row["extra_data"], true );
    $stage  = trim( (string)( $extra["stage_name"]["data"] ?? "" ) );
    $owner  = dist_art_find_artist( $db, $stage );
    if ( $owner && (int)$owner["manager_id"] > 0 && (int)$owner["manager_id"] !== (int)$row["user_id"] && empty( $body["force"] ) )
      $send( array( "ok" => false, "error" => "artist_managed_by_other", "artist" => $owner["name"],
        "hint" => "pass force=1 to transfer management" ), 409 );

    bof()->object->user_request->_approve( $rid );
    $owner = dist_art_find_artist( $db, $stage );
    $send( array( "ok" => true, "status" => "approved", "request_id" => $rid,
      "artist" => $owner ? dist_art_public( $owner ) : null ) );
  }

  // ---- everything else is scoped to one music account ----------------------
  $email = trim( (string)( $body["email"] ?? "" ) );
  if ( $email === "" )
    $send( array( "ok" => false, "error" => "missing_email" ), 400 );

  $user = dist_art_user( $db, $email );
  if ( !$user )
    $send( array( "ok" => true, "linked" => false, "artists" => array(), "candidates" => array(),
      "pending_requests" => array(), "hint" => "no HiTune Music account for this email yet" ) );
  $uid = (int)$user["ID"];

  if ( $action === "verify_request" ){

    $stage = trim( (string)( $body["stage_name"] ?? "" ) );
    $real  = trim( (string)( $body["real_name"] ?? "" ) );
    if ( $stage === "" || $real === "" )
      $send( array( "ok" => false, "error" => "stage_name_and_real_name_required" ), 400 );

    $artist = dist_art_find_artist( $db, $stage );
    if ( $artist && (int)$artist["manager_id"] === $uid )
      $send( array( "ok" => true, "status" => "already_verified", "artist" => dist_art_public( $artist ) ) );
    if ( $artist && (int)$artist["manager_id"] > 0 )
      $send( array( "ok" => false, "error" => "artist_managed_by_other",
        "hint" => "This artist name is already managed by another account. Contact support with proof of identity." ), 409 );

    // one open request per user + artist name
    $pend = $db->query( "SELECT ID, extra_data FROM `_u_requests` WHERE user_id = {$uid} AND type = 'm_artist' AND sta = 0" );
    while ( $pend && ( $p = $pend->fetch_assoc() ) ){
      $pe = json_decode( (string)$p["extra_data"], true );
      if ( dist_art_code( (string)( $pe["stage_name"]["data"] ?? "" ) ) === dist_art_code( $stage ) )
        $send( array( "ok" => true, "status" => "pending", "request_id" => (int)$p["ID"], "dedup" => true ) );
    }

    $urid = bof()->object->user_request->insert( array(
      "type"            => "m_artist",
      "user_id"         => $uid,
      "real_name"       => mb_substr( $real, 0, 150 ),
      "extra_data"      => json_encode( array( "stage_name" => array( "type" => "text", "data" => $stage ) ), JSON_UNESCAPED_UNICODE ),
      "additional_data" => mb_substr( (string)( $body["note"] ?? "" ), 0, 2000 ),
    ) );
    try {
      bof()->chapar->notify_admin( "man_verify_requested", array(
        "type" => "m_artist", "real_name" => $real, "additional_data" => "Via Distribution Artist Panel — stage name: {$stage}"
      ) );
    } catch ( \Throwable $e ) {}

    $send( array( "ok" => true, "status" => "pending", "request_id" => (int)$urid ) );
  }

  if ( $action !== "overview" && $action !== "analytics" )
    $send( array( "ok" => false, "error" => "unknown_action" ), 400 );

  // ---- overview / analytics -------------------------------------------------
  $days = (int)( $body["days"] ?? 30 );
  if ( !in_array( $days, array( 7, 30, 90 ), true ) ) $days = 30;

  $artists = array();
  $seen    = array();
  $r = $db->query( "SELECT * FROM `_c_m_artists` WHERE manager_id = {$uid} ORDER BY s_views DESC, ID ASC LIMIT 25" );
  while ( $r && ( $a = $r->fetch_assoc() ) ){
    $seen[(int)$a["ID"]] = true;
    $artists[] = dist_art_payload( $db, $a, $days );
  }

  // stage names the web portal knows about that are not managed by this user
  $candidates = array();
  foreach ( (array)( $body["stage_names"] ?? array() ) as $sn ){
    $sn = trim( (string)$sn );
    if ( $sn === "" ) continue;
    $a = dist_art_find_artist( $db, $sn );
    if ( $a && isset( $seen[(int)$a["ID"]] ) ) continue;
    $candidates[] = array(
      "name"   => $sn,
      "status" => !$a ? "not_in_catalog" : ( (int)$a["manager_id"] > 0 ? "managed_by_other" : "claimable" ),
      "url"    => $a ? web_address . "artist/" . $a["hash"] : null,
    );
  }

  $pending = array();
  $r = $db->query( "SELECT ID, real_name, extra_data, sta, time_add FROM `_u_requests`
    WHERE user_id = {$uid} AND type = 'm_artist' ORDER BY ID DESC LIMIT 10" );
  while ( $r && ( $q = $r->fetch_assoc() ) ){
    $qe = json_decode( (string)$q["extra_data"], true );
    $pending[] = array(
      "id"         => (int)$q["ID"],
      "stage_name" => (string)( $qe["stage_name"]["data"] ?? "" ),
      "status"     => (int)$q["sta"] === 1 ? "approved" : ( (int)$q["sta"] === -1 ? "rejected" : "pending" ),
      "time_add"   => $q["time_add"],
    );
  }

  // growth programme state (doc §8): Creator Day window + live contests
  $until = (string) bof()->object->db_setting->get( "tip_event_until" );
  $pct   = (int) bof()->object->db_setting->get( "tip_artist_pct" );
  $events = array(
    "tip_artist_pct"     => $pct > 0 ? $pct : 80,
    "tip_event_until"    => $until !== "" ? $until : null,
    "creator_day_active" => $until !== "" && strtotime( $until ) > time(),
  );
  $contests = array();
  $r = $db->query( "SELECT slug, title, chart, prize, ends_at FROM `_htx_contests`
    WHERE status = 'active' AND ( ends_at IS NULL OR ends_at > NOW() ) ORDER BY id DESC LIMIT 5" );
  while ( $r && ( $c = $r->fetch_assoc() ) ) $contests[] = $c;

  $send( array(
    "ok"               => true,
    "linked"           => true,
    "music_user"       => array( "id" => $uid, "username" => $user["username"], "name" => $user["name"] ),
    "days"             => $days,
    "artists"          => $artists,
    "candidates"       => $candidates,
    "pending_requests" => $pending,
    "events"           => $events,
    "contests"         => $contests,
    "generated_at"     => gmdate( "c" ),
  ) );
}

// ----------------------------------------------------------------------------

if ( !function_exists( "dist_art_code" ) ){

  function dist_art_code( $name ){
    try { return (string) bof()->general->make_code( $name ); }
    catch ( \Throwable $e ) { return strtolower( preg_replace( '/[^\p{L}0-9]+/u', '', (string)$name ) ); }
  }

  function dist_art_user( $db, $email ){
    $em = $db->real_escape_string( $email );
    $r = $db->query( "SELECT ID, username, name, email FROM `_u_list` WHERE email = '{$em}' LIMIT 1" );
    return ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
  }

  /** Catalog artist by stage name (BOF code, dist slug or exact name). */
  function dist_art_find_artist( $db, $name ){
    $name = trim( (string)$name );
    if ( $name === "" ) return null;
    $codes = array_unique( array_filter( array( dist_art_code( $name ), preg_replace( '/[^a-z0-9]+/', '', strtolower( $name ) ) ) ) );
    $conds = array( "name = '" . $db->real_escape_string( $name ) . "'" );
    foreach ( $codes as $c ) $conds[] = "code = '" . $db->real_escape_string( $c ) . "'";
    $r = $db->query( "SELECT * FROM `_c_m_artists` WHERE " . implode( " OR ", $conds ) . " ORDER BY ( manager_id > 0 ) DESC LIMIT 1" );
    return ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
  }

  function dist_art_public( $a ){
    return array(
      "id"        => (int)$a["ID"],
      "hash"      => $a["hash"],
      "name"      => $a["name"],
      "url"       => web_address . "artist/" . $a["hash"],
      "verified"  => (int)$a["manager_id"] > 0,
      "followers" => (int)( $a["s_subscribers"] ?? 0 ),
    );
  }

  function dist_art_country_name( $cc ){
    if ( !$cc ) return "Unknown";
    if ( class_exists( "Locale" ) ){
      $n = Locale::getDisplayRegion( "-" . $cc, "en" );
      if ( $n && $n !== $cc && $n !== "Unknown Region" ) return $n;
    }
    return $cc;
  }

  /** Full analytics block for one managed artist. */
  function dist_art_payload( $db, $a, $days ){

    $aid = (int)$a["ID"];
    $out = dist_art_public( $a );
    $out["tracks"]    = (int)( $a["s_tracks"] ?? 0 );
    $out["albums"]    = (int)( $a["s_albums"] ?? 0 );
    $out["days"]      = $days;

    // per-track totals (s_* counters are BOF's lifetime numbers, guests included)
    $track_ids = array(); $top = array();
    $totals = array( "plays" => 0, "unique" => 0, "likes" => 0, "downloads" => 0, "shares" => 0,
      "comments" => 0, "playlists" => 0, "ai_tracks" => 0 );
    $r = $db->query( "SELECT ID, hash, title, s_plays, s_plays_unique, s_likes, s_downloads, s_shares, s_comments,
        s_playlists, ai_pct, time_add
      FROM `_c_m_tracks` WHERE artist_id = {$aid} ORDER BY s_plays DESC, ID DESC LIMIT 500" );
    while ( $r && ( $t = $r->fetch_assoc() ) ){
      $track_ids[] = (int)$t["ID"];
      $totals["plays"]     += (int)$t["s_plays"];
      $totals["unique"]    += (int)$t["s_plays_unique"];
      $totals["likes"]     += (int)$t["s_likes"];
      $totals["downloads"] += (int)$t["s_downloads"];
      $totals["shares"]    += (int)$t["s_shares"];
      $totals["comments"]  += (int)$t["s_comments"];
      $totals["playlists"] += (int)$t["s_playlists"];
      if ( (int)$t["ai_pct"] > 0 ) $totals["ai_tracks"]++;
      if ( count( $top ) < 10 ) $top[] = array(
        "id"    => (int)$t["ID"], "title" => $t["title"], "plays" => (int)$t["s_plays"], "likes" => (int)$t["s_likes"],
        "ai_pct" => (int)$t["ai_pct"], "url" => web_address . "track/" . $t["hash"],
      );
    }
    $out["tracks"] = max( $out["tracks"], count( $track_ids ) );
    $out["top_tracks"] = $top;
    $in = $track_ids ? implode( ",", $track_ids ) : "0";

    // plays inside the window (per-play log started when this feature shipped;
    // older logged-in listens come from _u_actions so charts are never empty
    // for an active artist)
    $t0 = null;
    $r = $db->query( "SELECT MIN(time_add) m FROM `_htx_plays`" );
    if ( $r && $r->num_rows ) $t0 = $r->fetch_assoc()["m"];
    $since = "DATE_SUB(CURDATE(), INTERVAL " . ( $days - 1 ) . " DAY)";

    $byday = array();
    $r = $db->query( "SELECT DATE(time_add) d, COUNT(*) c FROM `_htx_plays`
      WHERE artist_id = {$aid} AND time_add >= {$since} GROUP BY d" );
    while ( $r && ( $x = $r->fetch_assoc() ) ) $byday[$x["d"]] = (int)$x["c"];

    if ( $track_ids ){
      $cut = $t0 ? " AND time_add < '" . $db->real_escape_string( $t0 ) . "'" : "";
      $r = $db->query( "SELECT DATE(time_add) d, COUNT(*) c FROM `_u_actions`
        WHERE type = 'stream' AND object_name = 'm_track' AND object_id IN ({$in})
        AND time_add >= {$since}{$cut} GROUP BY d" );
      while ( $r && ( $x = $r->fetch_assoc() ) ) $byday[$x["d"]] = ( $byday[$x["d"]] ?? 0 ) + (int)$x["c"];
    }

    $labels = array(); $values = array(); $window_plays = 0;
    for ( $i = $days - 1; $i >= 0; $i-- ){
      $d = date( "Y-m-d", strtotime( "-{$i} day" ) );
      $labels[] = $d;
      $v = $byday[$d] ?? 0;
      $values[] = $v;
      $window_plays += $v;
    }
    $out["series"] = array( "days" => $days, "labels" => $labels, "values" => $values );
    $totals["window_plays"] = $window_plays;

    // unique listeners in window (logged-in only — guests have no identity)
    $r = $db->query( "SELECT COUNT(DISTINCT user_id) c FROM `_htx_plays`
      WHERE artist_id = {$aid} AND user_id IS NOT NULL AND time_add >= {$since}" );
    $totals["listeners_window"] = ( $r && $r->num_rows ) ? (int)$r->fetch_assoc()["c"] : 0;

    // geo (only plays that have a location)
    $countries = array(); $cities = array(); $geo_total = 0;
    $r = $db->query( "SELECT cc, COUNT(*) c FROM `_htx_plays`
      WHERE artist_id = {$aid} AND cc IS NOT NULL AND time_add >= {$since}
      GROUP BY cc ORDER BY c DESC LIMIT 10" );
    while ( $r && ( $x = $r->fetch_assoc() ) ){ $geo_total += (int)$x["c"]; $countries[] = $x; }
    $r = $db->query( "SELECT city, cc, COUNT(*) c FROM `_htx_plays`
      WHERE artist_id = {$aid} AND city IS NOT NULL AND time_add >= {$since}
      GROUP BY city, cc ORDER BY c DESC LIMIT 10" );
    while ( $r && ( $x = $r->fetch_assoc() ) ) $cities[] = $x;
    $r = $db->query( "SELECT COUNT(*) c FROM `_htx_plays` WHERE artist_id = {$aid} AND cc IS NOT NULL AND time_add >= {$since}" );
    $geo_all = ( $r && $r->num_rows ) ? (int)$r->fetch_assoc()["c"] : 0;
    $out["geo"] = array(
      "available" => $geo_all > 0,
      "located_plays" => $geo_all,
      "countries" => array_map( function( $x ) use ( $geo_all ){
        return array( "cc" => $x["cc"], "name" => dist_art_country_name( $x["cc"] ), "plays" => (int)$x["c"],
          "pct" => $geo_all ? round( 100 * (int)$x["c"] / $geo_all, 1 ) : 0 );
      }, $countries ),
      "cities" => array_map( function( $x ) use ( $geo_all ){
        return array( "city" => $x["city"], "cc" => $x["cc"], "country" => dist_art_country_name( $x["cc"] ), "plays" => (int)$x["c"],
          "pct" => $geo_all ? round( 100 * (int)$x["c"] / $geo_all, 1 ) : 0 );
      }, $cities ),
    );

    // platform split (web vs app)
    $out["platforms"] = array();
    $r = $db->query( "SELECT platform, COUNT(*) c FROM `_htx_plays` WHERE artist_id = {$aid} AND time_add >= {$since} GROUP BY platform" );
    while ( $r && ( $x = $r->fetch_assoc() ) ) $out["platforms"][$x["platform"]] = (int)$x["c"];

    // fan tips (doc §4 direct fan-to-artist monetisation)
    $tips_n = 0; $tips_sum = 0.0;
    if ( $track_ids ){
      $r = $db->query( "SELECT COUNT(*) n, COALESCE(SUM(artist_amount),0) s FROM `_htx_tips` WHERE track_id IN ({$in})" );
      if ( $r && $r->num_rows ){ $x = $r->fetch_assoc(); $tips_n = (int)$x["n"]; $tips_sum = (float)$x["s"]; }
    }
    $totals["tips_count"]  = $tips_n;
    $totals["tips_amount"] = round( $tips_sum, 2 );

    // chart position of the artist's best track (lifetime plays)
    $out["rank"] = null;
    if ( $top ){
      $best = (int)$top[0]["plays"];
      $r = $db->query( "SELECT COUNT(*) + 1 c FROM `_c_m_tracks` WHERE s_plays > {$best}" );
      if ( $r && $r->num_rows ) $out["rank"] = array( "track" => $top[0]["title"], "position" => (int)$r->fetch_assoc()["c"] );
    }

    $out["totals"] = $totals;
    return $out;
  }

}

?>
