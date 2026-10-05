<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_unsubscribe( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "subscribe" ] );
  $object_hash = $loader->nest->user_input( "post", "object", "md5" );

  if ( $object_name && $object_hash ){

    $the_object = $loader->object->__get( $object_name );

    $get_object = $the_object->select(
      array(
        "hash" => $object_hash
      )
    );

    if ( $get_object ){

      $array = array(
        "user_id" => $loader->user->get()->ID,
        "type" => "subscribe",
        "object_name" => $object_name,
        "object_id" => $get_object["ID"]
      );

      bof()->chapar->notify_admin("unsubscribed", array(
        "object_name" => $the_object->bof()["label"],
        "item_title" => !empty( $get_object["title"] ) ? $get_object["title"] : ( !empty( $get_object["name"] ) ? $get_object["name"] : null ),
        "item_id" => $get_object["ID"],
      ));

      if ( $loader->object->ugc_property->select( $array ) ){

        $loader->object->ugc_property->delete( $array );

        if ( $object_name == "user" ){

          $loader->chapar->unnotify( array(
            "source_object" => "user",
            "source_id" => $loader->user->get()->ID,
            "hook" => "new_follower",
            "user_id" => $get_object["ID"]
          ) );

        }

      }

    }

  }

  $loader->api->set_message( $object_name == "user" ? "unfollowed" : "unsubscribed" );

}

?>
