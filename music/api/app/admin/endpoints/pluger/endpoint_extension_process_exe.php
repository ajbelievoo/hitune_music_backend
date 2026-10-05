<?php

if ( !defined( "bof_root" ) ) die;

set_time_limit(0);
ini_set('max_execution_time', 0);

function endpoint_extension_process_exe( $loader, $excuter, $args ){

  $ID = $loader->nest->user_input( "post", "ID", "int" );

  if ( $ID ){

    $process = $loader->db->_select( array(
      "table" => "_bof_plug_processes",
      "where" => array(
        [ "ID", "=", $ID ],
        [ "user_id", "=", $loader->user->get()->ID ],
        [ "time_start", null, null, true ],
      ),
      "limit" => 1,
      "single" => true
    ) );

    if ( $process ){
      $loader->db->query( "UPDATE _bof_plug_processes SET time_start = now() WHERE ID = '{$ID}'" );
      $loader->plug->process_execute( $process );
    }

  }

}

?>
