<?php

if ( !defined( "bof_root" ) ) die;

define( "ai_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "ai",
  ai_plugin_root . "/classes/class_ai.php"
);

$bof->object->core_files->add_key(
  "class",
  "ai_service",
  ai_plugin_root . "/classes/class_ai_service.php"
);

bof()->ai->__reset();

?>
