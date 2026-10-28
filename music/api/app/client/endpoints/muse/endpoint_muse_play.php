<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_play( $loader, $excuter, $args ){

  // Single-call play resolver: {object_type, object_hash, quality}
  // -> {url, type, mime, duration, loudness, youtube_id, expires, cached}
  // All raaz / piped / yt-dlp resolution happens server-side; resolved
  // googlevideo URLs are shared across clients via _bof_cache_streams.

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "play" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  $quality     = $loader->nest->user_input( "post", "quality", "string" );
  // Optional: client already resolved the stream — report it back so the
  // server-side cache can serve every other client.
  $resolved_url   = $loader->nest->user_input( "post", "resolved_url" );
  $posted_yt_id   = $loader->nest->user_input( "post", "youtube_id", "string" );
  $resolved_mime  = $loader->nest->user_input( "post", "resolved_mime", "string" );
  $resolved_type  = $loader->nest->user_input( "post", "resolved_type", "in_array", [ "values" => [ "audio", "video" ] ] );

  if ( !$object_name || !$object_hash ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $the_object = $loader->object->__get( $object_name );
  $object_item = $the_object->select(
    array(
      "hash" => $object_hash
    ),
    array(
      "muse_source" => true,
      "_eq" => array(
        "sources" => array(),
        "cover" => [],
        "album" => [ "cover" => [] ],
        "artist" => []
      )
    )
  );

  if ( !$object_item ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  // Normalize quality -> youtube_piped preferred type
  $prefer = null;
  if ( $quality ){
    $quality = strtolower( trim( $quality ) );
    $_map = array(
      "hq" => "audio_hq", "high" => "audio_hq",
      "lq" => "audio_lq", "low"  => "audio_lq",
      "audio" => "audio_hq", "video" => "video_hq"
    );
    if ( in_array( $quality, [ "audio_hq", "audio_lq", "video_hq", "video_lq" ], true ) )
    $prefer = $quality;
    elseif ( !empty( $_map[ $quality ] ) )
    $prefer = $_map[ $quality ];
  }

  $duration = !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null;

  // Cover: file cover -> largest spotify_cover variant on track/album
  // (cover_id is empty for ~9.5k imported tracks).
  $_cover = !empty( $object_item["bof_file_cover"]["image_thumb"] ) ? $object_item["bof_file_cover"]["image_thumb"] : null;
  $_sc_url = null;
  foreach ( [ "spotify_cover", "bof_dir_album", "bof_dir_artist" ] as $_k ){
    if ( $_k === "spotify_cover" ) $_raw = !empty( $object_item["spotify_cover"] ) ? $object_item["spotify_cover"] : null;
    else $_raw = !empty( $object_item[$_k]["spotify_cover"] ) ? $object_item[$_k]["spotify_cover"] : null;
    if ( empty( $_raw ) ) continue;
    $_arr = is_string( $_raw ) ? json_decode( $_raw, true ) : $_raw;
    if ( empty( $_arr ) || !is_array( $_arr ) ) continue;
    $_bw = -1;
    foreach ( $_arr as $_im ){
      if ( empty( $_im["url"] ) ) continue;
      $_w = intval( isset( $_im["width"] ) ? $_im["width"] : 0 );
      if ( $_w > $_bw ){ $_bw = $_w; $_sc_url = $_im["url"]; }
    }
    if ( $_sc_url ) break;
  }
  if ( $_sc_url && ( !$_cover || strpos( $_cover, "scdn.co" ) !== false ) )
  $_cover = $_sc_url;

  $extra = array(
    "duration" => $duration,
    "title" => $object_item["title"],
    "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
    "cover" => $_cover
  );

  $_loudness = bof()->music->track_loudness( $object_item );
  if ( $_loudness ){
    $extra["loudness"] = $_loudness;
    $extra["lufs"] = $_loudness["lufs"];
    if ( $_loudness["peak_db"] !== null ) $extra["peak_db"] = $_loudness["peak_db"];
  }

  $youtube_id = !empty( $object_item["youtube_id"] ) ? $object_item["youtube_id"] : ( $posted_yt_id ? $posted_yt_id : null );

  // Client-reported resolution -> feed the shared stream cache
  if ( $resolved_url && $youtube_id ){
    try {
      $loader->youtube_piped->set_setting()->cache_stream_url( $youtube_id, $resolved_url, array(
        "mime" => $resolved_mime,
        "type" => $resolved_type,
        "duration" => $duration,
        "prefer" => $prefer
      ) );
    } catch( Exception | bofException | Error $err ){}
  }

  $playable = null;

  if ( !empty( $object_item["sources"] ) ){

    foreach( $object_item["sources"] as $source_G ){

      $sources_by_type = $loader->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "stream" );

      if ( !$sources_by_type || $sources_by_type === "pending" || empty( $sources_by_type["user"] ) )
      continue;

      $_muse = $sources_by_type["user"]["muse"];
      $_t = !empty( $_muse["type"] ) ? $_muse["type"] : null;
      if ( !is_array( $_t ) || empty( $_t[0] ) )
      continue;

      // Direct playable audio/video source
      if ( ( $_t[0] == "audio" || $_t[0] == "video" ) && is_array( $_t[1] ) && !empty( $_t[1]["address"] ) ){

        $_address = $loader->general->https_url( $_t[1]["address"] );

        if ( !empty( $sources_by_type["user"]["protected"] ) && preg_match( "/\/files\/protected\//", $_address ) ){
          $_address = $loader->source->grant_access( $source_G["ot"], $source_G["raw"]["hash"], $sources_by_type["user"]["hash"], $_address, "90 MINUTE" );
        }

        $playable = array(
          "url" => $_address,
          "type" => $_t[0],
          "mime" => !empty( $_t[1]["mime"] ) ? $_t[1]["mime"] : ( !empty( $_t[1]["format"] ) ? $_t[1]["format"] : null ),
          "hls" => !empty( $_t[1]["hls"] ) ? true : false,
          "cached" => true
        );
        break;

      }

      // YouTube / raaz source -> resolve via server-side stream cache
      if ( $_t[0] == "youtube" || ( is_array( $_t[1] ) ? !empty( $_t[1]["raaz"] ) : false ) ){

        if ( !$youtube_id && is_array( $_t[1] ) )
        $youtube_id = !empty( $_t[1]["ID"] ) ? $_t[1]["ID"] : ( !empty( $_t[1]["youtube_id"] ) ? $_t[1]["youtube_id"] : null );

      }

    }

  }

  // YouTube id fallback: existing youtube source rows, then search
  if ( !$youtube_id && !empty( $object_item["bof_dir_sources"] ) ){
    foreach( $object_item["bof_dir_sources"] as $_source ){
      if ( $_source["type"] == "youtube" && !empty( $_source["data_decoded"]["youtube_id"] ) ){
        $youtube_id = $_source["data_decoded"]["youtube_id"];
        break;
      }
    }
  }

  if ( !$youtube_id && !$playable && !empty( $object_item["title"] ) && bof()->object->db_setting->get( "youtube_automation" ) ){
    $try = $loader->youtube->find_video( array(
      "title" => $object_item["title"],
      "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
      "duration" => $duration
    ) );
    if ( $try[0] ) $youtube_id = $try[1];
  }

  if ( !$playable && $youtube_id ){

    if ( empty( $object_item["youtube_id"] ) )
    bof()->music->set_track_youtube_id( $object_item["ID"], $youtube_id );

    // Cache peek only — never burns the 15-60s live YouTube chain.
    try {
      $stream = $loader->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
        "prefer" => $prefer,
        "allow_live" => false
      ) );
      if ( $stream ){
        $playable = array(
          "url" => $stream["url"],
          "type" => $stream["type"],
          "mime" => $stream["mime"],
          "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : $duration,
          "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
          "refresh_by" => !empty( $stream["expire"] ) ? intval( $stream["expire"] ) - 120 : null,
          "cached" => !empty( $stream["cached"] ) ? true : false
        );
      }
    } catch( Exception | bofException | Error $err ){}

  }

  if ( !$playable ){

    // JioSaavn fast path (~1.4s) — full-length CDN audio; the only
    // reliable resolver while YouTube bot-blocks this server IP.
    try {
      $_sv = bof()->hitune_saavn->resolve(
        $object_item["title"],
        !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
        $duration,
        $prefer
      );
      if ( $_sv && !empty( $_sv["url"] ) ){
        $playable = array(
          "url" => $_sv["url"],
          "type" => "audio",
          "mime" => $_sv["mime"],
          "duration" => $_sv["duration"],
          "saavn" => true,
          "cached" => false
        );
        if ( $youtube_id )
        try {
          $loader->youtube_piped->cache_stream_url( $youtube_id, $_sv["url"], array(
            "mime" => $_sv["mime"], "type" => "audio", "duration" => $_sv["duration"], "prefer" => $prefer, "expire_in" => 259200
          ) );
        } catch( Exception | bofException | Error $err ){}
      }
    } catch( Exception | bofException | Error $err ){}

  }

  if ( !$playable && $youtube_id ){

    // Live YouTube chain — last resort; the circuit breaker inside
    // get_stream_cached keeps this from stalling while the IP stays
    // bot-blocked.
    try {
      $stream = $loader->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
        "prefer" => $prefer
      ) );
      if ( $stream ){
        $playable = array(
          "url" => $stream["url"],
          "type" => $stream["type"],
          "mime" => $stream["mime"],
          "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : $duration,
          "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
          "refresh_by" => !empty( $stream["expire"] ) ? intval( $stream["expire"] ) - 120 : null,
          "cached" => !empty( $stream["cached"] ) ? true : false
        );
      }
    } catch( Exception | bofException | Error $err ){}

  }

  if ( !$playable ){

    // Last resort: iTunes preview (real audio, ~30s)
    try {
      $_q = trim( $object_item["title"] . " " . ( !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : "" ) );
      $_ctx = stream_context_create( [ "http" => [ "timeout" => 8 ] ] );
      $_it = @json_decode( @file_get_contents( "https://itunes.apple.com/search?term=" . urlencode( $_q ) . "&entity=song&limit=5", false, $_ctx ), true );
      if ( !empty( $_it["results"] ) ){
        $_best = null; $_best_score = 0;
        foreach( $_it["results"] as $_r ){
          if ( empty( $_r["previewUrl"] ) ) continue;
          $_score = 0;
          similar_text( strtolower( $_r["trackName"] ), strtolower( $object_item["title"] ), $_t_pct );
          $_score += $_t_pct;
          if ( !empty( $_r["artistName"] ) && !empty( $object_item["bof_dir_artist"]["name"] ) ){
            similar_text( strtolower( $_r["artistName"] ), strtolower( $object_item["bof_dir_artist"]["name"] ), $_a_pct );
            $_score += $_a_pct * 0.5;
          }
          if ( $_score > $_best_score ){ $_best_score = $_score; $_best = $_r; }
        }
        if ( $_best && $_best_score > 60 )
        $playable = array(
          "url" => $_best["previewUrl"],
          "type" => "audio",
          "mime" => "audio/mp4",
          "preview" => true,
          "is_preview" => true,
          "duration" => 30,
          "cached" => false
        );
      }
    } catch( Exception $err ){}

  }

  if ( !$playable ){
    $loader->api->set_error( "cant_play", array(
      "error_reason" => "no_playable_source",
      "youtube_id" => $youtube_id
    ) );
    return;
  }

  $playable["youtube_id"] = $youtube_id;

  // Next-track prefetch: client sends the upcoming queue item so its stream
  // is already resolved+cached before the current song ends (gapless-ish).
  $next_name = $loader->nest->user_input( "post", "next_object_type", "bofClient_object", [ "has_button" => "play" ] );
  $next_hash = $loader->nest->user_input( "post", "next_object_hash", "md5" );
  if ( $next_name && $next_hash ){
    try {
      $_next_item = $loader->object->__get( $next_name )->select( [ "hash" => $next_hash ], [ "muse_source" => true ] );
      $_next_yt   = !empty( $_next_item["youtube_id"] ) ? $_next_item["youtube_id"] : null;
      if ( !$_next_yt && !empty( $_next_item["bof_dir_sources"] ) ){
        foreach( $_next_item["bof_dir_sources"] as $_ns ){
          if ( $_ns["type"] == "youtube" && !empty( $_ns["data_decoded"]["youtube_id"] ) ){ $_next_yt = $_ns["data_decoded"]["youtube_id"]; break; }
        }
      }
      if ( $_next_yt ){
        $_ck = "yt:" . $_next_yt . ":audio_hq";
        $_cr = bof()->db->_select( [ "table" => "_bof_cache_streams", "where" => [ [ "stream_key", "=", $_ck ] ], "single" => true, "cache_load_rt" => false ] );
        $_cfresh = $_cr && !empty( $_cr["expire_at"] ) && ( intval( $_cr["expire_at"] ) - time() ) > 2700;
        if ( !$_cfresh )
        @shell_exec( "nohup /usr/bin/php82 " . escapeshellarg( base_root . "/scripts/warm_stream.php" ) . " " . escapeshellarg( $_next_yt ) . " >/dev/null 2>&1 &" );
      }
    } catch( Exception | bofException | Error $err ){}
  }

  $loader->api->set_message( "ok", array_merge( $playable, $extra ) );

}

?>
