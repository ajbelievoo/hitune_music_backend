<?php

if ( !defined( "bof_root" ) ) die;

define( "id3_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "id3",
  id3_plugin_root."/classes/class_id3.php"
);

?>
