<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_subscribe( $loader, $excuter, $args ){

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

      if ( $object_name == "user" ? $loader->user->get()->ID == $get_object["ID"] : false ){
        return false;
      }

      $array = array(
        "user_id" => $loader->user->get()->ID,
        "type" => "subscribe",
        "object_name" => $object_name,
        "object_id" => $get_object["ID"]
      );

      if ( !$loader->object->ugc_property->select( $array ) ){

        $ugc_id = $loader->object->ugc_property->insert( $array );

        bof()->chapar->notify_admin("subscribed", array(
          "object_name" => $the_object->bof()["label"],
          "item_title" => !empty( $get_object["title"] ) ? $get_object["title"] : ( !empty( $get_object["name"] ) ? $get_object["name"] : null ),
          "item_id" => $get_object["ID"],
        ));

        if ( $object_name == "user" ){

          $loader->chapar->notify( "new_follower", array(
            "triggerer" => array(
              "object" => "ugc_property",
              "id" => $ugc_id
            ),
            "target" => array(
              "user_id" => $get_object["ID"]
            ),
            "message" => array(
              "params" => [ "username" => $loader->user->get()->data["username"] ],
              "image" => !empty( $loader->user->get()->data["avatar_thumb"] ) ? $loader->user->get()->data["avatar_thumb"] : null,
              "link" => $loader->seo->url( "user", $loader->user->get()->data, "username" )
            ),
          ) );

        }

      }

    }

  }

  $loader->api->set_message( $object_name == "user" ? "followed" : "subscribed" );

}

?>
