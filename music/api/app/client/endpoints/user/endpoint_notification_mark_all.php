<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_notification_mark_all( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $loader->object->user_notification->update(
    array(
      "user_id" => $userID,
      [ "time_seen", null, null, true ]
    ),
    array(
      "time_seen" => $loader->general->mysql_timestamp()
    )
  );

  $loader->api->set_message( "ok", array(
    "unseen" => 0
  ) );

}

?>
