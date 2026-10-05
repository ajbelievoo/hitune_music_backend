<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_auth( $loader, $excuter, $args ){
  $loader->user_auth->endpoint();
}

?>
