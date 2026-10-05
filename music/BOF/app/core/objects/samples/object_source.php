<?php

if ( !defined( "bof_root" ) ) die;

class object_source extends bof_type_object_sample {

  // BusyOwlFramework handshake
  public function bof( $caller=null, $result=[] ){

    return array_merge( array(
      "icon" => "save",
    ), $result );

  }
  public function selectors( $caller, $result=[] ){

    $default = array(
      "type"  => [ "type", "=" ],
    );

    return array_merge( $default, $result );

  }
  
  public function columns( $caller, $result=[] ){

    $all_types = bof()->source->get_types();

    $supported_types = $caller->child()->types;
    if ( $supported_types === "all" )
    $supported_types = array_keys( $all_types );

    foreach( $supported_types as $type )
    $supported_types_options[] = [ $type, $all_types[ $type ]["name"] ];

    return array_merge( array(

      "type" => array(
        "label" => "Type",
        "validator" => array(
          "in_array",
          array(
            "values" => $supported_types
          )
        ),
        "input" => array(
          "name" => "type",
          "type" => "select_i",
          "options" => $supported_types_options
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "type" => array(
              "title" => "Type",
              "input" => array(
                "type" => "select_i",
                "options" => array_merge(
                  array(
                    [ "__all__", "All" ],
                  ),
                  $supported_types_options
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $supported_types
                )
              )
            )
          )
        )
      ),
      "download_able" => array(
        "label" => "Download able",
        "tip" => "HLS encrypted files CAN'T be played outside your app<br><br><b>Default</b>: Default setting for sources will be used<br><b>No</b>: This source can't be downloaded<br><b>In-App only</b>: This file can only be downloaded within app for offline use<br><b>Yes</b>: This source can be downloaded to users' hard drive",
        "input" => array(
          "name" => "download_able",
          "type" => "select_i",
          "value" => 0,
          "options" => array(
            [ "0", "Default" ],
            [ "-2", "No" ],
            [ "-1", "In-App only" ],
            [ "1", "Yes" ]
          )
        ),
        "validator" => array(
          "in_array",
          array(
            "empty()",
            "values" => [ "0", "1", "-1", "-2", 0, 1, -1, -2 ]
          ),
        ),
        "selectors" => array(
          "download_able" => [ "download_able", "=" ],
        ),
        "bofAdmin" => array(
        )
      ),
      "stream_able" => array(
        "label" => "Stream able",
        "tip" => "FLAC files CAN'T be played within app<br><br><b>Default</b>: Default setting for sources will be used<br><b>No</b>: This source can't be played within app<br><b>Yes</b>: This source can be played within app",
        "input" => array(
          "name" => "stream_able",
          "type" => "select_i",
          "value" => 0,
          "options" => array(
            [ "0", "Default" ],
            [ "-1", "No" ],
            [ "1", "Yes" ]
          )
        ),
        "validator" => array(
          "in_array",
          array(
            "empty()",
            "values" => [ "0", "1", "-1", 0, 1, -1 ]
          ),
        ),
        "selectors" => array(
          "stream_able" => [ "stream_able", "=" ],
        ),
        "bofAdmin" => array(
        )
      ),
      "encrypted" => array(
        "label" => "Encrypted",
        "input" => array(
          "name" => "encrypted",
          "type" => "checkbox",
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "selectors" => array(
          "encrypted" => [ "encrypted", "=" ],
        ),
        "bofAdmin" => array(
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
      "quality" => array(
        "label" => "Quality",
        "tip" => "Will be auto-detected for 'local files' if left empty",
        "validator" => array(
          "in_array",
          array(
            "empty()",
            "values" => [ 1, 2, 3, 4, 5, "1", "2", "3", "4", "5" ]
          ),
        ),
        "input" => array(
          "type" => "select_i",
          "options" => array(
            [ 1, "64k - 240p" ],
            [ 2, "128k - 480p" ],
            [ 3, "192k - 720p" ],
            [ 4, "256k - 1080p" ],
            [ 5, "320k - 4K" ],
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "display_on" => array(
              "type" => [ "in_array", [ "audio", "video" ] ],
            )
          ),
        )
      ),
      "duration" => array(
        "label" => "Duration",
        "tip" => "In seconds. Will be auto-detected for 'local files' if left empty",
        "validator" => array(
          "float",
          array(
            "empty()",
            "forceNull" => true
          ),
        ),
        "input" => array(
          "type" => "digit"
        ),
        "bofAdmin" => array(
          "object" => array()
        )
      ),
      "title" => array(
        "public" => true,
        "label" => "Title",
        "tip" => "Can be left empty",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "type" => "text",
            "required" => false,
          )
        ),
      ),
      "force_free" => array(
        "label" => "Force free",
        "tip" => "This source can be accessed for free, even if it belongs to a premium item",
        "accept_zero" => true,
        "input" => array(
          "name" => "force_free",
          "type" => "checkbox",
          "value" => 0,
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "selectors" => array(
          "force_free" => [ "force_free", "=" ],
        ),
        "bofAdmin" => array(
          "object" => array(
          )
        )
      ),
      "protected" => array(
        "label" => "Protected",
        "input" => array(
          "name" => "protected",
          "type" => "checkbox",
          "value" => 0,
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "selectors" => array(
          "protected" => [ "protected", "=" ],
        ),
      ),
      "queue" => array(
        "label" => "Waiting for process",
        "input" => array(
          "name" => "queue",
          "type" => "checkbox",
          "value" => 0,
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "selectors" => array(
          "queue" => [ "queue", "=" ],
        ),
      ),
      "queue_old" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
          ),
        ),
      ),

    ), $result );

  }
  public function bof_columns( $caller, $args=[] ){

    return array(
      "ID",
      "hash",
      "time_add",
    );

  }
  public function bof_admin( $caller ){

    $extraInputs = [];
    $all_types = bof()->source->get_types();

    $supported_types = $caller->child()->types;
    if ( $supported_types === "all" )
    $supported_types = array_keys( $all_types );

    foreach( $supported_types as $type ){
      if ( !empty( $all_types[ $type ]["inputs"] ) ){
        $extraInputs = array_merge( $extraInputs, $all_types[ $type ]["inputs"] );
      }
    }

    $setting = array(
      "config" => array(
        "search" => !empty( $caller->child()->searchable ),
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => $caller->child()->bof_admin_edit_url,
        "list_page_url" => $caller->child()->bof_admin_list_url,
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "object" => array_merge(
        array(
          "target_id" => null,
          "type" => null,
          "file_type" => array(
            "label" => "File type",
            "tip" => "Upload an item from your hard drive or paste web-address of an online item",
            "multi" => false,
            "input" => array(
              "name" => "file_type",
              "type" => "select_i",
              "options" => array(
                [ "remote", "Remote" ],
                [ "local", "Local" ]
              )
            ),
            "validator" => array(
              "in_array",
              array(
                "values" => [ "remote", "local" ],
                "empty()"
              )
            ),
            "display_on" => array(
              "type" => [ "in_array", [ "audio", "video" ] ],
            )
          ),
          "remote_address" => array(
            "label" => "Remote file address",
            "tip" => "Enter a public web-address for file",
            "multi" => false,
            "input" => array(
              "name" => "remote_address",
              "type" => "text",
            ),
            "validator" => array(
              "url",
              array(
                "empty()",
                "accept_port" => true,
                "accept_auth" => true
              )
            ),
            "display_on" => array(
              "file_type" => [ "equal", "remote" ],
              "type" => [ "in_array", [ "audio", "video" ] ],
            )
          ),
          "_source_audio" => array(
            "label" => "Local file",
            "multi" => false,
            "bofInput" => array(
              "file",
              array(
                "type" => "audio",
                "object_type" => $caller->bof()["name"],
              )
            ),
            "display_on" => array(
              "file_type" => [ "equal", "local" ],
              "type" => [ "equal", "audio" ],
            )
          ),
          "_source_video" => array(
            "label" => "Local file",
            "multi" => false,
            "bofInput" => array(
              "file",
              array(
                "type" => "video",
                "object_type" => $caller->bof()["name"],
              )
            ),
            "display_on" => array(
              "file_type" => [ "equal", "local" ],
              "type" => [ "equal", "video" ],
            )
          ),
          "youtube_id" => array(
            "label" => "Youtube ID",
            "multi" => false,
            "input" => array(
              "name" => "youtube_id",
              "type" => "text",
            ),
            "validator" => array(
              "string",
              array(
                "empty()"
              )
            ),
            "display_on" => array(
              "type" => [ "equal", "youtube" ]
            )
          ),
          "vimeo_id" => array(
            "label" => "Vimeo ID",
            "multi" => false,
            "input" => array(
              "name" => "vimeo_id",
              "type" => "text",
            ),
            "validator" => array(
              "string",
              array(
                "empty()"
              )
            ),
            "display_on" => array(
              "type" => [ "equal", "vimeo" ]
            )
          ),
          "soundcloud_id" => array(
            "label" => "Soundcloud ID",
            "multi" => false,
            "input" => array(
              "name" => "soundcloud_id",
              "type" => "text",
            ),
            "validator" => array(
              "string",
              array(
                "empty()"
              )
            ),
            "display_on" => array(
              "type" => [ "equal", "soundcloud" ]
            )
          ),
        ),
        $extraInputs
      ),
      "list" => array(
        "target_id" => null,
        "stats" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Stats",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            if ( $item["type"] == "audio" || $item["type"] == "video" ){
              $displayData["data"] .= "<li><b>Download able</b>" . ( $item["download_able"] == -2 ? "No" : ( $item["download_able"] == -1 ? "Only in-app" : "Yes" ) ) . "</li>";
              $displayData["data"] .= "<li><b>Stream able</b>" . ( $item["stream_able"] == -1 ? "No" : "Yes" ) . "</li>";
              $displayData["data"] .= "<li><b>Type</b>" . ( $item["data_decoded"]["file_type"] ) . "</li>";
              $displayData["data"] .= "<li><b>Quality</b>" . ( bof()->source->hr_quality( $item["type"], $item["quality"] ) ) . "</li>";
              if ( $item["data_decoded"]["file_type"] == "local" && !empty( $item["data_decoded"]["local_file"] ) ){
                $local_file = bof()->object->file->select(["ID"=>$item["data_decoded"]["local_file"]]);
                if ( $local_file )
                $displayData["data"] .= "<li><b>Extension</b>" . ( !empty( $item["encrypted"] ) ? "Encrypted - " : "" ) . ( $local_file["extension"] ) . "</li>";
              }
            }
            elseif( $item["type"] == "youtube" ? !empty( $item["data_decoded"]["youtube_id"] ) : false ){
              $displayData["data"] .= "<li><b>ID</b>" . ( $item["data_decoded"]["youtube_id"] ) . "</li>";
            }
            elseif( $item["type"] == "soundcloud" ? !empty( $item["data_decoded"]["soundcloud_id"] ) : false ){
              $displayData["data"] .= "<li><b>ID</b>" . ( $item["data_decoded"]["soundcloud_id"] ) . "</li>";
            }
            elseif ( !empty( $item["data_decoded"]["{$item["type"]}_id"] ) ){
              $displayData["data"] .= "<li><b>ID</b>" . ( $item["data_decoded"]["{$item["type"]}_id"] ) . "</li>";
            }
            if ( !empty( $item["force_free"] ) ){
              $displayData["data"] .= "<li><b>Free</b> Yes</li>";
            }
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "time_add" => array(
          "type" => "time"
        )
      ),
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $item_name == "target_id" && empty( $item_data["input"]["value"] ) && $request["type"] == "new" ){
          $givenID = bof()->nest->user_input( "get", "target_id", "int" );
          if ( $givenID ) $item_data["input"]["value"] = $givenID;
        }

        if ( $request["type"] != "single" )
        return;

        $item = $request["content"][ $request["IDS"][0] ];

        if ( $item_name == "remote_address" ? !empty( $item["data_decoded"]["remote_address"] ) : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["remote_address"];
        }
        elseif ( $item_name == "_source_audio" ? !empty( $item["data_decoded"]["local_file"] ) && $item["type"] == "audio" : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["local_file"];
        }
        elseif ( $item_name == "_source_video" ? !empty( $item["data_decoded"]["local_file"] ) && $item["type"] == "video" : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["local_file"];
        }
        elseif ( $item_name == "file_type" ? !empty( $item["data_decoded"]["file_type"] ) : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["file_type"];
        }
        elseif ( $item_name == "youtube_id" ? !empty( $item["data_decoded"]["youtube_id"] ) : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["youtube_id"];
        }
        elseif ( $item_name == "vimeo_id" ? !empty( $item["data_decoded"]["vimeo_id"] ) : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["vimeo_id"];
        }
        elseif ( $item_name == "soundcloud_id" ? !empty( $item["data_decoded"]["soundcloud_id"] ) : false ){
          $item_data["input"]["value"] = $item["data_decoded"]["soundcloud_id"];
        }
        elseif ( !empty( $item["data_decoded"]["{$item_name}"] ) ){
          $item_data["input"]["value"] = $item["data_decoded"]["{$item_name}"];
        }

      },
      "object_be_renderer" => function( $_inputs, $request ){

        if ( $request["type"] == "multi" ) return;

        $data = [];

        if ( $request["type"] == "single" ){
          $data = $request["content"][ $request["IDS"][0] ]["data_decoded"];
        }

        if ( $_inputs["data"]["type"] == "youtube" ){
          if ( !empty( $_inputs["data"]["youtube_id"] ) ){
            $data["youtube_id"] = $_inputs["data"]["youtube_id"];
          } else {
            $_inputs["report"]["fail"]["youtube_id"] = "Invalid";
          }
        }
        else if ( $_inputs["data"]["type"] == "soundcloud" ){
          if ( !empty( $_inputs["data"]["soundcloud_id"] ) ){
            $data["soundcloud_id"] = $_inputs["data"]["soundcloud_id"];
          } else {
            $_inputs["report"]["fail"]["soundcloud_id"] = "Invalid";
          }
        }
        else if ( $_inputs["data"]["type"] == "audio" ){

          if ( !empty( $_inputs["data"]["file_type"] ) ){

            $data["file_type"] = $_inputs["data"]["file_type"];
            if ( $data["file_type"] == "remote" ){

              if ( !empty( $_inputs["data"]["remote_address"] ) ){
                $data["remote_address"] = $_inputs["data"]["remote_address"];
              } else {
                $_inputs["report"]["fail"]["remote_address"] = "Invalid";
              }

            }
            else {

              if ( empty( $_inputs["data"]["_source_audio"] ) ) {
                $_inputs["report"]["fail"]["_source_audio"] = "Invalid";
              }
              else {
                $data["local_file"] = $_inputs["data"]["_source_audio"];
              }

            }

          } else {
            $_inputs["report"]["fail"]["file_type"] = "Select one";
          }

        }
        else if ( $_inputs["data"]["type"] == "video" ){

          if ( !empty( $_inputs["data"]["file_type"] ) ){

            $data["file_type"] = $_inputs["data"]["file_type"];
            if ( $data["file_type"] == "remote" ){

              if ( !empty( $_inputs["data"]["remote_address"] ) ){
                $data["remote_address"] = $_inputs["data"]["remote_address"];
              } else {
                $_inputs["report"]["fail"]["remote_address"] = "Invalid";
              }

            }
            else {

              if ( empty( $_inputs["data"]["_source_video"] ) ) {
                $_inputs["report"]["fail"]["_source_video"] = "Invalid";
              }
              else {
                $data["local_file"] = $_inputs["data"]["_source_video"];
              }

            }

          } else {
            $_inputs["report"]["fail"]["file_type"] = "Select one";
          }

        }
        else {
          
          $all_types = bof()->source->get_types();

          if ( !empty( $all_types[$_inputs["data"]["type"]]["inputs"] ) ){
            foreach( $all_types[$_inputs["data"]["type"]]["inputs"] as $_os_input ){
              $_os_input_name = $_os_input["input"]["name"];
              if ( !empty( $_inputs["data"][ $_os_input_name ] ) ){
                $data[ $_os_input_name ] = $_inputs["data"][ $_os_input_name ];
              } else {
                $_inputs["report"]["fail"][ $_os_input_name ] = "Invalid";
              }
            }
          }

        }

        $_inputs["data"]["data"] = $_inputs["set"]["data"] = $_inputs["update"]["data"] = $data;

        return $_inputs;

      },
    );

    return $setting;

  }

  // BusyOwlFramework helpers
  public function select( $caller, $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing ){
      $_eq[ "target" ] = [ "_eq" => [ "cover" => [] ] ];
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $caller, $whereArgs, $selectArgs );

  }
  public function create( $caller, $whereArray, $insertArray, $updateArray, $returnDetails=false, $exeRelations=true, $processCommands=null ){

    $old_local_file = null;
    $new_local_file = null;

    if ( $whereArray ){
      $old_item = $caller->select( $whereArray, [ "cache_load_rt" => false ] );
      if ( $old_item ){
        if ( !empty( $old_item["data_decoded"]["local_file"] ) )
        $old_local_file = $old_item["data_decoded"]["local_file"];
      }
    }

    if ( !empty( $insertArray["data"]["local_file"] ) ){
      $new_local_file = $insertArray["data"]["local_file"];
    }

    if ( !in_array( "download_able", array_keys( $insertArray ), true ) ){

      $get_types = bof()->source->get_types();

      $insertArray["download_able"] = 1;

      if ( in_array( $insertArray["type"], [ "youtube", "soundcloud", "vimeo" ], true ) )
      $insertArray["download_able"] = -2;

      elseif ( !empty( $get_types[ $insertArray["type"] ] ) ? in_array( "download_able", array_keys( $get_types[ $insertArray["type"] ] ), true ) : false )
      $insertArray["download_able"] = $get_types[ $insertArray["type"] ]["download_able"];

    }
    if ( !in_array( "stream_able", array_keys( $insertArray ), true ) ){
      $insertArray["stream_able"] = 1;
    }
    if ( !in_array( "encrypted", array_keys( $insertArray ), true ) ){
      $insertArray["encrypted"] = 0;
    }

    $create = bof()->object->_create( $caller, $whereArray, $insertArray, $insertArray, $returnDetails, $exeRelations );
    $create_id = $returnDetails ? $create["ID"] : $create;

    $online_process = !bof()->object->db_setting->get( "fs_bgp" );

    if ( $new_local_file && ( $online_process || !empty( $processCommands ) ) ){

      $update = $this->_bof_this->process( $caller, $create_id, true, $old_local_file, $processCommands );

    }
    elseif ( $new_local_file ) {

      $update["queue"] = 1;

    }

    if ( !empty( $update ) ){
      $do_update = $caller->update(
        array(
          "ID" => $create_id
        ),
        $update
      );
    }

    if ( ( !empty( $insertArray["type"] ) && !empty( $insertArray["data"]["file_type"] ) && !empty( $old_item ) ) ? 
    $insertArray["type"] == "audio" && $insertArray["data"]["file_type"] == "local" : 
    false ){
      $file_data = bof()->object->file->sid( $insertArray["data"]["local_file"] );
      if ( $file_data["host_id"] == 1 && $file_data["extension"] == "mp3" ){
           
        $_file_data = $caller->fetch_file_data( $insertArray["target_id"] );

        if (bof()->object->core_setting->get("id3_write_cover") && !empty($_file_data["new_id3_tags"]["cover"]) && is_file($_file_data["new_id3_tags"]["cover"])) {
          $_file_data["new_id3_tags"]["attached_picture"] = array(array(
            "picturetypeid" => 2,
            "description"   => 'cover',
            "mime"          => image_type_to_mime_type(exif_imagetype($_file_data["new_id3_tags"]["cover"])),
            "data"          => file_get_contents($_file_data["new_id3_tags"]["cover"])
          ));
          unset($_file_data["new_id3_tags"]["cover"]);

          try {
            bof()->id3->write_tags(
              $file_data["abs_path"],
              $_file_data["new_id3_tags"]
            );
          } catch (Exception | bofException $err) {
          }
        }
        
      }
    }

    return $create;

  }
  public function insert( $caller, $setArray ){

    $setArray["hash"] = !empty( $setArray["hash"] ) ? $setArray["hash"] : $caller->get_free_hash();

    return bof()->object->_insert( $caller, $setArray );

  }
  public function clean( $caller, $source, $args ){

    $_eq = [];
    $for_download = false;
    extract( $args );

    if ( $source["type"] == "youtube" ){
      $muse = array(
        "type" => array(
          "youtube",
          array(
            "youtube_id" => !empty( $source["data_decoded"]["youtube_id"] ) ? $source["data_decoded"]["youtube_id"] : null,
          )
        ),
      );
      if ( !empty( $source["data_decoded"]["youtube_id"] ) && bof()->object->core_setting->get( "piped_youtube" ) && bof()->getName() == "bof_client" ){
        /*try {
          $stream = bof()->youtube_piped->set_setting()->get_stream( $source["data_decoded"]["youtube_id"] );
          $muse = array(
            "type" => array(
              $stream["type"],
              array(
                "address" => $stream["url"] . "&bof_sw_ignore_me=sure&unique=" . uniqid(),
                "type" => "free",
                "format" => $stream["mime"],
              )
            )
          );
        } catch( bofException $err ){}*/
        $__d = explode( "_", bof()->object->db_setting->get( "youtube_piped_st" ) );
        $muse = array(
          "type" => array(
            reset( $__d ),
            array(
              "raaz" => true,
              "youtube_piped" => true,
              "youtube_id" => !empty( $source["data_decoded"]["youtube_id"] ) ? $source["data_decoded"]["youtube_id"] : null,
            )
          ),
        );
      }
    }
    elseif ( $source["type"] == "soundcloud" ) {

      $muse = array(
        "type" => array(
          "soundcloud",
          array(
            "ID" => !empty( $source["data_decoded"]["soundcloud_id"] ) ? $source["data_decoded"]["soundcloud_id"] : null,
          )
        ),
      );

    }
    elseif ( $source["type"] == "audio" || $source["type"] == "video" ? !empty( $source["data_decoded"]["file_type"] ) && $source["data_decoded"]["file_type"] == "remote" : false ) {

      $muse = array(
        "type" => array(
          $source["type"],
          array(
            "type" => "free",
            "address" => !empty( $source["data_decoded"]["remote_address"] ) ? bof()->general->https_url( $source["data_decoded"]["remote_address"] ) : null
          )
        ),
      );

      if ( !empty( $caller->child()->live ) )
      $muse["type"][1]["live"] = true;

    }
    elseif ( $source["type"] == "audio" || $source["type"] == "video" ? !empty( $source["data_decoded"]["file_type"] ) && ( $source["data_decoded"]["file_type"] == "local" || $source["data_decoded"]["file_type"] == "youtube_pending" ) : false ) {

      if ( !empty( $source["data_decoded"]["local_file"] ) )
      $local_file = bof()->object->file->select(["ID"=>$source["data_decoded"]["local_file"]]);

      if ( !empty( $local_file ) ){

        if ( empty( $local_file["time_moved"] ) ){

          $muse = array(
            "type" => array(
              "pending"
            )
          );

        }
        elseif ( !empty( $source["encrypted"] ) ){

          $muse = array(
            "type" => array(
              "video",
              array(
                "hls" => true,
                "type" => $source["type"],
                "address" => !empty( $local_file["web_address"] ) ? $local_file["web_address"] : null,
                "key" => !empty( $local_file["data_decoded"]["hls_key"] ) ? web_address . $local_file["data_decoded"]["hls_key"] : null,
              )
            ),
          );

          if ( $for_download ){
            if ( !empty( $local_file["data_decoded"]["_hls_slices"] ) ){
              $t = explode( "/", $local_file["web_address"] );
              array_pop( $t );
              $_base = implode( "/", $t ); 
              $muse["type"][1]["slices"] = [];
              foreach( $local_file["data_decoded"]["_hls_slices"] as $slice ){
                $_t = explode( "/", $slice );
                $muse["type"][1]["slices"][] = $_base . "/" . end( $_t );
              }
            }
          }

        }
        else {

          $muse = array(
            "type" => array(
              $source["type"],
              array(
                "type" => "free",
                "address" => !empty( $local_file["web_address"] ) ? $local_file["web_address"] : null
              )
            ),
          );

          if ( !empty( $source["force_free"] ) ){
            $muse["type"][1]["preview"] = true;
          }

        }

        if ( $for_download )
        $muse["file"] = $local_file;

      }
      elseif ( $source["data_decoded"]["file_type"] == "youtube_pending" ) {
        $muse = array(
          "type" => array(
            "youtube",
            array(
              "youtube_id" => !empty( $source["data_decoded"]["youtube_id"] ) ? $source["data_decoded"]["youtube_id"] : null,
              "pending" => true
            )
          ),
        );
      }

    }
    elseif ( !empty( $source["data_decoded"]["{$source["type"]}_id"] ) ) {

      $muse = array(
        "type" => array(
          $source["type"],
          array(
            "ID" => $source["data_decoded"]["{$source["type"]}_id"],
          )
        ),
      );

    }
    elseif ( !empty( $source["data_decoded"]["{$source["type"]}_url"] ) ) {

      $muse = array(
        "type" => array(
          $source["type"],
          array(
            "url" => $source["data_decoded"]["{$source["type"]}_url"],
          )
        ),
      );

    }

    if ( $source["type"] == "youtube" )
    $source["_title"] = "YouTube - #" . ( !empty( $source["data_decoded"]["youtube_id"] ) ? $source["data_decoded"]["youtube_id"] : "?" );

    elseif ( $source["type"] == "soundcloud" )
    $source["_title"] = "SoundCloud - #" . ( !empty( $source["data_decoded"]["soundcloud_id"] ) ? $source["data_decoded"]["soundcloud_id"] : "?" );

    elseif ( $source["type"] == "audio" )
    $source["_title"] = bof()->source->hr_quality( $source["type"], $source["quality"] );

    elseif ( $source["type"] == "video" )
    $source["_title"] = bof()->source->hr_quality( $source["type"], $source["quality"] );

    else
    $source["_title"] = ucfirst( $source["type"] ) . " - #" . ( !empty( $source["data_decoded"]["{$source["type"]}_id"] ) ? $source["data_decoded"]["{$source["type"]}_id"] : "?" );

    if ( !empty( $source["title"] ) )
    $source["_title"] = $source["title"];

    if ( empty( $source["_title"] ) )
    $source["_title"] = "?";

    $source["muse"] = !empty( $muse ) ? $muse : null;
    return $source;

  }
  public function delete( $caller, $whereArgs, $exeRelations=true ){

    $items = $caller->select(
      $whereArgs,
      array(
        "single" => false,
        "limit" => false,
        "cache_load_rt" => false,
        "relations" => true
      )
    );

    if ( $items ){
      foreach( $items as $item ){
        if ( !empty( $item["data_decoded"]["local_file"] ) ){
          bof()->object->file->unlink( $item["data_decoded"]["local_file"], false );
        }
      }
    }

    return bof()->object->_delete( $caller, $whereArgs, $exeRelations );

  }

  public function process( $caller, $source_id, $online, $old_local_file=null, $processCommands=null ){

    $sourceData = $caller->select(
      array(
        "ID" => $source_id
      )
    );

    if ( !empty( $sourceData["data_decoded"]["local_file"] ) )
    $new_local_file = $sourceData["data_decoded"]["local_file"];

    if ( !empty( $sourceData["data_decoded"]["file_type"] ) ? $sourceData["data_decoded"]["file_type"] == "youtube_pending" : false ){
      $new_local_file = bof()->youtube->download( $sourceData["data_decoded"]["youtube_id"] );
    }

    if ( !$online )
    $old_local_file = $sourceData["queue_old"];

    $_file_data = $caller->fetch_file_data( $sourceData["target_id"] );

    if ( !$_file_data )
    fall("Fetch file data failed on source process");

    if ( bof()->object->core_setting->get( "id3_write_cover" ) && !empty( $_file_data["new_id3_tags"]["cover"] ) && is_file( $_file_data["new_id3_tags"]["cover"]  ) ){
      $_file_data["new_id3_tags"]["attached_picture"] = array( array(
        "picturetypeid" => 2,
        "description"   => 'cover',
        "mime"          => 'image/jpeg',
        "data"          => file_get_contents( $_file_data["new_id3_tags"]["cover"] )
      ) );
    }

    unset( $_file_data["new_id3_tags"]["cover"] );

    $sourceExtraData = !empty( $sourceData["data_decoded"] ) ? $sourceData["data_decoded"] : [];

    $_new_name =  $_file_data["new_name"];
    $_new_name = preg_replace('/[^\x20-\x7E]/u', '', $_new_name);
    $_new_name = preg_replace('/[\/:*?"<>|\\\]/', '_', $_new_name);
    if ( empty( $_new_name ) || strlen( $_new_name ) < 5 )
    $_new_name = uniqid();

    $_validate_file = bof()->object->file->finalize_upload(
      $sourceData["type"],
      $caller->bof()["name"],
      $caller->bof()["name"] . $source_id,
      $new_local_file,
      $old_local_file,
      array(
        "encrypt" => !empty( $processCommands["disable_encrypt"] ) ? false : null,
        "lower" => !empty( $processCommands["disable_lower"] ) ? false : null,
        "real" => !empty( $processCommands["disable_real"] ) ? false : null,
        "convert" => !empty( $processCommands["disable_convert"] ) ? false : null,
        "preview" => !empty( $processCommands["disable_preview"] ) ? false : null,
        "force_no_protect" => !empty( $processCommands["force_no_protect"] ) ? true : false,
        "premium" => $_file_data["premium"],
        "new_name" => $_new_name,
        "new_id3_tags" => $_file_data["new_id3_tags"]
      )
    );

    if ( $_validate_file ){

      $sourceExtraData["local_file"] = $new_local_file;

      if ( empty( $_validate_file["already_moved"] ) ){

        if ( is_array( $_validate_file ) ? in_array( "download_able", array_keys( $_validate_file ), true ) : false )
        $update["download_able"] = $_validate_file["download_able"];

        if ( is_array( $_validate_file ) ? in_array( "stream_able", array_keys( $_validate_file ), true ) : false )
        $update["stream_able"] = $_validate_file["stream_able"];

        if ( empty( $sourceData["quality"] ) && !empty( $_validate_file["bitrate"] ) ){
          $update["quality"] = 1;
          if ( $_validate_file["bitrate"] >= 320 )
          $update["quality"] = 5;
          else if ( $_validate_file["bitrate"] >= 256 )
          $update["quality"] = 4;
          else if ( $_validate_file["bitrate"] >= 192 )
          $update["quality"] = 3;
          else if ( $_validate_file["bitrate"] >= 128 )
          $update["quality"] = 2;
        }

        if ( empty( $sourceData["quality"] ) && !empty( $_validate_file["v_quality"] ) ){
          $update["quality"] = 1;
          if ( $_validate_file["v_quality"] == "4K" )
          $update["quality"] = 5;
          else if ( $_validate_file["v_quality"] == "1080p" )
          $update["quality"] = 4;
          else if ( $_validate_file["v_quality"] == "720p" )
          $update["quality"] = 3;
          else if ( $_validate_file["v_quality"] == "480p" )
          $update["quality"] = 2;
        }

        if ( empty( $sourceData["duration"] ) && !empty( $_validate_file["duration"] ) )
        $update["duration"] = $_validate_file["duration"];

        if ( is_array( $_validate_file ) ? in_array( "hls", array_keys( $_validate_file ), true ) : false )
        $update["encrypted"] = 1;

        if ( !empty( $_validate_file["protected"] ) )
        $update["protected"] = 1;

        if ( is_array( $_validate_file ) ? !empty( $_validate_file["lower_qualities"] ) : false ){

          foreach( $_validate_file["lower_qualities"] as $lower_quality ){

            $caller->create(
              [],
              array(
                "target_id" => $sourceData["target_id"],
                "type" => $sourceData["type"],
                "quality" => $lower_quality["quality"],
                "duration" => !empty( $sourceData["duration"] ) ? $sourceData["duration"] : null,
                "title" => !empty( $sourceData["title"] ) ? $sourceData["title"] . " - " . ( bof()->source->hr_quality( $sourceData["type"], $lower_quality["quality"], true ) ) : null,
                "data" => array(
                  "file_type" => "local",
                  "local_file" => $lower_quality["ID"],
                  "parent" => $source_id,
                ),
              ),
              [],
              false,
              true,
              array(
                "disable_lower" => true,
                "disable_preview" => true,
              )
            );

          }

        }

        if ( is_array( $_validate_file ) ? !empty( $_validate_file["real_file"] ) : false ){

          $caller->create(
            [],
            array(
              "target_id" => $sourceData["target_id"],
              "type" => $sourceData["type"],
              "stream_able" => -1,
              "title" => !empty( $sourceData["title"] ) ? $sourceData["title"] : null,
              "data" => array(
                "file_type" => "local",
                "local_file" => $_validate_file["real_file"]["ID"],
                "encrypted_version" => $source_id,
              ),
            ),
            [],
            false,
            true,
            array(
              "disable_encrypt" => true,
              "disable_lower" => true,
              "disable_convert" => true,
              "disable_preview" => true
            )
          );

        }

        if ( is_array( $_validate_file ) ? !empty( $_validate_file["preview_file"] ) : false ){

          $caller->create(
            [],
            array(
              "target_id" => $sourceData["target_id"],
              "type" => $sourceData["type"],
              "stream_able" => 1,
              "download_able" => -2,
              "title" => !empty( $sourceData["title"] ) ? $sourceData["title"] . " - preview" : "preview",
              "force_free" => true,
              "data" => array(
                "file_type" => "local",
                "local_file" => $_validate_file["preview_file"]["ID"],
                "full_version" => $source_id,
              ),
            ),
            [],
            false,
            true,
            array(
              "disable_encrypt" => true,
              "disable_lower" => true,
              "disable_convert" => true,
              "disable_real" => true,
              "force_no_protect" => true,
              "disable_preview" => true
            )
          );

        }

      }

    }

    $update["data"] = $sourceExtraData;

    if ( empty( $online ) && !empty( $update ) ){
      $caller->update(
        array(
          "ID" => $source_id
        ),
        $update
      );
    }

    return $update;

  }

}

?>
