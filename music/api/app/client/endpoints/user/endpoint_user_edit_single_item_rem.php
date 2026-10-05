<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_edit_single_item_rem( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "ot", "bofClient_object" );
  $object_hash = $loader->nest->user_input( "post", "oh", "md5" );

  if ( $object_name && $object_hash ){

    $t_object = bof()->object->__get( $object_name );

    $object_item = $t_object->select( array(
      "hash" => $object_hash,
      "uploader_id" => bof()->user->get()->ID
    ) );

    if ( $object_item ){

      $t_object->delete( array(
        "ID" => $object_item["ID"]
      ) );

      bof()->chapar->notify_admin("delete_ok", array(
        "object_name" => $t_object->bof()["label"],
        "item_title" => !empty($object_item["title"]) ? $object_item["title"] : $object_item["name"],
      ));

    }

  }

  $loader->api->set_message( "deleted" );

}

?>
