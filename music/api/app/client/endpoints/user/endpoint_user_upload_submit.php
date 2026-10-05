<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_upload_submit( $loader, $excuter, $args ){

  $log = [
    "time" => date("Y-m-d H:i:s"),
    "post" => $_POST,
    "headers" => getallheaders()
  ];
  file_put_contents( dirname(dirname(__FILE__)) . "/upload_submit_debug.log", json_encode($log, JSON_PRETTY_PRINT) . "\n", FILE_APPEND );

  try {
    $verify = $loader->upload->setup()->verify_submit();
  } catch( bofException|Exception $err ){
    $loader->api->set_error(
      $err->getMessage(),
      array_merge(
        method_exists( $err, "getExtra") ? $err->getExtra() : [],
        [ "output_args" => [ "turn" => false ] ]
      )
    );
    return;
  }

  $loader->api->set_message( "ok", $verify );

}

?>
