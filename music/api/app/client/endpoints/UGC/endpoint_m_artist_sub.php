<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_m_artist_sub( $loader, $excuter, $args ){

  // Force object_type for this specific endpoint
  $_POST["object_type"] = "m_artist";
  
  // Call the main subscribe logic
  require_once( root . "/app/client/endpoints/UGC/endpoint_subscribe.php" );
  return endpoint_subscribe( $loader, $excuter, $args );

}

?>
