<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_pageBuilder_widget_verify( $loader, $excuter, $args ){

  $widgets = $loader->object->page->_get_widgets(["raw"=>true]);
  $type = "new";

  $_wid_name = $loader->nest->user_input( "post", "wid_name", "string", [ "strict" => true, "strict_regex" => "[a-z_]" ] );
  $_wid_id = $loader->nest->user_input( "post", "wid_id", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9]", "min" => 10, "max" => 10 ] );
  $_page_id = $loader->nest->user_input( "post", "page_id", "int" );

  if ( !$_wid_name || !$_page_id ? true : empty( $widgets["items"][ $_wid_name ] ) ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $_page_data = $loader->object->page->select(
    array(
      "ID" => $_page_id
    ),
    array(
      "_eq" => [ "widgets" => true ],
      "pageBuilder" => true
    )
  );

  if ( !$_page_data ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $_page_widgets = $_page_data["widgets"];

  if ( $_wid_id ){
    $_wid_data = $loader->object->page_widget->select(["unique_id"=>$_wid_id]);
    if ( $_wid_data ){
      $type = "edit";
      $_wid_name = $_wid_data["name"];
    }
  }

  if ( empty( $widgets["items"][ $_wid_name ] ) ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $_widget_structure = $widgets["items"][ $_wid_name ];
  $_widget_inputs = !empty( $_widget_structure[ "inputs" ] ) ? $_widget_structure[ "inputs" ] : [];
  $_group_inputs = $widgets["groups"][ $_widget_structure["group"] ][ "inputs" ];
  $_rules_inputs = $widgets["rules"];
  $inputs = $_group_inputs ? array_merge( $_rules_inputs, $_widget_inputs, $_group_inputs ) : $_widget_inputs;

  $_inputs = [
    "data" => [],
    "report" => array(
      "fail" => [],
      "ok" => [],
      "empty" => []
    ),
    "set" => []
  ];

  $_files = [];

  foreach( $inputs as &$_input ){

    $input_value = $input_validator = $input_validator_args = null;
    $input_name = $_input["input"]["name"];
    // $input_exists = !empty( $_POST[ $input_name ] );
    $input_exists = $loader->bofInput->exists( $_input );
    $input_required = true;

    if ( !empty( $_input["bofInput"] ) ){

      $input_value = $loader->bofInput->validate( $_input );
      $input_required = false;

      if ( $_input["bofInput"][0] == "file" ){
        $_files[ $input_name ] = array_merge( [
          "value" => $input_value
        ], $_input );
      }

    }
    else {

      if ( !empty( $_input["validator"] ) ){
        list( $input_validator, $input_validator_args ) = $_input["validator"];
      }

      if ( !empty( $input_validator_args ) ? in_array( "empty()", $input_validator_args, true ) : false ){
        $input_required = false;
      }

      if ( !empty( $input_validator ) && $input_exists ){

        $input_value = $loader->nest->user_input( "post", $input_name, $input_validator, $input_validator_args, false );

      }

    }

    if ( $input_value ){
      $_inputs["report"]["ok"][ $input_name ] = $input_value;
    }
    elseif ( !$input_required && !$input_exists ){
      $_inputs["report"]["empty"][ $input_name ] = $input_value;
      $input_value = null;
    }
    else {
      $_inputs["report"]["fail"][ $input_name ] = $input_value;
      $input_value = false;
    }

    $_inputs["data"][ $input_name ] = $input_value === "__all__" ? null : $input_value;
    $_input["value"] = $input_value;
    $_input["required"] = $input_required;

  }

  $_data = [ "wid_name" => $_wid_name ];
  foreach( $_inputs["data"] as $_k => $_v ){
    if ( $_v === null ) continue;
    $_data[ $_k ] = $_v;
  }

  if ( !empty( $_data["wid_type"] ) ? $_data["wid_type"] == "slider" : false ){
    if ( empty( $_data["wid_slider_rows"] ) && empty( $_data["wid_slider_mason"] ) ) $_inputs["report"]["fail"]["wid_slider_rows"] = false;
    if ( empty( $_data["wid_slider_size"] ) ) $_inputs["report"]["fail"]["wid_slider_size"] = false;
  }

  if ( !empty( $_data["wid_type"] ) ? $_data["wid_type"] == "list" : false ){
    if ( empty( $_data["wid_list_columns"] ) ) $_inputs["report"]["fail"]["wid_list_columns"] = false;
  }

  if ( !empty( $_data["wid_type"] ) ? $_data["wid_type"] == "table" : false ){

    if ( empty( $_data["wid_table_column"] ) ) $_inputs["report"]["fail"]["wid_table_column"] = false;
    if ( empty( $_data["wid_table_title"] ) ) $_inputs["report"]["fail"]["wid_table_title"] = false;
    if ( !empty( $_data["wid_table_column"] ) ? ( empty( $_data["wid_table_title"] ) ? true : count( $_data["wid_table_column"] ) != count( explode( ";", $_data["wid_table_title"] ) ) ) : false )
    $_inputs["report"]["fail"]["wid_table_title"] = count( $_data["wid_table_column"] ) . " column(s) selected but only " . count( explode( ";", $_data["wid_table_title"] ) ) . " semicolon separated label(s) entered";

  }

  if ( $_inputs["report"]["fail"] ){
    $loader->api->set_error( "Failed", [ "bad_inputs" => array_keys( $_inputs["report"]["fail"] ), "inputs" => $_inputs ] );
    return;
  }

  $unique_id = $type == "edit" ? $_wid_id : substr( md5(uniqid().rand(1,999999999).microtime()), 0, 10 );
  $_data[ "wid_id" ] = $unique_id;

  $db_id = $loader->object->page_widget->create(
    array(
      "unique_id" => $unique_id
    ),
    array(
      "page_id" => $_page_id,
      "unique_id" => $unique_id,
      "name" => $_wid_name,
      "args" => json_encode( $_data ),
      "active" => 0,
      "i" => $_page_widgets ? count( $_page_widgets ) + 1 : 1
    ),
    array(
      "args" => json_encode( $_data )
    )
  );

  if ( !empty( $_files ) && $db_id ){
    foreach( $_files as $_file ){

      $_validate_file = $loader->object->file->finalize_upload(
        $_file["bofInput"][1]["type"],
        $_file["bofInput"][1]["object_type"],
        "page_widget" . $db_id,
        $_file["value"],
        !empty( $_wid_data["args_decoded"][ $_file["input"]["name"] ] ) ? $_wid_data["args_decoded"][ $_file["input"]["name"] ] : false
      );

      if ( !$_validate_file )
      $_data[ $_file["input"]["name"] ] = 0;

    }
  }

  $loader->api->set_message( "done", [ "inputs" => $_inputs, "data" => $_data ] );

}

?>
