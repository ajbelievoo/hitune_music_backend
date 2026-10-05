<?php

if ( !defined( "bof_root" ) ) die;

class object_$NAME$ {

  // Magic
  public function __construct( $loader ){
    $this->loader = $loader;
  }

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "$NAME$",
      "label" => "",
      "icon" => "",
      "db_table_name" => "",
      // "db_empty_select" => false,
      // "db_primary_column" => "ID",
      // "db_rel_table_name" => "",
      // "db_rel_table_col_name" => "",
    );
  }

  public function columns(){
    return array(
      "sample_column" => array(

        "label" => "",
        "tip" => "",

        "input" => array(
          "type" => "input_type",
          "args" => "input_args",
        ),
        "bofInput" => array(
          "bofInput_type",
          array(
            // "object"
            "type" => "",

            // "file"
            "type" => "image",
            "type" => "audio",
            "type" => "video",
            "object_type" => ""
            "multi" => false
          ),
        ),

        "validator" => array(),
        "relations" => array(),
        "selectors" => array(),

        "bofAdmin" => array(

          "sortable" => false,

          "list" => array(

            "label" => false, // default label will be used
            "type" => "list_type", // simple // tag
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = null;
              $displayData["sub_data"] = null;
              $displayData["image_preview"] = null;
              return $displayData;
            },
          ),

          "object" => array(
            "required" => false,
            "seo_slug_source" => false,
            "multi" => false,
            "group" => ""
          ),

          "filters" => array(),

        ),

      ),
    );
  }

  public function relations(){
    return array(
      "rel_key_name" => array(
        "bofAdmin" => array(
          "filters" => array(),
          "objects" => array(),
          "lists" => array(),
        ),
        "exec" => array(
          "type" => "direct",
          "type" => "hub",
          "hub_type" => "",
          "parent_object" => "",
          "parent_object_stats_column" => "",
          "parent_object_selector_column" => "",
          "parent_object_custom_columns" => function( $effected_parents, $effected_childs ){
            if ( $effected_parents ? $effected_parents["all"] : false ){
              foreach( $effected_parents["all"] as $_pid ){
                $this->loader->db->query("UPDATE _u_ms_groups SET time_reply = ( SELECT time_add FROM _u_ms_messages WHERE _u_ms_messages.group_id = _u_ms_groups.ID ORDER BY time_add DESC ) WHERE ID = '{$_pid}'");
              }
            }
          },
          "child_object" => "",
          "child_object_stats_column" => "",
          "child_object_selector_column" => "",
          "delete_child_too" => true,
          "limit" => 1,
        ),
      ),
    );
  }
  public function selectors(){
    return array();
  }
  public function bof_columns(){
    return array(
      "ID",
      "code",
      "hash",
      "time_add",
      "seo",
      "social_links",
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
        "edit_page_url" => "blog_post",
        "list_page_url" => "blog_posts",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "filters" => array(
        "filter_name" => array(
          "title" => "Filter label",
          "tip" => "Filter tip",
          "input" => array(),
          "bofInput" => array(),
        ),
      ),
      "buttons" => array(
        "button_name" => array(
          "id" => "",
          "label" => "",
          "link" => "",
          "payload" => array(
            "post" => array(
              "__action" => "publish",
            ),
          ),
        ),
      ),
      "filters" => array(),
      "list" => array(),
      "list_config" => array(
        "order_by" => "ID",
        "order" => "ASC",
        "limit" => 100
      ),
      "object" => array(),
      "object_groups" => array(
        [ "key", "label" ],
      ),
      "buttons_renderer" => function( $item, $buttons ){
        // unset( $buttons[""] );
        return $buttons;
      },
      "object_renderer" => function( $__ds_item, $__item, $displayData  ){},
      "object_ui_renderer" => function( $object, $parsed, $args, $request, $_inputs, &$data ){},
      "object_be_renderer" => function( $_inputs, $request ){},
      "object_be_renderer_after" => function( $_inputs, $request, $IDS ){},
      "object_item_renderer" => function( $item_name, &$item_data, $request ){},
      "actions" => array(
        "actionName" => function( $ids ){
          return [ false, "failed" ];
        }
      ),
    );
  }

  // BusyOwlFramework helpers
  public function insert( $setArray ){

    return $this->loader->object->_insert( $this, $setArray );

  }
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $deleting = false;
    $editing = false;
    $_eq = [];
    extract( $selectArgs );
    $selectArgs["_eq"] = $_eq;


    $selectArgs["_eq"] = $_eq;
    return $this->loader->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args=[] ){

    $_eq = [];
    $search = false;
    $listing = false;
    extract( $args );

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" =>,
        "image" =>
      );
    }

    return $item;

  }

}

?>
