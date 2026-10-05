<?php

if ( !defined( "bof_root" ) ) die;

if ( empty( $args["tele_bot_name"] ) )
$bof->general->fall("Telegram plugin: Missing Name");

if ( empty( $args["tele_bot_key"] ) )
$bof->general->fall("Telegram plugin: Missing Key");

if ( empty( $args["tele_target_id"] ) )
$bof->general->fall("Telegram plugin: Missing Target");

$bof->object->core_files->add_key(
  "class",
  "telegram",
  dirname(__FILE__)."/classes/class_telegram.php"
);

$bof->telegram->set_name( $args["tele_bot_name"] );
$bof->telegram->set_key( $args["tele_bot_key"] );
$bof->telegram->set_target( $args["tele_target_id"] );

?>
