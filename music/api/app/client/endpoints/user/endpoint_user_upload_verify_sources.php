<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_upload_verify_sources( $loader, $excuter, $args ){

  $log = [
    "time" => date("Y-m-d H:i:s"),
    "post" => $_POST,
    "headers" => getallheaders()
  ];
  file_put_contents( dirname(dirname(__FILE__)) . "/upload_verify_sources_debug.log", json_encode($log, JSON_PRETTY_PRINT) . "\n", FILE_APPEND );

  $given = $loader->nest->user_input( "post", "given_data", "json" );
  $content = $loader->nest->user_input( "post", "content_data", "json" );
  $source = $loader->nest->user_input( "post", "source_data", "json" );

  try {
    $verify = $loader->upload->setup()->verify_sources( $content, $source, $given );
  } catch( exception $err ){
    $loader->api->set_error( $err->getMessage(), [ "err" => $err->getMessage() ] );
    return;
  }

  $loader->api->set_message( "ok", $verify );

}

?>
