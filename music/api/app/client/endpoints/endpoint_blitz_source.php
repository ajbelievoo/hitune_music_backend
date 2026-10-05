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

  // Get Stream URL (server-side cache first)
  $stream_url = null;
  try {
    $stream = $loader->youtube_piped->set_setting()->get_stream_cached( $youtube_id );
    if ( $stream ) $stream_url = $stream["url"];
  } catch (Exception | bofException $e) {
    // Fallback or handle error
  }

  if ( !$stream_url ) {
    // Fallback to yt-dlp with better headers to avoid bot detection
    $ua = "Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36";
    $extractor_args = "youtube:player_client=android";
    $command = "/usr/local/bin/yt-dlp -g -f ba --force-ipv4 --no-check-certificate --user-agent " . escapeshellarg($ua) . " --extractor-args " . escapeshellarg($extractor_args) . " 'https://www.youtube.com/watch?v=" . $youtube_id . "' 2>&1";
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
