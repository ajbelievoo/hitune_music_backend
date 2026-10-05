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

  $extra = array(
    "duration" => $duration,
    "title" => $object_item["title"],
    "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
    "cover" => !empty( $object_item["bof_file_cover"]["image_thumb"] ) ? $object_item["bof_file_cover"]["image_thumb"] : null
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
          $_address = $loader->source->grant_access( $source_G["ot"], $source_G["raw"]["hash"], $sources_by_type["user"]["hash"], $_address, "20 MINUTE" );
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
          "cached" => !empty( $stream["cached"] ) ? true : false
        );
      }
    } catch( Exception | bofException | Error $err ){}

  }

  if ( !$playable ){

    // JioSaavn fallback (plugin: bof_tool_hitune_extras) — full-length CDN
    // audio, no poToken needed while YouTube blocks the server IP.
    try {
      $_sv = bof()->hitune_saavn->resolve(
        $object_item["title"],
        !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
        $duration
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
          "duration" => $duration ? $duration : 30,
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

  $loader->api->set_message( "ok", array_merge( $playable, $extra ) );

}

?>
