<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_share( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object" );
  $object_has_source = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "source" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( $object_name && $object_hash ){

    $the_object = $loader->object->__get( $object_name );

    $object_item_widget = $the_object->select(
      array(
        "hash" => $object_hash
      ),
      array(
      )
    );

    if ( $object_item_widget ){

      $object_item = $the_object->select(
        array(
          "ID" => $object_item_widget["ID"]
        ),
        array(
          "muse_source" => true,
          "_eq" => array(
            "sources" => array(),
          ),
          "cache_load_rt" => false
        )
      );
      $object_item2 = $the_object->select(
        array(
          "ID" => $object_item_widget["ID"]
        ),
        array(
          "indexed" => true,
          "match_page" => true,
        )
      );


      $sources = [];
      if ( !empty( $object_item["sources"] ) && $object_has_source ){

        $sources_gs = $object_item["sources"];

        foreach( $sources_gs as $source_G ){

          $sources_data = $source_G["data"];
          $sources_by_type = $loader->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "embed" );

          if ( !empty( $sources_by_type["all"]["sources"] ) ){
            foreach( $sources_by_type["all"]["sources"] as $_s_group ){
              if ( !empty( $_s_group["sources"] ) ){
                foreach( $_s_group["sources"] as $_s_group_source ){
                  if ( !$_s_group_source["locked"] )
                  $sources[] = $_s_group_source["hook"];
                }
              }
            }
          }

        }

      }

    }

    $loader->api->set_message( "ok", array(
      "item" => bof()->seo->fetch( array(
        "object" => $object_name,
        "item" => $object_item2,
        "lang" => null,
      ), true ),
      "embedable" => ( bof()->object->db_setting->get( "muse_embedable", 1 ) && !empty( $sources ) ) ? array_unique( $sources ) : null
    ) );

  }

}

?>
