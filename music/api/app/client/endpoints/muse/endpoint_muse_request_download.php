<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_request_download( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "download" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  $source_hash = $loader->nest->user_input( "post", "source_hash", "md5" );
  $type = $loader->nest->user_input( "post", "type", "in_array", [ "values" => [ "in", "out" ] ] );

  if ( !$object_name ? $loader->nest->user_input( "post", "object_type", "equal", [ "value" => "a_book_chapter" ] ) : false )
  $object_name = "a_book_chapter";


  if ( $object_name && $object_hash && $source_hash && $type ){

    $the_object = $loader->object->__get( $object_name );
    $object_item = $the_object->select(
      array(
        "hash" => $object_hash
      ),
      array(
        "as_widget" => true,
        "muse_request_download" => true,
        "_eq" => array(
          "sources" => array(
            "for_download" => true
          ),
          "cover" => []
        )
      )
    );

    if ( $object_item ){

      $all_options = $loader->source->get_downloads( $object_name, $the_object, $object_item["raw"], "be" );
      if ( !empty( $all_options ) ? $all_options[ $type ][ $source_hash ] : false ){

        if ( !empty( $all_options[ $type ][ $source_hash ]["locked"] ) ){
          $loader->api->set_error( "locked", array(
            "output_args" => array(
              "turn" => false
            )
          ) );
          return;
        }

        $data = array(
          "title" => $object_item["title"],
          "sub_title" => !empty( $object_item["sub_data"] ) ? $object_item["sub_data"] : null,
          "source_title" => $all_options[ $type ][ $source_hash ]["_raw"]["_title"],
          "cover" => !empty( $object_item["cover"]["web_address"] ) ? $object_item["cover"]["web_address"] : null
        );

        $c_source_raw = $all_options[ $type ][ $source_hash ]["_raw"];
        $c_source = $c_source_raw["muse"]["type"];
        $c_source_file = !empty( $c_source_raw["muse"]["file"] ) ? $c_source_raw["muse"]["file"] : null;
        $source_type = $c_source[0];
        $source_args = $c_source[1];
        if ( !empty( $c_source_raw["muse"]["file"] ) )
        $c_source_raw["muse"]["file"] = null;

        if ( !empty( $source_args["hls"] ) ){
          $source_type = $source_args["type"];
          $file_list = array_merge( [ $source_args["key"] ], [ $source_args["address"] ], $source_args["slices"] );
        }
        else {
          if ( preg_match( "/\/files\/protected\//", $source_args["address"] ) ){
            $source_args["type"] = "protected";
            $source_args["address"] = $loader->source->grant_access( $object_name, $object_hash, $c_source_raw["hash"], $source_args["address"], "24 HOUR", "download" );
          }
          $file_list = [ $source_args["address"] ];
        }

        $data["web_address"] = $source_args["address"];
        $data["size"] = !empty( $c_source_file["data_decoded"]["total_size"] ) ? $c_source_file["data_decoded"]["total_size"] : ( !empty( $c_source_file["size"] ) ? $c_source_file["size"] : null );
        $data["muse"] = array(
          "data" => $sources_data = $the_object->get_sources_data( $object_item["raw"] ),
          "source" => $c_source_raw["muse"]
        );

        $loader->api->set_message( "ok", array(
          "source_data" => $data,
          "source_file_list" => $file_list
        ) );
        return;

      }

    }

  }

}

?>
