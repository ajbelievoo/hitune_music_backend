<?php

if ( !defined( "bof_root" ) ) die;

// BusyOwlFramework Client-Area Helper
class bofClient {

  private $objects = array(
    "user" => array(
      "single" => true,
      "search" => false
    ),
    "ugc_playlist" => array(
      "single" => true,
      "search" => true
    ),
    "ugc_property" => array(),
    "ugc_action" => array(),
    "b_post" => array(
      "single" => true,
      "search" => true
    ),
    "b_tag" => array(
      "single" => true,
      "search" => true
    ),
    "b_category" => array(
      "single" => true,
      "search" => true
    ),
  );
  private $cache = array(
    "table_i" => 1
  );
  private $custom_widget_parsers = array();

  public function _get_objects(){

    $objects = $this->objects;
    $objects["page"] = array(
      "single" => true,
      "search" => false
    );
    return $objects;

  }
  public function _add_object( $name, $args=[] ){

    $single = true;
    $list = false;
    $search = true;
    extract( $args );

    $this->objects[ $name ] = array(
      "single" => $single,
      "list" => $list,
      "search" => $search
    );

    return true;

  }
  public function _get_setting(){
    return $this->setting;
  }
  public function _add_setting( $name, $data ){
    $this->setting[ $name ] = $data;
  }

  public function __parse_widget( $object_name, $the_object, $data, $cache=false, $fromBE=false ){

    $this->cache["table_i"] = 1;

    if ( !empty( $data["object"]["whereArray"]["user_roles_only"] ) ){
      $_user_roles = bof()->user->get()->extra["role_ids"];
      if ( empty( array_intersect( $_user_roles, explode( ",", $data["object"]["whereArray"]["user_roles_only"] ) ) ) )
      return false;
      unset( $data["object"]["whereArray"]["user_roles_only"] );
    }

    if ( !empty( $data["object"]["whereArray"]["user_roles_exclude"] ) ){
      $_user_roles = bof()->user->get()->extra["role_ids"];
      if ( !empty( array_intersect( $_user_roles, explode( ",", $data["object"]["whereArray"]["user_roles_exclude"] ) ) ) )
      return false;
      unset( $data["object"]["whereArray"]["user_roles_exclude"] );
    }

    $validated_data = $data;
    $validated_data["widget_type"] = !empty( $data["widget_type"] ) ? $data["widget_type"] : ( !empty( $data["ID"] ) ? $data["ID"] : null );
    $classes = !empty( $data["display"]["classes"] ) ? $data["display"]["classes"] : [];
    $classes[] = "type_{$data["display"]["type"]}";
    $classes[] = "c_o_type_{$object_name}";

    $widget_object_name = null;
    $widget_the_object = null;

    if ( !empty( $data["object"]["name"] ) ){
      $widget_object_name = $data["object"]["name"];
      $_dff = bof()->general->get_full_fall();
      bof()->general->set_full_fall(false);
      try {
        $widget_the_object = bof()->object->__get( $widget_object_name );
      } catch( bofException|Exception $err ){
        bof()->general->set_full_fall( $_dff );
        return false;
      }
      bof()->general->set_full_fall( $_dff );
      $validated_data["display"]["o_type"] = $widget_object_name;
      $classes[] = "o_type_{$widget_object_name}";
    }

    if ( empty( $data["items"] ) && isset( $data["object"]["whereArray"] ) ? 
    !empty( $data["object"]["name"] ) && ( in_array( $data["object"]["name"], array_keys( $this->_get_objects() ), true ) || $data["object"]["name"] == "search_history" ) 
    : false ){

      if ( !empty( $data["object"]["selectArray"]["order_by"] ) ? substr( $data["object"]["selectArray"]["order_by"], -4 ) == "_ASC" : false ){
        $data["object"]["selectArray"]["order_by"] = substr( $data["object"]["selectArray"]["order_by"], 0, -4 );
        $data["object"]["selectArray"]["order"] = "ASC";
      }

      $widget_items = $widget_the_object->select(
        $data["object"]["whereArray"],
        array_merge(
          $data["object"]["selectArray"],
          array(
            "as_widget" => true,
            "empty_select" => true,
            "display_data" => $data["display"],
            "bof_cache" => bof()->object->core_setting->get("debug") ? false : $cache,
            "bof_cache_seed" => bof()->object->language->get_users()
          )
        )
      );

      $validated_data["items"] = $widget_items;
      unset( $validated_data["object"] );

    }

    if ( !empty( $validated_data["items"] ) ){

      foreach( $validated_data["items"] as &$item ){
        $item_object_name = !empty( $item["object_type"] ) ? $item["object_type"] : $widget_object_name;
        $item_the_object = !empty( $item["object_type"] ) ? bof()->object->__get( $item["object_type"] ) : $widget_the_object;
        $item = $this->__parse_item( $item_object_name, $item_the_object, $item, $data["display"] );
      }

      if ( $data["display"]["type"] == "slider" ){

        $items_count = count( $validated_data["items"] );
        $item_per_row = ceil( $items_count / $data["display"]["slider_rows"] );
        for( $i=1; $i<=$data["display"]["slider_rows"]; $i++ ){
          $items_by_row[ $i ] = array_slice( $validated_data["items"], ($i-1)*$item_per_row, $item_per_row );
        }
        $validated_data["items"] = $items_by_row;

      }

    }
    else {
      $classes[] = "zero_items";
    }

    if ( $data["display"]["type"] == "table" ){

      if ( empty( $data["display"]["table_hide_cover"] ) ){
        array_unshift( $validated_data["display"]["table_columns"], [ "val" => "cover", "class" => "cover" ] );
        array_unshift( $validated_data["display"]["table_labels"], [ "val" => bof()->object->language->turn("item",[],["uc_first"=>true,"lang"=>"users"]), "class" => "cover" ] );
      }

      array_unshift( $validated_data["display"]["table_columns"], [ "val" => "title", "class" => "title" ] );
      array_unshift( $validated_data["display"]["table_labels"], [ "val" => "", "class" => "title" ] );

      if ( !empty( $data["display"]["table_count"] ) ){
        array_unshift( $validated_data["display"]["table_columns"], [ "val" => "i", "class" => "i" ] );
        array_unshift( $validated_data["display"]["table_labels"], [ "val" => "i", "class" => "i" ] );
      }

      array_push( $validated_data["display"]["table_columns"], [ "val" => "", "class" => "buttons" ] );
      array_push( $validated_data["display"]["table_labels"], [ "val" => "", "class" => "buttons" ] );

    }

    if ( empty( $data["display"]["title"] ) ) $classes[] = "no_title";
    else $classes[] = "has_title";
    if ( !empty( $data["display"]["bg_img"] ) ) $classes[] = "has_bg_img";
    else $classes[] = "no_bg_img";

    if ( $data["display"]["type"] == "slider" ){
      $classes[] = "size_{$data["display"]["slider_size"]}";
      $classes[] = "rows_{$data["display"]["slider_rows"]}";
      if ( !empty( $data["display"]["slider_mason"] ) )
      $classes[] = "mason";
      else
      $classes[] = "liquid";
    }
    elseif( $data["display"]["type"] == "list" ){
      $classes[] = "cols_{$data["display"]["list_columns"]}";
    }

    if ( !empty( $data["display"]["bg_img"] ) ? !empty( $data["display"]["bg_img"]["image_strings"] ) : false ){
      $validated_data["display"]["bg_img"] = $data["display"]["bg_img"]["image_strings"]["1"]["html"];
    }

    if ( empty( $data["display"]["link"] ) && !empty( $data["display"]["pagination"] ) && !empty( $widget_the_object ) ){

      $page = bof()->nest->user_input( "get", "page", "int", [ "min" => 2 ], 1 );
      $_SA = $data["object"]["selectArray"];
      if ( !empty( $_SA["page"] ) ) unset( $_SA["page"] );
      $has_more = $widget_the_object->select(
        $data["object"]["whereArray"],
        array_merge(
          $_SA,
          array(
            "offset" => ($page)*$data["object"]["selectArray"]["limit"],
            "limit" => 1,
            "clean" => false,
            "columns" => "ID",
            "cache" => false,
            "cache_load_rt" => false,
            "empty_select" => true,
            "bof_cache" => bof()->object->core_setting->get("debug") ? false : $cache,
          )
        )
      );

      if ( $has_more ){
        $validated_data["display"]["link"] = $data["display"]["pagination"] === true ? "list/{$data["ID"]}" : $data["display"]["pagination"];
        if ( $data["display"]["type"] == "table" ){
          $validated_data["items"][] = array(
            "id" => "more",
            "ot" => null,
            "url" => null,
            "hash" => null,
            "tds" => array(
              array(
                "val" => "<a class='more_a' href='{$validated_data["display"]["link"]}'>". bof()->object->language->turn("more",[],["uc_first"=>true,"lang"=>"users"]) ."</a>",
                "class" => "more",
                "attr" => " colspan='".(!empty($validated_data["items"])?count(reset($validated_data["items"])["tds"]):"")."' "
              )
            )
          );
        }
      }

    }

    if ( !empty( $validated_data["display"]["link"] ) ){

      $classes[] = "linked";

      if ( !empty( $validated_data["display"]["link_on_bottom"] ) )
      $classes[] = "link_on_bottom";

    }

    if ( !empty( $validated_data["display"]["sub_data"] ) )
    $classes[] = "has_sub_data";


    if ( !empty( $widget_the_object ) ? ( $widget_the_object->method_exists("bof_client") ? !empty( $widget_the_object->bof_client()["buttons"] ) : false ) : false ){
      $validated_data["buttons"] = $this->__parse_widget_buttons( $widget_object_name, $widget_the_object, $widget_the_object->bof_client()["buttons"] );
    }

    if ( ( $data["display"]["type"] == "grid" || !empty( $data["args"]["gridType"] ) ) ? !empty( $data["display"]["widgets"] ) : false ){
      foreach( $validated_data["display"]["widgets"] as &$_widget ){
        $_widget = $this->__parse_widget( $object_name, $the_object, $_widget, true );
      }
      $classes[] = "cols_c_" . count( explode( "_", $data["args"]["columns"] ) );
      $classes[] = "cols_" . $data["args"]["columns"];
      if ( !empty( $data["args"]["fitMain"] ) )
      $classes[] = "fitMain";
    }

    $validated_data["display"]["classes"] = implode( " ", $classes );

    if ( !empty( $data["display"]["type"] ) ? $data["display"]["type"] == "ft_list" : false ){

      $lang = bof()->user->check()->language;

      $args = $data["args"];

      $html = "<div class='feature_list'>";
      if ( !empty( $args["features"] ) ){
        $features = json_decode( urldecode( $args["features"] ), true );
        if ( $features ){
          foreach( $features as $ft_id => $ft ){
            $html .= "<div class='feature'>";
            $html .= "<div class='icon'><span class='mdi mdi-{$ft["icon"]}'></span></div>";
            $html .= "<div class='title'>". ( !empty( $ft["title_{$lang}"] ) ? $ft["title_{$lang}"] : $ft["title"] ) ."</div>";
            $html .= "<div class='text'>". ( !empty( $ft["text_{$lang}"] ) ? $ft["text_{$lang}"] : $ft["text"] ) ."</div>";
            $html .= "</div>";
          }
        }
      }
      $html .= "</div>";

      if ( !empty( $args["background_color"] ) ){
        $validated_data["display"]["bg_img"] = "<div class=\"color_bg\" style=\"background: {$args["background_color"]}\"></div>" . ( !empty( $validated_data["display"]["bg_img"] ) ? $validated_data["display"]["bg_img"] : "" );
        $validated_data["display"]["classes"] = str_replace( "no_bg_img", "has_bg_img has_color_bg", $validated_data["display"]["classes"] );
      }

      if ( !empty( $args["color"] ) ){
        $validated_data["display"]["attr"] = "style=\"color: {$args["color"]}\"";
      }

      $validated_data["display"]["type"] = "html";
      $validated_data["display"]["html"] = $html;

    }
    elseif ( !empty( $data["display"]["type"] ) ? $data["display"]["type"] == "steps_list" : false ){

      $lang = bof()->user->check()->language;

      $args = $data["args"];

      $html = "<div class='steps_list'>";
      if ( !empty( $args["features"] ) ){
        $features = json_decode( urldecode( $args["features"] ), true );
        if ( $features ){
          $i=0;
          foreach( $features as $ft_id => $ft ){
            $i++;
            $html .= "<div class='step_list'>";
            $html .= "<div class='i'>{$i}</div>";
            $html .= "<div class='icon'><span class='mdi mdi-{$ft["icon"]}'></span></div>";
            $html .= "<div class='title'>". ( !empty( $ft["title_{$lang}"] ) ? $ft["title_{$lang}"] : $ft["title"] ) ."</div>";
            $html .= "<div class='text'>". ( !empty( $ft["text_{$lang}"] ) ? $ft["text_{$lang}"] : $ft["text"] ) ."</div>";
            $html .= "</div>";
          }
        }
      }
      $html .= "</div>";

      if ( !empty( $args["background_color"] ) ){
        $validated_data["display"]["bg_img"] = "<div class=\"color_bg\" style=\"background: {$args["background_color"]}\"></div>" . ( !empty( $validated_data["display"]["bg_img"] ) ? $validated_data["display"]["bg_img"] : "" );
        $validated_data["display"]["classes"] = str_replace( "no_bg_img", "has_bg_img has_color_bg", $validated_data["display"]["classes"] );
      }

      if ( !empty( $args["color"] ) ){
        $validated_data["display"]["attr"] = "style=\"color: {$args["color"]}\"";
      }

      $validated_data["display"]["type"] = "html";
      $validated_data["display"]["html"] = $html;

    }
    elseif ( !empty( $data["display"]["type"] ) ? $data["display"]["type"] == "cta" : false ){

      $lang = bof()->user->check()->language;
      $args = $data["args"];

      if ( !empty( $args["height"] ) )
      $validated_data["display"]["classes"] .= $args["height"] == "full" ? " full_vh" : " auto_height";

      if ( !empty( $args["font_size"] ) )
      $validated_data["display"]["classes"] .= " font_size_{$args["font_size"]}";

      if ( !empty( $args["img_place"] ) )
      $validated_data["display"]["classes"] .= " img_place_{$args["img_place"]}";

      if ( !empty( $args["font_color"] ) ){
        $validated_data["display"]["attr"] = "style=\"color: {$args["font_color"]}\"";
      }

      $html = "<div class='text_wrapper'>";

      $html .= "<div class='title'>{$validated_data["display"]["title"]}</div>";

      if ( !empty( $validated_data["display"]["sub_data"] ) )
      $html .= "<div class='sub_title'>{$validated_data["display"]["sub_data"]}</div>";

      if ( bof()->user->check()->language ? bof()->user->check()->language != "en" : false ){
        $userLang = bof()->user->check()->language;
        if ( !empty( $args["btn_title_1_{$userLang}"] ) ) $args["btn_title_1"] = $args["btn_title_1_{$userLang}"];
        if ( !empty( $args["btn_link_1_{$userLang}"] ) ) $args["btn_link_1"] = $args["btn_link_1_{$userLang}"];
        if ( !empty( $args["btn_title_2_{$userLang}"] ) ) $args["btn_title_2"] = $args["btn_title_2_{$userLang}"];
        if ( !empty( $args["btn_link_2_{$userLang}"] ) ) $args["btn_link_2"] = $args["btn_link_2_{$userLang}"];
      }

      if ( !empty( $args["btn_title_1"] ) || !empty( $args["btn_title_2"] ) )
      $html .= "<div class='buttons'>";

      if ( !empty( $args["btn_title_1"] ) && !empty( $args["btn_link_1"] ) )
      $html .= "<a href='{$args["btn_link_1"]}' class='btn btn-secondary btn-first' " . ( !empty( $args["font_color"] ) ? "style='color:{$args["font_color"]}'" : "" ) . ">{$args["btn_title_1"]}</a>";

      if ( !empty( $args["btn_title_2"] ) && !empty( $args["btn_link_2"] ) )
      $html .= "<a href='{$args["btn_link_2"]}' class='btn btn-secondary btn-second' " . ( !empty( $args["font_color"] ) ? "style='color:{$args["font_color"]}'" : "" ) . ">{$args["btn_title_2"]}</a>";

      if ( !empty( $args["btn_title_1"] ) || !empty( $args["btn_title_2"] ) )
      $html .= "</div>";


      $html .= "</div>";
      $html .= "<div class='image_wrapper'>";

      if ( !empty( $args["img"] ) ){
        $img = bof()->object->file->select(["ID"=>$args["img"]]);
        if ( $img ? !empty( $img["image_strings"] ) : false ){
          $html .= $img["image_strings"]["1"]["html"];
          $validated_data["display"]["classes"] .= " has_img";
        }
        else {
          $validated_data["display"]["classes"] .= " no_img";
        }
      }
      else {
        $validated_data["display"]["classes"] .= " no_img";
      }

      $html .= "</div>";

      if ( empty( $validated_data["display"]["bg_img"] ) ? !empty( $args["background_img_url"] ) : false ){
        $validated_data["display"]["bg_img"] = "<img src='{$args["background_img_url"]}'>";
        $validated_data["display"]["classes"] = str_replace( "no_bg_img", "has_bg_img", $validated_data["display"]["classes"] );
      }

      if ( !empty( $args["background_color"] ) ){
        $validated_data["display"]["bg_img"] = "<div class=\"color_bg\" style=\"background: {$args["background_color"]}\"></div>" . ( !empty( $validated_data["display"]["bg_img"] ) ? $validated_data["display"]["bg_img"] : "" );
        $validated_data["display"]["classes"] = str_replace( "no_bg_img", "has_bg_img has_color_bg", $validated_data["display"]["classes"] );
      }
      else {
        $validated_data["display"]["classes"] .= " no_bg_color";
      }

      if ( !empty( $args["background_img_dim"] ) ? $args["background_img_dim"] != "none" : false ){
        $validated_data["display"]["bg_img"] = "<div class=\"color_bg dimmer {$args["background_img_dim"]}\"></div>" . ( !empty( $validated_data["display"]["bg_img"] ) ? $validated_data["display"]["bg_img"] : "" );
        $validated_data["display"]["classes"] .= " bg_dimmed";
      }

      $validated_data["display"]["type"] = "html";
      $validated_data["display"]["html"] = $html;
      $validated_data["display"]["title"] = "";
      $validated_data["display"]["sub_data"] = "";

    }

    if ( $this->custom_widget_parsers && !empty( $data["display"]["type"] ) ? in_array( $data["display"]["type"], array_keys( $this->custom_widget_parsers ), true ) : false ){
      $this->custom_widget_parsers[ $data["display"]["type"] ]( $object_name, $the_object, $data, $validated_data );
    }

    unset( $validated_data["args"] );

    return $validated_data;

  }
  public function __parse_item( $object_name, $the_object, $data, $displayData ){

    if ( empty( $data ) )
    $data = [];

    $data["id"] = uniqid();
    $data["ot"] = !empty( $data["ot"] ) ? $data["ot"] : $object_name;

    if ( !empty( $data["raw"] ) )
    $data["url"] = !empty( $data["raw"]["url"] ) ? $data["raw"]["url"] : bof()->seo->url( $the_object, $data["raw"] );

    if ( !empty( $data["raw"]["hash"] ) ) $data["hash"] = $data["raw"]["hash"];

    // Cover
    $cover_size = 3;
    if ( $displayData["type"] == "slider" ? $displayData["slider_size"] == "large" : false )
    $cover_size = 2;
    if ( $displayData["type"] == "slider" ? $displayData["slider_size"] == "small" : false )
    $cover_size = 4;
    if ( $displayData["type"] == "table" )
    $cover_size = 12;

    if ( bof()->request->is_mobile() ){
      $cover_size = floor( $cover_size / 2 );
    }

    if ( !empty( $data["cover"]["image_strings"] ) )
    $data["cover"] = $data["cover"]["image_strings"][ $cover_size ]["html"];

    else {

      $placeholder = bof()->object->db_setting->get( "placeholder" );
      if ( $placeholder ){
        $placeholder = bof()->object->file->select( [ "ID" => $placeholder ] );
        if ( !empty( $placeholder["image_strings"] ) )
        $data["cover"] = $placeholder["image_strings"][1]["html"];
      }

    }

    // Table Columns
    if ( $displayData["type"] == "table" ? !empty( $displayData["table_columns"] ) && !empty( $data["title"] ) : false ){

      $data[ "title" ] = "<div class='title'>".(!empty($data["url"])?"<a href='{$data["url"]}'>{$data["title"]}</a>":$data["title"])."<div class='subData'><a ".(!empty($data["sub_link"])?"href='{$data["sub_link"]}'":"").">".(!empty($data["sub_data"])?$data["sub_data"]:"")."</a></div></div>";

      $data[ "tds" ] = [];

      if ( !empty( $displayData["table_count"] ) ){
        $data[ "tds" ][] = array( "val" => "<span class='num'>".((is_int($displayData["table_count"])?$displayData["table_count"]:0)+$this->cache["table_i"])."</span><div class='button_wrapper play _play'><span class='mdi mdi-play'></span></div>", "class" => "i" );
        $this->cache["table_i"]++;
      }

      if ( empty( $displayData["table_hide_cover"] ) ){
        $data[ "tds" ][] = array( "val" => ( !empty( $data["cover"] ) ? "<div class='cover_wrapper'>{$data["cover"]}<div class='button_wrapper play _play'><span class='mdi mdi-play'></span></div></div>" : "" ), "class" => "cover" );
      }

      $data[ "tds" ][] = array( "val" => $data["title"], "class" => "title" );
      foreach( $displayData["table_columns"] as $_edk => $_edArgs ){

        if ( is_int( $_edk ) ){
          $_edk = $_edArgs;
          $_edArgs = [];
        }

        $_edv = isset( $data["raw"][ $_edk ] ) ? $data["raw"][ $_edk ] : "";

        $_edcd = !empty( $the_object->columns()[ $_edk ] );
        $_edcs = [ "organic", !empty( $_edcd["validator"] ) ? "v_{$_edcd["validator"][0]}" : "v_unkown" ];
        if ( !empty( $_edArgs["classes"] ) )
        $_edcs = array_merge( $_edcs, $_edArgs["classes"] );

        if ( !empty( $_edcd["input"]["type"] ) )
        $_edcs[] = "t_{$_edcd["input"]["type"]}";

        if ( !empty( $_edcd["validator"] ) ? ( $_edcd["validator"][0] == "int" && !empty( $_edv ) ) : false )
        $_edv = number_format( $_edv );

        if ( $_edk == "duration" )
        $_edv = bof()->general->duration_hr( $_edv )["string"];

        if ( !empty( $_edArgs["func"] ) )
        $_edv = $_edArgs["func"]( $data, $displayData, bof() );

        $data[ "tds" ][] = array(
          "val" => $_edv,
          "class" => implode( " ", $_edcs )
        );

      }

      $data[ "tds" ][] = array( "val" => '<div class="button_wrapper more"><span class="mdi mdi-dots-horizontal"></span></div>', "class" => "buttons" );

      unset( $data["title"], $data["sub_data"], $data["cover"] );

    }

    // Buttons
    if ( !empty( $data["buttons"] ) ){
      $data["buttons"] = $data["buttons"];
    }

    unset( $data["raw"] );
    return $data;

  }
  public function __parse_widget_buttons( $object_name, $the_object, $buttons ){

    $active_buttons = [];
    foreach( [ "play", "like", "subscribe", "playlist", "share", "link", "purchase" ] as $_k ){
      if ( !empty( $buttons[ $_k ] ) ? $buttons[ $_k ] == "organic" : false ){
        $active_buttons[ $_k ] = $_k == "purchase" ? "dynamic" : "organic";
      }
    }

    foreach( [ "before", "after" ] as $_k ){

      if ( empty( $buttons["extra_{$_k}"] ) )
      continue;

      foreach( $buttons["extra_{$_k}"] as $_ek => $_ev ){
        if ( $_k == "before" ) $active_buttons = array_merge( [ $_ek => "dynamic" ], $active_buttons );
        else $active_buttons = array_merge( $active_buttons, [ $_ek => "dynamic" ] );
      }

    }

    return $active_buttons;

  }
  public function __parse_item_buttons( $object_name, $the_object, $item, $buttons, $parseDynamicButtons=false, $caller=null ){

    if ( !empty( $buttons["extra_before"] ) ){
      foreach( $buttons["extra_before"] as $extraButtonKey => $extraButton )
      $_buttons[ $extraButtonKey ] = $extraButton;
    }

    if ( !empty( $buttons["purchase"] ) && !empty( $item["hash"] ) ){

      $_buttons["purchase"] = array(

        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          $the_object = $args["the_object"];

          if ( bof()->object->ugc_property->owned( $args["object_name"], $item )["access"] )
          return;

          return array(
            "icon" => "cart",
            "title" => "purchase",
            "action" => "purchase"
          );

          }

      );

    }
    if ( !empty( $buttons["play"] ) && !empty( $item["hash"] ) && $caller !== "source" ){
      $_buttons["play"] = array(
        "icon" => "play",
        "hook" => "play",
        "action" => "play",
        "class" => "_play bof_{$object_name}_{$item["hash"]}",
        "attr" => "data-play='{$item["hash"]}'",
      );
      $_buttons["play_next"] = array(
        "icon" => "playlist-music",
        "hook" => "play_next",
        "action" => "play_next",
        "attr" => "data-play='{$item["hash"]}'",
      );
      $_buttons["play_last"] = array(
        "icon" => "playlist-music-outline",
        "hook" => "add_to_queue",
        "action" => "play_last",
        "attr" => "data-play='{$item["hash"]}'",
      );
    }
    if ( !empty( $buttons["download"] ) && !empty( $item["hash"] ) ){
      $_buttons["download"] = array(
        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          $the_object = $args["the_object"];

          if ( empty( $item["bof_dir_sources"] ) )
          $item = $the_object->select(
            array(
              "ID" => $item["ID"]
            ),
            array(
              "muse_source" => true,
              "_eq" => array(
                "sources" => array(
                  "for_download" => true
                ),
              )
            )
          );

          $source_by_method = bof()->source->get_downloads( $args["object_name"], $the_object, $item );
          if ( empty( $source_by_method ) )
          return;

          return array(
            "icon" => "download",
            "hook" => "download",
            "action" => "download",
            "sources" => $source_by_method
          );

        }
      );
    }
    if ( !empty( $buttons["download_child"] ) && !empty( $item["hash"] ) ){
      $_buttons["download_child"] = array(
        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          $the_object = $args["the_object"];

          if ( $the_object->method_exists("download_child") ){

            $newItem = $the_object->download_child( $button, $item, $args );

            $source_by_method = bof()->source->get_downloads( $args["object_name"], $the_object, $newItem );

            if ( empty( $source_by_method ) )
            return;

            return array(
              "icon" => "download",
              "hook" => "download",
              "action" => "download_child",
              "sources" => $source_by_method
            );

          }


        }
      );
    }
    if ( !empty( $buttons["like"] ) && !empty( $item["hash"] ) ){
      $_buttons["like"] = array(
        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          $liked = bof()->object->ugc_property->select(
            array(
              "user_id" => bof()->user->get()->ID,
              "type" => "like",
              "object_name" => $args["object_name"],
              "object_id" => $item["ID"]
            )
          );

          $button = array(
            "icon" => "heart",
            "hook" => !$liked? "like" : "unlike",
            "action" => !$liked? "like" : "unlike",
            "class" => !$liked?" _like bof_{$args["object_name"]}_{$item["hash"]} not_liked":" _like bof_{$args["object_name"]}_{$item["hash"]} liked",
          );

          return $button;

        }
      );
    }
    if ( !empty( $buttons["subscribe"] ) && !empty( $item["hash"] ) ? ( $object_name == "user" && bof()->user->get()->ID == $item["ID"] ? false : true ) : false ){
      $_buttons["subscribe"] = array(
        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          $subscribed = bof()->object->ugc_property->select(
            array(
              "user_id" => bof()->user->get()->ID,
              "type" => "subscribe",
              "object_name" => $args["object_name"],
              "object_id" => $item["ID"]
            )
          );

          $button = array(
            "icon" => "bell",
            "hook" => !empty( $args["object_name"] ) ? ( $args["object_name"] == "user" ? ( !$subscribed ? "follow" : "unfollow" ) : ( !$subscribed ? "subscribe" : "unsubscribe" ) ) : ( !$subscribed ? "subscribe" : "unsubscribe" ),
            "action" => !$subscribed ? "subscribe" : "unsubscribe",
            "class" => !$subscribed?" _subscribe bof_{$args["object_name"]}_{$item["hash"]} not_subscribed":" _subscribe bof_{$args["object_name"]}_{$item["hash"]} subscribed",
          );

          return $button;

        }
      );
    }
    if ( !empty( $buttons["playlist"] )  && !empty( $item["hash"] ) ){
      $_buttons["playlist"] = array(
        "icon" => "playlist-plus",
        "hook" => "add_to_playlist",
        "action" => "playlist",
        "class" => "with_child"
      );
    }
    if ( !empty( $buttons["link"] ) && !empty( $buttons["share"] ) && !empty( $item["hash"] ) ){
      $_buttons["share"] = array(
        "icon" => "share",
        "hook" => "share",
        "action" => "share",
        "attr" => "data-share='{$item["hash"]}'",
      );
    }
    if ( !empty( $buttons["link"] ) ){
      $_buttons["link"] = array(
        "icon" => "open-in-app",
        "hook" => "open",
        "url" => !empty( $item["url"] ) ? $item["url"] : bof()->seo->url( $the_object, $item )
      );
    }
    if ( !empty( $buttons["biography"] ) && !empty( $item["bio_content_html"] ) ){
      $_buttons["biography"] = array(
        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          if ( empty( $item["bio_content_html"] ) )
          return null;

          return array(
            "icon" => "fountain-pen-tip",
            "hook" => "biography",
            "action" => "view_bio",
          );

        }
      );
    }
    if ( !empty( $buttons["social_links"] ) && !empty( $item["external_addresses_decoded"] ) ){
      $_buttons["social_links"] = array(
        "dynamic" => true,
        "func" => function( $button, $item, $args ){

          $childs = [];
          foreach( bof()->seo->get_social_links_map() as $_ssK => $_ssSlug ){
            if ( !empty( $item["external_addresses_decoded"][$_ssK] ) ){
              $childs[] = array(
                "title" => $_ssSlug["name"],
                "url" => str_replace( "{slug}", $item["external_addresses_decoded"][$_ssK], $_ssSlug["url_format"] )
              );
            }
          }

          return array(
            "icon" => "fountain-pen-tip",
            "hook" => "social_links",
            "childs" => $childs
          );

        }
      );
    }
    if ( !empty( $buttons["extra_after"] ) ){
      foreach( $buttons["extra_after"] as $extraButtonKey => $extraButton )
      $_buttons[ $extraButtonKey ] = $extraButton;
    }

    foreach( $_buttons as $key => &$data ){

      if ( empty( $data["title"] ) && !empty( $data["hook"] ) )
      $data["title"] = bof()->object->language->turn( $data["hook"], [], [ "lang" => "users", "uc_first" => true ] );

      if ( empty( $data["dynamic"] ) )
      continue;

      if ( !$parseDynamicButtons ){
        $data["icon"] = "loading";
        $data["title"] = "<i>". bof()->object->language->turn( "loading", [], [ "lang" => "users", "uc_first" => true ] ) ."</i>";
        $data["attr"] = "id='dyna_{$key}'";
        unset( $data["func"] );
        continue;
      }

      if ( !$data["func"] )
      continue;

      $data = $data["func"]( $data, $item, array(
        "object_name" => $object_name,
        "the_object" => $the_object,
        "buttons" => $buttons,
        "_buttons" => $_buttons
      ) );

      if ( empty( $data ) )
      unset( $_buttons[ $key ] );

    }

    foreach( $_buttons as $key => &$data ){

      if ( !empty( $data["childs"] ) ){

        $data["class"] = "with_child";

        foreach( $data["childs"] as &$_c ){
          if ( empty( $_c["title"] ) && !empty( $_c["hook"] ) )
          $_c["title"] = bof()->object->language->turn( $_c["hook"], [], [ "lang" => "users", "uc_first" => true ] );
        }

      }

    }

    $_data = [];

    if ( !empty( $item["title"] ) || !empty( $item["name"] ) )
    $_data["_title"] = !empty( $item["title"] ) ? $item["title"] : $item["name"];

    if ( !empty( $item["bof_file_cover"]["image_strings"] ) ? is_array( $item["bof_file_cover"] ) : false )
    $_data["_cover"] = $item["bof_file_cover"]["image_strings"][3]["html"];

    return array(
      "items" => $_buttons,
      "data" => $_data
    );

  }
  public function __add_custom_widget_parser( $widget_name, $parser ){
    $this->custom_widget_parsers[ $widget_name ] = $parser;
  }

  public function _os( $object_name, $fromBE=false ){

    if ( bof()->object->core_setting->get( "client_private" ) ? ( !bof()->user->get()->logged && !$fromBE ) : false ){
      if ( $object_name == "page" ){
        $the_object = bof()->object->__get( $object_name );
        $request = $this->_os_parse_request( $object_name, $the_object );
        if ( empty( $request["item"]["data"]["public"] ) ){
          bof()->api->set_error("403", ["output_args" => ["turn" => false]]);
          return;
        }
      } else {
        bof()->api->set_error("403", ["output_args" => ["turn" => false]]);
        return;
      }
    }

    if ( !in_array( $object_name, array_keys( $this->_get_objects() ) ) )
    return false;

    $bof_args = $this->_get_objects()[ $object_name ];
    if ( empty( $the_object ) )
    $the_object = bof()->object->__get( $object_name );
    if ( empty( $request ) )
    $request = $this->_os_parse_request( $object_name, $the_object );

    if ( !$request ){
      bof()->response->set_header( "code", "HTTP/1.0 404 Not Found" );
      bof()->api->set_message( "404", [ "output_args" => [ "turn" => false ] ] );
      return false;
    }

    $_df_widgets = bof()->object->page->_get_widgets(array(
      "raw" => true,
    ))["items"];
    $widgets = [];

    if ( defined("bof_cache_entire_page") ? bof_cache_entire_page : false ){

      $bof_cache_query_hash  = md5( "bof_cache_entire_page" . $object_name );
      $bof_cache_params_hash = md5( bof()->nest->user_input( "get", "slug", "string" ) . ( bof()->request->is_mobile() ? "mob" : "desk" ) );
      $bof_cache_range = defined("bof_cache_range") ? bof_cache_range : "10 MINUTE";

      $bof_cache_load_query = bof()->db->query("SELECT * FROM _bof_cache_db WHERE query_hash = '{$bof_cache_query_hash}' AND params_hash = '{$bof_cache_params_hash}' AND time_add > SUBDATE( now(), INTERVAL {$bof_cache_range} ) ORDER BY time_add DESC LIMIT 1", null, true );

      if ( $bof_cache_load_query->num_rows ){

        $bof_cache_load_query_item = $bof_cache_load_query->fetch_assoc();

        $bof_cache_load_query_result = $bof_cache_load_query_item["results"] ? json_decode( $bof_cache_load_query_item["results"], 1 ) : null;

        if ( $bof_cache_load_query_result ){
          $widgets = $bof_cache_load_query_result;
          $loaded_entire_page_from_cache = true;
        }

      }

    }

    if ( !empty( $request["item"]["widgets"] ) && empty( $widgets ) ){
      foreach( $request["item"]["widgets"] as $i => $_widget ){
        if ( ( $_widget_parsed = bof()->bofClient->__parse_widget( $object_name, $the_object, $_widget, true, $fromBE ) ) ){

          if ( $_widget_parsed["display"]["classes"] ){
            $_dcs = explode( " ", $_widget_parsed["display"]["classes"] );
            if ( in_array( "zero_items", $_dcs, true ) && ( in_array( "type_slider", $_dcs, true ) || in_array( "type_table", $_dcs, true ) || in_array( "type_list", $_dcs, true ) || in_array( "type_chart", $_dcs, true ) ) )
            continue;
          }

          if ( empty( $_widget_parsed["items"] ) ? ( empty( $_df_widgets[ $_widget_parsed["display"]["type"] ] ) ? false : $_df_widgets[ $_widget_parsed["display"]["type"] ]["group"] == "content" ) : false ) {
            continue;
          }

          $_widget_parsed["display"]["classes"] .= " i{$i}";
          $widgets[] = $_widget_parsed;

        }
      }

      if ( !empty( $widgets ) ){
        foreach( $widgets as $i => &$widget ){

          $_pdcs = !empty( $widgets[$i-1] ) ? $widgets[$i-1] : false;
          $_ndcs = !empty( $widgets[$i+1] ) ? $widgets[$i+1] : false;

          if ( !empty( $_pdcs["display"]["classes"] ) ){
            $_pdcs = explode( " ", $_pdcs["display"]["classes"] );
            $_pdcc = array_intersect( $_pdcs, [ "has_bg_img", "has_bg_color" ] );
            if ( $_pdcc ) $widget["display"]["classes"] .= " pre_" . implode( " pre_", $_pdcc );
          }

          if ( !empty( $_ndcs["display"]["classes"] ) ){
            $_ndcs = explode( " ", $_ndcs["display"]["classes"] );
            $_ndcc = array_intersect( $_ndcs, [ "has_bg_img", "has_bg_color" ] );
            if ( $_ndcc ) $widget["display"]["classes"] .= " next_" . implode( " next_", $_ndcc );
          }

        }
      }
    }

    if ( defined("bof_cache_entire_page") ? bof_cache_entire_page && empty( $loaded_entire_page_from_cache ) : false ){

      try {
        $__d = $widgets ? json_encode( $widgets ) : null;
        $stmt = bof()->db->prepare("INSERT INTO _bof_cache_db ( query_hash, params_hash, results, time_expire ) VALUES ( ?, ?, ?, ADDDATE( now(), INTERVAL {$bof_cache_range}) ) ");
        $stmt->bind_param( "sss", $bof_cache_query_hash, $bof_cache_params_hash, $__d );
        $stmt->execute();
        $stmt->close();
      } catch( Exception|Error|bofException $err ){
      }

    }

    $item = !empty( $request["item"]["data"] ) ? $request["item"]["data"] : [];
    $page = !empty( $request["item"]["page"] ) ? $request["item"]["page"] : [];

    if ( !empty( $item ) && $the_object->method_exists( "bof_client" ) ? !empty( $the_object->bof_client()["buttons"] ) : false )
    $item["buttons"] = bof()->bofClient->__parse_item_buttons( $object_name, $the_object, $item, $the_object->bof_client()["buttons"] );
    if ( !empty( $item["buttons"]["link"] ) ) unset( $item["buttons"]["link"] );

    if ( $object_name == "page" ){
      $page["classes"] = !empty( $page["classes"] ) ? $page["classes"] : [];
      $page["classes"][] = "page_name_" . bof()->general->make_code( $item["name"] );
    }

    if ( !empty( $item["buttons"]["items"]["purchase"] ) ){
      $page["classes"][] = "purchasable";
      $item["purchased"] = false;
      if ( $item["price_d"] ){

        $item["purchased"] = bof()->object->ugc_property->owned( $object_name, $item, true )["access"] ? true : false;

        if ( $item["purchased"] )
        $page["classes"][] = "p_purchased";
        else
        $page["classes"][] = "p_priced";

      }
      else {
        $page["classes"][] = "p_free";
      }
    }
    else {
      $page["classes"][] = "unpurchasable";
    }

    if ( !empty( $page["classes"] ) )
    $page["classes"] = implode( " ", $page["classes"] );

    if ( empty( $item["bof_file_cover"] ) && !empty( $the_object->columns()["cover_id"] ) ){
      $placeholder = bof()->object->db_setting->get( "placeholder" );
      if ( $placeholder ){
        $placeholder = bof()->object->file->select( [ "ID" => $placeholder ] );
        $item["bof_file_cover"] = $placeholder;
      }
    }

    if ( empty( $item["bof_file_bg"] ) && !empty( $the_object->columns()["cover_id"] ) ){
      $placeholder = bof()->object->db_setting->get( "placeholder_bg" );
      if ( $placeholder ){
        $placeholder = bof()->object->file->select( [ "ID" => $placeholder ] );
        $item["bof_file_bg"] = $placeholder;
      }
    }

    $final_data = bof()->object->_publicize( $the_object, $item );
    if ( $object_name == "m_artist" ){
      $final_data["tracks"] = !empty($item["artist_tracks"]) ? $item["artist_tracks"] : [];
      $final_data["albums"] = !empty($item["artist_albums"]) ? $item["artist_albums"] : [];
      $final_data["related_artists"] = !empty($item["artist_related_artists"]) ? $item["artist_related_artists"] : [];
    }

    bof()->api->set_message( "ok", array(
      "data" => $final_data,
      "page" => $page,
      "widgets" => $widgets,
      "seo" => bof()->seo->fetch( array(
        "item" => $item,
        "object" => $object_name,
      ) )
    ) );
    return;

  }
  protected function _os_parse_request( $object_name, $the_object ){

    $slug = bof()->nest->user_input( "get", "slug", "string" );
    if ( !$slug && $object_name == "page" )
    $slug = "/";
    if ( !$slug ) return false;

    $item = $the_object->select(
      array(
        "seo_url" => $slug
      ),
      array(
        "client_single" => true,
      )
    );

    if ( !$item ) return false;
    $item["data"]["ot"] = !empty( $item["data"]["ot"] ) ? $item["data"]["ot"] : $object_name;

    // unique visit?
    if ( bof()->user->get()->ID ){

      $recorded_before = bof()->db->_select( array(
        "table" => bof()->object->core_setting->get( "api_request_log_table_name", null, [ "invalid_death" => true ] ),
        "where" => array(
          [ "endpoint_name", "=", "bofClient_single" ],
          [ "object_type", "=", $object_name ],
          [ "user_id", "=", bof()->user->get()->ID ],
          [ "bofClient_slug", "=", $slug ]
        ),
        "order_by" => "ID",
        "order" => "DESC",
        "columns" => "ID",
        "limit" => 2
      ) );

      if ( $recorded_before ? count( $recorded_before ) < 2 : true )
      $unique_visit = true;

    }

    $columns = $the_object->columns();
    if ( $the_object->method_exists( "stats_columns" ) )
    $stats_column = $the_object->stats_columns();

    if ( in_array( "s_views", array_keys( $columns ), true ) || ( !empty( $stats_column ) ? in_array( "views", $stats_column, true ) : false ) ){
      $the_object->update(
        array(
          "ID" => $item["data"]["ID"],
        ),
        array(
          "s_views" => $item["data"]["s_views"] + 1,
          "s_views_unique" => $item["data"]["s_views_unique"] + ( !empty( $unique_visit ) ? 1 : 0 ),
        ),
        false
      );
    }

    return array(
      "slug" => $slug,
      "item" => $item
    );

  }

  public function _ol_parse_request( $widget_hash ){

    if ( substr( $widget_hash, 0, 4 ) == "bof_" ){
      $object_name = substr( $widget_hash, 4 );
      $slug = bof()->nest->user_input( "get", "slug", "string" );
      $widget_name = bof()->nest->user_input( "get", "widget", "string" );
      if ( $slug && $widget_name && bof()->nest->validate( $object_name, "bofClient_object" ) ){

        $the_object = bof()->object->__get( $object_name );
        if ( $the_object->method_exists( "clean_client_single_widget" ) ){
          $the_item = $the_object->select(array("seo_url"=>$slug),array("client_widget"=>true));
          if ( $the_item ){
            if ( ( $get_widget = $the_object->clean_client_single_widget( $the_item, $widget_name, [], "bofClient" ) ) ){
              $widget = $get_widget;
              $widget_url = "bof_{$object_name}?slug={$slug}&widget={$widget_name}&";
            }
          }
        }

      }
    }
    else {
      $widget = bof()->object->page_widget->select(["unique_id"=>$widget_hash],["client_single"=>true,"match_page"=>true]);
      $widget_url = $widget["ID"] . "?";
    }

    if ( empty( $widget ) )
    return false;

    return array(
      "widget" => $widget,
      "widget_url" => $widget_url
    );

  }
  public function _ol( $widget_hash ){

    if ( bof()->object->core_setting->get( "client_private" ) && !bof()->user->get()->logged ){
      bof()->api->set_error( "403", [ "output_args" => [ "turn" => false ] ] );
      return;
    }

    $parse_request = bof()->bofClient->_ol_parse_request( $widget_hash );

    if ( $parse_request ){
      $widget = $parse_request["widget"];
      $widget_url = $parse_request["widget_url"];
    }

    if ( !empty( $widget ) ? !empty( $widget["display"]["pagination"] ) : false ){

      $requested_page = 1;

      if ( empty( $widget["display_ol"] ) ){
        $widget["display"]["type"] = "slider";
        $widget["display"]["slider_mason"] = true;
        $widget["display"]["slider_size"] = "medium";
        $widget["display"]["slider_rows"] = 1;
        $widget["display"]["link_on_bottom"] = false;
      } else {
        $widget["display"] = $widget["display_ol"];
      }

      $widget["object"]["selectArray"]["limit"] = !empty( $widget["object"]["limit"] ) ? $widget["object"]["limit"] : 50;
      if ( ( $requested_page = bof()->nest->user_input( "get", "page", "int", [ "min" => 2, "max" => 100 ], 1 ) ) )
      $widget["object"]["selectArray"]["page"] = $requested_page;

      $widget_object = bof()->object->__get( $widget["object"]["name"] );

      $has_pre = $requested_page > 1 ? $requested_page - 1 : false;
      $has_nxt = $widget_object->select(
        $widget["object"]["whereArray"],
        array(
          "clean" => false,
          "limit" => 1,
          "offset" => ( $requested_page ) * $widget["object"]["selectArray"]["limit"],
          "columns" => "ID",
          "empty_select" => true
        )
      ) ? $requested_page + 1 : false;

      if ( ( $_widget_parsed = bof()->bofClient->__parse_widget( $widget["object"]["name"], $widget_object, $widget ) ) ){

        $widgets[] = $_widget_parsed;

        bof()->api->set_message( "ok", array(
          "data" => [],
          "widgets" => $widgets,
          "pages" => array(
            "prev" => $has_pre ? "list/{$widget_url}page={$has_pre}" : false,
            "next" => $has_nxt ? "list/{$widget_url}page={$has_nxt}" : false
          ),
          "seo" => bof()->seo->fetch( array(
            "item" => $widget,
            "object" => "page_widget",
          ) )
        ) );

        return true;

      }

    }

  }

  public function _ob( $object_name ){

    if ( bof()->object->core_setting->get( "client_private" ) && !bof()->user->get()->logged ){
      bof()->api->set_error( "403", [ "output_args" => [ "turn" => false ] ] );
      return;
    }

    $object_hash = bof()->nest->user_input( "post", "hash", "md5" );
    $buttons = bof()->nest->user_input( "post", "buttons", "string", [ "regex" => "[a-zA-Z_\-\,]" ] );

    if ( !in_array( $object_name, array_keys( $this->_get_objects() ) ) || !$object_hash || !$buttons )
    return false;

    $buttons = explode( ",", $buttons );

    $bof_args = $this->_get_objects()[ $object_name ];
    $the_object = bof()->object->__get( $object_name );
    $bofClientButtons = $the_object->bof_client()["buttons"];
    $item = $the_object->select(
      array(
        "hash" => $object_hash
      ),
      array(
        "as_widget" => true,
        "as_button" => true
      )
    );

    if ( !$item )
    return false;

    $parsed_buttons = bof()->bofClient->__parse_item_buttons( $object_name, $the_object, $item["raw"], $bofClientButtons, true );
    $parsed_dyna_buttons = [];
    foreach( $parsed_buttons["items"] as $pbk => $parsed_button ){
      if ( !in_array( $pbk, $buttons, true ) ){
        continue;
      }
      $parsed_dyna_buttons["items"][ $pbk ] = $parsed_button;
    }

    if ( !$parsed_dyna_buttons )
    return false;

    if ( in_array( "remove_from_playlist", $buttons, true ) ){

      $page_ot = bof()->nest->user_input( "http_header", "x_bof_page_ot", "string" );
      $page_hash = bof()->nest->user_input( "http_header", "x_bof_page_hash", "md5" );
      $page_name = bof()->nest->user_input( "http_header", "x_bof_page_name", "string" );
      
      if ( ( $page_ot === "ugc_playlist" && $page_hash ) || ( !$page_hash && $page_name === "object_list" ) ){
        $parsed_dyna_buttons["items"]["remove_from_playlist"] = array(
          "icon" => "skull",
          "hook" => "remove_from_playlist",
          "action" => "playlist_shorten",
          "attr"=> " data-play='{$page_hash}' data-item='{$item["raw"]["hash"]}' data-item_ot='{$object_name}' "
        );
      }

    }

    foreach( $parsed_dyna_buttons["items"] as $key => &$data ){

      if ( empty( $data["title"] ) && !empty( $data["hook"] ) )
      $data["title"] = bof()->object->language->turn( $data["hook"], [], [ "lang" => "users", "uc_first" => true ] );

    }

    bof()->api->set_message( "ok", array(
      "buttons" => $parsed_dyna_buttons
    ) );

    return $item;

  }

  public function _browse( $object_name ){

    $validate_object_name = bof()->nest->validate( $object_name, "bofClient_object" );
    if ( !$validate_object_name )
    return;

    $the_object = bof()->object->__get( $object_name );
    if ( empty( $the_object->bof()["browsable"] ) )
    return;

    if ( !bof()->object->db_setting->get( "br_{$object_name}" ) )
    return;

    $get_filters = bof()->bofClient->_browse_get_filters( $object_name, $the_object );
    $get_content = bof()->bofClient->_browse_get_content( $object_name, $the_object, $get_filters );
    $get_pagination = bof()->bofClient->_browse_get_pagination( $object_name, $the_object, $get_filters, $get_content );
    $get_widget  = bof()->bofClient->_browse_get_widget( $object_name, $the_object, $get_content );

    bof()->api->set_message( "ok", array(
      "widgets" => array( $get_widget ),
      "title" => bof()->object->language->turn( $object_name, [], [ "uc_first" => true, "lang" => "users" ] ),
      "filters" => $get_filters,
      "count" => $get_content["count"],
      "pagination" => $get_pagination,
      "seo" => array(
        "title" => bof()->object->language->turn( $object_name, [], [ "uc_first" => true, "lang" => "users" ] ),
      )
    ) );

  }
  public function _browse_get_filters( $object_name, $the_object, $raw=false ){

    $parse_with_bofAdmin = bof()->bofAdmin->object_list_parse_caller( $the_object, false );
    $parse_object = bof()->object->parse_caller( $the_object );
    $bofAdmin_filters = $parse_with_bofAdmin["filters"];

    // Get columns & Relations filters
    $filters_raw = [];
    foreach( $parse_object->parsed->columns as $name => $args ){

      $bofClient = null;
      extract( $args );

      if ( $bofClient ? !empty( $bofClient["filters"] ) : false )
      $filters_raw = array_merge( $filters_raw, $bofClient["filters"] );

    }

    if ( !empty( $parse_object->parsed->relations ) ){
      foreach( $parse_object->parsed->relations as $name => $args ){

        $bofClient = null;
        extract( $args );

        if ( $bofClient ? !empty( $bofClient["filters"] ) : false )
        $filters_raw = array_merge( $filters_raw, $bofClient["filters"] );

      }
    }

    // Parse filters
    if ( !$filters_raw )
    return false;

    $filters = [];
    $object_filters_setting = bof()->object->db_setting->get( "br_{$object_name}_setting" );

    foreach( $parse_with_bofAdmin["sorters_bofFormat"] as $_i => $_v ){
      if ( in_array( $_v[0], [ "ID", "muse_report", "time_add", "s_views_unique", "s_plays_unique", "s_popularity", "price", "explicit" ], true ) )
      unset( $parse_with_bofAdmin["sorters_bofFormat"][ $_i ] );
    }

    $filters_raw = array_merge(
      array(
       "sort_by" => array(
         "input" => array(
           "type" => "select",
           "name" => "sort_by",
           "options" => $parse_with_bofAdmin["sorters_bofFormat"],
           "value" => array_keys( $parse_with_bofAdmin["sorters"] )[0]
         ),
         "validator" => array(
           "in_array",
           array(
             "values" => array_keys( $parse_with_bofAdmin["sorters"] )
           )
         )
       )
     ),
     $filters_raw
    );

    foreach( $filters_raw as $filter_name => $filter_args ){

      if ( !$raw && !empty( $object_filters_setting ) && $filter_name !== "sort_by" ? empty( $object_filters_setting[ "br_{$object_name}_{$filter_name}" ] ) : false )
      continue;

      if ( $filter_args === "_bofAdmin" ){
        $filters[ $filter_name ] = $bofAdmin_filters[ $filter_name ];
      }
      else {
        $filters[ $filter_name ] = $filter_args;
      }

      $filters[ $filter_name ]["input"] = !empty( $filters[ $filter_name ]["input"] ) ? $filters[ $filter_name ]["input"] : [];
      $filters[ $filter_name ]["input"]["name"] = $filter_name;

      $filter_given_value = bof()->bofInput->__get_value( $filter_name, $filters[ $filter_name ] );
      $filters[ $filter_name ]["value"] = $filters[ $filter_name ]["input"]["value"] = $filter_given_value[1];

      if ( !empty( $filters[ $filter_name ]["bofInput"] ) )
      $filters[ $filter_name ] = bof()->bofInput->parse( $filters[ $filter_name ] )["data"];

      if ( !$raw ){
        $filters[ $filter_name ]["title"] = bof()->object->language->turn($filter_name,[],["uc_first"=>true,"lang"=>"users"]);
        if ( $filter_name == "has_price" ){
          $filters[ $filter_name ]["input"]["options"][0][1] = bof()->object->language->turn("all",[],["uc_first"=>true,"lang"=>"users"]);
          $filters[ $filter_name ]["input"]["options"][1][1] = bof()->object->language->turn("free",[],["uc_first"=>true,"lang"=>"users"]);
          $filters[ $filter_name ]["input"]["options"][2][1] = bof()->object->language->turn("priced",[],["uc_first"=>true,"lang"=>"users"]);
        }
        elseif ( $filter_name == "col_lang" ){
          $filters[ $filter_name ]["input"]["options"]['__all__'][1] = bof()->object->language->turn("all",[],["uc_first"=>true,"lang"=>"users"]);
        }
        elseif ( ( $filters[ $filter_name ]["input"]["type"] == "select_i" || $filters[ $filter_name ]["input"]["type"] == "select" ) ){
          foreach( $filters[ $filter_name ]["input"]["options"] as &$_o ){
            $_o[1] = bof()->object->language->turn($_o[0],[],["uc_first"=>true,"lang"=>"users"]);
          }
        }
      }

    }

    if ( !$raw ){
      if ( !empty( $object_filters_setting["br_{$object_name}_sorters"] ) )
      $filters["sort_by"]["validator"][1]["values"] = explode( ";", $object_filters_setting["br_{$object_name}_sorters"] );
      foreach( $filters["sort_by"]["input"]["options"] as $_i => &$_o ){
        if ( !in_array( $_o[0], $filters["sort_by"]["validator"][1]["values"], true ) )
        unset( $filters["sort_by"]["input"]["options"][$_i] );
        $_o[1] = bof()->object->language->turn($_o[0],[],["uc_first"=>true,"lang"=>"users"]);
      }
    }

    return $filters;

  }
  public function _browse_get_content( $object_name, $the_object, $get_filters ){

    $page = bof()->nest->user_input( "get", "page", "int", [ "min" => 2, "max" => 100 ], 1 );

    $query = [];
    if ( $get_filters ){
      foreach( $get_filters as $_f_name => $_f_args ){
        if ( $_f_name == "sort_by" ) continue;
        if ( !empty( $_f_args["input"]["value"] ) ){
          if ( !empty( $_f_args["bofInput"] ) ? $_f_args["bofInput"][0] == "object" : false )
          $_f_args["input"]["value"] = explode( ",", $_f_args["input"]["value"] );
          $query[ $_f_name ] = $_f_args["input"]["value"];
        }
      }
    }

    $items = $the_object->select(
      $query,
      array(
        "empty_select" => true,
        "limit" => 50,
        "offset" => ( $page - 1 ) * 50,
        "as_widget" => true,
        "order" => "DESC",
        "order_by" => !empty( $get_filters["sort_by"]["value"] ) ? $get_filters["sort_by"]["value"] : null,
        "_eq" => array(
          "cover" => [],
          "avatar" => []
        )
      )
    );

    $count = $the_object->count(
      $query,
      array(
        "cache" => false
      )
    );

    return array(
      "items" => $items,
      "count" => $count ? bof()->general->number_format_hr( $count, [ "cache" => false ] ) : "",
      "count_cr" => $count,
      "page" => $page
    );

  }
  public function _browse_get_pagination( $object_name, $the_object, $get_filters, $get_content ){

    $query = [];
    if ( $get_filters ){
      foreach( $get_filters as $_f_name => $_f_args ){
        if ( !empty( $_f_args["input"]["value"] ) ){
          $query[ $_f_name ] = $_f_args["input"]["value"];
        }
      }
    }

    $max_pages = $get_content["count_cr"] ? ceil( $get_content["count_cr"] / 50 ) : 0;
    if ( $get_content["page"] < $max_pages ){
      $next = "browse/{$object_name}?" . ( $query ? http_build_query( $query ) . "&page=" . ( $get_content["page"] + 1 ) : "page=" . ( $get_content["page"] + 1 ) );
    }

    return array(
      "cur" => $get_content["page"],
      "next" => !empty( $next ) ? $next : false,
      "prev" => $get_content["page"] > 1 ? ( "browse/{$object_name}?" . ( $query ? http_build_query( $query ) . "&page=" . ( $get_content["page"] - 1 ) : "page=" . ( $get_content["page"] - 1 ) ) ) : false,
      "total" => $max_pages
    );

  }
  public function _browse_get_widget( $object_name, $the_object, $get_content ){

    $array_raw = array(
      "ID" => "items",
      "display" => array(
        "type" => "slider",
        "title" => false,
        "link" => false,
        "pagination" => false,
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => true,
        "classes" => [ "no_title" ]
      ),
      "object" => array(
        "name" => $object_name,
      ),
      "items" => $get_content["items"]
    );

    if ( empty( $get_content["items"] ) )
    $array_raw = array(
      "ID" => "items",
      "display" => array(
        "type" => "html",
        "title" => "",
        "html" => "<div class='nada'>
          <span class='_ti'>Nada</span>
          <span class='mdi mdi-emoticon-sad-outline'></span>
          <span class='_sti'>Try with less filters?</span>
        </div>"
      ),
    );

    $array_parsed = bof()->bofClient->__parse_widget( $object_name, $the_object, $array_raw );
    return $array_parsed;

  }

}

?>
