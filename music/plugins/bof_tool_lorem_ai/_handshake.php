<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_lorem_ai", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "lorem_ai",
  bof_lorem_ai . "/classes/class_lorem_ai.php"
);

$bof->lorem_ai->setup();

?>
