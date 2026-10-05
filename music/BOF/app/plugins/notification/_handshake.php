<?php

if ( !defined( "bof_root" ) ) die;

$bof->object->core_files->add_key(
  "class",
  "notification",
  dirname(__FILE__)."/classes/class_notification.php"
);

$bof->object->core_files->add_key(
  "object",
  "notification",
  dirname(__FILE__)."/objects/object_notification.php"
);

$bof->notification->set_key( "push_sender_id", !empty( $args["push_sender_id"] ) ? $args["push_sender_id"] : null );
$bof->notification->set_key( "push_server_api_key", !empty( $args["push_server_api_key"] ) ? $args["push_server_api_key"] : null );

?>
