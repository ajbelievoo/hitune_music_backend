<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_keep( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );

  if ( $playlist_id ){

    $playlist = $loader->object->ugc_playlist->select(
      array(
        "hash" => $playlist_id,
        "is_private" => 0
      )
    );

    if ( $playlist ? $playlist["user_id"] != $loader->user->get()->ID : false ){

      $array = array(
        "user_id" => $loader->user->get()->ID,
        "type" => "playlist_k",
        "object_name" => "ugc_playlist",
        "object_id" => $playlist["ID"]
      );

      if ( !$loader->object->ugc_property->select( $array ) ){

        $ugc_id = $loader->object->ugc_property->insert( $array );

        bof()->chapar->notify_admin("playlist_kept", array(
          "name" => $playlist["name"],
        ));

        if ( !empty( $playlist["user_id"] ) ){

          $loader->chapar->notify( "new_playlist_subscriber", array(
            "triggerer" => array(
              "object" => "ugc_property",
              "id" => $ugc_id
            ),
            "target" => array(
              "user_id" => $playlist["user_id"]
            ),
            "message" => array(
              "params" => [ "username" => $loader->user->get()->data["username"], "playlist_name" => $playlist["name"] ],
              "image" => !empty( $loader->user->get()->data["avatar_thumb"] ) ? $loader->user->get()->data["avatar_thumb"] : null,
              "link" => $loader->seo->url( "user", $loader->user->get()->data, "username" )
            ),
          ) );

        }

      }

    }

  }
  $loader->api->set_message( "ok" );

}

?>
