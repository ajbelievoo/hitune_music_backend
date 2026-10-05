<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_request_youtube_id( $loader, $excuter, $args ){

  if ( !$loader->object->db_setting->get( "youtube_automation" ) ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $_d["title"] = $title = $loader->nest->user_input( "post", "title", "string" );
  $_d["sub_title"] = $sub_title = $loader->nest->user_input( "post", "sub_title", "string" );
  $_d["object_type"] = $object_type = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "play" ] );
  $_d["object_hash"] = $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  $_d["duration"] = $duration = $loader->nest->user_input( "post", "duration", "int", [ "empty()" => true, "min" => 0 ] );

  if ( !$title || !$sub_title || !$object_type || !$object_hash || $object_type != "m_track" ){
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
        "cover" => [],
        "artist" => []
      )
    )
  );

  if ( !$object_item ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $_nd = array(
    "title" => $object_item["title"],
    "sub_title" => $object_item["bof_dir_artist"]["name"],
    "duration" => $object_item["duration"]
  );

  $_extra = array(
    "duration" => !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null
  );
  $_loudness = bof()->music->track_loudness( $object_item );
  if ( $_loudness ){
    $_extra["loudness"] = $_loudness;
    $_extra["lufs"] = $_loudness["lufs"];
    if ( $_loudness["peak_db"] !== null ) $_extra["peak_db"] = $_loudness["peak_db"];
  }

  if ( !empty( $object_item["bof_dir_sources"] ) ){
    foreach( $object_item["bof_dir_sources"] as $source ){
      if ( $source["type"] == "youtube" ? !empty( $source["data_decoded"]["youtube_id"] ) : false ){
        if ( empty( $object_item["youtube_id"] ) )
        bof()->music->set_track_youtube_id( $object_item["ID"], $source["data_decoded"]["youtube_id"] );
        $loader->api->set_message( "ok", array_merge( [ "youtube_id" => $source["data_decoded"]["youtube_id"] ], $_extra ) );
        return;
      }
    }
  }

  $request_video = $loader->youtube->find_video( $_nd );
  if ( $request_video[0] ){

    if ( david && !empty( $request_video[2] ) ){

      $_data = json_encode( array(
        "ID" => $request_video[1],
        "data" => $request_video[2]
      ) );

      $the_object->update(
        array(
          "ID" => $object_item["ID"]
        ),
        array(
          "_david" => $_data
        )
      );

    }

    $loader->object->__get( "m_track_source" )->insert(array(
      "target_id" => $object_item["ID"],
      "type" => "youtube",
      "data" => json_encode( [ "youtube_id" => $request_video[1] ] ),
      "stream_able" => 1,
      "download_able" => -2,
      "encrypted" => 0
    ));

    bof()->music->set_track_youtube_id( $object_item["ID"], $request_video[1] );

    $loader->api->set_message( "ok", array_merge( [ "youtube_id" => $request_video[1] ], $_extra ) );

    return;

  }

  $loader->api->set_error( $request_video[1], [ "output_args" => [ "turn" => false ] ] );

}

?>
