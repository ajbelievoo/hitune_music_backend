<?php

if ( !defined( "bof_root" ) ) die;

define( "ffmpeg_path", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "ffmpeg",
  ffmpeg_path . "/classes/class_ffmpeg.php"
);

?>
