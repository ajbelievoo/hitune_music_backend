<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofAdmin_setting( $loader, $excuter, $args ){

  $browse_func = function( $groups ){

    $objects = bof()->bofAdmin->_get_objects();
    $objects_inputs = [];

    $browsable_objects = [];

    foreach( $objects as $object_name => $object_args ){

      $object = bof()->object->__get( $object_name );
      if ( !$object->method_exists("bof") )
      continue;

      if ( empty( $object->bof()["browsable"] ) )
      continue;

      $object_filters_setting = bof()->object->db_setting->get( "br_{$object_name}_setting" );

      $browsable_objects[ $object_name ] = array(
        "icon" => $object->bof()["icon"],
        "title" => $object->bof()["label"],
        "inputs" => array(
          "br_{$object_name}" => array(
            "title" => "Active",
            "tip" => "If enabled, users can browse and filter all of {$object->bof()["label"]} related content in <a href=\"".web_address."browse/{$object_name}\">".web_address."browse/{$object_name}</a> address",
            "col_name" => "br_{$object_name}",
            "input" => array(
              "type" => "checkbox",
              "name" => "br_{$object_name}"
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()",
                "int" => true
              )
            )
          )
        )
      );

      if ( ( $browse_filters = bof()->bofClient->_browse_get_filters( $object_name, $object, true ) ) ){

        foreach( $browse_filters as $filter_select => $filter_data ){
          if ( $filter_select == "sort_by" ) continue;
          $browsable_objects[ $object_name ]["inputs"]["br_{$object_name}_{$filter_select}"] = array(
            "title" => "filter: " . $filter_data["title"],
            "input" => array(
              "type" => "checkbox",
              "name" => "br_{$object_name}_{$filter_select}",
              "value" => $object_filters_setting ? !empty( $object_filters_setting["br_{$object_name}_{$filter_select}"] ) : true
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()",
                "int" => true
              )
            )
          );
        }

        if ( !empty( $browse_filters["sort_by"] ) ){

          $browsable_objects[ $object_name ]["inputs"]["br_{$object_name}_sorters"] = array(
            "title" => "filter: sort by values",
            "input" => array(
              "type" => "select_m",
              "name" => "br_{$object_name}_sorters",
              "options" => $browse_filters["sort_by"]["input"]["options"],
              "value" => !empty( $object_filters_setting["br_{$object_name}_sorters"] ) ? explode( ";", $object_filters_setting["br_{$object_name}_sorters"] ) : $browse_filters["sort_by"]["validator"][1]["values"]
            ),
            "validator" => array(
              "in_array",
              array(
                "values" => $browse_filters["sort_by"]["validator"][1]["values"]
              )
            )
          );

        }

      }

    }

    return $browsable_objects;

  };
  $browse_func_be = function( $groups, $inputs ){

    $objects = bof()->bofAdmin->_get_objects();
    $objects_inputs = [];

    $browsable_objects = [];

    foreach( $objects as $object_name => $object_args ){

      $object = bof()->object->__get( $object_name );
      if ( !$object->method_exists("bof") )
      continue;

      if ( empty( $object->bof()["browsable"] ) )
      continue;

      if ( ( $browse_filters = bof()->bofClient->_browse_get_filters( $object_name, $object, true ) ) ){

        $browse_filter_setting = [];

        foreach( $browse_filters as $filter_select => $filter_data ){
          if ( $filter_select == "sort_by" ) continue;
          $browse_filter_setting[ "br_{$object_name}_{$filter_select}" ] = bof()->nest->user_input( "post", "br_{$object_name}_{$filter_select}", "boolean", [], false );
        }

        $new_sort_by = $browse_filters["sort_by"];
        $new_sort_by["input"]["type"] = "select_m";
        $browse_filter_setting[ "br_{$object_name}_sorters" ] = bof()->nest->user_input( "post", "br_{$object_name}_sorters", "in_array", [ "values" => $browse_filters["sort_by"]["validator"][1]["values"] ], false );
        $browse_filter_setting[ "br_{$object_name}_sorters" ] = bof()->bofInput->__get_value( "br_{$object_name}_sorters", $new_sort_by, "post" )[1];

        bof()->object->db_setting->set( "br_{$object_name}_setting", json_encode( $browse_filter_setting ), "json" );

      }

    }

    return $inputs;

  };
  $seo_func_ui  = function( $groups ){

    $objects = bof()->bofAdmin->_get_objects();
    $objects_inputs = [];
    $seo_objects = [];

    foreach( $objects as $object_name => $object_args ){

      $object = bof()->object->__get( $object_name );

      if ( !$object->method_exists("bof") )
      continue;

      if ( !$object->method_exists("bof_columns") )
      continue;

      if ( !in_array( "seo", array_keys( $object->bof_columns() ), true ) && !in_array( "seo", $object->bof_columns(), true ) )
      continue;

      $seo_column_data = !empty( $object->bof_columns()["seo"]["o_title_format"] ) ? $object->bof_columns()["seo"]["o_title_format"] : [ "title" => "title" ];
      $seo_object_data = bof()->object->db_setting->get( "seo_{$object_name}" );

      $seo_object_data_html = [];
      foreach( $seo_column_data as $_k => $_v )
      $seo_object_data_html[] = "<b>%{$_k}%</b>: " . ucfirst( $_v );

      $seo_objects[ $object_name ] = array(
        "icon" => $object->bof()["icon"],
        "title" => $object->bof()["label"],
        "inputs" => array(
          "seo_{$object_name}_title" => array(
            "title" => "Title",
            "tip" => "Customize title. Otherwise the title alone will be used<br>You can use available parameters surronded by % in a string. For example, the following string:<br><i>Stream %title% by %artist%!</i><br>For PinkFloyd-Time track would become:<br><i>Stream Time by Pink Floyd!</i><br><br>Available parameters:<br>" . implode( "<br>", $seo_object_data_html ),
            "input" => array(
              "type" => "text",
              "name" => "seo_{$object_name}_title",
              "value" => !empty( $seo_object_data["title"] ) ? $seo_object_data["title"] : null
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "seo_{$object_name}_desc" => array(
            "title" => "Description",
            "tip" => "Customize description. Otherwise it will be empty<br>You can use available parameters surronded by % in a string:<br><br>" . implode( "<br>", $seo_object_data_html ),
            "input" => array(
              "type" => "text",
              "name" => "seo_{$object_name}_desc",
              "value" => !empty( $seo_object_data["desc"] ) ? $seo_object_data["desc"] : null
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
        )
      );

    }

    return $seo_objects;

  };
  $seo_func_be = function( $groups, $inputs ){

    $objects = bof()->bofAdmin->_get_objects();
    $objects_inputs = [];
    $seo_objects = [];

    foreach( $objects as $object_name => $object_args ){

      $object = bof()->object->__get( $object_name );

      if ( !$object->method_exists("bof") )
      continue;

      if ( !$object->method_exists("bof_columns") )
      continue;

      if ( !in_array( "seo", array_keys( $object->bof_columns() ), true ) && !in_array( "seo", $object->bof_columns(), true ) )
      continue;

      if ( empty( $inputs["data"]["seo_{$object_name}_title"] ) && empty( $inputs["data"]["seo_{$object_name}_title"] ) ){
        bof()->object->db_setting->del( "seo_{$object_name}" );
        continue;
      }

      $seo_object_data = [];
      if ( !empty( $inputs["data"]["seo_{$object_name}_title"] ) )
      $seo_object_data["title"] = $inputs["data"]["seo_{$object_name}_title"];
      if ( !empty( $inputs["data"]["seo_{$object_name}_desc"] ) )
      $seo_object_data["desc"] = $inputs["data"]["seo_{$object_name}_desc"];

      bof()->object->db_setting->set( "seo_{$object_name}", json_encode( $seo_object_data ), "json" );

    }

    return $inputs;

  };
  $admin_nots = function( $groups ){

    $nots = bof()->chapar_admin->get_args( null );
    foreach( $nots as $notID => $notArgs ){
      $groups[$notID . "_setting"] = array(
        "title" => $notArgs["title"],
        "icon" => $notArgs["icon"],
        "inputs" => array(

          "{$notID}_active" => array(
            "title" => "Active",
            "input" => array(
              "name"  => "{$notID}_active",
              "type"  => "checkbox",
              "value" => true
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()"
              )
            )
          ),
          "{$notID}_email_active" => array(
            "title" => "Enable Email Channel",
            "input" => array(
              "name"  => "{$notID}_email_active",
              "type"  => "checkbox",
              "value" => true
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()"
              )
            )
          ),
          "{$notID}_telegram_active" => array(
            "title" => "Enable Telegram Channel",
            "input" => array(
              "name"  => "{$notID}_telegram_active",
              "type"  => "checkbox",
              "value" => true
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()"
              )
            )
          ),
          "{$notID}_whatsapp_active" => array(
            "title" => "Enable WhatsApp Channel",
            "input" => array(
              "name"  => "{$notID}_whatsapp_active",
              "type"  => "checkbox",
              "value" => true
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()"
              )
            )
          ),

        )
      );
    }
    

    $nots_db = bof()->object->db_setting->get( "admin_notifications" );
    if (!empty($nots_db)) {
      foreach ($groups as &$group) {
        foreach ($group["inputs"] as &$input_g) {
          $input_g["input"]["value"] = !empty($nots_db[$input_g["input"]["name"]]) ? $nots_db[$input_g["input"]["name"]] : "";
        }
        unset($input_g);
      }
      unset($group);
    }
    

    return $groups;

  };

  $settings = array(
    "storage" => array(

      "functions" => array(
        "ui_pre" => [ 'storage', 'admin_setting' ],
        "be_pre" => [ 'storage', 'admin_setting' ],
        "be_after" => [ 'storage', 'admin_setting_be' ],
      ),
      "groups" => array()

    ),
    "upload" => array(

      "functions" => array(
        "ui_pre" => [ 'file', 'admin_setting_ui' ],
        "be_pre" => [ 'file', 'admin_setting_ui' ],
        "be_after" => function( $groups, $inputs ){
          foreach( $inputs["set"] as $_k => $_v ){
            if ( substr( $_k, 0, 4 ) == "fs__" ? empty( $_v ) || $_v == "__" : false ){
              unset( $inputs["set"][ $_k ] );
              bof()->object->db_setting->del( $_k );
            }
          }
          return $inputs;
        }
      ),

      "groups" => array(

        "fs_bgp" => array(
          "title" => "Background Processing",
          "icon" => "dns",
          "inputs" => array(
            "fs_bgp" => array(
              "tip" => "Some processes like converting files can be time and resource consuming, resulting in request \"timeout\" when a user submits a file. If this option is enabled, uploaded content from user and admin area will not be processed ( converted, encrypted, etc ) when user submits the data, instead they will be processed in background by cronjobs. Unprocessed files are un-playable until they have been processed by the server. <br><br>Background processing is a necessity for \"video converting\" but it might not be necessary for encrypting ( audio & video ) or converting ( regular sized ) audio files as they don't require much time or resource",
              "col_name" => "fs_bgp",
              "title" => "Enable",
              "input" => array(
                "name"  => "fs_bgp",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "fs_chunk" => array(
          "title" => "Chunk upload",
          "icon" => "content_cut",
          "inputs" => array(
            "fs_chunk" => array(
              "tip" => "If enabled, script will cut files to smaller `chunks` and upload them 1by1. Helps uploading large files or uploading with low-speed connections. <a href='https://stackoverflow.com/questions/14909198/why-chunk-file-upload'>More info</a>",
              "col_name" => "fs_chunk",
              "title" => "Enable",
              "input" => array(
                "name"  => "fs_chunk",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_chunk_size" => array(
              "tip" => "Maximum size for chunked pieces in <b>MB</b><br>Maximum upload size on your server: <b>__MAX_SERVER_UPLOAD_SIZE__</b>",
              "col_name" => "fs_chunk_size",
              "title" => "Chunk size",
              "input" => array(
                "name"  => "fs_chunk_size",
                "type"  => "digit",
              ),
              "validator" => array(
                "float",
                array(
                  "min" => 0.01
                )
              )
            ),
          )
        ),
        "fs_image" => array(
          "title" => "Image",
          "icon" => "image",
          "inputs" => array(
            "fs_image_size_max" => array(
              "tip" => "Maximum file size for images in <b>MB</b>. For example if you want to set 2 MB as limit only enter <b>2</b>. Can be `float`, if you want to set 100 KB as limit for images, enter (100/1000=) <b>0.1</b>",
              "title" => "Maximum file size",
              "col_name" => "fs_image_size_max",
              "input" => array(
                "name"  => "fs_image_size_max",
                "type"  => "digit",
              ),
              "validator" => array(
                "float",
                array(
                  "min" => 0.0001
                )
              )
            ),
            "fs_image_size_min" => array(
              "tip" => "Minimum file size for images in <b>MB</b>. For example if you want to set 1 MB as limit only enter <b>1</b>. Can be `float`, if you want to set 10 KB as limit for images, enter (10/1000=) <b>0.01</b>",
              "title" => "Minimum file size",
              "col_name" => "fs_image_size_min",
              "input" => array(
                "name"  => "fs_image_size_min",
                "type"  => "digit",
              ),
              "validator" => array(
                "float",
                array(
                  "min" => 0.0001
                )
              )
            ),
            "fs_image_fl" => array(
              "title" => "Acceptable formats",
              "col_name" => "fs_image_fl",
              "input" => array(
                "name"  => "fs_image_fl",
                "type"  => "select_m",
                "options" => array(
                  [ "jpg", "JPEG" ],
                  [ "gif", "GIF" ],
                  [ "png", "PNG" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "jpg", "gif", "png" ]
                )
              )
            ),
            "fs_image_dim_min" => array(
              "tip" => "Minimum <b>width*height</b> for images in <b>Pixels</b>. For example if you want to set 400 pixel as minimum width limit and 200 pixel as minimum height limit, enter <b>400*200</b>",
              "title" => "Minimum image dimension",
              "col_name" => "fs_image_dim_min",
              "input" => array(
                "name"  => "fs_image_dim_min",
                "type"  => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strict" => true,
                  "strict_regex" => "/^([0-9]{1,5})\*([0-9]{1,5})$/",
                  "strict_regex_raw" => true
                )
              )
            ),
            "fs_image_dim_max" => array(
              "title" => "Maximum image dimension",
              "col_name" => "fs_image_dim_max",
              "tip" => "Maximum <b>width*height</b> for images in <b>Pixels</b>. For example if you want to set 2000 pixel as maximum width limit and 1500 pixel as maximum height limit, enter <b>2000*1500</b>",
              "input" => array(
                "name"  => "fs_image_dim_max",
                "type"  => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strict" => true,
                  "strict_regex" => "/^([0-9]{1,5})\*([0-9]{1,5})$/",
                  "strict_regex_raw" => true
                )
              )
            ),
            "fs_image_resize" => array(
              "tip" => "One image for all screen resolutions and different devices is not enough. Script can resize original image to create `responsive` images. <a href='https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images'>More info</a>",
              "title" => "Resize",
              "col_name" => "fs_image_resize",
              "input" => array(
                "name"  => "fs_image_resize",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "fs_audio" => array(

          "title" => "Audio",
          "icon" => "audiotrack",
          "inputs" => array(

            "fs_audio_size_max" => array(
              "tip" => "Maximum file size for audios in <b>MB</b>. For example if you want to set 20 MB as limit only enter <b>20</b>",
              "col_name" => "fs_audio_size_max",
              "title" => "Maximum file size",
              "input" => array(
                "name"  => "fs_audio_size_max",
                "type"  => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1
                )
              )
            ),
            "fs_audio_size_min" => array(
              "tip" => "Minimum file size for audios in <b>MB</b>. For example if you want to set 1 MB as limit only enter <b>1</b>. Can't be 0. Can be `float`, if you want to set 10 KB as limit, enter (10/1000=) <b>0.01</b>",
              "title" => "Minimum file size",
              "col_name" => "fs_audio_size_min",
              "input" => array(
                "name"  => "fs_audio_size_min",
                "type"  => "digit",
              ),
              "validator" => array(
                "float",
                array(
                  "min" => 0.0001
                )
              )
            ),
            "fs_audio_br_min" => array(
              "tip" => "Minimum bitrate for audio files",
              "title" => "Minimum Bitrate",
              "col_name" => "fs_audio_br_min",
              "input" => array(
                "name"  => "fs_audio_br_min",
                "type"  => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 10,
                  "empty()"
                )
              )
            ),
            "fs_audio_fl" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. If FFmpeg is not enabled, only mp3 will be accepted regardless of this setting. If enabled, all formats can be accepted to be converted to mp3",
              "title" => "Acceptable formats",
              "col_name" => "fs_audio_fl",
              "input" => array(
                "name"  => "fs_audio_fl",
                "type"  => "select_m",
                "options"  => array(
                  [ "mp3", "mp3" ],
                  [ "wav", "wav" ],
                  [ "flac", "flac" ],
                  [ "ogg", "ogg" ],
                  [ "aac", "aac" ],
                ),
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "mp3", "wav", "flac", "ogg", "aac" ]
                )
              )
            ),
            "fs_audio_waveform" => array(
              "tip" => "Script will make a waveform for uploaded audios",
              "title" => "Waveforms",
              "col_name" => "fs_audio_waveform",
              "input" => array(
                "name"  => "fs_audio_waveform",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_protect" => array(
              "title" => "Protect premium files",
              "tip" => "All premium ( priced ) files will be stored in a private directory. They can't be accessed directly or uploaded to third party storages",
              "col_name" => "fs_audio_protect",
              "input" => array(
                "name"  => "fs_audio_protect",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_preview" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 128k-bitrate preview from first 20% of the premium tracks which can be accessed for free",
              "title" => "Preview for premium files ( With FFmpeg )",
              "col_name" => "fs_audio_preview",
              "input" => array(
                "name"  => "fs_audio_preview",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_preview_no_ff" => array(
              "tip" => "<a href='cli_setting'><b>Without FFmpeg</b></a>. Script will make virtual copy from first 20% of the premium tracks which can be accessed for free. We recommned using FFmpeg version if you have it installed",
              "title" => "Preview for premium files ( No FFmpeg )",
              "col_name" => "fs_audio_preview_no_ff",
              "input" => array(
                "name"  => "fs_audio_preview_no_ff",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_flac" => array(
              "tip" => "You can upload FLAC files and app will convert them to MP3 for online streaming. If you wish to keep the real version of FLAC files for downloading purpose, check this box",
              "title" => "Keep FLAC files for download",
              "col_name" => "fs_audio_flac",
              "input" => array(
                "name"  => "fs_audio_flac",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_lower_256" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 256k-bitrate copy of mp3 files with 256k+ bitrate to keep",
              "title" => "Lower quality to 256k",
              "col_name" => "fs_audio_lower_256",
              "input" => array(
                "name"  => "fs_audio_lower_256",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_lower_192" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 192k-bitrate copy of mp3 files with 192k+ bitrate to keep",
              "title" => "Lower quality to 192k",
              "col_name" => "fs_audio_lower_192",
              "input" => array(
                "name"  => "fs_audio_lower_192",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_lower_128" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 128k-bitrate copy of mp3 files with 128k+ bitrate to keep",
              "title" => "Lower quality to 128k",
              "col_name" => "fs_audio_lower_128",
              "input" => array(
                "name"  => "fs_audio_lower_128",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_lower_64" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 64k-bitrate copy of mp3 files with 64k+ bitrate to keep",
              "title" => "Lower quality to 64k",
              "col_name" => "fs_audio_lower_64",
              "input" => array(
                "name"  => "fs_audio_lower_64",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_hls" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will encrypt files using AES-128 to be used as HLS only on app's player. To explain simpler, script will cut your files into smaller pieces and put a lock on each piece ( or 'encrypt' them ). These pieces can only be unlocked ( or 'decrypted' ) by app's player since other players don't have the key to unlock them. In other words, even if user manages to download all these parts, they can't play them outside your app",
              "title" => "HLS Encryption",
              "col_name" => "fs_audio_hls",
              "input" => array(
                "name"  => "fs_audio_hls",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_audio_hls_kr" => array(
              "tip" => "Encrypted files can not be downloaded to be played outside your app. If you wish to allow users to download and use your files outside your app while HLS encryption is enabled, check this box",
              "title" => "HLS Encryption - Keep real files for download",
              "col_name" => "fs_audio_hls_kr",
              "input" => array(
                "name"  => "fs_audio_hls_kr",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "fs_video" => array(

          "title" => "Video",
          "icon" => "play_arrow",
          "inputs" => array(

            "fs_video_size_max" => array(
              "tip" => "Maximum file size for videos in <b>MB</b>. For example if you want to set 20 MB as limit only enter <b>20</b>",
              "col_name" => "fs_video_size_max",
              "title" => "Maximum file size",
              "input" => array(
                "name"  => "fs_video_size_max",
                "type"  => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1
                )
              )
            ),
            "fs_video_size_min" => array(
              "tip" => "Minimum file size for videos in <b>MB</b>. For example if you want to set 1 MB as limit only enter <b>1</b>. Can't be 0. Can be `float`, if you want to set 10 KB as limit, enter (10/1000=) <b>0.01</b>",
              "title" => "Minimum file size",
              "col_name" => "fs_video_size_min",
              "input" => array(
                "name"  => "fs_video_size_min",
                "type"  => "digit",
              ),
              "validator" => array(
                "float",
                array(
                  "min" => 0.0001
                )
              )
            ),
            "fs_video_width_min" => array(
              "tip" => "Minimum width for video files in pixels ( integer )<br>below 1280: 480p<br>1280 and above: 720p<br>1920 and above: 1080p<br>3840 and above: 4K",
              "title" => "Minimum width",
              "col_name" => "fs_video_width_min",
              "input" => array(
                "name"  => "fs_video_width_min",
                "type"  => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 10,
                  "empty()"
                )
              )
            ),
            "fs_video_width_max" => array(
              "tip" => "Maximum width for video files in pixels ( integer )",
              "title" => "Maximum width",
              "col_name" => "fs_video_width_max",
              "input" => array(
                "name"  => "fs_video_width_max",
                "type"  => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 10,
                  "empty()"
                )
              )
            ),
            "fs_video_fl" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. If FFmpeg is not enabled, only mp4 will be accepted regardless of this setting. If enabled, all formats can be accepted to be converted to mp3",
              "title" => "Acceptable formats",
              "col_name" => "fs_video_fl",
              "input" => array(
                "name"  => "fs_video_fl",
                "type"  => "select_m",
                "options"  => array(
                  [ "mp4", "mp4" ],
                  [ "avi", "avi" ],
                  [ "mov", "mov" ],
                  [ "mkv", "mkv" ],
                  [ "wmv", "wmv" ],
                  [ "webm", "webm" ],
                ),
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "mp4", "avi", "mov", "mkv", "wmv", "webm" ]
                )
              )
            ),
            "fs_video_lower_1080" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 1080p copy of 4K videos to keep",
              "title" => "Lower quality to 1080p",
              "col_name" => "fs_video_lower_1080",
              "input" => array(
                "name"  => "fs_video_lower_1080",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_video_lower_720" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 720p copy of 1080p & 4K videos to keep",
              "title" => "Lower quality to 720p",
              "col_name" => "fs_video_lower_720",
              "input" => array(
                "name"  => "fs_video_lower_720",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_video_lower_480" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 480p copy of 1080p, 720p & 4K videos to keep",
              "title" => "Lower quality to 480p",
              "col_name" => "fs_video_lower_480",
              "input" => array(
                "name"  => "fs_video_lower_480",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_video_lower_240" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will make a 240p copy of 1080p, 720p, 480p & 4K videos to keep",
              "title" => "Lower quality to 240p",
              "col_name" => "fs_video_lower_240",
              "input" => array(
                "name"  => "fs_video_lower_240",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_video_hls" => array(
              "tip" => "<a href='cli_setting'><b>Requires FFmpeg</b></a>. Script will encrypt files using AES-128 to be used as HLS only on app's player. To explain simpler, script will cut your files into smaller pieces and put a lock on each piece ( or 'encrypt' them ). These pieces can only be unlocked ( or 'decrypted' ) by app's player since other players don't have the key to unlock them. In other words, even if user manages to download all these parts, they can't play them outside your app",
              "title" => "HLS Encryption",
              "col_name" => "fs_video_hls",
              "input" => array(
                "name"  => "fs_video_hls",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "fs_video_hls_kr" => array(
              "tip" => "Encrypted files can not be downloaded to be played outside your app. If you wish to allow users to download and use your files outside your app while HLS encryption is enabled, check this box",
              "title" => "HLS Encryption - Keep real files for download",
              "col_name" => "fs_video_hls_kr",
              "input" => array(
                "name"  => "fs_video_hls_kr",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),


          )
        ),
      )

    ),
    "gateway_offline" => array(
      "groups" => array(
        "gateway" => array(

          "title" => "Details",
          "icon" => "mode_edit",

          "inputs" => array(

            "gateway_offline" => array(
              "title" => "Enable",
              "tip" => "Do you want to enable offline bank transfer?<br> Users can see the <b>Bank Infomation</b>, transfer the money manually then upload the receipt. If you approve that receipt user will have his funds up",
              "col_name" => "gateway_offline",
              "input" => array(
                "name" => "gateway_offline",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                )
              )
            ),
            "gateway_offline_fee" => array(
              "title" => "Fee",
              "tip" => "Set a fee in percentage ( 0 to 100 ). Script will automatically reduce the fee from user payments",
              "col_name" => "gateway_offline_fee",
              "input" => array(
                "name" => "gateway_offline_fee",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "max" => 100,
                  "int" => true,
                  "asInt" => true,
                  "forceInt" => true
                )
              )
            ),
            "gateway_offline_detail" => array(
              "title" => "Bank Infomation",
              "col_name" => "gateway_offline_detail",
              "input" => array(
                "name" => "gateway_offline_detail",
                "type" => "textarea",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                  "allow_eol" => true,
                  "strip_emoji" => false
                )
              )
            ),

          )
        ),
      )
    ),
    "general" => array(
      "groups" => array(
        "general" => array(

          "title" => "Config",
          "icon" => "settings",

          "inputs" => array(
            "client_private" => array(
              "title" => "Private App",
              "tip" => "In a private app, only logged-in users can access the content and guests are intially asked to login/sign-up first. You can enable/disable sign-up in <a href='user_role/1'>Here</a>",
              "col_name" => "client_private",
              "input" => array(
                "name" => "client_private",
                "type" => "checkbox",
                "value" => client_private
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => true
                )
              )
            ),
            "client_give_attribute" => array(
              "title" => "Credit Sources",
              "tip" => "Script will display `copyright` or `disclaimer` for medias hosted on third party services",
              "col_name" => "client_give_attribute",
              "input" => array(
                "name" => "client_give_attribute",
                "type" => "checkbox",
                "value" => client_give_attribute
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => true
                )
              )
            ),
            "client_auto_images" => array(
              "title" => "Third-party images",
              "tip" => "Script will use available images from third-parties if there are no local images uploaded for an item",
              "col_name" => "client_auto_images",
              "input" => array(
                "name" => "client_auto_images",
                "type" => "checkbox",
                "value" => client_auto_images
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => true
                )
              )
            ),
          )
        ),
      )
    ),
    "placeholders" => array(
      "groups" => array(
        "placeholders" => array(

          "title" => "Placeholders",
          "icon" => "crop_square",

          "inputs" => array(
            "placeholder" => array(
              "title" => "Cover image fallback",
              "tip" => "Default fallback image for all objects' cover when there are no cover available",
              "col_name" => "placeholder",
              "input" => array(
                "name" => "placeholder"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "placeholder"
                )
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
            "placeholder_bg" => array(
              "title" => "Background image fallback",
              "tip" => "Default fallback image for all objects' background image when there are no local images available",
              "col_name" => "placeholder_bg",
              "input" => array(
                "name" => "placeholder_bg"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "placeholder_bg"
                )
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
            "phu_avatar" => array(
              "title" => "User avatar fallback image",
              "tip" => "Default fallback image for all users' avatar when they don't have an image",
              "col_name" => "phu_avatar",
              "input" => array(
                "name" => "phu_avatar"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "placeholder"
                )
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
            "phu_bg" => array(
              "title" => "User background fallback image",
              "tip" => "Default fallback image for all users' background image when they don't have an image",
              "col_name" => "phu_bg",
              "input" => array(
                "name" => "phu_bg"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "placeholder"
                )
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
      )
    ),
    "vapid" => array(
      "groups" => array(
        "general" => array(

          "title" => "PWA's VAPID",
          "icon" => "settings",

          "inputs" => array(

            "vapid_public" => array(
              "title" => "VAPID Public Key",
              "tip" => "To send push notification to clients that use browser instead of a native app, browsers need to verify your identity. A standard called VAPID can authenticate you for all browsers. You'll need to create and provide a public and private key for your server. These keys must be safely stored and should not change <b style='color:red'>Edit config.php to change this key</b>",
              "input" => array(
                "name" => "vapid_public",
                "type" => "text",
                "value" => vapid_public
              ),
            ),
            "vapid_private" => array(
              "title" => "VAPID Private Key",
              "tip" => "This key is not shown for security measures. <b style='color:red'>Edit config.php to change or see the key</b>",
              "input" => array(
                "name" => "vapid_private",
                "type" => "text",
              ),
            ),
          )
        ),
      )
    ),
    "brand" => array(
      "functions" => array(),
      "groups" => array(
        "brand" => array(

          "title" => "Brand",
          "icon" => "public",

          "inputs" => array(
            "sitename" => array(
              "title" => "Name",
              "tip" => "The name of your brand in full. It will be used in title, emails, seo meta tags and etc",
              "col_name" => "sitename",
              "input" => array(
                "name" => "sitename",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false
                )
              )
            ),
            "shortname" => array(
              "title" => "Shortname",
              "col_name" => "shortname",
              "tip" => "A short name made of English letters only. Used for manifest ( PWA requirement )",
              "input" => array(
                "name" => "shortname",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strict" => true,
                  "strict_regex" => "[a-z]"
                )
              )
            ),
            "logo" => array(
              "title" => "Logo",
              "tip" => "Will be used on <b>black</b> backgrounds",
              "col_name" => "logo",
              "input" => array(
                "name" => "logo"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "logo",
                  "force_localhost" => true
                )
              )
            ),
            "secondary_logo" => array(
              "title" => "Secondary Logo",
              "tip" => "Will be used on <b>white</b> backgrounds. Fallback image is `Logo`",
              "col_name" => "secondary_logo",
              "input" => array(
                "name" => "secondary_logo"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "logo",
                  "force_localhost" => true
                )
              )
            ),
            "mobile_logo" => array(
              "title" => "Mobile Logo",
              "tip" => "Used both in mobile PWA & native apps. Will be used on <b>black</b> backgrounds",
              "col_name" => "mobile_logo",
              "input" => array(
                "name" => "mobile_logo"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "mobile_logo",
                  "force_localhost" => true
                )
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
            "mobile_s_logo" => array(
              "title" => "Mobile Secondary Logo",
              "tip" => "Used both in mobile PWA & native apps. Will be used on <b>white</b> backgrounds",
              "col_name" => "mobile_s_logo",
              "input" => array(
                "name" => "mobile_s_logo"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "mobile_logo",
                  "force_localhost" => true
                )
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
            "icon" => array(
              "title" => "Icon",
              "tip" => "<b>Square only</b>: Image's width & height should be the same. We suggest uploading at least 512x512 px image. Will be used as favicon, PWA & native apps icon",
              "col_name" => "icon",
              "input" => array(
                "name" => "icon"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "icon",
                  "force_localhost" => true
                )
              )
            ),
          )
        ),
        "admin" => array(

          "title" => "Admin-area",
          "icon" => "shield_person",

          "inputs" => array(

            "admin_logo" => array(
              "title" => "Admin Logo",
              "tip" => "The admin logo, displayed on admin's login page and inside admin-area",
              "col_name" => "admin_logo",
              "input" => array(
                "name" => "admin_logo"
              ),
              "bofInput" => array(
                "file",
                array(
                  "type" => "image",
                  "object_type" => "logo",
                  "force_localhost" => true
                )
              )
            ),
            "admin_color" => array(
              "title" => "Admin Main Color",
              "col_name" => "admin_color",
              "input" => array(
                "name" => "admin_color",
                "type" => "bof_input",
              ),
              "bofInput" => array(
                "color",
                array(
                  "toRGB" => true
                )
              )
            ),
            "admin_color2" => array(
              "title" => "Admin Secondary Color",
              "col_name" => "admin_color2",
              "input" => array(
                "name" => "admin_color2",
                "type" => "bof_input",
              ),
              "bofInput" => array(
                "color",
                array(
                  "toRGB" => true
                )
              )
            ),

          )
        ),
        "social" => array(

          "title" => "Socialmedia links",
          "icon" => "share",

          "inputs" => array(

            "facebook" => array(
              "title" => "<i class=\"icon-facebook-sign\"></i> Facebook",
              "col_name" => "sl_facebook",
              "input" => array(
                "name" => "facebook",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "twitter" => array(
              "title" => "<i class=\"icon-twitter-sign\"></i> X",
              "col_name" => "sl_twitter",
              "input" => array(
                "name" => "twitter",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "linkedin" => array(
              "title" => "<i class=\"icon-linkedin-sign\"></i> Linkedin",
              "col_name" => "sl_linkedin",
              "input" => array(
                "name" => "linkedin",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "spotify" => array(
              "title" => "<i class=\"icon-spotify-sign\"></i> Spotify",
              "col_name" => "sl_spotify",
              "input" => array(
                "name" => "spotify",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "soundcloud" => array(
              "title" => "<i class=\"icon-soundcloud-sign\"></i> Soundcloud",
              "col_name" => "sl_soundcloud",
              "input" => array(
                "name" => "soundcloud",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "instagram" => array(
              "title" => "<i class=\"icon-instagram-sign\"></i> Instagram",
              "col_name" => "sl_instagram",
              "input" => array(
                "name" => "instagram",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "youtube" => array(
              "title" => "<i class=\"icon-youtube-sign\"></i> YouTube",
              "col_name" => "sl_youtube",
              "input" => array(
                "name" => "youtube",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

            "tiktok" => array(
              "title" => "<i class=\"icon-tiktok-sign\"></i> TikTok",
              "col_name" => "sl_tiktok",
              "input" => array(
                "name" => "tiktok",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "strip_emoji" => false,
                  "empty()"
                )
              )
            ),

          )
        ),
      )
    ),
    "touch" => array(
      "groups" => array(
        "touch" => array(

          "title" => "Touch",
          "icon" => "touch_app",

          "inputs" => array(
            "tap" => array(
              "title" => "Tap",
              "tip" => "What should happen when users `tap` on an item?",
              "input" => array(
                "name" => "tap",
                "type" => "select",
                "options" => array(
                  [ "visit", "Visit the item's page" ],
                  [ "play", "Play the item" ],
                  [ "menu", "Open the dropdown menu for item" ],
                  [ "nada", "Nothing" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "visit", "play", "menu", "nada" ]
                )
              )
            ),
            "doubletap" => array(
              "title" => "Double Tap",
              "tip" => "What should happen when users `double tap` on an item?",
              "input" => array(
                "name" => "doubletap",
                "type" => "select",
                "options" => array(
                  [ "visit", "Visit the item's page" ],
                  [ "play", "Play the item" ],
                  [ "menu", "Open the dropdown menu for item" ],
                  [ "nada", "Nothing" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "visit", "play", "menu", "nada" ]
                )
              )
            ),
            "hold" => array(
              "title" => "Hold",
              "tip" => "What should happen when users `hold` an item?",
              "input" => array(
                "name" => "hold",
                "type" => "select",
                "options" => array(
                  [ "visit", "Visit the item's page" ],
                  [ "play", "Play the item" ],
                  [ "menu", "Open the dropdown menu for item" ],
                  [ "nada", "Nothing" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "visit", "play", "menu", "nada" ]
                )
              )
            ),
          )
        ),
        "click" => array(

          "title" => "Mouse",
          "icon" => "mouse",

          "inputs" => array(
            "click" => array(
              "title" => "Click",
              "tip" => "What should happen when users `click` on an item?",
              "input" => array(
                "name" => "click",
                "type" => "select",
                "options" => array(
                  [ "visit", "Visit the item's page" ],
                  [ "play", "Play the item" ],
                  [ "menu", "Open the dropdown menu for item" ],
                  [ "nada", "Nothing" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "visit", "play", "menu", "nada" ]
                )
              )
            ),
            "rightclick" => array(
              "title" => "Right Click",
              "tip" => "What should happen when users `right click` on an item?",
              "input" => array(
                "name" => "rightclick",
                "type" => "select",
                "options" => array(
                  [ "visit", "Visit the item's page" ],
                  [ "play", "Play the item" ],
                  [ "menu", "Open the dropdown menu for item" ],
                  [ "nada", "Nothing" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "visit", "play", "menu", "nada" ]
                )
              )
            ),
          )
        ),
      ),
      "functions" => array(
        "ui_pre" => function( $groups ){

          $se = bof()->object->db_setting->get( "touch_setting" );

          $groups["touch"]["inputs"]["tap"]["input"]["value"] = $se["tap"];
          $groups["touch"]["inputs"]["doubletap"]["input"]["value"] = $se["doubletap"];
          $groups["touch"]["inputs"]["hold"]["input"]["value"] = $se["hold"];
          $groups["click"]["inputs"]["click"]["input"]["value"] = $se["click"];
          $groups["click"]["inputs"]["rightclick"]["input"]["value"] = $se["rightclick"];

          return $groups;

        },
        "be_after" => function( $groups, $data ){

          if ( empty( $data["report"]["fail"] ) ){
            bof()->object->db_setting->set( "touch_setting", json_encode( $data["data"] ), "json" );
          }

          return $data;

        }
      )
    ),
    "session" => array(
      "groups" => array(
        "session" => array(

          "title" => "Session",
          "icon" => "fingerprint",

          "inputs" => array(

            "session_ip_lock" => array(
              "title" => "IP Lock",
              "tip" => "Should users get logged out if their IP changes?",
              "col_name" => "session_ip_lock",
              "input" => array(
                "name" => "session_ip_lock",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                )
              )
            ),
            "session_pf_lock" => array(
              "title" => "Platform Lock",
              "tip" => "Should users get logged out if their OS or browser changes?",
              "col_name" => "session_pf_lock",
              "input" => array(
                "name" => "session_pf_lock",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                )
              )
            ),
            "session_max" => array(
              "title" => "Maximum sessions for account",
              "tip" => "How many active sessions can a user have? Enter zero for unlimited",
              "col_name" => "session_max",
              "input" => array(
                "name" => "session_max",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "max" => 99
                )
              )
            ),
            "session_life" => array(
              "title" => "Maximum session life",
              "tip" => "The user will be logged out `Maximum session life` seconds after logging in. Enter zero for unlimited",
              "col_name" => "session_life",
              "input" => array(
                "name" => "session_life",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                )
              )
            ),
            "session_cc" => array(
              "title" => "Session comparison chance",
              "tip" => "On scale of 1 to 100, how often should app run some extra queries to check ip-lock, platform-lock and other limitations?<br>100 means that app will ALWAYS run extra queries to check session while 10 means there is 10% chance per user request for that to happen",
              "col_name" => "session_cc",
              "input" => array(
                "name" => "session_cc",
                "type" => "digit",
                "value" => 10
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "max" => 100
                )
              )
            ),

          )
        ),
        "admin_session" => array(

          "title" => "Admin Session",
          "icon" => "fingerprint",

          "inputs" => array(

            "admin_ip_lock" => array(
              "title" => "IP Lock",
              "tip" => "Should admins/moderators get logged out if their IP changes?",
              "col_name" => "admin_ip_lock",
              "input" => array(
                "name" => "admin_ip_lock",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                )
              )
            ),
            "admin_ua_lock" => array(
              "title" => "Platform Lock",
              "tip" => "Should admins/moderators get logged out if their OS or browser changes?",
              "col_name" => "admin_ua_lock",
              "input" => array(
                "name" => "admin_ua_lock",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                )
              )
            ),
            "admin_nu_lock" => array(
              "title" => "Maximum sessions for account",
              "tip" => "How many active sessions can an admin/moderator account have? Enter zero for unlimited",
              "col_name" => "admin_nu_lock",
              "input" => array(
                "name" => "admin_nu_lock",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "max" => 99
                )
              )
            ),
            "admin_ti_lock" => array(
              "title" => "Maximum session life",
              "tip" => "The admins/moderators will be logged out `Maximum session life` seconds after logging in. Enter zero for unlimited",
              "col_name" => "admin_ti_lock",
              "input" => array(
                "name" => "admin_ti_lock",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                )
              )
            ),

          )
        ),
      )
    ),
    "email" => array(
      "groups" => array(
        "email" => array(

          "title" => "Server",
          "icon" => "email",

          "inputs" => array(

            "ma_from" => array(
              "title" => "From",
              "tip" => "From email. Left empty, script will send emails as noreply@yourdomain",
              "col_name" => "ma_from",
              "input" => array(
                "name" => "ma_from",
                "type" => "text",
              ),
              "validator" => array(
                "email",
                array(
                  "empty()"
                )
              )
            ),

            "ma_server" => array(
              "title" => "Server",
              "col_name" => "ma_server",
              "input" => array(
                "name" => "ma_server",
                "type" => "select_i",
                "options" => array(
                  [ "localhost", "This Server" ],
                  [ "smtp", "SMTP Server" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "localhost", "smtp" ]
                )
              )
            ),

            "ma_s_addr" => array(
              "title" => "SMTP Server Address",
              "col_name" => "ma_s_addr",
              "input" => array(
                "name" => "ma_s_addr",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

            "ma_s_port" => array(
              "title" => "SMTP Server Port",
              "col_name" => "ma_s_port",
              "input" => array(
                "name" => "ma_s_port",
                "type" => "text",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),

            "ms_s_username" => array(
              "title" => "SMTP Username",
              "col_name" => "ms_s_username",
              "input" => array(
                "name" => "ms_s_username",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "ma_s_password" => array(
              "title" => "SMTP Password",
              "col_name" => "ma_s_password",
              "input" => array(
                "name" => "ma_s_password",
                "type" => "text",
              ),
              "validator" => array(
                "password",
                array(
                  "empty()"
                )
              )
            ),
            "ma_s_encrypt" => array(
              "title" => "SMTP Encryption",
              "col_name" => "ma_s_encrypt",
              "tip" => "We highly discourage you from using unsecured SMTP servers",
              "input" => array(
                "name" => "ma_s_encrypt",
                "type" => "select_i",
                "options" => array(
                  [ "tls", "TLS" ],
                  [ "ssl", "SSL" ],
                  [ "none", "None 🚫" ]
                ),
                "value" => "tls"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "tls", "ssl", "none" ]
                )
              )
            ),


          )
        ),
        "setting" => array(

          "title" => "Setting",
          "icon" => "email",

          "inputs" => array(
            "ma_unsub_link" => array(
              "title" => "Unsubscribe link",
              "tip" => "Enable this option to automatically append an 'Unsubscribe' link at the end of the emails. This helps in compliance with regulations such as GDPR, allowing recipients to easily opt out from future communications. A unique link is created and stored for 7 days which users can use to disable all `email` notifications",
              "col_name" => "ma_unsub_link",
              "input" => array(
                "name" => "ma_unsub_link",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "ma_sub_default" => array(
              "title" => "Email subscribe default state",
              "tip" => "Should users be automatically opted into 'email notification' upon signing up? Decide whether they should remain opted in, or uncheck to have users receive emails only after activating email notification",
              "col_name" => "ma_sub_default",
              "input" => array(
                "name" => "ma_sub_default",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
      )
    ),
    "social_login" => array(
      "groups" => array(
        "social_login" => array(

          "title" => "Social login",
          "icon" => "<i class=\"icon-hub\"></i>",

          "inputs" => array(

            "sl" => array(
              "title" => "Enable",
              "col_name" => "sl",
              "tip" => "Enabling allows guests to create an account and login automatically via their social account",
              "input" => array(
                "name" => "sl",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "facebook" => array(

          "title" => "Facebook",
          "icon" => "<i class=\"icon-facebook-sign\"></i>",

          "inputs" => array(

            "sl_fb" => array(
              "title" => "Enable",
              "col_name" => "sl_fb",
              "tip" => "Enabling allows guests to create an account and login automatically via their Facebook account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=facebook</b>",
              "input" => array(
                "name" => "sl_fb",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_fb_id" => array(
              "title" => "App ID",
              "col_name" => "sl_fb_id",
              "input" => array(
                "name" => "sl_fb_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_fb_secret" => array(
              "title" => "App Secret",
              "col_name" => "sl_fb_secret",
              "input" => array(
                "name" => "sl_fb_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "twitter" => array(

          "title" => "X ( Twitter )",
          "icon" => "<i class=\"icon-twitter-sign\"></i>",

          "inputs" => array(

            "sl_tw" => array(
              "title" => "Enable",
              "col_name" => "sl_tw",
              "tip" => "Enabling allows guests to create an account and login automatically via their X account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=twitter</b>",
              "input" => array(
                "name" => "sl_tw",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_tw_id" => array(
              "title" => "ID",
              "col_name" => "sl_tw_id",
              "input" => array(
                "name" => "sl_tw_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_tw_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_tw_secret",
              "input" => array(
                "name" => "sl_tw_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "instagram" => array(

          "title" => "Instagram",
          "icon" => "<i class=\"icon-instagram-sign\"></i>",

          "inputs" => array(

            "sl_ig" => array(
              "title" => "Enable",
              "col_name" => "sl_ig",
              "tip" => "Enabling allows guests to create an account and login automatically via their Instagram account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=instagram</b>",
              "input" => array(
                "name" => "sl_ig",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_ig_id" => array(
              "title" => "ID",
              "col_name" => "sl_ig_id",
              "input" => array(
                "name" => "sl_ig_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_ig_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_ig_secret",
              "input" => array(
                "name" => "sl_ig_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "google" => array(

          "title" => "Google",
          "icon" => "<i class=\"icon-googleplus-sign\"></i>",

          "inputs" => array(

            "sl_gg" => array(
              "title" => "Enable",
              "col_name" => "sl_gg",
              "tip" => "Enabling allows guests to create an account and login automatically via their Google account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=google</b>",
              "input" => array(
                "name" => "sl_gg",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_gg_id" => array(
              "title" => "ID",
              "col_name" => "sl_gg_id",
              "input" => array(
                "name" => "sl_gg_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_gg_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_gg_secret",
              "input" => array(
                "name" => "sl_gg_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_gg_off" => array(
              "title" => "Follow Google's branding guidelines",
              "col_name" => "sl_gg_off",
              "tip" => "If enabled, the Google sign-in button will use a different style and follow Google's branding guidelines",
              "input" => array(
                "name" => "sl_gg_off",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_gg_extra" => array(
              "title" => "Sync Youtube-videos likes",
              "col_name" => "sl_gg_extra",
              "tip" => "If this option is enabled && user has logged-in using their Google-account && they gave your Google app enough access && they like a Youtube video -> script will like the Youtube video on your website and Youtube simultaneously",
              "input" => array(
                "name" => "sl_gg_extra",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "dribbble" => array(

          "title" => "Dribbble",
          "icon" => "<i class=\"icon-dribbble-sign\"></i>",

          "inputs" => array(

            "sl_dr" => array(
              "title" => "Enable",
              "col_name" => "sl_dr",
              "tip" => "Enabling allows guests to create an account and login automatically via their Dribbble account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=dribbble</b>",
              "input" => array(
                "name" => "sl_dr",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_dr_id" => array(
              "title" => "ID",
              "col_name" => "sl_dr_id",
              "input" => array(
                "name" => "sl_dr_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_dr_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_dr_secret",
              "input" => array(
                "name" => "sl_dr_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "github" => array(

          "title" => "Github",
          "icon" => "<i class=\"icon-github-sign\"></i>",

          "inputs" => array(

            "sl_gh" => array(
              "title" => "Enable",
              "col_name" => "sl_gh",
              "tip" => "Enabling allows guests to create an account and login automatically via their Github account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=github</b>",
              "input" => array(
                "name" => "sl_gh",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_gh_id" => array(
              "title" => "ID",
              "col_name" => "sl_gh_id",
              "input" => array(
                "name" => "sl_gh_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_gh_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_gh_secret",
              "input" => array(
                "name" => "sl_gh_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "spotify" => array(

          "title" => "Spotify",
          "icon" => "<i class=\"icon-spotify-sign\"></i>",

          "inputs" => array(

            "sl_sp" => array(
              "title" => "Enable",
              "col_name" => "sl_sp",
              "tip" => "Enabling allows guests to create an account and login automatically via their Spotify account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=spotify</b>",
              "input" => array(
                "name" => "sl_sp",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_sp_id" => array(
              "title" => "ID",
              "col_name" => "sl_sp_id",
              "input" => array(
                "name" => "sl_sp_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_sp_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_sp_secret",
              "input" => array(
                "name" => "sl_sp_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "linkedin" => array(

          "title" => "Linkedin",
          "icon" => "<i class=\"icon-linkedin-sign\"></i>",

          "inputs" => array(

            "sl_li" => array(
              "title" => "Enable",
              "col_name" => "sl_li",
              "tip" => "Enabling allows guests to create an account and login automatically via their Linkedin account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=linkedin</b>",
              "input" => array(
                "name" => "sl_li",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_li_id" => array(
              "title" => "ID",
              "col_name" => "sl_li_id",
              "input" => array(
                "name" => "sl_li_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_li_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_li_secret",
              "input" => array(
                "name" => "sl_li_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "disqus" => array(

          "title" => "Disqus",
          "icon" => "<i class=\"icon-disqus-sign\"></i>",

          "inputs" => array(

            "sl_dq" => array(
              "title" => "Enable",
              "col_name" => "sl_dq",
              "tip" => "Enabling allows guests to create an account and login automatically via their Disqus account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=disqus</b>",
              "input" => array(
                "name" => "sl_dq",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_dq_id" => array(
              "title" => "ID",
              "col_name" => "sl_dq_id",
              "input" => array(
                "name" => "sl_dq_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_dq_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_dq_secret",
              "input" => array(
                "name" => "sl_dq_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "reddit" => array(

          "title" => "Reddit",
          "icon" => "<i class=\"icon-reddit-sign\"></i>",

          "inputs" => array(

            "sl_rd" => array(
              "title" => "Enable",
              "col_name" => "sl_rd",
              "tip" => "Enabling allows guests to create an account and login automatically via their Reddit account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=reddit</b>",
              "input" => array(
                "name" => "sl_rd",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_rd_id" => array(
              "title" => "ID",
              "col_name" => "sl_rd_id",
              "input" => array(
                "name" => "sl_rd_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_rd_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_rd_secret",
              "input" => array(
                "name" => "sl_rd_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "twitch" => array(

          "title" => "Twitch",
          "icon" => "public",

          "inputs" => array(

            "sl_tt" => array(
              "title" => "Enable",
              "col_name" => "sl_tt",
              "tip" => "Enabling allows guests to create an account and login automatically via their Twitch account. Redirect URI is: <b>" . web_address . "api/login_social_ini?target=twitch</b>",
              "input" => array(
                "name" => "sl_tt",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "sl_tt_id" => array(
              "title" => "ID",
              "col_name" => "sl_tt_id",
              "input" => array(
                "name" => "sl_tt_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "sl_tt_secret" => array(
              "title" => "Secret",
              "col_name" => "sl_tt_secret",
              "input" => array(
                "name" => "sl_tt_secret",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
      )
    ),
    "cli" => array(
      "functions" => array(
        "ui_pre" => function( $groups ){

          $cookie_path = str_replace( "\\", "/", base_root ) . "/files/protected/yt_cookies.php";

          if ( is_file( $cookie_path ) ){
            $cookie_check_txt = "<b style='color:rgb(var(--c_green))'>yt_cookies.php is uploaded. It will be used in yt-dlp</b>";
          } else {
            $cookie_check_txt = "<b style='color:rgb(var(--c_red))'>yt_cookies.php is NOT uploaded</b>";
          }

          $groups["youtube_dl"]["inputs"]["ut_youtubedl_path"]["tip"] .= "<br><br><b>You can export your YouTube cookies ( from your browser, using available plugins ) and pass them to your RKHM's yt-dlp to bypass YouTube's restriction. In order to do so, rename your cookie file to yt_cookies.php and upload it to `{$cookie_path}`</b>. <a href='https://github.com/yt-dlp/yt-dlp/wiki/FAQ#how-do-i-pass-cookies-to-yt-dlp' target='_blank'> Read more here</a><br><br>{$cookie_check_txt}";

          return $groups;
        },
      ),
      "groups" => array(

        "ffmpeg" => array(
          "title" => "FFmpeg",
          "icon" => "<span class=\"material-icons-outlined\">build</span>",
          "inputs" => array(
            "ffmpeg_enabled" => array(
              "title" => "Enable",
              "col_name" => "ffmpeg_enabled",
              "tip" => "FFmpeg can be used to convert non-mp3 & non-mp4 files to usable extensions.<br>It can be used to convert original files to lower-qualities.<br>It can be used to encrypt your files in order to protect them with HLS.<br>It can be used to make preview of premium files.<br>It can be used to make waveforms out of audio files.<br>It can be used to make thumbnails/previews for video files.<br><b style='color:red'>Do not enable without installing & testing FFmpeg first!</b>",
              "input" => array(
                "name" => "ffmpeg_enabled",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "ffmpeg_path" => array(
              "title" => "Absolute/Shell path",
              "col_name" => "ffmpeg_path",
              "tip" => "<b>Fastest</b>. If you have FFmpeg installed on your server, you can define the shell or absolute path here. Test the path & FFmpeg itself before entering it here <div class='btn btn-primary' id='ffmpeg_test'>Test</div> Possible paths:<br><br><ul id='p_ps'><li>ffmpeg</li><li>/usr/bin/ffmpeg</li><li>/usr/local/bin/ffmpeg</li><li>/usr/share/ffmpeg</li><li>/opt/local/bin/ffmpeg</li><li>/opt/homebrew/bin/ffmpeg</li><li>C:\\Program Files\\FFmpeg\\bin\\ffmpeg.exe</li><li>/snap/bin/ffpmeg</li></ul><br><br>",
              "input" => array(
                "name" => "ffmpeg_path",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
									"strict" => true,
									"strict_regex" => "[a-zA-Z0-9_.\-\/\:\\\ ]"
                )
              )
            ),
            "ffmpeg_static" => array(
              "title" => "Use 'FFmpeg Static Builds'",
              "col_name" => "ffmpeg_static",
              "tip" => "<b>Fast</b>. If enabled, app will use <a href='https://www.johnvansickle.com/ffmpeg/' target='_blank'>FFmpeg Static Builds</a> which doesn't require 'installation'. There is a high chance that this can handle all of your FFmpeg-related processing without any further steps but if you have access to the server, you can install the official, dynamic verion of FFmpeg and use that. <div class='btn btn-primary' id='ffmpeg_static_test'>Test</div> <b style='color:red'>Make sure to disable static-build checkbox if you are using installed version on your server</b>",
              "input" => array(
                "name" => "ffmpeg_static",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "youtube_dl" => array(
					"title" => "youtube-dl",
					"icon" => "download",
					"inputs" => array(
						"ut_youtubedl_path" => array(
							"title" => "<a href='https://youtube-dl.org/' target='_blank'>youtube-dl</a> local path",
							"tip" => "Enter the path of <a href='https://youtube-dl.org/' target='_blank'>Youtube-dl</a> which is already installed on your server and is fully tested. You can also use <a href='https://github.com/yt-dlp/yt-dlp' target='_blank'>yt-dlp</a> instead of youtube-dl for much better download speed <a id='yt_test' class='btn btn-primary'>Test</a> Possible locations for yt-dlp, replace yt-dlp with youtube-dl for youtube-dl:<br><br><ul id='p_ps'><li>yt-dlp</li><li>/usr/bin/yt-dlp</li><li>/usr/local/bin/yt-dlp</li><li>/usr/share/yt-dlp</li><li>/opt/local/bin/yt-dlp</li><li>/opt/homebrew/bin/yt-dlp</li><li>C:\\Program Files\\yt-dlp\\bin\\yt-dlp.exe</li><li>/snap/bin/ffpmeg</li></ul>",
							"col_name" => "ut_youtubedl_path",
							"input" => array(
								"name" => "ut_youtubedl_path",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
									"strict" => true,
									"strict_regex" => "[a-zA-Z0-9_.\-\/\:\\\ ]"
								)
							)
						),
						"ut_youtubedl_proxy" => array(
							"title" => "<a href='https://youtube-dl.org/' target='_blank'>youtube-dl</a> proxy",
							"tip" => "If you wish to download through a proxy, enter it in type://username:password@ip:port . Example: http://4.4.4.4:99 or socks5://username:password@123.43.52.34:88",
							"col_name" => "ut_youtubedl_proxy",
							"input" => array(
								"name" => "ut_youtubedl_proxy",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
								)
							)
						),
					),
          
				),
        "webp" => array(
					"title" => "webp",
					"tip" => "RKHM can use <a href='https://github.com/spatie/image-optimizer' target='_blank'>spatie/image-optimizer</a> to create a webp version of your images ( and their resized versions ) to save traffic. <b style='color:red'>Make sure you have installed <a href='https://github.com/spatie/image-optimizer' target='_blank'>spatie/image-optimizer</a> required binaries on your server or webp version will be bigger than original one!</b>",
					"icon" => "palette",
					"inputs" => array(
            "fs_image_webp" => array(
              "tip" => "Convert to webp",
              "title" => "Make webp of all uploaded images",
              "col_name" => "fs_image_webp",
              "input" => array(
                "name"  => "fs_image_webp",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
					)
				),
        "nodejs" => array(
					"title" => "Node.js",
          "tip" => "Node.js is a JavaScript runtime built on Chrome's V8 engine, enabling server-side scripting, asynchronous programming, and scalable applications",

					"icon" => "javascript",
					"inputs" => array(
						"nodejs_path" => array(
							"title" => "Node.js local path",
							"tip" => "Enter the path of Node.js AFTER installing & testing it. <a id='nodejs_test' class='btn btn-primary'>Test</a> Possible locations for node:<br><br><ul id='p_ps'><li>node</li><li>/usr/bin/node</li><li>/usr/local/bin/node</li><li>/usr/share/node</li><li>/opt/local/bin/node</li><li>/opt/homebrew/bin/node</li><li>/snap/bin/node</li></ul>",
							"col_name" => "nodejs_path",
							"input" => array(
								"name" => "nodejs_path",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
									"strict" => true,
									"strict_regex" => "[a-zA-Z0-9_.\-\/\:\\\ ]"
								)
							)
						),
					)
				),

      )
    ),
    "player" => array(

      "functions" => array(
        "be_after" => function( $groups, $inputs ){

          $_ms = [];
          foreach( [ 
            "muse_hide", "muse_hide_yt", "muse_rec_thres",
            "queue_hide_infinite", "queue_hide", "queue_disable_auto", "queue_hide_lyrics", "queue_save",
            "ad_offset", "ad_interval", "ad_skippability", "ad_skippability_threshold"
            ] as $_k ){
            $_ms[ $_k ] = $inputs["data"][ $_k ];
          }

          bof()->object->db_setting->set( "muse_setting", json_encode( $_ms ), "json" );

          return $inputs;

        }
      ),
      "groups" => array(

        "player" => array(
          "title" => "Player",
          "icon" => "<span class=\"material-icons-outlined\">smart_display</span>",
          "inputs" => array(
            "muse_available_sources" => array(
              "title" => "Supported Sources",
              "col_name" => "muse_available_sources",
              "tip" => "",
              "input" => array(
                "name" => "muse_available_sources",
                "type" => "select_m",
                "options" => array(
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [  ]
                )
              )
            ),
            "download_available_sources" => array(
              "title" => "Download Supported Sources",
              "col_name" => "download_available_sources",
              "tip" => "",
              "input" => array(
                "name" => "download_available_sources",
                "type" => "select_m",
                "options" => array(
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [  ]
                )
              )
            ),
            "muse_hide" => array(
              "title" => "Hide player",
              "tip" => "Player will be as minimal as possible and users can only click on it to pause/play. On mobile devices, the player is completely hidden and users can control the audio with their build-in device media buttons",
              "input" => array(
                "name" => "muse_hide",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "muse_hide_yt" => array(
              "title" => "Hide YouTube's frame",
              "tip" => "Disclaimer: Read YouTube's term of usage before enabling this option",
              "input" => array(
                "name" => "muse_hide_yt",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "muse_embedable" => array(
              "title" => "Embedable on share",
              "col_name" => "muse_embedable",
              "input" => array(
                "name" => "muse_embedable",
                "type" => "checkbox",
                "value" => 1
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "muse_rec_thres" => array(
              "title" => "Record Threshold",
              "tip" => "Set the minimum duration (in seconds) a user must play a song for it to be counted as a play. For example, if the threshold is set to 10 seconds, the song must be played for at least 10 seconds before it is reported as \"played\" and its play count is increased",
              "col_name" => "muse_rec_thres",
              "input" => array(
                "name" => "muse_rec_thres",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                )
              )
            ),
          ),
        ),
        "queue" => array(
          "title" => "Queue",
          "tip" => "Queue refers to the pop-up page utilized for previewing item's image/video, viewing lyrics, checking the queue list, Infinite list, and more",
          "icon" => "<span class=\"material-icons-outlined\">smart_display</span>",
          "inputs" => array(
            "queue_hide" => array(
              "title" => "Hide Queue",
              "input" => array(
                "name" => "queue_hide",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "queue_disable_auto" => array(
              "title" => "Disable auto-open",
              "tip" => "If checked, queue will not open automatically when user plays an item",
              "input" => array(
                "name" => "queue_disable_auto",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "queue_hide_lyrics" => array(
              "title" => "Hide Lyrics tab",
              "input" => array(
                "name" => "queue_hide_lyrics",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "queue_hide_infinite" => array(
              "title" => "Hide Infinite tab",
              "input" => array(
                "name" => "queue_hide_infinite",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
            "queue_save" => array(
              "title" => "Save queue & load on page reload",
              "tip" => "Choose if queue should be saved in browser's cache for logged in users or not. Guests can't have browser cache for queue yet",
              "input" => array(
                "name" => "queue_save",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => 1
                )
              )
            ),
          ),
        ),
        "ads" => array(
          "title" => "Advertisement",
          "tip" => "Audio, video, and everything else that disrupts the user's playback experience!",
          "icon" => "<span class=\"material-icons-outlined\">ad</span>",
          "inputs" => array(
            "ad_offset" => array(
              "title" => "Start Time (Offset)",
              "tip" => "Configure when ads should start playing for new visitors. Enter 0 to play ads immediately when a new visitor starts playing an item, or enter a number (in minutes) to allow new visitors to listen ad-free for that duration. For example, entering 5 will give new visitors 5 minutes of uninterrupted listening before ads begin ( and repeat after *interval* minute(s) )",
              "col_name" => "ad_offset",
              "input" => array(
                "name" => "ad_offset",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "forceZero" => true
                )
              )
            ),
            "ad_interval" => array(
              "title" => "Frequency (Interval)",
              "tip" => "Define how often ads should play. Enter a number (in minutes) to determine the time between ads. For instance, if you enter 5, users will get an ad after every 5 minutes of playback. Ads won’t interrupt a track if it finishes before the interval. For shorter tracks, ads will play before the next one if the interval is reached",
              "col_name" => "ad_interval",
              "input" => array(
                "name" => "ad_interval",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "forceZero" => true
                )
              )
            ),
            "ad_skippability" => array(
              "title" => "Skippability",
              "tip" => "Decide whether users can skip ads after a certain amount of time. Enable this option to allow skipping after the threshold you set",
              "col_name" => "ad_skippability",
              "input" => array(
                "name" => "ad_skippability",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                )
              )
            ),
            "ad_skippability_threshold" => array(
              "title" => "Skippability Threshold",
              "tip" => "Set the time (in seconds) after which users can skip ads. For example, entering 10 allows users to skip the ad after 10 seconds of viewing",
              "col_name" => "ad_skippability_threshold",
              "input" => array(
                "name" => "ad_skippability_threshold",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 0,
                  "forceZero" => true
                )
              )
            ),
          ),
        ),
        "rep_limits" => array(
          "title" => "Report Limits",
          "tip" => "RKHM's players can automatically report broken YouTube videos, radio stations, and more back to your server. Here, you can set limits on the number of reports accepted from each user to prevent abuse",
          "icon" => "block",
          "inputs" => array(
            "rep_lim_ui" => array(
              "title" => "Max Reports per User per Item",
              "tip" => "Set the maximum number of times a logged-in user can report the same item. This helps prevent abuse by limiting excessive reports on the same item by the same user",
              "col_name" => "rep_lim_ui",
              "input" => array(
                "name" => "rep_lim_ui",
                "type" => "digit",
                "value" => 1
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1,
                  "max" => 999999
                )
              )
            ),
            "rep_lim_ii" => array(
              "title" => "Max Reports per IP per Item",
              "tip" => "Define the maximum number of times an item can be reported from the same IP address. This ensures that even if multiple users share an IP, the reporting limit is controlled",
              "col_name" => "rep_lim_ii",
              "input" => array(
                "name" => "rep_lim_ii",
                "type" => "digit",
                "value" => 1
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1,
                  "max" => 999999
                )
              )
            ),
            "rep_lim_id" => array(
              "title" => "Max Reports per IP per Day",
              "tip" => "Specify the maximum number of unique items that can be reported from the same IP address in a single day. This helps prevent abuse from a single IP address reporting an excessive number of items within a short period",
              "col_name" => "rep_lim_id",
              "input" => array(
                "name" => "rep_lim_id",
                "type" => "digit",
                "value" => 10
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1,
                  "max" => 999999
                )
              )
            ),
          )
        ),

      ),

    ),
    "theme" => array(

      "functions" => array(),

      "groups" => array(
        "menus" => array(
          "title" => "Menus",
          "icon" => "menu",
          "inputs" => array(
            "menu_p_desk" => array(
              "title" => "Desktop",
              "tip" => "Select the menu that should be served for desktop users",
              "bofInput" => array(
                "object",
                array(
                  "type" => "menu"
                )
              ),
              "input" => array(
                "name" => "menu_p_desk"
              )
            ),
            "menu_p_mob" => array(
              "title" => "Mobile",
              "tip" => "Select the menu that should be served for mobile users",
              "bofInput" => array(
                "object",
                array(
                  "type" => "menu"
                )
              ),
              "input" => array(
                "name" => "menu_p_mob"
              )
            ),
            "menu_p_footer" => array(
              "title" => "Footer",
              "tip" => "Select the menu that should be displayed on bottom of all pages as footer",
              "bofInput" => array(
                "object",
                array(
                  "type" => "menu"
                )
              ),
              "input" => array(
                "name" => "menu_p_footer"
              ),
              "validator" => [ "int", [ "empty()" ] ]
            ),
          )
        ),
        "font" => array(
          "title" => "Font setting",
          "icon" => "text_fields",
          "inputs" => array(
            "font_name" => array(
              "title" => "Name",
              "tip" => "Name of Google Font. You can see the list <a href='https://fonts.google.com/' target='_blank'>Here</a>",
              "col_name" => "font_name",
              "input" => array(
                "name" => "font_name",
                "type" => "select",
                "options" => []
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => []
                )
              )
            ),
          )
        ),
        "color" => array(
          "title" => "Color setting",
          "icon" => "palette",
          "inputs" => array(
            "theme_color" => array(
              "title" => "Theme color",
              "tip" => "Main color",
              "col_name" => "theme_color",
              "input" => array(
                "name" => "theme_color",
                "type" => "bof_input",
              ),
              "bofInput" => array(
                "color",
                array(
                  "toRGB" => true
                )
              )
            ),
          )
        ),
        "js" => array(
          "title" => "JavaScript",
          "icon" => "javascript",
          "inputs" => array(
            "custom_js" => array(
              "title" => "Code",
              "tip" => "Insert your custom javascript code here",
              "col_name" => "custom_js",
              "input" => array(
                "name" => "custom_js",
                "type" => "textarea",
              ),
              "validator" => array(
                "raw",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "header" => array(
          "title" => "Header buttons",
          "icon" => "smart_button",
          "inputs" => array(
            "upload_button" => array(
              "title" => "Upload button",
              "tip" => "When should this button get displayed",
              "col_name" => "upload_button",
              "input" => array(
                "name" => "upload_button",
                "type" => "select_i",
                "options" => array(
                  [ "never", "Never" ],
                  [ "onuse", "When user have access to upload" ],
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "never", "onuse" ]
                )
              )
            ),
            "upgrade_button" => array(
              "title" => "Upgrade button",
              "tip" => "When should this button get displayed",
              "col_name" => "upgrade_button",
              "input" => array(
                "name" => "upgrade_button",
                "type" => "select_i",
                "options" => array(
                  [ "never", "Never" ],
                  [ "onuse", "When user is not already subscribed" ],
                  [ "always", "Always" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "never", "always", "onuse" ]
                )
              )
            ),
            "offline_download_button" => array(
              "title" => "In-App download button",
              "tip" => "When should this button get displayed",
              "col_name" => "offline_download_button",
              "input" => array(
                "name" => "offline_download_button",
                "type" => "select_i",
                "options" => array(
                  [ "onuse", "When user have downloaded an item" ],
                  [ "always", "Always" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "always", "onuse" ]
                )
              )
            ),
          )
        ),
        "footer" => array(
          "title" => "Footer",
          "icon" => "smart_button",
          "inputs" => array(
            "footer_sign" => array(
              "title" => "Footer signature text",
              "col_name" => "footer_sign",
              "input" => array(
                "name" => "footer_sign",
                "type" => "textarea",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                  "strip_emoji" => false
                )
              )
            ),
          )
        ),
        "other" => array(
          "title" => "Other",
          "icon" => "smart_button",
          "inputs" => array(
            "default_body_class" => array(
              "title" => "Default body classes",
              "col_name" => "default_body_class",
              "input" => array(
                "name" => "default_body_class",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                )
              )
            ),
          )
        ),
      )

    ),
    "ads" => array(

      "functions" => array(),

      "groups" => array(
        "google" => array(
          "title" => "Google",
          "icon" => "store",
          "inputs" => array(
            "ads_google_auto_code" => array(
              "title" => "Google AdSense Code",
              "tip" => 'If you want to display Google Adsense in your website with "Auto Ads" enabled, paste your Google Adsense code here. If you wish to use Google adsense "Ads Unit" instead, use Admin -> Business -> Advertisement -> Campaigns -> New item -> JavaScript',
              "col_name" => "ads_google_auto_code",
              "input" => array(
                "name" => "ads_google_auto_code",
                "type" => "textarea",
              ),
              "validator" => array(
                "raw",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "banner" => array(
          "title" => "Banner",
          "icon" => "image",
          "inputs" => array(
            "ads_banner_v_f" => array(
              "title" => "Impression cost",
              "tip" => "How much do you want to charge everytime you show a banner ad to a visitor?",
              "col_name" => "ads_banner_v_f",
              "input" => array(
                "name" => "ads_banner_v_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
            "ads_banner_c_f" => array(
              "title" => "Click cost",
              "tip" => "How much do you want to charge everytime a banner ad gets clicked by a visitor?",
              "col_name" => "ads_banner_c_f",
              "input" => array(
                "name" => "ads_banner_c_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
          )
        ),
        "audio" => array(
          "title" => "Audio",
          "icon" => "play_circle",
          "inputs" => array(
            "ads_audio_v_f" => array(
              "title" => "Impression cost",
              "tip" => "How much do you want to charge everytime you play an audio ad for a visitor?",
              "col_name" => "ads_audio_v_f",
              "input" => array(
                "name" => "ads_audio_v_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
            "ads_audio_c_f" => array(
              "title" => "Click cost",
              "tip" => "How much do you want to charge everytime a banner ad gets clicked by a visitor?",
              "col_name" => "ads_audio_c_f",
              "input" => array(
                "name" => "ads_audio_c_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
          )
        ),
        "video" => array(
          "title" => "Video",
          "icon" => "videocam",
          "inputs" => array(
            "ads_video_v_f" => array(
              "title" => "Impression cost",
              "tip" => "How much do you want to charge everytime you play a video ad for a visitor?",
              "col_name" => "ads_video_v_f",
              "input" => array(
                "name" => "ads_video_v_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
            "ads_video_c_f" => array(
              "title" => "Click cost",
              "tip" => "How much do you want to charge everytime a banner ad gets clicked by a visitor?",
              "col_name" => "ads_video_c_f",
              "input" => array(
                "name" => "ads_video_c_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
          )
        ),
        "youtube" => array(
          "title" => "YouTube",
          "icon" => "youtube_activity",
          "inputs" => array(
            "ads_youtube_v_f" => array(
              "title" => "Impression cost",
              "tip" => "How much do you want to charge everytime you play a youtube ad for a visitor?",
              "col_name" => "ads_youtube_v_f",
              "input" => array(
                "name" => "ads_youtube_v_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
            "ads_youtube_c_f" => array(
              "title" => "Click cost",
              "tip" => "How much do you want to charge everytime a banner ad gets clicked by a visitor?",
              "col_name" => "ads_youtube_c_f",
              "input" => array(
                "name" => "ads_youtube_c_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
          )
        ),
        "script" => array(
          "title" => "Script",
          "icon" => "code",
          "inputs" => array(
            "ads_script_v_f" => array(
              "title" => "Impression cost",
              "tip" => "How much do you want to charge everytime you run the script",
              "col_name" => "ads_script_v_f",
              "input" => array(
                "name" => "ads_script_v_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
          )
        ),
        "popup" => array(
          "title" => "PopUp",
          "icon" => "campaign",
          "inputs" => array(
            "ads_popup_v_f" => array(
              "title" => "Impression cost",
              "tip" => "How much do you want to charge everytime you show a popup to a visitor?",
              "col_name" => "ads_popup_v_f",
              "input" => array(
                "name" => "ads_popup_v_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
            "ads_popup_c_f" => array(
              "title" => "Click cost",
              "tip" => "How much do you want to charge everytime a popup ad gets clicked by a visitor?",
              "col_name" => "ads_popup_c_f",
              "input" => array(
                "name" => "ads_popup_c_f",
              ),
              "bofInput" => array(
                "currency",
                []
              ),
            ),
          )
        ),
      )

    ),
    "cronjob" => array(

      "functions" => array(
        "ui_pre" => function( $groups ){

          $tables = bof()->cronjob->_clean_database_get_map();
          $db_map = bof()->object->db_setting->get( "crond_clean_map", [] );
          $db_setting = bof()->object->db_setting->get( "crond_setting_map", [] );

          $tables_html = [];
          foreach( $tables as $tableName => $tableArgs ){

            $tableActive = $db_setting ? ( in_array( $tableName, array_keys( $db_setting ), true ) ? $db_setting[ $tableName ] : true ) : true;

            $tableJobs = [ "Optimize" ];

            if ( !empty( $tableArgs["truncate"] ) )
            $tableJobs[] = "Truncate: every {$tableArgs["truncate"]} hour";

            if ( !empty( $tableArgs["remove_selectors"] ) )
            $tableJobs[] = "Clean: <rules>" . json_encode( $tableArgs["remove_selectors"] ) . "</rules>";

            $tables_html[] = "<div class='table_cleaner_wrapper'>
              <div class='input_wrapper'>
                <div class='checkbox_wrapper'>
                  <input type='checkbox' class='bof_input' name='crond_table_{$tableName}' ".($tableActive?"checked='checked'":"").">
                  <span class='checkbox_mask'><span></span></span>
                </div>
              </div>
              <name>{$tableName}</name>
              ".implode(", ",$tableJobs)."
            </div>";

          }

          $groups["db_cleaner_group"]["inputs"]["crond_db_tables"]["html"] = "<div class='crond_tables_wrapper'><btitle>Tables</btitle>".implode( "", $tables_html )."</div>";


          $jobs = bof()->cronjob->get_jobs();
          if ( !empty( $jobs ) ){

            $groups["crond_schedule"] = array(
              "title" => "Schedule",
              "tip" => "Cronjobs will be executed every day of week, unless selected here",
              "icon" => "schedule",
              "inputs" => []
            );

            if ( $jobs ){
              foreach( $jobs as $jID => $job ){
                $jobSchedule = bof()->object->cronjob->get_schedule( $jID );
                $groups["crond_schedule"]["inputs"]["cd_{$jID}"] = array(
                  "col_name" => "cd_{$jID}",
                  "col_name_skip_load" => true,
                  "title" => $job["title"],
                  "input" => array(
                    "type" => "select_m",
                    "name" => "cd_{$jID}",
                    "options" => array(
                      [ "d0", "Sunday" ],
                      [ "d1", "Monday" ],
                      [ "d2", "Tuesday" ],
                      [ "d3", "Wednesday" ],
                      [ "d4", "Thursday" ],
                      [ "d5", "Friday" ],
                      [ "d6", "Saturday" ],
                    ),
                    "value" => $jobSchedule,
                  ),
                  "validator" => array(
                    "in_array",
                    array(
                      "values" => [ "d0", "d1", "d2", "d3", "d4", "d5", "d6" ]
                    )
                  )
                );
              }
            }

          }

          return $groups;

        },
        "be_pre" => function( $groups ){

          $tables = bof()->cronjob->_clean_database_get_map();
          $tableSetting = [];
          foreach( $tables as $tableName => $tableArgs ){
            $tableSetting[ $tableName ] = bof()->nest->user_input( "post", "crond_table_{$tableName}", "boolean", [ "empty()" => true ] );
          }

          bof()->object->db_setting->set( "crond_setting_map", json_encode( $tableSetting ), "json" );

          $jobs = bof()->cronjob->get_jobs();
          if ( !empty( $jobs ) ){

            $groups["crond_schedule"] = array(
              "title" => "Schedule",
              "tip" => "Cronjobs will be executed every day of week, unless selected here",
              "icon" => "schedule",
              "inputs" => []
            );

            if ( $jobs ){
              foreach( $jobs as $jID => $job ){
                $jobSchedule = bof()->object->cronjob->get_schedule( $jID );
                $groups["crond_schedule"]["inputs"]["cd_{$jID}"] = array(
                  "col_name" => "cd_{$jID}",
                  "col_name_skip_load" => true,
                  "title" => $job["title"],
                  "input" => array(
                    "name" => "cd_{$jID}",
                    "type" => "select_m",
                    "options" => array(
                      [ "d0", "Sunday" ],
                      [ "d1", "Monday" ],
                      [ "d2", "Tuesday" ],
                      [ "d3", "Wednesday" ],
                      [ "d4", "Thursday" ],
                      [ "d5", "Friday" ],
                      [ "d6", "Saturday" ],
                    ),
                    "value" => $jobSchedule,
                  ),
                  "validator" => array(
                    "in_array",
                    array(
                      "values" => [ "d0", "d1", "d2", "d3", "d4", "d5", "d6" ]
                    )
                  )
                );
              }
            }

          }

          return $groups;

        }
      ),

      "groups" => array(

        "crond_group" => array(
          "title" => "Cronjob",
          "icon" => "smart_toy",
          "inputs" => array(
            "crond" => array(
              "col_name" => "crond",
              "title" => "Enable",
              "input" => array(
                "name"  => "crond",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()",
                  "int" => true
                )
              )
            ),
            "crond_interval" => array(
              "col_name" => "crond_interval",
              "title" => "Execution interval",
              "tip" => "How often cronjob is getting executed?",
              "input" => array(
                "name"  => "crond_interval",
                "type"  => "select_i",
                "value" => "1",
                "options" => array(
                  [ "1", "Once per minute" ],
                  [ "5", "Once per 5 minute" ],
                  [ "30", "Once per 30 minute" ],
                  [ "60", "Once per hour" ],
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "empty()",
                  "values" => [ "1", "5", "30", "60" ]
                )
              )
            ),
          )
        ),
        "db_cleaner_group" => array(
          "title" => "Database Cleaner",
          "icon" => "dns",
          "inputs" => array(
            "crond_db_cleaner" => array(
              "col_name" => "crond_db_cleaner",
              "title" => "Enable",
              "input" => array(
                "name"  => "crond_db_cleaner",
                "type"  => "checkbox",
                "value" => true,
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "crond_db_tables" => array(
              "html" => ""
            )
          )
        ),
        "crond_hd_cleaner" => array(
          "title" => "File Cleaner",
          "icon" => "dns",
          "inputs" => array(
            "crond_hd_cleaner" => array(
              "col_name" => "crond_hd_cleaner",
              "title" => "Enable",
              "input" => array(
                "name"  => "crond_hd_cleaner",
                "type"  => "checkbox",
                "value" => true,
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "crond_royalty_payer" => array(
          "title" => "Royalty stream payer",
          "icon" => "payments",
          "inputs" => array(
            "crond_royalty_payer" => array(
              "col_name" => "crond_royalty_payer",
              "title" => "Enable",
              "input" => array(
                "name"  => "crond_royalty_payer",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "crond_schedule" => array(
          "title" => "Schedule",
          "tip" => "Cronjobs will be executed every day of week, unless selected here",
          "icon" => "schedule",
          "inputs" => array(
            "crond_day0" => array(
              "col_name" => "crond_day0",
              "title" => "Sunday",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
            "crond_day1" => array(
              "col_name" => "crond_day1",
              "title" => "",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
            "crond_day2" => array(
              "col_name" => "crond_day2",
              "title" => "",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
            "crond_day3" => array(
              "col_name" => "crond_day3",
              "title" => "",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
            "crond_day4" => array(
              "col_name" => "crond_day4",
              "title" => "",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
            "crond_day5" => array(
              "col_name" => "crond_day5",
              "title" => "Saturday",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
            "crond_day6" => array(
              "col_name" => "crond_day6",
              "title" => "Sunday",
              "tip" => "Select the cronjobs you want to execute only on this day",
              "input" => array(
                "type" => "select_m",
                "options" => []
              )
            ),
          )
        ),

      )

    ),
    "browse" => array(
      "functions" => array(
        "ui_pre" => $browse_func,
        "be_pre" => $browse_func,
        "be_after" => $browse_func_be
      ),
      "groups" => array()
    ),
    "seo" => array(
      "functions" => array(
        "ui_pre" => $seo_func_ui,
        "be_pre" => $seo_func_ui,
        "be_after" => $seo_func_be
      ),
      "groups" => array()
    ),
    "search" => array(

      "functions" => array(
        "be_after" => function( $i, $o ){

          /*$index_type = $o["data"]["fulltext"];

          $clear_qs = array(
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext_2`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext_2`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext_2`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_2`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_2`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_2`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext_3`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext_3`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext_3`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_3`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_3`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_3`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext_4`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext_4`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext_4`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_4`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_4`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_4`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext2`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext2`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext2`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name2`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title2`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title2`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext3`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext3`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext3`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name3`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title3`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title3`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name_fulltext4`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title_fulltext4`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title_fulltext4`",
            "ALTER TABLE `_c_m_artists` DROP INDEX `name4`",
            "ALTER TABLE `_c_m_tracks` DROP INDEX `title4`",
            "ALTER TABLE `_c_m_albums` DROP INDEX `title4`",
          );

          if ( $index_type == "index" ){
            $add_qs = array(
              "ALTER TABLE `_c_m_artists` ADD INDEX(`name`)",
              "ALTER TABLE `_c_m_tracks` ADD INDEX(`title`)",
              "ALTER TABLE `_c_m_albums` ADD INDEX(`title`)",
            );
          } elseif ( $index_type == "fulltext" ) {
            $add_qs = array(
              "ALTER TABLE `_c_m_artists` ADD FULLTEXT(`name`)",
              "ALTER TABLE `_c_m_tracks` ADD FULLTEXT(`title`)",
              "ALTER TABLE `_c_m_albums` ADD FULLTEXT(`title`)",
            );
          }

          if ( $index_type != "inverted_indexing" ){
            foreach( array_merge( $clear_qs, $add_qs ) as $_q ){
              try {
                bof()->db->query( $_q );
              } catch( Exception|bofException $err ){}
            }
          }*/

          return $o;

        },
        "ui_after" => function( &$i ){

          $htmls = [];
          $__t = $__d = $__o = 0;
          foreach (bof()->bofAdmin->_get_objects() as $objectName => $objectArgs) {
            $_box = bof()->object->db_setting->get( "search_ii_p", [], false, false, true );
            $tObject = bof()->object->__get($objectName);
            if ($objectArgs['search'] ? $tObject->method_exists("clean_search_terms") : false) {
              $_done  = !empty( $_box["val"][$objectName]["done"] )  ? $_box["val"][$objectName]["done"]  : 0;
              $_total = !empty( $_box["val"][$objectName]["total"] ) ? $_box["val"][$objectName]["total"] : 0;
              $__d += $_done;
              $__t += $_total;
              $_t = !empty( $_box["val"][$objectName]["time"] ) ? $_box["val"][$objectName]["time"] : 0;
              $__o++;
              $htmls[] = 
              "<tr style='color:#bbb'><td>" . $tObject->bof()["label"] . " <i style='opacity:0.3;font-size:80%'>{$objectName}</i></td><td>" . number_format($_done) . "</td><td>" . number_format($_total) . "</td><td>" . bof()->general->passed_time_from_time_hr($_t) . "</td></tr>";
            }
          }

          $_html = "<span style='font-size:90%;color:#aaa'>A total of <span style='color:#fff;font-size:110%'>".number_format($__o)."</span> object(s) support inverted indexing. Out of <span style='color:#fff;font-size:110%'>".number_format($__t)."</span> items, <span style='color:#fff;font-size:110%'>".number_format($__d)."</span> have already been indexed. You can click <a style='text-decoration:underline' href='cronjobs?code=invert_indexing'>here</a> to see cronjob logs</span>";
          
          $i["inverted"]["inputs"]["stat"]["html"] = "{$_html}<br><br><table width='100%'><thead><tr><td>Object</td><td>Indexed</td><td>Total</td><td>Last check</td></tr></thead><tbody>" . implode( "", $htmls ) . "</tbody></table>";
          return $i;

        },
        "ui_pre" => function( $groups ){

          if ( bof()->db->is_only_full_groupby() )
          $groups["search"]["inputs"]["indextype"]["tip"] .= "<br><br><b style='color:red'>ONLY_FULL_GROUP_BY</b> is enabled in your database. Inverted indexing can still work, but results will be less relevant";
          return $groups;

        },
      ),

      "groups" => array(
        "search" => array(
          "title" => "Search",
          "icon" => "search",
          "inputs" => array(
            "indextype" => array(
              "title" => "Index type",
              "tip" => '<b style="color:red"><a href="https://support.busyowl.co/documentation/search">Click here</a> and read the documentation before making any changes</b>',
              "col_name" => "fulltext_search",
              "input" => array(
                "name" => "fulltext",
                "type" => "select_i",
                "value" => ( defined( "fulltext_search" ) ? fulltext_search : true  ) ? "fulltext" : "index",
                "options" => array(
                  [ "fulltext", "Fulltext" ],
                  [ "index", "Index" ],
                  [ "inverted_indexing", "Inverted Indexing" ]
                )
              ),
              "validator" => array(
                "raw",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "inverted" => array(
          "title" => "Inverted Indexing - Progress",
          "icon" => "search",
          "inputs" => array(
            "stat" => array(
              "title" => "",
              "html" => "test"
            ),
          )
        ),
      )

    ),
    "user_pps" => array(

      "functions" => array(
        "be_after" => function( $i, $o ){

          if ( empty( $o["report"]["fail"] ) ){
            $sps = explode( ",", $o["data"]["user_sps"] );
            $pps = explode( ",", $o["data"]["user_pps"] );
            if ( in_array( "all", $sps, true ) ) $sps = ["all"];
            if ( in_array( "all", $pps, true ) ) $pps = ["all"];
            $o["set"]["user_pps"] = implode( ",", $pps );
            $o["set"]["user_sps"] = implode( ",", $sps );
          }

          return $o;

        }
      ),

      "groups" => array(
        "user_links" => array(
          "title" => "Setting",
          "icon" => "account_box",
          "inputs" => array(
            "user_pps" => array(
              "title" => "Profile Pages",
              "tip" => "Choose which profile pages your users should have access to",
              "col_name" => "user_pps",
              "input" => array(
                "name" => "user_pps",
                "type" => "select_m",
                "value" => "all",
                "options" => bof()->general->bofify_options( [ "all", "playlists", "likes", "subscriptions", "purchased", "history", "uploads" ], "value", "value" )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "all", "playlists", "likes", "subscriptions", "purchased", "history", "uploads" ]
                )
              )
            ),
            "user_sps" => array(
              "title" => "Setting Pages",
              "tip" => "Choose which setting pages your users should have access to",
              "col_name" => "user_sps",
              "input" => array(
                "name" => "user_sps",
                "type" => "select_m",
                "value" => "all",
                "options" => bof()->general->bofify_options( array(
                  "all" => "all",
                  "profile" => "edit profile",
                  "security" => "security",
                  "transactions" => "transactions",
                  "links" => "social links",
                  "notifications" => "notifications",
                  "sessions" => "sessions",
                  "delete" => "delete account",
                  "unsub" => "cancel stripe subscription"
                ), "key", "value" )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "all", "profile", "security", "transactions", "links", "notifications", "sessions", "delete" ]
                )
              )
            ),
          )
        ),
      )

    ),
    "youtube_piped" => array(

      "functions" => array(
        "be_after" => function( $i, $o ){

          return $o;

        },
        "ui_pre" => function( $groups ){

          bof()->plugin("youtube");
          $is = bof()->youtube_piped->get_instances();
          if ( $is ){
            $html = "<table style='width:100%'>";
            $html .= "<thead><tr><td>URL</td><td width='20px'></td><td width='20px'></td><td width='200px'>Test result</td></tr></thead>";
            $html .= "<tbody>";
            $stats = bof()->object->db_setting->get("youtube_piped_ss",[]);
            foreach( $is as $_i ){
              $stat = !empty( $stats[ crc32( $_i ) ] ) ? $stats[ crc32( $_i ) ] : false;
              $html .= "<tr class='_tr_".uniqid()."'>
              <td class='_uri' style='font-size:85%'>{$_i}</td>
              <td><a class='btn btn-secondary' ID='test_instance'>Test</a></td>
              <td><a class='btn btn-primary' ID='select_instance'>Use</a></td>
              <td class='result ".($stat?($stat[0]?"ok":"failed"):"")."'>".($stat?$stat[1]:"-")."</td>
              </tr>";
            }
            $html .= "</tbody>";
            $html .= "</table>";
            $groups["instances"]["inputs"][0]["html"] = $html;
          }

          return $groups;
          
        }
      ),

      "groups" => array(
        "setting" => array(
          "title" => "Setting",
          "icon" => "settings",
          "size" => "col-12 col-lg-12",
          "inputs" => array(
            "youtube_piped" => array(
              "title" => "Enable",
              "tip" => '<a href="https://github.com/TeamPiped/Piped" target="_blank">Piped</a> by TeamPiped can `proxy` YouTube videos for you. <a href="https://support.busyowl.co/documentation/youtube-piped">Click here</a> for docs<br><br><b>Piped</b> doesn\'t work anymore in 2025+',
              "col_name" => "youtube_piped",
              "input" => array(
                "name" => "youtube_piped",
                "type" => "checkbox",
                "value" => defined( "youtube_piped" ) ? youtube_piped : false,
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "youtube_piped_iu" => array(
              "title" => "Instance URLs",
              "col_name" => "youtube_piped_iu",
              "input" => array(
                "name" => "youtube_piped_iu",
                "type" => "textarea"
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                  "allow_eol" => true
                )
              )
            ),
            "youtube_piped_st" => array(
              "title" => "Preferred Stream type",
              "col_name" => "youtube_piped_st",
              "input" => array(
                "name" => "youtube_piped_st",
                "type" => "select",
                "options" => array(
                  ["audio_lq", "Audio - Smallest"],
                  ["audio_hq", "Audio - Best"],
                  ["video_lq", "Video - Smallest"],
                  ["video_hq", "Video - Best"]
                ),
                "value" => "audio_hq"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "audio_lq", "audio_hq", "video_lq", "video_hq" ]
                )
              )
            ),
            "youtube_piped_be" => array(
              "title" => "Request Handling Method",
              "tip" => "Determine how user requests to Piped instances are processed. Choose \"Browser\" to send requests directly from the user's browser, or \"Server\" to route requests through your server for added privacy",
              "col_name" => "youtube_piped_be",
              "input" => array(
                "name" => "youtube_piped_be",
                "type" => "select_i",
                "options" => array(
                  ["client", "Browser"],
                  ["server", "Server"],
                ),
                "value" => "server"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "server", "client" ]
                )
              )
            ),
          )
        ),
        "instances" => array(
          "title" => "Scraped Instances",
          "tip" => "Scraped from <a href='https://github.com/TeamPiped/Piped/wiki/Instances' target='_blank'>https://github.com/TeamPiped/Piped/wiki/Instances</a>",
          "icon" => "search",
          "size" => "col-12 col-lg-12",
          "inputs" => array(
            array(
              "html" => "test"
            )
          )
        )
      )

    ),
    "apn" => array(
      "exe_display_rules" => true,
      "groups" => array(
        "mobile_apn" => array(
          "title" => "iOS APN",
          "icon" => "phone_iphone",
          "tip" => "Apple Push Notification service configuration for iOS apps",
          "inputs" => array(
            "apn_team_id" => array(
              "title" => "Apple Team ID",
              "col_name" => "apn_team_id",
              "input" => array(
                "name" => "apn_team_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            ),
            "apn_key_id" => array(
              "title" => "APN Key ID",
              "col_name" => "apn_key_id",
              "input" => array(
                "name" => "apn_key_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            ),
            "apn_bundle_id" => array(
              "title" => "App Bundle ID",
              "col_name" => "apn_bundle_id",
              "input" => array(
                "name" => "apn_bundle_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            ),
            "apn_environment" => array(
              "title" => "APN Environment",
              "col_name" => "apn_environment",
              "input" => array(
                "name" => "apn_environment",
                "type" => "select_i",
                "value" => "production",
                "options" => array(
                  [ "sandbox", "Sandbox (Development)" ],
                  [ "production", "Production" ]
                ),
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "sandbox", "production" ],
                  "empty()"
                )
              )
            ),
            "apn_auth_key" => array(
              "title" => "APN Auth Key",
              "tip" => "Download the .p8 key from Apple Developer Portal. Rename it to apn-AuthKey.p8 and upload it to <b>" . realpath(root . "/app") . "</b>",
              "input" => array(
                "type" => "empty",
              )
            ),
          )
        )
      ),
      "functions" => array(
        "ui_pre" => function( $groups ){

          $apn_key_check = root . "/app/apn-AuthKey.p8";

          if ( file_exists( $apn_key_check ) )
          $apn_key_check = "<b style='color:rgb(var(--c_green))'>File exists</b>";
          else 
          $apn_key_check = "<b style='color:rgb(var(--c_red));font-size:130%'>File not found</b>";


          $groups["mobile_apn"]["inputs"]["apn_auth_key"]["tip"] = $apn_key_check . "<br><br>" . $groups["mobile_apn"]["inputs"]["apn_auth_key"]["tip"];

          return $groups;

        },
      )
    ),
    "mobile" => array(
      "exe_display_rules" => true,
      "groups" => array(
        "mobile_menu" => array(

          "title" => "Bottom Navigation Bar",
          "icon" => "menu_open",

          "inputs" => array(

            "nav_is" => array(
              "title" => "Number of items",
              "input" => array(
                "name" => "nav_is",
                "type" => "select_i",
                "options" => array(
                  [ "4", "4" ],
                  [ "5", "5" ]
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "4", "5" ]
                )
              )
            )

          )
        ),
      ),
      "functions" => array(
        "ui_pre" => function( $groups ){

          $navBtns = bof()->highlights->getMobNavItems("options");
          $navKeys = bof()->highlights->getMobNavItems("keys");
          $saved = bof()->object->db_setting->get("mobile_setting_nav");
          $non_default_langs = bof()->object->language->get_all();

          $groups["mobile_menu"]["inputs"]["nav_is"]["input"]["value"] = !empty( $saved["number"] ) ? $saved["number"] : 5;

          for ($i = 1; $i <= 5; $i++) {

            $groups["mobile_menu"]["inputs"]["nav_i{$i}"] = array(
              "title" => "Item #{$i}",
              "tip" => "Which page or functionality should be linked to the #{$i} navigation item?",
              "input" => array(
                "name" => "nav_i{$i}",
                "type" => "select",
                "options" => $navBtns,
                "value" => !empty( $saved["i{$i}_id"] ) ? $saved["i{$i}_id"] : false
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $navKeys
                )
              )
            );
            $groups["mobile_menu"]["inputs"]["nav_i{$i}_i"] = array(
              "title" => "Item #{$i} - Other - Icon - Website - Inactive",
              "tip" => "For browsers/PWA version. Will be used when this item is inative. Choose an icon from <a href='https://materialdesignicons.com/' target='_blank'>https://materialdesignicons.com/</a>. Example: star",
              "display_on_cond" => "and",
              "display_on" => array(
                "nav_i{$i}" => ["equal", "other"]
              ),
              "input" => array(
                "name" => "nav_i{$i}_i",
                "type" => "text",
                "value" => !empty( $saved["i{$i}_icon"] ) ? $saved["i{$i}_icon"] : false
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            );
            $groups["mobile_menu"]["inputs"]["nav_i{$i}_ia"] = array(
              "title" => "Item #{$i} - Other - Icon - Website - Active",
              "tip" => "For browsers/PWA version. Will be used when this item is active. Choose an icon from <a href='https://materialdesignicons.com/' target='_blank'>https://materialdesignicons.com/</a>. Example: star",
              "display_on_cond" => "and",
              "display_on" => array(
                "nav_i{$i}" => ["equal", "other"]
              ),
              "input" => array(
                "name" => "nav_i{$i}_ia",
                "type" => "text",
                "value" => !empty( $saved["i{$i}_icon_a"] ) ? $saved["i{$i}_icon_a"] : false
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            );
            $groups["mobile_menu"]["inputs"]["nav_i{$i}_fa"] = array(
              "title" => "Item #{$i} - Other - Native Apps - Icon - Inactive",
              "tip" => "For Native apps. Will be used when this item is inative. Copy icon name from <a href='https://fonts.google.com/icons' target='_blank'>https://fonts.google.com/icons</a>. Example: favorite",
              "display_on_cond" => "and",
              "display_on" => array(
                "nav_i{$i}" => ["equal", "other"]
              ),
              "input" => array(
                "name" => "nav_i{$i}_fa",
                "type" => "text",
                "value" => !empty( $saved["i{$i}_icon_fa"] ) ? $saved["i{$i}_icon_fa"] : false
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            );
            $groups["mobile_menu"]["inputs"]["nav_i{$i}_faa"] = array(
              "title" => "Item #{$i} - Other - Native Apps - Icon - Active",
              "tip" => "For Native apps. Will be used when this item is active. Copy icon name from <a href='https://fonts.google.com/icons' target='_blank'>https://fonts.google.com/icons/</a>. Example: favorite",
              "display_on_cond" => "and",
              "display_on" => array(
                "nav_i{$i}" => ["equal", "other"]
              ),
              "input" => array(
                "name" => "nav_i{$i}_faa",
                "type" => "text",
                "value" => !empty( $saved["i{$i}_icon_faa"] ) ? $saved["i{$i}_icon_faa"] : false
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            );
            $groups["mobile_menu"]["inputs"]["nav_i{$i}_ct"] = array(
              "title" => "Item #{$i} - Other - Title",
              "display_on_cond" => "and",
              "display_on" => array(
                "nav_i{$i}" => ["equal", "other"]
              ),
              "input" => array(
                "name" => "nav_i{$i}_ct",
                "type" => "text",
                "value" => !empty( $saved["i{$i}_title"] ) ? $saved["i{$i}_title"] : false
              ),
              "validator" => array(
                "string",
                array("empty()")
              )
            );

            if ( $non_default_langs ) {
              foreach ($non_default_langs as $non_default_lang) {
                $groups["mobile_menu"]["inputs"]["nav_i{$i}_ct_{$non_default_lang["code2"]}"] = array(
                  "title" => "Item #{$i} - Other - Title - {$non_default_lang["name"]}",
                  "display_on_cond" => "and",
                  "display_on" => array(
                    "nav_i{$i}" => ["equal", "other"]
                  ),
                  "input" => array(
                    "name" => "nav_i{$i}_ct_{$non_default_lang["code2"]}",
                    "type" => "text",
                    "value" => !empty( $saved["i{$i}_title_{$non_default_lang["code2"]}"] ) ? $saved["i{$i}_title_{$non_default_lang["code2"]}"] : false
                  ),
                  "validator" => array(
                    "string",
                    array("empty()")
                  )
                );
              }
            }

            $groups["mobile_menu"]["inputs"]["nav_i{$i}_link"] = array(
              "title" => "Item #{$i} - Other - Link",
              "display_on_cond" => "and",
              "display_on" => array(
                "nav_i{$i}" => ["equal", "other"]
              ),
              "input" => array(
                "name" => "nav_i{$i}_link",
                "type" => "text",
                "value" => !empty( $saved["i{$i}_url"] ) ? $saved["i{$i}_url"] : false
              ),
              "validator" => array(
                "url",
                array("empty()")
              )
            );

          }

          return $groups;

        },
        "be_after" => function( $groups, $data ){

          if (empty($data["report"]["fail"])) {

            $navItems = bof()->highlights->getMobNavItems();
            $non_default_langs = bof()->object->language->get_all();

            $_data = $data["data"];

            $_j = array(
              "number" => $_data["nav_is"]
            );

            for ($i = 1; $i <= 5; $i++) {
              if ($_data["nav_i{$i}"] == "other") {
                $_j["i{$i}_id"] = "other";
                $_j["i{$i}_title"] = $_data["nav_i{$i}_ct"];
                $_j["i{$i}_icon"] = $_data["nav_i{$i}_i"];
                $_j["i{$i}_icon_a"] = $_data["nav_i{$i}_ia"];
                $_j["i{$i}_icon_fa"] = $_data["nav_i{$i}_fa"];
                $_j["i{$i}_icon_faa"] = $_data["nav_i{$i}_faa"];
                $_j["i{$i}_url"] = $_data["nav_i{$i}_link"];
                $_j["i{$i}_attr"] = null;
                if ($non_default_langs) {
                  foreach ($non_default_langs as $non_default_lang) {
                    $_j["i{$i}_title_{$non_default_lang["code2"]}"] = $_data["nav_i{$i}_ct_{$non_default_lang["code2"]}"];
                  }
                }
              } else {
                $_j["i{$i}_id"] = $_data["nav_i{$i}"];
                $_j["i{$i}_title"] = bof()->object->language->turn( $_data["nav_i{$i}"], [], ["uc_first"=>true]);
                $_j["i{$i}_icon"] = $navItems[$_data["nav_i{$i}"]]["icon"];
                $_j["i{$i}_icon_a"] = $navItems[$_data["nav_i{$i}"]]["icon_a"];
                $_j["i{$i}_icon_fa"] = $navItems[$_data["nav_i{$i}"]]["icon_fa"];
                $_j["i{$i}_icon_faa"] = $navItems[$_data["nav_i{$i}"]]["icon_faa"];
                $_j["i{$i}_url"] = !empty($navItems[$_data["nav_i{$i}"]]["link"]) ? $navItems[$_data["nav_i{$i}"]]["link"] : null;
                $_j["i{$i}_url"] = !empty($navItems[$_data["nav_i{$i}"]]["attr"]) ? $navItems[$_data["nav_i{$i}"]]["attr"] : null;
                if ($non_default_langs) {
                  foreach ($non_default_langs as $non_default_lang) {
                    $_j["i{$i}_title_{$non_default_lang["code2"]}"] = bof()->object->language->turn( $_data["nav_i{$i}"], [], ["lang" => $non_default_lang["code2"], "ucfirst" => true]);
                  }
                }
              }
            }

            bof()->object->db_setting->set("mobile_setting_nav", json_encode($_j), "json");
          }

          return $data;

        }
      )
    ),
    "fcm" => array(
      "exe_display_rules" => true,
      "groups" => array(
        "mobile_fcm" => array(

          "title" => "Android FCM",
          "icon" => "notifications",
          "tip" => "Your project ID + fcm-service-account.json is required to send push notifications in your Android app",

          "inputs" => array(

            "fcm_project_id" => array(
              "title" => "Project ID",
              "col_name" => "fcm_project_id",
              "input" => array(
                "name" => "fcm_project_id",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
                ),

            "fcm_project_number" => array(
              "title" => "Project number",
              "col_name" => "fcm_project_number",
              "input" => array(
                "name" => "fcm_project_number",
                "type" => "text",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),

            "fcm_service_account" => array(
              "title" => "Project Service Account Private key",
              "tip" => "Generate a private key, download it in form of .json from your Firebase console. Rename it to fcm-service-account.json and upload it to <b>" . realpath( root . "/app" ) . "</b>/fcm-service-account.json",
              "input" => array(
                "type" => "empty",
              )
            ),

          )
        ),
      ),
      "functions" => array(
        "ui_pre" => function( $groups ){

          $fcm_sapk_path = root . "app/fcm-service-account.json";

          if ( file_exists( $fcm_sapk_path ) )
          $fcm_sapk_check = "<b style='color:rgb(var(--c_green))'>File exists</b>";
          else 
          $fcm_sapk_check = "<b style='color:rgb(var(--c_red));font-size:130%'>File not found</b>";

          $groups["mobile_fcm"]["inputs"]["fcm_service_account"]["tip"] = $fcm_sapk_check . "<br><br>" . $groups["mobile_fcm"]["inputs"]["fcm_service_account"]["tip"];

          return $groups;

        },
      )
    ),
    "admin_notifications" => array(

      "functions" => array(
        "ui_pre" => $admin_nots,
        "be_pre" => $admin_nots,
        "be_after" => function( $groups, $inputs ){

          bof()->object->db_setting->set( "admin_notifications", json_encode( $inputs["data"] ), "json" );
          return $inputs;

        }
      ),

      "groups" => array(

        "general_setting" => array(
          "title" => "Setting",
          "icon" => "brightness_5",
          "inputs" => array(
            "active" => array(
              "tip" => "Globally enable/disable all notification channels. When disabled, no notifications will be sent through any channel regardless of individual settings",
              "title" => "Notifications Active",
              "input" => array(
                "name"  => "active",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "interval" => array(
              "tip" => "Defines how often the notification system runs in the background. Notifications are not sent instantly during user actions to avoid performance delays. Instead, they're processed by a cronjob<br><br>For example, entering 1 means the system will check and send notifications every 1 minute",
              "title" => "Execution Interval",
              "col_name" => "admin_notification_interval",
              "input" => array(
                "name"  => "interval",
                "type"  => "digit",
                "value" => 5
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1
                )
              )
            ),
            "retention" => array(
              "tip" => "Determines how long admin notifications should be stored in the database. Older notifications will be automatically deleted to save space. Enter the number of days to keep notifications (e.g., 7 to retain for one week).",
              "title" => "Log Retention",
              "col_name" => "admin_notification_retention",
              "input" => array(
                "name"  => "retention",
                "type"  => "digit",
                "value" => 60
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1
                )
              )
            ),
          )
        ),
        "email_setting" => array(
          "title" => "Email Setting",
          "icon" => "mail",
          "inputs" => array(
            "email_active" => array(
              "tip" => "When enabled, email notifications can be sent to <b>root admin's email</b>. Disabling this turns off ALL email notifications, overriding individual event settings. When enabled, configure specific event",
              "title" => "Enable Email Channel",
              "input" => array(
                "name"  => "email_active",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),

          )
        ),
        "telegram_setting" => array(
          "title" => "Telegram Settings",
          "icon" => "chat",
          "inputs" => array(
            "telegram_active" => array(
              "tip" => "When enabled, Telegram notifications can be sent. Disabling this turns off ALL Telegram notifications, overriding individual event settings. When enabled, configure specific Telegram alerts per event<br><br>Limits: Per hour: 150 message, per day: 2,400 messages",
              "title" => "Enable Telegram Channel",
              "input" => array(
                "name"  => "telegram_active",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "telegram_receiver" => array(
              "tip" => "Unique ID of user receiving alerts. Use <a href='https://t.me/getidsbot' target='_blank'>@getidsbot</a> to find your ID<br><br><b style='color:rgb(var(--c_red))'>Send a message to <a href='https://t.me/busyowl_notification_bot' target='_blank'>@busyowl_notification_bot</a> on your telegram with same account</b>",
              "title" => "Receiver Chat ID",
              "input" => array(
                "name"  => "telegram_receiver",
                "type"  => "text",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "whatsapp_setting" => array(
          "title" => "WhatsApp Settings",
          "icon" => "chat",
          "inputs" => array(
            "whatsapp_active" => array(
              "tip" => "When enabled, WhatsApp notifications can be sent. Disabling this turns off ALL WhatsApp notifications, overriding individual event settings. When enabled, configure specific WhatsApp alerts per event<br><br>Limits: Per hour: 15 message, per day: 240 messages",
              "title" => "Enable WhatsApp Channel",
              "input" => array(
                "name"  => "whatsapp_active",
                "type"  => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
                  "empty()"
                )
              )
            ),
            "whatsapp_phone" => array(
              "tip" => "International format: [CountryCode][Number] (e.g., 14155552671)<br><br><b style='color:rgb(var(--c_red))'>Add our bot number +90 534 254 44 67 as your contact with same number enetered here</b>",
              "title" => "Receiver Phone Number",
              "input" => array(
                "name"  => "whatsapp_phone",
                "type"  => "text",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),

      )

    ),
  );

  $settings["cdn"] = array(
    "groups" => array(
      "cdn" => array(
        "title" => "CDN / Edge",
        "icon" => "cloud",
        "inputs" => array(
          "cdn_url" => array(
            "title" => "CDN Base URL",
            "tip" => "If set, public file URLs for local storage will be prefixed with this URL (e.g. https://cdn.hitune.in/). Leave empty to use site URL.",
            "col_name" => "cdn_url",
            "input" => array(
              "name" => "cdn_url",
              "type" => "text",
            ),
            "validator" => array(
              "string",
              array(
                "empty()",
              )
            )
          ),
          "cdn_cache_headers" => array(
            "title" => "Serve long cache headers",
            "tip" => "If set, .htaccess will add Cache-Control: public, max-age=31536000 for files served from /files/. This is safe if files are versioned by path.",
            "col_name" => "cdn_cache_headers",
            "input" => array(
              "name" => "cdn_cache_headers",
              "type" => "checkbox",
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()",
                "int" => true
              )
            )
          ),
        )
      )
    )
  );

  $settings["mobile"]["functions"]["be_pre"] = $settings["mobile"]["functions"]["ui_pre"];

  $settings["player"]["functions"]["ui_pre"] = function( $groups ){

    $supported_sources = bof()->source->get_supported( "stream" );
    $groups["player"]["inputs"]["muse_available_sources"]["validator"][1]["values"] = $supported_sources["keys"];
    $groups["player"]["inputs"]["muse_available_sources"]["input"]["options"] = $supported_sources["options"];

    $supported_sources = bof()->source->get_supported( "download" );
    $groups["player"]["inputs"]["download_available_sources"]["validator"][1]["values"] = $supported_sources["keys"];
    $groups["player"]["inputs"]["download_available_sources"]["input"]["options"] = $supported_sources["options"];

    $_ms = bof()->object->db_setting->get( "muse_setting" );

    $groups["player"]["inputs"]["muse_hide"]["input"]["value"] = !empty( $_ms["muse_hide"] );
    $groups["player"]["inputs"]["muse_hide_yt"]["input"]["value"] = !empty( $_ms["muse_hide_yt"] );
    $groups["queue"]["inputs"]["queue_save"]["input"]["value"] = $_ms ? ( in_array( "queue_save", array_keys( $_ms ), true ) ? !empty( $_ms["queue_save"] ) : false ) : true;
    $groups["queue"]["inputs"]["queue_disable_auto"]["input"]["value"] = !empty( $_ms["queue_disable_auto"] );
    $groups["queue"]["inputs"]["queue_hide_infinite"]["input"]["value"] = !empty( $_ms["queue_hide_infinite"] );
    $groups["queue"]["inputs"]["queue_hide"]["input"]["value"] = !empty( $_ms["queue_hide"] );
    $groups["queue"]["inputs"]["queue_hide_lyrics"]["input"]["value"] = !empty( $_ms["queue_hide_lyrics"] );


    return $groups;

  };
  $settings["player"]["functions"]["be_pre"] = $settings["player"]["functions"]["ui_pre"];
  $settings["theme"]["functions"]["ui_pre"] = function( $groups ){
    return bof()->theme->admin_setting( "ui", $groups );
  };
  $settings["theme"]["functions"]["be_pre"] = function( $groups ){
    return bof()->theme->admin_setting( "be", $groups );
  };
  $settings["theme"]["functions"]["be_after"] = function( $groups, $inputs ){
    return bof()->theme->admin_setting( "be_after", $groups, $inputs );
  };

  bof()->pgt->add_setting("paypal", array(
    "gateway_paypal_mode" => array(
      "title" => "Mode",
      "col_name" => "gateway_paypal_mode",
      "tip" => "Sandbox allows you to test everything before going live",
      "input" => array(
        "name" => "gateway_paypal_mode",
        "type" => "select_i",
        "options" => array(
          ["sandbox", "Sandbox"],
          ["live", "Live"]
        )
      ),
      "validator" => array(
        "in_array",
        array(
          "empty()",
          "values" => ["sandbox", "live"]
        )
      )
    ),
    "gateway_paypal_key" => array(
      "title" => "Key",
      "tip" => "You can get your Paypal Key & Secret in PayPal's Developer Dashboard. <a href='https://developer.paypal.com/api/rest/#link-getcredentials' target='_blank'>Click here for more info</a>",
      "col_name" => "gateway_paypal_key",
      "input" => array(
        "name" => "gateway_paypal_key",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
    "gateway_paypal_secret" => array(
      "title" => "Secret",
      "tip" => "You can get your Paypal Key & Secret in PayPal's Developer Dashboard. <a href='https://developer.paypal.com/api/rest/#link-getcredentials' target='_blank'>Click here for more info</a>",
      "col_name" => "gateway_paypal_secret",
      "input" => array(
        "name" => "gateway_paypal_secret",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
  ));

  bof()->pgt->add_setting("stripe", array(
    "gateway_stripe_mode" => array(
      "title" => "Mode",
      "col_name" => "gateway_stripe_mode",
      "tip" => "Sandbox allows you to test everything before going live",
      "input" => array(
        "name" => "gateway_stripe_mode",
        "type" => "select_i",
        "options" => array(
          ["sandbox", "Test"],
          ["live", "Live"]
        )
      ),
      "validator" => array(
        "in_array",
        array(
          "empty()",
          "values" => ["sandbox", "live"]
        )
      )
    ),
    "gateway_stripe_key" => array(
      "title" => "Publishable Key",
      "tip" => "You can get your Stripe Keys in <a href='https://dashboard.stripe.com/account/apikeys' target='_blank'>Stripe's Developer Dashboard</a>. <a href='https://stripe.com/docs/keys' target='_blank'>Click here for more info</a>",
      "col_name" => "gateway_stripe_key",
      "input" => array(
        "name" => "gateway_stripe_key",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
    "gateway_stripe_secret" => array(
      "title" => "Secret Key",
      "tip" => "You can get your Stripe Keys in <a href='https://dashboard.stripe.com/account/apikeys' target='_blank'>Stripe's Developer Dashboard</a>. <a href='https://stripe.com/docs/keys' target='_blank'>Click here for more info</a>",
      "col_name" => "gateway_stripe_secret",
      "input" => array(
        "name" => "gateway_stripe_secret",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
    "gateway_stripe_taxLabel" => array(
      "title" => "Tax Label",
      "tip" => "Only required if you have set a `Fee`. What should the label of tax or fee be on Stripe receipt?",
      "col_name" => "gateway_stripe_taxLabel",
      "input" => array(
        "name" => "gateway_stripe_taxLabel",
        "type" => "text",
        "value" => "Tax"
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
    "gateway_stripe_desc" => array(
      "title" => "Description on receipt",
      "col_name" => "gateway_stripe_desc",
      "input" => array(
        "name" => "gateway_stripe_desc",
        "type" => "text",
        "value" => "Wallet charge"
      ),
      "validator" => array(
        "string",
        array()
      )
    ),
    "gateway_stripe_cText1" => array(
      "title" => "Custom text - above submit button",
      "tip" => "This text will appear above `submit` button in Stripe's payment page. Might not be visible on receipt issued on Stripe",
      "col_name" => "gateway_stripe_cText1",
      "input" => array(
        "name" => "gateway_stripe_cText1",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
    "gateway_stripe_cText2" => array(
      "title" => "Custom text - beneath submit button",
      "tip" => "This text will appear beneath `submit` button in Stripe's payment page. Might not be visible on receipt issued on Stripe",
      "col_name" => "gateway_stripe_cText2",
      "input" => array(
        "name" => "gateway_stripe_cText2",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
    "gateway_stripe_subs" => array(
      "title" => "Enable Recurring Fee Strategy for 'Subscription Plans'",
      "tip" => "Toggle this option to switch from one-time payments to recurring fees for subscription plans using Stripe. This allows for automatic billing cycles, ensuring continuous service for your subscribers without manual renewal.",
      "col_name" => "gateway_stripe_subs",
      "input" => array(
        "name" => "gateway_stripe_subs",
        "type" => "checkbox",
      ),
      "validator" => array(
        "boolean",
        array(
          "empty()",
          "forceDigit" => true,
          "forceInt" => true,
          "int" => true,
        )
      )
    ),
    "gateway_stripe_webhook_secret" => array(
      "title" => "Webhook Secret",
      "tip" => "Signing secret for Stripe webhook endpoint. Create endpoint in Stripe dashboard pointing to https://yourdomain.com/webhook/stripe/",
      "col_name" => "gateway_stripe_webhook_secret",
      "input" => array(
        "name" => "gateway_stripe_webhook_secret",
        "type" => "text",
      ),
      "validator" => array(
        "string",
        array(
          "empty()",
        )
      )
    ),
  ));

  foreach( $settings as $setting_name => $setting_array ){
    $loader->bofAdmin->_add_setting( $setting_name, $setting_array );
  }

  $loader->bofAdmin->setting( substr( $loader->request->get_requested_url(), strlen( "bofAdmin/setting/" ), -1 ) );

}

?>
