<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_dashboard_highlights( $loader, $excuter, $args ){

  $highlights = $loader->highlights->getDashData();
  $loader->api->set_message( "ok", [ "items" => $highlights ] );

}

?>
