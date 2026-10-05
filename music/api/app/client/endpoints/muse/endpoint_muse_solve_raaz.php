<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_solve_raaz( $loader, $excuter, $args ){

  // Validate simplified data
  $_d["title"] = $title = $loader->nest->user_input( "post", "title", "string" );
  $_d["sub_title"] = $sub_title = $loader->nest->user_input( "post", "sub_title", "string" );
  $_d["object_type"] = $object_type = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "play" ] );
  $_d["object_hash"] = $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  $_d["duration"] = $duration = $loader->nest->user_input( "post", "duration", "int", [ "empty()" => true, "min" => 0 ] );
  if ( !$title || !$sub_title || !$object_type || !$object_hash || $object_type != "m_track" ){
    $loader->api->set_error( "bad_inputs", [ "missing_params" => true ] );
    return;
  }

  // Validate Raaz data
  $youtube_id = bof()->nest->user_input( "post", "youtube_id", "youtube_uri" );
  $youtube_get = bof()->nest->user_input( "post", "youtube_get", "equal", [ "value" => "true" ] );
  $youtube_piped = bof()->nest->user_input( "post", "youtube_piped", "equal", [ "value" => "true" ] );
  $youtube_piped_instances = bof()->nest->user_input( "post", "youtube_piped_instances", "equal", [ "value" => "true" ] );
  $youtube_download = bof()->nest->user_input( "post", "youtube_download", "equal", [ "value" => "true" ] );
  $soundcloud_get = bof()->nest->user_input( "post", "soundcloud_get", "equal", [ "value" => "true" ] );

  if ( $youtube_download ){
    $_ytp = bof()->object->db_setting->get( "ut_youtubedl_path" );
    $_ytp = $_ytp ? htmlspecialchars_decode( $_ytp ) : null;
    if ( !$_ytp || !is_file( $_ytp ) || !is_executable( $_ytp ) ){
      $youtube_download = false;
    }
  }

  if ( $youtube_get ? !$loader->object->db_setting->get( "youtube_automation" ) : false )
  {
    $loader->api->set_message( "ok", array(
      "type" => array(
        "youtube",
        array(
          "youtube_id" => $youtube_id
        )
      )
    ) );
    return;
  }

  if ( $youtube_download ? !$loader->object->db_setting->get( "ut" ) : false )
  {
    $loader->api->set_message( "ok", array(
      "type" => array(
        "youtube",
        array(
          "youtube_id" => $youtube_id
        )
      )
    ) );
    return;
  }

  if ( $youtube_piped ? !$loader->object->db_setting->get( "youtube_piped" ) : false )
  {
    $loader->api->set_message( "ok", array(
      "type" => array(
        "youtube",
        array(
          "youtube_id" => $youtube_id
        )
      )
    ) );
    return;
  }

  if ( $soundcloud_get ? !$loader->object->db_setting->get( "soundcloud_automation" ) : false )
  {
    $loader->api->set_message( "ok", array(
      "type" => array(
        "soundcloud",
        array(
          "soundcloud_id" => null
        )
      )
    ) );
    return;
  }

  if ( $youtube_get || $youtube_download || $youtube_piped ){
    $target = "youtube";
  }
  elseif ( $soundcloud_get ){
    $target = "soundcloud";
  }
  else{
    $loader->api->set_error( "bad_inputs", [ "missing_params" => true ] );
    return;
  }

  // Validate object type & hash & existence
  $the_object = $loader->object->__get( $object_type );
  $object_item = $the_object->select(
    array(
      "hash" => $object_hash
    ),
    array(
      "cache_load" => true,
      "_eq" => array(
        "sources" => [],
        "cover" => [],
        "artist" => []
      )
    )
  );

  if ( !$object_item ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  // Loudness metadata (EBU R128) for client-side volume normalization
  $_loudness_extra = array();
  $_loudness = bof()->music->track_loudness( $object_item );
  if ( $_loudness ){
    $_loudness_extra["loudness"] = $_loudness;
    $_loudness_extra["lufs"] = $_loudness["lufs"];
    if ( $_loudness["peak_db"] !== null ) $_loudness_extra["peak_db"] = $_loudness["peak_db"];
  }

  // Validate titlle & sub-title
  $_nd = array(
    "title" => $object_item["title"],
    "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
    "duration" => !empty( $object_item["duration"] ) ? $object_item["duration"] : null
  );

  $target_source_exists = null;
  $target_source_id = null;
  $required_source_exists = null;
  // Check source existence
  if ( !empty( $object_item["bof_dir_sources"] ) ){
    foreach( $object_item["bof_dir_sources"] as $source ){
      if ( $target == "youtube" && ( $source["type"] == "youtube" ? !empty( $source["data_decoded"]["youtube_id"] ) : false ) ){
        $target_source_exists = $source;
        $target_source_id = $source["data_decoded"]["youtube_id"];
        if ( !$youtube_download && !$youtube_piped )
        $required_source_exists = $source;
      }
      elseif ( $target == "youtube" && $youtube_download ? ( $source["type"] == "audio" && ( empty( $source["data_decoded"]["file_type"] ) || $source["data_decoded"]["file_type"] != "youtube_pending" ) ) : false ){
        $required_source_exists = $source;
      }
      elseif ( $target == "soundcloud" && ( $source["type"] == "soundcloud" ? !empty( $source["data_decoded"]["soundcloud_id"] ) : false ) ){
        $target_source_exists = $source;
        $target_source_id = $source["data_decoded"]["soundcloud_id"];
        $required_source_exists = $source;
      }
    }
  }

  // Required source exists
  if ( $required_source_exists ){
    $loader->api->set_message( "ok", array_merge( (array) $required_source_exists["muse"], $_loudness_extra ) );
    return;
  }

  // Check if we can get target source if required
  $can_get_target = $target == "youtube" ? $youtube_get : $soundcloud_get;
  if ( !$required_source_exists && !$target_source_exists && !$can_get_target ){
    $loader->api->set_error( "cant_play", [ "error_reason" => "no_target_source" ] );
    return;
  } 

  // Check if we need to get target source
  if ( !$target_source_exists ){

    $_urid = bof()->request->user_request_ini("{$target}_id", $_d);
    if ( $_urid === true )
    return;

    // fetch
    if ( $target == "youtube" ){
      $try_to_fetch_target_id = $loader->youtube->find_video( $_nd );
    } 
    else {
      $try_to_fetch_target_id = $loader->soundcloud->find_track( $_nd );
    }

    bof()->request->user_request_update(
      $_urid,
      $try_to_fetch_target_id[0] ? true : false,
      $try_to_fetch_target_id[0] ? ["{$target}_id" => $try_to_fetch_target_id[1]] : $try_to_fetch_target_id[1]
    );

    if ( !$try_to_fetch_target_id[0] ? true : !$try_to_fetch_target_id[1] ){
      $loader->api->set_error( 
        $try_to_fetch_target_id[1], 
        [ "output_args" => [ "turn" => false ] ] 
      );
      return;
    }

    // record
    $target_source_id = $try_to_fetch_target_id[1];
    $loader->object->__get("m_track_source")->insert(array(
      "target_id" => $object_item["ID"],
      "type" => $target,
      "data" => json_encode([
        "{$target}_id" => $target_source_id
      ]),
      "stream_able" => 1,
      "download_able" => -2,
      "encrypted" => 0
    ));

  }

  if ( $target == "youtube" && $target_source_id && empty( $object_item["youtube_id"] ) )
  bof()->music->set_track_youtube_id( $object_item["ID"], $target_source_id );

  // Client-reported resolution -> feed the shared stream cache so other
  // clients skip resolving entirely
  if ( $target == "youtube" && $target_source_id ){
    $_resolved_url = bof()->nest->user_input( "post", "resolved_url" );
    if ( $_resolved_url ){
      try {
        $loader->youtube_piped->set_setting()->cache_stream_url( $target_source_id, $_resolved_url, array(
          "mime" => bof()->nest->user_input( "post", "resolved_mime", "string" ),
          "type" => bof()->nest->user_input( "post", "resolved_type", "in_array", [ "values" => [ "audio", "video" ] ] ),
          "duration" => $duration
        ) );
      } catch( Exception | bofException | Error $err ){}
    }
  }

  if ( ( $target == "youtube" && !$youtube_download && !$youtube_piped ) || ( $target == "soundcloud" ) ){
    $loader->api->set_message( "ok", array_merge( array(
      "type" => array(
        $target,
        array(
          "{$target}_id" => $target_source_id
        )
      )
    ), $_loudness_extra ) );
    return;
  }

  // yt-piped
  if ($youtube_piped) {

    $youtube_piped_method = bof()->object->db_setting->get( "youtube_piped_be", "browser" );
    $youtube_piped_method = strtolower( trim( (string)$youtube_piped_method ) );

    if ($youtube_piped_method == "server") {

      try {
        $stream = bof()->youtube_piped->set_setting()->get_stream_cached($target_source_id);
        $loader->api->set_message("ok", array_merge( array(
          "type" => array(
            $stream["type"],
            array(
              "address" => $stream["url"] . "&bof_sw_ignore_me=sure&unique=" . uniqid(),
              "type" => "free",
              "format" => $stream["mime"],
              "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : ( !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null ),
              "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
            )
          )
        ), $_loudness_extra ));
        return;
      } catch (Exception | bofException $err) {

        $response = array(
          "youtube_id" => $target_source_id,
          "youtube_piped_browser" => true,
          "youtube_piped_failed" => true,
          "youtube_piped_error" => $err->getMessage()
        );

        if ( !$youtube_piped_instances ){
          $response["youtube_piped_urls"] = bof()->youtube_piped->set_setting()->get_instances();
          $st = bof()->object->db_setting->get( "youtube_piped_st" );
          $response["youtube_piped_type"] = explode( "_", $st ? $st : "audio_hq" );
        }

        $loader->api->set_message("ok", array_merge( array(
          "type" => array(
            "youtube",
            $response
          )
        ), $_loudness_extra ) );
        return;

      }

    }
    else {

      $response = array(
        "youtube_id" => $target_source_id,
        "youtube_piped_browser" => true,
      );

      if ( !$youtube_piped_instances ){
        $response["youtube_piped_urls"] = bof()->youtube_piped->set_setting()->get_instances();
        $st = bof()->object->db_setting->get( "youtube_piped_st" );
        $response["youtube_piped_type"] = explode( "_", $st ? $st : "audio_hq" );
      }

      $loader->api->set_message("ok", array_merge( array(
        "type" => array(
          "youtube",
          $response
        )
      ), $_loudness_extra ) );
      return;

    }

  }

  // yt-dl
  if ($youtube_download) {

    $_urid = bof()->request->user_request_ini("youtube_dl", $target_source_id);

    // Dedup replay / in-flight request already emitted a response - never
    // overwrite it with the fallback stub below.
    if ( $_urid === true )
    return;

    // Queues a background download so the track still lands in the local
    // library (cron _bgp picks it up) when no usable audio source exists.
    $_queue_yt_pending = function() use ( $loader, $object_item, $target_source_id ){

      $check_pending = $loader->object->m_track_source->select(
        array(
          "target_id" => $object_item["ID"],
          "type" => "audio",
          "queue" => 1
        ),
        array(
          "limit" => 1,
          "single" => true
        )
      );

      if ( !$check_pending ) {
        $loader->object->m_track_source->create(
          [],
          array(
            "target_id" => $object_item["ID"],
            "type" => "audio",
            "data" => array(
              "file_type" => "youtube_pending",
              "youtube_id" => $target_source_id,
            ),
            "queue" => 1
          ),
          []
        );
      }

    };

    // Check if we already have a real audio source for this track (pending/queued rows do not count)
    $check_audio = null;
    $_audio_sources = $loader->object->m_track_source->select(
      array(
        "target_id" => $object_item["ID"],
        "type" => "audio"
      ),
      array(
        "limit" => 10
      )
    );
    if ( $_audio_sources ){
      foreach( $_audio_sources as $_as ){
        if ( !empty( $_as["data_decoded"]["file_type"] ) ? $_as["data_decoded"]["file_type"] == "youtube_pending" : false )
        continue;
        $check_audio = $_as;
        break;
      }
    }

    // A finished local audio source already exists - serve it directly
    if ( $check_audio ){

      $fresh_source = $loader->object->m_track_source->select(
        array( "ID" => $check_audio["ID"] ),
        array( "single" => true )
      );

      if ( !empty( $fresh_source["muse"] ) ){
        $_resp = array_merge( (array) $fresh_source["muse"], $_loudness_extra );
        bof()->request->user_request_update( $_urid, true, $_resp );
        $loader->api->set_message( "ok", $_resp );
        return;
      }

    }

    // Fast path: resolve a direct stream URL through the shared stream
    // cache (Piped -> yt-dlp -g). Much cheaper than downloading the whole
    // file and the resolved URL is shared across clients via
    // _bof_cache_streams.
    try {

      $stream = bof()->youtube_piped->set_setting()->get_stream_cached( $target_source_id );

      if ( $stream && !empty( $stream["url"] ) ){

        $_resp = array_merge( array(
          "type" => array(
            $stream["type"],
            array(
              "address" => $stream["url"] . "&bof_sw_ignore_me=sure&unique=" . uniqid(),
              "type" => "free",
              "format" => $stream["mime"],
              "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : ( !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null ),
              "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
            )
          )
        ), $_loudness_extra );

        if ( !$check_audio )
        $_queue_yt_pending();

        bof()->request->user_request_update( $_urid, true, $_resp );
        $loader->api->set_message( "ok", $_resp );
        return;

      }

    } catch( Exception | bofException | Error $err ){}

    // JioSaavn fallback (plugin: bof_tool_hitune_extras) — direct CDN MP4s
    // that need no poToken; works while YouTube blocks the server IP.
    try {
      $_sv = bof()->hitune_saavn->resolve(
        $object_item["title"],
        !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
        $duration
      );
      if ( $_sv && !empty( $_sv["url"] ) ){

        $_resp = array_merge( array(
          "type" => array(
            "audio",
            array(
              "address" => $_sv["url"],
              "type" => "free",
              "format" => $_sv["mime"],
              "duration" => $_sv["duration"],
              "saavn" => true
            )
          )
        ), $_loudness_extra );

        if ( !$check_audio )
        $_queue_yt_pending();

        bof()->request->user_request_update( $_urid, true, $_resp );
        $loader->api->set_message( "ok", $_resp );
        return;

      }
    } catch( Exception | bofException | Error $err ){}

    if ( !$check_audio ) {

      // Download the audio inline so playback never depends on the youtube embed
      try {

        set_time_limit( 300 );

        $download_and_convert = $loader->youtube->download( $target_source_id );

        if ( $download_and_convert && is_file( $download_and_convert ) ){

          $rules = $loader->object->file->get_rules( "audio", "m_track_source", [ "get_host" => true ] );

          $convert_file_id = $loader->object->file->insert(
            array(
              "type" => "audio",
              "host_id" => "1",
              "dest_host_id" => $rules["file_host"],
              "path" => $loader->object->file->clean_path( $download_and_convert, true ),
              "object_type" => "m_track_source",
            )
          );

          if ( $convert_file_id ){

            $new_source = $loader->object->m_track_source->create(
              [],
              array(
                "target_id" => $object_item["ID"],
                "type" => "audio",
                "data" => array(
                  "file_type" => "local",
                  "local_file" => $convert_file_id,
                ),
              ),
              []
            );

            $new_source_id = !empty( $new_source["ID"] ) ? $new_source["ID"] : ( !empty( $new_source["insert_id"] ) ? $new_source["insert_id"] : $new_source );

            $fresh_source = $loader->object->m_track_source->select(
              array( "ID" => $new_source_id ),
              array( "single" => true )
            );

            if ( !empty( $fresh_source["muse"] ) ? $fresh_source["muse"]["type"][0] == "audio" : false ){
              $_resp = array_merge( (array) $fresh_source["muse"], $_loudness_extra );
              bof()->request->user_request_update($_urid, true, $_resp);
              $loader->api->set_message( "ok", $_resp );
              return;
            }

          }

        }

      } catch( Exception | bofException | Error $err ){}

      // Download failed or did not resolve - queue a pending source instead
      $_queue_yt_pending();

    }

    // Nothing playable could be resolved - fail explicitly (success:false)
    // so the client knows to fall back instead of trusting a dead stub.
    bof()->request->user_request_update( $_urid, false, "cant_play" );
    $loader->api->set_error( "cant_play", array(
      "error_reason" => "resolve_failed",
      "youtube_id" => $target_source_id,
      "youtube_pending" => true
    ) );
    return;

  }

}

?>
