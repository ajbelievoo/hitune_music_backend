<?php

if ( !defined( "bof_root" ) ) die;

class object_ugc_action extends bof_type_object {

  public function bof(){
    return array(
      "name" => "ugc_action",
      "label" => "User Action",
      "icon" => "handshake",
      "db_table_name" => "_u_actions",
    );
  }
  public function columns(){
    return array(
      "user_id" => array(
        "public" => true,
        "label" => "User",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_dir_user"]["bof_file_avatar"] ) )
              $displayData["image_preview"] = $item["bof_dir_user"]["bof_file_avatar"]["image_thumb"];
              $displayData["data"] = $item["bof_dir_user"]["name_styled"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "col_user" => array(
              "title" => "User(s)",
              "input" => array(
                "name" => "col_user",
                "type" => "bof_input",
              ),
              "bofInput" => array(
                "object",
                array(
                  "type" => "user",
                  "multi" => true,
                  "args" => array(
                    "filter" => "col_user",
                  )
                )
              )
            ),
          ),
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => false
          )
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "user_subs",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
        "selectors" => array(
          "col_user" => [ "user_id", "by_column" ],
          "user_id" => [ "user_id", "=" ]
        )
      ),
      "type" => array(
        "public" => true,
        "label" => "Type",
        "validator" => array(
          "in_array",
          array(
            "values" => [ "stream", "download" ]
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "tag"
          ),
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "type" => array(
              "title" => "Type",
              "input" => array(
                "name" => "type",
                "type" => "select",
                "options" => array(
                  [ "__all__", "All" ],
                  [ "like", "Like" ],
                  [ "playlist", "Playlist-item" ],
                  [ "purchase", "Purchase" ],
                  [ "create", "Upload" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "like", "playlist", "purchase", "create", "subscribe" ]
                )
              )
            )
          ),
        ),
        "input" => array(
          "type" => "select",
          "options" => array(
            [ "like", "Like" ],
            [ "playlist", "Playlist-item" ],
            [ "purchase", "Purchase" ],
            [ "create", "Upload" ],
          )
        ),
        "selectors" => array(
          "type" => [ "type", "=" ],
        )
      ),
      "object_name" => array(
        "public" => true,
        "label" => "Object Name",
        "validator" => array(
          "string",
          array(
            "regex" => "[a-zA-Z0-9\-_]"
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "tag"
          ),
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "text",
        ),
        "selectors" => array(
          "object_name" => [ "object_name", "=" ],
          "col_object_name" => [ "object_name", "by_column" ]
        )
      ),
      "object_id" => array(
        "public" => true,
        "label" => "Object ID",
        "validator" => "int",
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "tag"
          ),
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "digit",
        ),
        "selectors" => array(
          "object_id" => [ "object_id", "=" ],
        )
      ),
      "related_object_name" => array(
        "public" => true,
        "label" => "Related Object Name",
        "validator" => array(
          "string",
          array(
            "regex" => "[a-zA-Z0-9\-_]",
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "text",
        ),
        "selectors" => array(
          "related_object_name" => [ "related_object_name", "=" ],
          "col_related_object_name" => [ "related_object_name", "by_column" ]
        )
      ),
      "related_object_id" => array(
        "public" => true,
        "label" => "Related Object ID",
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "digit",
        ),
        "selectors" => array(
          "related_object_id" => [ "related_object_id", "=" ],
        )
      ),
      "extra_data" => array(
        "label" => "ExtraData",
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        ),
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "hash",
      "time_add"
    );
  }
  public function relations(){
    return array(
      "user" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_playlists",
          "child_object" => "ugc_playlist",
          "child_object_selector_column" => "user_id",
          "delete_child_too" => true
        ),
      )
    );
  }
  public function selectors(){
    return array(
      "user_id" => [ "user_id", "=" ],
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "user_action",
        "list_page_url" => "user_actions",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
    );
  }
  public function bof_client(){
    return array(
      "single_url_prefix" => "ugc_action",
      "buttons" => array(
        "link" => true,
      )
    );
  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = null;
    $listing = null;
    $deleting = null;
    $editing = null;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing ){
      $_eq["subs_plan"] = [];
      $_eq["user"] = [ "_eq" => [ "avatar" => [] ] ];
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function insert( $insertArray ){

    $insertArray["hash"] = !empty( $insertArray["hash"] ) ? $insertArray["hash"] : $this->_bof_this->get_free_hash();

    $insert = bof()->object->_insert( $this, $insertArray );

    if ( $insertArray["type"] == "playlist" && $insertArray["related_object_name"] == "ugc_playlist" && !empty( $insertArray["related_object_id"] ) )
    bof()->object->ugc_playlist->catch_type( $insertArray["related_object_id"] );

    return $insert;

  }

  public function clean( $item, $args ){

    $get_object_item = true;
    extract( $args );

    if ( $get_object_item ){

      $action_object = bof()->object->__get( $item["object_name"] );
      $action_item = $action_object->select(
        array(
          "ID" => $item["object_id"]
        ),
        array(
          "as_widget" => true,
          "_eq" => array(
            "cover" => []
          )
        )
      );

      if ( !empty( $action_item ) ){
        $action_item["ot"] = $item["object_name"];
        $action_item["buttons"] = bof()->bofClient->__parse_item_buttons(
          $item["object_name"],
          $action_object,
          $action_item["raw"],
          $action_object->bof_client()["buttons"]
        );
        $item["action"] = $action_item;
      }
      else {
        return false;
      }

    }

    return $item;

  }
  public function clean_as_widget( $item, $args ){

    if ( empty( $item["action"] ) )
    return false;

    return array(
      "title"    => $item["action"]["title"],
      "sub_data" => !empty( $item["action"]["sub_data"] ) ? $item["action"]["sub_data"] : null,
      "sub_link" => !empty( $item["property"]["sub_link"] ) ? $item["property"]["sub_link"] : null,
      "cover"    => !empty( $item["action"]["cover"] ) ? $item["action"]["cover"] : null,
      "raw"      => $item["action"]["raw"],
      "ot"       => $item["object_name"],
      "on"       => bof()->object->language->turn( $item["object_name"], [], [ "uc_first" => true, "lang" => "users" ] ),
      "buttons"  => $item["action"]["buttons"],
      "hash"     => $item["action"]["raw"]["hash"]
    );

  }

}

?>
