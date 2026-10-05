<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_collab( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "playlist", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "playlist_hash", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );
  $collab = $loader->nest->user_input( "post", "collab", "int", [ "min" => 0, "max" => 1 ] );
  $invite = $loader->nest->user_input( "post", "invite", "int", [ "min" => 1 ] );

  if ( !$playlist_id )
  return;

  $playlist = $loader->object->ugc_playlist->select(
    array(
      "hash" => $playlist_id,
      "user_id" => $loader->user->get()->ID
    ),
    array(
      "_eq" => array(
        "cover" => []
      )
    )
  );

  if ( !$playlist ){
    $loader->api->set_error( "no_access" );
    return;
  }

  // Invite/remove a collaborator
  if ( $invite ){

    $target = $loader->object->user->sid( $invite );

    if ( $target && $invite != $playlist["user_id"] ){

      if ( $collab ){

        $exists = $loader->object->ugc_property->select(
          array(
            "user_id" => $invite,
            "type" => "pl_collab",
            "object_name" => "ugc_playlist",
            "object_id" => $playlist["ID"]
          )
        );

        if ( !$exists ){

          $loader->object->ugc_property->insert( array(
            "user_id" => $invite,
            "type" => "pl_collab",
            "object_name" => "ugc_playlist",
            "object_id" => $playlist["ID"]
          ) );

          $loader->chapar->notify( "collabed_in_playlist", array(
            "source" => array(
              "object" => "ugc_playlist",
              "id" => $playlist["ID"],
            ),
            "triggerer" => array(
              "object" => "user",
              "id" => $loader->user->get()->data["ID"]
            ),
            "target" => array(
              "user_id" => $invite
            ),
            "message" => array(
              "params" => [ "user" => $loader->user->get()->data["username"], "name" => $playlist["name"] ],
              "image" => !empty( $playlist["bof_file_cover"]["image_thumb"] ) ? $playlist["bof_file_cover"]["image_thumb"] : null,
              "link" => $loader->seo->url( "ugc_playlist", $playlist )
            ),
          ) );

        }

      }
      else {

        $loader->object->ugc_property->delete( array(
          "user_id" => $invite,
          "type" => "pl_collab",
          "object_name" => "ugc_playlist",
          "object_id" => $playlist["ID"]
        ) );

      }

    }

  }
  else {

    // Toggle the collaborative flag on the playlist itself
    $extra = !empty( $playlist["extra_data"] ) ? json_decode( $playlist["extra_data"], true ) : array();
    if ( !is_array( $extra ) ) $extra = array();
    $extra["collab"] = $collab ? 1 : 0;

    $loader->db->_update( array(
      "table" => "_u_playlists",
      "where" => array(
        [ "ID", "=", $playlist["ID"] ]
      ),
      "set" => array(
        [ "extra_data", json_encode( $extra ) ]
      )
    ) );

  }

  $loader->api->set_message( "ok" );

}

?>
