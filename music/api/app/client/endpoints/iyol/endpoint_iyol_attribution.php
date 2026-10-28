<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/audio/attribution?track={hash}
 * Resolves HiTune track metadata + rights/royalty attribution for IyolMe
 * when a sound is reused in reels. Bearer JWT (client_credentials or user).
 */
function endpoint_iyol_attribution( $loader, $excuter, $args ){

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

  $hash = $loader->nest->user_input( "get", "track", "md5" );
  if ( !$hash ) $hash = $loader->nest->user_input( "get", "hash", "md5" );
  if ( !$hash )
    $send( array( "error" => array( "code" => "invalid_request", "message" => "track={32-char hash} query param required" ) ), 400 );

  $track = bof()->object->m_track->select(
    array( "hash" => $hash ),
    array( "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
  );
  if ( !$track )
    $send( array( "error" => array( "code" => "not_found", "message" => "Track not found" ) ), 404 );

  // royalty / rights — resolve the dist submission that produced this track
  $rights = array( "source" => "catalog" );
  $r = $loader->db->query( "SELECT id, user_id, isrc, label_name, status, ai_pct FROM `_dist_submissions`
    WHERE catalog_published = 1 AND catalog_track_ids LIKE '%\"" . (int)$track["ID"] . "%' LIMIT 1" );
  if ( $r && $r->num_rows ){
    $sub = $r->fetch_assoc();
    $uploader = $iyol->user_payload( (int)$sub["user_id"] );
    $rights = array(
      "source"          => "hitune_distribution",
      "submission_id"   => (int)$sub["id"],
      "status"          => $sub["status"],
      "isrc"            => $sub["isrc"],
      "label"           => $sub["label_name"],
      "rights_holder"   => $uploader ? array( "sub" => $uploader["sub"], "username" => $uploader["username"], "name" => $uploader["name"] ) : null,
      "royalty_splits"  => array(
        array( "party" => "artist", "share" => 100, "note" => "HiTune promotional window — see distribution terms for DSP splits" )
      )
    );
  } else {
    // UGC / legacy catalog item — attribution goes to the uploader
    $uploader = !empty( $track["uploader_id"] ) ? $iyol->user_payload( (int)$track["uploader_id"] ) : null;
    $rights = array(
      "source"        => "catalog",
      "rights_holder" => $uploader ? array( "sub" => $uploader["sub"], "username" => $uploader["username"], "name" => $uploader["name"] ) : null
    );
  }

  $hitune_url = !empty( $track["url"] ) ? $track["url"] : web_address . "track/" . $track["hash"];
  if ( strpos( (string)$hitune_url, "http" ) !== 0 )
    $hitune_url = web_address . ltrim( (string)$hitune_url, "/" );

  $artist_name = !empty( $track["bof_dir_artist"]["name"] ) ? $track["bof_dir_artist"]["name"] : null;
  $owner = !empty( $rights["rights_holder"]["username"] ) ? $rights["rights_holder"]["username"] : "hitune";

  $send( array(
    "track" => array(
      "hash"        => $track["hash"],
      "title"       => $track["title"],
      "artists"     => $artist_name ? array( array( "name" => $artist_name, "hash" => !empty( $track["bof_dir_artist"]["hash"] ) ? $track["bof_dir_artist"]["hash"] : null ) ) : array(),
      "album"       => !empty( $track["bof_dir_album"]["title"] ) ? array( "title" => $track["bof_dir_album"]["title"], "hash" => !empty( $track["bof_dir_album"]["hash"] ) ? $track["bof_dir_album"]["hash"] : null ) : null,
      "duration_ms" => !empty( $track["duration"] ) ? (int)$track["duration"] * 1000 : null,
      "explicit"    => !empty( $track["explicit"] ) ? true : false,
      "ai_pct"      => (int)( $track["ai_pct"] ?? 0 ),
      "ai_badge"    => !empty( $track["ai_pct"] ) ? "AI Original" : null
    ),
    "attribution" => array(
      "label"          => "Original Sound by @{$owner} on HiTune Music",
      "hitune_url"     => $hitune_url,
      "open_in_hitune" => web_address . "track/" . $track["hash"]
    ),
    "rights" => $rights
  ) );

}

?>
