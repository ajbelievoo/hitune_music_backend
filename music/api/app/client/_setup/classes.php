<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

bof()->object->core_files->add_class( "client_config", root . "/app/client/classes/class_client_config.php" );
bof()->object->core_files->add_class( "search", bof_root . "/app/core/classes/class_search.php" );
bof()->object->core_files->add_class( "share", root . "/app/client/classes/class_share.php" );
bof()->object->core_files->add_class( "muse_infinite", root . "/app/client/classes/class_muse_infinite.php" );
bof()->object->core_files->add_class( "bofForm", root . "/app/client/classes/class_bofForm.php" );

// extend
bof()->extend(
  "class",
  "request",
  root . "/app/client/classes/class_request_extend.php",
  "request_extend"
);

bof()->extend(
  "class",
  "session",
  bof_root . "/app/core/classes/class_session_extend.php",
  "session_extend"
);

?>
