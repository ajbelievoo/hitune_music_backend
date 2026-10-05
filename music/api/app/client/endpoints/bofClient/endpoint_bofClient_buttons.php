<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofClient_buttons( $loader, $excuter, $args ){
  $loader->bofClient->_ob( substr( $loader->request->get_requested_url(), strlen( "bofClient/buttons/" ), -1 ) );
}

?>
