<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_wikimedia_scrapper", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "wikimedia_scrapper",
  bof_wikimedia_scrapper . "/classes/class_wikimedia_scrapper.php"
);

$bof->wikimedia_scrapper->setup();

?>
