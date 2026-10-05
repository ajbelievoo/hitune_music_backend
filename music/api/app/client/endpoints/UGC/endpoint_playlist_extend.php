<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_extend( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "playlist" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "object", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "hash", "md5" );
  $playlist_hash = $loader->nest->user_input( "post", "playlist", "md5" );
  if ( !$playlist_hash )
  $playlist_hash = $loader->nest->user_input( "post", "playlist_hash", "md5" );

  if ( $object_name ){

    $playlist = $loader->object->ugc_playlist->select( array(
      "user_access_id" => $loader->user->get()->ID,
      "hash" => $playlist_hash
    ), array(
      "_eq" => array(
        "cover" => []
      )
    ) );

    $the_object = $loader->object->__get( $object_name );

    $object_item = $the_object->select( array(
      "hash" => $object_hash
    ) );

    if ( $playlist && $object_item ){

      $highest_index = $loader->object->ugc_property->select(
        array(
          "type" => "playlist",
          "related_object_name" => "ugc_playlist",
          "related_object_id" => $playlist["ID"],
        ),
        array(
          "order_by" => "i",
          "order" => "DESC"
        )
      );

      if ( $highest_index ){
        $highest_index = $highest_index["i"] + 1;
      } else {
        $highest_index = 1;
      }

      if ( $the_object->method_exists( "playlisting" ) ){

        list( $new_object_name, $new_object_items ) = $the_object->playlisting( $object_item );
        foreach( $new_object_items as $_i => $_item ){
          $loader->object->ugc_property->insert(
            array(
              "user_id" => $loader->user->get()->ID,
              "type" => "playlist",
              "object_name" => $new_object_name,
              "object_id" => $_item["ID"],
              "related_object_name" => "ugc_playlist",
              "related_object_id" => $playlist["ID"],
              "i" => $highest_index + $_i
            )
          );
        }

      }
      else {

        $loader->object->ugc_property->insert(
          array(
            "user_id" => $loader->user->get()->ID,
            "type" => "playlist",
            "object_name" => $object_name,
            "object_id" => $object_item["ID"],
            "related_object_name" => "ugc_playlist",
            "related_object_id" => $playlist["ID"],
            "i" => $highest_index
          )
        );

      }

      bof()->chapar->notify_admin("playlist_extended", array(
        "name" => $playlist["name"],
        "item" => $object_item
      ));

      $playlist_subs = bof()->object->user->select(array(
        "pl_subs" => $playlist["ID"],
        [ "ID", "!=", bof()->user->check()->ID ]
      ),array(
        "limit" => 20,
        "single" => false,
        "cleaner" => function( $item ){
          return $item["ID"];
        }
      ));

      if ( !empty( $playlist_subs ) ){
        foreach( $playlist_subs as $playlist_sub ){

          $loader->chapar->notify( "playlist_update", array(
            "source" => array(
              "object" => "ugc_playlist",
              "id" => $playlist["ID"],
            ),
            "triggerer" => array(
              "object" => $object_name,
              "id" => $object_item["ID"],
            ),
            "target" => array(
              "user_id" => $playlist_sub
            ),
            "message" => array(
              "params" => [ "user" => $loader->user->get()->data["username"], "name" => $playlist["name"] ],
              "image" => !empty( $playlist["bof_file_cover"]["image_thumb"] ) ? $playlist["bof_file_cover"]["image_thumb"] : null,
              "link" => $loader->seo->url( "ugc_playlist", $playlist )
            ),
          ) );

        }
      }

      $loader->object->ugc_playlist->update(
        array(
          "ID" => $playlist["ID"]
        ),
        array(
          "time_update" => $loader->general->mysql_timestamp()
        )
      );

    }

  }

  $loader->db->query("DELETE FROM _bof_cache_db WHERE query_hash = '878f14ec7de994025b527a2e3b3bd196' ");
  $loader->api->set_message( "playlist_extended", array(
    "playlist" => array(
      "url" => $playlist["seo_url"]
    )
  ) );

}

?>
