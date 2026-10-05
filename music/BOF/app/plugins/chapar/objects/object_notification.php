<?php

if ( !defined( "bof_root" ) ) die;

class object_notification extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "notification",
      "label" => "Notifications",
      "icon" => "alert",
      "db_table_name" => "_bof_notification",
    );
  }
  public function columns(){
    return array(
      "hook" => array(
        "label" => "Event",
        "validator" => "string",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = ucfirst( str_replace( "_", " ", $displayData["data"] ) );
              $displayData["sub_data"] = $item["detail"];
              return $displayData;
            },
          )
        )
      ),
      "def_setting" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "detail" => array(
        "valdiator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "detail_params" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "setting" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "texts" => array(
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
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
    );
  }
  public function selectors(){
    return array(
      "hook" => [ "hook", "=" ],
      "col_hook" => [ "hook_id", "by_column" ],
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => true,
        "delete" => false,
        "pagination" => true,
        "edit_page_url" => "notification",
        "list_page_url" => "notifications",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "list" => array(
        "hook" => null,
        "active" => array(
          "type" => "boolean",
          "args" => array(
            "payloads" => [ "activate", "deactivate" ]
          ),
          "label" => "Active",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = !empty( $item["setting_decoded"]["methods"]["all"] );
            return $displayData;
          },
        ),
        "method_db" => array(
          "type" => "boolean",
          "args" => array(
            "payloads" => [ "activate_db", "deactivate_db" ]
          ),
          "label" => "Record",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = !empty( $item["setting_decoded"]["methods"]["all"] ) && !empty( $item["setting_decoded"]["methods"]["db"] );
            return $displayData;
          }
        ),
        "method_push" => array(
          "type" => "boolean",
          "args" => array(
            "payloads" => [ "activate_push", "deactivate_push" ]
          ),
          "label" => "Push",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = !empty( $item["setting_decoded"]["methods"]["all"] ) && !empty( $item["setting_decoded"]["methods"]["push"] );
            return $displayData;
          }
        ),
        "method_email" => array(
          "type" => "boolean",
          "args" => array(
            "payloads" => [ "activate_email", "deactivate_email" ]
          ),
          "label" => "Email",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = !empty( $item["setting_decoded"]["methods"]["all"] ) && !empty( $item["setting_decoded"]["methods"]["email"] );
            return $displayData;
          }
        ),
      ),
      "list_config" => array(
        "order_by" => "ID",
        "order" => "ASC",
        "limit" => 100
      ),
      "object" => array(

        "title" => array(
          "label" => "Default Title",
          "multi" => false,
          "input" => array(
            "name" => "title",
            "type" => "text",
          ),
          "validator" => array(
            "html",
            array(
              "strip_emoji" => false,
            )
          ),
        ),
        "content" => array(
          "label" => "Default Content",
          "multi" => false,
          "tip" => "`Content` is only used as email's body ( currently ). Rest of notification methods only use `Title`",
          "input" => array(
            "name" => "content",
            "type" => "textarea",
          ),
          "validator" => array(
            "html",
            array(
              "empty()",
              "strip_emoji" => false,
            )
          ),
        ),

        "db_title" => array(
          "label" => "Website Title",
          "tip" => "The text displayed when users open their notifications in website. Will fallback to `Default title` if left empty",
          "multi" => false,
          "input" => array(
            "name" => "db_title",
            "type" => "text",
          ),
          "validator" => array(
            "html",
            array(
              "empty()",
              "strip_emoji" => false,
            )
          ),
        ),

        "email_title" => array(
          "label" => "Email Title",
          "tip" => "The title of the email sent to user. Will fallback to `Default title` if left empty",
          "multi" => false,
          "input" => array(
            "name" => "email_title",
            "type" => "text",
          ),
          "validator" => array(
            "html",
            array(
              "empty()",
              "strip_emoji" => false,
            )
          ),
        ),
        "email_content" => array(
          "label" => "Email Content",
          "tip" => "Content of email sent to user. Will fallback to `Default content` if left empty",
          "multi" => false,
          "input" => array(
            "name" => "email_content",
            "type" => "textarea",
          ),
          "validator" => array(
            "html",
            array(
              "empty()",
              "strip_emoji" => false,
            )
          ),
        ),

        "push_title" => array(
          "label" => "Push Title",
          "tip" => "The text sent as push notification directly to user's device. Will fallback to `Default title` if left empty",
          "multi" => false,
          "input" => array(
            "name" => "push_title",
            "type" => "text",
          ),
          "validator" => array(
            "html",
            array(
              "empty()",
              "strip_emoji" => false,
            )
          ),
        ),

      ),
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $request["type"] != "single" ) return;
        if ( $item_name == "time_add" ) return;

        $content = $request["content"][ $request["IDS"][0] ];

        $item_data["tip"] = !empty( $item_data["tip"] ) ? $item_data["tip"] . "<br><br>" : "";

        $_ds = [];
        if ( !empty( $content["detail_params_decoded"] ) ){
          foreach( $content["detail_params_decoded"] as $_k => $_de )
          $_ds[] = "<b>%{$_k}%</b> -> {$_de}";
        }

        if ( $_ds )
        $item_data["tip"] .= "Dynamic parameters: <br>". implode( ", ", $_ds );

        if ( !empty( $content["texts_decoded"][$item_name] ) )
        $item_data["input"]["value"] = $content["texts_decoded"][$item_name];

      },
      "object_be_renderer" => function( $_inputs, $request ){

        if ( $request["type"] == "multi" ) return;

        $_inputs["set"]["texts"] = [];
        if ( !empty( $_inputs["data"] ) ){
          foreach( $_inputs["data"] as $__k => $__v ){
            if ( $__k !== "time_add" && !empty( $__v ) )
            $_inputs["set"]["texts"][ $__k ] = $__v;
          }
        }
        $_inputs["set"]["texts"] = $_inputs["update"]["texts"] = json_encode( $_inputs["set"]["texts"] );

        return $_inputs;

      },
      "actions" => array(
        "activate" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "all", true );
          return [ true, "Activated" ];
        },
        "deactivate" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "all", false );
          return [ true, "De-Activated" ];
        },
        "activate_db" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "db", true );
          return [ true, "Activated" ];
        },
        "deactivate_db" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "db", false );
          return [ true, "De-Activated" ];
        },
        "activate_email" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "email", true );
          return [ true, "Activated" ];
        },
        "deactivate_email" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "email", false );
          return [ true, "De-Activated" ];
        },
        "activate_push" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "push", true );
          return [ true, "Activated" ];
        },
        "deactivate_push" => function( $ids ){
          foreach( explode( ",", $ids ) as $id )
          bof()->object->notification->modify_method( $id, "push", false );
          return [ true, "De-Activated" ];
        },
      ),
    );
  }

  public function modify_method( $ID, $name, $newVal ){

    $item = $this->_bof_this->select(
      array(
        "ID" => $ID
      ),
      array(
        "cache_load_rt" => false
      )
    );

    if ( $name != "all" && $newVal )
    $item["setting_decoded"]["methods"]["all"] = true;
    $item["setting_decoded"]["methods"][$name] = $newVal;

    $this->_bof_this->update(
      array(
        "ID" => $ID
      ),
      array(
        "setting" => json_encode( $item["setting_decoded"] )
      )
    );

  }


}

?>
