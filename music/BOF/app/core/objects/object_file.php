<?php

if ( !defined( "bof_root" ) ) die;

class object_file extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "file",
      "label" => "File",
      "icon" => "file",
      "db_table_name" => "_bof_files",
    );
  }
  public function columns(){
    return array(
      "pass" => array(
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[a-zA-Z0-9]"
          ),
        )
      ),
      "type" => array(
        "validator" => array(
          "in_array",
          array(
            "values" => [ "image", "audio", "video" ]
          ),
        )
      ),
      "host_id" => array(
        "label" => "Storage",
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $data = bof()->object->storage->select(["ID"=>$item["host_id"]]);
              $displayData["data"] = $data["name"];
              return $displayData;
            }
          ),
        )
      ),
      "dest_host_id" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        )
      ),
      "user_id" => array(
        "label" => "Uploader",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = "System";
              if ( $item["user_id"] ){
                $data = bof()->object->user->select(["ID"=>$item["user_id"]]);
                $displayData["data"] = "@" . $data["username"];
              }
              return $displayData;
            }
          ),
        )
      ),
      "path" => array(
        "validator" => array(
          "string",
          array(
            "allow_eol" => true
          )
        )
        
      ),
      "name" => array(
        "label" => "Name",
        "tip" => "Caption/name/alt text of image",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){

              if ( !empty( $item["image_thumb"] ) )
              $displayData["image_preview"] = $item["image_thumb"];

              $displayData["data"] = pathinfo( $item["path"], PATHINFO_FILENAME ) . "<b>.{$item["extension"]}</b>";

              return $displayData;

            },
          ),
          "object" => array(
            "required" => true,
            "title" => "test"
          )
        )
      ),
      "extension" => array(
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "empty()",
          ),
        ),
      ),
      "mime_type" => array(
        "validator" => array(
          "string",
          array(
            "empty()",
            "strict" => true,
            "strict_regex" => "[a-zA-Z0-9\/\\_\-]"
          ),
        ),
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
      "object_type" => array(
        "label" => "Used in",
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[a-zA-Z0-9_\-]"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = "-";
              if ( !empty( $item["used_in_object"] ) )
              $displayData["data"] = bof()->object->__get( $item["used_in_object"] )->bof()["label"];
              return $displayData;
            }
          ),
        )
      ),
      "size" => array(
        "label" => "Total<br>Size",
        "validator" => array(
          "float",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = bof()->general->filesize_hr( $item["size"], [ "decimals" => 0 ] );
              return $displayData;
            },
          )
        )
      ),
      "used" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0,
          ),
        ),
      ),
      "used_in" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          ),
        ),
      ),
      "used_in_object" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          ),
        ),
      ),
      "time_moved" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
    );
  }
  public function selectors(){
    return array(
      "ID"          => [ "ID", "=" ],
      "pass"        => [ "pass", "=" ],
      "type"        => [ "type", "=" ],
      "host_id"     => [ "host_id", "=" ],
      "dest_host_id" => [ "dest_host_id", "=" ],
      "user_id"     => [ "user_id", "=" ],
      "object_type" => [ "object_type", "=" ],
      "used"        => [ "used", "=" ],
      "used_in_object" => [ "used_in_object", "=" ],
      "cleaning"   => function( $val ){

        if ( !$val )
        return;

        return array(
          "oper" => "AND",
          "cond" => array(
            [ "time_add", "<", "SUBDATE( now(), INTERVAL 5 DAY )", true ],
            [ "time_moved", null, null, true ]
          )
        );

      },
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "file",
        "list_page_url" => "files",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "buttons" => [],
      "filters" => array(
        "type" => array(
          "title" => "Type",
          "input" => array(
            "name" => "type",
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "image", "Image" ],
              [ "audio", "Audio" ],
              [ "video", "Video" ],
              [ "zip", "Zip" ],
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "audio", "image", "zip", "video", "__all__" ] ]
          )
        ),
      ),
      "list" => array(
        "name" => [],
        "host_id" => [],
        "user_id" => [],
        "size" => [],
        "object_type" => null,
        "time_add" => array(
          "label" => "Upload<br>Time",
          "type" => "time"
        )
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["type"] != "image" )
        $buttons = [];

        elseif ( $item["host_id"] == 1 )
        $buttons[] = array(
          "label" => "Make resized alternatives",
          "payload" => array(
            "post" => array(
              "__action" => "resize"
            )
          )
        );

        return $buttons;

      },
      "object" => array(

        "name" => null,
        "filename" => array(
          "label" => "Filename",
          "tip" => "The filename that will be used as web-address of this file. Currenly only works on images uploaded to this server. Third party storages support will be added in future updates",
          "multi" => false,
          "input" => array(
            "name" => "filename",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
              "strict" => true,
            )
          ),
        ),

      ),
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $request["type"] != "single" ) return;

        $content = $request["content"][ $request["IDS"][0] ];

        if ( $item_name == "filename" ){
          $item_data["input"]["value"] = pathinfo( $content["path"], PATHINFO_FILENAME );
        }

      },
      "object_be_renderer" => function( $_inputs, $request ){

        if ( $request["type"] == "multi" ) return;

        $content = $request["content"][ $request["IDS"][0] ];

        $data = [];

        $oldFileName = pathinfo( $content["path"], PATHINFO_FILENAME );
        $newFileName = $_inputs["data"]["filename"];

        if ( $newFileName ? $oldFileName != $newFileName : false ){
          $newPath = base_root . "/" . pathinfo( $content["path"], PATHINFO_DIRNAME ) . "/" . $newFileName . "." . pathinfo( $content["path"], PATHINFO_EXTENSION );
          rename( base_root . "/" . $content["path"], $newPath );
          bof()->object->file->update(
            array(
              "ID" => $content["ID"],
            ),
            array(
              "path" => $this->_bof_this->clean_path( $newPath, true )
            )
          );
        }

        return $_inputs;

      },
      "actions" => array(
        "resize" => function( $ids ){

          if ( count( explode( ",", $ids ) ) > 1 )
          return;

          $ID = $ids;
          $file = bof()->object->file->sid( $ID );

          if ( $file["host_id"] != 1 )
          return;

          $rules = bof()->object->file->get_rules( $file["type"] );

          if ( !empty( $file["data_decoded"]["_sisters"] ) ){

            $item_storage = bof()->object->storage->select(array(
              "ID" => $file["host_id"]
            ));

            foreach( $file["data_decoded"]["_sisters"] as $_sis ){
              $remove = bof()->transit
              ->set_storage( $item_storage )
              ->set_file( $_sis["path"] )
              ->delete();
            }

          }

          $d = bof()->object->file->_process_file( $file, $rules, array(
            "force_on_done" => true
          ) );

          if ( empty( $d ) )
          return;

          bof()->object->file->update(
            array(
              "ID" => $ID
            ),
            array(
              "size" => $d["data"]["total_size"],
              "data" => json_encode( $d["data"] )
            )
          );

          return "ok";

        }
      ),
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

    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function insert( $setArgs ){

    extract( $setArgs );

    if ( empty( $setArgs["pass"] ) )
    $setArgs["pass"] = substr( uniqid(), 0, 10 );

    if ( !empty( $setArgs["path"] ) )
    $setArgs["path"] = $this->clean_path( $setArgs["path"] );

    if ( $host_id == 1 ){
      if ( empty( $name ) ) $setArgs["name"] = pathinfo( base_root . "/" . $path, PATHINFO_FILENAME );
      if ( empty( $extension ) ) $setArgs["extension"] = pathinfo( base_root . "/" . $path, PATHINFO_EXTENSION );
      if ( empty( $mime_type ) ){
        try {
          $result = new finfo();
          $setArgs["mime_type"] = $result->file( base_root . "/" . $path, FILEINFO_MIME_TYPE );
        } catch( Error|Warning|Exception $err ){}
      }
    }

    return bof()->object->_insert( $this, $setArgs );

  }
  public function clean( $item, $args ){

    $select = false;
    $unlock = false;
    extract( $args );

    $_storage = bof()->object->storage->select(array("ID"=>$item["host_id"]));

    $item["used_in_parsed"] = !empty( $item["used_in"] ) ? explode( ",", $item["used_in"] ) : [];
    $item["web_address"] = bof()->transit->set_storage( $_storage )->set_file( $item )->url();

    $item["abs_path"] = null;
    if ( $item["host_id"] == 1 )
    $item["abs_path"] = $this->_bof_this->clean_path( base_root . "/" .  $item["path"] );

    if ( $item["type"] == "image" ){
      $item["image_thumb"] = $item["web_address"];
      $item["image_original"] = $item["web_address"];
    }
    if ( $item["data_decoded"] ){

      if ( !empty( $item["data_decoded"]["_sisters"] ) ){
        foreach( $item["data_decoded"]["_sisters"] as &$_sis ){
          $_sis["web_address"] = bof()->transit->set_file( $_sis["path"] )->url();
          if ( $item["type"] == "image" ){
            $item["image_list"][ $_sis["web_address"] ] = [ $_sis["width"], $_sis["height"] ];
          }
        }
      }

      if ( $item["type"] == "image" )
      $item["image_list"][ $item["web_address"] ] = [ $item["data_decoded"]["width"], $item["data_decoded"]["height"] ];

    }
    if ( $item["type"] == "image" && !empty( $item["image_list"] ) ){

      $item["image_list"] = array_merge(
        array(
          $item["web_address"] => [ $item["data_decoded"]["width"], $item["data_decoded"]["height"] ]
        ),
        $item["image_list"]
      );

      $_images = array_keys( $item["image_list"] );
      $item["image_thumb"] = end( $_images );
      $item["image_strings"] = bof()->image->html( $item["image_list"], $item );

    }

    if ( $select ? preg_match( '/image_strings_([0-9]{1,2})_(html|sources|image)/', $select, $matches ) : false ){

      if ( !empty( $item["image_strings"][$matches[1]][$matches[2]] ) )
      $item = $item["image_strings"][$matches[1]][$matches[2]];
      else
      $item = false;

    }

    if ( $unlock && $item["type"] == "image" && !empty( $item["web_address"] ) ? preg_match( "/\/files\/protected\//", $item["web_address"] ) : false ){
      $item["web_address"] = bof()->source->grant_access( $item["object_type"], md5( $item["ID"] ), md5( $item["name"] ), $item["web_address"], "1 HOUR" );
    }

    return $item;

  }

  public function admin_setting_ui( $groups ){

    $_max_up_size = bof()->general->_get_server_maximum_upload_size();
    $groups["fs_chunk"]["inputs"]["fs_chunk_size"]["tip"] = str_replace( "__MAX_SERVER_UPLOAD_SIZE__", bof()->general->filesize_hr( $_max_up_size ), $groups["fs_chunk"]["inputs"]["fs_chunk_size"]["tip"] );
    $groups["fs_chunk"]["inputs"]["fs_chunk_size"]["validator"]["args"]["max"] = ( $_max_up_size / (1000*1000) );

    $rules_groups = [];
    foreach( bof()->bofAdmin->_get_objects() as $object_name => $object_args ){
      $object_parsed = bof()->object->parse_caller( $object_name );
      $columns = $object_parsed->parsed->columns;
      if ( $object_parsed->proxied->method_exists( "bof_admin" ) ? !empty( $object_parsed->proxied->bof_admin()["object"] ) : false ){
        $columns = array_merge( $object_parsed->proxied->bof_admin()["object"], $columns );
      }
      foreach( $columns as $object_column_name => $object_column_args ){

        if ( $object_column_name != "seo_image" && !empty( $object_column_args["bofInput"] ) ? $object_column_args["bofInput"][0] == "file" : false ){

          if ( !empty( bof()->bofAdmin->_get_setting()["upload"]["groups"]["fs_{$object_column_args["bofInput"][1]["type"]}"]["inputs"] ) ){
            $_file_type_inputs = bof()->bofAdmin->_get_setting()["upload"]["groups"]["fs_{$object_column_args["bofInput"][1]["type"]}"]["inputs"];
            foreach( $_file_type_inputs as $_file_type_input ){

              $_file_type_input_type = substr( $_file_type_input["input"]["name"], strlen( "fs_{$object_column_args["bofInput"][1]["type"]}_" ) );
              if ( $_file_type_input_type == "preview_no_ff" ) continue;

              // if ( $object_column_args["bofInput"][1]["type"] == "image" ) continue;

              $_file_type_input["input"]["name"] = "fs__{$object_column_args["bofInput"][1]["type"]}_{$object_column_args["bofInput"][1]["object_type"]}_{$_file_type_input_type}";
              $_file_type_input["col_name"] = "fs__{$object_column_args["bofInput"][1]["type"]}_{$object_column_args["bofInput"][1]["object_type"]}_{$_file_type_input_type}";

              if ( $_file_type_input["input"]["type"] == "digit" || $_file_type_input["input"]["type"] == "text" ){
                $_file_type_input["validator"][1][] = "empty()";
              }
              elseif ( $_file_type_input["input"]["type"] == "select_m" || $_file_type_input["input"]["type"] == "select" || $_file_type_input["input"]["type"] == "select_i" ){
                $_file_type_input["input"]["options"] = array_merge( [ [ "__", "Default" ] ], $_file_type_input["input"]["options"] );
                $_file_type_input["input"]["value"] = "__";
                $_file_type_input["validator"][1]["values"][] = "__";
              }
              elseif ( $_file_type_input["input"]["type"] == "checkbox" ){

                $_file_type_input["input"] = array(
                  "name" => $_file_type_input["input"]["name"],
                  "type" => "select_i",
                  "options" => array(
                    [ "__", "Default" ],
                    [ "-1", "No" ],
                    [ "1", "Yes" ]
                  ),
                  "value" => "__"
                );
                $_file_type_input["validator"] = array(
                  "in_array",
                  array(
                    "values" => [ "__", "-1", "1", -1, 1 ],
                    "empty()"
                  ),
                );

              }

              if ( !empty( $_file_type_input["tip"] ) )
              $_file_type_input["tip"] = str_replace( "{$object_column_args["bofInput"][1]["type"]}s", $object_column_args["label"], $_file_type_input["tip"] ) ;

              $rules_groups[ $object_name . "_" . $object_column_args["bofInput"][1]["type"] . "_" . $object_column_args["bofInput"][1]["object_type"] ]["inputs"][ "fs__{$object_column_args["bofInput"][1]["type"]}_{$object_column_args["bofInput"][1]["object_type"]}_{$_file_type_input_type}" ] = $_file_type_input;
              $rules_groups[ $object_name . "_" . $object_column_args["bofInput"][1]["type"] . "_" . $object_column_args["bofInput"][1]["object_type"] ]["title"] = $object_parsed->direct->bof()["label"] . " - " . $object_column_args["label"] . " - {$object_column_args["bofInput"][1]["type"]}";
              $rules_groups[ $object_name . "_" . $object_column_args["bofInput"][1]["type"] . "_" . $object_column_args["bofInput"][1]["object_type"] ]["icon"] = $object_parsed->direct->bof()["icon"];
            }
          }

        }

      }

    }

    $groups = array_merge( $groups, $rules_groups );

    return $groups;

  }

  public function clean_path( $path, $no_root=false ){

    $path = str_replace( "\\", "/", $path );
    //$path = substr( $path, 0, 1 ) == "/" ? substr( $path, 1 ) : $path;
    return $no_root ? str_replace( $this->clean_path( base_root ) . "/", "", $path ) : $path;

  }
  public function get_rules( $type, $name=null, $args=[] ){

    $get_host = false;
    extract( $args );

    if ( !in_array( $type, $this->_bof_this->columns()["type"]["validator"][1]["values"], true ) )
    fall( "Requesting file_rules for unkown type: {$type}" );

    $rules = [
      "validators" => [],
      "file_host" => 1
    ];

    if ( $type == "image" ){
      $validator_key_list = [ "size_min", "size_max", "dim_min", "dim_max", "fl", "resize", "webp" ];
    }
    elseif ( $type == "audio" ){
      $validator_key_list = [ "size_min", "size_max", "fl", "waveform", "protect", "preview", "br_min", "lower_64", "lower_128", "lower_192", "lower_256", "flac", "hls", "hls_kr" ];
    }
    elseif ( $type == "video" ){
      $validator_key_list = [ "size_min", "size_max", "fl", "width_min", "width_max", "lower_1080", "lower_720", "lower_480", "lower_240", "hls", "hls_kr" ];
    }
    elseif ( $type == "zip" ){
      $validator_key_list = [ "size_min", "size_max", "fl" ];
    }

    if ( $name ){

      foreach( $validator_key_list as $validator_key ){
        $_custom_validator = bof()->object->db_setting->get( "fs__{$type}_{$name}_{$validator_key}" );
        if ( $_custom_validator !== null && $_custom_validator !== "__" ){
          if ( $validator_key == "fl" ){
            $_custom_validator = explode( ",", $_custom_validator );
            if ( in_array( "__", $_custom_validator, true ) )
            $_custom_validator = null;
          }
          if ( $_custom_validator == "-1" ) $_custom_validator = 0;
          $rules[ "validators" ][ $validator_key ] = $_custom_validator;
        }
      }

      $hosts = bof()->object->db_setting->get( "fh_setting" );

      if ( !empty( $hosts[ "{$name}_{$type}" ] ) )
      $rules["file_host"] = $hosts[ "{$name}_{$type}" ];
      elseif ( !empty( $hosts[ "type_{$type}" ] ) )
      $rules["file_host"] = $hosts[ "type_{$type}" ];
      elseif ( !empty( $hosts[ "default" ] ) )
      $rules["file_host"] = $hosts[ "default" ];

      if ( $get_host )
      $rules["file_host_data"] = bof()->object->storage->select(["ID"=>$rules["file_host"]]);

    }

    foreach( $validator_key_list as $validator_key ){

      if ( empty( $rules[ "validators" ][ $validator_key ] ) ? ( isset( $rules[ "validators" ][ $validator_key ] ) ? $rules[ "validators" ][ $validator_key ] !== 0 && $rules[ "validators" ][ $validator_key ] !== "0" : true ) : false )
      $rules[ "validators" ][ $validator_key ] = bof()->object->db_setting->get( "fs_{$type}_{$validator_key}" );

      if ( $validator_key == "dim_min" || $validator_key == "dim_max" ){
        $rules[ "validators" ][ "{$validator_key}_parsed" ] = explode( "*", $rules[ "validators" ][ $validator_key ] );
      }

      if ( $validator_key == "fl" ){
        if ( $rules[ "validators" ][ $validator_key ] ? in_array( "jpg", $rules[ "validators" ][ $validator_key ], true ) : false )
        $rules[ "validators" ][ $validator_key ][] = "jpeg";
      }


    }

    return $rules;

  }
  public function add_rule( $hook, $type, $args ){

    if ( !in_array( $type, $this->_bof_this->columns()["type"]["validator"][1]["values"] ) )
    return false;

    $this->rules[ $hook ] = array(
      "hook" => $hook,
      "type" => $type,
      "args" => $args
    );

  }

  public function handle_url( $url, $args=[] ){

    $type = "image";
    $object_type = null;
    $__path = parse_url( $url, PHP_URL_PATH );
    $extension = pathinfo( $__path, PATHINFO_EXTENSION) ;
    $filename = urldecode( pathinfo( $__path, PATHINFO_FILENAME ) );
    $sub_directory = "url";
    $expected_header = 200;
    $tmp_file = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp_" . uniqid();
    extract( $args );

    if ( empty( $object_type ) )
    return [ 0, "no_object_type" ];

    /*
    try {
      $saved = bof()->curl->download( $url, array(
        "filename" => $filename,
        "extension"     => $extension,
        "sub_directory" => $sub_directory,
      ) );
    } catch( Exception $err ){
      return [ 0, $err->getMessage() ];
    }
    */

    // load url content
    $data = bof()->curl->exe( array(
      "url" => $url,
      "agent" => "chrome",
      "cache" => false,
      "cache_save" => false,
      "cache_load" => false,
    ) );

    if ( !empty( $data["error"] ) )
    return [ 0, $data["error"] ];

    if ( $data["http_code"] != $expected_header )
    return [ 0, "invalid_header" ];

    file_put_contents( $tmp_file, $data["body"] );

    $saved = base_root . bof()->file->save( $tmp_file, array(
			"filename"      => $filename,
			"extension"     => $extension,
			"sub_directory" => $sub_directory,
		) );

    $rules = $this->_bof_this->get_rules( $type, $object_type );

    $valid = $this->validate_file( $type, $saved, $rules );
    if ( $valid !== true ){
      if ( is_file( $saved ) )
      unlink( $saved );
      return [ 0, "invalid_file" ];
    }

    $file_pass = substr( md5( uniqid() . time() ), 0, 10 );
    $file_id = $this->_bof_this->insert( array(
      "type" => $type,
      "pass" => $file_pass,
      "object_type" => $object_type,
      "host_id" => 1,
      "dest_host_id" => $rules["file_host"],
      "user_id" => 0,
      "path" => str_replace( base_root, "", $saved ),
    ) );

    return [ 1, array(
      "file_path" => $saved,
      "file_id" => $file_id,
      "file_pass" => $file_pass
    ) ];

  }
  public function handle_string( $string, $args=[] ){

    $type = "image";
    $object_type = false;
    $tmp_file = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp_" . uniqid();
    extract( $args );

    try {
      $i = imagecreatefromstring( $string );
      imagejpeg( $i, $tmp_file, 100 );
      imagedestroy( $i );
      $string_parsed = 1;
    } catch( exception $err ){
      $string_parsed = 0;
    }

    if ( !$string_parsed )
    return [ 0, "parse_failed" ];

    $saved = base_root . bof()->file->save( $tmp_file, array(
			"sub_directory" => "upload_img_tmp",
      "extension" => "jpg"
		) );

    $rules = $this->_bof_this->get_rules( $type, $object_type );

    $valid = $this->validate_file( $type, $saved, $rules );
    if ( $valid !== true ){
      if ( is_file( $saved ) )
      unlink( $saved );
      return [ 0, "invalid_file" ];
    }

    $file_pass = substr( md5( uniqid() . time() ), 0, 10 );
    $file_id = $this->_bof_this->insert( array(
      "type" => $type,
      "pass" => $file_pass,
      "object_type" => $object_type,
      "host_id" => 1,
      "dest_host_id" => $rules["file_host"],
      "user_id" => 0,
      "path" => str_replace( base_root, "", $saved ),
    ) );

    return [ 1, array(
      "file_path" => $saved,
      "file_id" => $file_id,
      "file_pass" => $file_pass
    ) ];

  }
  public function handle_upload(){

    $types = $this->_bof_this->columns()["type"]["validator"][1]["values"];
    $type = bof()->nest->user_input( "get", "type", "in_array", [ "values" => $types ] );
    if ( !$type ) $type = bof()->nest->user_input( "post", "type", "in_array", [ "values" => $types ] );
    $object_type = bof()->nest->user_input( "get", "object_type", "string", $this->_bof_this->columns()["object_type"]["validator"][1] );
    if ( !$object_type ) $object_type = bof()->nest->user_input( "post", "object_type", "string", $this->_bof_this->columns()["object_type"]["validator"][1] );

    if ( !$type || !$object_type )
    fall("Invalid upload");

    // setting
    $chunk = bof()->object->db_setting->get( "fs_chunk" );
    $chunk_size = bof()->object->db_setting->get( "fs_chunk_size" );
    $rules = $this->_bof_this->get_rules( $type, $object_type );

    // get file
    $file = $this->_bof_this->handle_dropzone_upload( array(
      "extensions" => $rules["validators"]["fl"],
      "min_size"   => $rules["validators"]["size_min"] ? ( $rules["validators"]["size_min"] * 1000 * 1000 ) : 0,
      "max_size"   => $rules["validators"]["size_max"] ? ( $rules["validators"]["size_max"] * 1000 * 1000 ) : 0,
      "accept"     => $chunk ? "both" : "uncut",
      "chunk_size" => $chunk_size * 1000 * 1000
    ) );

    if ( !$file[0] ){
      return array(
        false,
        bof()->getName() == "bof_client" ? bof()->object->language->turn( $file[1], !empty( $file[2] ) ? $file[2] : [], [ "lang" => "users" ] ) : $file[1]
      );
    }

    if ( $file[0] ? $file[1] == "uploaded_chunk" : false )
    return [ true, "ok", [] ];

    $file = base_root . $file[1];

    // Validate per type && rules
    $valid = $this->validate_file( $type, $file, $rules );
    if ( $valid !== true ){

      if ( is_file( $file ) )
      unlink( $file );

      return array(
        false,
        bof()->object->language->turn( $valid[1], !empty( $valid[2] ) ? $valid[2] : [], [ "lang" => bof()->getName() == "bof_client" ? "users" : bof()->object->language->get_default() ] )
      );

    }

    $file_pass = substr( md5( uniqid() . time() ), 0, 10 );
    $file_id = $this->_bof_this->insert( array(
      "type" => $type,
      "pass" => $file_pass,
      "object_type" => $object_type,
      "host_id" => 1,
      "dest_host_id" => $rules["file_host"],
      "user_id" => bof()->user->get()->logged ? bof()->user->get()->ID : 0,
      "path" => str_replace( base_root, "", $file ),
    ) );

    $file_data = array(
      "type" => $type,
      "file_id" => $file_id,
      "file_pass" => $file_pass,
      "file_preview" => $this->clean_path( str_replace( base_root, web_address, $file ) )
    );

    return [ true, "done", $file_data ];

  }
  public function handle_dropzone_upload( $args = [] ){

    $input_name = '$file';
    $accept     = null;
    $chunk_size = null;
    $min_size   = null;
    $max_size   = null;
    $extensions = null;
    extract( $args );
    $maxChunks = ceil( $max_size / $chunk_size );

    if ( !empty( $_FILES[ $input_name ]["error"] ) )
    return [ false, "Upload failed: error no {$_FILES[ $input_name ]["error"]}. Contact your host manager" ];

    $file = bof()->nest->user_input( "file", $input_name, "file", array(
      "acceptable_extensions" => $extensions,
      // "max_size" => $max_size,
      // "min_size" => $chunk_size
    ) );

    if ( !$file )
    return [ false, "invalid_file" ];

    // Detect upload type ( chunked or uncut? )
    $dzuuid = bof()->nest->user_input( "post", "dzuuid", "string", [ "strict" => true, "strict_regex" => "[a-z0-9\-]", "min_length" => 20, "max_length" => 60 ] );
    $dzchunkindex = bof()->nest->user_input( "post", "dzchunkindex", "int", [ "min" => 0 ] );
    $dztotalfilesize = bof()->nest->user_input( "post", "dztotalfilesize", "int", [ "min" => 100000 ] );
    $dzchunksize = bof()->nest->user_input( "post", "dzchunksize", "int", [ "min" => 1000 ] );
    $dztotalchunkcount = bof()->nest->user_input( "post", "dztotalchunkcount", "int", [ "max" => 3000 ] );
    $dzchunkbyteoffset = bof()->nest->user_input( "post", "dzchunkbyteoffset", "int", [ "min" => 0 ] );
    $chunk_uid = md5( $dzuuid . $file["name"] );

    $__all_chunk_param_exists = !is_null( $dzuuid ) && !is_null( $dzchunkindex ) && !is_null( $dztotalfilesize ) && !is_null( $dztotalchunkcount ) && !is_null( $dzchunksize ) && !is_null( $dzchunkbyteoffset );
    $upload_type = $__all_chunk_param_exists ? "chunked" : "uncut";

    // Accepted upload_type?
    if ( $accept == 'both' ? false : ( $accept != $upload_type ) )
    return [ false, "invalid_file" ];

    // Valid chunked file?
    if ( $upload_type == "chunked" ){

      if ( $dzchunksize != $chunk_size || $file['size'] > $chunk_size )
      return [ false, "invalid_file" ];

      if ( $dztotalfilesize > $max_size )
      return [ false, "invalid_file_big" ];

      if ( $dzchunkindex > $maxChunks || $dzchunkindex < 0 )
      return [ false, "invalid_file" ];

      if ( $dztotalchunkcount != ceil( $dztotalfilesize / $dzchunksize ) )
      return [ false, "invalid_file" ];

      if ( $file["type"] != 'application/octet-stream' )
      return [ false, "invalid_file" ];

    }
    // Valid uncut file?
    else {

      if ( $file["size"] > $max_size )
      return [ false, "invalid_file_big" ];

    }

    // Save chunked file
    if ( $upload_type == "chunked" ){

      $chunk_dir = bof()->file->mkdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/chunk_" . $chunk_uid  );
      if ( is_file( "{$chunk_dir}/{$dzchunkindex}.part" ) ) unlink( "{$chunk_dir}/{$dzchunkindex}.part" );
      move_uploaded_file( $file['tmp_name'], "{$chunk_dir}/{$dzchunkindex}.part" );

      // All chunks ready?
      $__all_chunk_ready = true;
      for( $i=0; $i<$dztotalchunkcount; $i++ ){
        if ( !is_file( "{$chunk_dir}/{$i}.part" ) ){
          $__all_chunk_ready = false;
        }
      }

      if ( $__all_chunk_ready ){

        $__chunks_combied = $chunk_dir . "/file.ready";

        if ( is_file( $__chunks_combied ) )
        unlink( $__chunks_combied );

        touch( $__chunks_combied );

        for( $i=0; $i<$dztotalchunkcount; $i++ ){
          file_put_contents( $__chunks_combied, file_get_contents( "{$chunk_dir}/{$i}.part" ), FILE_APPEND );
          unlink( "{$chunk_dir}/{$i}.part" );
        }

        $__se = $file["extension"];
        $__df = realpath( $__chunks_combied );

      }
      elseif ( $dzchunkindex == $maxChunks ){
        return [ false, "invalid_file" ];
      }
      else {
        return [ true, "uploaded_chunk" ];
      }
      unset( $__all_chunk_ready );

    }
    else {

      $__se = $file["extension"];
      $__df = $file['tmp_name'];

    }

    if ( !empty( $extensions ) ? !in_array( $__se, $extensions ) : false )
    [ false, "invalid_file" ];

    return [ true, bof()->file->save( $__df, array(
      "sub_directory" => "unused",
      "filename" => substr( $file["name"], 0, ( -1 * ( strlen( $file["extension"] ) + 1 ) ) ),
      "extension" => $__se,
      "overwrite" => false
    ) ) ];

  }
  public function validate_file( $type, $path, $rules ){

    if ( $type == "image" ){

      $dims = getimagesize( $path );
      if ( !$dims )
      fall( "Invalid image" );

      $width = $dims[0];
      $height = $dims[1];

      // Auto-resize if too big
      if ( !empty( $rules["validators"]["dim_max_parsed"][0] ) && !empty( $rules["validators"]["dim_max_parsed"][1] ) ){
        if ( $width > $rules["validators"]["dim_max_parsed"][0] || $height > $rules["validators"]["dim_max_parsed"][1] ){
          ini_set( "memory_limit", "512M" );
          try {
            bof()->image->set( $path )->resize([
              "max_width" => $rules["validators"]["dim_max_parsed"][0],
              "max_height" => $rules["validators"]["dim_max_parsed"][1]
            ])->save([ "path" => $path ]);
          } catch( Exception $e ){}
          bof()->image->unset();
        }
      }

      $parse_image = bof()->image->set( $path );

      if ( !$parse_image->source )
      return [ false, "invalid_image" ];

      $parse_image->secure()->unset();

      return true;

    }
    elseif ( $type == "audio" ) {

      $read_tags = bof()->id3->read_tags( $path );

      if ( !$read_tags ){
        // Debug: Log detailed error info
        error_log( "[DEBUG] ID3 read_tags failed for: " . $path );
        error_log( "[DEBUG] File exists: " . ( is_file( $path ) ? "yes" : "no" ) );
        error_log( "[DEBUG] File size: " . ( is_file( $path ) ? filesize( $path ) : "N/A" ) );
        if ( is_file( $path ) ){
          $getID3 = bof()->id3->core( "reader" );
          $data = $getID3->analyze( $path );
          error_log( "[DEBUG] ID3 analyze errors: " . json_encode( $data["error"] ?? "none" ) );
          error_log( "[DEBUG] ID3 audio data: " . ( empty( $data["audio"] ) ? "missing" : "present" ) );
          error_log( "[DEBUG] ID3 playtime: " . ( empty( $data["playtime_seconds"] ) ? "missing" : $data["playtime_seconds"] ) );
        }
        return [ false, "invalid_file" ];
      }

      if ( !in_array( $read_tags["format"], $rules["validators"]["fl"], true ) )
      return [ false, "invalid_format", [ "cur" => $read_tags["format"] ] ];

      if ( empty( $read_tags["bitrate"] ) ? true : $read_tags["bitrate"] < $rules["validators"]["br_min"] )
      return [ false, "audio_too_low", [ "cur" => $read_tags["bitrate"], "min" => $rules["validators"]["br_min"] ] ];

      return true;

    }
    elseif ( $type == "video" ) {

      $read_tags = bof()->id3->read_tags( $path, "video" );
      if ( !in_array( pathinfo( $path, PATHINFO_EXTENSION ), $rules["validators"]["fl"], true ) )
      return [ false, "invalid_format", [ "cur" => $read_tags["format"] ] ];

      if ( empty( $read_tags["width"] ) ? true : $read_tags["width"] < $rules["validators"]["width_min"] )
      return [ false, "vid_too_small", [ "cur" => !empty( $read_tags["width"] ) ? $read_tags["width"] : "?", "min" => $rules["validators"]["width_min"] ] ];

      if ( empty( $read_tags["width"] ) ? true : $read_tags["width"] > $rules["validators"]["width_max"] )
      return [ false, "vid_too_big", [ "cur" => !empty( $read_tags["width"] ) ? $read_tags["width"] : "?", "max" => $rules["validators"]["width_max"] ] ];

      return true;

    }
    elseif ( $type == "zip" ){

      return true;

    }

    return [ false, "invalid_file" ];

  }

  public function unlink( $ID, $item_hook ){

    $item = $this->select(array(
      "ID" => $ID
    ));

    if ( !$item )
    return false;

    if ( !$item_hook ){
      $item["used_in_parsed"] = [];
    }
    elseif ( in_array( $item_hook, $item["used_in_parsed"], true ) ){
      unset( $item["used_in_parsed"][ array_search( $item_hook, $item["used_in_parsed"] ) ] );
    }

    if ( empty( $item["used_in_parsed"] ) ){

      $item_storage = bof()->object->storage->select(array(
        "ID" => $item["host_id"]
      ));

      $remove = bof()->transit
      ->set_storage( $item_storage )
      ->set_file( $item )
      ->delete();

      if ( !empty( $item["data_decoded"]["_sisters"] ) ){
        foreach( $item["data_decoded"]["_sisters"] as $_sis ){
          $remove = bof()->transit
          ->set_file( $_sis["path"] )
          ->delete();
        }
      }

      if ( !empty( $item["data_decoded"]["_hls_slices"] ) ){
        foreach( $item["data_decoded"]["_hls_slices"] as $_slice ){
          $remove = bof()->transit
          ->set_file( $_slice )
          ->delete();
        }
      }

      if ( !empty( $item["data_decoded"]["hls_key"] ) ){
        $remove = bof()->transit
        ->set_file( $item["data_decoded"]["hls_key"] )
        ->delete();
      }

      $this->_bof_this->delete(array(
        "ID" => $ID
      ));

    } else {

      bof()->object->file->update(array(
        "ID" => $ID
      ),array(
        "used" => $item["used_in_parsed"] ? count( $item["used_in_parsed"] ) : 0,
        "used_in" => implode( ",", $item["used_in_parsed"] )
      ));

    }

    bof()->object->storage->count_files( $item["host_id"] );

    return true;

  }

  public function validate_pass( $id, $pass ){

    if ( ( $file = $this->_bof_this->select(["ID"=>$id]) ) ){
      if ( $file["pass"] == $pass )
      return true;
    }

    return false;
  }
  public function finalize_upload( $type, $object_type, $item_hook, $newID, $oldID=false, $args=[], $beArgs=[] ){

    $new = $this->select(array("ID"=>$newID),["cache_load_rt"=>false]);
    $old = $oldID ? $this->select(array("ID"=>$oldID),["cache_load_rt"=>false]) : false;

    if ( $new ? $new["type"] != $type || $new["object_type"] != $object_type : false )
    return false;

    if ( $new ? $new["time_moved"] : false ){
      if ( $type == "image" )
      $this->_image_cap( $type, $object_type, $item_hook, $args, $new, $beArgs );
      return array(
        "already_moved" => true
      );
    }

    // remove from old file
    if ( $old )
    $this->unlink( $old["ID"], $item_hook );

    if ( !$new )
    return false;

    // process new file
    $rules = $this->_bof_this->get_rules( $type, $object_type );
    $process_file = $this->_process_file( $new, $rules, $args );

    $data = $process_file["data"];
    $changes = $process_file["changes"];

    if ( !empty( $changes["hls"] ) )
    $args["force_no_protect"] = true;

    if (
      (
        ( !empty( $args["premium"] ) && !empty( $rules["validators"]["protect"] ) ) ||
        !empty( $args["protect"] )
      ) &&
      empty( $args["force_no_protect"] )
    )
    $changes["protected"] = true;

    // move files
    if ( !empty( $changes["path"] ) )
    $new["path"] = $changes["path"];
    if ( !empty( $changes["extension"] ) )
    $new["extension"] = $changes["extension"];
    if ( !empty( $changes["name"] ) )
    $new["name"] = $changes["name"];
    if ( !empty( $changes["mime_type"] ) )
    $new["mime_type"] = $changes["mime_type"];

    $move = $this->_move_file( $new, $data, $rules, $args );
    if ( !$move[0] ) return false;

    $used_in = $new["used_in_parsed"];
    $used_in[] = $item_hook;
    $used_in = array_values( array_unique( $used_in ) );

    $used_in_object = null;
    $used_in_f = $used_in[0];
    $used_in_f = $used_in_f ? preg_replace( '/[0-9]+/', '', $used_in_f ) : $used_in_f;
    if ( in_array( $used_in_f, array_keys( bof()->object->core_files->get_files( "object" ) ), true ) )
    $used_in_object = $used_in_f;

    // update path
    bof()->object->file->update(array(
      "ID" => $new["ID"]
    ),array(
      "used" => count( $used_in ),
      "used_in" => implode( ",", $used_in ),
      "used_in_object" => $used_in_object
    ));

    if ( !$new["time_moved"] ){
      $this->_bof_this->update(
        array(
          "ID" => $new["ID"]
        ),
        array(
          "path" => $move[1],
          "extension" => $new["extension"],
          "mime_type" => $new["mime_type"],
          "name" => $new["name"],
          "data" => json_encode( $data ),
          "size" => $data["total_size"],
          "host_id" => $new["dest_host_id"],
          "dest_host_id" => 0,
          "time_moved" => bof()->general->mysql_timestamp(),
        ),
        array(
          "cache_load_rt" => false
        )
      );
    }

    $affected_hosts = [];
    if ( !empty( $new["host_id"] ) ) $affected_hosts[] = $new["host_id"];
    if ( !empty( $new["dest_host_id"] ) ) $affected_hosts[] = $new["dest_host_id"];
    if ( !empty( $old["host_id"] ) ) $affected_hosts[] = $old["host_id"];
    if ( !empty( $old["dest_host_id"] ) ) $affected_hosts[] = $old["dest_host_id"];

    if ( $affected_hosts ){
      foreach( array_unique( $affected_hosts ) as $affected_host ){
        bof()->object->storage->count_files( $affected_host );
      }
    }

    if ( $type == "image" )
    $this->_image_cap( $type, $object_type, $item_hook, $args, $new, $beArgs );

    return $changes;

  }
  protected function _image_cap( $type, $object_type, $item_hook, $args, $new, $beArgs ){

    if ( empty( $type ) || empty( $object_type ) || empty( $item_hook ) || empty( $new ) || empty( $beArgs["key"] ) )
    return;

    $cap = bof()->nest->user_input( "post", "{$beArgs["key"]}_cap", "string" );
    if ( empty( $cap ) )
    return;

    bof()->object->file->update(
      array(
        "ID" => $new["ID"],
      ),
      array(
        "name" => $cap
      )
    );

  }
  public function _process_file( $object, $rules, $args ){

    $encrypt = null;
    $lower = null;
    $real = null;
    $convert = null;
    $preview = null;
    $new_name = null;
    $new_id3_tags = null;
    $premium = false;
    $protect = false;
    $force_no_protect = false;
    $force_on_done = false;
    extract( $args );

    if ( $new_name )
    $new_name = bof()->file->_filter_filename( $new_name );

    $data = [
      "total_size" => 0
    ];

    $changes = [
      "done" => true
    ];

    if ( is_file( base_root . "/" . $object["path"] ) )
    $data["total_size"] = filesize( base_root . "/" . $object["path"] );

    if (
      $object["type"] == "image" &&
      $object["host_id"] == 1 &&
      ( !$object["time_moved"] || $force_on_done )
    ){

      $image_path = base_root . "/" . $object["path"];
      $image_size = getimagesize( $image_path );
      $image_width  = $data["width"]  = $image_size[0];
      $image_height = $data["height"] = $image_size[1];
      $data["size"] = filesize( $image_path );
      $data["total_size"] = filesize( $image_path );
      $data["dominant_color"] = bof()->image->set( $image_path )->get_dominant_color();

      if ( $rules["validators"]["webp"] ){

        require_once( bof_root . "/app/core/third/spatie-image-optimizer/autoload.php" );
        $optimizerChain = \Spatie\ImageOptimizer\OptimizerChainFactory::create();

        $webp_image_name = "{$object["name"]}_webp.webp";
        $webp_image_path = $this->clean_path( pathinfo( $image_path, PATHINFO_DIRNAME ) . "/" . $webp_image_name );
        $optimizerChain->optimize( $image_path, $webp_image_path );

        if ( is_file( $webp_image_path ) ){
          $data["total_size"] += filesize( $webp_image_path );
          $data["_sisters"][ "webp" ] = array(
            "name" => $webp_image_name,
            "path" => $this->clean_path( $webp_image_path, true ),
            "size" => filesize( $webp_image_path ),
            "width" => $image_width,
            "height" => $image_height,
            "webp" => true
          );
        }

      }

      if ( $rules["validators"]["resize"] ){

        foreach( array(
          "large" => 1200,
          "medium" => 500,
          "small" => 300
        ) as $_size_name => $_size ){

          if ( $image_width > $_size || $image_height > $_size ){

            $resized_image_name = "{$object["name"]}_{$_size}.{$object["extension"]}";
            $resized_image_path = $this->clean_path( pathinfo( $image_path, PATHINFO_DIRNAME ) . "/" . $resized_image_name );

            $resize = bof()->image->set( $image_path )->resize( array(
              "max_width" => $_size,
              "max_height" => $_size
            ) )->save( array(
              "path" => $resized_image_path
            ) )->unset();

            if ( is_file( $resized_image_path ) ){

              $data["total_size"] += filesize( $resized_image_path );
              $data["_sisters"][ $_size ] = array(
                "name" => $resized_image_name,
                "path" => $this->clean_path( $resized_image_path, true ),
                "size" => filesize( $resized_image_path ),
                "width" => getimagesize( $resized_image_path )[0],
                "height" => getimagesize( $resized_image_path )[1]
              );

              if ( $rules["validators"]["webp"] ){

                $resized_image_webp_name = "{$object["name"]}_{$_size}_webp.webp";
                $resized_image_webp_path = $this->clean_path( pathinfo( $image_path, PATHINFO_DIRNAME ) . "/" . $resized_image_webp_name );
                $optimizerChain->optimize( $resized_image_path, $resized_image_webp_path );

                if ( is_file( $resized_image_webp_path ) ){
                  $data["total_size"] += filesize( $resized_image_webp_path );
                  $data["_sisters"][ $_size . "_webp" ] = array(
                    "name" => $resized_image_webp_name,
                    "path" => $this->clean_path( $resized_image_webp_path, true ),
                    "size" => filesize( $resized_image_webp_path ),
                    "width" => getimagesize( $resized_image_path )[0],
                    "height" => getimagesize( $resized_image_path )[1],
                    "webp" => true
                  );
                }

              }

            }

          }

        }

        if ( $image_width > 100 && $image_height > 100 ){

          $thumb_image_name = "{$object["name"]}_thumb.{$object["extension"]}";
          $thumb_image_path = $this->clean_path( pathinfo( $image_path, PATHINFO_DIRNAME ) . "/" . $thumb_image_name );

          $thumb = bof()->image->set( $image_path )->resize( array(
            "abs_width" => 100,
            "abs_height" => 100
          ) )->save( array(
            "path" => $thumb_image_path
          ) )->unset();

          if ( is_file( $thumb_image_path ) ){

            $data["total_size"] += filesize( $thumb_image_path );
            $data["_sisters"][ "thumb" ] = array(
              "name" => $thumb_image_name,
              "path" => $this->clean_path( $thumb_image_path, true ),
              "size" => filesize( $thumb_image_path ),
              "width" => getimagesize( $thumb_image_path )[0],
              "height" => getimagesize( $thumb_image_path )[1]
            );

            if ( $rules["validators"]["webp"] ){

              $thumb_image_webp_name = "{$object["name"]}_thumb_webp.webp";
              $thumb_image_webp_path = $this->clean_path( pathinfo( $image_path, PATHINFO_DIRNAME ) . "/" . $thumb_image_webp_name );
              $optimizerChain->optimize( $thumb_image_path, $thumb_image_webp_path );

              if ( is_file( $thumb_image_webp_path ) ){
                $data["total_size"] += filesize( $thumb_image_webp_path );
                $data["_sisters"][ "thumb_webp" ] = array(
                  "name" => $thumb_image_webp_name,
                  "path" => $this->clean_path( $thumb_image_webp_path, true ),
                  "size" => filesize( $thumb_image_webp_path ),
                  "width" => getimagesize( $thumb_image_path )[0],
                  "height" => getimagesize( $thumb_image_path )[1],
                  "webp" => true
                );
              }

            }

          }

        }

      }

    }

    elseif (
      $object["type"] == "audio" &&
      $object["host_id"] == 1 &&
      !$object["time_moved"]
    ){

      $audio_path = ( base_root . "/" . $object["path"] );

      if ( !is_file( $audio_path ) )
      throw new Exception("File doesnt exists? {$audio_path}");

      $audio_name = $new_name ? $new_name : pathinfo( $audio_path, PATHINFO_FILENAME );
      $audio_dir  = pathinfo( $audio_path, PATHINFO_DIRNAME );

      $data["size"] = filesize( $audio_path );
      $data["total_size"] = filesize( $audio_path );
      $data["tags"] = bof()->id3->read_tags( $audio_path );
      $data["bitrate"] = $data["tags"]["bitrate"];
      $changes["bitrate"] = $data["tags"]["bitrate"];
      $changes["duration"] = $data["tags"]["duration"];
      $changes["lower_qualities"] = [];
      unset( $data["tags"] );

      // Convert non-mp3 to mp3
      if ( $object["extension"] !== "mp3" && ( $convert === null || $convert === true ) ){

        if ( $rules["validators"][ "flac" ] && ( $real === null || $real === true ) && $object["extension"] === "flac" ){

          $real_file_ID = $this->_bof_this->insert(
            array(
              "type" => $object["type"],
              "host_id" => $object["host_id"],
              "dest_host_id" => $object["dest_host_id"],
              "user_id" => $object["user_id"],
              "path" => $this->_bof_this->clean_path( $audio_path, true ),
              "object_type" => $object["object_type"],
            )
          );

          $changes["real_file"] = array(
            "ID" => $real_file_ID,
          );

          $keep_real = true;

        }

        $convert = $this->_convert_file( "audio", $audio_path, $object, array(
          "name" => $audio_name,
          "dir"  => $audio_dir
        ) );

        if ( $convert === false )
        fall( "Converting failed - to mp3" . " -> " . bof()->ffmpeg->getError() );

        if ( empty( $keep_real ) )
        unlink( $audio_path );

        $audio_path = $convert;

        $data["size"] = filesize( $audio_path );
        $data["total_size"] = filesize( $audio_path );
        $data["tags"] = bof()->id3->read_tags( $audio_path );
        $changes["path"] = bof()->object->file->clean_path( $audio_path, true );
        $changes["extension"] = "mp3";
        $changes["mime_type"] = "audio/mpeg";
        $changes["bitrate"] = $data["bitrate"] = $data["tags"]["bitrate"];
        $changes["duration"] = $data["duration"] = $data["tags"]["duration"];
        unset( $data["tags"] );

      }

      // Loudness measurement (EBU R128) for volume normalization
      $_loudness = bof()->ffmpeg->measure_loudness( $audio_path );
      if ( $_loudness ){
        $data["lufs"] = $_loudness["lufs"];
        $data["peak_db"] = $_loudness["peak_db"];
      }

      // Tags
      if ( pathinfo( $audio_path, PATHINFO_EXTENSION ) == "mp3" && $new_id3_tags ){
        bof()->id3->write_tags( $audio_path, $new_id3_tags );
      }

      // Make preview for protected files
      if ( $premium && !empty( $rules["validators"]["preview"] ) && ( $preview === true || $preview === null ) ){

        if ( empty( $data["duration"] ) ){
          $duration = bof()->id3->read_tags( $audio_path )["duration"];
        } else {
          $duration = $data["duration"];
        }
        $cut = ceil( $duration * .2 );
        $cut = $cut > 60 ? 60 : $cut;

        $convert = $this->_convert_to_lower( "audio", $audio_path, $object, array(
          "name" => $audio_name . "_preview",
          "dir"  => $audio_dir,
          "ab"   => "128k",
          "cut"  => $cut
        ) );

        if ( $convert === false )
        fall( "Converting failed - creating preview" . " -> ". bof()->ffmpeg->getCommand() . " - " . bof()->ffmpeg->getError() );

        $preview_id = $this->_bof_this->insert(
          array(
            "type" => $object["type"],
            "host_id" => $object["host_id"],
            "dest_host_id" => $object["dest_host_id"],
            "user_id" => $object["user_id"],
            "path" => $this->_bof_this->clean_path( $convert, true ),
            "object_type" => $object["object_type"],
          )
        );

        $changes["preview_file"] = array(
          "quality" => 2,
          "ID" => $preview_id,
        );

      }

      // Make lower quality(s)
      foreach ( array(
        [ 64, 1],
        [ 128, 2],
        [ 192, 3],
        [ 256, 4 ]
      ) as $_quality ){

        list( $bitrate, $quality_int ) = $_quality;
        if ( $rules["validators"]["lower_{$bitrate}"] && !empty( $data["bitrate"] ) && ( $lower === null || $lower === true ) ? $data["bitrate"] > $bitrate : false ){

          $convert = $this->_convert_to_lower( "audio", $audio_path, $object, array(
            "name" => $audio_name . "_" . $bitrate,
            "dir"  => $audio_dir,
            "ab"   => $bitrate . "k"
          ) );

          if ( $convert === false )
          fall( "Converting failed - mp3 to lower" . " -> " . bof()->ffmpeg->getError() );

          $lower_quality_ID = $this->_bof_this->insert(
            array(
              "type" => $object["type"],
              "host_id" => $object["host_id"],
              "dest_host_id" => $object["dest_host_id"],
              "user_id" => $object["user_id"],
              "path" => $this->_bof_this->clean_path( $convert, true ),
              "object_type" => $object["object_type"],
            )
          );

          $changes["lower_qualities"][] = array(
            "quality" => $quality_int,
            "ID" => $lower_quality_ID,
          );

        }

      }

      // Encrypt using HLS
      if ( $rules["validators"][ "hls" ] && ( $encrypt === null || $encrypt === true ) ){

        $encrypt = $this->_encrypt_file( $audio_path, "audio", $rules["validators"]["hls_kr"] );

        if ( $encrypt === false )
        fall( "HLS Encryption failed" . " -> " . bof()->ffmpeg->getError() );

        if ( !empty( $encrypt["real_file"] ) && ( $real === null || $real === true ) && ( $rules["validators"]["flac"] ? $object["extension"] !== "flac" : true ) ){

          $real_file_ID = $this->_bof_this->insert(
            array(
              "type" => $object["type"],
              "host_id" => $object["host_id"],
              "dest_host_id" => $object["dest_host_id"],
              "user_id" => $object["user_id"],
              "path" => $this->_bof_this->clean_path( $encrypt["real_file"], true ),
              "object_type" => $object["object_type"],
            )
          );

          $changes["real_file"] = array(
            "ID" => $real_file_ID,
          );

        }

        $data["size"] = 0;
        $data["total_size"] = $encrypt["slices_size"];
        $data["_hls_slices"] = $encrypt["slices"];
        $data["hls_address"] = $encrypt["map"];
        $data["hls_key"] = $encrypt["key"];

        $changes["download_able"] = -1;
        $changes["hls"] = true;
        $changes["path"] = $encrypt["map"];
        $changes["name"] = "map";
        $changes["extension"] = "m3u8";
        $changes["mime_type"] = "application/x-mpegURL";

      }

    }

    elseif (
      $object["type"] == "video" &&
      $object["host_id"] == 1 &&
      !$object["time_moved"]
    ){

      $video_path = base_root . "/" . $object["path"];
      $video_name = $new_name ? $new_name : pathinfo( $video_path, PATHINFO_FILENAME );
      $video_dir  = pathinfo( $video_path, PATHINFO_DIRNAME );

      $data["size"] = filesize( $video_path );
      $data["total_size"] = filesize( $video_path );
      $data["tags"] = bof()->id3->read_tags( $video_path, "video" );

      $changes["duration"] = $data["tags"]["duration"];
      $changes["v_quality"] = $data["tags"]["v_quality"];
      $changes["lower_qualities"] = [];
      unset( $data["tags"] );

      // Convert non-mp4 to mp4
      if ( $object["extension"] !== "mp4" && ( $convert === null || $convert === true ) ){

        $convert = $this->_convert_file( "video", $video_path, $object, array(
          "name" => $video_name,
          "dir"  => $video_dir,
        ) );

        if ( $convert === false )
        fall( "Converting failed - to mp4" . " -> " . bof()->ffmpeg->getError() );

        if ( empty( $keep_real ) )
        unlink( $video_path );

        $video_path = $convert;

        $data["size"] = filesize( $video_path );
        $data["total_size"] = filesize( $video_path );
        $data["tags"] = bof()->id3->read_tags( $video_path, "video" );
        $changes["path"] = bof()->object->file->clean_path( $video_path, true );
        $changes["extension"] = "mp4";
        $changes["mime_type"] = "video/mp4";
        $changes["duration"] = $data["duration"] = $data["tags"]["duration"];
        $data["width"] = $data["tags"]["width"];

        unset( $data["tags"] );

      }

      // Tags
      if ( pathinfo( $video_path, PATHINFO_EXTENSION ) == "mp4" && $new_id3_tags ){
        // bof()->id3->write_tags( $video_path, [] );
      }

      // Make lower quality(s)
      foreach ( array(
        [ 352, 240, 1],
        [ 640, 480, 2],
        [ 1280, 720, 3],
        [ 1920, 1080, 4 ]
      ) as $_quality ){

        list( $_q_aw, $_q_w, $_q_q ) = $_quality;

        if ( $rules["validators"]["lower_{$_q_w}"] && !empty( $data["width"] ) && ( $lower === null || $lower === true ) ? $data["width"] > $_q_aw : false ){

          $convert = $this->_convert_to_lower( "video", $video_path, $object, array(
            "name" => $video_name . "_{$_q_w}p",
            "dir"  => $video_dir,
            "scale" => round( $_q_aw * 1.06 ),
            "scale_format" => null,
            "cv" => null,
            "ca" => null,
            "ba" => null,
          ) );

          if ( $convert === false )
          fall( "Converting failed - mp4 to lower" . " -> " . bof()->ffmpeg->getError() );

          $lower_quality_ID = $this->_bof_this->insert(
            array(
              "type" => $object["type"],
              "host_id" => $object["host_id"],
              "dest_host_id" => $object["dest_host_id"],
              "user_id" => $object["user_id"],
              "path" => $this->_bof_this->clean_path( $convert, true ),
              "object_type" => $object["object_type"],
            )
          );

          $changes["lower_qualities"][] = array(
            "quality" => $_q_q,
            "ID" => $lower_quality_ID,
          );

        }

      }

      // Encrypt using HLS
      if ( $rules["validators"]["hls"] && ( $encrypt === null || $encrypt === true ) ){

        $encrypt = $this->_encrypt_file( $video_path, "video", $rules["validators"]["hls_kr"] );

        if ( $encrypt === false )
        fall( "HLS Encryption failed" . " -> " . bof()->ffmpeg->getError() );

        if ( !empty( $encrypt["real_file"] ) && ( $real === null || $real === true ) ){

          $real_file_ID = $this->_bof_this->insert(
            array(
              "type" => $object["type"],
              "host_id" => $object["host_id"],
              "dest_host_id" => $object["dest_host_id"],
              "user_id" => $object["user_id"],
              "path" => $this->_bof_this->clean_path( $encrypt["real_file"], true ),
              "object_type" => $object["object_type"],
            )
          );

          $changes["real_file"] = array(
            "ID" => $real_file_ID,
          );

        }

        $data["size"] = 0;
        $data["total_size"] = $encrypt["slices_size"];
        $data["_hls_slices"] = $encrypt["slices"];
        $data["hls_address"] = $encrypt["map"];
        $data["hls_key"] = $encrypt["key"];

        $changes["download_able"] = -1;
        $changes["hls"] = true;
        $changes["path"] = $encrypt["map"];
        $changes["name"] = "map";
        $changes["extension"] = "m3u8";
        $changes["mime_type"] = "application/x-mpegURL";

      }

    }

    return array(
      "data" => $data,
      "changes" => $changes
    );

  }
  protected function _move_file( &$object, &$data, $rules, $args ){

    $new_name = false;
    $premium = false;
    $protect = false;
    $force_localhost = false;
    $force_no_protect = false;
    extract( $args );

    $dirname = $object["object_type"];

    if ( $new_name )
    $new_name = bof()->file->_filter_filename( $new_name );

    if ( $object["time_moved"] )
    return [ true, $object["path"] ];

    if ( !$protect && $premium && !empty( $rules["validators"]["protect"] ) )
    $protect = true;

    if ( $force_no_protect )
    $protect = false;

    if ( $protect ){
      $object["dest_host_id"] = 1;
      $dirname = "protected/{$dirname}";
    }

    if ( $force_localhost ){
      $object["dest_host_id"] = 1;
    }

    $dest_host = bof()->object->storage->select(array(
      "ID" => $object["dest_host_id"]
    ));

    $id = uniqid();

    $move = bof()->transit
    ->set_storage( $dest_host )
    ->set_file( $object )
    ->move(array(
      "dirname" => $dirname,
      "subdir" => $id,
      "filename" => $new_name ? $new_name : $object["name"],
      "extension" => $object["extension"]
    ));

    if ( !$move[0] )
    return $move;

    if ( !empty( $data["_sisters"] ) ){
      foreach( $data["_sisters"] as &$_sister ){

        $move_sis = bof()->transit
        ->set_file( $_sister["path"] )
        ->move(array(
          "dirname" => $dirname,
          "subdir" => $id,
          "filename" => uniqid(),
          "extension" => !empty( $_sister["webp"] ) ? "webp" : $object["extension"]
        ));

        if ( !$move_sis[0] )
        return $move_sis;

        $_sister["path"] = $move_sis[1];
        $_sister["name"] = pathinfo( $_sister["path"], PATHINFO_FILENAME );

      }
    }

    if ( !empty( $data["_hls_slices"] ) ){
      foreach( $data["_hls_slices"] as $i => $_slice ){

        $move_slice = bof()->transit
        ->set_file( bof()->object->file->clean_path( $_slice, true ) )
        ->move(array(
          "dirname" => $object["object_type"],
          "subdir" => $id,
          "filename" => pathinfo( $_slice, PATHINFO_FILENAME ),
          "extension" => pathinfo( $_slice, PATHINFO_EXTENSION )
        ));

        if ( !$move_slice[0] )
        return $move_slice;

        $data["_hls_slices"][$i] = $move_slice[1];

      }
    }

    return $move;

  }
  protected function _encrypt_file( $path, $type="audio", $keep_real=false ){

    $key_name = md5( $path );
    $key_dir = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/hls_keys";
    $key_path = $key_dir . "/{$key_name}.key";
    $keyinfo_path = $key_dir . "/{$key_name}.keyinfo";

    if ( !is_file( $key_path ) || !is_file( $keyinfo_path ) ){

      if ( !is_dir( $key_dir ) )
      mkdir( $key_dir, true );

      $iv = bin2hex(openssl_random_pseudo_bytes(16));
      file_put_contents( $key_path, random_bytes(16) );
      file_put_contents( $keyinfo_path, web_address . bof()->object->core_setting->get( "file_save_base_directory" ) . "/hls_keys/{$key_name}.key" . PHP_EOL . realpath( $key_path ) . PHP_EOL . $iv );

    }

    $encrypt = bof()->ffmpeg->hls_encrypt( $path, array(
      "keyinfo_path" => $keyinfo_path,
      "destination" => pathinfo( $path, PATHINFO_DIRNAME ),
      "filename" => pathinfo( $path, PATHINFO_FILENAME ),
      "keep_real_file" => $keep_real,
      "type" => $type
    ) );

    if ( $encrypt === false )
    return false;

    return array_merge(
      $encrypt,
      array(
        "key" => bof()->object->core_setting->get( "file_save_base_directory" ) . "/hls_keys/{$key_name}.key"
      )
    );

  }
  protected function _convert_file( $type, $path, $object, $args=[] ){

    if ( $type == "audio" )
    $convert = bof()->ffmpeg->convert_to_mp3( $path, $object, $args );
    else
    $convert = bof()->ffmpeg->convert_to_mp4( $path, $object, $args );

    return $convert;

  }
  protected function _convert_to_lower( $type, $path, $object, $args=[] ){

    if ( $type == "audio" )
    $convert = bof()->ffmpeg->convert_to_mp3( $path, $object, $args );
    else
    $convert = bof()->ffmpeg->convert_to_mp4( $path, $object, $args );

    return $convert;

  }

}

?>
