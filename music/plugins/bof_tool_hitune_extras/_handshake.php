<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_hitune_extras", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "hitune_extras",
  bof_hitune_extras . "/classes/class_hitune_extras.php"
);

$bof->object->core_files->add_key(
  "class",
  "hitune_saavn",
  bof_hitune_extras . "/classes/class_hitune_saavn.php"
);

$bof->object->core_files->add_key(
  "class",
  "developer_api",
  bof_hitune_extras . "/classes/class_developer_api.php"
);

$bof->object->core_files->add_key(
  "class",
  "iyolme",
  bof_hitune_extras . "/classes/class_iyolme.php"
);

$bof->object->core_files->add_key(
  "class",
  "hitune_ai",
  bof_hitune_extras . "/classes/class_hitune_ai.php"
);

$bof->hitune_extras->setup();

?>
