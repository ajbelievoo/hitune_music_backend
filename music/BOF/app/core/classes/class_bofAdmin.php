<?php

if ( !defined( "bof_root" ) ) die;

// BusyOwlFramework Admin-Area Helper
class bofAdmin {

  protected $caller = null;
  protected $cache = array(
    "object_list_parse_caller" => []
  );
  public $objects = array(
    "notification" => array(
      "search" => false,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => false
    ),
    "ads" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "currency" => array(
      "search" => false,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "payment" => array(
      "search" => false,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => false
    ),
    "transaction" => array(
      "search" => false,
      "list" => true,
      "edit" => false,
      "new" => false,
      "delete" => false
    ),
    "language" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "storage" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "file" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => false
    ),
    "cronjob" => array(
      "search" => false,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => false
    ),
    "error_log" => array(
      "search" => false,
      "list" => true,
      "edit" => false,
      "new" => false,
      "delete" => false
    ),
    "blacklist" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => true,
    ),
    "page" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
      "seo" => true
    ),
    "menu" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
    ),
    "user" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
      "social_links" => true,
    ),
    "user_role" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "user_subs_plan" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "user_subs" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true
    ),
    "user_request" => array(
      "search" => false,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => true
    ),
    "user_withdraw" => array(
      "search" => false,
      "list" => true,
      "edit" => true,
      "new" => false,
      "delete" => true
    ),
    "ugc_playlist" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
      "seo" => true
    ),
    "ugc_property" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
    ),
    "b_post" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
      "seo" => true
    ),
    "b_category" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
      "seo" => true
    ),
    "b_tag" => array(
      "search" => true,
      "list" => true,
      "edit" => true,
      "new" => true,
      "delete" => true,
      "seo" => true
    ),
  );
  public $setting = array();
  public $stats = array(
    "dashboard" => array(
      "title" => "dashboard",
      "icon" => "stacked_bar_chart"
    ),
    "visits" => array(
      "title" => "visits",
      "icon" => "tour"
    ),
    "system" => array(
      "title" => "system",
      "icon" => "settings_suggest"
    ),
    "users" => array(
      "title" => "users",
      "icon" => "person"
    ),
    "financial" => array(
      "title" => "financial",
      "icon" => "payments"
    ),
  );

  public function __construct(){}

  public function _get_objects( $args=[] ){

    $get_columns = null;
    $get_filters = null;
    $parse = null;
    extract( $args );

    $objects = $this->objects;

    return $objects;

  }
  public function _add_object( $name, $args=[] ){

    $search = true;
    $list = true;
    $edit = true;
    $new = true;
    $delete = true;
    $seo = true;
    $social_links = false;
    $biography = false;
    extract( $args );

    $this->objects[ $name ] = array(
      "search" => $search,
      "list" => $list,
      "edit" => $edit,
      "new" => $new,
      "delete" => $delete,
      "seo" => $seo,
      "social_links" => $social_links,
      "biography" => $biography,
    );

    return true;

  }
  public function _get_setting(){
    return $this->setting;
  }
  public function _add_setting( $name, $data ){
    $this->setting[ $name ] = $data;
  }
  public function _set_stats( $array ){
    $this->stats = $array;
  }
  public function _get_stats(){
    return $this->stats;
  }

  public function __check_access( $object_name, $access_type ){

    $object = bof()->object->__get( $object_name );
    $object_args = bof()->bofAdmin->_get_objects()[ $object_name ];
    // $object_structure = $object->get_admin_structure( "object", true );

    if ( empty( $object_args[ $access_type ] ) )
    return false;


    $user_extra_data = bof()->user->get()->extra;
    if ( $user_extra_data ? ( $user_extra_data["role"] == "moderator" ? $user_extra_data["moderator_roles"]["type"] == "some" : false ) : false ){

      $_roles = $user_extra_data["moderator_roles"]["objects"];

      if ( !in_array( $object_name, array_keys( $_roles ), true ) )
      return false;

      $_role = $_roles[ $object_name ];

      if ( empty( $_role["crud"][ $access_type ] ) )
      return false;

      $this->objects[ $object_name ][ "limits_filters" ] = $_role["filters"];

    }

    return true;

  }

  public function object_list( $object_name ){

    if ( !in_array( $object_name, array_keys( $this->objects ) ) ? true : !bof()->bofAdmin->__check_access( $object_name, "list" ) )
    return false;

    $this->caller = $object_name;
    $object = bof()->object->__get( $object_name );
    $parsed = $this->object_list_parse_caller( $object );

    $relations = bof()->object->parse_caller( $object )->parsed->relations;

    $filters = $this->object_list_parse_filters( $object, $parsed );
    $request = $this->object_list_parse_request( $object, $parsed, $filters );

    $query = $request["query"];
    $filters = $request["filters"];

    $content = $this->object_list_fetch_content( $object, $parsed, $filters, $query );
    $buttons = $this->object_list_parse_buttons( $object, $parsed, $parsed["buttons"] );

    $_items = $content["items"] ? array_values( $content["items"] ) : [];

    if ( $_items ){
      foreach( $_items as &$_item )
      unset( $_item["raw"] );
    }

    $_result = array(

      "config"   => $parsed["config"],
      "object_endpoint" => "bofAdmin/object/". $object->bof()["name"] ."/",
      "filters"  => $filters,
      "template" => $parsed["items"],
      "items"    => $_items,
      "buttons"  => $buttons,

      "records" => array(

        "total"    => $content["total_records"],
        "total_hr" => number_format( $content["total_records"] ),

        "pages" => array(
          "cur"       => $content["total_records"] ? $request["query"]["select"]["page"] : false,
          "max"       => $content["max_pages"] > 1 ? $content["max_pages"] : false,
          "next"      => $content["has_next"] && $content["max_pages"] - $request["query"]["select"]["page"] > 1 ? $request["query"]["select"]["page"] + 1 : false,
          "pre"       => $content["has_pre"] ? $request["query"]["select"]["page"] - 1 : false,
          "has_next"  => $content["has_next"],
          "has_pre"   => $content["has_pre"],
          "has_first" => $content["has_first"],
        ),

        // "query" => $query

      ),

    );

    if ( $object->method_exists("admin_list") )
    $_result = $object->admin_list( $_result, $request );

    bof()->api->set_message( "ok", $_result );

  }
  public function object_list_parse_caller( $object, $check_access=true ){

    $object_name = $object->get_class_name();
    if ( !empty( $this->cache["object_list_parse_caller"][$object_name] ) )
    return $this->cache["object_list_parse_caller"][$object_name];

    $bofAdmin = $object->bof_admin();

    $edit_access = $new_access = $delete_access = false;

    if ( $check_access ){
      $edit_access = bof()->bofAdmin->__check_access( $object->bof()["name"], "edit" ) ;
      $new_access = bof()->bofAdmin->__check_access( $object->bof()["name"], "new" ) ;
      $delete_access = bof()->bofAdmin->__check_access( $object->bof()["name"], "delete" ) ;
    }

    $sorters = [];
    $list_items = !empty( $bofAdmin["list"] ) ? $bofAdmin["list"] : [];
    $filters =  !empty( $bofAdmin["filters"] ) ? $bofAdmin["filters"] : [];
    $buttons = [];

    $parse_caller_object = bof()->object->parse_caller( $object );
    $columns = $parse_caller_object->parsed->columns;

    foreach( $columns as $column_name => $column_args ){

      if ( empty( $column_args["bofAdmin"] ) ) continue;

      if ( !empty( $column_args["bofAdmin"]["sortable"] ) ){
        $sorters[ $column_name ] = !empty( $column_args["label"] ) ? $column_args["label"] : $column_name;
        $sorters[ $column_name . "_ASC" ] = !empty( $column_args["label"] ) ? $column_args["label"] . " - ASC" : $column_name . " - ASC";
      }

      if ( in_array( "list", array_keys( $column_args["bofAdmin"] ), true ) ){
        $list_items[ $column_name ] = array_merge(
          array(
            "label" => !empty( $column_args["label"] ) ? $column_args["label"] : "-",
          ),
          $column_args["bofAdmin"]["list"]
        );
      }

      if ( !empty( $column_args["bofAdmin"]["filters"] ) )
      $filters = array_merge( $filters, $column_args["bofAdmin"]["filters"] );

    }

    if ( !empty( $parse_caller_object->parsed->relations ) ){
      foreach( $parse_caller_object->parsed->relations as $relation_k => $relation ){

        if ( !empty( $relation["bofAdmin"]["filters"] ) )
        $filters = array_merge( $filters, $relation["bofAdmin"]["filters"] );

        if ( !empty( $relation["bofAdmin"]["lists"] ) )
        $list_items = array_merge( $list_items, $relation["bofAdmin"]["lists"] );

      }
    }

    if ( $filters ){
      foreach( $filters as $filter_name => $filter_args ){
        if ( !empty( $filter_args["bofInput"] ) ){
          $filter_args["input"]["name"] = $filter_name;
          $bofInput_parsed = bof()->bofInput->parse( $filter_args );
          $filters[ $filter_name ] = $bofInput_parsed["data"];
        }
      }
    }

    if ( !$edit_access )
    $bofAdmin["config"]["edit"] = $bofAdmin["config"]["multi"]["edit"] = false;

    if ( !$new_access )
    $bofAdmin["config"]["create"] = false;

    if ( !$delete_access )
    $bofAdmin["config"]["delete"] = $bofAdmin["config"]["multi"]["delete"] = false;

    $sorters_bofFormat = [];
    if ( $sorters ){
      foreach( $sorters as $__n => $__v )
      $sorters_bofFormat[] = [ $__n, $__v ];
    }

    $data = array(
      "items" => $list_items,
      "filters" => $filters,
      "sorters" => $sorters,
      "sorters_bofFormat" => $sorters_bofFormat,
      "buttons" => !empty( $bofAdmin["buttons"] ) ? $bofAdmin["buttons"] : [],
      "config" => $bofAdmin["config"],
    );

    if ( isset( $bofAdmin["buttons_renderer"] ) )
    $data["buttons_renderer"] = $bofAdmin["buttons_renderer"];

    if ( isset( $bofAdmin["buttons_renderers"] ) )
    $data["buttons_renderers"] = $bofAdmin["buttons_renderers"];

    $this->cache["object_list_parse_caller"][$object_name] = $data;
    return $data;

  }
  protected function object_list_parse_filters( $object, $parsed_object ){

    $filters = $this->object_list_default_filters( $object, $parsed_object );
    if ( !empty( $parsed_object["filters"] ) ) $filters = array_merge( $parsed_object["filters"], $filters );

    foreach( $filters as $filter_name => &$filter_args ){
      $filter_args["input"]["name"] = $filter_name;
    }

    return $filters;

  }
  protected function object_list_default_filters( $object, $parsed_object ){

    $_default_filters = array(
      "limit" => array(
        "title" => "Limit",
        "input" => array(
          "name" => "limit",
          "type" => "select_i",
          "options" => array(
            1  => [ 1, 1 ],
            10 => [ 10, 10 ],
            20 =>[ 20, 20 ],
            50 => [ 50, 50 ],
            100 => [ 100, 100 ]
          ),
          "value" => !empty( $object->bof_admin()["list_config"]["limit"] ) ? $object->bof_admin()["list_config"]["limit"] : 10
        ),
        "validator" => array(
          "int",
          [ "min" => 1, "max" => 999 ]
        )
      ),
      "query" => array(
        "title" => "Search",
        "input" => array(
          "name" => "query",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          [ "empty()" ]
        )
      ),
      "page" => array(
        "validator" => array(
          "int",
          [ "empty()" ]
        )
      ),
    );

    $__sorters = [];
    if ( !empty( $parsed_object["sorters"] ) ){

      foreach( $parsed_object["sorters"] as $_k => $_v ){
        $__sorters[ "{$_k}" ] = [ "{$_k}", "{$_v}", $_k, "ASC" ];
        //$__sorters[ "{$_k}_DESC" ] = [ "{$_k}_DESC", "{$_v} - DESC", $_k, "DESC" ];
      }

      $order_by = !empty( $object->bof_admin()["list_config"]["order_by"] ) ? $object->bof_admin()["list_config"]["order_by"] : "ID";
      $order = !empty( $object->bof_admin()["list_config"]["order"] ) ? $object->bof_admin()["list_config"]["order"] : "DESC";

      $_default_filters["sort_by"] = array(
        "title" => "Sort By",
        "input" => array(
          "name" => "sort_by",
          "type" => "select",
          "options" => $__sorters,
          "value" => "{$order_by}_{$order}"
        ),
        "validator" => array(
          "in_array",
          [ "values" => array_keys( $__sorters ) ]
        )
      );

    }

    return $_default_filters;

  }
  protected function object_list_parse_request( $object, $parsed_object, $filters ){

    $bofAdmin = $object->bof_admin();
    $default_order_by = !empty( $bofAdmin["list_config"]["order_by"] ) ? $bofAdmin["list_config"]["order_by"] : "ID";
    $default_order = !empty( $bofAdmin["list_config"]["order"] ) ? $bofAdmin["list_config"]["order"] : "DESC";
    $default_limit = !empty( $bofAdmin["list_config"]["limit"] ) ? $bofAdmin["list_config"]["limit"] : 10;

    $Query = array(
      "where" => [],
      "select" => [
        "limit" => $default_limit,
        "order_by" => $default_order_by,
        "order" => $default_order,
        "page" => 1
      ]
    );

    foreach( $filters as $filter_name => &$filter_args ){

      list( $filter_exists, $filter_value ) = bof()->bofInput->__get_value( $filter_name, $filter_args );

      if ( isset( $filter_value ) ) {
        if ( !empty( $filter_args["bofInput"] ) ? $filter_args["bofInput"][0] == "object" : false ){

          if ( !$filter_value ) continue;

          $filter_args["bofInput"][1]["args"]["load"]["IDS"] = $filter_value;
          $bofInput_parsed = bof()->bofInput->parse( $filter_args );
          $filter_args = $bofInput_parsed["data"];

          if ( $bofInput_parsed["items"] )
          $Query["where"][ $filter_name ] = $bofInput_parsed["ids"];

        }
        else {

          if ( !$filter_exists ) continue;
          if ( $filter_value == "__all__" ) continue;

          if ( $filter_name == "limit" )
          $Query["select"]["limit"] = $filter_value;

          elseif ( $filter_name == "page" )
          $Query["select"]["page"] = $filter_value;

          elseif ( $filter_name == "sort_by" ){

            $order = substr( $filter_value, -4 ) == "_ASC" ? "ASC" : "DESC";
            $order_by = str_replace( ["_ASC", "_DESC" ], "", $filter_value );
            $Query["select"]["order_by"] = $order_by;
            $Query["select"]["order"] = $order;

          }

          elseif ( $filter_name == "query" ? !empty( $filter_value ) : true )
          $Query["where"][ $filter_name ] = $filter_value;

          $filter_args["input"]["value"] = $filter_value;

        }
      }

      unset( $filter_args["validator"] );

    }

    unset( $filters["page"] );

    return array(
      "query" => $Query,
      "filters" => $filters
    );

  }
  protected function object_list_fetch_content( $object, $parsed_object, $filters, $query ){

    $_where = $query["where"] ? $query["where"] : [];

    if ( !empty( $this->objects[ $object->bof()["name"] ][ "limits_filters" ] ) ? !in_array( "all", $this->objects[ $object->bof()["name"] ][ "limits_filters" ], true ) : false ){

      foreach( $this->objects[ $object->bof()["name"] ][ "limits_filters" ] as $_limit_filters ){
        $_limit_filters_parsed = bof()->object->parse_caller_where( bof()->object->parse_caller( $object ), $_limit_filters );
        $_limits_wheres[] = $_limit_filters_parsed[0];
      }

      $_where[] = array(
        "oper" => "OR",
        "cond" => $_limits_wheres
      );

    }

    $_fs = !empty( $_where["query"] );
    // Get items
    $items_raw = $object->select(
      $_where,
      array_merge(
        $query["select"],
        array(
          "single" => false,
          "empty_select" => true,
          "listing" => true,
          "full_search" => $_fs
        )
      )
    );

    // Construct display strucure for each item
    $items = [];
    if ( $items_raw ){

      foreach( $items_raw as &$__item ){

        $items[ $__item["ID"] ] = array(
          "ID" => $__item["ID"],
          "raw" => $__item,
          "display" => array(),
          "buttons" => array()
        );

        foreach( $parsed_object["items"] as $__ds_item_name => $__ds_item ){

          $displayData = array(
            "data" => !empty( $__item[ $__ds_item_name ] ) ? $__item[ $__ds_item_name ] : null,
            "id" => $__ds_item_name,
            "head_type" => !empty( $__ds_item["type"] ) ? $__ds_item["type"] : "boolean_d",
            "head_class" => !empty( $__ds_item["class"] ) ? $__ds_item["class"] : "",
            "args" =>  !empty( $__ds_item["args"] ) ? $__ds_item["args"] : "",
            // "size" => !empty( $__ds_item["size"] ) ? $__ds_item["size"] : "auto",
          );

          if ( $displayData["head_type"] == "time" ){
            $displayData["data"] = $displayData["data"] ? substr( $displayData["data"], 0, strlen( "yyyy-mm-dd" ) ) : null;
            if ( !empty( $__item[ "bof_{$__ds_item_name}_hr" ] ) ) $displayData["sub_data"] = $__item[ "bof_{$__ds_item_name}_hr" ];
          }

          if ( $displayData["head_type"] == "time_f" ){
            $displayData["data"] = $displayData["data"] ? substr( $displayData["data"], 0, strlen( "yyyy-mm-dd" ) ) : null;
            if ( !empty( $__item[$displayData["id"]] ) ){
              $displayData["sub_data"] = bof()->general->time_in_future_hr( strtotime( $__item[$displayData["id"]] ) );
              /*if ( time() > $_t ) {
                if ( !empty( $__item[ "bof_{$__ds_item_name}_hr" ] ) ) $displayData["sub_data"] = $__item[ "bof_{$__ds_item_name}_hr" ] . " ago";
              } else {
                $_d = $_t - time();
                $displayData["sub_data"] = "in " . bof()->general->passed_time_hr( $_d )["string"];
              }*/
            }
            $displayData["head_type"] = "time";
          }

          if ( $__ds_item ? $__ds_item["type"] == "currency" : false ){
            $displayData["head_type"] = "simple";
            if ( $displayData["data"] ){
              $default_currency = bof()->object->currency->get_default();
              $displayData["data"] = bof()->object->currency->parse_price_string( $displayData["data"], $default_currency, true );
            }
          }

          if ( $__ds_item ? in_array( "renderer", array_keys( $__ds_item ), true ) : false )
          $displayData = $__ds_item["renderer"]( $__ds_item, $__item, $displayData, $object );

          if ( !empty( $displayData["image_preview"] ) )
          $displayData["data"] = "<div class='data_with_cover'><div class='cover_wrapper' style='background-image:url(\"{$displayData["image_preview"]}\")'></div>" . $displayData["data"] . "</div>";

          $items[ $__item["ID"] ]["display"][ $__ds_item_name ] = $displayData;

        }

        $items[ $__item["ID"] ]["buttons"] = $this->object_list_parse_buttons( $object, $parsed_object, $parsed_object["buttons"], "single" );

        if ( in_array( "buttons_renderer", array_keys( $parsed_object ), true ) ){
          $_exe_buttons_renderer = $parsed_object["buttons_renderer"]( $__item, $items[ $__item["ID"] ]["buttons"], $object );
          if ( isset( $_exe_buttons_renderer ) ) $items[ $__item["ID"] ]["buttons"] = $_exe_buttons_renderer;
        }

        if ( in_array( "buttons_renderers", array_keys( $parsed_object ), true ) ){
          $renderers = $parsed_object["buttons_renderers"];
          if ( is_array( $renderers ) ){
            foreach( $renderers as $_r ){
              $_exe_buttons_renderer = $_r( $__item, $items[ $__item["ID"] ]["buttons"], $object );
              if ( isset( $_exe_buttons_renderer ) ) $items[ $__item["ID"] ]["buttons"] = $_exe_buttons_renderer;
            }
          }
        }

      }

    }

    // Get total records
    $total_records = $object->select(
      $_where,
      array(
        "limit" => 1,
        "clean" => false,
        "columns" => "COUNT(*) as count",
        "listing" => true,
        "empty_select" => true,
      )
    );

    $total_records = !empty( $total_records ) ? $total_records["count"] : 0;
    $_max_pages = ceil( $total_records / $query["select"]["limit"] );
    $_has_next = $_max_pages > $query["select"]["page"];
    $_has_pre = $query["select"]["page"] > 1;

    return array(
      "items" => empty( $items ) ? false : $items,
      "total_records" => $total_records,
      "max_pages" => $_max_pages > $query["select"]["page"] ? $_max_pages : false,
      "has_first" => $query["select"]["page"] > 2 ? 1 : false,
      "has_next" => $_has_next,
      "has_pre" => $_has_pre
    );

  }
  protected function object_list_parse_buttons( $object, $parsed_object, $buttons=[], $callerType="multi" ){

    if ( $callerType != "multi" ){

      if (
        ( $object->method_exists("bof_columns") ? ( in_array( "seo", array_values( $object->bof_columns() ), true ) || in_array( "seo", array_keys( $object->bof_columns() ), true ) ) : false ) ||
        $object->bof()["name"] == "user"
      ){
        $buttons["visit"] = array(
          "label" => "Visit",
          "ID" => "visit"
        );
      }
    }

    if ( $callerType == "multi" ? $parsed_object["config"]["multi"]["edit"] : false ){
      $buttons["edit"] = array(
        "label" => "Edit",
        "ID" => "edit"
      );
    }

    if ( $callerType == "multi" ? $parsed_object["config"]["multi"]["delete"] : false ){
      $buttons["delete"] = array(
        "label" => "Delete",
        "ID" => "delete"
      );
      if ( $object->method_exists("bof") ? !empty( $object->bof()["blacklistable"] ) : false ){
        $buttons["blacklist"] = array(
          "label" => "Delete & Blacklist",
          "ID" => "blacklist"
        );
      }
    }

    if ( $callerType != "multi" ? $parsed_object["config"]["edit"] : false ){
      $buttons["edit"] = array(
        "label" => "Edit",
        "ID" => "edit"
      );
    }

    if ( $callerType != "multi" ? $parsed_object["config"]["delete"] : false ){
      $buttons["delete"] = array(
        "label" => "Delete",
        "ID" => "delete"
      );
      if ( $object->method_exists("bof") ? !empty( $object->bof()["blacklistable"] ) : false ){
        $buttons["blacklist"] = array(
          "label" => "Delete & Blacklist",
          "ID" => "blacklist"
        );
      }
    }

    return $buttons;

  }

  public function object( $object_name ){

    $this->caller = $object_name;
    $object = bof()->object->__get( $object_name );
    $parsed = bof()->bofAdmin->object_parse_caller( $object );
    $args = $this->objects[ $object_name ];
    $request = $this->object_parse_request( $object, $parsed );

    if ( !$request )
    $request = [ "type" => "new", "IDS" => null, "content" => null, "values" => null ];

    if ( !in_array( $object_name, array_keys( $this->objects ) ) ? true : !bof()->bofAdmin->__check_access( $object_name, $request["type"] == "new" ? "new" : "edit" ) )
    return false;

    $this->object_custom_inputs( $object, $parsed, $args, $request );

    if ( $parsed["renderer"] )
    $parsed["renderer"]( $object, $parsed, $args, $request );

    if ( !empty( $request["values"] ) ){
      foreach( $request["values"] as $_k => $_v ){
        if ( !empty( $parsed["items"][ $_k ] ) )
        $parsed["items"][ $_k ]["input"]["value"] = $_v;
      }
    }

    foreach( $parsed["items"] as $item_name => &$item_data ){
      if ( $parsed["item_renderer"] )
      $parsed["item_renderer"]( $item_name, $item_data, $request, $object );
      $callArgs = [ $item_name, $request, $object ];
      bof()->call( "object_{$object_name}", "bofAdmin_object_item_renderer", $callArgs, $item_data );
    }

    if ( bof()->nest->user_input( "get", "bof", "equal", [ "value" => "submitting" ] ) )
    return $this->object_be( $object, $parsed, $args, $request );
    return $this->object_ui( $object, $parsed, $args, $request );

  }
  public function object_parse_caller( $object ){

    $bofAdmin = $object->bof_admin();
    $object_items = [];
    $groups = !empty( $bofAdmin["object_groups"] ) ? $bofAdmin["object_groups"] : [];

    if ( !empty( $bofAdmin["object"] ) ){
      foreach( $bofAdmin["object"] as $__object_name => $__object_args ){
        $__object_args["input"]["name"] = $__object_name;
        $__object_args["group"] = !empty( $__object_args["bofAdmin"]["object"]["group"] ) ? $__object_args["bofAdmin"]["object"]["group"] : null;
        $__object_args["group"] = !empty( $__object_args["group"] ) ? $__object_args["group"] : $__object_args["group"];
        if ( !empty( $column_required ) ) $__object_args["label"] .= " <nec>*</nec>";
        $object_items[ $__object_name ] = $__object_args;
      }
    }

    $parse_caller_object = bof()->object->parse_caller( $object );
    $columns = $parse_caller_object->parsed->columns;
    foreach( $columns as $column_name => $column_args ){

      if ( empty( $column_args["bofAdmin"] ) ) continue;

      $column_required = !empty( $column_args["bofAdmin"]["object"]["required"] );

      if ( in_array( "object", array_keys( $column_args["bofAdmin"] ), true ) ){
        $column_args["column_name"] = $column_name;
        $column_args["input"]["name"] = $column_name;
        $column_args["group"] = !empty( $column_args["bofAdmin"]["object"]["group"] ) ? $column_args["bofAdmin"]["object"]["group"] : null;
        $column_args["display_on"] = !empty( $column_args["bofAdmin"]["object"]["display_on"] ) ? $column_args["bofAdmin"]["object"]["display_on"] : null;
        $column_args["display_on_cond"] = !empty( $column_args["bofAdmin"]["object"]["display_on_cond"] ) ? $column_args["bofAdmin"]["object"]["display_on_cond"] : null;
        $column_args["label"] = !empty( $column_args["label"] ) ? ucwords( str_replace( "<br>", " ", $column_args["label"] ) ) : null;
        if ( !empty( $column_required ) ) $column_args["label"] .= " <nec>*</nec>";
        $object_items[ $column_name ] = $column_args;
      }

    }

    $parse_caller_object = bof()->object->parse_caller( $object );
    $relations = $parse_caller_object->parsed->relations;
    if ( $relations ){
      foreach( $relations as $relation ){
        if ( !empty( $relation["bofAdmin"]["objects"] ) ){
          foreach( $relation["bofAdmin"]["objects"] as $__object_name => $__object_args ){
            $__object_args["input"]["name"] = $__object_name;
            $__object_args["group"] = !empty( $__object_args["bofAdmin"]["object"]["group"] ) ? $__object_args["bofAdmin"]["object"]["group"] : null;
            if ( !empty( $column_required ) ) $__object_args["label"] .= " <nec>*</nec>";
            $object_items[ $__object_name ] = $__object_args;
          }
        }
      }
    }

    $data = array(
      "items" => $object_items,
      "groups" => $groups,
      "actions" => isset( $bofAdmin["actions"] ) ? $bofAdmin["actions"] : null,
      "renderer" => isset( $bofAdmin["object_renderer"] ) ? $bofAdmin["object_renderer"] : null,
      "item_renderer" => isset( $bofAdmin["object_item_renderer"] ) ? $bofAdmin["object_item_renderer"] : null,
      "ui_renderer" => isset( $bofAdmin["object_ui_renderer"] ) ? $bofAdmin["object_ui_renderer"] : null,
      "ui_renderer_before" => isset( $bofAdmin["object_ui_renderer_before"] ) ? $bofAdmin["object_ui_renderer_before"] : null,
      "be_renderer" => isset( $bofAdmin["object_be_renderer"] ) ? $bofAdmin["object_be_renderer"] : null,
      "be_renderer_after" => isset( $bofAdmin["object_be_renderer_after"] ) ? $bofAdmin["object_be_renderer_after"] : null,
      "config" => $bofAdmin["config"],
    );

    return $data;

  }
  protected function object_custom_inputs( $object, &$parsed_object, $object_args, $request ){

    if ( !empty( $object_args["biography"] ) && $request["type"] != "multi" ){
      $parsed_object["groups"][] = [ "bio", "Biography" ];
    }
    if ( in_array( "translations", array_keys( $object->bof_columns() ), true ) && $request["type"] != "multi" ){

      if ( $request["type"] == "single" )
      $_item_translations = !empty( $request["content"][ $request["IDS"][0] ]["translations_decoded"] ) ? $request["content"][ $request["IDS"][0] ]["translations_decoded"] : [];

      $indexed_non_default_langs = bof()->object->language->get_all();

      if ( $indexed_non_default_langs ) {

        $parsed_object["groups"][] = [ "translations", "Translations" ];

        foreach( $object->bof_columns()["translations"] as $_t_input_name ){

          $_t_input = $parsed_object["items"][ $_t_input_name ];

          foreach( $indexed_non_default_langs as $indexed_non_default_lang ){

            $indexed_non_default_lang_input_value =
            !empty( $_item_translations[ "{$_t_input_name}_{$indexed_non_default_lang["code2"]}" ] ) ? $_item_translations[ "{$_t_input_name}_{$indexed_non_default_lang["code2"]}" ] : null;

            if ( $_t_input["input"]["type"] == "text_editor" ){
              if ( !empty( $indexed_non_default_lang_input_value ) )
              $indexed_non_default_lang_input_value = htmlspecialchars_decode( $indexed_non_default_lang_input_value );
            }

            $parsed_object["items"]["{$_t_input_name}_{$indexed_non_default_lang["code2"]}"] = array(
              "group" => "translations",
              "label" => str_replace( "<nec>*</nec>", "", $_t_input["label"] ) . " -> {$indexed_non_default_lang["name"]} translation",
              "input" => array(
                "name" => "{$_t_input_name}_{$indexed_non_default_lang["code2"]}",
                "type" => $_t_input["input"]["type"],
                "value" => $indexed_non_default_lang_input_value
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                  "strip_emoji" => false
                )
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => false,
                ),
              ),
            );

          }

        }

      }

    }
    if ( ( in_array( "price", array_keys( $object->bof_columns() ), true ) || in_array( "price", array_values( $object->bof_columns() ), true ) ) ){

      if ( $request["type"] == "single" )
      $_price_setting = !empty( $request["content"][ $request["IDS"][0] ]["price_setting_decoded"] ) ? $request["content"][ $request["IDS"][0] ]["price_setting_decoded"] : [];

      $currency = bof()->object->currency->get_default();
      $parsed_object["items"]["price"]["tip"] = "Price in {$currency["name"]}. Leave empty or enter 0 to set price to free";

      $parsed_object["items"]["price"]["tip"] = "Price in <b>{$currency["name"]}</b>";

      if ( !empty( $object->bof_columns()["price"]["parent"] ) ){

        $parsed_object["items"]["price"]["tip"] .= "<br>If left empty or set to zero, item will follow it's {$object->bof_columns()["price"]["parent_name"]} purchase, it won't be accessible unless user has access to {$object->bof_columns()["price"]["parent_name"]}";
        $parsed_object["items"]["price"]["tip"] .= "<br>If you need to set this item free in a priced {$object->bof_columns()["price"]["parent_name"]}, set price to 0 and enable 'Force free'";
        $parsed_object["items"]["price"]["tip"] .= "<br>If bigger than zero, item will ignore <b>{$object->bof_columns()["price"]["parent_name"]}</b> purchase and have to be bought seperately";

        $parsed_object["items"]["disable_parent"] = array(
          "group" => "price",
          "public" => true,
          "label" => "Force free",
          "tip" => "Enable to force this item to be free even if it belongs to a priced {$object->bof_columns()["price"]["parent_name"]}",
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true,
            ),
          ),
          "input" => array(
            "type" => "checkbox",
            "name" => "disable_parent",
            "value" => !empty( $_price_setting["disable_parent"] )
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "price",
              "multi" => true,
            ),
          ),
        );

      }

      $parsed_object["items"]["disable_subs"] = array(
        "group" => "price",
        "public" => true,
        "label" => "Exclude subscription plans access",
        "tip" => "Users can get access to premium items either by purchasing them or subscribing to a subscription plan. If this option is enabled, subscription plans access are disabled and users have to purchase this item to access it",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true,
          ),
        ),
        "input" => array(
          "type" => "checkbox",
          "name" => "disable_subs",
          "value" => !empty( $_price_setting["disable_subs"] )
        ),
        "bofAdmin" => array(
          "object" => array(
            "group" => "price",
            "multi" => true
          ),
        ),
      );

    }
    if ( !empty( $object_args["social_links"] ) && $request["type"] != "multi" ){

      if ( $request["type"] == "single" )
      $_item_sl_data = !empty( $request["content"][ $request["IDS"][0] ]["external_addresses_decoded"] ) ? $request["content"][ $request["IDS"][0] ]["external_addresses_decoded"] : [];

      $parsed_object["groups"][] = [ "sl", "Links" ];

      $socialLinksMap = bof()->seo->get_social_links_map();

      foreach( $socialLinksMap as $_sl => $_sD ){
        $parsed_object["items"]["sl_".$_sl] = array(
          "group" => "sl",
          "label" => "<i class=\"icon-{$_sl}-sign\"></i> " . $_sD["name"],
          "multi" => false,
          "input" => array(
            "name" => "sl_".$_sl,
            "type" => "text",
            "value" => !empty( $_item_sl_data[ $_sl ] ) ? $_item_sl_data[ $_sl ] : null
          ),
          "validator" => $_sD["validator"],
        );
      }

    }
    if ( !empty( $object_args["seo"] ) && $request["type"] != "multi" ){

      if ( $request["type"] == "single" )

      $_item_seo_data = !empty( $request["content"][ $request["IDS"][0] ]["seo_data_decoded"] ) ? $request["content"][ $request["IDS"][0] ]["seo_data_decoded"] : [];

      $parsed_object["groups"][] = [ "seo", "SEO" ];

      $parsed_object["items"]["seo_url"] = array(
        "group" => "seo",
        "label" => "<span class=\"_g_n\">SEO</span> URL Slug <nec>*</nec>",
        "column_name" => "seo_url",
        "tip" =>  web_address . $object->bof_client()["single_url_prefix"] . "/<b>SLUG</b><br>Enter only <b>SLUG</b> part. Example: pink-floyd<br>Users can access this item from this url. Used as `canonical` url and og:url",
        "input" => array(
          "name" => "seo_url",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => false,
            "required" => true
          ),
        ),
      );
      $parsed_object["items"]["seo_image"] = array(
        "group" => "seo",
        "label" => "<span class=\"_g_n\">SEO</span> Image",
        "column_name" => "seo_image",
        "tip" =>  "Used as og:image and twitter:image",
        "input" => array(
          "name" => "seo_image",
          "type" => "bof_input",
          "file_type" => "image",
        ),
        "bofInput" => array(
          "file",
          array(
            "type" => "image",
            "object_type" => "seo_image"
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => false,
          ),
        ),
      );
      $parsed_object["items"]["seo_title"] = array(
        "group" => "seo",
        "label" => "<span class=\"_g_n\">SEO</span> Title",
        "tip" =>  "Script will choose best possible title if left empty<br>Used as title, og:title and twitter:title",
        "input" => array(
          "name" => "seo_title",
          "type" => "text",
          "value" => !empty( $_item_seo_data["title"] ) ? $_item_seo_data["title"] : null
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
            "strip_emoji" => false
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => false,
          ),
        ),
      );
      $parsed_object["items"]["seo_description"] = array(
        "group" => "seo",
        "label" => "<span class=\"_g_n\">SEO</span> Description",
        "tip" =>  "Script will choose best possible description if left empty<br>Used as description, og:description and twitter:description",
        "input" => array(
          "name" => "seo_description",
          "type" => "text",
          "value" => !empty( $_item_seo_data["description"] ) ? $_item_seo_data["description"] : null
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
            "strip_emoji" => false
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => false,
          ),
        ),
      );
      $parsed_object["items"]["seo_tags"] = array(
        "group" => "seo",
        "label" => "<span class=\"_g_n\">SEO</span> Tags",
        "tip" =>  "Script will choose best possible keys if left empty. Enter comma ( <b>,</b> ) separated. Example: pink-floyd,rockNroll,busyowl",
        "input" => array(
          "name" => "seo_tags",
          "type" => "text",
          "value" => !empty( $_item_seo_data["tags"] ) ? $_item_seo_data["tags"] : null
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
            "strip_emoji" => false
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => false,
          ),
        ),
      );

      $indexed_non_default_langs = bof()->object->language->get_all();
      if ( $indexed_non_default_langs ){
        foreach( $indexed_non_default_langs as $indexed_non_default_lang ){
          $parsed_object["items"]["seo_{$indexed_non_default_lang["code3"]}_title"] = array(
            "group" => "seo",
            "divider" => "{$indexed_non_default_lang["name"]} Translations",
            "label" => "<span class=\"_g_n\">SEO {$indexed_non_default_lang["code2"]}</span> {$indexed_non_default_lang["name"]} Title",
            "tip" =>  "Script will choose best possible title if left empty<br>Used as title, og:title and twitter:title",
            "input" => array(
              "name" => "seo_{$indexed_non_default_lang["code3"]}_title",
              "type" => "text",
              "value" => !empty( $_item_seo_data[ $indexed_non_default_lang["code2"] ]["title"] ) ? $_item_seo_data[ $indexed_non_default_lang["code2"] ]["title"] : null
            ),
            "validator" => array(
              "string",
              array(
                "empty()",
                "strip_emoji" => false
              )
            ),
            "bofAdmin" => array(
              "object" => array(
                "multi" => false,
              ),
            ),
          );
          $parsed_object["items"]["seo_{$indexed_non_default_lang["code3"]}_description"] = array(
            "group" => "seo",
            "label" => "<span class=\"_g_n\">SEO {$indexed_non_default_lang["code2"]}</span> {$indexed_non_default_lang["name"]} Description",
            "tip" =>  "Script will choose best possible description if left empty<br>Used as description, og:description and twitter:description",
            "input" => array(
              "name" => "seo_{$indexed_non_default_lang["code3"]}_description",
              "type" => "text",
              "value" => !empty( $_item_seo_data[ $indexed_non_default_lang["code2"] ]["description"] ) ? $_item_seo_data[ $indexed_non_default_lang["code2"] ]["description"] : null
            ),
            "validator" => array(
              "string",
              array(
                "empty()",
                "strip_emoji" => false
              )
            ),
            "bofAdmin" => array(
              "object" => array(
                "multi" => false,
              ),
            ),
          );
          $parsed_object["items"]["seo_{$indexed_non_default_lang["code3"]}_tags"] = array(
            "group" => "seo",
            "label" => "<span class=\"_g_n\">SEO {$indexed_non_default_lang["code2"]}</span> {$indexed_non_default_lang["name"]} Tags",
            "tip" =>  "Script will choose best possible keys if left empty. Enter comma ( <b>,</b> ) separated. Example: pink-floyd,rockNroll,busyowl",
            "input" => array(
              "name" => "seo_{$indexed_non_default_lang["code3"]}_tags",
              "type" => "text",
              "value" => !empty( $_item_seo_data[ $indexed_non_default_lang["code2"] ]["tags"] ) ? $_item_seo_data[ $indexed_non_default_lang["code2"] ]["tags"] : null
            ),
            "validator" => array(
              "string",
              array(
                "empty()",
                "strip_emoji" => false
              )
            ),
            "bofAdmin" => array(
              "object" => array(
                "multi" => false,
              ),
            ),
          );
        }
      }

    }

    return $parsed_object;

  }
  protected function object_parse_request( $object, $parsed_object ){

    $custom_ids = !empty( $parsed_object["config"]["custom_ids"] );

    $id_request_string = bof()->nest->user_input( "get", "IDs", "string" );

    if ( $custom_ids )
    $IDS = explode( ",", $id_request_string );

    else if ( bof()->general->numeric( $id_request_string ) )
    $IDS = [ $id_request_string ];

    elseif ( $id_request_string ? count( explode( ",", $id_request_string ) ) > 1 : false ) {
      $id_request_string_exploded = explode( ",", $id_request_string );
      foreach( $id_request_string_exploded as $id_request_string_exploded_one ){
        if ( bof()->general->numeric( $id_request_string_exploded_one ) )
        $IDS[] = $id_request_string_exploded_one;
      }
    }

    if ( empty( $IDS ) )
    return false;

    $deleting = bof()->nest->user_input( "post", "__action", "equal", [ "value" => "delete" ] );

    foreach( $IDS as $_ID ){

      if ( !( $_content = $object->select( array(
        "ID" => $_ID
      ), array(
        $deleting ? "deleting" : "editing" => true,
        //"editing" => true
      ) ) ) ) continue;

      $contents[ $_ID ] = $_content;
      foreach( $_content as $_content_k => $_content_v ){
        $values[ $_content_k ][] = is_array( $_content_v ) ? null : $_content_v;
      }

    }

    if ( empty( $contents ) )
    return false;

    foreach( $values as $value_k => $_values ){
      $one = count( array_unique( $_values ) ) == 1;
      $values_unique[ $value_k ] = $one ? reset( $_values ) : null;
    }

    return array(
      "type" => count( $contents ) > 1 ? "multi" : "single",
      "IDS" => array_keys( $contents ),
      "content" => $contents,
      "values" => $values_unique
    );

  }
  protected function object_ui( $object, $parsed_object, $object_args, $request ){

    if ( $parsed_object["ui_renderer_before"] )
    $parsed_object["ui_renderer_before"]( $object, $parsed_object, $object_args, $request, $parsed_object["items"] );

    $call_args = [ $object, $object_args, $request, $parsed_object["items"] ];
    bof()->call( "bofAdmin_" . $object->bof()["name"], "ui_renderer_before", $call_args, $parsed_object );

    $_inputs = [];
    foreach( $parsed_object["items"] as $_k => $_structure ){

      if ( empty( $_structure["input"]["name"] ) )
      continue;

      if ( $request["type"] == "multi" && empty( $_structure["bofAdmin"]["object"]["multi"] ) )
      continue;

      if ( empty( $_structure["group"] ) )
      $_structure["group"] = "detail";

      $_value = null;

      if ( !empty( $_structure["col_name"] )  ){
        $_value = isset( $request["values"][ $_structure["col_name"] ] ) ? $request["values"][ $_structure["col_name"] ] : null;
        if ( $request["type"] == "multi" && !isset( $_value ) ) $_value = "--- Different Values ---";
        $_structure["input"]["value"] = $_value;
      }

      if ( !empty( $_structure["bofInput"] ) ){

        if ( $_structure["bofInput"][0] == "object" && !empty( $_structure["bofInput"][1]["multi"] ) && !isset( $_structure["bofInput"][1]["autoload"] ) && !empty( $request["IDS"][0] ) ){
          $_structure["bofInput"][1]["args"]["load"]["where"][ $_k ] = $request["IDS"][0];
        }

        $_parse_input = bof()->bofInput->parse( $_structure );
        $_structure = $_parse_input["data"];

      }

      if ( !empty( $_structure["bofAdmin"]["object"]["offable"] ) )
      $_structure["oner"] = array(
        "type" => "checkbox",
        "name" => "{$_structure["input"]["name"]}_oner",
        "value" => !empty( $_structure["input"]["value"] ) ? true : false
      );

      if ( $request["type"] == "multi" )
      $_structure["locked"] = array(
        "type" => "checkbox",
        "name" => "{$_structure["input"]["name"]}_edit"
      );

      if ( !empty( $_structure["bofAdmin"]["object"]["seo_slug_source"] ) )
      $_structure["input"]["class"] = !empty( $_structure["input"]["class"] ) ? $_structure["input"]["class"] . " seo_slug" : "seo_slug";

      if ( !empty( $_structure["bofAdmin"] ) )
      unset( $_structure["bofAdmin"] );


      $_inputs[ $_k ] = $_structure;

    }

    $data = array(
      "type" => $request["type"],
      "request" => $request,
      "values" => $request[ "values" ],
      "display" => $_inputs,
      "groups" => !empty( $parsed_object["groups"] ) ? array_merge( [ [ "detail", "Details", true ] ], $parsed_object["groups"] ) : false,
      "endpoint" => "bofAdmin/object/". $object->bof()["name"] ."/",
      "structure" => $parsed_object
    );

    if ( $parsed_object["ui_renderer"] )
    $parsed_object["ui_renderer"]( $object, $parsed_object, $object_args, $request, $data["display"], $data );

    $__mArgs = [ $object, $parsed_object, $object_args, $request, $data["display"], $data ];
    bof()->call( get_class( $object->direct() ), "bofAdmin_object_ui_renderer", $__mArgs, $data );

    bof()->api->set_message( "ok", $data );
    return true;

  }
  protected function object_be( $object, $parsed_object, $object_args, $request ){

    $columns = bof()->object->parse_caller( $object )->parsed->columns;

    if ( isset( $_POST["__action"] ) )
    return $this->object_action_be( $object, $parsed_object, $object_args, $request );

    $_inputs = [
      "data" => [],
      "report" => array(
        "fail" => [],
        "ok" => [],
        "empty" => []
      ),
      "set" => []
    ];

    foreach( $parsed_object["items"] as $input_name => $input ){

      if ( $request["type"] == "multi" && empty( $input["bofAdmin"]["object"]["multi"] ) )
      continue;

      if ( $request["type"] == "multi" && empty( $_POST[ "{$input_name}_edit" ] ) )
      continue;

      $input_required = !empty( $input["bofAdmin"]["object"]["required"] );
      $input_zero = !empty( $input["accept_zero"] );

      // get value
      if ( !empty( $input["bofInput"] ) ){

        $input_exists = !empty( $_POST[ $input_name ] );
        $input_value = bof()->bofInput->validate( $input );
        if ( !$input_value && !empty( $input["column_name"] ) ){
          $_inputs["set"][ $input["column_name"] ] = $input["bofInput"][0] != "color" ? 0 : false;
          $_inputs["update"][ $input["column_name"] ] = $input["bofInput"][0] != "color" ? 0 : false;
        }
        if ( !$input_value )
        $input_value = 0;

      }
      else {

        list( $input_exists, $input_value ) = bof()->bofInput->__get_value( $input_name, $input, "post" );

      }

      if ( !empty( $input["bofAdmin"]["object"]["offable"] ) ){
        if ( !bof()->nest->user_input( "post", "{$input_name}_oner", "boolean" ) ){
          $input_value = null;
          $input_exists = false;
        }
      }

      // update report
      if ( $input_value ){

        $_inputs["report"]["ok"][ $input_name ] = $input_value;

        if ( !empty( $input["column_name"] ) ){
          $_inputs["set"][ $input["column_name"] ] = $input_value;
          $_inputs["update"][ $input["column_name"] ] = $input_value;
        }

      }
      elseif ( !$input_required && ( !$input_exists || $input_value === 0 || $input_value === 0.0 || $input_value === "0" ) ){

        $_inputs["report"]["empty"][ $input_name ] = $input_value;

        if ( !empty( $input["column_name"] ) && empty( $input["bofInput"] ) && ( empty( $input["validator"] ) ? true : $input["validator"][0] != "timestamp" && $input["validator"][0] != "datetime" ) ){
          $_inputs["set"][ $input["column_name"] ] = $input_value;
          $_inputs["update"][ $input["column_name"] ] = $input_value;
        }

        if ( empty( $input_zero ) )
        $input_value = false;
        elseif ( $input_value === 0 || $input_value === "0" )
        $input_value = 0;
        else
        $input_value = null;

        if ( !empty( $input["input"]["type"] ) ? $input["input"]["type"] == "checkbox" && !empty( $input["column_name"] ) : false ){
          $_inputs["set"][ $input["column_name"] ] = 0;
          $_inputs["update"][ $input["column_name"] ] = 0;
          $input_value = 0;
        }

      }
      else {

        $_inputs["report"]["fail"][ $input_name ] = $input_required && !$input_exists ? "Can't be empty" : null;
        $input_value = false;

      }

      $_inputs[ "data" ][ $input_name ] = $input_value;

    }

    if ( $parsed_object["be_renderer"] ){
      $run_renderer = $parsed_object["be_renderer"]( $_inputs, $request, $object );
      if ( $run_renderer ) $_inputs = $run_renderer;
    }

    bof()->call( get_class( $object->direct() ), "bofAdmin_object_be_renderer", $request, $_inputs );

    if ( empty( $_inputs["report"]["fail"] ) ){

      foreach( $columns as $_k => $column ){

        if ( !isset( $column["bofAdmin_validator"] ) ) continue;

        $code_args = false;
        $make_hash = false;
        $bofAdmin_validator = $column["bofAdmin_validator"];
        extract( $bofAdmin_validator );

        if ( $code_args && $request["type"] != "multi" ){

          foreach( $code_args["from"] as $_f ){
            if ( !empty( $_inputs["data"][ $_f ] ) )
            $new_code_array[] = $_inputs["data"][ $_f ];
          }

          if ( empty( $new_code_array ) )
          fall( "bad_code" );

          $new_code = bof()->general->make_code( implode( "-", $new_code_array ) );
          $request_id = !empty( $request["IDS"][0] ) ? $request["IDS"][0] : false;

          $check = $object->select(array(
            "code" => $new_code
          ));

          if ( $check ? ( $request_id ? $check["ID"] != $request_id : true ) : false ){
            $_inputs["report"]["fail"][ reset( $code_args["from"] ) ] = "Already in use";
          } else {
            $_inputs["data"]["code"] = $_inputs["set"]["code"] = $_inputs["update"]["code"] = $new_code;
          }

        }
        elseif( $make_hash && $request["type"] != "multi" ){
          $_inputs["data"]["hash"] = $_inputs["set"]["hash"] = $object->get_free_hash();
        }

      }

    }

    if ( $_inputs["report"]["fail"] ){
      bof()->api->set_error( "Failed. Check the form", [ "bad_inputs" => array_keys( $_inputs["report"]["fail"] ), "inputs" => $_inputs ] );
      return;
    }

    if ( in_array( "price", array_keys( $object->bof_columns() ), true ) || in_array( "price", array_values( $object->bof_columns() ), true ) ){

      if ( !empty( $object->bof_columns()["price"]["parent"] ) ){
        $_inputs["set"]["price_setting"] = $_inputs["update"]["price_setting"] = json_encode( array(
          "disable_parent" => !empty( $_inputs["data"]["disable_parent"] ),
          "disable_subs" => !empty( $_inputs["data"]["disable_subs"] ),
        ) );
      }
      else {
        $_inputs["set"]["price_setting"] = $_inputs["update"]["price_setting"] = json_encode( array(
          "disable_subs" => !empty( $_inputs["data"]["disable_subs"] ),
        ) );
      }

    }

    if (  in_array( "translations", array_keys( $object->bof_columns() ), true ) ){

      $_inputs["set"]["translations"] = $_inputs["update"]["translations"] = array();
      $indexed_non_default_langs = bof()->object->language->get_all();

      if ( $indexed_non_default_langs ){

        foreach( $object->bof_columns()["translations"] as $_t_input_name ){

          $_t_input = $parsed_object["items"][ $_t_input_name ];

          foreach( $indexed_non_default_langs as $indexed_non_default_lang ){
            if ( !empty( $_inputs["data"][ "{$_t_input_name}_{$indexed_non_default_lang["code2"]}" ] ) )
            $_inputs["set"]["translations"][ "{$_t_input_name}_{$indexed_non_default_lang["code2"]}" ] = $_inputs["update"]["translations"][ "{$_t_input_name}_{$indexed_non_default_lang["code2"]}" ] = $_inputs["data"][ "{$_t_input_name}_{$indexed_non_default_lang["code2"]}" ];
          }

        }

      }

      $_inputs["set"]["translations"] = $_inputs["update"]["translations"] = json_encode( $_inputs["set"]["translations"], JSON_UNESCAPED_UNICODE );

    }

    if ( !empty( $object_args["seo"] ) && $request["type"] != "multi" ){

      $seo_data = [];
      if ( !empty( $_inputs["data"]["seo_title"] ) ) $seo_data["title"] = $_inputs["data"]["seo_title"];
      if ( !empty( $_inputs["data"]["seo_description"] ) ) $seo_data["description"] = $_inputs["data"]["seo_description"];
      if ( !empty( $_inputs["data"]["seo_tags"] ) ) $seo_data["tags"] = $_inputs["data"]["seo_tags"];
      $indexed_non_default_langs = bof()->object->language->get_all();
      if ( $indexed_non_default_langs ){
        foreach( $indexed_non_default_langs as $indexed_non_default_lang ){
          if ( !empty( $_inputs["data"]["seo_{$indexed_non_default_lang["code3"]}_title"] ) ) $seo_data[$indexed_non_default_lang["code2"]]["title"] = $_inputs["data"]["seo_{$indexed_non_default_lang["code3"]}_title"];
          if ( !empty( $_inputs["data"]["seo_{$indexed_non_default_lang["code3"]}_description"] ) ) $seo_data[$indexed_non_default_lang["code2"]]["description"] = $_inputs["data"]["seo_{$indexed_non_default_lang["code3"]}_description"];
          if ( !empty( $_inputs["data"]["seo_{$indexed_non_default_lang["code3"]}_tags"] ) ) $seo_data[$indexed_non_default_lang["code2"]]["tags"] = $_inputs["data"]["seo_{$indexed_non_default_lang["code3"]}_tags"];
        }
      }

      $_inputs["set"]["seo_data"] = $_inputs["update"]["seo_data"] = json_encode( $seo_data, JSON_UNESCAPED_UNICODE );

      if ( $request["type"] == "single" )
      $_itemID = $request["IDS"][0];
      $checkURL = $object->select(["seo_url"=>$_inputs["data"]["seo_url"]]);

      if ( $checkURL ? ( !empty( $_itemID ) ? $checkURL["ID"] != $_itemID : true ) : false ){
        $_inputs["report"]["fail"]["seo_url"] = "Another item is using this URL";
        bof()->api->set_error( "Bad Url", [ "bad_inputs" => array_keys( $_inputs["report"]["fail"] ), "inputs" => $_inputs ] );
        return;
      }

    }

    if ( !empty( $object_args["social_links"] ) && $request["type"] != "multi" ){

      $sl_data = [];
      foreach( array_keys( bof()->seo->get_social_links_map() ) as $_sl ){
        if ( !empty( $_inputs["data"]["sl_".$_sl] ) ) $sl_data[$_sl] = $_inputs["data"]["sl_".$_sl];
      }
      $_inputs["set"]["external_addresses"] = $_inputs["update"]["external_addresses"] = json_encode( $sl_data, JSON_UNESCAPED_UNICODE );

    }

    if ( $request["type"] == "new" ){

      try {
        $create = $object->create(
          ["ignore_blacklist()"],
          $_inputs["set"],
          null
        );
      } catch( Exception $err ){
        bof()->api->set_error( "Failed to create: " . $err->getMessage(), [ "detail" =>  $err->getMessage() ] );
        return;
      }

      if ( !$create ){
        bof()->api->set_error( "Failed to create", [ "detail" => $create ] );
        return;
      }

    }
    else {
      foreach ( $request["IDS"] as $__id ){

        try {
          $update = $object->create(
            array(
              "ignore_blacklist()",
              "ID" => $__id
            ),
            $_inputs["set"],
            !empty( $_inputs["update"] ) ? $_inputs["update"] : null
          );
        } catch( Exception $err ){
          bof()->api->set_error( "Failed to update ID:{$__id}: " . $err->getMessage(), [ "detail" =>  $err->getMessage() ] );
          return;
        }

        if ( !$update ){
          bof()->api->set_error( "Failed to update ID:{$__id}", [ "detail" => $update ] );
          return;
        }

      }
    }

    if ( $parsed_object["be_renderer_after"] ){
      $parsed_object["be_renderer_after"]( $_inputs, $request, $request["type"] == "new" ? $create : $request["IDS"], $object );
    }

    $callArgs = array(
      "request" => $request,
      "create_id" => $request["type"] == "new" ? $create : ( is_array( $request["IDS"] ) ? reset( $request["IDS"] ) : $request["IDS"] )
    );
    bof()->call( get_class( $object->direct() ), "bofAdmin_object_be_renderer_after", $callArgs, $_inputs );

    bof()->api->set_message( $request["type"] == "new" ? "Created" : "Updated", [ "inputs" => $_inputs, "redirect" => $request["type"] == "new" ? $parsed_object["config"]["edit_page_url"] . "/{$create}" : null ] );

  }
  protected function object_action_be( $object, $parsed_object, $object_args, $request ){

    $list_actions = [ "delete", "visit" ];

    if ( $parsed_object["actions"] )
    $list_actions = array_merge( array_keys( $parsed_object["actions"] ), $list_actions );

    $action = bof()->nest->user_input( "post", "__action", "in_array", [ "values" => $list_actions ] );
    if ( !$action || $action == "edit" ) fall( "Invalid action" );

    if ( $action == "delete" ){

      if ( bof()->bofAdmin->__check_access( $object->bof()["name"], "delete" ) ){
        if ( !empty( $request["IDS"] ) ){

          if ( $object->method_exists("bof") ? !empty( $object->bof()["blacklistable"] ) && bof()->nest->user_input( "post", "blacklist", "boolean", [ "empty()" ] ) : false ){

            $getCodes = $object->select(
              array(
                "ID_in" => $request["IDS"]
              ),
              array(
                "clean" => false,
                "limit" => false,
                "single" => false
              )
            );

            if ( !empty( $getCodes ) ){
              foreach( $getCodes as $code ){
                if ( !empty( $code["code"] ) ){
                  bof()->object->blacklist->create(
                    array(
                      "and()",
                      "object_type" => $object->bof()["name"],
                      "code" => $code["code"]
                    ),
                    array(
                      "object_type" => $object->bof()["name"],
                      "code" => $code["code"]
                    ),
                    array(
                      "object_type" => $object->bof()["name"],
                      "code" => $code["code"]
                    )
                  );
                }
              }
            }

          }
          
          $object->delete( array(
            "ID_in" => implode( ",", $request["IDS"] )
          ) );

          $response = [ true, "deleted" ];

        }
      }

    }
    elseif ( $action == "visit" ){

      if ( bof()->bofAdmin->__check_access( $object->bof()["name"], "edit" ) ){
        if ( !empty( $request["IDS"] ) ){
          $item = $object->sid( $request["IDS"][0] );
          if ( !empty( $item["seo_url"] ) ? substr( $item["seo_url"], 0, 1 ) == "/" : false )
          $item["seo_url"] = substr( $item["seo_url"], 1 );
          $response = [ true, "visit", [ "url" => web_address . bof()->seo->url( $object, $item ) ] ];
        }
      }

    }
    else {
      if ( !empty( $request["IDS"] ) )
      $response = $parsed_object["actions"][ $action ]( implode( ",", $request["IDS"] ) );
    }

    if ( !empty( $response ) ){
      bof()->api->set_message( $response[1], !empty($response[2])?$response[2]:[], $response[0] );
    }

  }

  public function setting( $setting_name ){

    if ( !in_array( $setting_name, array_keys( $this->setting ), true ) )
    return false;

    $structure = $this->setting[ $setting_name ];
    if ( bof()->nest->user_input( "get", "bof", "equal", [ "value" => "submitting" ] ) )
    return bof()->bofAdmin->setting_be( $setting_name, $structure );
    return bof()->bofAdmin->setting_ui( $setting_name, $structure );

  }
  public function setting_ui( $setting_name, $structure ){

    if ( !empty( $structure["functions"]["ui_pre"] ) ){
      if ( is_callable( $structure["functions"]["ui_pre"] ) ){
        $structure["groups"] = $structure["functions"]["ui_pre"]( $structure["groups"] );
      } else {
        $__on = $structure["functions"]["ui_pre"][0];
        $__fn = $structure["functions"]["ui_pre"][1];
        $structure["groups"] = bof()->object->__get( $__on )->$__fn( $structure["groups"] );
      }
    }

    foreach( $structure["groups"] as $_g_key => &$_g_data ){
      foreach( $_g_data["inputs"] as $_i_key => &$_i_data ){

        if ( !empty( $_i_data["col_name"] ) && empty( $_i_data["col_name_skip_load"] ) ){
          $_db_value = bof()->object->db_setting->get( $_i_data["col_name"], null, false );
          if ( $_db_value !== false && $_db_value !== null )
          $_i_data["input"]["value"] = $_db_value;
        }

        if ( !empty( $_i_data["bofInput"] ) ){
          if ( !empty( $_i_data["input"]["value"] ) ){

            if ( $_i_data["bofInput"][0] == "object" ){
              $IDS = is_array( $_i_data["input"]["value"] ) ? $_i_data["input"]["value"] : explode( ",", $_i_data["input"]["value"] );
              $_i_data["bofInput"][1]["args"]["load"]["IDS"] = $IDS;
            }

          }

          $_parse_input = bof()->bofInput->parse( $_i_data );
          $_i_data = $_parse_input["data"];
        }
        elseif ( !empty( $_i_data["input"]["type"] ) ? $_i_data["input"]["type"] == "select_m" && !empty( $_i_data["input"]["value"] ) : false ){
          if ( !is_array( $_i_data["input"]["value"] ) )
          $_i_data["input"]["value"] = explode( ",", $_i_data["input"]["value"] );
        }

        bof()->call( "bofAdmin", "setting_ui_input", $setting_name, $_i_data );

      }
      $_groups[ $_g_key ] = $_g_data;
    }

    $_output = array(
      "exe_display_rules" => !empty( $structure["exe_display_rules"] ),
      "groups" => $_groups,
      "action_btn_title" => !empty( $structure["action_btn_title"] ) ? $structure["action_btn_title"] : "Save"
    );

    if ( !empty( $structure["functions"]["ui_after"] ) ){
      if ( is_callable( $structure["functions"]["ui_after"] ) ){
        $structure["groups"] = $structure["functions"]["ui_after"]( $structure["groups"], $_output );
      } else {
        $__on = $structure["functions"]["ui_after"][0];
        $__fn = $structure["functions"]["ui_after"][1];
        $structure["groups"] = bof()->object->__get( $__on )->$__fn( $structure["groups"], $_output );
      }
    }

    bof()->api->set_message( "ok", $_output );

  }
  public function setting_be( $setting_name, $structure ){

    $_inputs = [
      "data" => [],
      "report" => array(
        "fail" => [],
        "ok" => [],
        "empty" => []
      ),
      "set" => []
    ];

    if ( !empty( $structure["functions"]["be_pre"] ) ){
      if ( is_callable( $structure["functions"]["be_pre"] ) ){
        $structure["groups"] = $structure["functions"]["be_pre"]( $structure["groups"] );
      } else {
        $__on = $structure["functions"]["be_pre"][0];
        $__fn = $structure["functions"]["be_pre"][1];
        $structure["groups"] = bof()->object->__get( $__on )->$__fn( $structure["groups"] );
      }
    }

    foreach( $structure["groups"] as $_g_key => &$_g_data ){
      foreach( $_g_data["inputs"] as $_i_key => &$input ){

        if ( !empty( $input["input"]["name"] ) ){

          $input_value = null;
          $input_name = $input["input"]["name"];
          $input_exists = !empty( $_POST[ $input_name ] );
          $input_required = true;

          // get value
          if ( !empty( $input["bofInput"] ) ){
            $input_value = bof()->bofInput->validate( $input );
            if ( !empty( $input["validator"][1] ) ? in_array( "empty()", $input["validator"][1], true ) : false ){
              $input_required = false;
            }
            if ( $input_value && $input["bofInput"][0] == "file" ){

              $old_value = bof()->object->db_setting->get( $input["input"]["name"] );

              if ( $old_value != $input_value ){
                $_validate_file = bof()->object->file->finalize_upload(
                  $input["bofInput"][1]["type"],
                  $input["bofInput"][1]["object_type"],
                  "st_" . $input["input"]["name"],
                  $input_value,
                  $old_value,
                  $input["bofInput"][1]
                );
              }

            }
            elseif ( $input_value && $input["bofInput"][0] == "color" && !empty( $input["bofInput"][1]["toRGB"] ) ){

              $color_hex = $input_value;
              list($r, $g, $b) = sscanf($color_hex, "%02x%02x%02x");
              $color_rgb = "{$r}, {$g}, {$b}";
              $_inputs["set"]["{$input_name}_rgb"] = $color_rgb;

            }
          }
          elseif ( $input["input"]["type"] == "select_m" ){
            $input_value = [];
            foreach( $input["validator"][1]["values"] as $_i_sm_val ){
              if ( bof()->nest->user_input( "post", $input_name . "_" . $_i_sm_val, "boolean" ) )
              $input_value[] = $_i_sm_val;
            }
            $input_value = $input_value ? implode( ",", $input_value ) : null;
          }
          else {
            if ( !empty( $input["validator"] ) ){
              list( $input_validator, $input_validator_args ) = $input["validator"];
            }
            if ( !empty( $input_validator_args ) ? in_array( "empty()", $input_validator_args, true ) : false ){
              $input_required = false;
            }
            if ( !empty( $input_validator ) && $input_exists ){
              $input_value = bof()->nest->user_input( "post", $input_name, $input_validator, $input_validator_args, false );
            }
          }

          // update report
          if ( $input_value ){

            $_inputs["report"]["ok"][ $input_name ] = $input_value;
            if ( !empty( $input["col_name"] ) )
            $_inputs["set"][ $input["col_name"] ] = $input_value;

          }
          elseif ( !$input_required && !$input_exists ){

            $_inputs["report"]["empty"][ $input_name ] = $input_value;
            if ( !empty( $input["col_name"] ) )
            $_inputs["set"][ $input["col_name"] ] = false;
            $input_value = null;

          }
          else {

            $_inputs["report"]["fail"][ $input_name ] = $input_value;
            $input_value = false;

          }
          $_inputs[ "data" ][ $input_name ] = $input_value;

        }

      }
    }

    if ( !empty( $structure["functions"]["be_after"] ) ){
      if ( is_callable( $structure["functions"]["be_after"] ) ){
        $_inputs = $structure["functions"]["be_after"]( $structure["groups"], $_inputs );
      } else {
        $__on = $structure["functions"]["be_after"][0];
        $__fn = $structure["functions"]["be_after"][1];
        $_inputs = bof()->object->__get( $__on )->$__fn( $structure["groups"], $_inputs );
      }
    }

    if ( $_inputs["report"]["fail"] ){
      bof()->api->set_error( "Failed", [ "bad_inputs" => array_keys( $_inputs["report"]["fail"] ), "inputs" => $_inputs ] );
      return;
    }

    if ( !empty( $_inputs["set"] ) ){
      foreach( $_inputs["set"] as $_k => $_v ){
        bof()->object->db_setting->set( $_k, $_v );
      }
    }

    bof()->api->set_message( "Saved", [ "inputs" => $_inputs ] );

  }

  public function stats( $name ){

    $stats_list = bof()->bofAdmin->_get_stats();

    if ( !in_array( $name, array_keys( $stats_list ), true ) )
    return false;

    $stats_structure = $stats_list[ $name ];

    if ( !empty( $stats_structure["functions"]["exe"] ) )
    $stats_structure = $stats_structure["functions"]["exe"]( $name, $stats_structure );

    $range = bof()->nest->user_input( "get", "range", "timestamp_range" );
    if ( $range ) $userRange = true;
    if ( !$range ) $range = bof()->general->mysql_timestamp( time() - (10*24*60*60) ) . " - " . bof()->general->mysql_timestamp();

    $stats_structure_parsed = $this->parse_structure( $name, $stats_structure, $range, !empty( $userRange ) );
    $stats_structure_re = $this->rearrange_structure( $name, $stats_structure_parsed );

    bof()->api->set_message( "Saved", array(
      "structure_re" => $stats_structure_re["re"],
      "structure_items" => $stats_structure_re["items"],
      "title" => !empty( $stats_structure["title"] ) ? $stats_structure["title"] : null,
      "range_hr" => $range
    ) );

  }
  protected function parse_structure( $name, $structure, $range, $userRange ){

    if ( empty( $structure["items"] ) )
    return $structure;

    foreach( $structure["items"] as $stat_name => &$stat_item ){

      if ( !empty( $structure["functions"]["exe_item"] ) )
      $stat_item = $structure["functions"]["exe_item"]( $name, $stat_item["type"], $stat_name, $stat_item );

      if ( $stat_item["type"] == "cards" ){
        foreach( $stat_item["cards"] as $i => $card ){
          $stat_item["cards"][$i]["value"] = $card["value"] ? ( bof()->general->numeric( $card["value"] ) ? number_format( $card["value"] ) : $card["value"] ) : $card["value"];
        }
      }
      elseif ( $stat_item["type"] == "graph" && !empty( $stat_item["graph"] ) ){

        try {
          $parseGraph = $this->parse_graph( $stat_name, $stat_item["graph"], $range, $userRange );
          $stat_item["graph_parsed"] = $parseGraph;
        } catch( Exception $err ){
          var_dump( $err->getMessage() );
          die;
        }

        unset( $stat_item["graph"] );

      }

    }

    return $structure;

  }
  protected function parse_graph( $name, $graph, $range, $userRange ){

    list ( $range_o, $range_l ) = explode( " - ", $range );
    $days = ceil( abs( ( strtotime( $range_l ) - strtotime( $range_o ) ) / ( 24*60*60 ) ) );

    $query_hash = md5( $name );
    $params_hash = md5( $userRange ? $range : "def" );

    $check_cache = bof()->db->query("SELECT * FROM _bof_cache_db WHERE query_hash = '{$query_hash}' AND params_hash = '{$params_hash}' AND time_add > SUBDATE( now(), INTERVAL 5 MINUTE ) ORDER BY time_add DESC LIMIT 1", null, true );

    if ( $check_cache->num_rows ){
      $cache = $check_cache->fetch_assoc();
      bof()->db->query("UPDATE _bof_cache_db SET used = used + 1 WHERE ID = '{$cache["ID"]}' ", null, true);
      return json_decode( $cache["results"], true );
    }

    $force_range = false;
    $table = false;
    $items = false;
    $type = null;
    $labels = null;
    $tooltip_append = null;
    $extraWhere = "";
    $height = 300;
    extract( $graph );

    if ( $force_range ){
      $range_l = bof()->general->mysql_timestamp();
      $range_o = bof()->general->mysql_timestamp( time() - ( $force_range*24*60*60 ) );
      $days = $force_range;
    }

    $extraWhere = $extraWhere ? "{$extraWhere} AND" : "";

    if ( !$type )
    throw new Exception("no_type");

    if ( !$table && !$items )
    throw new Exception("no_input");

    if ( $type == "xy_basic" ){

      $range_oo = bof()->general->mysql_timestamp( strtotime($range_o) - ( strtotime($range_l) - strtotime($range_o) ) );
      $query = $this->parse_graph_xy_query(
        "basic",
        array(
          "string" => "SELECT DATE({$xy_basic_time_col}) as _day,{$xy_basic_val_col} as _val FROM `{$table}` WHERE {$extraWhere} {$xy_basic_time_col} BETWEEN '{$range_o}' AND '{$range_l}' GROUP BY DATE({$xy_basic_time_col})",
          "table" => $table,
        ),
        array(
          "start" => $range_o,
          "end" => $range_l,
          "days" => $days
        )
      );
      $query_previous = $this->parse_graph_xy_query(
        "basic",
        array(
          "string" => "SELECT DATE({$xy_basic_time_col}) as _day,{$xy_basic_val_col} as _val FROM `{$table}` WHERE {$extraWhere} {$xy_basic_time_col} BETWEEN '{$range_oo}' AND '{$range_o}' GROUP BY DATE({$xy_basic_time_col})",
          "table" => $table,
        ),
        array(
          "start" => $range_oo,
          "end" => $range_o,
          "days" => $days
        )
      );

      $items = [];
      for ( $i=0; $i<$days; $i++ ){
        $i_k = array_keys( $query )[$i];
        $i_v = $query[ $i_k ];
        $b_k = array_keys( $query_previous )[$i];
        $b_v = $query_previous[ $b_k ];
        $items[] = array(
          "date" => $i_k,
          "value" => $i_v,
          "previousDate" => $b_k,
          "previousValue" => $b_v
        );
      }

    }
    elseif ( $type == "xy_stacked" ){

      $query = $this->parse_graph_xy_query(
        "stacked",
        array(
          "string" => "SELECT DATE({$xy_stacked_time_col}) as _day,{$xy_stacked_val_col} as _val,{$xy_stacked_type_col} as _type FROM `{$table}` WHERE {$extraWhere} {$xy_stacked_time_col} BETWEEN '{$range_o}' AND '{$range_l}' GROUP BY DATE({$xy_stacked_time_col}), {$xy_stacked_type_col}",
          "table" => $table,
        ),
        array(
          "start" => $range_o,
          "end" => $range_l,
          "days" => $days
        )
      );

      if ( $query ){
        foreach( $query as $_k => &$_v )
        $_v["date"] = $_k;
        $items = array_values( $query );
      }

    }
    elseif ( $type == "pie_basic" ){

      $items = bof()->db->_query( array(
        "table" => $table,
        "action" => "select",
        "query" => "SELECT {$pie_var_col} as _var, {$pie_val_col} as _val FROM `{$table}` WHERE {$pie_time_col} BETWEEN '{$range_o}' AND '{$range_l}' GROUP BY {$pie_var_col}",
      ) );

    }
    elseif ( $type == "map" ){

      $get_items = bof()->db->_query( array(
        "table" => $table,
        "action" => "select",
        "query" => "SELECT {$map_country_col} as _country,{$map_val_col} as _val FROM `{$table}` WHERE time_add BETWEEN '{$range_o}' AND '{$range_l}' GROUP BY {$map_country_col}",
      ) );

      if ( $get_items ){
        $items = [];
        foreach( $get_items as $item ){
          $items[] = array(
            "id" => $item["_country"] ? strtoupper( $item["_country"] ) : "Unkown",
            "value" => $item["_val"]
          );
        }
      }

    }

    $output = array(
      "type" => $type,
      "items" => $items,
      "height" => $height,
      "tooltip_append" => $tooltip_append,
      "labels" => $labels
    );

    $output_encoded = json_encode( $output );
    $stmt = bof()->db->prepare("INSERT INTO _bof_cache_db ( query_hash, params_hash, results, time_expire ) VALUES ( ?, ?, ?, ADDDATE( now(), INTERVAL 5 MINUTE ) ) ");
    $stmt->bind_param( "sss", $query_hash, $params_hash, $output_encoded );
    $stmt->execute();
    $stmt->close();

    return $output;

  }
  protected function parse_graph_xy_query( $graphType, $query, $range ){

    $items = [];

    $get_items = bof()->db->_query( array(
      "table" => $query["table"],
      "action" => "select",
      "query" => $query["string"],
    ) );

    if ( $get_items ){
      foreach( $get_items as $item ){
        if ( $graphType=="stacked" ){
          $items[ $item["_day"] ][ $item["_type"] ] = $item["_val"];
          $_types[] = $item["_type"];
        }
        else
        $items[ $item["_day"] ] = $item["_val"];
      }
    }

    for ( $_d=0; $_d<$range["days"]; $_d++ ){
      $_d_t = date( "Y-m-d", strtotime( "+{$_d} day", strtotime( $range["start"] ) ) );
      if ( $graphType=="stacked" && !empty( $_types ) ){
        foreach( $_types as $_type ){
          if ( empty( $items[ $_d_t ][ $_type ] ) )
          $items[ $_d_t ][ $_type ] = null;
        }
      }
      elseif ( $graphType!="stacked" )
      if ( empty( $items[ $_d_t ] ) ) $items[ $_d_t ] = 0;
    }

    ksort( $items );

    foreach( $items as $k => $v ){
      $new_items[ $graphType=="stacked"?$k:strtotime( $k )*1000 ] = $v;
    }

    return !empty( $new_items ) ? $new_items : false;

  }
  protected function rearrange_structure( $name, $structure ){

    $new_structure = [];
    $items = [];
    foreach( $structure["rows"] as $row_i => $row ){
      foreach( $row as $col_name => $col_args ){
        $new_structure[ $row_i ][ $col_name ] = array(
          "args" => $col_args,
          "items" => []
        );
        foreach( $structure["items"] as $item_name => $item_data ){
          if ( $item_data["col"] == $col_name ){

            $item_data["ID"] = $item_name;

            // if ( $structure["functions"]["exe_item"] )
            // $item_data = $structure["functions"]["exe_item"]( $name, $item_data["type"], $item_name, $item_data );

            $new_structure[ $row_i ][ $col_name ][ "items" ][ $item_name ] = $item_data;
            $items[ $item_name ] = $item_data;

          }
        }
      }
    }

    return array(
      "re" => $new_structure,
      "items" => $items
    );

  }

}

?>
