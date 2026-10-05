<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_searchSuggs( $loader, $excuter, $args ){

  $type = bof()->nest->user_input( "post", "type", "in_array", array(
    "values" => [ "ini" ]
  ) );

  if ( !$type )
  return;

  if ( bof()->object->core_setting->get("search_index_type") != "inverted_indexing" )
  return;

  $history = $loader->bofClient->__parse_widget( "history", null, array(
    "ID" =>  "history",
    "display" => array(
      "type" => "slider",
      "title" => "History",
      "link" => false,
      "pagination" => false,
      "slider_size" => "medium",
      "slider_rows" => 1,
      "slider_mason" => false,
      "classes" => [ "searchSuggs", "_history" ]
    ),
    "object" => array(
      "name" => "search_history",
      "whereArray" => array(
        "display" => true,
        "users" => true
      ),
      "selectArray" => array(
        "empty_select" => true,
        "single" => false,
        "limit" => 20,
        "columns" => "target_object_type,target_object_id,MAX(time_redirect) as time_redirect",
        "group_by" => "GROUP BY target_object_type,target_object_id",
        "order_by" => "time_redirect",
      )
    ),
  ) );

  $popular = $loader->bofClient->__parse_widget( "popular", null, array(
    "ID" =>  "popular",
    "display" => array(
      "type" => "slider",
      "title" => "Popular",
      "link" => false,
      "pagination" => false,
      "slider_size" => "medium",
      "slider_rows" => 1,
      "slider_mason" => false,
      "classes" => [ "searchSuggs", "_popular" ]
    ),
    "object" => array(
      "name" => "search_history",
      "whereArray" => array(
        "display" => true
      ),
      "selectArray" => array(
        "empty_select" => true,
        "single" => false,
        "limit" => 20,
        "columns" => "target_object_type,target_object_id,MAX(time_redirect) as time_redirect,COUNT(*) as cc",
        "group_by" => "GROUP BY target_object_type,target_object_id",
        "order_by" => "cc",
        "order" => "DESC"
      )
    ),
  ) );

  $loader->api->set_message( "ok", [ "suggestions" => array(
    "popular" => $popular,
    "history" => $history
  ) ] );

}

?>
