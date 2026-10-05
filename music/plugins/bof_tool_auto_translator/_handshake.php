<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_tool_google_translator", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "tool_google_translator",
  bof_tool_google_translator . "/classes/class_tool_google_translator.php"
);

$bof->tool_google_translator->setup();

?>
