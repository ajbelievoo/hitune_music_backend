<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofClient_list( $loader, $excuter, $args ){
  $loader->bofClient->_ol( substr( $loader->request->get_requested_url(), strlen( "bofClient/list/" ), -1 ) );
}

?>
