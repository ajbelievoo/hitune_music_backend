<?php

if ( !defined( "bof_root" ) ) die;

class source extends bof_type_class {

  protected $types = array(
    "audio" => array(
      "name" => "Audio files"
    ),
    "video" => array(
      "name" => "Video files"
    ),
    "youtube" => array(
      "name" => "YouTube"
    ),
    "soundcloud" => array(
      "name" => "SoundCloud"
    ),
  );
  protected $supported_sources = array(
      "audio_quality_1" => "Audio 64k",
      "audio_quality_2" => "Audio 128k",
      "audio_quality_3" => "Audio 192k",
      "audio_quality_4" => "Audio 256k",
      "audio_quality_5" => "Audio 320k",
      "video_quality_1" => "Video 240p",
      "video_quality_2" => "Video 480p",
      "video_quality_3" => "Video 720p",
      "video_quality_4" => "Video 1080p",
      "video_quality_5" => "Video 4K",
      "soundcloud" => "SoundCloud",
      "youtube" => "YouTube",
  );

  public function get_types(){
    return $this->types;
  }
  public function add_type( $key, $name ){
    $this->types[ $key ] = $name;
  }
  public function get_supported_sources(){
    return $this->supported_sources;
  }
  public function add_supported_source( $key, $name ){
    $this->supported_sources[ $key ] = $name;
  }

  public function get_contents(){
    return [];
  }
  public function get_supported( $type, $level="raw", $end=null ){

    $prefix = $type == "download" ? "download" : "muse";
    $prefix2 = $type == "download" ? "download_types" : "player";

    if ( $level == "raw" ) $level = 0;
    elseif ( $level == "setting" ) $level = 1;
    elseif ( $level == "user" ) $level = 2;

    $supported_sources = $this->_bof_this->get_supported_sources();

    if ( $type == "download" ){

      unset( $supported_sources["youtube"], $supported_sources["soundcloud"], $supported_sources["mixcloud"], $supported_sources["vimeo"] );

    }

    if ( $level >= 1 ){
      $setting = bof()->object->db_setting->get("{$prefix}_available_sources");
      if ( $setting ){
        $o_supported_sources = $supported_sources;
        $supported_sources = [];
        foreach( $setting as $_k ){
          if ( !empty( $o_supported_sources[ $_k ] ) )
          $supported_sources[ $_k ] = $o_supported_sources[ $_k ];
        }
      }
    }

    if ( $level >= 2 ){

      if ( bof()->user->get()->logged && $end !== "embed" )
      $user_access = bof()->user->get()->extra["roles"];

      else
      $user_access = bof()->user->get_guest()["roles"];

      if ( !$user_access ) return false;

      if ( $type == "stream" && empty( $user_access["player"] ) ){
        $supported_sources = [];
      }
      elseif ( $type == "download" && empty( $user_access["download"] ) ){
        $supported_sources = [];
      }
      else {
        $o_supported_sources = $supported_sources;
        $supported_sources = [];
        foreach( $user_access[ $prefix2 ] as $_u_player ){
          if ( in_array( $_u_player, array_keys( $o_supported_sources ), true ) )
          $supported_sources[ $_u_player ] = $o_supported_sources[ $_u_player ];
        }
      }
    }

    foreach( $supported_sources as $_k => $_v ){
      $options[] = [ $_k, $_v ];
      $_temp = explode( "_", $_k );
      $_type = reset( $_temp );
      $types[] = $_type;
    }

    return array(
      "array" => $supported_sources,
      "keys" => array_keys( $supported_sources ),
      "options" => $options,
      "types" => array_unique( $types )
    );

  }
  public function get_available( $type, $args ){

    $item_access = false;
    $item = false;
    $object_name = false;
    $exclude_subs = false;
    $sources = false;
    $end = null;
    extract( $args );

    $prefix = $type == "download" ? "download" : "muse";
    $prefix2 = $type == "download" ? "download_types" : "player";

    $user_supported = $this->_bof_this->get_supported( $type, "user" );

    if ( bof()->user->get()->logged && $end !== "embed" )
    $user_access = bof()->user->get()->extra["roles"];
    else
    $user_access = bof()->user->get_guest()["roles"];

    $premium_type_access = !empty( $user_access["premium_rules"][ $prefix2 ] ) ? $user_access["premium_rules"][ $prefix2 ] : null;

    if ( $item_access )
    return $user_supported;

    if ( $exclude_subs )
    return false;

    if ( !$premium_type_access ){
      $access_by_subs_plan = bof()->object->user_role->has_access( $user_access, array(
        "object_item" => $item,
        "object_name" => $object_name,
        "object_hash" => $item["hash"],
      ) );
      return $access_by_subs_plan ? $user_supported : false;
    }

    if ( empty( $premium_type_access ) )
    return false;

    foreach( $premium_type_access as $_h ){
      if ( !empty( $user_supported["array"][$_h] ) )
      $newArray[ $_h ] = $user_supported["array"][$_h];
    }

    if ( empty( $newArray ) )
    return false;

    foreach( $newArray as $_k => $_v ){
      $options[] = [ $_k, $_v ];
      $_temp = explode( "_", $_k );
      $_type = reset( $_temp );
      $types[] = $_type;
    }

    return array(
      "array" => $newArray,
      "keys" => array_keys( $newArray ),
      "options" => $options,
      "types" => array_unique( $types )
    );

  }
  public function get( $type, $object_name, $object_item, $sources, $end=null ){

    $the_object = bof()->object->__get( $object_name );
    $all = $this->_bof_this->get_supported( $type, "setting" );

    $requested_type = bof()->nest->user_input( "post", "type", "string", [], "audio_quality_1" );
    $requested_id = bof()->nest->user_input( "post", "ID", "md5" );

    $hasPendingSource = false;
    if ( !empty( $sources ) ){
      foreach( $sources as $_s ){
        if ( !empty( $_s["muse"]["type"][0] ) ? $_s["muse"]["type"][0] == "pending" : false ){
          $hasPendingSource = true;
          break;
        }
      }
    }

    // Item property access
    $item_property_access = bof()->object->ugc_property->owned( $object_name, $object_item );

    if ( $end == "embed" && !empty( $item_property_access["price"] ) ){
      $item_property_access["access"] = false;
      if ( !empty( $item_property_access["purchasable"]["price_setting_decoded"]["disable_subs"] ) )
      $item_property_access["purchasable"]["price_setting_decoded"]["disable_subs"] = false;
    }

    $user = $this->_bof_this->get_available( $type, array(
      "item_access" => !empty( $item_property_access["access"] ) ? $item_property_access["access"] : false,
      "item" => $object_item,
      "object_name" => $object_name,
      "exclude_subs" => !empty( $item_property_access["purchasable"]["price_setting_decoded"]["disable_subs"] ),
      "sources" => $sources,
      "end" => $end
    ) );

    // Check sources
    $dynamic_sources = [];
    if ( $object_name == "m_track" ){

      if ( bof()->object->db_setting->get( "youtube_automation" ) && $end == "stream" )
      $dynamic_sources[] = "youtube";

      if ( bof()->object->db_setting->get( "soundcloud_automation" ) && $end == "stream" )
      $dynamic_sources[] = "soundcloud";

      if ( ( bof()->object->db_setting->get( "youtube_automation" ) && $end == "stream" ) ? bof()->object->db_setting->get( "ut" ) : false )
      $dynamic_sources[] = "youtube_dl";

    }
    if ( $object_name == "m_track" ){

      if ( ( ( $object_item["price"] || $object_item["album_price"] ) && $end == "stream" ) ? bof()->object->db_setting->get( "fs_audio_preview_no_ff" ) : false )
      $dynamic_sources[] = "audio_preview_no_ff";

    }

    // if ( ( !$sources && !$dynamic_sources && !$free_sources ) || ( !$user && !$free_sources ) || !$all )
    if ( ( !$sources && !$dynamic_sources ) || !$all )
    return false;

    $user_supported = $this->_bof_this->get_supported( $type, "user" );

    if ( $sources ){
      foreach( $sources as $source ){

        $source_hook = $source["type"] == "audio" || $source["type"] == "video" ? "{$source["type"]}_quality_{$source["quality"]}" : $source["type"];

        if ( $end ? ( $end == "stream" || $end == "embed" ) && $source["stream_able"] != 1 : false ) continue;
        if ( $end ? $end == "download" && $source["download_able"] == -2 : false ) continue;
        if ( !in_array( $source_hook, $all["keys"], true ) ) continue;

        $locked = true;

        if ( $user ? in_array( $source_hook, $user["keys"], true ) : false ) $locked = false;
        if ( !empty( $source["force_free"] ) ){
           $locked = false;
           $free_sources[] = $source_hook . $source["hash"];
        }

        $__sd = array(
          "hook" => $source_hook,
          "hash" => $source["hash"],
          "title" => $source["_title"],
          "locked" => $locked,
          "_raw" => $source
        );

        if ( !empty( $source["real_ot"] ) )
        $__sd["real_ot"] = $source["real_ot"];
        if ( !empty( $source["real_oh"] ) )
        $__sd["real_oh"] = $source["real_oh"];

        $sources_by_type[ $source["type"] ][ $source_hook . $source["hash"] ] = $__sd;

      }
    }

    if ( $dynamic_sources && $type == "stream" ){

      if (!empty($user["keys"]) &&
      in_array("youtube", $dynamic_sources, true) ?
        (
          in_array("youtube", $user["keys"], true) &&
          !empty($sources_by_type) ?
          !in_array("youtube", array_keys($sources_by_type), true) :
          true
        )
        : false
      ) {

        $sources_by_type["youtube"] = array(
          "youtube_get" => array(
            "hook" => "youtube",
            "hash" => md5( $object_item["hash"] . "youtube" ),
            "title" => "Youtube",
            "locked" => false,
            "_raw" => array(
              "hash" => md5( $object_item["hash"] . "youtube" ),
              "muse" => array(
                "type" => array(
                  "youtube",
                  array(
                    "raaz" => true,
                    "youtube_get" => true,
                    "youtube_piped" => bof()->object->core_setting->get( "piped_youtube" )
                  )
                ),
              )
            )
          )
        );
        
      }

      if (!empty($user["types"]) && in_array("youtube_dl", $dynamic_sources, true) && !empty($sources_by_type) ? (
      in_array("audio", $user["types"], true) &&
      in_array("youtube", array_keys($sources_by_type), true) &&
      !in_array("audio", array_keys($sources_by_type), true)
      ) : false
      ) {

        $youtube_source = &$sources_by_type["youtube"];
        $keys = array_keys($youtube_source);

        $sources_by_type["audio"] = array(
          "youtube_get" => array(
            "hook" => "audio_quality_2",
            "hash" => md5( $object_item["hash"] . "youtube_dl" ),
            "title" => "Audio",
            "locked" => false,
            "_raw" => array(
              "hash" => md5( $object_item["hash"] . "youtube_dl" ),
              "muse" => array(
                "type" => array(
                  "audio",
                  array(
                    "raaz" => true,
                    "youtube_id" => !empty($youtube_source[$keys[0]]["_raw"]["muse"]["type"][1]["ID"]) ? $youtube_source[$keys[0]]["_raw"]["muse"]["type"][1]["ID"] : null,
                    "youtube_get" => empty($youtube_source[$keys[0]]["_raw"]["muse"]["type"][1]["ID"]),
                    "youtube_download" => true
                  )
                ),
              )
            )
          )
        );
      }

      if (!empty($user["keys"]) &&
      in_array("soundcloud", $dynamic_sources, true) ?
        (
          in_array("soundcloud", $user["keys"], true) &&
          !empty($sources_by_type) ?
          !in_array("soundcloud", array_keys($sources_by_type), true) :
          true
        )
        : false
      ) {

        $sources_by_type["soundcloud"] = array(
          "soundcloud_get" => array(
            "hook" => "soundcloud",
            "hash" => md5( $object_item["hash"] . "soundcloud" ),
            "title" => "SoundCloud",
            "locked" => false,
            "_raw" => array(
              "hash" => md5( $object_item["hash"] . "soundcloud" ),
              "muse" => array(
                "type" => array(
                  "soundcloud",
                  array(
                    "raaz" => true,
                    "soundcloud_get" => true
                  )
                ),
              )
            )
          )
        );
      }

      if (in_array("audio_preview_no_ff", $dynamic_sources, true)) {

        $sources_by_type["audio"]["preview_no_ff"] = array(
          "hook" => "audio_preview_no_ff",
          "hash" => md5( $object_item["hash"] . "preview_raw" ),
          "title" => "Audio - Preview",
          "locked" => false,
          "_raw" => array(
            "hash" => md5( $object_item["hash"] . "preview_raw" ),
            "muse" => array(
              "type" => array(
                "audio",
                array(
                  "type" => "free",
                  "address" => endpoint_address . "muse_preview_no_ff?bof_version=dont_cache&mtid={$object_item["hash"]}",
                  "live" => true,
                  "preview" => true
                )
              ),
            )
          )
        );
      }

    }

    if ( empty( $sources_by_type ) ){
      if ( $hasPendingSource )
      return "pending";
      return false;
    }

    foreach( $sources_by_type as $type => $type_sources ){

      if ( !$type_sources ) continue;

      foreach( $type_sources as $type_source_k => $type_source ){

        $source = $type_source["_raw"];
        if ( $end != "download" )
        unset( $sources_by_type[ $type ][ $type_source_k ]["_raw"] );

        $locked = true;
        if ( $user ? in_array( $type_source["hook"], $user["keys"], true ) : false ) $locked = false;
        if ( !empty( $free_sources ) ? in_array( $type_source_k, $free_sources, true ) : false ) $locked = false;
        if ( $type_source["hook"] == "audio_preview_no_ff" ) $locked = false;
        if ( $locked ) continue;

        $user_sources[] = $source;

        if ( $requested_id ? $source["hash"] == $requested_id : false )
        $user_source = $source;

        if ( $type_source["hook"] == $requested_type && empty( $user_source ) )
        $user_source = $source;

      }

    }

    if ( empty( $user_source ) && !empty( $user_sources ) ){
      $user_source = reset( $user_sources );
    }

    $types["count"] = count( $sources_by_type );

    foreach( $all["types"] as $_type ){

      if ( empty( $sources_by_type[ $_type ] ) ) continue;

      $_sources = $sources_by_type[ $_type ];
      // ksort( $_sources );
      $_sources_active = false;
      $_sources_locked = true;

      foreach( $_sources as &$_source ){

        if ( !empty( $user_source ) ? $_source["hash"] == $user_source["hash"] : false ){
          $_source["active"] = true;
          $_sources_active = true;
        }

        if ( !$_source["locked"] )
        $_sources_locked = false;

        if ( !empty( $_source["_raw"] ) && $end != "download" )
        unset( $_source["_raw"] );

      }

      $types["sources"][ $_type ] = array(
        "count" => count( $_sources ),
        "active" => $_sources_active,
        "locked" => $_sources_locked,
        "sources" => array_values( $_sources ),
      );

    }

    return array(
      "all" => $types,
      "user" => !empty( $user_source ) ? $user_source : null
    );

  }
  public function hr_quality( $type, $int, $excludeType=false ){

    if ( !$type || !$int ) return "";
    $supported_sources = $this->_bof_this->get_supported_sources();
    $title = $supported_sources["{$type}_quality_{$int}"];

    if ( $excludeType ){
      $_s = explode( " ", $title );
      $title = end( $_s );
    }

    return $title;

  }
  public function grant_access( $object_name, $object_hash, $source_hash, $web_address, $time_expire=null, $action="stream" ){

    for( $i=1; $i<=3; $i++ )
    $keys[$i] = md5(uniqid());

    $insertArray = array(
      [ "action", $action ],
      [ "object_type", $object_name ],
      [ "object_hash", $object_hash ],
      [ "source_hash", $source_hash ],
      [ "path_hash", md5( $web_address ) ],
      [ "key1", $keys[1] ],
      [ "key2", $keys[2] ],
      [ "key3", $keys[3] ],
      [ "user_agent", bof()->request->get_userAgent()["string"] ],
      [ "user_ip", bof()->request->get_userIP()["string"] ]
    );

    if ( $time_expire )
    $insertArray[] = [ "time_expire", "ADDDATE( now(), INTERVAL {$time_expire} )", true ];

    bof()->db->_insert( array(
      "table" => "_bof_cache_files_access",
      "set" => $insertArray
    ) );

    return
    str_replace( [ '%20', "%20", " " ], '+', $web_address ) . ( preg_match( "/\?/", $web_address ) ? "&" : "?" ) .
    "bof_version=dont_cache&bof_sw_ignore_me=true&protected=yes&ot={$object_name}&oh={$object_hash}&sid={$source_hash}&key1={$keys[1]}&key2={$keys[2]}&key3={$keys[3]}";

  }
  public function get_downloads( $object_name, $the_object, $item, $from="fe" ){

    if ( empty( bof()->user->get()->extra["roles"]["download"] ) )
    return null;

    if ( empty( $item["bof_dir_sources"] ) )
    return null;

    $sources_by_type = $this->_bof_this->get( "download", $object_name, $item, $item["bof_dir_sources"], "download" );

    if ( !empty( $sources_by_type["all"] ) ? $sources_by_type["all"]["count"] : false ){
      foreach( $sources_by_type["all"]["sources"] as $type => $type_sources ){
        if ( empty( $type_sources["sources"] ) ) continue;
        foreach( $type_sources["sources"] as $type_source )
        $all_sources[] = $type_source;
      }
    }

    if ( empty( $all_sources ) )
    return null;

    $source_by_method = [];

    foreach( $all_sources as $source ){

      if ( empty( $source["_raw"] ) )
      continue;

      $source_d = $source["_raw"];

      if ( $source_d["download_able"] == -2 )
      continue;

      if ( $from == "fe" )
      unset( $source["_raw"] );

      if ( $source_d["download_able"] == 1 && !empty( bof()->user->get()->extra["roles"]["download_out"] ) )
      $source_by_method["out"][ $source["hash"] ] = $source;

      if ( ( $source_d["download_able"] == -1 || ( $source_d["download_able"] == 1 && empty( $source_d["data_decoded"]["encrypted_version"] ) ) ) &&
      !empty( bof()->user->get()->extra["roles"]["download_in"] ) )
      $source_by_method["in"][ $source["hash"] ] = $source;


    }

    return $source_by_method;

  }

}

?>
