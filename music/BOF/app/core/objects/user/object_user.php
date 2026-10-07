<?php

if ( !defined( "bof_root" ) ) die;

class object_user extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "user",
      "label" => "User",
      "icon" => "person",
      "db_table_name" => "_u_list",
      "db_rel_table_name" => "_u_relations",
      "db_rel_table_col_name" => "user_id",
      "widgetable" => true,
    );
  }
  public function columns(){
    return array(
      "username" => array(
        "public" => true,
        "label" => "Username",
        "validator" => "username",
        "selectors" => array(
          "username" => [ "username", "=" ]
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_file_avatar"] ) )
              $displayData["image_preview"] = $item["bof_file_avatar"]["image_thumb"];
              $_sub = $item["email"];
              if ( empty( $item["time_verify"] ) ) $_sub .= " <b style='color:rgb(var(--c_red))'>Unverified</b>";
              $displayData["data"] = $displayData["data"] . "<span class='sub'>{$_sub}</div>";
              return $displayData;
            },
          ),
          "object" => array(
            "required" => true,
            "seo_slug_source" => true,
          )
        )
      ),
      "name" => array(
        "public" => true,
        "label" => "Name",
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => []
        )
      ),
      "password" => array(
        "label" => "Password",
        "tip" => "Enter a new password if you wish to change the old one",
        "validator" => "raw",
        "selectors" => array(
          "password" => [ "password", "=" ]
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "object" => array(
            "group" => "password",
          )
        )
      ),
      "email" => array(
        "label" => "Email",
        "validator" => array(
          "email",
          array( "empty()" )
        ),
        "selectors" => array(
          "email" => [ "email", "=" ]
        ),
        "input" => array(
          "type" => "email"
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          )
        )
      ),
      "phone" => array(
        "label" => "Phone",
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[0-9+\\- ]",
            "empty()"
          )
        ),
        "selectors" => array(
          "phone" => [ "phone", "=" ],
          "phone_suffix" => [ "phone", "LIKE" ]
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "role_ids" => array(
        "label" => "Roles",
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[0-9,]"
          )
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user_role",
            "multi" => true,
            "autoload" => false
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "tags",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = "<ul>";
              foreach( $item["roles_parsed"]["_raw"] as $_role ){
                $displayData["data"] .= "<li><span class='_role {$_role["type"]}'>{$_role["name"]}</span></li>";
              }
              $displayData["data"] .= "</ul>";
              return $displayData;
            },
          ),
          "object" => array(
            "required" => true
          )
        ),
        "relations" => array(
          "user_role" => array(
            "exec" => array(
              "type" => "hub",
              "hub_type" => "role",
              "parent_object" => "user",
              "parent_object_list_column" => "role_ids",
              "child_object" => "user_role",
              "child_object_stats_column" => "s_users"
            ),
          )
        )
      ),
      "avatar_id" => array(
        "label" => "Avatar",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofInput" => array(
          "file",
          array(
            "type" => "image",
            "object_type" => "user_avatar"
          )
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "bg_img_id" => array(
        "label" => "Background",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofInput" => array(
          "file",
          array(
            "type" => "image",
            "object_type" => "user_bg"
          ),
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "fund" => array(
        "label" => "Funds",
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => false,
            "forceZero" => true
          )
        ),
        "input" => array(
          "type" => "digit"
        ),
        "accept_zero" => true,
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "details",
            "renderer" => function( $displayItem, $item, $displayData ){

              $displayData["data"] = "<ul>";

              $displayData["data"] .= "<li><b>Fund</b>" . ( $item["fund"] ? "<i style='color:rgba(var(--theme_color),0.6)'>{$item["fund"]}</i>" : "0" ) . "</li>";
              if ( $item["fund_by_deposit"] ) $displayData["data"] .= "<li><b>Deposits</b>" . $item["fund_by_deposit"] . "</li>";
              if ( $item["fund_by_revenue"] ) $displayData["data"] .= "<li><b>Earnings</b>" . $item["fund_by_revenue"] . "</li>";
              if ( $item["s_transactions"] ) $displayData["data"] .= "<li><b>Transactions</b>" . number_format( $item["s_transactions"] ) . "</li>";

              $displayData["data"] .= "</ul>";
              return $displayData;

            },
          ),
          "object" => array(
            "group" => "funds"
          )
        )
      ),
      "fund_by_deposit" => array(
        "label" => "Deposits",
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "fund_by_revenue" => array(
        "label" => "Earnings",
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "s_posts" => array(
        "public" => true,
        "label" => "Total Blog Posts",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "s_followers" => array(
        "public" => true,
        "label" => "Total Followers",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "s_followings" => array(
        "public" => true,
        "label" => "Total Followings",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "s_subscriptions" => array(
        "public" => true,
        "label" => "Total Subscriptions",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "s_likes" => array(
        "public" => true,
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        )
      ),
      "s_playlists" => array(
        "public" => true,
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        )
      ),
      "s_playlists_followers" => array(
        "public" => true,
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        )
      ),
      "s_payments" => array(
        "public" => true,
        "label" => "Total Payments",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "s_transactions" => array(
        "public" => true,
        "label" => "Total Transactions",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      "feed_setting" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),
      "not_setting" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),
      "email_setting" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),
      "time_verify" => array(
        "label" => "Verification Time",
        "validator" => array(
          "timestamp",
          array(
           "empty()"
         ),
       ),
       "input" => array(
         "type" => "time",
       ),
       "bofAdmin" => array(
         "sortable" => true,
         "object" => []
       )
      ),
      "time_notified" => array(
        "validator" => array(
          "timestamp",
          array(
           "empty()"
          )
        )
      ),
      "time_online" => array(
        "validator" => array(
          "timestamp",
          array(
           "empty()"
          )
        )
      ),
      "time_verify_try" => array(
        "validator" => array(
          "timestamp",
          array(
           "empty()"
         ),
       ),
      ),
      "verification_code" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "extraData" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "external_addresses" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
    );
  }
  public function stats_columns(){
    return array();
  }
  public function selectors(){
    return array(
      "query" => function( $val ){
        if ( bof()->getName() == "bof_admin" ){
          return array(
            "oper" => "OR",
            "cond" => array(
              [ "username", "LIKE%", $val ],
              [ "email", "LIKE%", $val ]
            )
          );
        } else {
          return [ "username", "LIKE%", $val ];
        }
      },
      "seo_url" => [ "username", "=" ],
      "verification_code" => [ "verification_code", "=" ],
      "is_subscribed" => function( $val ){
        $val = $val == "yes" ? true : false;
        return $val ?
          [ "ID", "IN", "SELECT user_id FROM `_u_subs` WHERE time_expire > now()", true ] :
          [ "ID", "NOT IN", "SELECT user_id FROM `_u_subs` WHERE time_expire > now()", true ];

      },
      "follower_of" => function( $val ){
        if ( !bof()->general->numeric( $val ) ) return;
        return [ "ID", "IN", "SELECT user_id  FROM `_u_properties` WHERE `type` = 'subscribe' AND `object_name` = 'user' AND object_id = {$val}", true ];
      },
      "pl_subs" => function( $val ){
        if ( $val )
        return array(
          "oper" => "OR",
          "cond" => array(
            [ "ID", "=", " ( SELECT user_id FROM `_u_playlists` WHERE ID = '{$val}' ) ", true ],
            [ "ID", "IN", "SELECT user_id FROM `_u_properties` WHERE ( type = 'pl_collab' OR type = 'playlist_k' ) AND object_name = 'ugc_playlist' AND object_id = '{$val}' ", true ],
          )
        );
        else
        return [ "ID", "=", "0" ];
      },
      "pl_collab" => function( $val ){
        if ( $val )
        return [ "ID", "IN", "SELECT user_id FROM `_u_properties` WHERE type = 'pl_collab' AND object_name = 'ugc_playlist' AND object_id = '{$val}' ", true ];
        else
        return [ "ID", "=", "0" ];
      },
      "role_type" => function( $val ){
        return bof()->object->user->select_role_type( $val );
      },
      "type" => function( $val ){
        return bof()->object->user->selectors()["role_type"]( $val );
      }
      // "ms_group_users" => [ "ID", "parent_with_relations", "rel_parent" ],
    );
  }
  public function select_role_type( $val ){
    if ( !in_array( $val, [ "admin", "moderator", "user" ], true ) ) return;
    return [ "ID", "IN", "SELECT user_id FROM `_u_relations` WHERE type = 'role' AND target_id IN ( SELECT ID FROM _u_roles WHERE _u_roles.type = '{$val}' )", true ];
  }
  public function relations(){
    return array(
      "subs" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "child_object" => "user_subs",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true,
        ),
      ),
      "playlists" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_playlists",
          "child_object" => "ugc_playlist",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true
        ),
      ),
      "likes" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_likes",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "user_id",
          "child_object_where_array" => array(
            "type" => "like"
          ),
          "delete_child_too" => true
        ),
      ),
      "subscriptions" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_subscriptions",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "user_id",
          "child_object_where_array" => array(
            "type" => "subscribe",
            [ "object_name", "!=", "user" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "followings" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_followings",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "user_id",
          "child_object_where_array" => array(
            "type" => "subscribe",
            [ "object_name", "=", "user" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "followers" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_followers",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "subscribe",
            [ "object_name", "=", "user" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "requests" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "child_object" => "user_request",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true,
        ),
      ),
      "withdrawals" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "child_object" => "user_withdraw",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true,
        ),
      ),
      "notifications" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "child_object" => "user_notification",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true,
        ),
      ),
    );
  }
  public function bof_columns(){
    return array(
      "hash",
      "ID",
      "time_add",
      "social_links"
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
        "edit_page_url" => "user",
        "list_page_url" => "user_list",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "buttons" => array(),
      "filters" => array(
        "role_type" => array(
          "title" => "Type",
          "input" => array(
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "user", "User" ],
              [ "moderator", "Moderator" ],
              [ "admin", "Admin" ],
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "user", "moderator", "admin", "__all__" ] ]
          )
        ),
        "is_subscribed" => array(
          "title" => "Subscribed users",
          "input" => array(
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "yes", "Yes" ],
              [ "no", "No" ],
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "yes", "no", "__all__" ] ]
          )
        ),
      ),
      "list" => array(
        "username" => null,
        "role_ids" => null,
        "fund" => null,
        "stats" => array(
          "type" => "simple",
          "label" => "Stats",
          "class" => "details",
          "renderer" => function ( $displayItem, $item, $displayData ){

            $displayData["data"] = "<ul>";

            $displayData["data"] .= "<li><b>Online</b> ".($item["time_online"]?bof()->general->passed_time_from_time_hr($item["time_online"]):"-")."</li>";
            $displayData["data"] .= "<li><b>Followers</b> {$item["s_followers"]}</li>";
            $displayData["data"] .= "<li><b>Likes</b> {$item["s_likes"]}</li>";
            if ( $item["s_posts"] ) $displayData["data"] .= "<li><b>Blog Posts</b>" . number_format( $item["s_posts"] ) . "</li>";

            $displayData["data"] .= "</ul>";
            return $displayData;

          },
        )
      ),
      "object" => array(
      ),
      "object_be_renderer" => function ( $_inputs, $request ){

        if ( $request["type"] != "multi" ){

          $request_id = !empty( $request["IDS"][0] ) ? $request["IDS"][0] : false;

          if ( !empty( $_inputs["data"]["username"] ) ){
            $username = $_inputs["data"]["username"];
            $check_username = bof()->object->user->select(array(
              "username" => $username
            ));
            if ( $check_username ? ( $request_id ? $check_username["ID"] != $request_id : true ) : false ){
              $_inputs["report"]["fail"]["username"] = "Already in use";
            }
          }

          if ( !empty( $_inputs["data"]["email"] ) ){
            $email = $_inputs["data"]["email"];
            $check_email = bof()->object->user->select(array(
              "email" => $email
            ));
            if ( $check_email ? ( $request_id ? $check_email["ID"] != $request_id : true ) : false ){
              $_inputs["report"]["fail"]["email"] = "Already in use";
            }
          }

        }

        return $_inputs;

      },
      "object_ui_renderer" => function( $object, $parsed, $args, $request, &$_inputs, &$data ){
        $_inputs["password"]["input"]["value"] = null;
      },
      "object_groups" => array(
        [ "password", "Password" ],
        [ "funds", "Funds" ],
      ),
      "buttons" => array(
        "list_transactions" => array(
          "id" => "list_transactions",
          "label" => "List Tranactions",
        ),
        "list_payments" => array(
          "id" => "list_payments",
          "label" => "List Payments",
        ),
        "list_subs" => array(
          "id" => "list_subs",
          "label" => "List Subscriptions",
        ),
        "list_properties" => array(
          "id" => "list_properties",
          "label" => "List Properties",
        ),
        "list_playlists" => array(
          "id" => "list_playlists",
          "label" => "List Playlists",
        ),
        "list_posts" => array(
          "id" => "list_posts",
          "label" => "List Posts",
        ),
      ),
      "buttons_renderer" => function( $item, &$buttons ){

        $buttons["list_transactions"]["link"] = "transactions?col_user={$item["ID"]}";
        $buttons["list_payments"]["link"] = "payments?col_user={$item["ID"]}";
        $buttons["list_subs"]["link"] = "user_subs?col_user={$item["ID"]}";
        $buttons["list_properties"]["link"] = "user_properties?col_user={$item["ID"]}";
        $buttons["list_playlists"]["link"] = "user_playlists?col_user={$item["ID"]}";
        $buttons["list_posts"]["link"] = "blog_posts?col_user={$item["ID"]}";

      },
    );
  }
  public function bof_client(){
    return array(
      "single_url_prefix" => "user",
      "user_browse" => true,
      "buttons" => array(
        "link" => true,
        "share" => true,
        "subscribe" => true,
        "social_links" => true,
        "extra_after" => array(
          "library" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){
              $button = null;
              if ( bof()->user->get()->ID === $item["ID"] ){
                $button = array(
                  "icon" => "music-box-multiple",
                  "hook" => "library",
                  "url" => "user_library"
                );
              }
              return $button;
            }
          ),
          "edit_profile" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){
              $button = null;
              if ( bof()->user->get()->ID === $item["ID"] ){
                $button = array(
                  "icon" => "cog",
                  "hook" => "edit_profile",
                  "url" => "user_edit"
                );
              }
              return $button;
            }
          ),
        ),
      )
    );
  }

  // BusyOwlFramework helpers
  public function create( $whereArray, $insertArray, $updateArray=false, $returnDetails=false, $exeRelations=true ){

    $initial = false;
    if ( in_array( "initial", array_keys( $insertArray ), true ) ){
      $initial = $insertArray["initial"];
      unset( $insertArray["initial"] );
    }

    if ( !empty( $insertArray["password"] ) ){
      if ( empty( $updateArray ) ) $updateArray = [];
      $insertArray["password"] = $updateArray["password"] = $this->_bof_this->hash_password( $insertArray["password"] );
    } else {
      if ( $updateArray ? in_array( "password", array_keys( $updateArray ), true ) : false )
      unset( $updateArray["password"] );
    }

    if ( !empty( $insertArray["password_hashed"] ) ){
      $insertArray["password"] = $insertArray["password_hashed"];
      unset( $insertArray["password_hashed"] );
    }

    if ( empty( $insertArray["role_ids"] ) )
    $insertArray["role_ids"] = "2";

    $insertArray["hash"] = !empty( $insertArray["hash"] ) ? $insertArray["hash"] : $this->_bof_this->get_free_hash();

    $db_id = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray, $returnDetails, $exeRelations );

    if ( $initial ){
      bof()->object->user_setting->set( $db_id, "notify_email", bof()->object->db_setting->get( "ma_sub_default", 0 ) ? 1 : 0 );
    }

    return $db_id;

  }
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $client_single = false;
    $client_widget = false;
    $as_widget = false;
    $_eq = [];
    extract( $selectArgs );

    if ( in_array( "cover", array_keys( $_eq ), true ) ){
      $_eq["avatar"] = $_eq["cover"];
    }

    if ( $search || $listing || $client_widget || $as_widget )
    $_eq["avatar"] = [];

    if ( $listing )
    $_eq["roles"] = true;

    if ( $client_single ){
      $_eq["bg_img"] = [];
      $_eq["avatar"] = [];
    }

    $selectArgs["_eq"] = $_eq;

    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function select_by_id( $ID ){

    return $this->select(array(
      "ID" => $ID
    ));

  }
  public function clean( $item, $args ){

    $search = false;
    $_eq = [];
    extract( $args );

    $item["name_styled"] = !empty( $item["name"] ) ? $item["name"] : $item["username"];

    if ( in_array( "avatar", array_keys( $_eq ), true ) && empty( $item["bof_file_avatar"] ) && ( $default_profile_img = bof()->object->db_setting->get( "phu_avatar" ) ) ){
      $item["bof_file_avatar"] = bof()->object->file->select( [ "ID" => $default_profile_img ], $_eq["avatar"] );
    }

    if ( in_array( "bg_img", array_keys( $_eq ), true ) && empty( $item["bof_file_bg_img"] ) && ( $default_profile_img = bof()->object->db_setting->get( "phu_bg" ) ) ){
      $item["bof_file_bg_img"] = bof()->object->file->select( [ "ID" => $default_profile_img ] );
    }

    if ( !empty( $item["external_addresses"] ) ){
      $item["external_addresses_decoded"] = json_decode( $item["external_addresses"], true );
    }

    $item["roles_parsed"] = null;
    if ( !empty( $_eq[ "roles" ] ) && !empty( $item["role_ids"] ) )
    $item["roles_parsed"] = bof()->object->user_role->parse_users( $item );

    if ( !empty( $_eq["manager_role"] ) ){
      $item["manager_role"] = bof()->object->user_role->parse_managers( $item, $_eq["manager_role"]["type"] );
    }

    $item["url"] = bof()->seo->url( $this->_bof_this, $item, "username" );

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["username"],
        "image" => !empty( $item["bof_file_avatar"]["image_thumb"] ) ? $item["bof_file_avatar"]["image_thumb"] : false
      );
    }

    return $item;

  }
  public function clean_as_widget( $item, $args=[] ){

    return array(
      "title" => $item["username"],
      "sub_data" => $item["name_styled"],
      "cover" =>  !empty( $item["bof_file_avatar"] ) ? $item["bof_file_avatar"] : null,
      "ot" => "user",
      "raw" => $item
    );

  }
  public function delete( $whereArray, $exeRelations=true ){

    bof()->general->set_full_fall(false);
    foreach( bof()->object->user->select( $whereArray, [ "single" => false, "limit" => false, "clean" => false ] ) as $_u ){
      foreach( array(
        "session", "file", "user_setting", "b_post", "ugc_action", "ugc_playlist", "ugc_property", "payment", "transaction", "user_subs",
        "p_ai_creator", "p_ai_show", "p_ai_episode", "p_ai_job",
        "m_album" => "uploader_id",
        "m_track" => "uploader_id",
        "p_show" => "uploader_id",
        "p_episode" => "uploader_id",
      ) as $_a => $_b ){
        $_oN = is_int( $_a ) ? $_b : $_a;
        $_sN = is_int( $_a ) ? "user_id" : $_b;
        try {
          bof()->object->__get( $_oN )->delete( array(
            $_sN => $_u["ID"]
          ) );
        } catch( bofException|Exception $err ){}
      }
    }
    bof()->general->set_full_fall(true);

    return bof()->object->_delete( $this, $whereArray, $exeRelations );

  }

  public function clean_client_single( $item, $args ){

    $widgets = [];

    $owner = false;
    if ( bof()->user->get()->ID === $item["ID"] ){
      $owner = true;
    }

    $item["subscribed"] = false;
    if ( bof()->user->get()->ID && !$owner ){
      $item["subscribed"] = bof()->object->ugc_property->select(
        array(
          "user_id" => bof()->user->get()->ID,
          "type" => "subscribe",
          "object_name" => "user",
          "object_id" => $item["ID"]
        )
      ) ? true : false;
    }

    $widgets["user_playlists"] = $this->_bof_this->clean_client_single_widget( $item, "user_playlists" );
    $widgets["user_followers"] = $this->_bof_this->clean_client_single_widget( $item, "user_followers" );

    $item["stats"] = [];

    if ( !empty( $item["s_points"] ) ? $item["s_points"] > 10 : false )
    $item["stats"][] = array(
      "icon" => "police-badge-outline",
      "value" => turn( "user_points" ) . ": <b>" . number_format( $item["s_points"] ) . "</b>"
    );

    if ( !empty( $item["s_followers"] ) ? $item["s_followers"] > 10 : false )
    $item["stats"][] = array(
      "icon" => "account-multiple-outline",
      "value" => turn( "followers" ) . ": <b>" . number_format( $item["s_followers"] ) . "</b>"
    );

    if ( !empty( $item["s_playlists"] ) ? $item["s_playlists"] : false )
    $item["stats"][] = array(
      "icon" => "playlist-music",
      "value" => turn( "playlists" ) . ": <b>" . number_format( $item["s_playlists"] ) . "</b>"
    );

    if ( empty( $item["stats"] ) )
    $item["stats"][] = array(
      "icon" => "clock-time-two",
      "value" => turn( "signup_time" ) . ": <b>" . bof()->general->passed_time_hr( time() - strtotime($item["time_add"]), [ "translate" => true ] )["string"] . "</b>"
    );

    return array(
      "widgets" => $widgets,
      "data" => $item,
      "page" => array(
        "classes" => [ $owner ? "owner" : "" ]
      )
    );

  }
  public function clean_client_single_widget( $item, $widget_name, $args=[], $caller="self" ){

    $owner = false;
    if ( bof()->user->get()->ID === $item["ID"] ){
      $owner = true;
    }

    $widgets = array(
      "user_playlists" => array(
        "ID" => "user_playlists",
        "display" => array(
          "type" => "slider",
          "title" => "Playlists",
          "pagination" => "list/bof_user?slug={$item["username"]}&widget=user_playlists",
          "link" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => true,
          "classes" => [ "t_p playAsAction" ]
        ),
        "object" => array(
          "name" => "ugc_playlist",
          "whereArray" => array(
            "col_user" => $item["ID"],
          ),
          "selectArray" => array(
            "order_by" => "time_update",
            "order" => "DESC",
            "limit" => 10,
          )
        ),
      ),
      "user_followers" => array(
        "ID" => "user_followers",
        "display" => array(
          "type" => "slider",
          "title" => "Followers",
          "pagination" => "list/bof_user?slug={$item["username"]}&widget=user_followers",
          "link" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => true,
          "classes" => [ "t_p" ]
        ),
        "object" => array(
          "name" => "user",
          "whereArray" => array(
            "follower_of" => $item["ID"],
          ),
          "selectArray" => array(
            "order_by" => "time_add",
            "order" => "DESC",
            "limit" => 10,
            "_eq" => array(
              "avatar" => [],
              "cover" => []
            )
          )
        ),
      ),
    );

    if ( !$owner ){
      $widgets["playlists"]["object"]["whereArray"]["is_private"] = 0;
    }

    return !empty( $widgets[ $widget_name ] ) ? $widgets[ $widget_name ] : null;

  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["username"] => 1,
    );

    return $o;

  }

  public function authorize( $id_type, $id, $raw_password, $website ){

    $user = $this->_bof_this->select(
      array(
        $id_type => $id
      ),
      array(
        "_eq" => array(
          "roles" => array(
            "website" => $website
          )
        ),
        "no_bof_time" => true
      )
    );

    if ( $user ? $user["time_verify"] : false ){

      $verify_password = $this->_bof_this->verify_password( $raw_password, $user["password"] );
      if ( $verify_password ){


        if ( $website == "admin" && ( !empty( $user["roles_parsed"]["admin"] ) || !empty( $user["roles_parsed"]["moderator"] ) ) ){

          $redirect = "index";
          $_role_type = !empty( $user["roles_parsed"]["admin"] ) ? "admin" : "moderator";
          if ( $_role_type == "moderator" ){
            $moderator_type = $user["roles_parsed"]["moderator"]["type"];
            $moderator_objects = $moderator_type == "all" ? bof()->bofAdmin->_get_objects() : $user["roles_parsed"]["moderator"]["objects"];
            $moderator_objects = array_keys( $moderator_objects );
            $moderator_object_name = reset( $moderator_objects );
            $moderator_object = bof()->object->__get( $moderator_object_name );
            $redirect = $moderator_object->bof_admin()["config"]["list_page_url"];
          }

          return array(
            "user" => array(
              "name" => $user["name"],
              "email" => $user["email"],
              "username" => $user["username"],
              "ID" => $user["ID"]
            ),
            "role" => $_role_type,
            "moderator_roles" => $_role_type == "moderator" ? $user["roles_parsed"]["moderator"] : [],
            "user_roles" => $_role_type == "user" ? $user["roles_parsed"]["user"] : [],
            "redirect" => $redirect
          );

        }
        else if ( !empty( $user["roles_parsed"]["user"] ) ) {
          return $this->_bof_this->parse_authorized_user( $user );
        }
      }

    }

    return false;

  }
  public function parse_authorized_user( $user ){

    return array(
      "user" => array(
        "name" => $user["name"],
        "email" => $user["email"],
        "username" => $user["username"],
        "ID" => $user["ID"]
      ),
      "role" => "user",
      // "user_roles" => $user["roles_parsed"]["user"],
      "redirect" => ""
    );

  }

  public function add_fund( $id, $amount, $args=[] ){

    $type = "deposit";
    $object_type = null;
    $object_id = null;
    $text = null;
    $revenue = 0;
    extract( $args );

    // get user
    $user = $this->_bof_this->select(array(
      "ID" => $id
    ),array(
      "cache_load_rt" => false,
    ));

    // add transaction
    $t_id = bof()->object->transaction->insert(array(
      "user_id" => $id,
      "type" => $type,
      "amount" => $amount,
      "object_type" => $object_type,
      "object_id" => $object_id,
      "revenue" => $revenue,
      "data" => json_encode( array(
        "text" => $text
      ) )
    ));

    // give funds
    $this->_bof_this->update(array(
      "ID" => $id,
    ),array(
      "fund" => $user["fund"] + $amount
    ));

    if ( $type == "deposit" )
    bof()->db->query("UPDATE _u_list SET fund_by_deposit = fund_by_deposit + {$amount} WHERE ID = '{$id}' ");

    elseif ( $type == "sell" )
    bof()->db->query("UPDATE _u_list SET fund_by_revenue = fund_by_revenue + {$amount} WHERE ID = '{$id}' ");

    elseif ( $type == "commission" )
    bof()->db->query("UPDATE _u_list SET fund_by_referring = fund_by_referring + {$amount} WHERE ID = '{$id}' ");

    return $t_id;

  }
  public function remove_fund( $id, $amount, $args=[] ){

    $type = "disperse";
    $object_type = null;
    $object_id = null;
    $text = null;
    $revenue = 0;
    extract( $args );

    // get user
    $user = $this->_bof_this->select(array(
      "ID" => $id
    ),array(
      "cache_load_rt" => false,
    ));

    bof()->nest->validate( $amount, "float" );

    // add transaction
    $t_id = bof()->object->transaction->insert(array(
      "user_id" => $id,
      "type" => $type,
      "amount" => $amount * -1,
      "object_type" => $object_type,
      "object_id" => $object_id,
      "revenue" => $revenue,
      "data" => json_encode( array(
        "text" => $text
      ) )
    ));

    // remove funds
    $this->_bof_this->update(array(
      "ID" => $id,
    ),array(
      "fund" => $user["fund"] - $amount
    ));

    if ( $type == "disperse" )
    bof()->db->query("UPDATE _u_list SET fund_by_deposit = fund_by_deposit - {$amount} WHERE ID = '{$id}' ");

    return $t_id;

  }

  public function make_username( $seed="" ){

    $seed = $seed ? bof()->general->make_code( strtolower( $seed ), "a-zA-Z0-9", 50 ) : uniqid();
    $i=1;

    $unique = false;
    while( !$unique ){

      $exists = $this->_bof_this->select(
        array(
          "username" => $seed . ( $i==1?"":$i )
        ),
        array(
          "clean" => false,
          "cache" => false
        )
      );

      $unique = !$exists;
      if ( $unique ) break;

      $i++;

    }

    return $seed . ( $i==1?"":$i );

  }

  public function get( $ID ){
    // Must return `groups`
    return $this->select( [ "ID" => $ID ], [] );
  }
  public function hash_password( $string ){
    return password_hash( $string, PASSWORD_DEFAULT );
  }
  public function verify_password( $string, $hash ){
    return password_verify( $string, $hash );
  }

  public function notify_creator_update( $ot, $name, $id, $url ){

    $get_subscribed_users = bof()->object->user->select(
      array(
        [ "ID", "IN", "SELECT user_id FROM `_u_properties` WHERE type = 'subscribe' AND object_name = '{$ot}' AND object_id = '{$id}' ", true ]
      ),
      array(
        "single" => false,
        "limit" => false
      )
    );

    if ( $get_subscribed_users ){
      foreach( $get_subscribed_users as $subscribed_user ){
        bof()->chapar->notify( "creator_update", array(
          "source" => array(
            "object" => $ot,
            "id" => $id
          ),
          "target" => array(
            "user_data" => $subscribed_user
          ),
          "message" => array(
            "params" => [ "creator_name" => $name ],
            "link" => $url
          ),
        ) );
      }
    }

  }

}

?>
