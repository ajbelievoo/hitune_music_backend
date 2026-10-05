<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_preview_no_ff( $loader, $excuter, $args ){

  if ( !bof()->object->db_setting->get( "fs_audio_preview_no_ff" ) )
  return;

  $hash = bof()->nest->user_input( "get", "mtid", "md5" );

  if ( !$hash )
  return;

  $track = bof()->object->m_track->select(
    array(
      "hash" => $hash
    ),
    array(
      "limit" => 1,
      "single" => true
    )
  );

  if ( !$track )
  return;

  if ( !$track["price"] && !$track["album_price"] )
  return;

  $source = bof()->object->m_track_source->select(
    array(
      "target_id" => $track["ID"],
      "type" => "audio"
    ),
    array(
      "order_by" => "quality",
      "order" => "ASC",
      "limit" => 1,
      "single" => true
    )
  );

  if ( !$source )
  return;

  if ( substr( $source["muse"]["type"][1]["address"], 0, strlen( web_address . "files/" ) ) == web_address . "files/" ){
    $location = ( base_root . "/files/" . substr( $source["muse"]["type"][1]["address"], strlen( web_address . "files/" ) ) );
  }

  if ( !is_file( $location ) ) return false;

  $mimeType = 'audio/mpeg';
  $filename = "preeview";

  $size = filesize($location);
  $time = date('r', filemtime($location));

  $fm = fopen($location, 'rb');

  $size = intval( $size * 0.2 );

  $begin = 0;
  $end = $size - 1;

  if ( isset( $_SERVER['HTTP_RANGE'] ) ? preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches) : false ){

    $begin = intval( $matches[1] );
    if ( !empty( $matches[2] ) ? $end+1 >= $matches[2] : false )
    $end = intval( $matches[2] );

  }

  if ( isset( $_SERVER['HTTP_RANGE'] ) ){
    header( 'HTTP/1.1 206 Partial Content' );
  } else {
    header( 'HTTP/1.1 200 OK' );
  }

  if ( isset( $_SERVER['HTTP_RANGE'] ) )
  header("Content-Range: bytes {$begin}-{$end}/{$size}");

  header("Content-Type: {$mimeType}");
  header("Cache-Control: public, must-revalidate, max-age=0");
  header("Pragma: no-cache");
  header("Accept-Ranges: bytes");
  header("Content-Length:" .( ( $end-$begin ) + 1) );
  header("Content-Encoding: none");
  header("Content-Disposition: inline; filename={$filename}");
  header("Content-Transfer-Encoding: binary");
  header("Last-Modified: {$time}");

  $cur = $begin;
  fseek($fm, $begin, 0);

  while( !feof($fm) && $cur <= $end && ( connection_status() == 0 ) ){

    print fread($fm, min(1024 * 64, ($end - $cur) + 1));
    ob_flush();
    flush();
    $cur += 1024 * 64;

  }

  die;

}

?>
