<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_podcast_cover_downloader", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "podcast_cover_downloader",
  bof_podcast_cover_downloader . "/classes/class_podcast_cover_downloader.php"
);

$bof->podcast_cover_downloader->setup();

?>
