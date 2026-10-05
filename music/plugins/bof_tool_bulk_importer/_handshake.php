<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_bulk_importer", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "bulk_importer",
  bof_bulk_importer . "/classes/class_bulk_importer.php"
);

$bof->bulk_importer->setup();

?>
