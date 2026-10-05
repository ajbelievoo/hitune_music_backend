<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_youtube_scrapper", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "youtube_scrapper",
  bof_youtube_scrapper . "/classes/class_youtube_scrapper.php"
);

$bof->youtube_scrapper->setup();

?>
