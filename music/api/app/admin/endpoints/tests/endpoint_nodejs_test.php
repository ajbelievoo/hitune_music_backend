<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_nodejs_test( $loader, $excuter, $args ){

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


  if ( $path ){

    $version_command = "\"{$path}\"" . ' -v';
    $test[] = "Checking version ...";
    $version = exec( $version_command );
    if ( empty( $version ) ) 
    $version = "<b style='color:red'>Not found, path is incorrect or Node.js is not installed. Server-related issue</b><br><br>Run this command yourself: `{$version_command}`";
    $test[] = "Version: <b>{$version}</b>";
    $test[] = exec( "\"{$path}\" " . ( dirname(__FILE__) . "/node_test.js" ) );
    $test[] = "If you can't see the success message above, the running node failed";

  }
  else {
    $test[] = "Given path is in incorrect format";
  }

  $loader->api->set_message( $test );

}

?>
