<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_blitz_source( $loader, $excuter, $args ){

  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  
  if ( !$object_hash ) {
    $loader->api->set_message( "ok", [ "error" => "no_hash" ] );
    return;
  }

  $the_object = $loader->object->__get( "m_track" );
  $object_item = $the_object->select(
    array( "hash" => $object_hash )
  );

  if ( !$object_item ) {
    $loader->api->set_message( "ok", [ "error" => "not_found" ] );
    return;
  }

  // Find YouTube ID
  $youtube_id = null;
  $yt_res = $loader->youtube->find_video( array(
    "title" => $object_item["title"],
    "sub_title" => $object_item["bof_dir_artist"]["name"] ?? null,
    "duration" => $object_item["duration"] ?? null
  ) );

  if ( $yt_res[0] ) {
    $youtube_id = $yt_res[1];
  }

  if ( !$youtube_id ) {
    $loader->api->set_message( "ok", [ "error" => "youtube_id_not_found" ] );
    return;
  }

  // Get Stream URL — cache peek first (instant), then saavn (~1.4s; the
  // only reliable resolver while this IP is YouTube bot-blocked), then
  // the breaker-guarded live chain.
  $stream_url = null;
  try {
    $stream = $loader->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
      "allow_live" => false
    ) );
    if ( $stream ) $stream_url = $stream["url"];
  } catch (Exception | bofException $e) {
    // Fallback or handle error
  }

  if ( !$stream_url ) {
    try {
      $_sv = bof()->hitune_saavn->resolve(
        $object_item["title"],
        $object_item["bof_dir_artist"]["name"] ?? null,
        $object_item["duration"] ?? null
      );
      if ( $_sv && !empty( $_sv["url"] ) ){
        $stream_url = $_sv["url"];
        try {
          $loader->youtube_piped->cache_stream_url( $youtube_id, $_sv["url"], array(
            "mime" => $_sv["mime"], "type" => "audio", "duration" => $_sv["duration"], "expire_in" => 259200
          ) );
        } catch (Exception | bofException $e) {}
      }
    } catch (Exception | bofException $e) {}
  }

  if ( !$stream_url ) {
    try {
      $stream = $loader->youtube_piped->set_setting()->get_stream_cached( $youtube_id );
      if ( $stream ) $stream_url = $stream["url"];
    } catch (Exception | bofException $e) {}
  }

  if ( !$stream_url ) {

    // Respect the shared resolve breaker — while YouTube is hard-failing,
    // a synchronous yt-dlp call here just burns an FPM worker for ~15s.
    $_brk = @json_decode( @file_get_contents( rtrim( sys_get_temp_dir(), "/\\" ) . "/bof_yt_resolve_breaker" ), true );
    if ( !empty( $_brk["open_until"] ) && time() < intval( $_brk["open_until"] ) ) {
      $loader->api->set_message( "ok", [ "error" => "resolve_backoff", "youtube_id" => $youtube_id ] );
      return;
    }

    // Fallback to yt-dlp with better headers to avoid bot detection
    $ua = "Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36";
    $extractor_args = "youtube:player_client=android";
    $command = "timeout 15 /usr/local/bin/yt-dlp -g -f ba --force-ipv4 --no-check-certificate --user-agent " . escapeshellarg($ua) . " --extractor-args " . escapeshellarg($extractor_args) . " 'https://www.youtube.com/watch?v=" . $youtube_id . "' 2>&1";
    $output = shell_exec($command);
    if ( $output && strpos($output, 'http') !== false && strpos($output, 'ERROR:') === false ) {
        $lines = explode("\n", trim($output));
        $stream_url = end($lines);
    }
  }

  if ( !$stream_url ) {
    $loader->api->set_message( "ok", [ "error" => "stream_not_found", "youtube_id" => $youtube_id ] );
    return;
  }

  $loader->api->set_message( "ok", [ 
    "title" => $object_item["title"],
    "stream_url" => $stream_url
  ] );

}
