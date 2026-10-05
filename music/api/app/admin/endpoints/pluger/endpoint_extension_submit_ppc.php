<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_extension_submit_ppc( $loader, $excuter, $args ){

  $ppc = $loader->nest->user_input( "post", "ppc", "string", array(
    "strict" => true,
    "strict_regex" => "/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i",
    "strict_regex_raw" => true
  ) );

  if ( !$ppc ){
    $loader->api->set_error( "invalid code" );
    return;
  }

  try {
    $request = $loader->boac->submit_ppc( $ppc );
    $loader->api->set_message( $request );
  } catch( exception $err ){
    $loader->api->set_error( $err->getMessage() );
  }

}

?>
