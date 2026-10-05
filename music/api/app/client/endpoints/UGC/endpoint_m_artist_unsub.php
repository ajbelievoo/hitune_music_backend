<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_m_artist_unsub( $loader, $excuter, $args ){

  // Force object_type for this specific endpoint
  $_POST["object_type"] = "m_artist";
  
  // Call the main unsubscribe logic
  require_once( root . "/app/client/endpoints/UGC/endpoint_unsubscribe.php" );
  return endpoint_unsubscribe( $loader, $excuter, $args );

}

?>
