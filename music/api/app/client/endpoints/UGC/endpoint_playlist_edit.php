<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_edit( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );
  $playlist_name = $loader->nest->user_input( "post", "name", "string", [ "strip_emoji" => false ]  );
  $playlist_private = $loader->nest->user_input( "post", "private", "int", [ "min" => 0, "max" => 1 ], 0 );
  $validate_cover = $loader->bofInput->validate( $loader->object->ugc_playlist->columns()["cover_id"] );
  $collabs = $loader->nest->user_input( "post", "collabs_ids", "int_imploded" );

  if ( $playlist_id && $playlist_name ){

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

    if ( $playlist ){

      $cover_input = 0;

      if ( $validate_cover ){

        $finalize_upload = $loader->object->file->finalize_upload(
          $loader->object->ugc_playlist->columns()["cover_id"]["bofInput"][1]["type"],
          $loader->object->ugc_playlist->columns()["cover_id"]["bofInput"][1]["object_type"],
          $loader->object->ugc_playlist->bof()["name"] . $playlist["ID"] ,
          $validate_cover,
          $playlist["cover_id"]
        );

        if ( $finalize_upload )
        $cover_input = $validate_cover ? $validate_cover : $cover_input;

      }

      $loader->object->ugc_playlist->update(
        array(
          "ID" => $playlist["ID"]
        ),
        array(
          "name" => $playlist_name,
          "private" => $playlist_private,
          "cover_id" => $cover_input,
        )
      );

      bof()->chapar->notify_admin("playlist_edited", array(
        "name" => $playlist["name"],
        "new_name" => $playlist_name
      ));

      // Collaborators
      $validCollabs = [];
      if ( $collabs ){
        foreach( explode( ",", $collabs ) as $collabID ){
          if ( $collabID == $playlist["user_id"] ) continue;
          $collab = bof()->object->user->sid( $collabID );
          if ( $collab ) $validCollabs[] = $collabID;
        }
      }

      $oldCollabs = bof()->object->user->select(array(
        "pl_collab" => $playlist["ID"]
      ),array(
        "limit" => 20,
        "single" => false,
        "cleaner" => function( $item ){
          return $item["ID"];
        }
      ));

      // remove removed collabs from db
      if ( !empty( $oldCollabs ) ){
        foreach( $oldCollabs as $oldCollabID ){
          if ( !in_array( $oldCollabID, $validCollabs, true ) )
          $loader->object->ugc_property->delete( array(
            "user_id" => $oldCollabID,
            "type" => "pl_collab",
            "object_name" => "ugc_playlist",
            "object_id" => $playlist["ID"]
          ) );
        }
      }

      // add new collabs to db
      if ( !empty( $validCollabs ) ){
        foreach( $validCollabs as $validCollabID ){
          if ( !in_array( $validCollabID, ($oldCollabs?$oldCollabs:[]), true ) ){

            $loader->object->ugc_property->insert( array(
              "user_id" => $validCollabID,
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
                "id" =>$loader->user->get()->data["ID"]
              ),
              "target" => array(
                "user_id" => $validCollabID
              ),
              "message" => array(
                "params" => [ "user" => $loader->user->get()->data["username"], "name" => $playlist["name"] ],
                "image" => !empty( $playlist["bof_file_cover"]["image_thumb"] ) ? $playlist["bof_file_cover"]["image_thumb"] : null,
                "link" => $loader->seo->url( "ugc_playlist", $playlist )
              ),
            ) );

          }
        }
      }

      $loader->api->set_message( "ok" );

      return;

    }

  }

}

?>
