<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_stats( $loader, $excuter, $args ){

  $loader->api->set_message( !empty( $loader->session->getAll()["name"] ) ? $loader->session->getAll()["name"] : null );

}

?>
