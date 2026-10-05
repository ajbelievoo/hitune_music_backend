<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_ffmpeg_test( $loader, $excuter, $args ){

  $type = bof()->nest->user_input( "post", "type", "in_array", [ "values" => [ "native", "static" ] ], "native" );

  if ( bof()->user->check()->ID != 1 ){
    $loader->api->set_error("Only root-admin can do this");
    return;
  }

  if ( !function_exists('proc_open') ){
    $loader->api->set_error("proc_open function is disabled by your host");
    return;
  }

  if ( !function_exists('exec') ){
    $loader->api->set_error("exec function is disabled by your host");
    return;
  }

  $path = bof()->nest->user_input( "post", "path", "string", array(
    "strict" => true,
    "strict_regex" => "[a-zA-Z0-9_.\-\/\:\\\ ]"
  ) );

  if ( $type == "static" ){
    $path = bof()->ffmpeg->getClient( $type );
  }

  $test = [];

  if ( is_file( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/output_for_ffmpeg_test.mp3" ) )
  unlink( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/output_for_ffmpeg_test.mp3" );

  if ( $path ){
    $version_command = "\"{$path}\"" . ' -version';
    $test[] = "Checking version ...";
    $version = exec( $version_command );
    if ( empty( $version ) ) $version = "<b style='color:red'>Not found, path is incorrect or ffmpeg is not installed. Server-related issue</b><br><br>Run this command yourself: `{$version_command}`";
    $test[] = "Version: <b>{$version}</b>";
    $test[] = "Converting sample file for test";
    
    if ( !is_dir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/" ) )
    mkdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/" );
    if ( is_file( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/output_for_ffmpeg_test.mp3" ) )
    unlink(  base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/output_for_ffmpeg_test.mp3" );

    $tc = bof()->ffmpeg->convert_to_mp3( bof_root . "/app/core/third/sample_for_ffmpeg_test.wav", null, array(
      "force_client" => $type,
      "force_path" => $type == "native" ? $path : null,
      "dir" => base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp/",
      "name" => "output_for_ffmpeg_test"
    ) );
    if ( !$tc ){
      $test[] = "Converting failed. FFmpeg is not working";
      $test[] = "Command: <b>". bof()->ffmpeg->getCommand()."</b>, output:";
      $test[] = bof()->ffmpeg->getError();
    } else {
      $test[] = "Everything went ok. <a href='".str_replace( base_root, web_address, realpath( $tc ) )."' target='_blank' style='text-decoration:underline'>click here</a> to check the output, if you can hear Gaben, FFmpeg is working";
    }
  }
  else {
    $test[] = "Given path is in incorrect format";
  }

  $loader->api->set_message( $test );

}

?>
