<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_client_translations( $loader, $excuter, $args ){

  $language = $loader->object->language->select(
    array(
      "code2" => $loader->user->check()->language
    ),
    array(
      "_eq" => array(
        "items" => array(
          "limit" => false,
          "public" => true,
          "no_bof_time" => true,
          "select_cleaner" => function( $items ){
            foreach( $items as $item )
            $n[$item["hook"]] = !empty( $item["text_decoded"] ) ? $item["text_decoded"] : "?{$item["hook"]}?";
            return !empty( $n ) ? $n : [];
          },
        )
      )
    )
  );

  $loader->api->set_message( "ok", [ "translations" => $language["bof_dir_items"], "direction" => $language["direction"], "code" => $language["code2"] ] );

}

?>
