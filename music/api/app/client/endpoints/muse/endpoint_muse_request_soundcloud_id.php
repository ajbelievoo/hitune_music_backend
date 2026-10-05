<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_request_soundcloud_id( $loader, $excuter, $args ){

  if ( !$loader->object->db_setting->get( "soundcloud_automation" ) ){
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
        "cover" => []
      )
    )
  );

  if ( !$object_item ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $request_track = $loader->soundcloud->find_track( $_d );

  if ( $request_track[0] ){

    $loader->object->__get( "m_track_source" )->insert(array(
      "target_id" => $object_item["ID"],
      "type" => "soundcloud",
      "data" => json_encode( [ "soundcloud_id" => $request_track[1] ] ),
      "stream_able" => 1,
      "download_able" => -2,
      "encrypted" => 0
    ));

    $loader->api->set_message( "ok", [ "soundcloud_id" => $request_track[1] ] );

    return;

  }

  $loader->api->set_error( $request_track[1], [ "output_args" => [ "turn" => false ] ] );

}

?>
