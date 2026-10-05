<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofAdmin_list( $loader, $excuter, $args ){
  $loader->bofAdmin->object_list( substr( $loader->request->get_requested_url(), strlen( "bofAdmin/list/" ), -1 ) );
}

?>
