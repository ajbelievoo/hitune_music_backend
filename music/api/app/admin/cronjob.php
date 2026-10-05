<?php

require_once( dirname(dirname(dirname(dirname(__FILE__)))) . "/api/app/config.php" );

require_once( bof_root . "/loader.php" );

$bof_instance = new BusyOwlFramework(array(
  "name" => "bof_admin",
  "plugins" => array(
    "id3" => [],
    "ffmpeg" => [],
    "chapar" => [],
    "pgt" => [],
    "google-translate" => [],
    "ai" => []
  )
));

function bof(){
  global $bof_instance;
  return $bof_instance;
}

bof()->__setup();

require_once( root . "/app/admin/_setup/endpoint_groups.php" );
require_once( root . "/app/admin/_setup/endpoints.php" );
require_once( root . "/app/admin/_setup/classes.php" );
require_once( root . "/app/admin/_setup/objects.php" );
require_once( root . "/app/admin/_setup/db.php" );
require_once( root . "/app/admin/_setup/parasites.php" );
require_once( root . "/app/admin/_setup/plugins.php" );

bof()->endpoint->set( "bot" );
bof()->execute->run();
bof()->response->display();

?>
