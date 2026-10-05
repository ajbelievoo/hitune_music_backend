<?php

if ( !defined( "bof_root" ) ) die;

define( "social_login_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "social_login",
  social_login_plugin_root."/classes/class_social_login.php"
);

?>
