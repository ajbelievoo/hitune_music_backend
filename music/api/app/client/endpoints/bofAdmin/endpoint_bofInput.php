<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofInput( $loader, $excuter, $args ){
  return $loader->bofInput->execute( substr( $loader->request->get_requested_url(), strlen( "bofInput/" ), -1 ) );
}

?>
