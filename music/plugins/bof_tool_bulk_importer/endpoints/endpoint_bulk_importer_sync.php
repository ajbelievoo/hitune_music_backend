<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bulk_importer_sync( $loader, $excuter, $args ){

  bof()->bulk_importer->clean_covers();
  bof()->object->db_setting->set( "bulk_import_cs", json_encode([]), "json" );

  $loader->object->db_setting->set( "bulk_import_state", 1 );
  $loader->api->set_message( "ok");

}

?>
