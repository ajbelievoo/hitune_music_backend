<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_change_currency( $loader, $excuter, $args ){

  $code = $loader->nest->user_input( "post", "code", "string" );

  if ( $code ){

    $checkCode = $loader->object->currency->select(
      array(
        "iso_code" => $code,
        "active" => 1
      )
    );

    if ( $checkCode ){
      $loader->session->set( "currency", $code, true );
      $loader->api->set_message( "ok" );
    }

  }

}

?>
