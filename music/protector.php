<?php

// Protected file delivery — standalone (no BOF bootstrap).
// The framework boot was unreliable here: an early warning cascaded through
// BOF's error_inspector into a fatal (500), and its fall() callback renders
// the site 404 page — both fatal for media streaming. This script only
// needs DB + request metadata, so it loads config constants directly.

require_once( dirname(__FILE__) . "/api/app/config.php" );

function pdie( $reason ){
  header('HTTP/1.1 503 Service Temporarily Unavailable');
  header('Status: 503 Service Temporarily Unavailable');
  die( $reason );
}

// Long streams must not be killed by max_execution_time
@set_time_limit( 0 );

$path        = isset( $_GET["path"] )      ? (string) $_GET["path"]      : null;
$protected   = isset( $_GET["protected"] ) ? $_GET["protected"] === "yes" : false;
$object_type = isset( $_GET["ot"] )        ? (string) $_GET["ot"]        : null;
$object_hash = isset( $_GET["oh"] )        ? (string) $_GET["oh"]        : null;
$source_hash = isset( $_GET["sid"] )       ? (string) $_GET["sid"]       : null;
$key1        = isset( $_GET["key1"] )      ? (string) $_GET["key1"]      : null;
$key2        = isset( $_GET["key2"] )      ? (string) $_GET["key2"]      : null;
$key3        = isset( $_GET["key3"] )      ? (string) $_GET["key3"]      : null;

if ( $object_hash !== null && !preg_match( "/^[a-f0-9]{32}$/i", $object_hash ) ) $object_hash = null;
if ( $source_hash !== null && !preg_match( "/^[a-f0-9]{32}$/i", $source_hash ) ) $source_hash = null;
foreach( [ "key1", "key2", "key3" ] as $_k )
  if ( $$_k !== null && !preg_match( "/^[a-f0-9]{32}$/i", $$_k ) ) $$_k = null;

if ( $path !== null && ( strpos( $path, ".." ) !== false || strpos( $path, "\0" ) !== false ) )
$path = null;

// validate url — the grant is bound to the exact /files/protected/<path> URL
$_protocol = ( ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) || ( !empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 ) ) ? "https://" : "http://";
$_host = !empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : "";
$_url  = $_protocol . $_host . urldecode( (string) parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : "", PHP_URL_PATH ) );
$_url2 = $_protocol . $_host . "/files/protected/" . $path;

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

  // Access is verified for EVERY request — the old 15-85% mid-file window
  // skipped the DB check and let anyone pull the middle of any file.
  $path_hash = md5( $_url );

  $mysqli = @new mysqli( db_host, db_user, db_pass, db_name );
  if ( $mysqli->connect_errno )
  pdie("no_access");

  $stmt = $mysqli->prepare(
    "SELECT action, user_agent, user_ip, time_expire FROM `_bof_cache_files_access`
     WHERE object_type = ? AND object_hash = ? AND source_hash = ? AND path_hash = ?
       AND key1 = ? AND key2 = ? AND key3 = ? LIMIT 1"
  );

  if ( !$stmt ){
    $mysqli->close();
    pdie("no_access");
  }

  $stmt->bind_param( "sssssss", $object_type, $object_hash, $source_hash, $path_hash, $key1, $key2, $key3 );
  $stmt->execute();
  $item = $stmt->get_result() ? $stmt->get_result()->fetch_assoc() : null;
  $stmt->close();
  $mysqli->close();

  if ( empty( $item ) )
  pdie("no_access");

  if ( $item["action"] == "stream" ){
    $lock_ip = false;
    $lock_agent = true;
  }
  else {
    $lock_ip = $lock_agent = true;
  }

  if ( !empty( $lock_agent ) ){
    $ua = isset( $_SERVER["HTTP_USER_AGENT"] ) ? $_SERVER["HTTP_USER_AGENT"] : "";
    if ( $item["user_agent"] != $ua )
    pdie("no_access");
  }

  if ( !empty( $lock_ip ) ){
    $ip = isset( $_SERVER["REMOTE_ADDR"] ) ? $_SERVER["REMOTE_ADDR"] : "";
    if ( $item["user_ip"] != $ip )
    pdie("no_access");
  }

  if ( !empty( $item["time_expire"] ) ){
    if ( time() > strtotime( $item["time_expire"] ) )
    pdie("expired");
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

  $fm = fopen( $file_path, 'rb' );
  $cur = $http_header_range_begin;
  fseek( $fm, $http_header_range_begin, 0 );

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
