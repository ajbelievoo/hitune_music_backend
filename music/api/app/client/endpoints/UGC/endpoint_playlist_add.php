<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_add( $loader, $excuter, $args ){

  require_once( dirname(__FILE__) . "/endpoint_playlist_extend.php" );
  endpoint_playlist_extend( $loader, $excuter, $args );

}

?>
