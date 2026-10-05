<?php

class seo extends bof_type_class {

  public function url( $object, $item, $itemUrlKey="seo_url" ){

    if ( $object == "user" )
    $itemUrlKey = "username";

    if ( empty( $item[$itemUrlKey] ) )
    return false;

    if ( is_string( $object ) )
    $object = bof()->object->__get( $object );

    if ( !$object->method_exists( "bof_client" ) )
    return $item[$itemUrlKey];

    $prefix = $object->bof_client()["single_url_prefix"];

    return "{$prefix}/{$item[$itemUrlKey]}";

  }

  public function fetch( $args=[], $full=false ){

    $object = null;
    $item = null;
    $seo_data = null;
    $lang = bof()->user->check()->language;
    $page_name = false;
    $page = array(
      "title_hook" => 404
    );
    if( $args )
    extract( $args );

    if ( empty( $seo_data ) && !empty( $item["seo_data_decoded"] ) )
    $seo_data = $item["seo_data_decoded"];

    if ( !empty( $seo_data ) && empty( $seo_data["image_id"] ) && !empty( $item["seo_image"] ) )
    $seo_data["image_id"] = $item["seo_image"];

    // Absolute data ( user/developer defined )
    if ( !empty( $seo_data["title"] ) )
    $_title = $_title_abs = $seo_data["title"];
    if ( !empty( $seo_data["description"] ) )
    $_description = $_description_abs = $seo_data["description"];
    if ( !empty( $seo_data["tags"] ) )
    $_tags = $_tags_abs = $seo_data["tags"];
    if ( !empty( $seo_data["image"] ) )
    $_image = $_image_abs_direct = $seo_data["image"];
    if ( empty( $seo_data["image"] ) && !empty( $seo_data["image_id"] ) ){
      $get_image = bof()->object->file->select(["ID"=>$seo_data["image_id"]]);
      if ( $get_image )
      $_image = $image_abs_file = $get_image["image_original"];
    }

    // Absolute data ( user/developer defined - per language )
    if ( $lang && !empty( $seo_data[ $lang ]["title"] ) )
    $_title = $_title_abs_lang = $seo_data[ $lang ]["title"];
    if ( $lang && !empty( $seo_data[ $lang ]["description"] ) )
    $_description = $_description_abs_lang = $seo_data[ $lang ]["description"];
    if ( $lang && !empty( $seo_data[ $lang ]["tags"] ) )
    $_tags = $_tags_abs_lang = $seo_data[ $lang ]["tags"];

    // Organic data ( per object/item )
    if ( !empty( $object ) && !empty( $item ) ){

      $the_object = bof()->object->__get( $object );

      // organic
      if ( empty( $_title ) ){
        foreach( bof()->object->parse_caller( $the_object )->parsed->columns as $column_name => $column_args ){
          if ( !empty( $column_args["bofAdmin"]["object"]["seo_slug_source"] ) && !empty( $item[ $column_name ] ) ){
            $_title = $_title_organic = $item[ $column_name ];
            break;
          }
        }
      }

      if ( empty( $_image ) && !empty( $item["cover_id"] ) ){
        $get_image = bof()->object->file->select(["ID"=>$item["cover_id"]]);
        if ( $get_image )
        $_image = $image_organic = $get_image["image_original"];
      }

      if ( empty( $_image ) && !empty( $item["remote_cover"] ) ){
        $_image = $item["remote_cover"];
      }

      // organic - custom
      if ( $the_object->method_exists( "seo" ) ){

        $organic_func_data = $the_object->seo( $item, $full );
        if ( empty( $_title ) && !empty( $organic_func_data["title"] ) )
        $_title = $_title_organic_func = $organic_func_data["title"];

        if ( empty( $_description ) && !empty( $organic_func_data["description"] ) )
        $_description = $_description_organic_func = $organic_func_data["description"];

        if ( empty( $_tags ) && !empty( $organic_func_data["tags"] ) )
        $_tags = $_tags_organic_func = $organic_func_data["tags"];

        if ( empty( $_image ) && !empty( $organic_func_data["image"] ) )
        $_image = $_image_organic_func = $organic_func_data["image"];

      }

    }

    // get page data
    if ( empty( $_title ) && !empty( $page_name ) && bof()->getName() == "bof_client" ){
      $allPages = bof()->client_config->get_pages();
      if ( !empty( $allPages[ $page_name ] ) ){
        $page = $allPages[ $page_name ];
      }
    }

    // page title
    if ( empty( $_title ) && ( !empty( $page["title"] ) || !empty( $page["title_hook"] ) ) )
    $_title = !empty( $page["title_hook"] ) ? bof()->object->language->turn( $page["title_hook"], [], [ "uc_first" => true, "lang" => "users" ] ) : $page["title"];

    if ( $full && $item && $object ){

      if ( empty( $item["title"] ) && !empty( $item["name"] ) ) $item["title"] = $item["name"];
      if ( empty( $item["title"] ) && !empty( $item["username"] ) ) $item["title"] = $item["username"];

      $_f_data = bof()->object->db_setting->get( "seo_{$object}" );
      if ( !empty( $_f_data ) ){
        if ( !empty( $_f_data["title"] ) ) $_f_title = bof()->object->language->paste_params( $_f_data["title"], $item );
        if ( !empty( $_f_data["desc"] ) ) $_f_description = bof()->object->language->paste_params( $_f_data["desc"], $item );
      }

      if ( !empty( $_f_title ) && empty( $_title_abs ) ) $_title = $_f_title;
      if ( !empty( $_f_description ) && empty( $_description_abs ) ) $_description = $_f_description;

    }

    $seo_output = array(
      "title" => htmlspecialchars_decode( strval( $_title ), ENT_QUOTES ) . " - " . bof()->object->db_setting->get("sitename"),
      "description" => !empty( $_description ) ? $_description : null,
      "tags" => !empty( $_tags ) ? $_tags : null,
      "image" => !empty( $_image ) ? $_image : null,
      "url" => $full ? web_address . $this->_bof_this->url( $object, $item ) : null
    );

    return $seo_output;

  }

  public function get_social_links_map(){

    return array(
      "facebook" => array(
        "name" => "Facebook",
        "url_format" => "https://www.facebook.com/{slug}/",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-\?\="
          )
        )
      ),
      "twitter" => array(
        "name" => "X",
        "url_format" => "https://twitter.com/{slug}",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "linkedin" => array(
        "name" => "LinkedIn",
        "url_format" => "https://www.linkedin.com/in/{slug}/",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "spotify" => array(
        "name" => "Spotify",
        "url_format" => "https://open.spotify.com/user/{slug}",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "soundcloud" => array(
        "name" => "SoundCloud",
        "url_format" => "https://soundcloud.com/{slug}",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "instagram" => array(
        "name" => "Instagram",
        "url_format" => "https://www.instagram.com/{slug}/",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "vk" => array(
        "name" => "VK.com",
        "url_format" => "https://vk.com/{slug}/",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "website" => array(
        "name" => "Website",
        "url_format" => "{slug}",
        "validator" => array(
          "url",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
      "tiktok" => array(
        "name" => "TikTok",
        "url_format" => "https://tiktok.com/@{slug}",
        "validator" => array(
          "username",
          array(
            "empty()",
            "regex" => "a-zA-Z0-9_.\-"
          )
        )
      ),
    );

  }

}

?>
