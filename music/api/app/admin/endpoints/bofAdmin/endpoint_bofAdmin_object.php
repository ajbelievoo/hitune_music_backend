<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofAdmin_object( $loader, $excuter, $args ){
  $loader->bofAdmin->object( substr( $loader->request->get_requested_url(), strlen( "bofAdmin/object/" ), -1 ) );
}

?>
