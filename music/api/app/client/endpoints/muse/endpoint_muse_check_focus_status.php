<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_check_focus_status( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "source" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( !$object_name ? $loader->nest->user_input( "post", "object_type", "equal", [ "value" => "a_book" ] ) : false )
  $object_name = "a_book"; 

  if ( $object_name && $object_hash && bof()->user->check()->ID ){

    $object_item = $loader->object->__get( $object_name )->select(array(
      "hash" => $object_hash
    ));

    if ( $object_item ){

      $liked = bof()->object->ugc_property->select(
        array(
          "user_id" => bof()->user->check()->ID,
          "type" => "like",
          "object_name" => $object_name,
          "object_id" => $object_item["ID"],
        ),
        array(
          "clean" => false
        )
      );

      $loader->api->set_message("ok",array(
        "liked" => $liked ? true : false
      ));
      
      return;

    }

  }

  $loader->api->set_message("ok");

}

?>
