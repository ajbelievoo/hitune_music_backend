<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_extra_gateways_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "extra_gateways",
  bof_extra_gateways_root . "/classes/class_extra_gateways.php"
);

$bof->extra_gateways->setup();

?>
