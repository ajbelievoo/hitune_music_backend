<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_other_apps_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "other_apps",
  bof_other_apps_root . "/classes/class_other_apps.php"
);

$bof->other_apps->setup();

?>
