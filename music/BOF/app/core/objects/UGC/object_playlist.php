<?php

if ( !defined( "bof_root" ) ) die;

class object_ugc_playlist extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "ugc_playlist",
      "label" => "User Playlists",
      "icon" => "queue_music",
      "db_table_name" => "_u_playlists",
      "db_rel_table_name" => "_u_playlists_relations",
      "db_rel_table_col_name" => "playlist_id",
      "widgetable" => true,
      "client_single_disable_placeholder" => true
    );
  }
  public function columns(){
    return array(

      "name" => array(
        "public" => true,
        "label" => "Name",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_file_cover"]["image_thumb"];
              return $displayData;
            }
          ),
          "object" => array(
            "type" => "text",
            "required" => true,
            "seo_slug_source" => true
          )
        ),
      ),
      "user_id" => array(
        "label" => "Owner",
        "tip" => "The user who originally created this playlist or is in control of it now",
        "bofInput" => array(
          "object",
          array(
            "type" => "user"
          ),
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "col_user" => array(
              "title" => "Owner",
              "bofInput" => array(
                "object",
                array(
                  "type" => "user",
                  "multi" => true,
                )
              )
            ),
          )
        ),
        "selectors" => array(
          "user_id" => [ "user_id", "=" ],
          "col_user" => [ "user_id", "by_column" ],
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "parent_object_stats_column" => "s_playlists",
              "child_object" => "ugc_playlist",
              "child_object_selector_column" => "user_id",
              "limit" => 1,
              "delete_child_too" => true
            ),
          )
        ),
      ),
      "description" => array(
        "public" => true,
        "label" => "Description",
        "tip" => "A few words about this playlist. Visible to public",
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true,
            "strip_emoji" => false
          )
        ),
        "input" => array(
          "type" => "textarea"
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "cover_id" => array(
        "label" => "Cover",
        "input" => array(
          "name" => "cover_id",
          "type" => "bof_input"
        ),
        "bofInput" => array(
          "file",
          array(
            "type" => "image",
            "object_type" => "ugc_playlist_c"
          )
        ),
        "selectors" => array(
          "has_cover" => function( $val ){

            if ( $val > 0 )
            return [ "cover_id", ">", "0" ];

            return array(
              "oper" => "OR",
              "cond" => array(
                [ "cover_id", "=", "0" ],
                [ "cover_id", null, null, true ]
              )
            );

          },
        ),
        "bofAdmin" => array(
          "object" => array(),
          "filters" => array(
            "has_cover" => array(
              "title" => "Has cover",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ -1, "no" ],
                  [ 1, "yes" ],
                  [ "__all__", "all" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                [ "values" => [ "-1", "1", "__all__" ] ]
              )
            ),
          ),
        ),
      ),
      "private" => array(
        "label" => "Private",
        "public" => true,
        "input" => array(
          "type" => "checkbox"
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          )
        ),
        "bofAdmin" => array(
          "object" => array(),
          "filters" => array(
            "is_private" => array(
              "title" => "Private",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ "__all__", "All" ],
                  [ "1", "Private" ],
                  [ "0", "Public" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "__all__", "1", "0" ],
                  "empty()"
                )
              )
            ),
          )
        ),
        "selectors" => array(
          "is_private" => [ "private", "=" ],
        ),
      ),
      "object_type" => array(
        "public" => true,
        "label" => "Object type",
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        ),
      ),

      "s_views" => array(
        "public" => true,
        "label" => "Total Views",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      ),
      "s_views_unique" => array(
        "public" => true,
        "label" => "Total Views",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      ),
      "s_items" => array(
        "public" => true,
        "label" => "Total Items",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      ),
      "s_subscribers" => array(
        "public" => true,
        "label" => "Total Subscribers",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      ),

      "spotify_id" => array(
        "label" => "Spotify ID",
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
        "selectors" => array(
          "spotify_id" => [ "spotify_id", "=" ]
        )
      ),
      "extra_data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),

      "time_update" => array(
        "public" => true,
        "label" => "Update time",
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),

    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "hash",
      "time_add",
      "seo" => array(
        "o_title_format" => array(
          "title" => "playlist name",
          "owner_username" => "owner username"
        )
      ),
    );
  }
  public function relations(){
    return array(
      "_user" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_playlists",
          "child_object" => "ugc_playlist",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true
        ),
      ),
      "_items" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "ugc_playlist",
          "parent_object_stats_column" => "s_items",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "related_object_id",
          "child_object_where_array" => array(
            "type" => "playlist",
            [ "related_object_name", "=", "ugc_playlist" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "_subs" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "ugc_playlist",
          "parent_object_stats_column" => "s_subscribers",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "playlist_k",
            [ "object_name", "=", "ugc_playlist" ]
          ),
          "delete_child_too" => true
        ),
      ),
    );
  }
  public function selectors(){
    return array(
      "query" => [ "name", "LIKE%lower" ],
      "user_library_id" => function( $val ){

        if ( bof()->user->get()->ID )
        return array(
          "oper" => "OR",
          "cond" => array(
            [ "user_id", "=", bof()->user->get()->ID ],
            [ "ID", "IN", "SELECT object_id FROM ".( bof()->object->ugc_property->bof()["db_table_name"] )." WHERE user_id = ".( bof()->user->get()->ID )." AND type = 'playlist_k'", true ],
            [ "ID", "IN", "SELECT object_id FROM ".( bof()->object->ugc_property->bof()["db_table_name"] )." WHERE user_id = ".( bof()->user->get()->ID )." AND type = 'pl_collab'", true ],
          )
        );

        return [ "ID", "=", "0" ];

      },
      "user_access_id" => function( $val ){

        if ( bof()->user->get()->ID )
        return array(
          "oper" => "OR",
          "cond" => array(
            [ "user_id", "=", bof()->user->get()->ID ],
            [ "ID", "IN", "SELECT object_id FROM ".( bof()->object->ugc_property->bof()["db_table_name"] )." WHERE user_id = ".( bof()->user->get()->ID )." AND type = 'pl_collab'", true ],
          )
        );

        return [ "ID", "=", "0" ];

      }
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "user_playlist",
        "list_page_url" => "user_playlists",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "object" => array(
      )
    );
  }
  public function bof_client(){
    return array(
      "single_url_prefix" => "ugc_playlist",
      "buttons" => array(
        "link" => true,
        "share" => true,
        "play" => true,
        "playlist" => false,
        // "download_child" => true,
        "extra_after" => array(
          "delete" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;

              if ( $item["user_id"] == bof()->user->get()->ID ){

                $button = array(
                  "hook" => "manage",
                  "icon" => "cog-box",
                  "childs" => array(
                    array(
                      "hook" => "edit",
                      "icon" => "lead-pencil",
                      "action" => "playlist_edit",
                      "attr" => "data-play='{$item["hash"]}'",
                    ),
                    array(
                      "hook" => "delete",
                      "icon" => "lead-pencil",
                      "action" => "playlist_delete_confirm",
                      "attr" => "data-play='{$item["hash"]}'",
                    )
                  )
                );

              }
              else {

                $button = array(
                  "hook" => "add_to_library",
                  "icon" => "music-box-multiple",
                  "action" => "playlist_keep",
                  "attr" => "data-play='{$item["hash"]}'"
                );

                if ( bof()->user->get()->ID ){

                  $array = array(
                    "user_id" => bof()->user->get()->ID,
                    "type" => "playlist_k",
                    "object_name" => "ugc_playlist",
                    "object_id" => $item["ID"]
                  );

                  if ( bof()->object->ugc_property->select( $array ) ){
                    $button = array(
                      "hook" => "remove_from_library",
                      "icon" => "music-box-multiple",
                      "action" => "playlist_lose",
                      "attr" => "data-play='{$item["hash"]}'"
                    );
                  }

                }

              }

              return $button;

            }
          ),
        )
      )
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $deleting = false;
    $editing = false;
    $client_single = false;
    $client_widget = false;
    $muse_source = false;
    $as_widget = false;
    $match_page = false;
    $muse_request_download = false;
    $search_terms = false;
    $_eq = [];
    extract( $selectArgs );
    $selectArgs["_eq"] = $_eq;

    if ( $listing || $search || $as_widget || $client_single ){
      $_eq["cover"] = [];
    }

    if ( $client_single || $as_widget ){
      $_eq["user"] = array(
        "_eq" => array(
          "avatar" => [],
        ),
        "public" => true
      );
    }

    if ( $client_single || $client_widget || $muse_source || $muse_request_download ){

      $_eq["items"] = array(
        "_eq" => array(
          "cover" => []
        ),
        "public" => true
      );

    }

    if ( $search_terms ){
      $_eq["user"] = ["clean"=>false];
    }

    if ( $muse_request_download )
    $selectArgs["muse_source"] = true;

    if ( $match_page ){
      $_eq["user"] = ["clean"=>false];
    }

    if ( $muse_source ){
      $_eq["items"]["limit"] = $this->get_muse_limit();
    }

    if ( $client_single || $match_page ){

      if ( bof()->user->get()->ID ){
        $whereArgs[] = array(
          "oper" => "OR",
          "cond" => array(
            [ "private", "=", "0" ],
            [ "user_id", "=", bof()->user->get()->ID ],
            [ "ID", "IN", "SELECT object_id FROM _u_properties WHERE `type` = 'pl_collab' AND `object_name` = 'ugc_playlist' AND user_id = '".bof()->user->get()->ID."' ", true ]
          )
        );
      }
      else {
        $whereArgs["is_private"] = 0;
      }

    }

    if ( $as_widget ){
      if ( bof()->user->get()->ID ){

        if ( empty( $whereArgs["user_library_id"] ) ){
          $whereArgs[] = array(
            "oper" => "OR",
            "cond" => array(
              [ "private", "=", "0" ],
              [ "user_id", "=", bof()->user->get()->ID ]
            )
          );
        } else {
          $whereArgs[] = array(
            "oper" => "OR",
            "cond" => array(
              [ "private", "=", "0" ],
              [ "user_id", "=", bof()->user->get()->ID ],
              [ "ID", "IN", "SELECT object_id FROM _u_properties WHERE `type` = 'pl_collab' AND `object_name` = 'ugc_playlist' AND user_id = '".(bof()->user->get()->ID)."' ", true ]
            )
          );
        }
        
      }
      else {
        $whereArgs["is_private"] = 0;
      }

    }
    
    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function create( $whereArray, $insertArray, $updateArray=false ){

    $insertArray["hash"] = !empty( $insertArray["hash"] ) ? $insertArray["hash"] : $this->_bof_this->get_free_hash();
    $insertArray["seo_url"] = !empty( $insertArray["seo_url"] ) ? $insertArray["seo_url"] : $this->_bof_this->get_free_url( $insertArray["name"] );
    $db_id = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray );
    return $db_id;

  }
  public function delete( $whereArray ){

    $items = $this->_bof_this->select(
      $whereArray,
      array(
        "limit" => 200
      )
    );

    $delete = bof()->object->_delete( $this, $whereArray );

    if ( $delete && $items ){
      foreach( $items as $_item ){
        bof()->object->ugc_property->delete(
          array(
            "type" => "playlist",
            "related_object_name" => "ugc_playlist",
            "related_object_id" => $_item["ID"]
          )
        );
      }
    }
    return $delete;

  }
  public function clean( $item, $args=[] ){

    $_eq = [];
    $search = false;
    $listing = false;
    $muse_source = false;
    $as_widget = false;
    $match_page = false;
    $muse_request_download = false;
    extract( $args );

    if ( in_array( "cover", array_keys( $_eq ), true ) && empty( $item["bof_file_cover"] ) && !empty( $item["extra_data_decoded"]["spotify_images"] ) && client_auto_images ){
      foreach( $item["extra_data_decoded"]["spotify_images"] as $_s_cover )
      $_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
      $_images = array_keys( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_strings"] = bof()->image->html( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_thumb"] = end( $_images );
    }

    if ( $as_widget && empty( $item["bof_file_cover"] ) ){
      $_eq["items"] = array(
        "_eq" => array(
          "cover" => [],
        ),
        "limit" => 6
      );
    }
    if ( in_array( "items", array_keys( $_eq ), true ) ){

      $page = 1;
      if ( bof()->endpoint->get()->name == "bofClient_list" )
      $page = bof()->nest->user_input( "get", "page", "int", [ "min" => 2, "max" => 100 ], 1 );
      $limitPerPage = !empty( $_eq["items"]["limit"] ) ? $_eq["items"]["limit"] : 40;

      $items = [];
      $properties = bof()->object->ugc_property->select(
        array(
          "type" => "playlist",
          "related_object_name" => "ugc_playlist",
          "related_object_id" => $item["ID"]
        ),
        array(
          "order_by" => "i",
          "order" => "DESC",
          "offset" => ( $page - 1 ) * $limitPerPage,
          "limit" => $limitPerPage
        )
      );

      if ( $properties ){
        foreach( $properties as $property ){

          if ( bof()->object->core_files->validate_key( "object", $property["object_name"] ) ){

            $property_object = bof()->object->__get( $property["object_name"] );
            $property_item = $property_object->select(
              array(
                "ID" => $property["object_id"]
              ),
              array(
                "as_widget" => true,
                "_eq" => array(
                  $muse_source ? "sources" : "_nvm" => []
                ),
                "muse_source" => $muse_source
              )
            );

            if ( $property_item ){

              $_d = $property;
              unset( $_d["property"] );

              $property_item["raw"]["property"] = $_d;
              $property_item["ot"] = $property["object_name"];
              $property_item["buttons"] = bof()->bofClient->__parse_item_buttons(
                $property["object_name"],
                $property_object,
                !empty( $property_item["raw"] ) ? $property_item["raw"] : null,
                $property_object->bof_client()["buttons"]
              );

              if (
                bof()->user->get()->ID === $item["user_id"] ||
                [ "ID", "IN", "SELECT object_id FROM ".( bof()->object->ugc_property->bof()["db_table_name"] )." WHERE user_id = ".( bof()->user->get()->ID )." AND type = 'pl_collab'", true ]
              ){
                $property_item["buttons"]["items"]["remove_from_playlist"] = array(
                  "icon" => "skull",
                  "title" => "Remove from playlist",
                );
              }

            }

            $property_item["i"] = $property["i"];
            if ( !empty( $property_item["buttons"]["items"]["remove_from_playlist"] ) ){
              $property_item["buttons"]["items"]["remove_from_playlist"]["i"] = $property["i"];
            }
            $items[] = $property_item;

          }

        }
      }

      $item["items"] = $items;

    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"],
        "image" => null
      );
    }
    if ( $match_page ){
      $item = array(
        "title" => $item["name"],
        "owner_username" => !empty( $item["bof_dir_user"]["username"] ) ? $item["bof_dir_user"]["username"] : null,
        "seo_url" => $item["seo_url"],
        "cover_id" => $item["cover_id"]
      );
    }
    if ( $muse_source ){
      foreach( $items as $_item ){
        $item["sources"] = empty( $item["sources"] ) ? $_item["raw"]["sources"] : array_merge( $item["sources"], $_item["raw"]["sources"] );
      }
    }

    return $item;

  }
  public function clean_as_widget( $item, $args ){

    if ( empty( $item["bof_file_cover"] ) && !empty( $item["items"] ) ){

      $_items_covers = [];
      foreach( $item["items"] as $_item ){
        if ( !empty( $_item["cover"] ) )
        $_items_covers[] = "<div class='_l'>". $_item["cover"]["image_strings"][4]["html"] ."</div>";
      }

      if ( $_items_covers ? count( $_items_covers ) >= 4 : false ){

        $_items_covers = array_slice( $_items_covers, 0, 4 );
        $item["bof_file_cover"] = array( "image_strings" => array() );
        for( $i=1; $i<=12; $i++ )
        $item["bof_file_cover"]["image_strings"][$i]["html"] = "<div class='multi t4'>".(implode("",$_items_covers))."</div>";

      }

    }

    return array(
      "title" => $item["name"],
      "hash" => $item["hash"],
      "ID" => $item["hash"],
      "count" => intval( $item["s_items"] ),
      "ot" => "ugc_playlist",
      "object_type" => "ugc_playlist",
      "sub_data" => !empty( $item["bof_dir_user"]["username"] ) ? $item["bof_dir_user"]["username"] : null,
      "sub_link" => !empty( $item["bof_dir_user"]["url"] ) ? $item["bof_dir_user"]["url"] : null,
      "cover" =>  !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"] : null,
      "raw" => $item
    );

  }
  public function clean_client_single( $item, $args ){

    $page = [];

    if ( $item["object_type"] == "empty" ){
      $item["object_title"] = "";
    } elseif( $item["object_type"] == "mixed" ){
      $item["object_title"] = bof()->object->language->turn("items",[],["uc_first"=>true,"lang"=>"users"]);
    } else {
      $item["object_title"] = bof()->object->language->turn($item["object_type"],[],["uc_first"=>true,"lang"=>"users"]);
    }

    if ( !empty( $item["bof_dir_user"] ) ){
      $item["sub_title"] = "@" . $item["bof_dir_user"]["username"];
      $item["sub_link"] = "user/" . $item["bof_dir_user"]["username"];
    }


    $owner = false;
    if ( bof()->user->get()->ID === $item["user_id"] ){
      $owner = true;
    }

    $widgets = [];

    $widgets["items"] = $this->_bof_this->clean_client_single_widget( $item, "items", $args );
    $widgets["items"]["display"]["title"] = $item["object_title"];

    $page["classes"] = [];
    $page["classes"][] = $owner ? "owner" : "";
    if ( empty( $item["bof_file_cover"] ) )
    $page["classes"][] = "no_cover";

    $item["head_play_title"] = $item["name"];

    $item["stats"] = [];

    if ( !empty( $item["time_update"] ) )
    $item["stats"][] = array(
      "icon" => "clock-time-two",
      "value" => turn( "last_update" ) . ": <b>" . bof()->general->passed_time_hr( time() - strtotime($item["time_update"]), [ "translate" => true ] )["string"] . "</b>"
    );

    if ( !empty( $item["s_subscribers"] ) ? $item["s_subscribers"] > 10 : false )
    $item["stats"][] = array(
      "icon" => "account-multiple-outline",
      "value" => turn( "subscribers" ) . ": <b>" . number_format( $item["s_subscribers"] ) . "</b>"
    );

    if ( !empty( $item["s_items"] ) ? $item["s_items"] : false )
    $item["stats"][] = array(
      "icon" => "playlist-music",
      "value" => turn( "items" ) . ": <b>" . number_format( $item["s_items"] ) . "</b>"
    );

    if ( empty( $item["stats"] ) )
    $item["stats"][] = array(
      "icon" => "clock-time-two",
      "value" => turn( "signup_time" ) . ": <b>" . bof()->general->passed_time_hr( time() - strtotime($item["time_add"]), [ "translate" => true ] )["string"] . "</b>"
    );

    return array(
      "widgets" => $widgets,
      "data" => $item,
      "page" => $page
    );

  }
  public function clean_client_single_widget( $item, $widget_name, $args=[], $caller="self" ){

    $widgets = array(
      "items" => array(
        "ID" => "items",
        "display" => array(
          "type" => "table",
          "title" => "Items",
          "pagination" => "list/bof_ugc_playlist?slug={$item["seo_url"]}&widget=items&page=2",
          "link" => false,
          "table_columns" => array(
            "object_hr" => array(
              "func" => function( $data, $displayData, $loader ){
                return bof()->object->language->turn( $data["ot"], [], [ "uc_first" => true, "lang" => "users" ] );
              },
              "classes" => [ "_ot" ]
            ),
          ),
          "table_labels" => array(
            [ "val" => "Object", "class" => "_ot" ]
          ),
          "table_hide_cover" => false,
          "table_count" => false,
          "classes" => [ " playAsAction", "no_thead" ]
        ),
        "display_ol" => array(
          "type" => "table",
          "table_columns" => array(
            "object_type" => array(
              "func" => function( $data, $displayData ){
                return bof()->object->language->turn( $data["ot"], [], [ "uc_first" => true, "lang" => "users" ] );
              },
              "classes" => [ "_ot" ]
            ),
          ),
          "table_labels" => array(
            [ "val" => "Object", "class" => "_ot" ]
          ),
          "table_hide_cover" => false,
          "table_count" => false,
          "classes" => [ "t_p playAsAction" ]
        ),
        "object" => array(
          "name" => "ugc_property",
          "whereArray" => array(
            "type" => "playlist",
            "related_object_name" => "ugc_playlist",
            "related_object_id" => $item["ID"]
          ),
          "selectArray" => array(
            "order_by" => "i",
            "order" => "DESC",
            "limit" => 40
          )
        ),
        "items" => !empty( $item["items"] ) ? $item["items"] : null
      )
    );

    return !empty( $widgets[ $widget_name ] ) ? $widgets[ $widget_name ] : null;

  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["name"] => 1,
    );
    
    if ( !empty( $item["bof_dir_user"]["username"] ) ){
      $o[ $item["bof_dir_user"]["username"] ] = 0.6;
    }

    return $o;

  }

  public function catch_type( $item, $updateItem=true ){

    $ID = is_array( $item ) ? $item["ID"] : $item;

    $object_names = bof()->db->_select(array(
      "table" => bof()->object->ugc_property->bof()["db_table_name"],
      "columns" => "object_name",
      "where" => array(
        [ "type", "=", "playlist" ],
        [ "related_object_name", "=", "ugc_playlist" ],
        [ "related_object_id", "=", $ID ]
      ),
      "group" => "GROUP BY object_name",
      "limit" => 2,
      "single" => false,
      "cache_load_rt" => false,
      "cache" => false
    ));

    if ( !$object_names )
    $playlist_type = "empty";

    elseif ( count( $object_names ) > 1 )
    $playlist_type = "mixed";

    else
    $playlist_type = reset( $object_names )["object_name"];

    if ( $updateItem ){
      $this->_bof_this->update(
        array(
          "ID" => $ID
        ),
        array(
          "object_type" => $playlist_type
        )
      );
    }

    return $playlist_type;


  }
  public function get_sources( $item, $args=[] ){

    $sources = [];

    if ( !empty( $item["items"] ) ){
      foreach( $item["items"] as $_item ){
        $_sources = bof()->object->__get( $_item["ot"] )->get_sources( $_item["raw"], $args );
        if ( $_sources ){
          foreach( $_sources as $_si => $_source ){
            $sources[ $_si ] = $_source;
          }
        }
      }
    }

    return $sources;

  }
  public function get_infinite_siblings( $item, $user_inputs, $object_data ){

    $items = array(
      "object_name" => "ugc_property",
      "where_array" => array(
        "type" => "playlist",
        "related_object_name" => "ugc_playlist",
        "related_object_id" => $item["ID"]
      ),
      "select_array" => array(
        "order_by" => "i",
        "order" => "DESC",
        "limit" => 10,
      ),
      "offset" => 40
    );

    $items["offset"] = $this->get_muse_limit();

    return $items;

  }

  public function download_child( $button, $item, $args ){

    $sources = [];

      $_item = $this->_bof_this->sid(
        $item["ID"],
        array(
          "as_widget" => true,
          "muse_request_download" => true,
          "_eq" => array(
            "sources" => array(
              "for_download" => true
            ),
            "items" => [],
            "user" => [],
            "cover" => []
          )
        )
      );

      if ( !empty( $_item["raw"]["items"] ) )
      $item["items"] = $_item["raw"]["items"];

    if ( empty( $item["items"] ) )
    return false;

    foreach( $item["items"] as $item ){

      $_p = $item["raw"]["property"];
      $_i = bof()->object->__get( $_p["object_name"] )->select( array(
        "ID" => $_p["object_id"]
      ), array(
        "muse_source" => true,
        "as_widget" => true,
        "_eq" => array(
          "sources" => [],
        ),
        "cache_load_rt" => false
      ) )["raw"];

      if ( !empty( $_i["bof_dir_sources"] ) ){

        usort( $_i["bof_dir_sources"], function($a, $b) {
          return $a['download_able'] < $b['download_able'] ? 1 : 0;
        });

        $_source = reset( $_i["bof_dir_sources"] );
        $_source["real_ot"] =$_p["object_name"];
        $_source["real_oh"] = $_i["hash"];
        $sources = array_merge( $sources, [ $_source ] );
      }

    }

    $item["bof_dir_sources"] = $sources;

    return $item;

  }

  protected function get_muse_limit(){

    $range = 40;
    if ( defined("player_playlist_range") )
    $range = player_playlist_range;

    return $range;

  }

}

?>
