<?php

if ( !defined( "bof_root" ) ) die;

define( "google_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "google_api_helper",
  google_plugin_root."/classes/class_google_api_helper.php"
);

?>
