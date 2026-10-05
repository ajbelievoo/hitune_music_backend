<?php

if ( !defined( "bof_root" ) ) die;

class object_user_notification extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "user_notification",
      "label" => "User Notifications",
      "icon" => "alert",
      "db_table_name" => "_u_notifications",
    );
  }
  public function columns(){
    return array(
      "hook" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "user_id" => array(
        "label" => "User",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = $item["bof_dir_user"]["name_styled"];
              $displayData["sub_data"] = "Email: {$item["bof_dir_user"]["email"]}<br>ID: {$item["bof_dir_user"]["ID"]}";
              return $displayData;
            },
          )
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "user_notification",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "target_email" => array(
        "validator" => array(
          "email",
          array(
            "empty()"
          )
        )
      ),
      "target_push_count" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        )
      ),
      "triggerer_object" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "triggerer_id" => array(
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        )
      ),
      "source_object" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "source_id" => array(
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        )
      ),
      "message_type" => array(
        "validator" => "string"
      ),
      "message_texts" => array(
        "public" => true,
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "message_params" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "message_image" => array(
        "public" => true,
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "message_link" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "method_email" => array(
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          )
        )
      ),
      "method_push" => array(
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          )
        )
      ),
      "extra" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "time_seen" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
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
      "user_id"  => [ "user_id", "=" ],
      "col_user" => [ "user_id", "by_column" ],
      "target_email" => [ "target_email", "=" ],
      "triggerer_object" => [ "triggerer_object", "=" ],
      "triggerer_id" => [ "triggerer_id", "=" ],
      "message_type" => [ "message_type", "=" ],
      "hook" => [ "hook", "=" ],
      "source_object" => [ "source_object", "=" ],
      "source_id" => [ "source_id", "=" ],
    );
  }
  public function clean( $item, $args=[] ){

    $for_display = false;
    extract( $args );

    if ( $for_display ){

      if ( empty( $item["message_texts_decoded"]["title"] ) )
      return null;

      $item["message_title"] = $item["message_texts_decoded"]["title"];

      if ( empty( $item["message_image"] ) ){
        $placeholder = bof()->object->db_setting->get( "placeholder" );
        if ( $placeholder ){
          $placeholder = bof()->object->file->select( [ "ID" => $placeholder ] );
          if ( !empty( $placeholder["image_thumb"] ) )
          $item["message_image"] = $placeholder["image_thumb"];
        }
      }

      if ( empty( $item["time_seen"] ) )
      $this->_bof_this->update(
        array(
          "ID" => $item["ID"]
        ),
        array(
          "time_seen" => bof()->general->mysql_timestamp()
        )
      );

      $item = array(
        "type" => $item["message_type"],
        "image" => $item["message_image"],
        "title" => $item["message_title"],
        "link" => $item["message_link"],
        "time" => bof()->general->passed_time_hr( time() - strtotime( $item["time_add"] ) )["string"],
        "seen" => $item["time_seen"] ? true : false
      );

    }

    return $item;

  }

}

?>
