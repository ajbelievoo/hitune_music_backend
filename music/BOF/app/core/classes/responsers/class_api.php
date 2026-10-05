<?php

if ( !defined( "bof_root" ) ) die;

class api {

  public function set_message( $texts, $args=[], $success=true ){

    $turn = bof()->getName() == "bof_client";
    $output_args = [];
    if ( !empty( $args["output_args"] ) ){
      $output_args = $args["output_args"];
      unset( $args["output_args"] );
    }
    extract( $output_args );

    $texts = is_array( $texts ) ? $texts : [ $texts ];

    if ( $turn ){
      foreach( $texts as &$text ){
        $text = bof()->object->language->turn( $text, $args, [ "lang" => "users" ] );
      }
    }

    $output = array(
      "success" => $success,
      "messages" => is_array( $texts ) ? $texts : [ $texts ]
    );

    if ( bof()->object->core_setting->get( "debug" ) &&
    bof()->object->core_setting->get( "debug_report" ) ){

      $db_stats = bof()->defined("db") ? bof()->db->__get_stats() : null;
      $curl_stats = bof()->curl->__get_stats();
      $exe_time = microtime( true ) - time_ini;
      if ( !empty( $db_stats["tables"] ) ){
        foreach( $db_stats["tables"] as &$table ){
          $table["time"] = round( $table["time"], 3 );
        }
      }
      $output["report"] = array(
        "db_count" => $db_stats ? $db_stats["count"] : null,
        "db_time" => $db_stats ? round( $db_stats["time"], 3 ) : null,
        "db_cache_count" => $db_stats ? $db_stats["cache_count"] : null,
        "db_cache_time" => $db_stats ? round( $db_stats["cache_time"], 3 ) : null,
        "db_tables" => $db_stats ? $db_stats["tables"] : null,
        "db_queries" => $db_stats ? $db_stats["queries"] : null,
        "curl_count" => $curl_stats["count"],
        "curl_cached" => $curl_stats["cached"],
        "curl_size" => $curl_stats["size"],
        "curl_time" => $curl_stats["time"],
        "exe_time" => round( $exe_time, 3 ),
        "exe_time_ndb" => $db_stats ? round( $exe_time - $db_stats["time"], 3 ) : round( $exe_time, 3 ),
        "bof_proxy_calls" => bof()->return_proxy_stats()
      );

    }

    bof()->response->json->set( array_merge( $args, $output ) );

  }
  public function set_error( $texts, $args=[] ){

    return $this->set_message( $texts, $args, false );

  }

}

?>
