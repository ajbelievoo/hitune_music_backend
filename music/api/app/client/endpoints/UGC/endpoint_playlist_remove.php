<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_remove( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "playlist", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "playlist_hash", "md5" );

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object" );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "object", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "hash", "md5" );

  if ( !$playlist_id )
  return;

  $playlist = $loader->object->ugc_playlist->select(
    array(
      "hash" => $playlist_id,
      "user_access_id" => $loader->user->get()->ID
    )
  );

  if ( !$playlist )
  return;

  // Item removal mode: object_type + object posted -> remove that item from playlist
  if ( $object_name && $object_hash ){

    $item = $loader->object->__get( $object_name )->select(
      array(
        "hash" => $object_hash
      )
    );

    if ( $item ){

      $loader->object->ugc_property->delete(
        array(
          "user_id" => $loader->user->get()->ID,
          "type" => "playlist",
          "object_name" => $object_name,
          "object_id" => $item["ID"],
          "related_object_name" => "ugc_playlist",
          "related_object_id" => $playlist["ID"],
        )
      );

      $loader->object->ugc_playlist->update(
        array(
          "ID" => $playlist["ID"]
        ),
        array(
          "time_update" => $loader->general->mysql_timestamp()
        )
      );

      bof()->chapar->notify_admin("playlist_shorten", array(
        "name" => $playlist["name"],
      ));

    }

    $loader->db->query("DELETE FROM _bof_cache_db WHERE query_hash = '878f14ec7de994025b527a2e3b3bd196' ");
    $loader->api->set_message( "deleted" );
    return;

  }

  // Playlist delete mode — only the owner may delete the whole playlist
  if ( $playlist["user_id"] != $loader->user->get()->ID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $loader->object->ugc_playlist->delete(
    array(
      "ID" => $playlist["ID"]
    )
  );

  bof()->chapar->notify_admin("playlist_removed", array(
    "name" => $playlist["name"],
  ));

  $loader->api->set_message( "deleted" );

}

?>
