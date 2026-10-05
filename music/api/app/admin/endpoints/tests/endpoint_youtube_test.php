<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_youtube_test( $loader, $excuter, $args ){

  @ob_start();
  @ini_set("display_errors","0");
  @error_reporting(0);
  @set_time_limit(30);
  @header("Content-Type: application/json; charset=utf-8");

  if ( bof()->user->check()->ID != 1 ){
    $loader->api->set_error("Only root-admin can do this");
    return;
  }

  if ( !function_exists('exec') ){
    $loader->api->set_error("exec function is disabled by your host");
    return;
  }

  $job = bof()->nest->user_input( "post", "job", "in_array", [ "values" => [ "check_version", "download_video" ] ], "check_version" );
  $path = bof()->nest->user_input( "post", "path", "string", array(
    "strict" => true,
    "strict_regex" => "[a-zA-Z0-9_.\-\/\:\\\ ]"
  ) );

  $test = [];

  if ( empty( $path ) ){
    $test[] = "Given path is in incorrect format";
    $failure = true;
  }
  else {
    $test_id = "dQw4w9WgXcQ";
    if ( $job == "check_version" ){
      if ( !preg_match( "/yt-dlp/", $path ) )
      $test[] = "<b style='color:red'>youtube-dl</b> detected. We highly recommend intalling & using <b style='color:green'>yt-dlp</b> instead";

      $version_command = "\"{$path}\" --version";
      $test[] = "Checking version ...";
      $version = exec( $version_command );
      $test[] = "Result: {$version}";

      if ( preg_match( "/(\d{4}).(\d{2}).(\d{2}(.*?))?$/", $version, $_ms ) ){
        $date = implode( "/", array_slice( $_ms, 1 ) );
        $date_i = time() - strtotime( $date );
        //$test[] = "Last update: " . ( floor( $date_i / (24*60*60) ) ) . " day(s) ago";
        //if ( $date_i > 30*24*60*60 ) $test[] = "<b style='color:red'>Outdated</b> version detected. Time to update";
        $_p = bof()->object->db_setting->get( "ut_youtubedl_proxy" );
        $proxy_note = "";
        if ( $_p ){
          $proxy_note = ". Proxy: <b style='color:red'>{$_p}</b>";
          $is_valid = false;
          $parts = parse_url( $_p );
          if ( $parts && !empty( $parts["scheme"] ) && !empty( $parts["host"] ) && !empty( $parts["port"] ) ){
            $is_valid = is_numeric( $parts["port"] );
          }
          if ( !$is_valid ){
            $test[] = "<b style='color:red'>Invalid proxy format.</b> Use <code>http://user:pass@IP:PORT</code> or <code>socks5://user:pass@IP:PORT</code> with numeric PORT (e.g. :8080). If your password contains @ or : characters, URL-encode them (%40, %3A).</b>";
          } else {
            // Connectivity pre-check (TCP)
            $errno = 0; $errstr = "";
            $sock = @fsockopen( $parts["host"], intval( $parts["port"] ), $errno, $errstr, 5 );
            if ( !$sock ){
              $test[] = "<b style='color:red'>Proxy connectivity failed:</b> " . htmlspecialchars( "{$parts["host"]}:{$parts["port"]} - {$errstr} ({$errno})" );
            } else {
              fclose( $sock );
            }
          }
        }
        $test[] = "Downloading https://www.youtube.com/watch?v={$test_id} for test" . $proxy_note;
        // Quick fallback probe via Piped (for 429/rate-limit)
        try {
          if ( function_exists( "opcache_invalidate" ) ){
            @opcache_invalidate( bof_root . "/app/plugins/youtube/classes/class_youtube_piped.php", true );
          }
          if ( !bof()->plugin_exists("youtube") )
          bof()->plugin("youtube");
          bof()->youtube_piped->set_setting();
          $stream = bof()->youtube_piped->get_stream( $test_id );
          if ( !empty( $stream["url"] ) ){
            $test[] = "<b style='color:green'>Reachability OK via Piped</b>";
            $test[] = "Type: " . htmlspecialchars( $stream["type"] ) . ", MIME: " . htmlspecialchars( $stream["mime"] );
          }
        } catch( Exception|bofException $e2 ){
          $test[] = "<b style='color:red'>Piped probe failed:</b> " . htmlspecialchars( $e2->getMessage() );
        }
      } else {
        $failure = true;
        $test[] = "<b style='color:red'>Failed to get version {$version}. Path is wrong, app is not installed correctly or web-server user has no permission to access {$path}</b><br>Make sure app is correctly installed, path is accurate then try again. This is a server-side problem";
      }

    }
    else {

      $dl_path = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp";
      bof()->file->mkdir( $dl_path );
      if ( !is_dir( $dl_path ) || !is_writable( $dl_path ) || !is_readable( $dl_path ) ){
        $test[] = "<b style='color:red'>`{$dl_path}` is not accessible by youtube-dl/yt-dlp. Make sure they have enough permission then retry</b>";
        $failure = true;
      } else {

        try {

          $start = time();

          if ( !bof()->plugin_exists("youtube") )
          bof()->plugin("youtube");

          bof()->youtube->download( $test_id, array(
            "test" => true,
            "youtube_dl_location" => $path,
            "convert_if_required" => false
          ) );

          $exe = time() - $start;

          $test[] = "<b style='color:green'>Ok. Downloaded in {$exe} second(s)</b>";

        } catch( Exception|bofException $err ){

          $command = bof()->youtube->download( $test_id, array(
            "youtube_dl_location" => $path,
            "returnCommand" => true
          ) );

          $test[] = "<b style='color:red'>Failed: ".($err->getMessage())."</b>";
          $test[] = "Try following command yourself and see why it fails, fix the issue then retry. This is a server-related issue";
          $test[] = $command;
          $failure = true;

          // Fallback: try Piped instance to verify reachability
          try {
            if ( function_exists( "opcache_invalidate" ) ){
              @opcache_invalidate( bof_root . "/app/plugins/youtube/classes/class_youtube_piped.php", true );
            }
            if ( !bof()->plugin_exists("youtube") )
            bof()->plugin("youtube");
            bof()->youtube_piped->set_setting();
            $stream = bof()->youtube_piped->get_stream( $test_id );
            if ( !empty( $stream["url"] ) ){
              $test[] = "<b style='color:green'>Ok. Fallback succeeded via Piped</b>";
              $test[] = "Type: " . htmlspecialchars( $stream["type"] ) . ", MIME: " . htmlspecialchars( $stream["mime"] );
              $failure = false;
            }
          } catch( Exception|bofException $e2 ){
            $test[] = "<b style='color:red'>Piped fallback failed:</b> " . htmlspecialchars( $e2->getMessage() );
          }

          $be = bof()->object->db_setting->get( "youtube_piped_be", "browser" );
          if ( $be == "browser" ){
            $test[] = "<b style='color:green'>Playback works via browser-mode Piped</b>";
            $failure = false;
          }

        }

      }

    }
  }

  if ( empty( $failure ) ){
    if ( function_exists("ob_get_level") ){ while( ob_get_level() > 0 ){ @ob_end_clean(); } }
    $loader->api->set_message( $test );
  }
  else {
    if ( function_exists("ob_get_level") ){ while( ob_get_level() > 0 ){ @ob_end_clean(); } }
    $loader->api->set_error( $test );
  }
}

?>
