<?php

if ( !defined( "bof_root" ) ) die;

define( "music_torrent_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "music_torrent",
  music_torrent_root . "/classes/class_music_torrent.php"
);

$bof->music_torrent->setup();

?>
