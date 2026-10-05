<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_chapar( $loader, $excuter, $args ){

  $type = $loader->nest->user_input( "post", "type", "in_array", [ "values" => [ "get", "list", "set" ] ], "list" );

  $users = [];
  $has_more = false;

  $unseen = $loader->object->user_notification->count( array(
    "user_id" => $loader->user->check()->ID,
    [ "time_seen", null, null, true ]
  ), [ "cache" => false ] );

  if ( $type == "list" ){

    $page = $loader->nest->user_input( "post", "page", "int", [ "min" => 1, "max" => 50 ], 1 );
    $perRequest = $loader->request->is_mobile() ? 20 : 10;

    $users = $loader->object->user_notification->select(
      array(
        "user_id" => $loader->user->check()->ID
      ),
      array(
        "page" => $page,
        "limit" => $perRequest,
        "order_by" => "time_add",
        "order" => "DESC",
        "single" => false,
        "for_display" => true,
      )
    );

    $has_more = $loader->object->user_notification->select(
      array(
        "user_id" => $loader->user->check()->ID
      ),
      array(
        "limit" => 1,
        "offset" => $page * $perRequest,
        "order_by" => "time_add",
        "order" => "DESC",
        "clean" => false
      )
    ) ? ($page+1) : false;

  }
  elseif ( $type == "set" ){

    $loader->object->user_notification->update(
      array(
        "user_id" => $loader->user->check()->ID,
        [ "time_seen", null, null, true ]
      ),
      array(
        "time_seen" => $loader->general->mysql_timestamp()
      )
    );

    $unseen = 0;

  }

  $loader->api->set_message( "ok", array(
    "type" => $type,
    "items" => $users,
    "has_more" => $has_more,
    "unseen" => $unseen
  ) );

}

?>
