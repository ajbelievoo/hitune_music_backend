<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_tool_spotify_multi_accounts", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "tool_spotify_multi_accounts",
  bof_tool_spotify_multi_accounts . "/classes/class_tool_spotify_multi_accounts.php"
);

$bof->tool_spotify_multi_accounts->setup();

?>
