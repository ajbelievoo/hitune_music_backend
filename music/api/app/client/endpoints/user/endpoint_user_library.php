<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_library( $loader, $excuter, $args ){

  $enabledTabsByAdmin = bof()->object->db_setting->get( "user_pps", "all" );

  if ( $enabledTabsByAdmin == "all" )
  $tabs = [ "likes", "playlists", "subscriptions", "history", "purchased", "uploads" ];
  else
  $tabs = explode( ",", $enabledTabsByAdmin );

  bof()->call( "__custom", "user_library_tabs", $tab, $tabs );

  $tab = $loader->nest->user_input( "get", "tab", "in_array", [ "values" => $tabs ], $tabs[0] );
  $page = $loader->nest->user_input( "get", "page", "int", [ "min" => 2, "max" => 100 ], 1 );
  $limit = 50;

  $widgets = array();

  if ( $tab == "playlists" ){

    $widgets["playlists"] = array(
      "ID" => "playlists",
      "display" => array(
        "type" => "slider",
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => true,
        "title" => "",
        "pagination" => "user_library?tab=playlists&page=" . ($page+1),
        "link" => false,
        "classes" => [ "no_title" ],
        "link_on_bottom" => true
      ),
      "object" => array(
        "name" => "ugc_playlist",
        "whereArray" => array(
          "user_library_id" => $loader->user->get()->ID
        ),
        "selectArray" => array(
          "order_by" => "time_update",
          "order" => "DESC",
          "limit" => 50,
          "page" => $page
        )
      ),
    );

  }
  elseif( $tab == "likes" ){

    $tableCols = array(
      "object_type" => array(
        "func" => function( $data, $displayData ){
          return $data["on"];
        },
        "classes" => [ "date" ]
      ),
      "duration_hr" => array(
        "func" => function( $data, $displayData ){
          return "<a href='{$data["sub_link"]}'>{$data["sub_data"]}</a>";
        },
        "classes" => [ "date" ]
      ),
    );
    $tableLabels = array(
      [ "val" => "Duration", "class" => "date" ],
      [ "val" => "Duration", "class" => "date" ],
    );
    if ( bof()->request->is_mobile() ){
      unset( $tableCols["duration_hr"], $tableLabels[0] );
    }
    $widgets[ $tab ] = array(
      "ID" =>  $tab,
      "display" => array(
        "type" => "table",
        "table_columns" => $tableCols,
        "table_labels" => $tableLabels,
        "table_hide_cover" => false,
        "table_count" => false,
        "title" => "",
        "pagination" => "user_library?tab=likes&page=" . ($page+1),
        "link" => false,
        "classes" => [ "no_title no_thead smaller_cover playAsAction" ],
      ),
      "object" => array(
        "name" => "ugc_property",
        "whereArray" => array(
          "user_id" => $loader->user->get()->ID,
          "type" => "like",
        ),
        "selectArray" => array(
          "limit" => 20,
          "page" => $page,
          "library_page" => true,
          "order_by" => "time_add",
          "order" => "DESC"
        )
      ),
    );

  }
  elseif( $tab == "subscriptions" ){

    $widgets[ $tab ] = array(
      "ID" =>  $tab,
      "display" => array(
        "type" => "slider",
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => true,
        "title" => "",
        "pagination" => "user_library?tab={$tab}&page=" . ($page+1),
        "link" => false,
        "classes" => [ "no_title playAsAction" ],
        "link_on_bottom" => true
      ),
      "object" => array(
        "name" => "ugc_property",
        "whereArray" => array(
          "user_id" => $loader->user->get()->ID,
          "type" => "subscribe",
        ),
        "selectArray" => array(
          "limit" => 20,
          "page" => $page,
          "library_page" => true,
          "order_by" => "time_add",
          "order" => "DESC"
        )
      ),
    );

  }
  elseif( $tab == "purchased" ){

    $widgets[ $tab ] = array(
      "ID" =>  $tab,
      "display" => array(
        "type" => "slider",
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => true,
        "title" => "",
        "pagination" => "user_library?tab={$tab}&page=" . ($page+1),
        "link" => false,
        "classes" => [ "no_title playAsAction" ],
      ),
      "object" => array(
        "name" => "ugc_property",
        "whereArray" => array(
          "user_id" => $loader->user->get()->ID,
          "type" => "purchase",
        ),
        "selectArray" => array(
          "limit" => 50,
          "page" => $page,
          "library_page" => true,
          "order_by" => "time_add",
          "order" => "DESC"
        )
      ),
    );

  }
  elseif( $tab == "history" ){

    $tableCols = array(
      "object_type" => array(
        "func" => function( $data, $displayData ){
          return $data["on"];
        },
        "classes" => [ "date" ]
      ),
      "duration_hr" => array(
        "func" => function( $data, $displayData ){
          return "<a href='{$data["sub_link"]}'>{$data["sub_data"]}</a>";
        },
        "classes" => [ "date" ]
      ),
    );
    $tableLabels = array(
      [ "val" => "Duration", "class" => "date" ],
      [ "val" => "Duration", "class" => "date" ],
    );
    if ( bof()->request->is_mobile() ){
      unset( $tableCols["duration_hr"], $tableLabels[0] );
    }

    $widgets[ $tab ] = array(
      "ID" =>  $tab,
      "display" => array(
        "type" => "table",
        "table_columns" => $tableCols,
        "table_labels" => $tableLabels,
        "table_hide_cover" => false,
        "table_count" => false,
        "title" => "",
        "pagination" => "user_library?tab={$tab}&page=" . ($page+1),
        "link" => false,
        "classes" => [ "no_title no_thead smaller_cover playAsAction" ],
      ),
      "object" => array(
        "name" => "ugc_action",
        "whereArray" => array(
          "user_id" => $loader->user->get()->ID,
          "type" => "stream",
        ),
        "selectArray" => array(
          "limit" => 20,
          "page" => $page,
          "order_by" => "time_add",
          "order" => "DESC"
        )
      ),
    );

  }
  elseif( $tab == "uploads" ){

    $widgets[ $tab ] = array(
      "ID" =>  $tab,
      "display" => array(
        "type" => "slider",
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => true,
        "title" => "",
        "pagination" => "user_library?tab={$tab}&page=" . ($page+1),
        "link" => false,
        "classes" => [ "no_title playAsAction" ],
      ),
      "object" => array(
        "name" => "ugc_property",
        "whereArray" => array(
          "user_id" => $loader->user->get()->ID,
          "type" => "upload",
        ),
        "selectArray" => array(
          "limit" => false,
          "page" => $page,
          "library_page" => true,
          "order_by" => "time_add",
          "order" => "DESC"
        )
      ),
    );

  }

  bof()->call( "__custom", "user_library_widgets", $tab, $widgets );

  if ( !empty( $widgets[ $tab ] ) ){
    $widgets[ $tab ] = $loader->bofClient->__parse_widget( "mixed", null, $widgets[ $tab ] );
  }

  if ( empty( $widgets[ $tab ][ "items" ] ) )
  $widgets = [];

  $loader->api->set_message( "ok", array(
    "tab" => $tab,
    "page" => $page,
    "widgets" => array_values( $widgets ),
    "seo" => bof()->seo->fetch( [ "page_name" => "user_library" ] ),
    "tabs" => $tabs
  ) );

}

?>
