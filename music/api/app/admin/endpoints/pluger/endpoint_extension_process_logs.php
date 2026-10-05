<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_extension_process_logs( $loader, $excuter, $args ){

  $ID = $loader->nest->user_input( "post", "ID", "int" );
  $logID = $loader->nest->user_input( "post", "logID", "int" );

  if ( !$ID ){
    if ( $loader->nest->user_input( "post", "ID", "equal", [ "value" => "script" ] ) ){
      $ID = bof()->object->db_setting->get( "self_update_pid" );
      $process = $loader->db->_select( array(
        "table" => "_bof_plug_processes",
        "where" => array(
          [ "ID", "=", $ID ],
        ),
        "limit" => 1,
        "single" => true
      ) );
    }
  }
  if ( $ID ){

    if ( empty( $process ) )
    $process = $loader->db->_select( array(
      "table" => "_bof_plug_processes",
      "where" => array(
        [ "ID", "=", $ID ],
        [ "user_id", "=", $loader->user->get()->ID ],
      ),
      "limit" => 1,
      "single" => true
    ) );

    if ( $process ){

      $logs_where = array(
        [ "process_id", "=", $ID ]
      );

      if ( $logID )
      $logs_where[] = [ "ID", ">", $logID ];

      $logs = $loader->db->_select( array(
        "table" => "_bof_plug_logs",
        "where" => $logs_where,
        "single" => false,
        "limit" => 100,
        "order" => "ASC",
        "order_by" => "ID"
      ) );

      if ( $logs ){

        $biggest = 0;
        foreach( $logs as &$log ){

          if ( $log["ID"] > $biggest )
          $biggest = $log["ID"];

          $log = array(
            "type" => $log["code"],
            "text" => $log["text"]
          );

        }

        $loader->api->set_message( "ok", array(
          "logs" => $logs,
          "logID" => $biggest,
        ) );
        return;

      }

      if ( $process["time_finish"] ){
        $loader->api->set_message( "ok", array(
          "finished" => true,
          "p_success" => $process["sta"] == 1
        ) );
        return;
      }

    }

  }

}

?>
