<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_shorten( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );
  $item_id = $loader->nest->user_input( "post", "item", "md5" );
  $item_ot = $loader->nest->user_input( "post", "ot", "bofClient_object" );
  $i = $loader->nest->user_input( "post", "i", "int" );

  if ( $playlist_id && $item_id && $item_ot && $i ){

    $playlist = $loader->object->ugc_playlist->select(
      array(
        "hash" => $playlist_id,
        "user_access_id" => $loader->user->get()->ID
      )
    );

    $item = $loader->object->__get( $item_ot )->select(
      array(
        "hash" => $item_id
      )
    );

    if ( $playlist && $item ){

      $loader->object->ugc_property->delete(
        array(
          "user_id" => $loader->user->get()->ID,
          "type" => "playlist",
          "object_name" => $item_ot,
          "object_id" => $item["ID"],
          "related_object_name" => "ugc_playlist",
          "related_object_id" => $playlist["ID"],
          "i" => $i
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

  }

  $loader->db->query("DELETE FROM _bof_cache_db WHERE query_hash = '878f14ec7de994025b527a2e3b3bd196' ");
  $loader->api->set_message( "deleted" );

}

?>
