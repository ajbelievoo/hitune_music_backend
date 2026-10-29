<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_m_artist_sub( $loader, $excuter, $args ){

  // Force object_type for this specific endpoint
  $_POST["object_type"] = "m_artist";

  // App sends {hash, sub:1|0} on the same toggle endpoint — route sub=0 to unsubscribe
  if ( isset( $_POST["sub"] ) && ( $_POST["sub"] === "0" || $_POST["sub"] === 0 ) ){
    require_once( root . "/app/client/endpoints/UGC/endpoint_unsubscribe.php" );
    return endpoint_unsubscribe( $loader, $excuter, $args );
  }

  // Call the main subscribe logic
  require_once( root . "/app/client/endpoints/UGC/endpoint_subscribe.php" );
  return endpoint_subscribe( $loader, $excuter, $args );

}

?>
