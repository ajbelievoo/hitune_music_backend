<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_music_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "music",
  bof_music_root . "/classes/class_music.php"
);

$bof->music->setup();

?>
