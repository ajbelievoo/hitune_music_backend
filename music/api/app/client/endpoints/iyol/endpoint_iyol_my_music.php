<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/iyol/my_music?sub=u:{user_hash}   (or ?uid={id})
 * Returns the requesting HiTune user's own sounds for IyolMe's
 * "My Music" reel picker: AI Studio song_gen outputs + distribution
 * releases (catalog-published tracks use their real track hash so
 * they resolve to the existing HITUNE: sound; unpublished dist audio
 * and AI songs carry a direct audio_url + pseudo hash).
 * Bearer JWT (client_credentials) required.
 */
function endpoint_iyol_my_music( $loader, $excuter, $args ){

  $send = function( $data, $http=200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
      header( "Cache-Control: no-store" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES );
    exit;
  };

  $iyol = bof()->iyolme;
  if ( !$iyol->enabled() )
    $send( array( "error" => array( "code" => "disabled", "message" => "IyolMe integration is disabled" ) ), 503 );

  $auth = $iyol->verify_bearer();
  if ( !$auth )
    $send( array( "error" => array( "code" => "invalid_token", "message" => "Bearer token required — POST /api/v1/oauth/token with client_credentials" ) ), 401 );

  // resolve the HiTune user
  $uid = (int) $loader->nest->user_input( "get", "uid", "int" );
  $sub = $loader->nest->user_input( "get", "sub", "string" );
  if ( !$uid && $sub && preg_match( "/^u:([a-f0-9]{32})$/", $sub, $m ) ){
    $u = $loader->db->_select( array(
      "table" => "_u_list", "columns" => "ID",
      "where" => array( array( "hash", "=", $m[1] ) ),
      "limit" => 1, "single" => true
    ) );
    if ( $u ) $uid = (int)$u["ID"];
  }
  if ( !$uid )
    $send( array( "error" => array( "code" => "user_not_found", "message" => "sub=u:{hash} or uid required" ) ), 400 );

  $owner = $iyol->user_payload( $uid );
  if ( !$owner )
    $send( array( "error" => array( "code" => "user_not_found" ) ), 404 );

  $songs = array();
  $owner_ref = array( "sub" => $owner["sub"], "username" => $owner["username"], "name" => $owner["name"] );

  /* ---------------- AI Studio generated songs ---------------- */

  $r = $loader->db->query( "SELECT id, params, result_url, result_path, result_data, engine, time_done
    FROM `_htx_ai_jobs`
    WHERE user_id = " . (int)$uid . " AND type = 'song_gen' AND status = 'done'
    ORDER BY id DESC LIMIT 50" );
  if ( $r && $r->num_rows ){
    while ( $job = $r->fetch_assoc() ){
      $params = json_decode( (string)$job["params"], true );
      $rdata  = json_decode( (string)$job["result_data"], true );
      $audio  = !empty( $job["result_url"] ) ? $job["result_url"]
              : ( !empty( $job["result_path"] ) ? web_address . ltrim( $job["result_path"], "/" ) : null );
      if ( !$audio ) continue;

      $title = !empty( $params["title"] ) ? $params["title"]
             : ( !empty( $rdata["title"] ) ? $rdata["title"] : "AI Track #" . (int)$job["id"] );
      $secs  = !empty( $rdata["duration"] ) ? (int)$rdata["duration"]
             : ( !empty( $params["seconds"] ) ? (int)$params["seconds"] : null );

      $songs[] = array(
        "hitune_track_hash" => "ai" . (int)$job["id"],
        "title"             => $title,
        "artist"            => $owner["name"] ? $owner["name"] : $owner["username"],
        "duration_ms"       => $secs ? $secs * 1000 : null,
        "cover"             => !empty( $rdata["cover"] ) ? $rdata["cover"] : null,
        "audio_url"         => $audio,
        "hitune_url"        => web_address . "ai-studio",
        "attribution_label" => "AI song by @" . $owner["username"] . " — made in HiTune AI Studio",
        "ai_pct"            => 100,
        "ai_badge"          => "AI Original",
        "origin"            => "ai",
        "uploader"          => $owner_ref
      );
    }
  }

  /* ---------------- Distribution releases ---------------- */

  $r = $loader->db->query( "SELECT id, title, artist_name, status, cover_art_path, catalog_track_ids
    FROM `_dist_submissions`
    WHERE user_id = " . (int)$uid . " AND status IN ('submitted','in_review','in_progress','approved','launched')
    ORDER BY id DESC LIMIT 50" );
  if ( $r && $r->num_rows ){
    while ( $sub = $r->fetch_assoc() ){

      $cover = !empty( $sub["cover_art_path"] ) ? web_address . ltrim( $sub["cover_art_path"], "/" ) : null;
      $catalog_ids = json_decode( (string)$sub["catalog_track_ids"], true );

      if ( !empty( $catalog_ids ) && is_array( $catalog_ids ) ){
        // published to HiTune catalog — hand over real track payloads
        foreach ( $catalog_ids as $tid ){
          $p = $iyol->sound_payload( (int)$tid );
          if ( !$p || empty( $p["sound"] ) ) continue;
          $songs[] = array_merge( $p["sound"], array(
            "origin" => "dist",
            "status" => $sub["status"],
            "release" => $sub["title"]
          ) );
        }
        continue;
      }

      // not catalog-published yet — expose the raw dist audio files
      $rt = $loader->db->query( "SELECT id, title, artist_name, duration, audio_file_path
        FROM `_dist_tracks` WHERE submission_id = " . (int)$sub["id"] . " ORDER BY track_number ASC" );
      if ( !$rt || !$rt->num_rows ) continue;
      while ( $t = $rt->fetch_assoc() ){
        if ( empty( $t["audio_file_path"] ) ) continue;
        $songs[] = array(
          "hitune_track_hash" => "dist" . (int)$t["id"],
          "title"             => !empty( $t["title"] ) ? $t["title"] : $sub["title"],
          "artist"            => !empty( $t["artist_name"] ) ? $t["artist_name"] : $sub["artist_name"],
          "duration_ms"       => !empty( $t["duration"] ) ? (int)$t["duration"] * 1000 : null,
          "cover"             => $cover,
          "audio_url"         => web_address . ltrim( $t["audio_file_path"], "/" ),
          "hitune_url"        => "https://distribution.hitune.in/",
          "attribution_label" => "Release by @" . $owner["username"] . " via HiTune Distribution",
          "ai_pct"            => null,
          "ai_badge"          => null,
          "origin"            => "dist",
          "status"            => $sub["status"],
          "release"           => $sub["title"],
          "uploader"          => $owner_ref
        );
      }
    }
  }

  $send( array(
    "songs" => $songs,
    "count" => count( $songs ),
    "owner" => $owner_ref
  ) );

}

?>
