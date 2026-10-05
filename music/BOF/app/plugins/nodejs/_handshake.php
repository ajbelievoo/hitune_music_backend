<?php

if ( !defined( "bof_root" ) ) die;

$bof->object->core_files->add_key(
  "class",
  "nodejs",
  dirname(__FILE__)."/classes/class_nodejs.php"
);

?>
