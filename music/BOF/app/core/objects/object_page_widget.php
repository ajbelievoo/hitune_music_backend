<?php

if ( !defined( "bof_root" ) ) die;

class object_page_widget extends bof_type_object {

  public function bof(){
    return array(
      "name" => "page_widget",
      "label" => "Page Widget",
      "icon" => "layout",
      "db_table_name" => "_d_pages_widgets"
    );
  }
  public function columns(){
    return array(
      "page_id" => array(
        "validator" => "int",
      ),
      "unique_id" => array(
        "validator" => "string_abcd",
      ),
      "i" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          ),
        ),
      ),
      "name" => array(
        "validator" => "string_abcd"
      ),
      "args" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          ),
        ),
      ),
      "cache" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        )
      ),
      "time_cache" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        )
      ),
      "time_update" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        )
      ),
      "active" => array(
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        )
      ),
      "native" => array(
        "validator" => array(
          "string",
          array(
            "empty()",
          ),
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
      "page_id" => [ "page_id", "=" ],
      "unique_id" => [ "unique_id", "=" ],
      "name" => [ "name", "=" ]
    );
  }
  public function relations(){
    return array(
      "page" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "page",
          "parent_object_stats_column" => "s_widgets",
          "child_object" => "page_widget",
          "child_object_selector_column" => "page_id",
          "delete_child_too" => true,
        ),
      ),
    );
  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    $listing = false;
    $editing = false;
    $deleting = false;
    $match_page = false;
    $client_single = false;
    extract( $selectArgs );

    if (
      $match_page &&
      !empty( $whereArgs["unique_id"] ) &&
      ( $slug = bof()->nest->user_input( "get", "slug", "string" ) ) &&
      ( $widget_name = bof()->nest->user_input( "get", "widget", "string" ) ) ?
        substr( $whereArgs["unique_id"], 0, 4 ) == "bof_"
      : false
    ){

      $object_name = substr( $whereArgs["unique_id"], 4 );

      if ( bof()->nest->validate( $object_name, "bofClient_object" ) ){

        $the_object = bof()->object->__get( $object_name );
        if ( $the_object->method_exists( "clean_client_single_widget" ) ){
          $the_item = $the_object->select(array("seo_url"=>$slug));
          if ( $the_item ){
            if ( ( $get_widget = $the_object->clean_client_single_widget( $the_item, $widget_name, [], "bofClient" ) ) ){
              $widget = $get_widget;
              return array_merge(
                $the_item,
                array(
                  "_widget" => $widget,
                  "_object_name" => $object_name
                )
              );
            }
          }
        }

      }

    }

    $widget = bof()->object->_select( $this, $whereArgs, $selectArgs );
    return $widget;

  }
  public function create( $whereArray, $insertArray, $updateArray=false ){

    if ( !empty( $insertArray["name"] ) ? $insertArray["name"] == "text" : false ){
      $old_data = $this->_bof_this->select( $whereArray, [ "cache_load_rt" => false, "cache" => false ] );
    }

    $db_id = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray );

    if ( !empty( $insertArray["name"] ) ? $insertArray["name"] == "text" && !empty( json_decode( $updateArray["args"], true )["editor_js"] ) : false ){

      $new_data = bof()->editorjs->finalize(
        bof()->object->parse_caller( $this ),
        $db_id,
        json_decode( $updateArray["args"], true )["editor_js"],
        !empty( $old_data["args_decoded"]["editor_js"] ) ? $old_data["args_decoded"]["editor_js"] : null
      );

      $new_args = !empty( $old_data["args_decoded"] ) ? $old_data["args_decoded"] : json_decode( $insertArray["args"], 1 );
      $new_args["editor_js"] = $new_data;
      $new_args = json_encode( $new_args );

      $this->_bof_this->update(
        array(
          "ID" => $db_id
        ),
        array(
          "args" => $new_args
        )
      );

    }

    return $db_id;

  }
  public function clean( $item, $args ){

    $client_single = false;
    extract( $args );

    $menus = bof()->object->page->_get_widgets(["raw"=>true]);

    $item["display_classes"] = [];

    foreach( array(
      "wid_type",
      "wid_limit",
      "wid_name"
    ) as $data_used_in_design_key ){
      if ( isset( $item["args_decoded"][$data_used_in_design_key] ) ){
        $data_used_in_design = $item["args_decoded"][$data_used_in_design_key];
        $item["display_classes"][] = "{$data_used_in_design_key}_{$data_used_in_design}";
        $item["display_classes"][] = "has_{$data_used_in_design_key}";
      }
    }

    $item["display_classes_string"] = implode( " ", $item["display_classes"] );

    if ( $client_single ){

      $_a = $item["args_decoded"];
      $selectors = [];

      foreach( $_a as $_k => $_v ){
        if ( substr( $_k, 0, strlen( "wid_" ) ) == "wid_" || $_k == "order_by" ) continue;
        $selectors[ $_k ] = $_v;
      }

      $tableLabels = null;
      if ( !empty( $_a["wid_table_title"] ) ){

        $tableLabels = [];
        foreach( explode( ";", $_a["wid_table_title"] ) as $_a_title )
        $tableLabels[] = array(
          "val" => $_a_title,
          "class" => "organic",
        );

      }

      $wid_title = !empty( $_a["wid_title"] ) ? $_a["wid_title"] : null;
      $wid_sub = !empty( $_a["wid_sub_data"] ) ? $_a["wid_sub_data"] : null;

      if ( bof()->getName() == "bof_client" ){
        $lang = bof()->user->check()->language;
        if ( !empty( $_a["wid_title_{$lang}"] ) ) $wid_title = $_a["wid_title_{$lang}"];
        if ( !empty( $_a["wid_sub_data_{$lang}"] ) ) $wid_sub = $_a["wid_sub_data_{$lang}"];
      }

      $original_item = $item;
      $item = array(
        "ID" => $item["unique_id"],
        "args" => $_a,
        "i" => $item["i"],
        "display" => array(
          "type" => !empty( $_a["wid_type"] ) ? $_a["wid_type"] : $_a["wid_name"],
          "title" => $wid_title,
          "sub_data" => $wid_sub,
          "link" => !empty( $_a["wid_link"] ) ? $_a["wid_link"] : null,
          "pagination" => !empty( $_a["wid_pagination"] ) ? $_a["wid_pagination"] : null,
          "bg_img" => !empty( $_a["wid_bg_img"] ) ? bof()->object->file->select( [ "ID" => $_a["wid_bg_img"] ] ) : null,
          "slider_size" => !empty( $_a["wid_slider_size"] ) ? $_a["wid_slider_size"] : null,
          "slider_mason" => !empty( $_a["wid_slider_mason"] ) ? true : false,
          "slider_rows" => !empty( $_a["wid_slider_mason"] ) ? 1 : ( !empty( $_a["wid_slider_rows"] ) ? $_a["wid_slider_rows"] : null ),
          "list_columns" => !empty( $_a["wid_list_columns"] ) ? $_a["wid_list_columns"] : null,
          "table_columns" => !empty( $_a["wid_table_column"] ) ? $_a["wid_table_column"] : null,
          "table_labels" => $tableLabels
        ),
        "items" => array(),
      );

      if ( $item["display"]["type"] == "html" ){
        $item["display"]["html"] = $original_item["args_decoded"]["html"];
      }

      if ( $item["display"]["type"] == "text" && !empty( $original_item["args_decoded"]["editor_js"] ) ){
        $item["display"]["html"] = bof()->editorjs->htmlize( json_decode( $original_item["args_decoded"]["editor_js"] ) );
      }

      if ( $item["display"]["type"] == "ads" ){
        $item["display"]["html"] = "<bof_thingie class='".(!empty($original_item["args_decoded"]["banner_size"])?"size_{$original_item["args_decoded"]["banner_size"]}":"")."'>widget_{$original_item["ID"]}</bof_thingie>";
      }

      if ( $item["display"]["type"] == "grid" ){
        $item["display"]["grid_cols"] = $original_item["args_decoded"]["columns"];
        if ( !empty( $original_item["args_decoded"]["fitMain"] ) )
        $item["display"]["grid_fitMain"] = true;
      }

      if ( !empty( $_a["order_by"] ) ){
        $item["object"] = array(
          "name" => $_a["wid_name"],
          "selectArray" => array(
            "limit" => !empty( $_a["wid_limit"] ) ? $_a["wid_limit"] : null,
            "order_by" => !empty( $_a["order_by"] ) ? $_a["order_by"] : null,
          ),
          "whereArray" => $selectors
        );
      }

    }

    return $item;

  }

  public function verify_args( $_wid_name, $_wid_data ){

    $widgets = bof()->object->page->_get_widgets(["raw"=>true]);

    if ( empty( $widgets["items"][ $_wid_name ] ) )
    throw new bofException( "invalid", 0, null, [ "reason" => "doesnt_exists" ] );

    $_widget_structure = $widgets["items"][ $_wid_name ];
    $_widget_inputs = !empty( $_widget_structure[ "inputs" ] ) ? $_widget_structure[ "inputs" ] : [];
    $_group_inputs = $widgets["groups"][ $_widget_structure["group"] ][ "inputs" ];
    $_rules_inputs = $widgets["rules"];
    $inputs = $_group_inputs ? array_merge( $_rules_inputs, $_widget_inputs, $_group_inputs ) : $_widget_inputs;

    foreach( $inputs as &$_input ){

      $input_value = $input_validator = $input_validator_args = null;
      $input_name = $_input["input"]["name"];
      $input_exists = in_array( $input_name, array_keys( $_wid_data, true ) );
      $input_required = true;

      if ( !empty( $_input["bofInput"] ) ){

        $input_required = false;

        if ( $input_exists ){

          $input_value = $_wid_data[ $input_name ];

          if ( $_input["bofInput"][0] == "file" ){
            if ( !bof()->nest->validate( $input_value, "url", [ "accept_port" => true ] ) )
            $input_value = false;
          }
          elseif ( $_input["bofInput"][0] == "object" ){
            if ( !bof()->nest->validate( $input_value, "int_imploded" ) )
            $input_value = false;
          }

        }

      }
      else {

        if ( !empty( $_input["validator"] ) ){

          $input_validator_args = [];
          
          if ( is_array( $_input["validator"] ) ? count( $_input["validator"] ) == 2 : false )
          list( $input_validator, $input_validator_args ) = $_input["validator"];
          elseif ( is_array( $_input["validator"] ) )
          $input_validator = $_input["validator"][0];
          else
          $input_validator = $_input["validator"];

        }

        if ( !empty( $input_validator_args ) ? in_array( "empty()", $input_validator_args, true ) : false ){
          $input_required = false;
        }

        if ( !empty( $input_validator ) && !empty( $input_validator_args ) ? $input_validator == "in_array" && in_array( "__all__", $input_validator_args["values"], true ) : false )
        $input_required = false;

        if ( !empty( $input_validator ) && $input_exists ? bof()->nest->validate( $_wid_data[ $input_name ], $input_validator, $input_validator_args ) : false ){
          $input_value = $_wid_data[ $input_name ];
        }

      }

      if ( $input_value ? false : $input_required )
      throw new bofException( "invalid", 0, null, [ "reason" => "input_{$input_name}_invalid" ] );

      if ( $input_value )
      $valid_input_values[ $input_name ] = $input_value;

    }

    return $valid_input_values;

  }

  public function seo( $item ){

    $title_name = bof()->object->db_setting->get( "sitename" );

    // native
    if ( !empty( $item["args_decoded"]["wid_title"] ) ){
      $title = $item["args_decoded"]["wid_title"];
    }
    elseif ( !empty( $item["_widget"]["display"]["title"] ) ) {
      $title = $item["_widget"]["display"]["title"];
    }
    elseif ( !empty( $item["display"]["title"] ) ){
      $title =  $item["display"]["title"];
    }
    else {
      $title = "list";
    }

    return array(
      "image" => null,
      "title" => $title . ( $title_name ? " | {$title_name}" : "" ),
      "description" => null,
      "tags" => null,
    );

  }

}

?>
