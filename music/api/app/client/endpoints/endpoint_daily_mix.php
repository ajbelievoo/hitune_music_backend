<?php

if ( !defined( "bof_root" ) ) die;

require_once( dirname(__FILE__) . "/endpoint_recommendations.php" );

function endpoint_daily_mix( $loader, $excuter, $args ){

  $items = __bof_rec_track_items( $loader, array(), array(
    "order_by" => "RAND()",
    "order" => "",
    "limit" => 20
  ) );

  if ( empty( $items ) )
  $items = __bof_rec_track_items( $loader );

  $loader->api->set_message( "ok", array(
    "items" => $items
  ) );

}

?>
