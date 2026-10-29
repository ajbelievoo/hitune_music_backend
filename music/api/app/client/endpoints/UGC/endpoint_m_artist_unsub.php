<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_m_artist_unsub( $loader, $excuter, $args ){

  // Force object_type for this specific endpoint
  $_POST["object_type"] = "m_artist";

  // App sends {hash, sub:1|0} toggle — route sub=1 back to subscribe
  if ( isset( $_POST["sub"] ) && ( $_POST["sub"] === "1" || $_POST["sub"] === 1 ) ){
    require_once( root . "/app/client/endpoints/UGC/endpoint_subscribe.php" );
    return endpoint_subscribe( $loader, $excuter, $args );
  }

  // Call the main unsubscribe logic
  require_once( root . "/app/client/endpoints/UGC/endpoint_unsubscribe.php" );
  return endpoint_unsubscribe( $loader, $excuter, $args );

}

?>
