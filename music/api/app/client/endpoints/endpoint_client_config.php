<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_client_config( $loader, $excuter, $args ){

  $loader->api->set_message( "ok", $loader->client_config->get() );

}

?>
 
