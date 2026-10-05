<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_notification_mark_read( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $notification_id = $loader->nest->user_input( "post", "notification_id", "int", [ "min" => 1 ] );
  if ( !$notification_id )
  $notification_id = $loader->nest->user_input( "post", "id", "int", [ "min" => 1 ] );

  if ( !$notification_id ) return;

  $loader->db->_update( array(
    "table" => "_u_notifications",
    "where" => array(
      [ "ID", "=", $notification_id ],
      [ "user_id", "=", $userID ]
    ),
    "set" => array(
      [ "time_seen", $loader->general->mysql_timestamp() ]
    )
  ) );

  $loader->api->set_message( "ok" );

}

?>
