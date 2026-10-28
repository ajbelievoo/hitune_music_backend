<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_comments( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object" );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "object", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "hash", "md5" );
  $page = $loader->nest->user_input( "post", "page", "int", [ "min" => 1, "max" => 100 ], 1 );
  $per_request = 20;

  if ( !$object_name || !$object_hash ) return;

  $the_object = $loader->object->__get( $object_name );
  $object_item = $the_object->select(
    array( "hash" => $object_hash ),
    array( "clean" => false )
  );

  if ( !$object_item ) return;

  $me = $loader->user->check()->ID;

  $rows = $loader->db->_select( array(
    "table" => "_u_comments",
    "where" => array(
      [ "object_name", "=", $object_name ],
      [ "object_id", "=", $object_item["ID"] ]
    ),
    "order_by" => "time_add",
    "order" => "DESC",
    "limit" => $per_request,
    "offset" => ($page - 1) * $per_request,
    "single" => false
  ) );

  $items = array();
  if ( $rows ){
    foreach( $rows as $row ){

      $author = $loader->object->user->sid(
        $row["user_id"],
        array(
          "_eq" => array( "avatar" => [] )
        )
      );

      $items[] = array(
        "id" => intval( $row["ID"] ),
        "author" => $author ? ( $author["name"] ? $author["name"] : $author["username"] ) : null,
        "user" => $author ? array(
          "id" => intval( $author["ID"] ),
          "name" => $author["name"] ? $author["name"] : $author["username"],
          "username" => $author["username"]
        ) : null,
        "avatar" => !empty( $author["bof_file_avatar"]["image_thumb"] ) ? $author["bof_file_avatar"]["image_thumb"] : null,
        "text" => $row["text"],
        "likes" => intval( $row["s_likes"] ),
        "mine" => ( $me && $row["user_id"] == $me ) ? 1 : 0,
        "created_at" => !empty( $row["time_add"] ) ? strtotime( $row["time_add"] ) : null
      );

    }
  }

  $has_more = $loader->db->_select( array(
    "table" => "_u_comments",
    "where" => array(
      [ "object_name", "=", $object_name ],
      [ "object_id", "=", $object_item["ID"] ]
    ),
    "columns" => "ID",
    "order_by" => "time_add",
    "order" => "DESC",
    "limit" => 1,
    "offset" => $page * $per_request,
    "single" => true
  ) ) ? ($page + 1) : false;

  $loader->api->set_message( "ok", array(
    "items" => $items,
    "has_more" => $has_more,
    "page" => $page
  ) );

}

?>
