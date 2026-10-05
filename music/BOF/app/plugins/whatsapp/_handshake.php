<?php

if ( !defined( "bof_root" ) ) die;

if ( empty( $args["wa_token"] ) )
  $bof->general->fall("WhatsApp plugin: Missing Token");

if ( empty( $args["wa_phone_id"] ) )
  $bof->general->fall("WhatsApp plugin: Missing Phone ID");

$bof->object->core_files->add_key(
  "class",
  "whatsapp",
  dirname(__FILE__)."/classes/class_whatsapp.php"
);

$bof->whatsapp->set_token( $args["wa_token"] );
$bof->whatsapp->set_phone_id( $args["wa_phone_id"] );

?>