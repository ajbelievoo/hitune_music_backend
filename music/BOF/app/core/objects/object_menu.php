<?php

if ( !defined( "bof_root" ) ) die;

class object_menu extends bof_type_object {

  private $default_app_pages = array(
    "user_auth?do=login" => "Authorization - Login",
    "user_auth?do=signup" => "Authorization - Signup",
    "user_auth?do=recover" => "Authorization - Recover",
    "upload" => "User Upload",
    "user_area" => "User Profile",
    "user_pay" => "User Pay",
    "user_withdrawal" => "User Withdrawal Page",
    "user_library?tab=playlist" => "User Library - Playlists",
    "user_library?tab=likes" => "User Library - Likes",
    "user_library?tab=subscriptions" => "User Library - Subscriptions",
    "user_library?tab=purchased" => "User Library - Purchased",
    "user_library?tab=history" => "User Library - History",
    "user_library?tab=uploads" => "User Library - Uploads",
    "user_edit?tab=profile" => "User Setting - Profile",
    "user_edit?tab=security" => "User Setting - Security",
    "user_edit?tab=transactions" => "User Setting - Transactions",
    "user_edit?tab=links" => "User Setting - Social Links",
    "user_edit?tab=notifications" => "User Setting - Notifications",
    "subscription_plans" => "Subscription Plans",
    "developer" => "Developer API Portal",
    "developer-dashboard" => "Developer API Dashboard",
  );
  public function bof(){
    return array(
      "name" => "menu",
      "label" => "menu",
      "icon" => "menu",
      "db_table_name" => "_d_menus"
    );
  }
  public function columns(){
    return array(
      "name" => array(
        "label" => "menu<br>Name",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["sub_data"] = $item["comment"];
              return $displayData;
            },
          ),
          "object" => array(
            "seo_slug_source" => true,
            "required" => true
          )
        )
      ),
      "comment" => array(
        "label" => "Comment",
        "tip" => "A few words to remember this menu by",
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true,
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "textarea"
        ),
        "bofAdmin" => array(
          "object" => array()
        )
      ),
      "targets" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()",
          )
        )
      ),
      "structure" => array(
        "label" => "Structure",
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        ),
      )
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add",
    );
  }
  public function selectors(){
    return array(
      "query" => [ "name", "LIKE%lower" ],
      "_def" => [ "_def", "=" ]
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
        "edit_page_url" => "menu",
        "list_page_url" => "menus",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "buttons_renderer" => function( $item, &$buttons ){

        if ( $item["_def"] )
        unset( $buttons["delete"] );

      },
      "object_renderer" => function(  $object, $parsed, $args, &$request ){

        $non_default_langs = bof()->object->language->get_all();
        if ( $non_default_langs ){
          foreach( $non_default_langs as $non_default_lang ){
            $request["langs"][ $non_default_lang["code2"] ] = $non_default_lang["name"];
          }
        }

        $objects = bof()->bofAdmin->_get_objects();
        foreach( $objects as $object_name => $object ){
          if ( empty( $object["seo"] ) ) continue;
          $object_itself = bof()->object->__get( $object_name );
          $objects_seo[ $object_name ] = array(
            "label" => $object_itself->bof()["label"],
            "icon" => $object_itself->bof()["icon"],
            "selector" => bof()->bofInput->parse( array(
              "title" => $object_itself->bof()["label"],
              "bofInput" => array(
                "object",
                array(
                  "type" => $object_name
                )
              ),
              "input" => array(
                "name" => "object_{$object_name}"
              )
            ) )["data"]
          );
        }

        $request["objects_with_seo"] = $objects_seo;
        $default_app_pages = bof()->object->menu->get_app_pages();
        foreach( $default_app_pages as $default_app_pageURL => $default_app_pageLabel ){
          $default_app_pages[ $default_app_pageURL ] = [ $default_app_pageURL, $default_app_pageLabel ];
        }
        $request["default_app_pages"] = array_values( $default_app_pages );

      }
    );
  }

  public function get( $ID ){
    return $this->_bof_this->select(
      array(
        "ID" => $ID
      ),
      array(
        "for_display" => true
      )
    );
  }
  public function select( $whereArgs=[], $selectArgs=[] ){

    $deleting = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $deleting )
    $whereArgs[] = [ "_def", "!=", "1" ];

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args ){

    $for_display = false;
    $search = false;
    $_eq = [];
    extract( $args );

    if ( !empty( $item["structure_decoded"] ) )
    $item["structure_decoded"] = array_values( $item["structure_decoded"] );

    if ( $for_display ){

      $item = $item["structure_decoded"];

      if ( $item && bof()->getName() == "bof_client" ){

        $client_language = bof()->user->check()->language;
        $o_item = $item;
        $item = [];
        foreach( $o_item as $_m ){

          $validates = $this->check_rules( $_m );

          if ( !$validates ) continue;

          if ( !empty( $_m["title_{$client_language}"] ) )
          $_m["title"] = $_m["title_{$client_language}"];

          if ( empty( $_m["title"] ) )
          continue;

          $o_childs = !empty( $_m["childs"] ) ? $_m["childs"] : false;
          $_m["childs"] = [];
          if ( $o_childs ){
            foreach( $o_childs as $_s ){

              if ( !empty( $_s["title_{$client_language}"] ) )
              $_s["title"] = $_s["title_{$client_language}"];

              if ( empty( $_s["title"] ) )
              continue;

              $_m["childs"][] = $_s;

            }
          }

          $item[] = $_m;

        }

      }

    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"],
        "image" => false
      );
    }

    return $item;

  }

  protected function check_rules( &$item, $checkChild=true ){

    if ( !empty( $item["childs"] ) && $checkChild ){
      foreach( $item["childs"] as $i => $itemChild ){
        if ( !$this->check_rules( $itemChild, false ) )
        unset( $item["childs"][ $i ] );
      }
    }

    if ( empty( $item["user_roles_exclude"] ) && empty( $item["user_roles_only"] ) )
    return true;

    if ( !empty( bof()->user->get()->extra ) )
    $_user_roles = bof()->user->get()->extra["role_ids"];

    if ( !empty( $item["user_roles_only"] ) && !empty( $_user_roles ) ){
      if ( !empty( array_intersect( $_user_roles, explode( ",", $item["user_roles_only"] ) ) ) )
      return true;
      return false;
    }

    if ( !empty( $item["user_roles_exclude"] ) && !empty( $_user_roles ) ){
      if ( empty( array_intersect( $_user_roles, explode( ",", $item["user_roles_exclude"] ) ) ) )
      return true;
      return false;
    }

    return false;

  }

  public function verify_structure( $data ){

    if ( !$data ? true : !is_array( $data ) )
    throw new bofException( "Invalid input" );

    foreach( $data as $i => &$item ){

      if ( !is_array( $item ) )
      continue;

      if ( empty( $item["title"] ) || !in_array( "icon", array_keys( $item ), true ) || !in_array( "href", array_keys( $item ), true ) )
      throw new bofException( "Invalid input: item {$i} _rHn" );

      if ( !empty( $item["user_roles_only"] ) ? !bof()->nest->validate( $item["user_roles_only"], "int_imploded" ) : false )
      throw new bofException( "Invalid input: item {$i} _rOn" );

      if ( !empty( $item["user_roles_exclude"] ) ? !bof()->nest->validate( $item["user_roles_exclude"], "int_imploded" ) : false )
      throw new bofException( "Invalid input: item {$i} _rEx" );

      $validated_data[$i] = $item;

    }

    if ( empty( $validated_data ) )
    throw new bofException( "Invalid input: no valid items" );

    return $validated_data;

  }
  public function get_app_pages(){

    $pages = $this->default_app_pages;

    $browsable_objects = [];
    foreach( bof()->bofAdmin->_get_objects() as $object_name => $object_args ){

      $object = bof()->object->__get( $object_name );
      if ( !$object->method_exists("bof") )
      continue;

      if ( empty( $object->bof()["browsable"] ) )
      continue;

      if ( empty( bof()->object->db_setting->get( "br_{$object_name}" ) ) )
      continue;

      $pages["browse/{$object_name}"] = "Browse - {$object->bof()["label"]}";

    }

    return $pages;
  }

}

?>
