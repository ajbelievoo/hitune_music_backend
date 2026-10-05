<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_edit_ini( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );

  if ( $playlist_id ){

    $playlist = $loader->object->ugc_playlist->select(
      array(
        "hash" => $playlist_id,
        "user_id" => $loader->user->get()->ID
      ),
      array()
    );

    if ( $playlist ){

      $_cover_column = $loader->object->ugc_playlist->columns()["cover_id"];
      $_cover_column["input"]["value"] = $playlist["cover_id"];
      $cover_input = $loader->bofInput->parse( $_cover_column, [ "translate" => true ] );

      $_collabs_column = array(
        "label" => bof()->object->language->turn( "collaborators", [], [ "uc_first" => true, "lang" => "users" ] ),
        "tip" => bof()->object->language->turn( "playlist_colab_tip", [], [ "uc_first" => true, "lang" => "users" ] ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => true
          )
        ),
      );
      $_collabs_column["input"]["name"] = "collabs_ids";

      $collabs = bof()->object->ugc_property->select(array(
        "type" => "pl_collab",
        "object_name" => "ugc_playlist",
        "object_id" => $playlist["ID"],
      ),array(
        "limit" => 20,
        "single" => false,
        "cleaner" => function( $item ){
          return $item["user_id"];
        }
      ));

      if ( $collabs ){
        $_collabs_column["input"]["value"] = $collabs;
      }

      $collabs_input = $loader->bofInput->parse( $_collabs_column, [ "translate" => true ] );

      $loader->api->set_message( "ok", array(
        "playlist" => bof()->object->ugc_playlist->publicize( $playlist ),
        "cover_input" => $cover_input,
        "collabs_input" => $collabs_input
      ) );

      return;

    }

  }

}

?>
