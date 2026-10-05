<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_lose( $loader, $excuter, $args ){

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

      if ( $loader->object->ugc_property->select( $array ) ){

        bof()->chapar->notify_admin("playlist_released", array(
          "name" => $playlist["name"],
        ));

        $loader->object->ugc_property->delete( $array );

        if ( !empty( $playlist["user_id"] ) ){

          $loader->chapar->unnotify( array(
            "user_id" => $playlist["user_id"],
            "hook" => "new_playlist_subscriber",
            "source_object" => "user",
            "source_id" => $loader->user->get()->ID
          ) );

        }

      }

    }

  }
  $loader->api->set_message( "deleted" );

}

?>
