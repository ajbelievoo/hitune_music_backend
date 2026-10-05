<?php

require_once( dirname(__FILE__) . "/api/app/config.php" );
require_once( bof_root . "/loader.php" );

function pdie( $reason ){
  header('HTTP/1.1 503 Service Temporarily Unavailable');
  header('Status: 503 Service Temporarily Unavailable');
  die( $reason );
}

$bof_instance = new BusyOwlFramework(array(
  "name" => "bof_client",
  "plugins" => array()
));

function bof(){
  global $bof_instance;
  return $bof_instance;
}

bof()->__setup();


// requests
$path = bof()->nest->user_input( "get", "path", "string" );
$protected = bof()->nest->user_input( "get", "protected", "equal", [ "value" => "yes"] );
$object_type = bof()->nest->user_input( "get", "ot", "string" );
$object_hash = bof()->nest->user_input( "get", "oh", "md5" );
$source_hash = bof()->nest->user_input( "get", "sid", "md5" );
$key1 = bof()->nest->user_input( "get", "key1", "md5" );
$key2 = bof()->nest->user_input( "get", "key2", "md5" );
$key3 = bof()->nest->user_input( "get", "key3", "md5" );

// validate url
$_protocol = ( ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$_url = $_protocol . $_SERVER['HTTP_HOST'] . urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$_url2 = $_protocol . $_SERVER["HTTP_HOST"] . "/files/protected/" . $path;

if (
  !empty( $path ) && !empty( $protected ) && !empty( $object_type ) && !empty( $object_hash ) && !empty( $source_hash ) && !empty( $key1 ) && !empty( $key2 ) && !empty( $key3 ) ?
    md5( $_url ) == md5( $_url2 )
  : false
){

  $file_path = base_root . "/files/protected/" . $path;

  if ( !is_file( $file_path ) )
  pdie("no_access");

  $filesize = filesize( $file_path );

  $http_header_range_begin = 0;
  $http_header_range_end = $filesize-1;
  if ( isset( $_SERVER['HTTP_RANGE'] ) ? preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches) : false ){

    $http_header_range = true;

    if ( !empty( $matches[1] ) ? $matches[1] < $filesize : false )
    $http_header_range_begin = intval( $matches[1] );

    if ( !empty( $matches[2] ) ? $filesize >= $matches[2] : false )
    $http_header_range_end = intval( $matches[2] );

  }

  if ( !empty( $http_header_range ) ? ( $http_header_range_begin / $filesize > .85 || $http_header_range_begin / $filesize < .15 ) : true ){

    $path_hash = md5( $_url );
    require_once( root . "/app/admin/_setup/db.php" );

    $check_db = bof()->db->_select(
      array(
        "table" => "_bof_cache_files_access",
        "where" => array(
          [ "object_type", "=", $object_type ],
          [ "object_hash", "=", $object_hash ],
          [ "source_hash", "=", $source_hash ],
          [ "path_hash", "=", $path_hash ],
          [ "key1", "=", $key1 ],
          [ "key2", "=", $key2 ],
          [ "key3", "=", $key3 ],
        )
      ),
    );



    if ( empty( $check_db ) )
    pdie("no_access");

    $item = $check_db[0];

    if ( $item["action"] == "stream" ){
      $lock_ip = false;
      $lock_agent = true;
    }
    else {
      $lock_ip = $lock_agent = true;
    }

    if ( !empty( $lock_agent ) ){
      if ( $item["user_agent"] != bof()->request->get_userAgent()["string"] )
      pdie("no_access");
    }

    if ( !empty( $lock_ip ) ){
      if ( $item["user_ip"] != bof()->request->get_userIP()["string"] )
      pdie("no_access");
    }

    if ( !empty( $item["time_expire"] ) ){
      if ( time() > strtotime( $item["time_expire"] ) )
      pdie("expired");
    }

  }

  $_ext = pathinfo( $file_path, PATHINFO_EXTENSION );

  if ( $_ext == "png" || $_ext == "gif" || $_ext == "jpeg" || $_ext == "jpg" ){

    if ( $_ext == "png" )
    $im = imagecreatefrompng( $file_path );
    elseif( $_ext == "gif" )
    $im = imagecreatefromgif( $file_path );
    else
    $im = imagecreatefromjpeg( $file_path );

    imagesavealpha($im, true);

    header("Content-type: image/png");
    imagepng($im);
    imagedestroy($im);
    return;

  }
  if ( $_ext == "zip" ){

    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=" . pathinfo( $file_path, PATHINFO_BASENAME ) );
    header("Content-Length: " . filesize( $file_path ) );
    readfile( $file_path );
    exit;

  }

  header( !empty( $http_header_range ) ? 'HTTP/1.1 206 Partial Content' : 'HTTP/1.1 200 OK' );

  if ( !empty( $http_header_range ) )
  header("Content-Range: bytes {$http_header_range_begin}-{$http_header_range_end}/{$filesize}");

  header("Content-Type: " . ( !empty( $http_header_range ) ? "application/octet-stream" : mime_content_type( $file_path ) ) );
  header("Cache-Control: public, must-revalidate, max-age=0");
  header("Pragma: no-cache");
  header("Accept-Ranges: bytes");
  header("Content-Length:" .( ( $http_header_range_end-$http_header_range_begin ) + 1) );
  header("Content-Disposition: attachment; filename=" . pathinfo( $file_path, PATHINFO_BASENAME ) );

  $begin = 0;
  $fm = fopen( $file_path, 'rb' );
  $cur = $http_header_range_begin;
  fseek( $fm, $begin, 0 );

  while(
    !feof( $fm ) &&
    $cur <= $http_header_range_end &&
    ( connection_status() == 0 )
  ){

    echo fread($fm, min( 1024 * 64, ( $http_header_range_end - $cur ) + 1 ) );
    ob_flush();
    flush();
    $cur += 1024 * 64;

  }

}
else {
  pdie("no_access");
}

?>
