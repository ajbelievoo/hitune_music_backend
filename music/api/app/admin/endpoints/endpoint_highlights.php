<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_highlights( $loader, $excuter, $args ){

  $sb_family = $loader->nest->user_input( "post", "sb_family", "in_array", [ "values" => [ "dashboard", "content", "users", "business", "setting", "page_builder", "extensions" ] ], "dashboard" );

  if ( $sb_family == "dashboard" ){

    foreach( bof()->bofAdmin->_get_stats() as $k => $args ){
      if ( $k == "dashboard" ) continue;
      $loader->highlights
      ->new_item( "dashboard_links", array(
        "icon" => $args["icon"],
        "title" => ucfirst( $args["title"] ),
        "link" => "stat/{$k}"
      ) );
    }

    $loader->highlights
    ->new_item( "dashboard_links", array(
      "icon" => "priority_high",
      "title" => "Error Logs",
      "link" => "error_logs"
    ) );

  }
  elseif ( $sb_family == "content") {

    $loader->highlights
    ->new_group( "content_stats", "section_stats2", array(
      "sb_family" => "content"
    ) );

    $loader->highlights
    ->new_item( "content_stats", array(
      "icon" => "article",
      "title" => "Posts",
      "tip" => "Published blog posts",
      "value" => number_format( $loader->object->b_post->count( [ "active" => 1 ], [] ) )
    ) )
    ->new_item( "content_stats", array(
      "icon" => "grid_view",
      "title" => "Pages",
      "tip" => "Active pages",
      "value" => number_format( $loader->object->page->count( [ "active" => 1 ], [] ) )
    ) );

  }
  elseif ( $sb_family == "users") {

    $loader->highlights
    ->new_group( "users_stats", "section_stats2", array(
      "sb_family" => "users"
    ) );

    $loader->highlights
    ->new_item( "users_stats", array(
      "icon" => "person",
      "title" => "Users",
      "tip" => "All users count",
      "value" => number_format( $loader->object->user->count( [], [] ) )
    ) )
    ->new_item( "users_stats", array(
      "icon" => "card_membership",
      "title" => "Subs",
      "tip" => "Number of Subscribed-users",
      "value" => number_format( $loader->object->user->count( ["is_subscribed"=>"yes"], [] ) )
    ) )
    ->new_item( "users_stats", array(
      "icon" => "security",
      "title" => "Admins",
      "tip" => "Number of admins",
      "value" => number_format( $loader->object->user->count( ["role_type"=>"admin"], [] ) )
    ) )
    ->new_item( "users_stats", array(
      "icon" => "engineering",
      "title" => "Moderators",
      "tip" => "Number of moderators",
      "value" => number_format( $loader->object->user->count( ["role_type"=>"moderator"], [] ) )
    ) );

  }

  $highlights = $loader->highlights->display( $sb_family, true );
  $loader->api->set_message( "ok", [ 
    "html" => $highlights["str"], 
    "json" => $highlights["json"],
    "sbs" => $highlights["sbs"]
  ] );

}

?>
