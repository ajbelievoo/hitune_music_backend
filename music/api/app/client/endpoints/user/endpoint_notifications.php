<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_notifications( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $page = $loader->nest->user_input( "post", "page", "int", [ "min" => 1, "max" => 100 ], 1 );
  $per_request = 20;

  $rows = $loader->db->_select( array(
    "table" => "_u_notifications",
    "where" => array(
      [ "user_id", "=", $userID ]
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

      $texts = !empty( $row["message_texts"] ) ? json_decode( $row["message_texts"], true ) : null;
      if ( !is_array( $texts ) ) $texts = array();

      $title = !empty( $texts["title"] ) ? $texts["title"] : ( !empty( $row["message_type"] ) ? $row["message_type"] : "" );
      $body = !empty( $texts["body"] ) ? $texts["body"] : ( !empty( $texts["text"] ) ? $texts["text"] : ( !empty( $texts["detail"] ) ? $texts["detail"] : "" ) );

      $items[] = array(
        "id" => intval( $row["ID"] ),
        "type" => $row["message_type"],
        "title" => $title,
        "body" => $body,
        "image" => !empty( $row["message_image"] ) ? $row["message_image"] : null,
        "link" => !empty( $row["message_link"] ) ? $row["message_link"] : null,
        "is_read" => !empty( $row["time_seen"] ) ? 1 : 0,
        "created_at" => !empty( $row["time_add"] ) ? strtotime( $row["time_add"] ) : null
      );

    }
  }

  $unseen = $loader->object->user_notification->count( array(
    "user_id" => $userID,
    [ "time_seen", null, null, true ]
  ), [ "cache" => false ] );

  $has_more = $loader->db->_select( array(
    "table" => "_u_notifications",
    "where" => array(
      [ "user_id", "=", $userID ]
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
    "unseen" => $unseen,
    "has_more" => $has_more,
    "page" => $page
  ) );

}

?>
