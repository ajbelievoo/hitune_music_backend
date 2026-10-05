<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofClient_browse( $loader, $excuter, $args ){
  $loader->bofClient->_browse( substr( $loader->request->get_requested_url(), strlen( "bofClient/browse/" ), -1 ) );
}

?>
