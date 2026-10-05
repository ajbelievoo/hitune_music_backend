<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_report( $loader, $excuter, $args ){

  $data = $loader->nest->user_input( "post", "data", "json" );
  $source = $loader->nest->user_input( "post", "source", "json" );
  if ( ( $data && $source ) ? ( is_array( $data ) && is_array( $source ) ) : false ){
    if ( !empty( $data["ot"] ) && !empty( $data["hash"] && !empty( $source["type"][1]["_report"] ) && !empty( $source["type"][0] ) && !empty( $source["type"][1]["_hash"] ) ) ?
    ( $loader->nest->validate( $data["ot"], "bofClient_object", [ "has_button" => "source" ] ) ) &&
    ( $loader->nest->validate( $data["hash"], "md5" ) ) &&
    ( $loader->nest->validate( $source["type"][1]["_report"], "md5" ) ) &&
    ( $loader->nest->validate( $source["type"][1]["_hash"], "md5" ) ) &&
    ( $loader->nest->validate( $source["type"][0], "string" ) )
  : false )
    {

      $object_type = $data["ot"];
      $object_hash = $data["hash"];
      $source_hash = $source["type"][1]["_hash"];
      $source_type = $source["type"][0];
      $report_hash = $source["type"][1]["_report"];
      $object = $loader->object->__get( $object_type );

      $the_item = $object->select(["hash"=>$object_hash],["_eq"=>["sources"=>["limit"=>10]]]);

      if ( $the_item ? !empty( $the_item["bof_dir_sources"] ) : false ){
        foreach( $the_item["bof_dir_sources"] as $_source ){
          if ( ( $_source["hash"] == $source_hash && $_source["type"] == $source_type ) ? 
            md5( $_source["hash"] . sign_code ) === $report_hash
          : false ){

            if ( bof()->db->_select( array(
              "table" => "_u_reports",
              "columns" => "COUNT(*) as c",
              "where" => array(
                [ "user_ip", "=", bof()->request->get_userIP()["string"] ],
                [ "time_add", ">", "SUBDATE( now(), INTERVAL 1 DAY )", true ],
              ),
              "single" => true,
              "limit" => 1
            ) )["c"] >= bof()->object->db_setting->get( "rep_lim_id", 10 ) )
            break;

            if ( bof()->db->_select( array(
              "table" => "_u_reports",
              "columns" => "COUNT(*) as c",
              "where" => array(
                [ "user_ip", "=", bof()->request->get_userIP()["string"] ],
                [ "object_type", "=", $object_type ],
                [ "object_id", "=", $the_item["ID"] ],
              ),
              "single" => true,
              "limit" => 1
            ) )["c"] >= bof()->object->db_setting->get( "rep_lim_ii", 1 ) )
            break;

            if ( bof()->user->get()->ID ? 
            (
              bof()->db->_select( array(
                "table" => "_u_reports",
                "columns" => "COUNT(*) as c",
                "where" => array(
                  [ "user_id", "=", bof()->user->get()->ID ],
                  [ "object_type", "=", $object_type ],
                  [ "object_id", "=", $the_item["ID"] ],
                ),
                "single" => true,
                "limit" => 1
              ) )["c"] >= bof()->object->db_setting->get( "rep_lim_ui", 1 )
            )
            : false )
            break;

            if ( $object->method_exists( "stats_columns" ) ){
              $stat_columns = $object->stats_columns();
              if ( !empty( $stat_columns ) ? in_array( "muse_report", $stat_columns, true ) : false ){
                if ( $the_item ){
                  $object->update(
                    array(
                      "hash" => $object_hash
                    ),
                    array(
                      "s_muse_report" => $the_item["s_muse_report"] ? $the_item["s_muse_report"] + 1 : 1
                    )
                  );
                }
              }
            }

            bof()->db->_insert( array(
              "table" => "_u_reports",
              "set" => array(
                [ "user_id", bof()->user->get()->ID ? bof()->user->get()->ID : null, true ],
                [ "user_ip", bof()->request->get_userIP()["string"] ],
                [ "object_type", $object_type ],
                [ "object_id", $the_item["ID"] ],
                [ "source_id", $_source["ID"] ]
              )
            ) );

            $rep_args = array(
              "object_type" => $object_type,
              "object_id" => $the_item["ID"],
              "source_id" => $_source["ID"],
              "item" => $the_item,
              "source" => $_source
            );
            $rep = null;
            $rep = bof()->call( "_custom", "muse_report", $rep_args, $rep );

            $loader->api->set_message( "ok", array(
              "rep" => $rep
            ) );

            return;
          }
        }
      }
      
    }

  }

  $loader->api->set_error( "failed", [ "output_args" => [ "turn" => false ] ] );
}

?>
