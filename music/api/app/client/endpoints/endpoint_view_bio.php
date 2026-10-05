<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_view_bio( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "biography" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hook", "md5" );

  if ( $object_name && $object_hash ){

    $object_item = $loader->object->__get( $object_name )->select( array(
      "hash" => $object_hash,
    ), array(
      "_eq" => array(
        "cover" => []
      ),
      "cleaner" => function( $item ){

        foreach( $item as $_k => $_d ){
          if ( substr( $_k, 0, strlen( "bio_" ) ) == "bio_" ? substr( $_k, 0, strlen( "bio_content" ) ) != "bio_content" : false )
          $attrs[] = array( bof()->object->language->turn( substr( $_k, 4 ), [], [ "uc_first" => true, "lang" => "users" ] ), $_d );
        }

        return array(
          "name" => $item["name"],
          "cover" => !empty( $item["bof_file_cover"]["image_original"] ) ? $item["bof_file_cover"]["image_original"] : null,
          "content" => $item["bio_content_html"],
          "attrs" => $attrs,
        );

      }
    ) );

    if ( $object_item ){
      $loader->api->set_message( "ok", array(
        "data" => $object_item,
        "output_args" => [ "uc_first" => true ]
      ) );
      return;
    }

  }

}

?>
