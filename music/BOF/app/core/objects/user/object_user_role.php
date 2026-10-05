<?php

if ( !defined( "bof_root" ) ) die;

class object_user_role extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "user_role",
      "label" => "User role",
      "icon" => "star",
      "db_table_name" => "_u_roles"
    );
  }
  public function columns(){
    return array(
      "name" => array(
        "label" => "Name",
        "validator" => "string",
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["sub_data"] = $item["comment"];
              return $displayData;
            },
          ),
          "object" => array(
            "required" => true
          )
        )
      ),
      "comment" => array(
        "label" => "Comment",
        "tip" => "A few words about this user role. Visible to admins only",
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
      "type" => array(
        "label" => "Type",
        "validator" => array(
          "in_array",
          [ "values" => [ "guest", "user", "moderator", "admin" ] ]
        ),
        "input" => array(
          "type" => "select_i",
          "options" => array(
            [ "moderator", "Moderator" ],
            [ "user", "User" ],
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "filters" => array(
            "type" => array(
              "title" => "Type",
              "input" => array(
                "name" => "type",
                "type" => "select_i",
                "options" => array(
                  [ "__all__", "All" ],
                  [ "guest", "Guest" ],
                  [ "user", "User" ],
                  [ "moderator", "Moderator" ],
                  [ "admin", "Admin" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                [ "values" => [ "guest", "user", "moderator", "admin", "__all__" ] ]
              )
            ),
          ),
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = ucfirst( $displayData["data"] );
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true
          )
        ),
        "selectors" => array(
          "type" => function( $val ){

            if ( $val == "user_guest" ){
              return array(
                "oper" => "OR",
                "cond" => array(
                  [ "type", "=", "guest" ],
                  [ "type", "=", "user" ]
                )
              );
            }

            return [ "type", "=", $val ];

          },
          "def" => [ "def", "=" ]
        )
      ),
      "bofAdmin_access" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        ),
      ),
      "access" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          ),
        )
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          ),
        ),
      ),
      "comparators" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true
          ),
        )
      ),
      "s_users" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
      ),
    );
  }
  public function selectors(){
    return array(
      "query" => [ "name", "LIKE%" ]
    );
  }
  public function relations(){
    return array(
      "users" => array(
        "exec" => array(
          "type" => "hub",
          "hub_type" => "role",
          "parent_object" => "user",
          "parent_object_list_column" => "roles",
          "child_object" => "user_role",
          "child_object_stats_column" => "s_users"
        ),
      ),
      "user_subs_plans" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user_role",
          "child_object" => "user_subs_plan",
          "child_object_selector_column" => "target_role_id",
          "delete_child_too" => true,
        )
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add" => array(
        "no_object" => true
      )
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
        "edit_page_url" => "user_role",
        "list_page_url" => "user_roles",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "buttons" => array(),
      "buttons_renderer" => function( $item, &$buttons ){

        if ( $item["def"] )
        unset( $buttons["delete"] );

        if ( $item["ID"] == 4 )
        unset( $buttons["edit"] );

      },
      "filters" => array(),
      "list" => array(
        "name" => [],
        "type" => [],
        "time_add" => array(
          "label" => "Register<br>Time",
          "type" => "time"
        )
      ),
      "list_config" => array(
        "order_by" => "ID",
        "order" => "ASC",
        "limit" => 100
      ),
      "object" => array(

        "name" => null,
        "comment" => null,
        "type" => null,

        "moderator_access_type" => array(
          "label" => "Moderator access type",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "moderator" ]
          ),
          "input" => array(
            "name" => "moderator_access_type",
            "type" => "select_i",
            "options" => array(
              [ "all", "All" ],
              [ "some", "Only checked" ],
            )
          ),
          "validator" => array(
            "in_array",
            array(
              "empty()",
              "values" => [ "all", "some" ]
            )
          )
        ),

        "guest_ads" => array(
          "label" => "Display Advertisement",
          "tip" => "Should app display advertisement for guests?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ]
          ),
          "input" => array(
            "name" => "guest_ads",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),

        "guest_download" => array(
          "label" => "Download content",
          "tip" => "Can guests download free content from your app?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ]
          ),
          "input" => array(
            "name" => "guest_download",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "guest_download_in" => array(
          "label" => "Download content - In-app",
          "tip" => "Content will be cached by app and can be played only in-app",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ],
            "guest_download" => [ "equal", true ]
          ),
          "input" => array(
            "name" => "guest_download_in",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "guest_download_out" => array(
          "label" => "Download content - To device",
          "tip" => "Content can be downloaded and played on guest's device",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ],
            "guest_download" => [ "equal", true ]
          ),
          "input" => array(
            "name" => "guest_download_out",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "guest_language" => array(
          "label" => "Change language",
          "tip" => "Can guests change app language to non-indexed languages?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ]
          ),
          "input" => array(
            "name" => "guest_language",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "guest_mob_yt_sl" => array(
          "label" => "Mobile Apps - YouTube Background play",
          "tip" => "<b>Effects Mobile Apps only</b>. Can guests continue listening to YouTube videos with their screen off?<br><br>This feature is intended for <b>personal use only</b>. By enabling background playback, you accept full responsibility for compliance with YouTube's terms of service and any applicable laws",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ]
          ),
          "input" => array(
            "name" => "guest_mob_yt_sl",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "guest_signup" => array(
          "label" => "Signup",
          "tip" => "Can guests sign up?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ]
          ),
          "input" => array(
            "name" => "guest_signup",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "guest_signup_verify" => array(
          "label" => "Signup - Verification required",
          "tip" => "If checked, registration won't be completed until submitted email is verified",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "guest" ]
          ),
          "input" => array(
            "name" => "guest_signup_verify",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),

        "user_ads" => array(
          "label" => "Display Advertisement",
          "tip" => "Should app display advertisement for users belonging to this user role?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_ads",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_language" => array(
          "label" => "Change language",
          "tip" => "Can users change app language to non-indexed languages?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_language",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_download" => array(
          "label" => "Download content",
          "tip" => "Can users which belong to this user-role download free content ( or premium content that they have bought or have access to thro one of their user-roles ) from your app?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_download",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_download_in" => array(
          "label" => "Download content - In-app",
          "tip" => "Content will be cached by app and can be played only in-app",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ],
            "user_download" => [ "equal", true ]
          ),
          "input" => array(
            "name" => "user_download_in",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_download_out" => array(
          "label" => "Download content - To device",
          "tip" => "Content can be downloaded and played on user's device",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ],
            "user_download" => [ "equal", true ]
          ),
          "input" => array(
            "name" => "user_download_out",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_withdraw" => array(
          "label" => "Withdrawal request",
          "tip" => "Can users which belong to this user-role request to withdraw their fund?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_withdraw",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_upload" => array(
          "label" => "Upload content",
          "tip" => "Can users which belong to this user-role upload content to your app?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_upload",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_verify" => array(
          "label" => "Verification access",
          "tip" => "Can users which belong to this user-role submit their data to become verified managers, affiliates and etc?",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_verify",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_mob_yt_sl" => array(
          "label" => "Mobile Apps - YouTube Background play",
          "tip" => "<b>Effects Mobile Apps only</b>. Can users belonging to this user-role continue listening to YouTube videos with their screen off?<br><br>This feature is intended for <b>personal use only</b>. By enabling background playback, you accept full responsibility for compliance with YouTube's terms of service and any applicable laws",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_mob_yt_sl",
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
            )
          )
        ),
        "user_premium" => array(
          "label" => "Premium content access",
          "tip" => "Choose what type of premium content this user-role should have access to ( for playing, downloading or etc )",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ]
          ),
          "input" => array(
            "name" => "user_premium",
            "type" => "select_i",
            "options" => array(
              [ "none", "None" ],
              [ "some", "Some" ],
              [ "all", "All" ]
            )
          ),
          "validator" => array(
            "in_array",
            array(
              "empty()",
              "values" => [ "none", "some", "all" ]
            )
          )
        ),
        "user_premium_b_post" => array(
          "label" => "Premium Blog - Access by post",
          "tip" => "Users belonging this user-role will have access to selected premium blog posts",
          "multi" => false,
          "display_on" => array(
            "type" => [ "equal", "user" ],
            "user_premium" => [ "equal", "some" ]
          ),
          "input" => array(
            "name" => "user_premium_b_post",
          ),
          "bofInput" => array(
            "object",
            array(
              "autoload" => false,
              "type" => "b_post",
              "multi" => true,
            )
          ),
        ),

      ),
      "object_ui_renderer" => function( $object, $parsed, $args, $request, &$_inputs, &$data ){

        $_data = $request["type"] == "single" ? reset( $request["content"] ) : false;

        $objects = bof()->bofAdmin->_get_objects();
        $objects_inputs = [];

        if ( !empty( $_data["bofAdmin_access_decoded"]["type"] ) )
        $_inputs["moderator_access_type"]["input"]["value"] = $_data["bofAdmin_access_decoded"]["type"];

        foreach( $objects as $object_name => $object_args ){

          $object = bof()->object->__get( $object_name );

          foreach( array(
            "new" => [ "Create", "Gives assigned users to this group the ability to create new content. Requires `Read` access to be enabled" ],
            "list" => [ "Read", "Gives assigned users to this group the ability to read ( or list ) content" ],
            "edit" => [ "Update", "Gives assigned users to this group the ability to update existing content. Requires `Read` access to be enabled" ],
            "delete" => [ "Delete", "Gives assigned users to this group the ability to delete existing content. Requires `Read` access to be enabled" ],
          ) as $_k => $_n ){

            if ( !empty(  $object_args[ $_k ] ) )
            $objects_inputs[ $object_name ][ $_k ] = array(
              "label" => $_n[0],
              "tip" => $_n[1],
              "group" => "a",
              "input" => array(
                "name" => $_k,
                "type" => "checkbox",
                "value" => isset( $_data[ "bofAdmin_access_decoded" ][ "objects_args" ][ $object_name ][ $_k ] ) ? $_data[ "bofAdmin_access_decoded" ][ "objects_args" ][ $object_name ][ $_k ] : false
              )
            );

          }

          $object_args["filters"] = bof()->bofAdmin->object_list_parse_caller( $object )["filters"];

          foreach( $object_args["filters"] as $_filter_name => &$_filter ){

            $object_args["filters"][ $_filter_name ]["input"]["name"] = $_filter_name;
            $object_args["filters"][ $_filter_name ]["group"] = "b";
            if ( isset( $_data[ "bofAdmin_access_decoded" ][ "objects_args" ][ $object_name ][ $_filter_name ] ) ){
              $object_args["filters"][ $_filter_name ]["input"]["value"] = $_data[ "bofAdmin_access_decoded" ][ "objects_args" ][ $object_name ][ $_filter_name ];
            }
            if ( !empty( $_filter["bofInput"] ) ){
              $_filter = bof()->bofInput->parse( $_filter )["data"];
            }

          }

          $objects_inputs[ $object_name ] = array_merge( $objects_inputs[ $object_name ], $object_args["filters"] );

          $object_label = $object->bof()["label"];
          $object_icon = $object->bof()["icon"];

          $_inputs[ "_u_r_m_a_{$object_name}" ] = array(
            "label" => ucfirst( $object_label ) . "<span class='_setting' data-object='{$object_name}'><span class='material-icons-outlined'>{$object_icon}</span></span>",
            "tip" => "Users assigned with this role will have access to <b>{$object_label}</b> data<br>" .
              "<span class='_crud_stats'>".

                ( !empty( $object_args["new"] ) ? "Create: " . ( !empty( $_data["bofAdmin_access_decoded"]["objects_args"][$object_name]["new"] ) ? "<span class=\"material-icons-outlined ok\">verified</span>" : "<span class=\"material-icons-outlined\">not_interested</span>" ) : "" ) .
                ( !empty( $object_args["list"] ) ? "Read: " . ( !empty( $_data["bofAdmin_access_decoded"]["objects_args"][$object_name]["list"] ) ? "<span class=\"material-icons-outlined ok\">verified</span>" : "<span class=\"material-icons-outlined\">not_interested</span>" ) : "" ) .
                ( !empty( $object_args["edit"] ) ? "Update: " . ( !empty( $_data["bofAdmin_access_decoded"]["objects_args"][$object_name]["edit"] ) ? "<span class=\"material-icons-outlined ok\">verified</span>" : "<span class=\"material-icons-outlined\">not_interested</span>" ) : "" ) .
                ( !empty( $object_args["delete"] ) ? "Delete: " . ( !empty( $_data["bofAdmin_access_decoded"]["objects_args"][$object_name]["delete"] ) ? "<span class=\"material-icons-outlined ok\">verified</span>" : "<span class=\"material-icons-outlined\">not_interested</span>" ) : "" ) .

                "Limit: " . ( !empty( $_data["bofAdmin_access_decoded"]["objects_args"][$object_name] ) ?
                  (
                    array_diff( array_keys( $_data["bofAdmin_access_decoded"]["objects_args"][$object_name] ), [ "new", "delete", "edit", "list" ] ) ? "<span class=\"_text ok\">Some</span>" : "<span class=\"_text\">None</span>"
                  ) :
                "<span class=\"_text\">None</span>" ) .
              "</span>" .
            "<span class='_setting' data-object='{$object_name}'><span class='material-icons-outlined'>settings</span>Click here for setting</span>",
            "input" => array(
              "type" => "checkbox",
              "name" => "_u_r_m_a_{$object_name}",
              "value" => !empty( $_data["bofAdmin_access_decoded"]["objects"] ) ? in_array( $object_name, $_data["bofAdmin_access_decoded"]["objects"] ) : false
            ),
            "display_on_cond" => "and",
            "display_on" => array(
              "type" => [ "equal", "moderator" ],
              "moderator_access_type" => [ "equal", "some" ]
            ),
            "group" => "detail"
          );

        }

        $data["_u_groups"] = array_merge( $_inputs, $objects_inputs );
        $data["data"] = $_data;

        if ( !empty( $_data["data_decoded"]["guest"] ) ){
          foreach( $_data["data_decoded"]["guest"] as $_k => $_v ){
            if ( !empty( $_inputs[ $_k ] ) )
            $_inputs[ $_k ]["input"]["value"] = $_v;
          }
        }
        if ( !empty( $_data["data_decoded"]["user"] ) ){
          foreach( $_data["data_decoded"]["user"] as $_k => $_v ){
            if ( !empty( $_inputs[ $_k ] ) )
            $_inputs[ $_k ]["input"]["value"] = $_v;
          }
        }
        if ( !empty( $_data["def"] ) ){
          unset( $_inputs["name"] );
          $_inputs["type"]["input"]["type"] = "hidden";
        }

        $_o_inputs = $_inputs;
        $_inputs = [];

        foreach( $_o_inputs as $_input_name => $_input ){

          if ( substr( $_input_name, -4 ) == "_ads" ){

            $_ss = [];
            $supported_sources = bof()->source->get_supported( "stream", "setting" );

            $_g_type = str_replace( "_ads", "", $_input_name );
            $_inputs[ $_g_type . "_player" ] = array(
              "label" => "Player supported types",
              "multi" => false,
              "group" => "detail",
              "display_on" => array(
                "type" => [ "equal", $_g_type ]
              ),
              "input" => array(
                "name" => $_g_type . "_player",
                "type" => "select_m",
                "options" => $supported_sources["options"],
                "value" => !empty( $_data["data_decoded"][ $_g_type ]["{$_g_type}_player"] ) ? explode( ";", $_data["data_decoded"][ $_g_type ]["{$_g_type}_player"] ) : null
              ),
              "validator" => array(
                "in_array",
                array(
                  "empty()",
                  "values" => $supported_sources["keys"]
                )
              )
            );

          }

          if ( !empty( $_input["bofInput"] ) ){
            $_input = bof()->bofInput->parse( $_input )["data"];
          }

          if ( !empty( $_input["input"]["type"] ) ? $_input["input"]["type"] == "select_m" && !empty( $_input["input"]["value"] ) : false ){
            if ( !is_array( $_input["input"]["value"] ) ){
              $__t = explode( ",", $_input["input"]["value"] );
              if ( count( $__t ) > 1 )
              $_input["input"]["value"] = $__t;
              else
              $_input["input"]["value"] = explode( ";", $_input["input"]["value"] );
            }
          }

          $_inputs[ $_input_name ] = $_input;

          if ( substr( $_input_name, -1 * strlen("_download_out") ) == "_download_out" ){

            $_ss = [];
            $supported_sources = bof()->source->get_supported( "download", "setting" );

            $_g_type = str_replace( "_download_out", "", $_input_name );
            $_inputs[ $_g_type . "_download_types" ] = array(
              "label" => "Download supported types",
              "multi" => false,
              "group" => "detail",
              "display_on" => array(
                "type" => [ "equal", $_g_type ],
                "{$_g_type}_download" => [ "equal", true ]
              ),
              "input" => array(
                "name" => $_g_type . "_download_types",
                "type" => "select_m",
                "options" => $supported_sources["options"],
                "value" => !empty( $_data["data_decoded"][ $_g_type ]["{$_g_type}_download_types"] ) ? explode( ";", $_data["data_decoded"][ $_g_type ]["{$_g_type}_download_types"] ) : null
              ),
              "validator" => array(
                "in_array",
                array(
                  "empty()",
                  "values" => $supported_sources["keys"]
                )
              )
            );

          }

        }

        foreach ( [ "user" ] as $_g_type ){

          $_inputs[ $_g_type . "_p_download_types" ] = array(
            "label" => "Download premium access",
            "multi" => false,
            "group" => "detail",
            "display_on" => array(
              "type" => [ "equal", $_g_type ],
              "user_premium" => [ "equal", "some" ],
              "{$_g_type}_download" => [ "equal", true ],
            ),
            "input" => array(
              "name" => $_g_type . "_p_download_types",
              "type" => "select_m",
              "options" => $supported_sources["options"],
              "value" => !empty( $_data["data_decoded"][ $_g_type ]["{$_g_type}_p_download_types"] ) ? explode( ";", $_data["data_decoded"][ $_g_type ]["{$_g_type}_p_download_types"] ) : null
            ),
            "validator" => array(
              "in_array",
              array(
                "empty()",
                "values" => $supported_sources["keys"]
              )
            )
          );

          $_inputs[ $_g_type . "_p_player" ] = array(
            "label" => "Player premium access",
            "multi" => false,
            "group" => "detail",
            "display_on" => array(
              "type" => [ "equal", $_g_type ],
              "user_premium" => [ "equal", "some" ]
            ),
            "input" => array(
              "name" => $_g_type . "_p_player",
              "type" => "select_m",
              "options" => $supported_sources["options"],
              "value" => !empty( $_data["data_decoded"][ $_g_type ]["{$_g_type}_p_player"] ) ? explode( ";", $_data["data_decoded"][ $_g_type ]["{$_g_type}_p_player"] ) : null
            ),
            "validator" => array(
              "in_array",
              array(
                "empty()",
                "values" => $supported_sources["keys"]
              )
            )
          );

        }

      },
      "object_be_renderer" => function( &$_inputs, $request ){

        $supported_sources = bof()->source->get_supported( "stream", "setting" );
        $supported_sources_download = bof()->source->get_supported( "download", "setting" );
        $_data = $request["type"] == "single" ? reset( $request["content"] ) : false;
        $objects = bof()->bofAdmin->_get_objects();
        $bofAdmin_access = [];

        if ( !empty( $_data["def"] ) ){
          unset( $_inputs["report"]["fail"]["name"] );
        }

        if ( $_inputs["data"]["type"] == "moderator" ){

          $_objects = bof()->nest->user_input( "post", "bofAdmin_access_objects", "json" );
          $_objects_args = bof()->nest->user_input( "post", "bofAdmin_access_objects_args", "json" );

          // Validate enabled objects
          if ( $_objects ){
            if ( !array_diff( $_objects, array_keys( $objects ) ) ){
              $bofAdmin_access["objects"] = $_objects;
            }
          }

          // Validate objects' argumens
          if ( $_objects_args ){
            foreach( $_objects_args as $_object_name => $_object_args ){

              $objects[ $_object_name ]["filters"] = bof()->bofAdmin->object_list_parse_caller( bof()->object->__get( $_object_name ) )["filters"];

              if ( !in_array( $_object_name, array_keys( $objects ), 1 ) ) continue;
              if ( !is_array( $_object_args ) ) continue;

              foreach( $_object_args as $_object_arg_name => $_object_arg ){

                if ( in_array( $_object_arg_name, [ "edit", "new", "list", "delete" ], true ) ){
                  $filter = array(
                    "input" => array(
                      "name" => $_object_arg_name,
                      "type" => "checkbox"
                    ),
                    "validator" => array(
                      "boolean",
                      array(
                        "empty()"
                      )
                    )
                  );
                }
                elseif ( empty( $objects[ $_object_name ][ "filters" ][ $_object_arg_name ] ) ) {
                  continue;
                } else {
                  $filter = $objects[ $_object_name ][ "filters" ][ $_object_arg_name ];
                }

                if ( !empty( $filter["bofInput"] ) ){
                  $filter["bofInput"][1]["user_value"] = $_object_arg;
                  $input_value = bof()->bofInput->validate( $filter );
                  if ( $input_value ){
                    $bofAdmin_access["objects_args"][ $_object_name ][ $_object_arg_name ] = $input_value;
                  }
                }

                else {
                  $input_validator = null;
                  if ( !empty( $filter["validator"] ) )
                  list( $input_validator, $input_validator_args ) = $filter["validator"];
                  if ( !empty( $input_validator ) ){
                    if ( bof()->nest->validate( $_object_arg, $input_validator, $input_validator_args ) ){
                      $bofAdmin_access["objects_args"][ $_object_name ][ $_object_arg_name ] = $_object_arg;
                    }
                  }
                }

              }
            }
          }

          if ( empty( $_inputs["data"]["moderator_access_type"] ) ){
            $_inputs["report"]["fail"]["moderator_access_type"] = "Select one";
          }
          else {
            $bofAdmin_access["type"] = $_inputs["data"]["moderator_access_type"];
          }

        }
        elseif ( $_inputs["data"]["type"] == "guest" ){

          $_guest_data = [];
          foreach( $_inputs["data"] as $_i_n => $_i_d ){
            if ( substr( $_i_n, 0, strlen( "guest_" ) ) == "guest_" )
            $_guest_data[ $_i_n ] = $_i_d;
          }

          $_data = !empty( $_inputs["set"]["data"] ) || !empty( $_inputs["update"]["data"] ) ? json_decode( !empty( $_inputs["set"]["data"] ) ? $_inputs["set"]["data"] : $_inputs["update"]["data"], 1 ) : [];
          $_data["guest"] = $_guest_data;
          $_data["guest"]["guest_player"] = bof()->nest->user_input( "post", "guest_player", "string", array( "select_m()", "values" => $supported_sources["keys"] ) );
          $_data["guest"]["guest_download_types"] = bof()->nest->user_input( "post", "guest_download_types", "string", array( "select_m()", "values" => $supported_sources_download["keys"] ) );
          $_data["guest"]["guest_download_in"] = !empty( $_data["guest"]["guest_download_in"] );
          $_data["guest"]["guest_download_out"] = !empty( $_data["guest"]["guest_download_out"] );
          $_inputs["set"]["data"] = $_inputs["update"]["data"] = json_encode( $_data );

        }
        elseif ( $_inputs["data"]["type"] == "user" ){

          $_user_data = [];
          foreach( $_inputs["data"] as $_i_n => $_i_d ){
            if ( substr( $_i_n, 0, strlen( "user_" ) ) == "user_" )
            $_user_data[ $_i_n ] = $_i_d;
          }

          $_data = !empty( $_inputs["set"]["data"] ) || !empty( $_inputs["update"]["data"] ) ? json_decode( !empty( $_inputs["set"]["data"] ) ? $_inputs["set"]["data"] : $_inputs["update"]["data"], 1 ) : [];
          $_data["user"] = $_user_data;
          $_data["user"]["user_player"] = bof()->nest->user_input( "post", "user_player", "string", array( "select_m()", "values" => $supported_sources["keys"] ) );
          $_data["user"]["user_download_types"] = bof()->nest->user_input( "post", "user_download_types", "string", array( "select_m()", "values" => $supported_sources["keys"] ) );
          $_data["user"]["user_p_download_types"] = bof()->nest->user_input( "post", "user_p_download_types", "string", array( "select_m()", "values" => $supported_sources["keys"] ) );
          $_data["user"]["user_p_player"] = bof()->nest->user_input( "post", "user_p_player", "string", array( "select_m()", "values" => $supported_sources["keys"] ) );
          $_inputs["set"]["data"] = $_inputs["update"]["data"] = json_encode( $_data );

        }

        $_inputs["set"]["bofAdmin_access"] = $_inputs["update"]["bofAdmin_access"] = json_encode( $bofAdmin_access );

      },
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = null;
    $listing = null;
    $deleting = null;
    $editing = null;
    $_eq = [];
    extract( $selectArgs );

    if ( $deleting )
    $whereArgs[] = [ "def", "!=", "1" ];

    $selectArgs["order_by"] = !empty( $selectArgs["order_by"] ) ? " def DESC, " . $selectArgs["order_by"] : "";

    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args ){

    $editing = null;
    $search = null;
    extract( $args );

    if ( $editing ){
      $objects = bof()->bofAdmin->_get_objects( [ "get_filters" => true ] );
      $item["objects"] = $objects;
    }

    $item["bofAdmin_access_decoded"] = null;
    $item["data_decoded"] = null;

    if ( !empty( $item["bofAdmin_access"] ) )
    $item["bofAdmin_access_decoded"] = json_decode( $item["bofAdmin_access"], 1 );

    if ( !empty( $item["data"] ) ){
      $item["data_decoded"] = json_decode( $item["data"], 1 );
    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"] . " <i>( {$item["type"]} )</i>",
        "image" => false
      );
    }

    return $item;

  }

  public function parse_users( $user, $website="auto" ){

    $default_roles = $all_roles = explode( ",", $user["role_ids"] );

    $subscriptions = bof()->object->user_subs->select(
      array(
        "user_id"  => $user["ID"],
        "has_time" => 1
      ),
      array(
        "limit"  => false,
        "single" => false,
        "clean" => false
      )
    );

    if ( !empty( $subscriptions ) ){
      foreach( $subscriptions as $subscription ){
        $subscription_plan = bof()->object->user_subs_plan->select(
          array(
            "ID" => $subscription["subs_plan_id"]
          ),
          array(
            "clean" => false
          )
        );
        $subscription_plan_role = $subscription_plan["target_role_id"];
        if ( !in_array( $subscription_plan_role, $all_roles ) ){
          $all_roles[] = $subscription_plan_role;
          $roles_expiration_time[ $subscription_plan_role ] = $subscription["time_expire"];
        }
      }
    }

    $roles_parsed = [];
    foreach( $all_roles as $role_id ){
      $role_data = $this->_bof_this->select(
        array(
          "ID" => $role_id
        ),
        array(
          "no_bof_time" => true
        )
      );
      if ( empty( $role_data ) ) continue;
      if ( !empty( $roles_expiration_time[ $role_id ] ) ){
        $role_data["time_expire"] = $roles_expiration_time[ $role_id ];
        $role_data["by_subscription"] = true;
      }
      $roles_parsed[ $role_data["type"] ][ $role_id ] = $role_data;
      $roles_parsed[ "_raw" ][ $role_id ] = $role_data;
    }

    if ( $website == "client" || ( $website == "auto" && bof()->getName() == "bof_client" ) ){
      $_exe_user = $this->_bof_this->parse_user_roles( !empty( $roles_parsed["user"] ) ? $roles_parsed["user"] : null, $user["ID"], $user );
      if ( $_exe_user ) $roles_parsed["user"] = $_exe_user;
    }
    elseif ( $website == "admin" || ( $website == "auto" && bof()->getName() == "bof_admin" ) ){
      $_exe_mod = $this->_bof_this->parse_moderator_roles( !empty( $roles_parsed["moderator"] ) ? $roles_parsed["moderator"] : null, $user["ID"], $user );
      if ( $_exe_mod ) $roles_parsed["moderator"] = $_exe_mod;
    }

    return $roles_parsed;

  }
  public function parse_managers( $manager, $manager_type, $manager_args=[] ){

    $manager_map = null;
    extract( $manager_args, EXTR_PREFIX_ALL, "manager" );

    $roles = $this->_bof_this->select(
      array(
        "ID_in" => $manager["role_ids"],
        "type" => $manager_type
      ),
      array(
        "no_bof_time" => true,
        "single" => false,
        "limit" => false
      )
    );

    if ( empty( $roles ) )
    return false;

    foreach( $roles as $role ){

      if ( empty( $role["data_decoded"][ $manager_type ] ) )
      continue;

      $roles_datas[] = $role["data_decoded"][ $manager_type ];

    }

    if ( empty( $roles_datas ) )
    return false;

    $map = $this->_bof_this->parse_managers_roles_get_map( $manager_type, $manager_args );
    $roles_parsed = [];

    foreach( $map as $trait => $trait_map ){
      foreach( $trait_map as $_k ){
        $_vals = [];
        foreach ( $roles_datas as $role_data ){
          if ( isset( $role_data[ $_k ] ) ){
            $_vals[] = $role_data[ $_k ];
          }
        }
        if ( count( $_vals ) > 0 ){
          $_val = $trait ? max( $_vals ) : min( $_vals );
          $roles_parsed[ $_k ] = $_val;
        }
      }
    }

    return $roles_parsed;

  }
  public function parse_managers_roles_get_map( $manager_type, $manager_args=[] ){

    if ( in_array( $manager_type, [ "artist", "podcaster", "narrator", "producer" ], true ) )
    return array(
      1 => [ "streaming_royalty" ],
      0 => [ "fixed_fee", "dyna_fee" ]
    );

    if ( $manager_type == "affiliate" )
    return array(
      1 => [ "hit_rew", "signup_rew", "sale_rew", "sale_share" ],
      0 => []
    );

  }

  public function parse_moderator_roles( $roles, $user_id, $user_data=null ){

    if ( empty( $roles ) )
    return false;

    $type = "some";
    $objects = [];
    $objects_args = [];
    $_crud_args = [ "new", "edit", "list", "delete" ];
    foreach( $roles as $role ){

      $bofData = $role["bofAdmin_access_decoded"];
      $objects = array_unique( array_merge( $objects, $bofData["objects"] ? $bofData["objects"] : [] ) );

      if ( $bofData["type"] == "all" ){
        $type = "all";
        break;
      }

      if ( $bofData["objects"] && $bofData["objects_args"] ){
        foreach( $bofData["objects_args"] as $_obName => $_obArgs ){
          if ( !in_array( $_obName, $bofData["objects"], true ) ) continue;
          foreach( $_crud_args as $_crud ){
            if ( !empty( $_obArgs[ $_crud ] ) )
            $objects_args[ $_obName ][ "crud" ][ $_crud ] = true;
          }
          foreach( $_obArgs as $_obArg_k => $_obArg ){
            if ( in_array( $_obArg_k, $_crud_args, true ) ) continue;
            $objects_args[ $_obName ][ "filters" ][ $role["ID"] ][ $_obArg_k ] = $_obArg;
          }
          if ( empty( $objects_args[ $_obName ][ "filters" ][ $role["ID"] ] ) )
          $objects_args[ $_obName ][ "filters" ][ $role["ID"] ] = "all";
        }
      }

      if ( !empty( $objects_args ) ){
        foreach( $objects_args as $_obName => $_obArgs ){
          $objects_args[$_obName]["type"] = in_array( "all", $_obArgs["filters"] ) ? "all" : "some";
        }
      }

    }

    return array(
      "type" => $type,
      "objects" => $objects_args
    );

  }
  public function parse_user_roles_get_map( $roles, $user_id, $user_data ){

    return array(
      1 => [ "download", "download_in", "download_out", "upload", "language", "withdraw", "verify", "mob_yt_sl" ],
      0 => [ "ads" ],
      "combine" => []
    );

  }
  public function parse_user_roles( $roles, $user_id=null, $user_data=null ){

    if ( empty( $roles ) )
    return false;

    $map = $this->_bof_this->parse_user_roles_get_map( $roles, $user_id, $user_data );
    $access = [];

    $access["premium"] = "none";
    $access["premium_rules"] = array(
      "player" => []
    );

    foreach( $roles as $role ){

      if ( empty( $role["data_decoded"] ) ? true : empty( $role["data_decoded"][ $user_id ? "user" : "guest" ] ) )
      continue;

      $roles_user = $role["data_decoded"][ $user_id ? "user" : "guest" ];

      foreach( $map as $trait => $trait_map ){
        foreach( $trait_map as $_k ){

          if ( $trait == "combine" ){
            if ( !empty( $roles_user[ ( $user_id ? "user" : "guest" ) . "_{$_k}" ] ) ){
              $__vs = explode( ";", $roles_user[ ( $user_id ? "user" : "guest" ) . "_{$_k}" ] );
              if ( empty( $access[ $_k ] ) ) $access[ $_k ] = $__vs;
              else $access[ $_k ] = array_unique( array_merge( $access[ $_k ], $__vs ) );
            }
            continue;
          }

          $_v = in_array( ( $user_id ? "user" : "guest" ) . "_{$_k}", array_keys( $roles_user ), true ) ? $roles_user[( $user_id ? "user" : "guest" ) . "_{$_k}"] : null;
          if ( $_v !== null ){

            $_old_v = in_array( $_k, array_keys( $access ), true ) ? $access[ $_k ] : null;

            if ( $_old_v === null ){
              $access[ $_k ] = $_v;
            }
            else {

              if ( $trait ? $_old_v || $_v : false )
              $access[ $_k ] = true;

              elseif ( $trait )
              $access[ $_k ] = false;

              elseif( !$trait ? !$_old_v || !$_v : false )
              $access[ $_k ] = false;

              else
              $access[ $_k ] = true;

            }

          }

        }
      }

      if ( !empty( $roles_user[ ( $user_id ? "user" : "guest" ) . "_player" ] ) ){
        $_player_access = !empty( $roles_user[ ( $user_id ? "user" : "guest" ) . "_player" ] ) ? explode( ";", $roles_user[ ( $user_id ? "user" : "guest" ) . "_player" ] ) : [];
        $_download_types_access = !empty( $roles_user[ ( $user_id ? "user" : "guest" ) . "_download_types" ] ) ? explode( ";", $roles_user[ ( $user_id ? "user" : "guest" ) . "_download_types" ] ) : [];
        $access["player"] = empty( $access["player"] ) ? $_player_access : array_unique( array_merge( $access["player"], $_player_access ) );
        $access["download_types"] = empty( $access["download_types"] ) ? $_download_types_access : array_unique( array_merge( $access["download_types"], $_download_types_access ) );
      }

      if ( $access["premium"] != "all" &&
        ( !empty( $roles_user["user_premium"] ) ? $roles_user["user_premium"] != "none" : false ) ){

        $access["premium"] = $roles_user["user_premium"];

        if ( $access["premium"] == "some" ){
          
          foreach( $roles_user as $_k => $_v ){
            if ( substr( $_k, 0, strlen("user_premium_") ) == "user_premium_" && $_v ){
              $_kp = substr( $_k, strlen("user_premium_") );
              $_v = explode( ",", $_v );
              $access["premium_rules"][ $_kp ] = !empty( $access["premium_rules"][ $_kp ]  ) ? array_unique( array_merge( $access["premium_rules"][ $_kp ], $_v ) ) : $_v;
            }
          }
  
          if ( !empty( $roles_user["user_p_player"] ) ){
            $access["premium_rules"]["player"] = array_merge( $access["premium_rules"]["player"], explode( ";", $roles_user["user_p_player"] ) );
          }
          
        }    

      }

    }

    return $access;

  }

  public function has_access( $parsed_user_role, $args=[] ){

    $object_item = null;
    $source_type = null;
    $source_hook = null;
    $source_id   = null;
    extract( $args );

    if ( empty( $object_name ) || ( empty( $object_item ) && empty( $object_hash) ) )
    return false;

    $the_object = bof()->object->__get( $object_name );

    if ( !$object_item )
    $object_item = $the_object->select( [ "hash" => $object_hash ], [ "purchase" => true, "purchase_check" => true ] );

    if ( $parsed_user_role["premium"] == "all" )
    return true;

    if ( $parsed_user_role["premium"] == "none" )
    return false;

    if ( !$the_object->method_exists( "check_role_access" ) )
    return false;


    $check_object_access = $the_object->check_role_access( $object_item, $parsed_user_role["premium_rules"] );

    if ( $check_object_access && $source_hook )
    $check_object_access = !empty( $parsed_user_role["player"] ) ? in_array( $source_hook, $parsed_user_role["player"], true ) : false;

    return $check_object_access;

  }

}

?>
