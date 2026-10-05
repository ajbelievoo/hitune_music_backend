<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_notifications( $loader, $excuter, $args ){

  $nots = bof()->chapar_admin->get_endpoint();

  $notTime = bof()->object->db_setting->get( "last_seen_nots" );

  if ( $nots["time"] ? $nots["time"] > $notTime : false ){
    $hasUpdate = true;
  }

  bof()->object->db_setting->set( "last_seen_nots", time() );

  $loader->api->set_message( "ok", array(
    "has_update" => !empty( $hasUpdate ),
    "has_next" => $nots["next"],
    "ups" => $nots["list"],
    "t1" => $notTime,
    "t2" => $nots["time"]
  ) );

}

?>
