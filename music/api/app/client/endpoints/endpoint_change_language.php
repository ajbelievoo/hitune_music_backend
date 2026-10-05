<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_change_language( $loader, $excuter, $args ){

  $code = $loader->nest->user_input( "post", "code", "string" );

  if ( $code ){

    $checkCode = $loader->object->language->select(
      array(
        "code2" => $code,
        "_index" => 1
      )
    );

    if ( $checkCode ){
      $loader->session->set( "language", $code, true );
      $loader->api->set_message( "ok" );
    }

  }

}

?>
