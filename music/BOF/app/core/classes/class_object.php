<?php

class bof_object {

  public $required = null;
  protected $_selector_helpers = [];

  public function __construct(){
    $this->required = (object)[];
    $this->set_selector_helpers();
  }
  protected function require_core_files(){

    $path = bof_root . "/app/core/objects/object_core_files.php";
    if ( !file_exists( $path ) )
    fall( "{$path} is missing" );

    require_once( $path );
    $this->required->core_files = new bofProxy( "object_core_files" );
    return $this->required->core_files;

  }
  public function __get( $name ){

    if ( !empty( $this->required->$name ) ){
      return $this->required->$name;
    }

    if ( $name == "core_files" )
    return $this->require_core_files();

    if ( !( $path = $this->core_files->validate_key( "object", $name ) ) )
    fall("object {$name} is undefined");

    if ( !file_exists( $path ) )
    fall("object {$name} file: {$path} is missing");

    require_once( realpath( $path ) );

    $extender = bof()->get_extender( "object", $name );
    if ( $extender ){

      if ( !file_exists( $extender["path"] ) )
      fall("object {$extender["name"]} file: {$extender["path"]} is missing");

      require_once( realpath( $extender["path"] ) );

      $_func = new bofProxy( $extender["name"] );
      $this->required->$name = $_func;

      if ( $_func->method_exists( "__setup" ) )
      $this->required->$name->__setup();

      return $_func;

    }

    $_n = "object_{$name}";
    $_func = new bofProxy( $_n );
    $this->required->$name = $_func;

    if ( $_func->method_exists( "__setup" ) )
    $this->required->$name->__setup();

    return $_func;

  }
  public function __exists( $name ){
    return !empty( $this->required->$name );
  }

  public function set_selector_helpers(){

    $this->_selector_helpers["timestamp_range"] = function( $val, $column_name, $args=[] ){

      if ( !bof()->nest->validate( $val, "timestamp_range" ) )
      return;

      list( $time_range_start, $time_range_end ) = explode( " - ", $val );
      $time_range_start = strtotime( $time_range_start );
      $time_range_end = strtotime( $time_range_end );
      $whereArgs["oper"] = "AND";
      $whereArgs["cond"][] = [ $column_name, ">", bof()->general->mysql_timestamp( $time_range_start ) ];
      $whereArgs["cond"][] = [ $column_name, "<", bof()->general->mysql_timestamp( $time_range_end ) ];
      return $whereArgs;

    };
    $this->_selector_helpers["timestamp_dyna_range"] = function( $val, $column_name, $args=[] ){

      $val = intval( $val );

      if ( !bof()->general->numeric( $val ) )
      return;

      $pos = $val > 0;
      $a_val = abs( $val );

      if ( $val == 0 )
      return [ $column_name, null, null, true ];

      if ( $pos )
      return [ $column_name, ">", "SUBDATE( now(), INTERVAL {$a_val} DAY)", true ];

      return array(
        "oper" => "OR",
        "cond" => array(
          [ $column_name, null, null, true ],
          [ $column_name, "<", "SUBDATE( now(), INTERVAL {$a_val} DAY)", true ]
        )
      );

    };
    $this->_selector_helpers["related_to_parent"] = function( $val, $column_name, $args=[] ){

      $caller = null;
      $data = null;
      extract( $args );

      if ( empty( $data["rel_parent"] ) )
      return;

      $rel_parent_data = bof()->object->__get( $data["rel_parent"] )->direct()->bof();
      $hub_type_query = is_array( $data["hub_type"] ) ? "type IN ( '". implode( "','", $data["hub_type"] ) ."' )" : "type = '{$data["hub_type"]}'";

      if ( bof()->general->numeric( $val ) )
      return array(
        "ID",
        "IN",
        "SELECT target_id FROM {$rel_parent_data["db_rel_table_name"]} WHERE {$rel_parent_data["db_rel_table_col_name"]} = {$val} AND {$hub_type_query}",
        true
      );

      $val = is_array( $val ) ? $val : explode( ",", $val );
      foreach( $val as $__gid ){
        if ( $__gid ? bof()->general->numeric( $__gid ) : false )
        $valid_vals[] = $__gid;
      }

      if ( empty( $valid_vals ) )
      return;

      $valid_vals_imploded = implode( ", ", $valid_vals );
      return array(
        "ID",
        "IN",
        "SELECT target_id FROM {$rel_parent_data["db_rel_table_name"]} WHERE {$rel_parent_data["db_rel_table_col_name"]} IN ( {$valid_vals_imploded} ) AND {$hub_type_query} ",
        true
      );

      return;

    };
    $this->_selector_helpers["parent_with_relations"] = function ( $val, $column_name, $args ){

      $caller = null;
      $data = null;
      extract( $args );

      if ( empty( $data["rel_parent"] ) )
      return;

      $rel_parent_data = bof()->object->__get( $data["rel_parent"] )->direct()->bof();
      $hub_type_query = is_array( $data["hub_type"] ) ? "type IN ( '". implode( "','", $data["hub_type"] ) ."' )" : "type = '{$data["hub_type"]}'";

      $val = is_array( $val ) ? $val : explode( ",", $val );
      foreach( $val as $__gid ){
        if ( $__gid ? bof()->general->numeric( $__gid ) : false )
        $valid_vals[] = $__gid;
      }

      if ( empty( $valid_vals ) )
      return;

      $valid_vals_imploded = implode( ", ", $valid_vals );
      return array(
        "ID",
        "IN",
        "SELECT {$rel_parent_data["db_rel_table_col_name"]} FROM {$rel_parent_data["db_rel_table_name"]} WHERE target_id IN ( {$valid_vals_imploded} ) AND {$hub_type_query}",
        true
      );

    };
    $this->_selector_helpers["by_column"] = function( $val, $column_name, $args=[] ){

      $force_array = !empty( $args["data"][2]["force_array"] );

      if ( $force_array && !is_array( $val ) )
      $val = explode( ",", $val );
      elseif ( !is_array( $val ) ? preg_match( "/,/", $val ) && is_string( $val ) : false )
      $val = explode( ",", $val );

      $val_type = is_array( $val ) ? "array" : "single";

      if ( $val_type == "array" ){
        foreach( $val as $__gid ){
          if ( $__gid ? bof()->general->numeric( $__gid ) || is_string( $__gid ) : false )
          $processed_val[] = is_string( $__gid ) ? "'{$__gid}'" : $__gid;
        }
      }
      elseif ( $val ? bof()->general->numeric( $val ) || is_string( $val ) : false ) {
        $processed_val = !bof()->general->numeric( $val ) ? "'{$val}'" : $val;
      }

      if ( empty( $processed_val ) )
      return;

      return array(
        $column_name,
        $val_type == "array" ? "IN" : "=",
        $processed_val,
        $val_type == "array"
      );

    };

  }
  public function selector_helper( $type, $val, $column_name, $args=[] ){

    if ( !in_array( $type, array_keys( $this->_selector_helpers ), true ) )
    return;

    return $this->_selector_helpers[ $type ]( $val, $column_name, $args );

  }

  public function parse_caller( $caller_raw ){

    if ( is_string( $caller_raw ) ){
      $proxied = bof()->object->__get( $caller_raw );
      $direct = $proxied->direct();
    }
    elseif ( !is_object( $caller_raw ) ){
      fall("Invalid object caller" . json_encode( $caller_raw ) );
    }
    elseif ( !empty( $caller_raw->name ) && !empty( $caller_raw->proxied ) && !empty( $caller_raw->direct) && !empty( $caller_raw->parsed ) ){
      return $caller_raw;
    }
    elseif ( get_class( $caller_raw ) == "bofProxy" ) {
      $proxied = $caller_raw;
      $direct = $caller_raw->direct();
    }
    else {
      $proxied = bof()->object->__get( $caller_raw->bof()["name"] );
      $direct = $caller_raw;
    }

    if ( !method_exists( $direct, "bof" ) )
    fall( "Invalid object class: 1: " . get_class( $direct ) );

    $caller = (object) array(
      "name"    => $direct->bof()["name"],
      "direct"  => $direct,
      "proxied" => $proxied,
      "parsed"  => null
    );

    $bof_data  = $direct->bof();
    $columns   = bof()->object->parse_caller_columns( $caller );
    $selectors = bof()->object->parse_caller_selectors( $caller, $columns );
    list( $relations, $selectors ) = bof()->object->parse_caller_relations( $caller, $columns, $selectors );

    $primary_columns = !empty( $bof_data["db_primary_column"] ) ? $bof_data["db_primary_column"] : "ID";
    if ( !is_array( $primary_columns ) ) $primary_columns = [ $primary_columns ];

    $parsed_data = (object) array(
      "name" => $bof_data["name"],
      "table_name" => $bof_data["db_table_name"],
      "primary_columns" => $primary_columns,
      "columns" => $columns,
      "selectors" => $selectors,
      "relations" => $relations,
      "empty_select" => !empty( $bof_data["db_empty_select"] )
    );

    $caller->parsed = $parsed_data;

    return $caller;

  }
  public function parse_caller_columns( $caller ){

    $columns = $caller->proxied->columns();

    if ( $caller->proxied->method_exists( "bof_columns" ) )
    $bof_columns = $caller->proxied->bof_columns();

    if ( !empty( $bof_columns ) ){
      foreach( $bof_columns as $i => $b ){
        $bof_column_name = is_int( $i ) ? $b : $i;
        $bof_column_args = is_int( $i ) ? [] : $b;
        $parse_bof_column = bof()->object->parse_caller_column_bof( $caller, $bof_column_name, $bof_column_args );
        if ( $parse_bof_column ) $columns = array_merge( $columns, $parse_bof_column );
      }
    }

    if ( $caller->proxied->method_exists( "stats_columns" ) )
    $stats_columns = $caller->proxied->stats_columns();

    if ( !empty( $stats_columns) ){
      foreach( $stats_columns as $i => $b ){
        $stat_column_name = is_int( $i ) ? $b : $i;
        $stat_column_args = is_int( $i ) ? [] : $b;
        $parse_stat_column = bof()->object->parse_caller_column_stat( $caller, $stat_column_name, $stat_column_args );
        if ( $parse_stat_column ) $columns = array_merge( $columns, $parse_stat_column );
      }
    }

    foreach( $columns as $column_name => &$column_args )
    $column_args = bof()->object->parse_caller_column( $caller, $column_name, $column_args );

    return $columns;

  }
  public function parse_caller_column_stat( $caller, $name, $args=[] ){

    $_common_columns_labels = array(
      "views" => "page views",
      "views_unique" => "page views ( unique )",
      "plays" => "stream count",
      "plays_unique" => "stream count ( unique )",
      "likes" => "likes",
      "sales" => "sales",
      "subscribers" => "subscribers",
      "popularity" => "popularity",
      "albums" => "albums",
      "tracks" => "tracks",
      "artists" => "artists",
      "shows" => "shows",
      "creators" => "creators",
      "episodes" => "episodes",
      "stations" => "stations",
      "regions" => "regions",
      "books" => "books",
      "cities" => "cities",
      "countries" => "countries",
      "muse_report" => "System report ( media failure )",
      "shares" => "shares"
    );

    $label = null;
    extract( $args );

    if ( !$label ){
      if ( empty( $_common_columns_labels[ $name ] ) )
      fall( "Stat column has no title {$name}" );
      $label = $_common_columns_labels[ $name ];
    }

    return array(
      "s_{$name}" => array(
        "public" => true,
        "label" => ucwords( "Stat: " . $label ),
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      )
    );

  }
  public function parse_caller_column_bof( $caller, $name, $args=[] ){

    $column = null;
    $columns = null;

    switch( $name ){
      case "time_add" :
        $column = array(
          "public" => true,
          "label" => "Creation time",
          "bofAdmin" => array(
            "sortable" => true,
            ( !empty( $args["no_object"] ) ? "no_object" : "object" ) => array(
              "multi" => true
            ),
            "filters" => array(
              "time_add_range" => array(
                "title" => "Creation time range",
                "input" => array(
                  "type" => "time_range"
                ),
                "validator" => array(
                  "string",
                  array(
                    "strict" => true,
                    "strict_regex" => "/^(19|20)([0-9]{2})\-(0|1)([0-9]{1})\-(0|1|2|3)([0-9]{1}) (0|1|2)([0-9]{1}):([0-6]{1})([0-9]{1}) \- (19|20)([0-9]{2})\-(0|1)([0-9]{1})\-(0|1|2|3)([0-9]{1}) (0|1|2)([0-9]{1}):([0-6]{1})([0-9]{1})$/",
                    "strict_regex_raw" => true,
                    "empty()"
                  )
                )
              ),
            )
          ),
          "input" => array(
            "type" => "time"
          ),
          "validator" => array(
            "timestamp",
            array(
              "empty()"
            )
          ),
          "selectors" => array(
            "time_add_range" => [ "time_add", "timestamp_range" ]
          )
        );
        break;
      case "ID" :
        $column = array(
          "label" => "ID",
          "validator" => array(
            "int",
            array(
              "empty()"
            )
          ),
          "bofAdmin" => array(
            "sortable" => true,
          ),
          "selectors" => array(
            "ID" => [ "ID", "=" ],
            "ID_in" => function( $val ){

              $validate_vals = [];
              foreach( is_array( $val ) ? $val : explode( ",", $val ) as $_a ){
                if ( bof()->general->numeric( $_a ) )
                $validate_vals[] = $_a;
              }

              if ( empty( $validate_vals ) )
              return;

              return array(
                "ID",
                "IN",
                implode( ",", $validate_vals ),
                true
              );

            },
          ),
        );
        break;
      case "hash" :
        $column = array(
          "public" => true,
          "validator" => "md5",
          "selectors" => array(
            "hash" => [ "hash", "=" ],
          ),
          "bofAdmin_validator" => array(
            "make_hash" => true
          )
        );
        break;
      case "code" :
        $column = array(
          "public" => true,
          "validator" => "string_code",
          "bofAdmin_validator" => array(
            "code_args" => $args
          ),
          "selectors" => array(
            "code" => [ "code", "=" ],
          ),
        );
        break;
      case "seo" :
        $columns["seo_url"] = array(
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
            ),
          ),
          "selectors" => array(
            "url" => [ "seo_url", "=" ],
            "seo_url" => [ "seo_url", "=" ],
          ),
        );
        $columns["seo_image"] = array(
          "label" => "SEO image",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "seo_image"
            ),
          ),
        );
        $columns["seo_data"] = array(
          "validator" => array(
            "json",
            array(
              "empty()",
              "encode" => true
            )
          ),
        );
        break;
      case "social_links" :
        $columns["external_addresses"] = array(
          "public" => true,
          "validator" => array(
            "json",
            array(
              "empty()",
              "encode" => true
            )
          ),
        );
        break;
      case "biography" :
        $columns["bio_name"] = array(
          "public" => true,
          "label" => "Real name",
          "tip" => "An artist alter-ego's name might differ from their real name. In that case, enter their real name here",
          "validator" => array(
            "string",
            array(
              "empty()",
              "strip_emoji" => false,
            ),
          ),
          "input" => array(
            "type" => "text",
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "bio"
            ),
          ),
        );
        $columns["bio_country"] = array(
          "public" => true,
          "label" => "Country",
          "tip" => "Origin country of artist",
          "validator" => array(
            "string",
            array(
              "empty()",
            ),
          ),
          "input" => array(
            "type" => "text",
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "bio"
            ),
          ),
        );
        $columns["bio_city"] = array(
          "public" => true,
          "label" => "City",
          "tip" => "Origin city of artist",
          "validator" => array(
            "string",
            array(
              "empty()",
            ),
          ),
          "input" => array(
            "type" => "text",
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "bio"
            ),
          ),
        );
        $columns["bio_birthday"] = array(
          "public" => true,
          "label" => "Birthday",
          "validator" => array(
            "timestamp",
            array(
              "empty()",
            ),
          ),
          "input" => array(
            "type" => "time",
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "bio"
            ),
          ),
        );
        $columns["bio_deathday"] = array(
          "public" => true,
          "label" => "Deathday",
          "validator" => array(
            "timestamp",
            array(
              "empty()",
            ),
          ),
          "input" => array(
            "type" => "time",
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "bio"
            ),
          ),
        );
        $columns["bio_content"] = array(
          "public" => true,
          "label" => "Biography / Details",
          "validator" => array(
            "editor_js",
            array(
              "empty()",
            ),
          ),
          "input" => array(
            "type" => "text_editor",
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "bio"
            ),
          ),
        );
        break;
      case "translations" :
        $columns["translations"] = array(
          "public" => true,
          "validator" => array(
            "json",
            array(
              "empty()",
              "encode" => true
            )
          ),
        );
        break;
      case "cover" :
        $columns["cover_id"] = array(
          "public" => true,
          "label" => "Cover",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => $caller->name . "_c"
            )
          ),
          "selectors" => array(
            "has_cover" => function( $val ){

              if ( $val > 0 )
              return [ "cover_id", ">", "0" ];

              return array(
                "oper" => "OR",
                "cond" => array(
                  [ "cover_id", "=", "0" ],
                  [ "cover_id", null, null, true ]
                )
              );

            },
          ),
          "bofAdmin" => array(
            "object" => array(
              "multi" => true
            ),
            "filters" => array(
              "has_cover" => array(
                "title" => "Has cover",
                "input" => array(
                  "type" => "select_i",
                  "options" => array(
                    [ -1, "no" ],
                    [ 1, "yes" ],
                    [ "__all__", "all" ]
                  ),
                  "value" => "__all__"
                ),
                "validator" => array(
                  "in_array",
                  [ "values" => [ "-1", "1", "__all__" ] ]
                )
              ),
            ),
          ),
        );
        if ( empty( $args["no_bg"] ) ){
          $columns["bg_id"] = array(
            "public" => true,
            "label" => "Background",
            "bofInput" => array(
              "file",
              array(
                "type" => "image",
                "object_type" => $caller->name . "_bg"
              )
            ),
            "selectors" => array(
              "has_bg" => function( $val ){

                if ( $val > 0 )
                return [ "bg_id", ">", "0" ];

                return array(
                  "oper" => "OR",
                  "cond" => array(
                    [ "bg_id", "=", "0" ],
                    [ "bg_id", null, null, true ]
                  )
                );

              },
            ),
            "bofAdmin" => array(
              "object" => array(),
              "filters" => array(
                "has_bg" => array(
                  "title" => "Has background",
                  "input" => array(
                    "type" => "select_i",
                    "options" => array(
                      [ -1, "no" ],
                      [ 1, "yes" ],
                      [ "__all__", "all" ]
                    ),
                    "value" => "__all__"
                  ),
                  "validator" => array(
                    "in_array",
                    [ "values" => [ "-1", "1", "__all__" ] ]
                  )
                ),
              ),
            ),
          );
        }
        break;
      case "price" :
        $columns["price"] = array(
          "public" => true,
          "label" => "Price",
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0,
              "forceZero" => true
            ),
          ),
          "input" => array(
            "type" => "digit"
          ),
          "selectors" => array(
            "has_price" => function( $val ){

              if ( $val > 0 )
              return [ "price", ">", "0" ];

              return array(
                "oper" => "OR",
                "cond" => array(
                  [ "price", "=", "0" ],
                  [ "price", null, null, true ],
                )
              );

            },
          ),
          "bofAdmin" => array(
            "sortable" => true,
            "list" => array(
              "type" => "tag",
              "renderer" => function( $displayItem, $item, $displayData ){
                if ( !$item["price"] )
                $displayData["data"] = "free";
                else
                $displayData["data"] = "\${$item["price"]}";
                return $displayData;
              },
            ),
            "object" => array(
              "group" => "price",
              "multi" => true
            ),
            "filters" => array(
              "has_price" => array(
                "title" => "Has Price",
                "input" => array(
                  "type" => "select_i",
                  "options" => array(
                    [ -1, "no" ],
                    [ 1, "yes" ],
                    [ "__all__", "all" ]
                  ),
                  "value" => "__all__"
                ),
                "validator" => array(
                  "in_array",
                  [ "values" => [ "-1", "1", "__all__" ] ]
                )
              ),
            )
          ),
          "bofClient" => array(
            "filters" => array(
              "has_price" => array(
                "title" => "Price",
                "input" => array(
                  "type" => "select_i",
                  "options" => array(
                    [ "__all__", "all" ],
                    [ -1, "Free" ],
                    [ 1, "Priced" ],
                  ),
                  "value" => "__all__"
                ),
                "validator" => array(
                  "in_array",
                  [ "values" => [ "-1", "1", "__all__" ] ]
                )
              ),
            )
          ),
        );
        $columns["price_setting"] = array(
          "public" => true,
          "validator" => array(
            "json",
            array(
              "empty()",
              "encode" => true
            ),
          ),
        );
        break;
    }

    if ( $columns ) return $columns;
    if ( !$column ) return null;
    return array( $name => $column );

  }
  public function parse_caller_column( $caller, $name, $args=[] ){

    if ( !empty( $args["validator"] ) ? !is_array( $args["validator"] ) : false ){
      $args["validator"] = array(
        $args["validator"],
        []
      );
    }
    if ( empty( $args["validator"] ) ? !empty( $args["bofInput"] ) : false ){
      if ( $args["bofInput"][0] == "file" ){
        $args["validator"] = array(
          "int",
          array(
            "min" => 0,
            "empty()"
          )
        );
      } else {

        if ( empty( $args["bofInput"][1]["multi"] ) ){
          $args["validator"] = array(
            "int",
            array(
              "empty()",
              "min" => 0
            )
          );
        } else {
          $args["validator"] = array(
            "string",
            array(
              "strict" => true,
              "strict_regex" => "[0-9,]",
              "empty()",
            )
          );
        }

      }
    }
    return $args;

  }
  public function parse_caller_selectors( $caller, $parsed_columns ){

    $selectors = [];
    if ( $caller->proxied->method_exists( "selectors" ) )
    $selectors = $caller->proxied->selectors();

    foreach( $parsed_columns as $column_name => $column_args ){
      if ( empty( $column_args["selectors"] ) ) continue;
      $selectors = array_merge( $column_args["selectors"], $selectors );
    }

    return $selectors;

  }
  public function parse_caller_relations( $caller, $parsed_columns, $selectors ){

    $relations = [];
    if ( $caller->proxied->method_exists( "relations" ) )
    $relations = $caller->proxied->relations();

    foreach( $parsed_columns as $column_name => $column_args ){

      if ( !empty( $column_args["relations"] ) )
      $relations = array_merge( $column_args["relations"], $relations );

    }

    if ( $relations ){
      foreach( $relations as $relation ){
        if ( !empty( $relation["selectors"] ) ){
          foreach( $relation["selectors"] as $_k => $_args )
          $selectors[ $_k ] = $_args;
        }
      }
    }

    return [ $relations, $selectors ];

  }

  public function exe_caller_relations( $caller, $action, $whereArgs, $args=[] ){

    $relations = $caller->parsed->relations;

    if ( !$relations )
    return;

    $old_items = null;
    $new_items = null;
    extract( $args );

    if ( $action != "delete" ){
      $new_items = $caller->proxied->select(
        $whereArgs,
        array(
          "single" => false,
          "limit" => false,
          "cache_load_rt" => false
        )
      );
    }

    foreach( $relations as $relation )
    $this->exe_caller_relation( $caller, $action, $old_items, $new_items, $args, $relation["exec"] );

  }
  public function exe_caller_relation( $caller, $action, $old_items, $new_items, $args, $exec ){

    $type = null;
    $hub_type = null;
    $parent_object = null;
    $parent_object_selector_column = "ID";
    $parent_object_stats_column = null;
    $parent_object_list_column = null;
    $parent_object_direct_edit_column = null;
    $parent_object_custom_columns = null;
    $child_object = null;
    $child_object_selector_column = null;
    $child_object_where_array = [];
    $child_object_stats_column = null;
    $delete_child_too = false;
    $KillOldsOnChildUpdate = false;
    extract( $exec );

    $data = null;
    extract( $args );

    $effected_parents = $effected_childs = array(
      "all" => [],
      "new" => [],
      "old" => [],
    );

    $original_caller = $caller->name;
    $original_caller_role = $original_caller == $parent_object ? "parent" : "child";
    $parent_column = $original_caller_role == "parent" ? $parent_object_selector_column : $child_object_selector_column;

    $parent_parsed = bof()->object->parse_caller( $parent_object );
    $child_parsed = bof()->object->parse_caller( $child_object );

    // Old items
    if ( $old_items ){
      foreach( $old_items as $old_item ){

        if ( $original_caller_role == "child" ){
          $effected_childs["all"][] = $old_item["ID"];
          $effected_childs["old"][] = $old_item["ID"];
        }

        if ( !empty( $old_item[ $parent_column ] ) ){
          foreach( explode( ",", $old_item[ $parent_column ] ) as $_opid ){
            $effected_parents["all"][] = $_opid;
            $effected_parents["old"][] = $_opid;
          }
        }

      }
    }

    // New items
    if ( $new_items ){
      foreach( $new_items as $new_item ){

        if ( $original_caller_role == "child" ){
          $effected_childs["all"][] = $new_item["ID"];
          $effected_childs["new"][] = $new_item["ID"];
        }

        if ( !empty( $new_item[ $parent_column ] ) ){
          foreach( explode( ",", $new_item[ $parent_column ] ) as $_npid ){
            $effected_parents["all"][] = $_npid;
            $effected_parents["new"][] = $_npid;
          }
        }

      }
    }

    $effected_parents["all"] = array_unique( $effected_parents["all"] );
    $effected_parents["new"] = array_unique( $effected_parents["new"] );
    $effected_parents["old"] = array_unique( $effected_parents["old"] );
    $effected_childs["all"] = array_unique( $effected_childs["all"] );
    $effected_childs["new"] = array_unique( $effected_childs["new"] );
    $effected_childs["old"] = array_unique( $effected_childs["old"] );

    // Actions
    if ( $type == "direct" ){

      if ( $original_caller_role == "parent" && $action == "delete" && $delete_child_too && !empty( $effected_parents["old"] ) ){
        foreach( $effected_parents["old"] as $deleted_parent_selector ){
          bof()->object->__get( $child_object )->delete(
            array_merge(
              array(
                $child_object_selector_column => $deleted_parent_selector
              ),
              $child_object_where_array
            ),
            $child_object != $parent_object
          );
        }
      }
      if ( $parent_object_stats_column && !empty( $effected_parents["all"] ) ){
        foreach( $effected_parents["all"] as $effected_parent_selector ){

          $count = bof()->object->__get( $child_object )->count(
            array_merge(
              array(
                $child_object_selector_column => $effected_parent_selector
              ),
              $child_object_where_array
            ),
            array(
              "cache" => false
            )
          );

          bof()->object->__get( $parent_object )->update(
            array(
              $parent_object_selector_column => $effected_parent_selector
            ),
            array(
              $parent_object_stats_column => $count
            ),
            false
          );

        }
      }
      if ( $original_caller_role == "parent" && in_array( $action, [ "insert", "update" ], true ) && $parent_object_direct_edit_column ? in_array( $parent_object_direct_edit_column, array_keys( $data ), true ) : false  ){

        $_ids = $data[ $parent_object_direct_edit_column ];

        foreach( $effected_parents["all"] as $_p_id ){

          $child_parsed->proxied->update(
            array(
              $child_object_selector_column => $_p_id
            ),
            array(
              $child_object_selector_column => 0
            )
          );

          if ( $_ids ){
            foreach( explode( ",", $_ids ) as $_id ){
              $child_parsed->proxied->update(
                array(
                  "ID" => $_id
                ),
                array(
                  $child_object_selector_column => $_p_id
                )
              );
            }
          }

        }

      }

    }
    elseif ( $type == "hub" ){

      foreach( is_array( $hub_type ) ? $hub_type : [ $hub_type ] as $a_hub_type ){

        $delete_rels = [];

        if ( in_array( $action, [ "insert", "update" ], true ) && isset( $data[ $a_hub_type . "_ids" ] ) && $original_caller_role == "parent" ){

          if ( empty( $data[ $a_hub_type . "_ids" ] ) ){

            foreach( $effected_parents["new"] as $_nepid ){
              $delete_rels[] = bof()->object->_delete_rels( $parent_object, $_nepid, false, $a_hub_type );
            }

          }
          else {

            $_new_ids = is_string( $data[ $a_hub_type . "_ids" ] ) ? explode( ",", $data[ $a_hub_type . "_ids" ] ) : $data[ $a_hub_type . "_ids" ];
            foreach( $effected_parents["new"] as $_nepid ){
              $_make_rel = bof()->object->_make_rels( $parent_object, $_nepid, $_new_ids, $a_hub_type );
              if ( !empty( $_make_rel["new"] ) ){
                foreach( $_make_rel["new"] as $_new_child_id ){
                  $effected_childs["all"][] = $_new_child_id;
                  $effected_childs["new"][] = $_new_child_id;
                }
              }
              if ( !empty( $_make_rel["removed"] ) ){
                foreach( $_make_rel["removed"] as $_old_child_id ){
                  $effected_childs["all"][] = $_old_child_id;
                  $effected_childs["old"][] = $_old_child_id;
                }
              }
            }

          }

        }
        if ( in_array( $action, [ "insert", "update" ], true ) && isset( $data[ $child_object_selector_column ] ) && $original_caller_role == "child" ){

          if ( empty( $data[ $child_object_selector_column ] ) ){

            foreach( $effected_parents["new"] as $_nepid ){
              $delete_rels[] = bof()->object->_delete_rels( $parent_object, $_nepid, false, $a_hub_type );
            }

          }
          else {

            $_new_ids = is_string( $data[ $child_object_selector_column ] ) ? explode( ",", $data[ $child_object_selector_column ] ) : $data[ $child_object_selector_column ];

            foreach( $_new_ids as $_nepid ){

              $_make_rel = bof()->object->_make_rels( $parent_object, $_nepid, $effected_childs["new"], $a_hub_type, $KillOldsOnChildUpdate );

              if ( !empty( $_make_rel["new"] ) ){
                foreach( $_make_rel["new"] as $_new_child_id ){
                  $effected_childs["all"][] = $_new_child_id;
                  $effected_childs["new"][] = $_new_child_id;
                }
              }

            }

          }

        }


        if ( $action == "delete" && $original_caller_role == "parent" ){
          foreach( $effected_parents["old"] as $_oepid ){
            $delete_rels[] = bof()->object->_delete_rels( $parent_object, $_oepid, false, $a_hub_type );
          }
        }
        if ( $action == "delete" && $original_caller_role == "child" ){
          foreach( $effected_childs["old"] as $_oeiid ){
            $delete_rels[] = bof()->object->_delete_rels( $parent_object, false, $_oeiid, $a_hub_type );
          }
        }
        if ( !empty( $delete_rels ) ){
          foreach( $delete_rels as $delete_rel ){

            if ( !empty( $delete_rel["parents"] ) ){
              foreach( $delete_rel["parents"] as $deleted_parent ){
                $effected_parents["all"][] = $deleted_parent;
                $effected_parents["old"][] = $deleted_parent;
              }
            }

            if ( !empty( $delete_rel["childs"] ) ){
              foreach( $delete_rel["childs"] as $deleted_child ){
                $effected_childs["all"][] = $deleted_child;
                $effected_childs["old"][] = $deleted_child;
              }
            }

          }
        }

      }

      $effected_parents["all"] = array_unique( $effected_parents["all"] );
      $effected_parents["new"] = array_unique( $effected_parents["new"] );
      $effected_parents["old"] = array_unique( $effected_parents["old"] );
      $effected_childs["all"] = array_unique( $effected_childs["all"] );
      $effected_childs["new"] = array_unique( $effected_childs["new"] );
      $effected_childs["old"] = array_unique( $effected_childs["old"] );

      if ( $child_object_stats_column ){

        foreach( $effected_childs["all"] as $_c_id ){
          $get_count_rels = bof()->db->_select(array(
            "table" => $parent_parsed->direct->bof()["db_rel_table_name"],
            "where" => array(
              [ "type", is_array( $hub_type ) ? "IN" : "=", is_array( $hub_type ) ? "'" . implode( "','", $hub_type ) . "'" : $hub_type, is_array( $hub_type ) ],
              [ "target_id", "=", $_c_id ]
            ),
            "columns" => "COUNT(*) as count",
            "cache_load_rt" => false
          ));
          $count_rels = !empty( $get_count_rels[0]["count"] ) ? $get_count_rels[0]["count"] : 0;
          $child_parsed->proxied->update(
            array(
              "ID" => $_c_id
            ),
            array(
              $child_object_stats_column => $count_rels
            ),
            false
          );
        }

      }
      if ( $parent_object_stats_column ){

        foreach( $effected_parents["all"] as $_p_id ){

          $get_count_rels = bof()->db->_select(array(
            "table" => $parent_parsed->direct->bof()["db_rel_table_name"],
            "where" => array(
              [ "type", is_array( $hub_type ) ? "IN" : "=", is_array( $hub_type ) ? "'" . implode( "','", $hub_type ) . "'" : $hub_type, is_array( $hub_type ) ],
              [ $parent_parsed->direct->bof()["db_rel_table_col_name"], "=", $_p_id ]
            ),
            "columns" => "COUNT(*) as count",
            "cache_load_rt" => false
          ));

          $count_rels = !empty( $get_count_rels[0]["count"] ) ? $get_count_rels[0]["count"] : 0;

          $parent_parsed->proxied->update(
            array(
              "ID" => $_p_id
            ),
            array(
              $parent_object_stats_column => $count_rels
            ),
            false
          );

        }

      }

    }

    if ( $parent_object_custom_columns ){
      $parent_object_custom_columns( $effected_parents, $effected_childs );
    }

  }

  public function parse_caller_where( $caller, $args ){

    if ( !$args )
    return false;

    $_ws = [];
    foreach( $args as $arg_k => $arg ){

      $selectors = $caller->parsed->selectors;
      $selector_data = !empty( $selectors[ $arg_k ] ) ? $selectors[ $arg_k ] : false;

      if ( empty( $selector_data ) && !is_array( $_ws ) )
      fall( "Invalid object class: 2: " . $caller->name . ": " . $arg_k );

      if ( empty( $selector_data ) ){
        if ( in_array( $arg_k, array( "oper", "cond" ), true ) )
        $_ws[ $arg_k ] = $arg;
        else
        $_ws[] = $arg;
        continue;
      }

      if ( is_object( $selector_data ) ){

        $_r = $selector_data( $arg );
        if ( $_r ) $_ws[] = $_r;

      }
      elseif( is_array( $selector_data ) ? in_array( $selector_data[1], array_keys( $this->_selector_helpers ), true ) : false ){

        $run_selector_helper = $this->selector_helper( $selector_data[1], $arg, $selector_data[0], array(
          "caller" => $caller,
          "data" => $selector_data
        ) );
        if ( $run_selector_helper ) $_ws[] = $run_selector_helper;

      }
      elseif( is_array( $selector_data ) ){

        $_ws[] = array(
          $selector_data[0],
          $selector_data[1],
          !empty( $selector_data[3] ) ? $selector_data[3] : $arg,
          !empty( $selector_data[2] ) ? true : false
        );

      }
      else
      fall( "Invalid object class: 3: " . $caller->name . ": " . $arg_k );

    }

    return $_ws;

  }
  public function parse_caller_set( $caller, $args, $update=false ){

    if ( !$args )
    return false;

    $columns = $caller->parsed->columns;
    $_ss = [];

    foreach( $columns as $_key => $_args ){
      if ( $update && !in_array( $_key, array_keys( $args ) ) ) continue;

      $_val = isset( $args[ $_key ] ) ? $args[ $_key ] : null;
      if ( !isset( $_val ) && in_array( "empty()", !empty( $_args["validator"][1] ) ? $_args["validator"][1] : [] ) ) continue;

      if ( !empty( $_args["validator"][0] ) ? $_args["validator"][0] == "json" && is_array( $_val ) : false )
      $_val = json_encode( $_val );

      $validate = bof()->nest->validate(
        $_val,
        $_args["validator"][0],
        !empty( $_args["validator"][1] ) ? $_args["validator"][1] : []
      );

      if ( !$validate ){
        return json_encode( "{$_key}:" . ( is_array( $_val ) ? "ARRAY" : $_val ) );
      }

      $_ss[] = [ $_key, $_val ];

    }

    return $_ss;

  }
  public function parse_caller_result_key( $caller, $args ){

    $keys = [];

    foreach( $caller->parsed->primary_keys as $__key ){
      $keys[] = $args[ $__key ];
    }

    return implode( "_", $keys );

  }

  public function _sid( $caller_raw, $ID, $selectArgs=[] ){
    return bof()->object->_select( $caller_raw, [ "ID" => $ID ], $selectArgs );
  }
  public function _shash( $caller_raw, $hash, $selectArgs=[] ){
    return bof()->object->_select( $caller_raw, [ "hash" => $hash ], $selectArgs );
  }
  public function _select_m( $caller_raw, $whereArgs, $selectArgs=[] ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $whereArgsParsed = [];
    while( count( $whereArgs ) > 0 ){
      $oneWhereArg = array_splice( $whereArgs, 0, 1 );
      if ( $oneWhereArg == array( "and()" ) ) continue;
      $whereArgsParsed = array_merge( $whereArgsParsed, bof()->object->parse_caller_where( $caller, $oneWhereArg ) );
    }

    return $this->_select( $caller, array(
      "oper" => "or",
      "cond" => $whereArgsParsed
    ), $selectArgs, true );

  }
  public function _select( $caller_raw, $whereArgs, $selectArgs=[], $fromMulti=false ){

    $caller = bof()->object->parse_caller( $caller_raw );
    $caller_name = $caller->name;
    $caller_parsed = $caller->parsed;

    if ( in_array( "ignore_blacklist()", $whereArgs, true ) ){
      unset( $whereArgs[ array_search( "ignore_blacklist()", $whereArgs ) ] );
      $ignore_blacklist = true;
    }

    $whereArgs_parsed = $fromMulti ? $whereArgs : bof()->object->parse_caller_where( $caller, $whereArgs );
    $empty_select = $caller_parsed->empty_select || !empty( $selectArgs["empty_select"] );
    if ( !$whereArgs_parsed && !$empty_select )
    return false;


    $limit = 1;
    $single = true;
    $clean = true;
    $_eq = [];
    $columns = "*";
    $order = "DESC";
    $order_by = null;
    $page = null;
    $offset = null;
    $group_by = null;
    $full_search = null;

    $bof_cache = false;
    $bof_cache_seed = "rawmean007";
    $bof_cache_save = true;
    $bof_cache_load = true;
    $bof_cache_range = defined("bof_cache_range") ? bof_cache_range : "10 MINUTE";
    extract( $selectArgs );

    if ( !empty( $page ) ){
      $selectArgs["offset"] = ($page-1)*$limit;
      unset( $selectArgs["page"] );
    }

    if ( ( ( bof()->object->core_setting->get("search_index_type") == "fulltext" ) && $full_search ) ? ( !empty( $caller->direct->bof()["fulltext_search"] ) && !empty( $whereArgs["query"] ) ) : false ){

      foreach( $whereArgs_parsed as $_wa ){
        if ( empty( $_wa ) ? true : !is_array( $_wa ) ) continue;
        if ( $_wa[1] == "MATCH" ) $_waC = $_wa[0];
      }

      if ( !empty( $_waC ) ){
        $__q = $whereArgs["query"];
        bof()->nest->validate( $__q, "string" );
        $columns = "*,MATCH( `$_waC` ) AGAINST ( '\"$__q\"' ) as ___s";
        $order_by = "___s";
      }

    }

    if ( empty( $whereArgs_parsed ) && !$empty_select )
    fall( $caller->name . " doesn't allow empty selects" );

    if ( $bof_cache && $bof_cache_load ){

      $bof_cache_query_hash = md5( $caller_parsed->table_name . $limit . $bof_cache_seed );
      $bof_cache_params_hash = md5( json_encode( $whereArgs_parsed ) . json_encode( $selectArgs ) );

      $bof_cache_load_query = bof()->db->query("SELECT * FROM _bof_cache_db WHERE query_hash = '{$bof_cache_query_hash}' AND params_hash = '{$bof_cache_params_hash}' AND time_add > SUBDATE( now(), INTERVAL {$bof_cache_range} ) ORDER BY time_add DESC LIMIT 1", null, true );

      if ( $bof_cache_load_query->num_rows ){

        $bof_cache_load_query_item = $bof_cache_load_query->fetch_assoc();

        $bof_cache_load_query_result = $bof_cache_load_query_item["results"] ? json_decode( $bof_cache_load_query_item["results"], 1 ) : null;

        if ( $bof_cache_load_query_result )
        return $bof_cache_load_query_result;

      }

    }

    $items = bof()->db->_select(
      array_merge( $selectArgs, array(
        "table" => $caller_parsed->table_name,
        "limit" => $limit,
        "where" => $whereArgs_parsed,
        "group" => $group_by,
        "single" => false,
        "order" => $order,
        "order_by" => $order_by,
        "columns" => $columns
      ) )
    );

    if ( !$items ? !empty( $_waC ) : false ){
      foreach( $whereArgs_parsed as &$_wa ){
        if ( empty( $_wa ) ? true : !is_array( $_wa ) ) continue;
        if ( $_wa[1] == "MATCH" ) $_wa[1] = "MATCH%";
      }
      $items = bof()->db->_select(
        array_merge( $selectArgs, array(
          "table" => $caller_parsed->table_name,
          "limit" => $limit,
          "where" => $whereArgs_parsed,
          "group" => $group_by,
          "single" => false,
          "order" => $order,
          "order_by" => $order_by,
          "columns" => $columns
        ) )
      );
    }

    if ( !$items )
    return false;

    if ( $clean ){
      $o_items = $items;
      foreach( $items as $i => &$item ){
        $clean = $this->_clean_item( $caller, $o_items, $i, $item, $whereArgs, $selectArgs, $fromMulti );
        if ( $clean === false )
        unset( $items[ $i ] );
      }
    }

    if ( !empty( $selectArgs["select_cleaner"] ) ? is_callable( $selectArgs["select_cleaner"] ) : false ){
      $items = $selectArgs["select_cleaner"]( $items, bof() );
    }

    $select_results = $single && $limit == 1 ? reset( $items ) : $items;

    if ( $bof_cache && $bof_cache_save && $select_results ){

      if ( empty( $bof_cache_query_hash ) ){
        $bof_cache_query_hash = md5( $caller_parsed->table_name . $limit );
        $bof_cache_params_hash = md5( json_encode( $whereArgs_parsed ) . json_encode( $selectArgs ) );
      }

      try {
        $select_results_encoded = json_encode( $select_results );
        $__d = $select_results_encoded ? $select_results_encoded : null;
        $stmt = bof()->db->prepare("INSERT INTO _bof_cache_db ( query_hash, params_hash, results, time_expire ) VALUES ( ?, ?, ?, ADDDATE( now(), INTERVAL {$bof_cache_range}) ) ");
        $stmt->bind_param( "sss", $bof_cache_query_hash, $bof_cache_params_hash, $__d );
        $stmt->execute();
        $stmt->close();
      } catch( Exception|Error|bofException $err ){
      }

    }

    return $select_results;

  }
  protected function _clean_item( $caller, $original_items, $item_i, &$item, $whereArgs, $selectArgs, $fromMulti ){

    $public = !empty( $selectArgs["public"] );
    $cleaner = !empty( $selectArgs["cleaner"] ) ? $selectArgs["cleaner"] : false;
    $original_item = $item;

    // Clean column-type-based
    foreach( $caller->parsed->columns as $column_name => $column_args ){

      // Decode jsons
      if ( !empty( $column_args["validator"][0] ) ? $column_args["validator"][0] == "json" : false ){
        $item[ $column_name . "_decoded" ] = !empty( $item[ $column_name ] ) ? json_decode( $item[ $column_name ], true ) : [];
      }

      // EditorJS
      if ( !empty( $column_args["validator"][0] ) ? $column_args["validator"][0] == "editor_js" : false ){
        $valueObject = null;
        if ( $item[ $column_name ] ){
          try {
            $valueObject = json_decode( $item[ $column_name ] );
          } catch( Exception $err ){}
          if ( !$valueObject ){
            try {
              $valueObject = json_decode( htmlspecialchars_decode( $item[ $column_name ] ) );
            } catch( Exception $err ){}
          }
        }
        if ( empty( $valueObject->html ) )
        $item[ $column_name . "_html" ] = $item[ $column_name ] ? bof()->editorjs->htmlize( $valueObject ) : "";
        else
        $item[ $column_name . "_html" ] = $valueObject->html;

        $item[ $column_name . "_html_pure" ] = "";
        if ( bof()->getName() == "bof_client" )
        unset( $item[ $column_name ] );
      }

      // Time Helpers
      if ( !empty( $column_args["validator"][0] ) ? $column_args["validator"][0] == "timestamp" : false ){
        if (
          (
            bof()->getName() == "bof_admin" ?
              empty( $column_args[1]["no_bof_time"] ) && empty( $selectArgs["no_bof_time"] )
            : false
          ) ||
          (
            bof()->getName() == "bof_client" ?
              !empty( $column_args[1]["bof_time"] ) || !empty( $selectArgs["bof_time"] )
            : false
          )
        ){

          $value = !empty( $item[ $column_name ] ) ? $item[ $column_name ] : null;
          $item[ "bof_{$column_name}" ] = $value ? substr( $value, 0, strlen( "yyyy-mm-dd" ) ) : "?";
          $item[ "bof_{$column_name}_year" ] = $value ? substr( $value, 0, strlen( "yyyy" ) ) : "?";
          $item[ "bof_{$column_name}_seconds" ] = $value && is_string( $value ) ? strtotime( $value ) : null;
          $item[ "bof_{$column_name}_seconds_ago" ] = $__seconds = $value && is_string( $value ) ? time() - strtotime( $value ) : null;
          $item[ "bof_{$column_name}_hr" ] = $__seconds ? bof()->general->passed_time_hr( $__seconds, array(
            "translate" => !empty( $selectArgs["bof_time_translate"] ) || ( bof()->getName() == "bof_client" ? bof()->user->check()->language != "en" : false )
          ) )["string"] : "";

        }
      }

      // Get requested files
      $selectArgs["_eq"] = !empty( $selectArgs["_eq"] ) ? $selectArgs["_eq"] : [];
      if ( !empty( $column_args["bofInput"] ) ? $column_args["bofInput"][0] == "file" : false ){
        $_column_eq_name = substr( $column_name, 0, -3 );
        if ( in_array( $_column_eq_name, array_keys( $selectArgs["_eq"] ), true ) ){
          $item[ "bof_file_" . $_column_eq_name ] = null;
          if ( !empty( $item[ $column_name ] ) ){

            $item[ "bof_file_" . $_column_eq_name ] = bof()->object->file->select(
              array(
                "ID" => $item[ $column_name ]
              ),
              !empty( $selectArgs["_eq"][ $_column_eq_name ] ) ? $selectArgs["_eq"][ $_column_eq_name ] : []
            );

          }
        }
      }

    }

    // Get requested relations
    if ( !empty( $caller->parsed->relations ) ){
      foreach( $caller->parsed->relations as $relation_k => $relation ){

        if ( empty( $relation["exec"] ) )
        fall( "Bad relation " .$caller->parsed->name . "_" . $relation_k );

        $relation_exe = $relation["exec"];
        $caller_role = $caller->parsed->name == $relation_exe["parent_object"] ? "parent" : "child";
        $other_object = $caller_role == "parent" ? $relation_exe["child_object"] : $relation_exe["parent_object"];

        if ( !empty( $selectArgs["_eq"] ) ? in_array( $relation_k, array_keys( $selectArgs["_eq"] ), true ) : false ){

          if ( $relation_exe["type"] == "direct" ){

            $__sargs = array_merge(
              array(
                "limit" => !empty( $relation_exe["limit"] ) ? $relation_exe["limit"] : 10,
              ),
              is_array( $selectArgs["_eq"][ $relation_k ] ) ? $selectArgs["_eq"][ $relation_k ] : []
            );

            if ( !empty( $relation_exe["order_by"] ) )
            $__sargs["order_by"] = $relation_exe["order_by"];
            if ( !empty( $relation_exe["order"] ) )
            $__sargs["order"] = $relation_exe["order"];

            if ( $caller_role == "child" ){

              if ( !empty( $original_item[ $relation_exe["child_object_selector_column"] ] ) ){

                $item[ "bof_dir_{$relation_k}" ] = bof()->object->__get( $other_object )->select(
                  array(
                    "ID_in" => $original_item[ $relation_exe["child_object_selector_column"] ]
                  ),
                  $__sargs
                );

              }

            }
            else {

              $item[ "bof_dir_{$relation_k}" ] = bof()->object->__get( $other_object )->select(
                array(
                  !empty( $caller->proxied->bof()["db_rel_table_col_name"] ) ? $caller->proxied->bof()["db_rel_table_col_name"] : $relation_exe["child_object_selector_column"] => $original_item[ !empty( $relation_exe["parent_object_selector_column"] ) ? $relation_exe["parent_object_selector_column"] : "ID" ]
                ),
                $__sargs
              );

            }
          }
          else {

            $_relation_whereArray = array(
              $caller->parsed->name . "_" . $relation_k => $original_item["ID"]
            );

            if ( $caller_role == "child" && !empty( $relation_exe["child_object_selector_column"] ) ){
              $_relation_whereArray = array(
                "ID_in" => $original_item[ $relation_exe["child_object_selector_column"] ]
              );
            }

            $item[ "bof_rel_{$relation_k}" ] = bof()->object->__get( $other_object )->select(
              $_relation_whereArray,
              array_merge(
                array(
                  "limit" => !empty( $relation_exe["limit"] ) ? $relation_exe["limit"] : 10,
                ),
                is_array( $selectArgs["_eq"][ $relation_k ] ) ? $selectArgs["_eq"][ $relation_k ] : []
              )
            );
          }

        }

      }
    }

    // Get translations
    if ( bof()->getName() == "bof_client" && $caller->proxied->method_exists("bof_columns") ){
      if ( in_array( "translations", array_keys( $caller->proxied->bof_columns() ), true ) && !empty( $item["translations_decoded"] ) ){

        $_translations = $item["translations_decoded"];
        $_translation_cols = $caller->proxied->bof_columns()["translations"];
        $_lang = bof()->user->check()->language;

        foreach( $_translation_cols as $_col_name ){
          if ( !empty( $_translations[ "{$_col_name}_{$_lang}" ] ) ){

            $_col_detail = $caller->proxied->columns()[ $_col_name ];

            $item[ $_col_name ] = $_translations[ "{$_col_name}_{$_lang}" ];

            if ( !empty( $_col_detail["input"]["type"] ) ? $_col_detail["input"]["type"] == "text_editor" : false ){

              $valueObject = null;
              try {
                $valueObject = json_decode( $item[ $_col_name ] );
              } catch( Exception $err ){}

              if ( !$valueObject ){
                try {
                  $valueObject = json_decode( htmlspecialchars_decode( $item[ $_col_name ] ) );
                } catch( Exception $err ){}
              }

              $item[ $_col_name . "_html" ] = $item[ $_col_name ] ? bof()->editorjs->htmlize( $valueObject ) : "";
              $item[ $_col_name . "_html_pure" ] = "";
            }

          }
        }

      }
    }

    // Get price
    if ( in_array( "price", array_keys( $item ), true ) ){
      $parse_price = bof()->object->currency->parse_price( $item["price"] );
      $item["price_hr"] = $parse_price["string"];
      $item["price"] = $parse_price["user"]["price"];
      $item["currency"] = $parse_price["user"]["currency"];
      $item["price_d"] = $parse_price["default"]["price"];
    }

    // Fetch canonical URL
    if ( !empty( $original_item["seo_url"] ) )
    $item["url"] = bof()->seo->url( $caller->proxied, $original_item );

    if ( $caller->proxied->method_exists( "clean" ) ){
      $item = $caller->proxied->clean(
        $item,
        $selectArgs,
        !empty( $original_items[ $item_i-1 ] ) ? $original_items[ $item_i-1 ] : null,
        !empty( $original_items[ $item_i+1 ] ) ? $original_items[ $item_i+1 ] : null
      );
      if ( $item === false )
      return false;
    }

    if ( $public ){
      $item = bof()->object->_publicize( $caller, $item, $selectArgs );
    }

    if ( !empty( $selectArgs["as_widget"] ) ?  $caller->proxied->method_exists( "clean_as_widget" ) : false ){

      $o_item = $item;
      $item = $caller->proxied->clean_as_widget(
        $item,
        $selectArgs,
        !empty( $original_items[ $item_i-1 ] ) ? $original_items[ $item_i-1 ] : null,
        !empty( $original_items[ $item_i+1 ] ) ? $original_items[ $item_i+1 ] : null
      );

      if ( !empty( $o_item["price_hr"] ) ){
        $item["price_hr"] = $o_item["price_hr"];
        $item["price"] = $o_item["price"];
      }
      unset( $o_item );

    }

    if ( !empty( $selectArgs["client_single"] ) ? $caller->proxied->method_exists( "clean_client_single" ) : false ){
      $item = $caller->proxied->clean_client_single(
        $item,
        $selectArgs,
        !empty( $original_items[ $item_i-1 ] ) ? $original_items[ $item_i-1 ] : null,
        !empty( $original_items[ $item_i+1 ] ) ? $original_items[ $item_i+1 ] : null
      );
    }

    if ( !empty( $selectArgs["search_terms"] ) ?  $caller->proxied->method_exists( "clean_search_terms" ) : false ){
      $item = $caller->proxied->clean_search_terms(
        $item,
        $selectArgs
      );
    }

    if ( $cleaner ? is_callable( $cleaner ) : false ){
      if ( ( $run_cleaner = $cleaner( $item ) ) )
      $item = $run_cleaner;
    }

    return true;

  }
  public function _publicize( $caller_raw, $item, $selectArgs=[] ){

    $caller = bof()->object->parse_caller( $caller_raw );

    foreach( $caller->parsed->columns as $column_name => $column_args ){

      $type = "normal";

      if ( !empty( $column_args["validator"][0] ) ? $column_args["validator"][0] == "json" : false )
      $type = "json";

      if ( !empty( $column_args["validator"][0] ) && empty( $column_args[1]["no_bof_time"] ) ? $column_args["validator"][0] == "timestamp" : false )
      $type = "time";

      if ( empty( $column_args["public"] ) && !empty( $item ) ? in_array( $column_name, array_keys( $item ), true ) : false ){
        unset( $item[ $column_name ] );
      }
      else {

        if ( $type == "json" )
        unset( $item[ $column_name ] );

        continue;

      }

      // Decode jsons
      if ( !empty( $column_args["validator"][0] ) ? $column_args["validator"][0] == "json" : false ){
        unset( $item[ $column_name . "_decoded" ] );
      }

      // Time Helpers
      if ( !empty( $column_args["validator"][0] ) && ( !empty( $column_args[1]["no_bof_time"] ) || !empty( $selectArgs["no_bof_time"] ) ) ? $column_args["validator"][0] == "timestamp" : false ){
        unset( $item[ "bof_{$column_name}" ] );
        unset( $item[ "bof_{$column_name}_seconds" ] );
        unset( $item[ "bof_{$column_name}_seconds_ago" ] );
        unset( $item[ "bof_{$column_name}_hr" ] );
      }

    }

    return $item;

  }
  public function _update( $caller_raw, $whereArgs, $setArgs, $exeRelations=true ){

    $caller = bof()->object->parse_caller( $caller_raw );
    $caller_parsed = $caller->parsed;

    $whereArgs_parsed = bof()->object->parse_caller_where( $caller, $whereArgs );
    $setArgs_parsed = bof()->object->parse_caller_set( $caller, $setArgs, true );

    if ( !$whereArgs_parsed )
    fall( $caller->name . " can't update without whereArgs" );

    if ( in_array( "ignore_blacklist()", $whereArgs, true ) ){
      unset( $whereArgs[ array_search( "ignore_blacklist()", $whereArgs ) ] );
      $ignore_blacklist = true;
    }

    $items = $caller->proxied->select(
      $whereArgs,
      array(
        "single" => false,
        "limit" => false,
        "cache_load_rt" => false,
        "relations" => true
      )
    );

    if ( $setArgs_parsed ? is_array( $setArgs_parsed ) : false ){
      bof()->db->_update( array(
        "table" => $caller_parsed->table_name,
        "where" => $whereArgs_parsed,
        "set"   => $setArgs_parsed
      ) );
    } else {
      // var_dump( $setArgs );
      // die;
    }

    if ( $exeRelations )
    $this->exe_caller_relations(
      $caller,
      "update",
      $whereArgs,
      array(
        "old_items" => $items,
        "data" => $setArgs
      )
    );

    return true;

  }
  public function _insert( $caller_raw, $setArgs, $exeRelations=true ){

    $caller = bof()->object->parse_caller( $caller_raw );
    $caller_parsed  = $caller->parsed;
    $setArgs_parsed = bof()->object->parse_caller_set( $caller, $setArgs );

    if ( !is_array( $setArgs_parsed ) )
    fall( $caller->name . " setArgs failed " . $setArgs_parsed );

    if ( !bof()->defined("db") )
    return;

    $insert_id = bof()->db->_insert(
      array_merge( $setArgs, array(
        "table" => $caller_parsed->table_name,
        "set"   => $setArgs_parsed
      ) )
    );

    if ( $exeRelations )
    $this->exe_caller_relations(
      $caller,
      "insert",
      array(
        "ID" => $insert_id
      ),
      array(
        "data" => $setArgs
      )
    );

    return $insert_id;

  }
  public function _delete( $caller_raw, $whereArgs, $exeRelations=true ){

    $caller = bof()->object->parse_caller( $caller_raw );
    $caller_parsed = $caller->parsed;
    $whereArgs_parsed = bof()->object->parse_caller_where( $caller, $whereArgs );

    if ( !$whereArgs_parsed )
    fall( $caller->name . " can't delete without whereArgs" );

    $items = $caller->proxied->select(
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
        foreach( $item as $_k => $_v ){
          if ( !empty( $caller->parsed->columns[ $_k ] ) ){

            $_column = $caller->parsed->columns[ $_k ];

            if ( !empty( $_column["bofInput"] ) ? $_column["bofInput"][0] == "file" && $_v : false ){
              bof()->object->file->unlink( $_v, $caller_parsed->name . $item["ID"] );
            }
            elseif ( !empty( $_column["validator"] ) ? $_column["validator"] == "editor_js" || ( !empty( $_column["validator"][0] ) ? $_column["validator"][0] == "editor_js" : false ) : false ){
              bof()->editorjs->remove( $caller, $item["ID"], $_v );
            }

          }
        }
      }
    }

    bof()->db->_delete( array(
      "table" => $caller_parsed->table_name,
      "where" => $whereArgs_parsed,
    ) );

    if ( $exeRelations )
    $this->exe_caller_relations( $caller, "delete", $whereArgs, array(
      "old_items" => $items
    ) );

    return true;

  }
  public function _create( $caller_raw, $whereArray, $insertArray, $updateArray, $returnDetails=false, $exeRelations=true ){

    $caller = bof()->object->parse_caller( $caller_raw );

    if ( in_array( "ignore_blacklist()", $whereArray, true ) ){
      unset( $whereArray[ array_search( "ignore_blacklist()", $whereArray ) ] );
      $ignore_blacklist = true;
    }

    if ( ( $caller->proxied->method_exists("bof") && empty( $ignore_blacklist ) ) ? !empty( $caller->proxied->bof()["blacklistable"] ) && ( !empty( $insertArray["code"] ) || !empty( $updateArray["code"] ) ) : false ){
      $code = !empty( $insertArray["code"] ) ? $insertArray["code"] : $updateArray["code"];
      $blacklisted = bof()->object->blacklist->select(
        array(
          "object_type" => $caller->proxied->bof()["name"],
          "code" => $code
        )
      );
      if ( $blacklisted ){
        throw new Exception( "blacklisted" );
      }
    }

    if ( $whereArray ){

      $whereArrayType = "or";
      if ( in_array( "and()", $whereArray, true ) ){
        unset( $whereArray[ array_search( "and()", $whereArray ) ] );
        $whereArrayType = "and";
      }

      $whereArrayParsed = bof()->object->parse_caller_where( $caller, $whereArray );
      if ( count( $whereArrayParsed ) > 1 ){
        $whereArrayParsed = array(
          "oper" => strtoupper( $whereArrayType ),
          "cond" => $whereArrayParsed
        );
      }

      $check_db = $caller->proxied->select(
        $whereArrayParsed,
        array(
          "cache_load_rt" => false
        )
      );

      if ( $check_db ){

        if ( $updateArray ){

          $hasUpdate = false;
          foreach( $check_db as $_k => $_v ){

            if ( !in_array( $_k, array_keys( $updateArray ) ) )
            continue;

            $column_data = !empty( $caller->parsed->columns[ $_k ] ) ? $caller->parsed->columns[ $_k ] : null;

            if ( $updateArray[ $_k ] != $_v ){

              $hasUpdate = $_k;
              if ( !empty( $column_data["bofInput"] ) ? $column_data["bofInput"][0] == "file" : false ){

                $_validate_file = bof()->object->file->finalize_upload(
                  $column_data["bofInput"][1]["type"],
                  $column_data["bofInput"][1]["object_type"],
                  $caller->parsed->name . $check_db["ID"] ,
                  $updateArray[ $_k ],
                  $_v,
                  $column_data["bofInput"][1],
                  array(
                    "key" => $_k,
                    "data" => $column_data
                  )
                );

                if ( !$_validate_file )
                $updateArray[ $_k ] = 0;

              }
              elseif ( !empty( $column_data["validator"] ) ? $column_data["validator"] == "editor_js" || ( !empty( $column_data["validator"][0] ) ? $column_data["validator"][0] == "editor_js" : false ) : false ){

                $__new_data = bof()->editorjs->finalize( $caller, $check_db["ID"], $updateArray[ $_k ], $check_db[ $_k ] );
                $updateArray[ $_k ] = $__new_data;

              }

            }
            else {

              if ( !empty( $column_data["bofInput"] ) ? $column_data["bofInput"][0] == "file" : false ){
                $_validate_file = bof()->object->file->finalize_upload(
                  $column_data["bofInput"][1]["type"],
                  $column_data["bofInput"][1]["object_type"],
                  $caller->parsed->name . $check_db["ID"] ,
                  $updateArray[ $_k ],
                  $_v,
                  $column_data["bofInput"][1],
                  array(
                    "key" => $_k,
                    "data" => $column_data
                  )
                );
              }

            }

          }

          $caller->proxied->update( $whereArray, $updateArray, $exeRelations );

        }

        return $returnDetails ? array(
          "ID" => !empty( $check_db["ID"] ) ? $check_db["ID"] : null,
          "type" => "update",
          "had_update" => !empty( $hasUpdate ) ? $hasUpdate : false
        ) : ( !empty( $check_db["ID"] ) ? $check_db["ID"] : null );

      }

    }

    $insert_id = $caller->proxied->insert( $insertArray, $exeRelations );

    $after_insert_update_array = [];

    foreach( $insertArray as $_k => $_v ){

      $column_data = !empty( $caller->parsed->columns[ $_k ] ) ? $caller->parsed->columns[ $_k ] : null;

      if ( !empty( $column_data["bofInput"] ) ? $column_data["bofInput"][0] == "file" : false ){

        $_validate_file = bof()->object->file->finalize_upload(
          $column_data["bofInput"][1]["type"],
          $column_data["bofInput"][1]["object_type"],
          $caller->parsed->name . $insert_id,
          $_v,
          false,
          $column_data["bofInput"][1],
          array(
            "key" => $_k,
            "data" => $column_data
          )
        );

        if ( !$_validate_file )
        $after_insert_update_array[ $_k ] = 0;

      }
      elseif ( !empty( $column_data["validator"] ) ? $column_data["validator"] == "editor_js" || ( !empty( $column_data["validator"][0] ) ? $column_data["validator"][0] == "editor_js" : false ) : false ){

        $__new_data = bof()->editorjs->finalize( $caller, $insert_id, $_v );

        if ( $__new_data !== $_v )
        $after_insert_update_array[ $_k ] = $__new_data;

      }
    }

    if ( $after_insert_update_array ){
      $caller->proxied->update(
        array(
          "ID" => $insert_id
        ),
        $after_insert_update_array,
        $exeRelations
      );
    }

    return $returnDetails ? array(
      "ID" => $insert_id,
      "type" => "insert"
    ) : $insert_id;

  }
  public function _count( $caller_raw, $whereArgs, $selectArgs=[] ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $cache = true;
    $columns = "";
    extract( $selectArgs );

    $count = $caller->proxied->select(
      $whereArgs,
      array(
        "single" => true,
        "limit" => 1,
        "offset" => false,
        "page" => false,
        "columns" => "COUNT(*) as c",
        "empty_select" => true,
        "clean" => false,
        "cache" => $cache,
        "cache_load_rt" => $cache,
        "cache_save" => $cache,
        "cache_load" => $cache,
        "cache_load_range" => "10 MINUTE",
        "from_cronjob" => !empty( $selectArgs["from_cronjob"] ) ? $selectArgs["from_cronjob"] : null
      )
    );

    if ( !$count )
    return 0;

    return $count["c"];

  }
  public function _count_v2( $caller_raw, $whereArgs, $selectArgs=[] ){

    $caller = bof()->object->parse_caller( $caller_raw );
    $caller_parsed = $caller->parsed;

    $cache = true;
    $columns = "";
    extract( $selectArgs );

    $whereArgs_parsed = bof()->object->parse_caller_where( $caller, $whereArgs );
    $whereArgs_parsed_parsed = bof()->db->parse_where( $whereArgs_parsed );

    if ( $whereArgs_parsed_parsed[0] )
    $query = "SELECT COUNT(*) as c FROM ( SELECT 1 FROM {$caller_parsed->table_name} WHERE ( {$whereArgs_parsed_parsed[0]} ) LIMIT 1000000000 ) as sq";
    else
    $query = "SELECT COUNT(*) as c FROM ( SELECT 1 FROM {$caller_parsed->table_name} LIMIT 1000000000 ) as sq";

    $runQuery = bof()->db->query( $query );

    return  $runQuery->fetch_assoc()["c"];

  }
  public function _search( $caller_raw, $args ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $query = null;
    extract( $args );

    if ( !$query )
    throw new Exception( "invalid_query" );

    if ( empty( $caller->parsed->selectors["query"] ) )
    return [];

    $search = $caller->proxied->select(
      array(
        "query" => $query
      ),
      array(
        "limit" => 10,
        "as_widget" => true,
        "full_search" => true
      )
    );

    return $search;

  }
  public function _______seo( $caller_raw, $item ){

    $caller = bof()->object->parse_caller( $caller_raw );
    $title_name = bof()->object->db_setting->get( "sitename" );

    if ( !empty( $item["seo_image"] ) ){
      $seo_image = bof()->object->file->select( array(
        "ID" => $item["seo_image"]
      ) );
    }

    if ( !empty( $item["seo_data_decoded"]["title"] ) )
    $seo_abs_title = $item["seo_data_decoded"]["title"];

    if ( !empty( $item["seo_data_decoded"]["tags"] ) )
    $seo_abs_tags = $item["seo_data_decoded"]["tags"];

    if ( !empty( $item["seo_data_decoded"]["description"] ) )
    $seo_abs_description = $item["seo_data_decoded"]["description"];

    if ( empty( $seo_abs_title ) || empty( $seo_abs_description ) || empty( $seo_abs_tags ) ){

      foreach( $caller->parsed->columns as $column_name => $column_args ){
        if ( !empty( $column_args["bofAdmin"]["object"]["seo_slug_source"] ) && !empty( $item[ $column_name ] ) ){
          $seo_organic_title = $item[ $column_name ];
          $seo_organic_tags = $item[ $column_name ];
          $seo_organic_description = $item[ $column_name ];
          break;
        }
      }

    }

    return array(
      "image" => !empty( $seo_image["image_list"] ) ? array_keys( $seo_image["image_list"] )[0] : null,
      "title" => ( !empty( $seo_abs_title ) ? $seo_abs_title : $seo_organic_title ) . ( $title_name ? " | {$title_name}" : "" ),
      "description" => !empty( $seo_abs_description ) ? $seo_abs_description : $seo_organic_description,
      "tags" => !empty( $seo_abs_tags ) ? $seo_abs_tags : $seo_organic_tags,
    );

  }

  public function _get_free_hash( $caller_raw, $column_name="hash" ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $free_hash = false;
    while( !$free_hash ){
      $hash = md5( uniqid() );
      if (
        $caller->proxied->select(
          array(
            $column_name => $hash
          ),
          array(
            "cache_load_rt" => false
          )
        )
      ) continue;
      $free_hash = $hash;
    }
    return $free_hash;

  }
  public function _get_free_url( $caller_raw, $seed ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $free_url = null;
    $tries = 1;
    while( !$free_url ){

      $seedURL = bof()->general->make_url( $seed );
      $url = $tries == 1 ? $seedURL : "{$seedURL}-{$tries}";

      if (
        $caller->proxied->select(
          array(
            "seo_url" => $url
          ),
          array(
            "cache_load_rt" => false,
            "bof_object_get_free_url" => true
          )
        )
      ){
        $tries++;
        continue;
      }
      $free_url = $url;

    }

    return $free_url;

  }

  public function _make_rels( $caller_raw, $sourceID, $relIDs, $relType, $deleteNonExistents=true ){

    $caller = bof()->object->parse_caller( $caller_raw );
    if ( $relIDs ? is_array( $relIDs ) : false ) $relIDs = array_unique( $relIDs );

    $rels = array(
      "exists" => [],
      "new" => [],
      "removed" => [],
      "no_change" => [],
    );

    $get_current_rels = bof()->db->_select( array(
      "table" => $caller->proxied->bof()["db_rel_table_name"],
      "where" => array(
        [ $caller->proxied->bof()["db_rel_table_col_name"], "=", $sourceID ],
        [ "type", "=", $relType ]
      ),
      "limit" => false,
      "single" => false,
      "cache_load_rt" => false
    ) );

    if ( $get_current_rels && $deleteNonExistents ){

      foreach( $get_current_rels as $_current_rel_data ){
        $rels[ "exists" ][] = $_current_rel_data["target_id"];
      }

      foreach( $rels["exists"] as $_relID ){
        if (  $relIDs ? !in_array( $_relID, $relIDs ) : true ){
          $rels["removed"][] = $_relID;
          bof()->db->_delete( array(
            "table" => $caller->proxied->bof()["db_rel_table_name"],
            "where" => array(
              [ $caller->proxied->bof()["db_rel_table_col_name"], "=", $sourceID ],
              [ "type", "=", $relType ],
              [ "target_id", "=", $_relID ]
            )
          ) );
        }
      }

    }

    if ( $relIDs ){
      foreach( $relIDs as $i => $_relID ){

        if ( in_array( $_relID, $rels["exists"] ) ){
          $rels["no_change"][] = $_relID;
          bof()->db->_update(array(
            "table" => $caller->proxied->bof()["db_rel_table_name"],
            "where" => array(
              [ $caller->proxied->bof()["db_rel_table_col_name"], "=", $sourceID ],
              [ "type", "=", $relType ],
              [ "target_id", "=", $_relID ]
            ),
            "set" => array(
              [ "i", $i ]
            )
          ));
        }

        else {

          $setArray = array(
            [ $caller->proxied->bof()["db_rel_table_col_name"], $sourceID ],
            [ "type", $relType ],
            [ "target_id", $_relID ],
            [ "i", $i ]
          );

          $rels["new"][] = $_relID;
          bof()->db->_insert(array(
            "table" => $caller->proxied->bof()["db_rel_table_name"],
            "where" => array(
              [ $caller->proxied->bof()["db_rel_table_col_name"], "=", $sourceID ],
              [ "type", "=", $relType ],
              [ "target_id", "=", $_relID ]
            ),
            "set" => $setArray
          ));
        }

      }
    }

    return $rels;

  }
  public function _delete_rels( $caller_raw, $sourceID, $targetID, $relType ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $whereQuery = array();
    if ( $relType ) $whereQuery[] = [ "type", "=", $relType ];
    if ( $sourceID ) $whereQuery[] = [ $caller->proxied->bof()["db_rel_table_col_name"], "=", $sourceID ];
    if ( $targetID ) $whereQuery[] = [ "target_id", "=", $targetID ];

    $getRels = bof()->db->_select(array(
      "table" => $caller->proxied->bof()["db_rel_table_name"],
      "where" => $whereQuery,
      "limit" => false,
      "single" => false,
      "cache_load_rt" => false
    ));

    $effected_childs = $effected_parents = [];

    if ( $getRels ){

      foreach( $getRels as $rel ){
        $effected_childs[] = $rel["target_id"];
        $effected_parents[] = $rel[ $caller->proxied->bof()["db_rel_table_col_name"] ];
      }

      bof()->db->_delete(array(
        "table" => $caller->proxied->bof()["db_rel_table_name"],
        "where" => $whereQuery
      ));

    }

    return array(
      "childs" => array_unique( $effected_childs ),
      "parents" => array_unique( $effected_parents ),
    );

  }
  public function _select_rels( $caller_raw, $sourceID, $targetID, $relType ){

    $caller = bof()->object->parse_caller( $caller_raw );

    $whereQuery = array();
    if ( $relType ) $whereQuery[] = [ "type", "=", $relType ];
    if ( $sourceID ) $whereQuery[] = [ $caller->proxied->bof()["db_rel_table_col_name"], "=", $sourceID ];
    if ( $targetID ) $whereQuery[] = [ "target_id", "=", $targetID ];

    $getRels = bof()->db->_select(array(
      "table" => $caller->proxied->bof()["db_rel_table_name"],
      "where" => $whereQuery,
      "limit" => false,
      "single" => false,
      "cache_load_rt" => false
    ));

    return $getRels;

  }

  public function _mark_time( $caller_raw, $whereArray, $column_name, $time=null ){

    $caller = bof()->object->parse_caller( $caller_raw );

    if ( !$time )
    $time = bof()->general->mysql_timestamp();

    elseif ( !is_string( $time ) )
    $time = bof()->general->mysql_timestamp( $time );

    $whereArray = is_array( $whereArray ) ? $whereArray : [ "ID" => $whereArray ];

    $updateArray = array(
      "time_{$column_name}" => $time
    );

    return $caller->proxied->update(
      $whereArray,
      $updateArray,
      false
    );

  }

}

?>
