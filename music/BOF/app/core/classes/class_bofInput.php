<?php

if ( !defined( "bof_root" ) ) die;

// BusyOwlFramework Custom-Inputs Helper
class bofInput {

  public $objects = array(
    "storage"
  );

  public function exists( $data, $type="post" ){

    $_vals = $type == "post" ? $_POST : $_GET;

    if ( empty( $data["input"]["name"] ) || ( empty( $data["input"]["type"] ) && empty( $data["bofInput"] ) ) )
    return false;

    if ( !empty( $data["bofInput"] ) )
    return false;

    if ( $data["input"]["type"] == "select_m" ){
      $select_m_exists = false;
      if ( !empty( $data["validator"][1]["values"] ) ){
        foreach( $data["validator"][1]["values"] as $_v ){
          if ( !empty( $_vals[ $data["input"]["name"] . "_" . $_v ] ) )
          $select_m_exists = true;
        }
      }
      return $select_m_exists;
    }

    return !empty( $_vals[ $data["input"]["name"] ] );

  }

  public function parse( $data, $oArgs=[] ){

    $_data = !empty( $data["bof_input"] ) ? $data["bof_input"] : $data["bofInput"];
    $type = $_data[0];
    $args = !empty( $_data[1] ) ? $_data[1] : [];
    if ( $oArgs ) $args = array_merge( $args, $oArgs );

    if ( !in_array( $type, [ "object", "file", "currency", "color" ], true ) )
    fall( "Unkown BofInput type");

    $_name = "parse_{$type}";
    return $this->$_name( $args, $data );

  }
  protected function parse_object( $_args, $data ){

    $type = null;
    $sub_type = null;
    $args = array(
      "search" => null,
      "load" => array(
        "where" => [],
        "select" => [],
        "IDS" => []
      )
    );
    $multi = false;
    $value = null;
    extract( $_args );

    $_items = $_ids = null;

    if ( !empty( $args["load"]["IDS"] ) ? is_array( $args["load"]["IDS"] ) : false )
    $args["load"]["IDS"] = implode( ",", $args["load"]["IDS"] );
    elseif ( !empty( $data["input"]["value"] ) )
    $args["load"]["IDS"] = $data["input"]["value"];

    if ( !empty( $args["load"]["IDS"] ) )
    $args["load"]["where"]["ID_in"] = $args["load"]["IDS"];

    if ( !empty( $args["load"]["where"] ) ){

      if ( $sub_type )
      $args["load"]["where"]["type"] = $sub_type;

      $_items = bof()->object->__get( $type )->select(
        $args["load"]["where"],
        array_merge(
          array(
            "search" => true,
            "object_search" => true,
            "limit" => $multi ? 50 : 1,
            "single" => false
          ),
          !empty( $args["load"]["select"] ) ? $args["load"]["select"] : []
        )
      );

      $_ids = [];
      if ( $_items ){
        foreach( $_items as $_item )
        $_ids[] = $_item["ID"];
      }

    }

    $data["input"] = array(
      "name" => !empty( $data["input"]["name"] ) ? $data["input"]["name"] : "object",
      "type" => "bof_object",
      "value" => !empty( $_ids ) ? implode( ",", $_ids ) : "",
      "bof_object" => $type,
      "bof_object_sub" => $sub_type,
      "bof_s_type" => $multi ? "multi" : "single",
      "bof_items" => $_items,
      "bof_search_eq" => !empty( $args["search"] ) ? $args["search"] : null
    );

    return array(
      "items" => $_items,
      "ids" => $_ids,
      "value" => $_ids,
      "type" => $type,
      "data" => $data
    );

  }
  protected function parse_file( $args, $data ){

    $type = null;
    $object_type = null;
    $_value = null;
    $_pass = null;
    $_preview = null;
    $_maxFiles = 1;
    $translate = false;
    $preview_size = "image_thumb";
    extract( $args );

    $file_rules = bof()->object->file->get_rules( $type, $object_type, [ "get_host" => true ] );

    foreach( $file_rules["validators"]["fl"] as $_format_list )
    $_extensions[] = ".{$_format_list}";

    $__min = "Min";
    $__max = "Max";
    $texts = array(
      "up_rule_extensions" => "Acceotable extensions",
      "up_rule_filesize" => "Acceotable file size",
      "up_rule_img_dim" => "Acceptable image dimensions",
      "up_rule_vid_widh" => "Acceptable video dimensions",
      "up_rule_audio_bit" => "Minimum bitrate",
    );

    if ( $translate ){
      $__max = bof()->object->language->turn( "max", [ "uc_first" => true] , [ "lang" => bof()->object->language->get_users() ] );
      $__min = bof()->object->language->turn( "min", [ "uc_first" => true] , [ "lang" => bof()->object->language->get_users() ] );
      foreach( $texts as $k => &$v )
      $v = bof()->object->language->turn( $k, [ "uc_first" => true] , [ "lang" => bof()->object->language->get_users() ] );
    }

    $data["tip"] = !empty( $data["tip"] ) ? $data["tip"] . "<BR><BR>" : "";
    if ( bof()->getName() == "bof_admin" )
    $data["tip"] .= "Storage server: <b>{$file_rules["file_host_data"]["name"]}</b><br>";
    $data["tip"] .= "{$texts["up_rule_extensions"]}: <b>" . implode( "</b>, <b>", $file_rules["validators"]["fl"] ) . "</b><br>";
    $data["tip"] .= "{$texts["up_rule_filesize"]}: $__min: <b>{$file_rules["validators"]["size_min"]}MB</b>. $__max: <b>{$file_rules["validators"]["size_max"]}MB</b>";
    if ( $type == "image" ) $data["tip"] .= "<br>{$texts["up_rule_img_dim"]}: $__min: <b>{$file_rules["validators"]["dim_min"]}px</b>. $__max: <b>{$file_rules["validators"]["dim_max"]}px</b>";
    if ( $type == "video" ) $data["tip"] .= "<br>{$texts["up_rule_vid_widh"]}: $__min: <b>{$file_rules["validators"]["width_min"]}</b>. $__max: <b>{$file_rules["validators"]["width_max"]}</b>";
    if ( $type == "audio" ) $data["tip"] .= "<br>{$texts["up_rule_audio_bit"]}: <b>{$file_rules["validators"]["br_min"]}</b>";
    if ( bof()->getName() == "bof_admin" ){
      if ( $type == "audio" || $type == "video" ) $data["tip"] .= "<br>HLS Encryption: <b>".(!empty($file_rules["validators"]["hls"])?"On":"Off")."</b> . Keep real file: <b>".(!empty($file_rules["validators"]["hls_kr"])?"On":"Off")."</b>";
      if ( $type == "video" ) $data["tip"] .= "<br>Convert to:" .
      ( $file_rules["validators"]["lower_1080"] ? " <b>1080P</b>" : "" ) .
      ( $file_rules["validators"]["lower_720"] ? " <b>720P</b>" : "" ) .
      ( $file_rules["validators"]["lower_480"] ? " <b>480P</b>" : "" ) .
      ( $file_rules["validators"]["lower_240"] ? " <b>240P</b>" : "" );
      if ( $type == "audio" ) $data["tip"] .= "<br>Convert to:" .
      ( $file_rules["validators"]["lower_64"] ? " <b>64K</b>" : "" ) .
      ( $file_rules["validators"]["lower_128"] ? " <b>128K</b>" : "" ) .
      ( $file_rules["validators"]["lower_192"] ? " <b>192K</b>" : "" ) .
      ( $file_rules["validators"]["lower_256"] ? " <b>256K</b>" : "" );
      $data["tip"] .= "<br><br><a href='storage_setting'>For <b>Storage setting</b> click here</a><br><a href='upload_setting'>For <b>Upload setting</b> click here</a>";
    }


    if ( !$_value && !empty( $data["input"]["value"] ) ){
      if ( ( $fileData = bof()->object->file->select(["ID"=>$data["input"]["value"]]) ) ){
        $_value = $fileData["ID"];
        $_pass = $fileData["pass"];
        if ( $fileData["type"] == "image" )
        $_preview = !empty( $fileData[ $preview_size ] ) ? $fileData[ $preview_size ] : false;
        elseif ( $fileData["type"] == "audio" )
        $_preview = $fileData["web_address"];
        elseif ( $fileData["type"] == "video" )
        $_preview = $fileData["web_address"];
      }
    }

    $data["input"] = array(
      "name" => $data["input"]["name"],
      "type" => "bof_file",
      "value" => $_value,
      "bof_file_pass" => $_pass,
      "bof_file_type" => $type,
      "bof_file_object_type" => $object_type,
      "preview" => $_preview,
      "accept" => implode( ",", $_extensions ),
      "chunk" => bof()->object->db_setting->get( "fs_chunk" ) ? "yes" : "no",
      "chunk_size" => bof()->object->db_setting->get( "fs_chunk_size" ),
      "max_files" => $_maxFiles,
      "cap" => !empty( $fileData["name"] ) ? $fileData["name"] : null,
    );

    return array(
      "type" => $type,
      "object_type" => $object_type,
      "data" => $data
    );

  }
  protected function parse_currency( $args, $data ){

    $value = !empty( $data["input"]["value"] ) ? $data["input"]["value"] : null;
    extract( $args );

    $default_currency = bof()->object->currency->get_default();

    $data["tip"] = "Price in <b>{$default_currency["name"]}</b>" . ( !empty( $data["tip"] ) ? ". {$data["tip"]}" : "" );

    $data["input"] = array(
      "name" => $data["input"]["name"],
      "type" => "text",
      "value" => $value,
    );

    return array(
      "data" => $data
    );

  }
  protected function parse_color( $args, $data ){

    $value = !empty( $data["input"]["value"] ) ? $data["input"]["value"] : null;
    extract( $args );

    $data["input"] = array(
      "name" => $data["input"]["name"],
      "type" => "text",
      "value" => "#" . $value,
      "attr" => "data-coloris"
    );

    return array(
      "data" => $data
    );

  }

  public function validate( $data ){

    $_data = !empty( $data["bof_input"] ) ? $data["bof_input"] : $data["bofInput"];
    $type = $_data[0];
    $args = !empty( $_data[1] ) ? $_data[1] : [];

    if ( !in_array( $type, [ "object", "file", "currency", "color" ], true ) )
    fall( "Unkown BofInput type");

    $_name = "validate_{$type}";
    return $this->$_name( $args, $data );

  }
  protected function validate_object( $_args, $data ){

    $type = null;
    $sub_type = null;
    $multi = false;
    $user_value = bof()->nest->user_input( "post", $data["input"]["name"], "string", [ "strict" => true, "strict_regex" => "[0-9,]" ] );
    extract( $_args );

    if ( !$user_value )
    return false;

    $whereArray = array(
      "ID_in" => $user_value
    );

    if ( $sub_type )
    $whereArray["type"] = $sub_type;

    $_items = bof()->object->__get( $type )->select(
      $whereArray,
      array(
        "columns" => "ID",
        "clean" => false,
        "limit" => $multi ? 50 : 1,
        "single" => false
      )
    );

    if ( !$_items )
    return false;

    foreach( $_items as $_item )
    $_ids[] = $_item["ID"];

    return implode( ",", $_ids );

  }
  protected function validate_file( $_args, $data ){

    $cover_id = bof()->nest->user_input( "post", $data["input"]["name"], "int" );
    $cover_pass = bof()->nest->user_input( "post", "{$data["input"]["name"]}_pass", "string", [ "strict" => true, "strict_regex" => "[0-9a-zA-Z]" ] );

    if ( $cover_id ){
      if ( $cover_pass )
      $validate = bof()->object->file->validate_pass( $cover_id, $cover_pass );
      else {
        $file = bof()->object->file->sid( $cover_id );
        if ( $file ? ( ( $file["user_id"] && $file["user_id"] == bof()->user->get()->ID ) || ( !$file["user_id"] && bof()->user->get()->ID ) ) : false )
        $validate = true;
      }
    }

    if ( !empty( $validate ) )
    return $cover_id;

    return false;

  }
  protected function validate_currency( $_args, $data ){

    $user_value = bof()->nest->user_input( "post", $data["input"]["name"], "float", [ "empty()", "min" => 0 ] );
    extract( $_args );

    if ( !$user_value )
    return false;

    return $user_value;

  }
  protected function validate_color( $_args, $data ){

    $user_value = bof()->nest->user_input( "post", $data["input"]["name"], "string_color_hex", $_args );
    extract( $_args );

    if ( !$user_value )
    return false;

    return $user_value;

  }

  public function execute( $type, $args=[] ){

    if ( !in_array( $type, [ "object", "file" ], true ) )
    fall( "Unkown BofInput type");

    $_name = "execute_{$type}";
    return $this->$_name( $args );

  }
  protected function execute_file( $args ){

    if ( !bof()->user->check()->logged )
    return false;

    $upload = bof()->object->file->handle_upload();

    if ( !$upload[0] ){
      // bof()->response->set_header( "503", "HTTP/1.1 503 Service Temporarily Unavailable" );
      bof()->api->set_error( $upload[1], [ "error" => $upload[1], "output_args" => [ "turn" => false ] ] );
      return false;
    }

    if ( bof()->getName() == "bof_admin" && !empty( $upload[2]["type"] ) ? $upload[2]["type"] === "image" : false ){
      $upload[2]["cap"] = "bof";
    }

    bof()->api->set_message( $upload[1], $upload[2], [ "output_args" => [ "turn" => false ] ] );
    return true;

  }
  protected function execute_object( $args ){

    // $extra_query = bof()->nest->user_input( "post", "bofInput_extra_query", "json" );
    $query = bof()->nest->user_input( "post", "bofInput_bof_query", "string" );
    $object_name = bof()->nest->user_input( "post", "bofInput_object_type", "string", [ "strict" => true, "strict_regex" => "[a-z0-9_]" ] );
    $object_sub_type = bof()->nest->user_input( "post", "bofInput_object_sub_type", "string", [ "strict" => true, "strict_regex" => "[a-z0-9_]" ] );

    if ( !$object_name ){
      bof()->api->set_error( "failed", [ "output_args" => [ "turn" => false ] ] );
      return false;
    }

    if ( bof()->getName() == "bof_admin" ){

      if ( bof()->user->check()->extra["role"] == "admin" ? false : bof()->user->check()->extra["moderator_roles"]["type"] == "some" ){

        if ( empty( bof()->user->check()->extra["moderator_roles"]["objects"] ) )
        return false;

        if ( !in_array( $object_name, array_keys( bof()->user->check()->extra["moderator_roles"]["objects"] ), true ) )
        return false;

      }

    } else {

      $objects = bof()->bofClient->_get_objects();
      if ( !in_array( $object_name, array_keys( $objects ), true ) )
      return false;

      $the_object = bof()->object->__get( $object_name );
      if( !$the_object )
      return false;

      if ( !$the_object->method_exists( "bof_client" ) )
  		return false;

      if ( empty( $the_object->bof_client()["public_browse"] ) ? ( empty( $the_object->bof_client()["user_browse"] ) || !bof()->user->check()->logged ) : false )
      return false;

    }

    $whereArray = array(
      "query" => $query,
    );

    if ( $object_sub_type )
    $whereArray["type"] = $object_sub_type;

    $objects = bof()->object->__get( $object_name )->select(
      $whereArray,
      array(
        "search" => true,
        "object_search" => true,
        "single" => false,
        "limit" => 50
      )
    );

    bof()->api->set_message( "ok", [ "objects" => $objects, "output_args" => [ "turn" => false ] ] );
    return true;

  }

  public function __get_value( $name, $args=[], $input_type = "get" ){

    $value = null;
    $_p_exists = isset( $_POST[ $name ] ) ? $_POST[ $name ] !== '' && $_POST[ $name ] !== ' ' : false;
    $_r_exists = isset( $_REQUEST[ $name ] ) || isset( $_GET[ $name ] );
    $exists = $input_type == "post" ? $_p_exists : $_r_exists;

    // Bof input
    if ( !empty( $args["bofInput"] ) ){

      $bofInput_type = $args["bofInput"][0];
      $bofInput_args = !empty( $args["bofInput"][1] ) ? $args["bofInput"][1] : null;
      $bofInput_user_values = bof()->nest->user_input( $input_type, $name, "string", [ "strict" => true, "strict_regex" => "[0-9,]" ], false );
      $bofInput_user_values_validated = [];

      if ( $bofInput_user_values ){
        foreach( explode( ",", $bofInput_user_values ) as $bofInput_user_value ){
          if ( empty( $bofInput_user_value ) ? true : !bof()->general->numeric( $bofInput_user_value ) ) continue;
          $bofInput_user_values_validated[] = intval( $bofInput_user_value );
        }
      }

      if ( $bofInput_user_values_validated )
      $value = $bofInput_user_values_validated;

    }
    // select_m
    elseif ( !empty( $args["input"]["type"] ) ? $args["input"]["type"] == "select_m" && !empty( $args["input"]["options"] ) : false ){
      $_sm_vals = [];
      $_sm_keys = array_values( array_map( function( $val ){
        return $val[0];
      }, $args["input"]["options"] ) );

      if ( !empty( $_sm_keys ) ){
        foreach( $_sm_keys as $_sm_key ){
          $_sm_key_sent = $input_type == "get" ? !empty( $_GET["{$name}_{$_sm_key}"] ) : !empty( $_POST["{$name}_{$_sm_key}"] );
          if ( $_sm_key_sent ) $_sm_vals[] = $_sm_key;
        }
      }


      if ( count( $_sm_vals ) )
      $value = implode( ";", $_sm_vals );

    }
    // Html input
    elseif ( !empty( $args["validator"] ) ) {

      $validator = !empty( $args["validator"][0] ) ? $args["validator"][0] : $args["validator"];
      $validator_args = !empty( $args["validator"][1] ) ? $args["validator"][1] : [];
      $value = bof()->nest->user_input( $input_type, $name, $validator, $validator_args );

    }

    return array(
      $exists,
      $value
    );

  }
  public function __check_form( $inputs, $args=[] ){

    $input_required_def = true;
    $input_name_prefix = false;
    $input_name_prefix_separator = "_";
    extract( $args );

    $output = [
      "data" => [],
      "report" => array(
        "fail" => [],
        "ok" => [],
        "empty" => []
      ),
    ];

    foreach( $inputs as $k => $input_w ){

      $input = !empty( $input_w["input"] ) ? $input_w["input"] : false;

      if ( empty( $input["name"] ) )
      $input_name = $input_w["input"]["name"] = ( $input_name_prefix ? $input_name_prefix . $input_name_prefix_separator : "" ) . $k;

      else
      $input_name = ( $input_name_prefix ? $input_name_prefix . $input_name_prefix_separator : "" ) . $input["name"];

      $input_required = in_array( "required", array_keys( $input_w ), true ) ? $input_w["required"] : $input_required_def;

      // get value
      if ( !empty( $input_w["bofInput"] ) ){

        $input_exists = !empty( $_POST[ $input_name ] );
        $input_value = bof()->bofInput->validate( $input_w );

        if ( !$input_value )
        $input_value = false;

      }
      else {

        list( $input_exists, $input_value ) = bof()->bofInput->__get_value( $input_name, $input_w, "post" );

      }

      if ( $input_value ){
        $output["report"]["ok"][ $input_name ] = $input_value;
      }
      elseif ( !$input_required && ( !$input_exists || $input_value === 0 || $input_value === "0" ) ){
        $output["report"]["empty"][ $input_name ] = $input_value;
        $input_value = false;
      }
      else {
        $output["report"]["fail"][ $input_name ] = $input_required && !$input_exists ? "Can't be empty" : null;
        $input_value = false;
      }

      $output[ "data" ][ $input_name_prefix ? str_replace( $input_name_prefix . $input_name_prefix_separator, "", $input_name ) : $input_name ] = $input_value;

    }

    $output["bad_inputs"] = !empty( $output["report"]["fail"] ) ? array_keys( $output["report"]["fail"] ) : [];
    $output["inputs"] = [ "report" => $output["report"] ];
    $output["ok"] = empty( $output["report"]["fail"] );
    unset( $output["report"] );
    return $output;

  }

}

?>
