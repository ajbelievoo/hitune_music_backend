<?php

if ( !defined( "bof_root" ) ) die;

class object_tag extends bof_type_object_sample {

  // BusyOwlFramework handshake
  public function bof( $caller=null, $result=[] ){

    return array_merge( array(
      "icon" => "tag",
      "widgetable" => true,
    ), $result );

  }
  public function columns( $caller, $result=[] ){

    if ( !empty( $caller->child()->hiearchy ) ){
      $result["parent_id"] = array(
        "label" => "Parent",
        "bofInput" => array(
          "object",
          array(
            "type" => "m_genre"
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => true
          ),
          "filters" => array(
            "type" => array(
              "title" => "Has children",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ "parent", "Yes" ],
                  [ "child", "No" ],
                  [ "__all__", "All" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "__all__", "parent", "child" ]
                )
              )
            ),
            "col_parent" => array(
              "title" => "Parent(s)",
              "tip" => "Will select childs belonging to selected parent(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => $caller->bof()["name"],
                  "sub_type" => "parent",
                  "multi" => true,
                  "autoload" => false
                )
              )
            )
          ),
        ),
        "relations" => array(
          "hiearchy" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "m_genre",
              "parent_object_stats_column" => "s_childs",
              "child_object" => "m_genre",
              "child_object_selector_column" => "parent_id",
            )
          )
        ),
        "selectors" => array(
          "parent_id" => [ "parent_id", "=" ],
          "type" => function( $val ){
            if ( $val == "parent" )
            return [ "s_childs", ">", "0" ];
            if ( $val == "child" )
            return array(
              "oper" => "AND",
              "cond" => array(
                [ "parent_id", "NOT", null, true ],
                [ "parent_id", "!=", "0" ]
              )
            );
          },
          "col_parent" => [ "parent_id", "by_column", [ "force_array" => true ] ],
        )
      );
    }

    return array_merge( array(
      "name" => array(
        "public" => true,
        "label" => "Name",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          )
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ( $displayItem, $item, $displayData, $caller ){
              if ( !empty( $item["parent_id"] ) ){
                $parent_data = $caller->sid( $item["parent_id"] );
                $displayData["data"] = "{$displayData["data"]}" . "<span class='sub'>- {$parent_data["name"]}</div>";;
              }
              if ( !empty( $item["bof_file_cover"] ) ? !empty( $item["bof_file_cover"]["image_thumb"] ) : false )
              $displayData["image_preview"] = $item["bof_file_cover"]["image_thumb"];
              return $displayData;
            },
          ),
          "object" => array(
            "seo_slug_source" => true,
            "required" => true
          ),
        ),
      ),
    ), $result );

  }
  public function stats_columns( $caller, $result=[] ){

    $default = array(
      "views",
      "views_unique",
    );

    foreach( $caller->child()->relations as $relation_k => $relation_d )
    $default[ $relation_d["stat"] ] = array(
      "label" => bof()->object->__get( $relation_k )->bof()["name"]
    );

    if ( !empty( $caller->child()->hiearchy ) )
    $default["childs"] = array(
      "label" => "childs"
    );

    return array_merge( $default, $result );

  }
  public function bof_columns( $caller, $args=[] ){

    $cs = array(
      "ID",
      "hash",
      "code" => array(
        "from" => array(
          "name"
        )
      ),
      "time_add",
      "seo",
      "translations" => array(
        "name"
      )
    );

    if ( empty( $caller->child()->no_cover ) )
    $cs[] = "cover";

    return $cs;

  }

  public function selectors( $caller, $result=[] ){

    $default = array(
      "query"  => [ "name", "LIKE%" ],
    );

    foreach( $caller->child()->relations as $relation_k => $relation_d ){

      if ( !empty( $relation_d["hub_type"] ) )
      $default[ $relation_k . "_" . $relation_d["plural"] ] = [ "ID", "related_to_parent", "rel_parent" => $relation_k, "hub_type" => $relation_d["hub_type"] ];

    }

    return array_merge( $default, $result );

  }
  public function relations( $caller, $result=[] ){

    $default = array();

    foreach( $caller->child()->relations as $relation_k => $relation_d ){

      if ( !empty( $relation_d["hub_type"] ) ){
        $default[ $relation_k ] = array(
          "exec" => array(
            "type" => "hub",
            "hub_type" => $relation_d["hub_type"],
            "parent_object" => $relation_k,
            "parent_object_stats_column" => "s_" . $relation_d["plural"],
            "child_object" => $caller->bof()["name"],
            "child_object_stats_column" => "s_" . $relation_d["stat"]
          ),
        );
      }
      else {
        $default[ $relation_k ] = array(
          "exec" => array(
            "type" => "direct",
            "parent_object" => $caller->bof()["name"],
            "parent_object_stats_column" => "s_" . $relation_d["stat"],
            "child_object" => $relation_k,
            "child_object_selector_column" => $relation_d["selector"]
          ),
        );
      }

    }

    return array_merge( $default, $result );

  }

  public function bof_admin( $caller ){

    $setting = array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => $caller->child()->bof_admin_edit_url,
        "list_page_url" => $caller->child()->bof_admin_list_url,
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "list" => array(
        "name" => null,
        "stats" => array(
          "label" => "Stats",
          "type" => "simple",
          "class" => "details",
          "renderer" => function( $displayItem, $item, $displayData, $caller ){
            $displayData["data"] = "<ul>";
            foreach( $caller->stats_columns() as $stat_column_k => $stat_column ){
              $stat_column_name = is_int( $stat_column_k ) ? $stat_column : $stat_column_k;
              if ( !empty( $item[ "s_" . $stat_column_name ] ) )
              $displayData["data"] .= "<li><b>". bof()->object->language->turn( $stat_column_name, [], [ "lang" => "en" ] ) ."</b>" . number_format( $item[ "s_" . $stat_column_name ] ) . "</li>";
            }
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
          "object_be_renderer" => function ( $_inputs, $request, $caller ){

            if ( $request["type"] != "multi" ){

              $request_id = !empty( $request["IDS"][0] ) ? $request["IDS"][0] : false;

              if ( !empty( $_inputs["data"]["name"] ) ){

                $check = $caller->select( array(
                  "code" => bof()->general->make_code( $_inputs["data"]["name"] )
                ) );

                if ( $check ? ( $request_id ? $check["ID"] != $request_id : true ) : false ){
                  $_inputs["report"]["fail"]["name"] = "Already in use";
                }

              }

              if ( empty( $_inputs["report"]["fail"] ) )
              $inputs["set"]["code"] = $inputs["update"]["code"] = bof()->general->make_code( $_inputs["data"]["name"] );

            }

            return $_inputs;

          },
        ),
        "time_add" => array(
          "label" => "Time<br>Add",
          "type" => "time"
        )
      ),
      "object" => array(
        "name" => null,
        "cover_id" => null,
        "bg_id" => null,
      ),
      "buttons_renderer" => function( $item, $buttons, $caller ){

        foreach( $caller->child()->relations as $relation_k => $relation_d ){
          if ( !empty( $relation_d["hub_type"] ) ){
            $buttons[ "list_" . $relation_d["stat"] ] = array(
              "label" => "List " . bof()->object->language->turn( $relation_d["stat"], [], [ "lang" => "en" ] ),
              "link" => $relation_d["bof_admin_list_url"] . "?rel_{$relation_d["hub_type"]}={$item["ID"]}"
            );
          }
        }

        return $buttons;

      },
      "object_be_renderer" => function( $_inputs, $request, $caller ){

        if ( $request["type"] == "new" )
        return;

        if ( empty( $_inputs["data"]["parent_id"] ) )
        return;

        $parent_data = $caller->sid( $_inputs["data"]["parent_id"] );
        if ( !empty( $parent_data["parent_id"] ) ){
          $_inputs["report"]["fail"]["parent_id"] = "Selected parent:{$parent_data["name"]} is a child itself! Remove {$parent_data["name"]} parent then retry";
          return $_inputs;
        }

        foreach( $request["IDS"] as $_id ){

          $_id_data = $caller->sid( $_id );
          if ( !empty( $_id_data["s_childs"] ) ){
            $_inputs["report"]["fail"]["parent_id"] = "Selected tag:{$_id_data["name"]} is a parent itself! Remove all of the children belonging to {$_id_data["name"]}, edit {$_id_data["name"]} & then retry";
            return $_inputs;
          }

          if ( $_id == $_inputs["data"]["parent_id"] ){
            $_inputs["report"]["fail"]["parent_id"] = "Can't assign tag:{$_id_data["name"]} as it's own parent!";
            return $_inputs;
          }

          if ( !empty( $_id_data["parent_id"] ) )
          $old_parents[] = $_id_data["parent_id"];

        }

      },
      "object_be_renderer_after" => function( $_inputs, $request, $IDS, $caller ){

        if ( $request["type"] == "new" )
        return;

        if ( empty( $_inputs["data"]["parent_id"] ) )
        return;

        $parent_data = $caller->sid( $_inputs["data"]["parent_id"] );
        foreach( $request["IDS"] as $_id ){
          $_id_data = $caller->sid( $_id );
          if ( !empty( $_id_data["parent_id"] ) )
          $old_parents[] = $_id_data["parent_id"];
        }

        $all_parents = array_merge( [ $parent_data["ID"] ], !empty( $old_parents ) ? $old_parents : [] );
        if ( empty( $_inputs["report"]["fail"] ) ){
          if ( !empty( $all_parents ) ){
            foreach( $all_parents as $_parent ){
              bof()->db->query("UPDATE {$caller->bof()["db_table_name"]} SET s_childs = ( SELECT COUNT(*) as s FROM {$caller->bof()["db_table_name"]} as xx WHERE xx.parent_id = '{$_parent}' ) WHERE ID = '{$_parent}' ");
            }
          }
        }

      }
    );

    if ( !empty( $caller->child()->no_cover ) ){
      unset( $setting["object"]["cover_id"] );
      unset( $setting["object"]["bg_id"] );
    }

    return $setting;

  }
  public function bof_client( $caller ){
    return array(
      "public_browse" => true,
      "single_url_prefix" => $caller->child()->bof_client_single_url,
      "list_url" => $caller->child()->bof_client_list_url,
      "buttons" => array(
        "link" => true,
        "share" => true,
      ),
    );
  }

  // BusyOwlFramework helpers
  public function select( $caller, $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $client_single = false;
    $as_widget = false;
    $_eq = [];
    extract( $selectArgs );
    $selectArgs[ "_eq" ] = $_eq;

    if ( $search || $listing || $as_widget ){
      $_eq = array(
        "cover" => []
      );
    }

    if ( $client_single )
    $_eq["bg"] = [];

    $selectArgs["_eq"] = $_eq;

    return bof()->object->_select( $caller, $whereArgs, $selectArgs );

  }
  public function insert( $caller, $setArray ){

    if ( !empty( $caller->child()->hiearchy ) ? in_array( "parent_id", array_keys( $setArray ), true )  : false )
    $this->hiearchy_indexes( $caller );

    $setArray["code"] = !empty( $setArray["code"] ) ? $setArray["code"] : bof()->general->make_code( $setArray["name"] );
    $setArray["seo_url"] = !empty( $setArray["seo_url"] ) ? $setArray["seo_url"] : $caller->get_free_url( $setArray["name"] );
    $setArray["hash"] = !empty( $setArray["hash"] ) ? $setArray["hash"] : $caller->get_free_hash();
    return bof()->object->_insert( $caller, $setArray );

  }
  public function update( $caller, $whereArray, $setArray, $exeRelations=true ){

    if ( !empty( $caller->child()->hiearchy ) ? in_array( "parent_id", array_keys( $setArray ), true )  : false )
    $this->hiearchy_indexes( $caller );

    return bof()->object->_update( $caller, $whereArray, $setArray, $exeRelations );

  }

  protected function hiearchy_indexes( $caller ){

    $genres = $caller->select(
      [],
      array(
        "empty_select" => true,
        "empty_select()",
        "limit" => false,
        "single" => false,
        "clean" => false
      )
    );

    if ( !$genres )
    return;

    $tn = $caller->bof()["db_table_name"] . "_hiearchy";

    bof()->db->query("TRUNCATE {$tn}");

    $vals = [];
    $i = 0;

    foreach( $genres as $genre ){

      $vals[] = "{$genre["ID"]}, {$genre["ID"]}";
      if ( !empty( $genre["parent_id"] ) )
      $vals[] = "{$genre["ID"]}, {$genre["parent_id"]}";


      if ( $i % 20 ){

        $t = "(" . implode( "), (", $vals ) . ")" . PHP_EOL;
        bof()->db->query("INSERT INTO {$tn} ( genre_id, hook_id ) VALUES {$t}");
        $vals = [];

      }

      $i++;

    }

    if ( !empty( $vals ) ){
      $t = "(" . implode( "), (", $vals ) . ")" . PHP_EOL;
      bof()->db->query("INSERT INTO {$tn} ( genre_id, hook_id ) VALUES {$t}");
    }

  }

  public function clean( $caller, $item, $args ){

    $_eq = [];
    $search = false;
    $listing = false;
    $cleanest = false;
    extract( $args );

    if ( $cleanest ? in_array( $cleanest, array_keys( $item ), true ) : false )
    $item = $item[ $cleanest ];

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"],
        "image" => !empty( $item["cover"]["image_thumb"] ) ? $item["cover"]["image_thumb"] : false
      );
    }

    return $item;

  }
  public function clean_as_widget( $caller, $item, $args ){

    return array(
      "title" => $item["name"],
      "cover" =>  !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"] : null,
      "raw" => $item
    );

  }
  public function clean_client_single( $caller, $item, $args ){

    $widgets = [];

    foreach( $caller->child()->relations as $relation_k => $relation_d ){

      if ( $caller->method_exists("clean_client_single_widget") ){
        $widgets[ $relation_d["stat"] ]  = $caller->clean_client_single_widget( $item, $relation_d["stat"], $args );
      } else {
        $widgets[ $relation_d["stat"] ]  = $this->clean_client_single_widget( $caller, $item, $relation_d["stat"], $args );
      }

    }

    $item = array(
      "widgets" => $widgets,
      "data" => $item,
    );

    return $item;

  }
  public function clean_client_single_widget( $caller, $item, $widget_name, $args=[] ){

    $widgets = [];

    foreach( $caller->child()->relations as $relation_k => $relation_d )
    $widgets[ $relation_d["stat"] ] = array(
      "ID" => $relation_d["stat"],
      "display" => array(
        "type" => "slider",
        "title" => $relation_d["label"],
        "pagination" => "list/bof_{$caller->bof()["name"]}?slug={$item["seo_url"]}&widget={$relation_d["stat"]}",
        "link" => false,
        "slider_size" => "medium",
        "slider_rows" => !empty( $relation_d["display"]["slider_rows"] ) ? $relation_d["display"]["slider_rows"] : 1,
        "slider_mason" => !empty( $relation_d["display"]["slider_mason"] ) ? $relation_d["display"]["slider_mason"] : false,
        "link_on_bottom" => !empty( $relation_d["display"]["link_on_bottom"] ),
      ),
      "object" => array(
        "name" => $relation_k,
        "whereArray" => array(
          !empty( $relation_d["hub_type"] ) ? "rel_{$relation_d["hub_type"]}" : $relation_d["selector"] => $item["ID"],
        ),
        "selectArray" => array(
          "order_by" => !empty( $relation_d["display"]["order_by"] ) ? $relation_d["display"]["order_by"] : "time_add",
          "order" => !empty( $relation_d["display"]["order"] ) ? $relation_d["display"]["order"] : "DESC",
          "limit" => !empty( $relation_d["display"]["limit"] ) ? $relation_d["display"]["limit"] : 10,
        )
      ),
    );

    return !empty( $widgets[ $widget_name ] ) ? $widgets[ $widget_name ] : null;

  }

  public function get_id( $caller, $name, $extra=[] ){

    if ( ( $check_db = $caller->select( array(
      "code" => bof()->general->make_code( $name )
    ) ) ) ){
      return $check_db["ID"];
    }

    return $caller->insert( array_merge( $extra, array(
      "code" => bof()->general->make_code( $name ),
      "seo_url"  => $caller->get_free_url( $name ),
      "name" => $name
    ) ) );

  }

}

?>
