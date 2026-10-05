<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_client_config( $loader, $excuter, $args ){
  bof()->client_config->endpoint();
}

?>
