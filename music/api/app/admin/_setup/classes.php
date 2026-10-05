<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

// add
bof()->object->core_files->add_class( "highlights", root . "/app/admin/classes/class_highlights.php" );
bof()->object->core_files->add_class( "client_config", root . "/app/admin/classes/class_client_config.php" );

// extend
bof()->extend(
  "class",
  "request",
  root . "/app/admin/classes/class_request_extend.php",
  "request_extend"
);

bof()->extend(
  "class",
  "session",
  bof_root . "/app/core/classes/class_session_extend.php",
  "session_extend"
);

?>
