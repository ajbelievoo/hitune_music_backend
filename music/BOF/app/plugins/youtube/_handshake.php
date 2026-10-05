<?php

if ( !defined( "bof_root" ) ) die;

$bof->object->core_files->add_key(
  "class",
  "youtube",
  dirname(__FILE__)."/classes/class_youtube.php"
);

$bof->object->core_files->add_key(
  "class",
  "youtube_piped",
  dirname(__FILE__)."/classes/class_youtube_piped.php"
);

?>
