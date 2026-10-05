<?php

if ( !defined( "bof_root" ) ) die;

define( "chapar_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "chapar",
  chapar_plugin_root . "/classes/class_chapar.php"
);

$bof->object->core_files->add_key(
  "class",
  "chapar_email",
  chapar_plugin_root . "/classes/class_chapar_email.php"
);

$bof->object->core_files->add_key(
  "class",
  "chapar_push",
  chapar_plugin_root . "/classes/class_chapar_push.php"
);

$bof->object->core_files->add_key(
  "class",
  "chapar_admin",
  chapar_plugin_root . "/classes/class_chapar_admin.php"
);

$bof->object->core_files->add_object( "notification", chapar_plugin_root . "/objects/object_notification.php" );
$bof->object->core_files->add_object( "user_notification", chapar_plugin_root . "/objects/object_user_notification.php" );

?>
