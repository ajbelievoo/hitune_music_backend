<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_download_youtube( $loader, $excuter, $args ){

  if ( !$loader->object->db_setting->get( "ut" ) ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $_d["object_type"] = $object_type = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "play" ] );
  $_d["object_hash"] = $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( !$object_hash || $object_type != "m_track" ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $the_object = $loader->object->__get( $object_type );

  $object_item = $the_object->select(
    array(
      "hash" => $object_hash
    ),
    array(
      "_eq" => array(
        "sources" => [],
        "cover" => []
      )
    )
  );

  if ( !$object_item ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }


  if ( $object_item ){

    $sources_by_type = $loader->source->get( "stream", $object_type, $object_item, $object_item["bof_dir_sources"], "stream" )["all"]["sources"];
    if ( !empty( $sources_by_type["youtube"] ) && !empty( $sources_by_type["audio"] ) && !empty( $object_item["bof_dir_sources"] ) ? $sources_by_type["audio"]["sources"][0]["hash"] == "youtube_dl" : false ){

      foreach( $object_item["bof_dir_sources"] as $source ){
        if ( $source["hash"] == $sources_by_type["youtube"]["sources"][0]["hash"] && !empty( $source["data_decoded"]["youtube_id"] ) ){
          $youtube_id = $source["data_decoded"]["youtube_id"];
        }
      }

      try {
        $download_and_convert = $loader->youtube->download( $youtube_id );
      } catch( Exception $err ){
        $loader->api->set_error( "Failure: " . $err->getMessage(), [ "output_args" => [ "turn" => false ] ] );
        return;
      }

      $rules = $loader->object->file->get_rules( "audio", "m_track_source", [ "get_host" => true ] );

      $convert_file_id = $loader->object->file->insert(
        array(
          "type" => "audio",
          "host_id" => "1",
          "dest_host_id" => $rules["file_host"],
          "path" => $loader->object->file->clean_path( $download_and_convert, true ),
          "object_type" => "m_track_source",
        )
      );

      $convert_source = $loader->object->m_track_source->create(
        [],
        array(
          "target_id" => $object_item["ID"],
          "type" => "audio",
          "data" => array(
            "file_type" => "local",
            "local_file" => $convert_file_id,
          ),
        ),
        []
      );

    }

  }

  $loader->api->set_message( "done" );

}

?>
