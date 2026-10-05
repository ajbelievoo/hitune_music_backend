<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_spotify_scrapper", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "spotify_scrapper",
  bof_spotify_scrapper . "/classes/class_spotify_scrapper.php"
);

$bof->spotify_scrapper->setup();

?>
