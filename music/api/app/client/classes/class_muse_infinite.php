<?php

use function PHPSTORM_META\map;

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class muse_infinite extends bof_type_class {

  public function endpoint(){

    $user_inputs = bof()->muse_infinite->get_user_inputs();
    extract( $user_inputs, EXTR_PREFIX_ALL, "user_input" );

    $get_object = bof()->muse_infinite->get_requested_object( $user_inputs );

    if ( !$get_object && !empty( $user_inputs["seed"]["all"] ) ){

      $seed_first = reset( $user_inputs["seed"]["all"] );
      $user_inputs["object_name"] = $seed_first["object_type"];
      $user_inputs["object_hash"] = $seed_first["object_hash"];
      $get_object = bof()->muse_infinite->get_requested_object( $user_inputs );

    }

    if ( !$get_object ) return;
    extract( $get_object, EXTR_PREFIX_ALL, "object" );

    $siblings = bof()->muse_infinite->get_siblings( $user_inputs, $get_object );

    if ( $siblings ){

      extract( $siblings, EXTR_PREFIX_ALL, "siblings" );

      $exe_siblings = bof()->muse_infinite->exe_query( $user_inputs, $get_object, $siblings );
      if ( $exe_siblings )
      $items = $exe_siblings["items"];

    }

    if ( empty( $items ) ? true : count( $items ) < 10 ){

      $required_related_items = empty( $items ) ? 10 : 10 - count( $items );
      $exe_related = bof()->muse_infinite->exe_related( $user_inputs, $get_object, $required_related_items );
      if ( $exe_related )
      $items = empty( $items ) ? $exe_related : array_merge( $items, $exe_related );

    }

    if ( !empty( $items ) ){
      foreach( $items as &$item ){

        if ( empty( $item["cover"] ) ){
          $placeholder = bof()->object->db_setting->get( "placeholder" );
          if ( $placeholder ){
            $placeholder = bof()->object->file->select( [ "ID" => $placeholder ] );
            $item["cover"] = $placeholder["image_thumb"];
          }
        }

      }
      unset( $item );
    }
    
    $unique_items = [];
    if ( $items && !empty( $user_input_queue["all"] ) ){
      $queue_ids = [];
      foreach( $user_input_queue["all"] as $queue_i ){
        $queue_ids[] = $queue_i["object_type"] . $queue_i["object_hash"];
      }
      foreach( $items as $item ){
        if ( !in_array( "{$item["ot"]}{$item["hash"]}", $queue_ids, true ) ){
          $unique_items[] = $item;
        }
      }
    }

    $output = array(
      "items" => !empty( $unique_items ) ? $unique_items : null,
      "has_more" => !empty( $unique_items ) ? count( $unique_items ) >= 10 : false
    );

    if( bof()->object->core_setting->get( "debug" ) ){
      $output = array_merge( $output, array(
        "queue_ids" => !empty( $queue_ids ) ? $queue_ids : null,
        "raw_items" => $items,
        "user_inputs" => $user_inputs,
        "object" => $get_object,
        "siblings" => !empty( $exe_siblings ) ? $exe_siblings : false,
        "related" => !empty( $exe_related ) ? $exe_related : false,
      ) );
    }

    bof()->api->set_message( "ok", $output );

  }
  public function get_user_inputs(){

    return array(
      "object_name" => bof()->nest->user_input( "post", "object_type", "bofClient_object"),
      "object_hash" => bof()->nest->user_input( "post", "object_hash", "md5" ),
      "widget_id" => bof()->nest->user_input( "post", "widget_id", "string" ),
      "widget_page" => bof()->nest->user_input( "post", "widget_page", "int", array( "min" => 2, "max" => 20 ), 1 ),
      "page_object_type" => bof()->nest->user_input( "post", "page_ot", "bofClient_object" ),
      "page_object_hash" => bof()->nest->user_input( "post", "page_hash", "md5" ),
      "page_object_url" => bof()->nest->user_input( "post", "page_url", "string" ),
      "page_object_name" => bof()->nest->user_input( "post", "page_name", "string" ),
      "page_object_page" => bof()->nest->user_input( "post", "page", "int", array( "min" => 2, "max" => 100 ), 1 ),
      "queue" => bof()->muse_infinite->get_queue(),
      "seed" => bof()->muse_infinite->get_seed(),
      "infinite" => bof()->muse_infinite->get_infinite(),
    );

  }
  public function get_queue( $postName = "queue" ){

    $queue = array();
    $raw = bof()->nest->user_input( "post", $postName, "json" );
    if ( $raw ? !is_array( $raw ) : true ){
      if ( $postName == "seed" ){
        $raw = array(
          array(
            "object_type" => bof()->nest->user_input( "post", "object_type", "bofClient_object"),
            "object_hash" => bof()->nest->user_input( "post", "object_hash", "md5" ),
          )
        );
      } else 
      return false;
    }

    foreach( $raw as $item ){

      if ( empty( $item["object_hash"] ) || empty( $item["object_type"] ) ) return false;
      if ( !bof()->nest->validate( $item["object_hash"], "md5" ) ) return false;
      if ( !bof()->nest->validate( $item["object_type"], "bofClient_object" ) ) return false;

      $queue["all"][] = $item;
      $queue["by_object"][ $item["object_type"] ][] = $item["object_hash"];

    }

    return $queue;

  }
  public function get_seed(){
    return bof()->muse_infinite->get_queue( "seed" );
  }
  public function get_infinite(){
    return bof()->muse_infinite->get_queue( "infinite" );
  }
  public function get_requested_object( $user_inputs ){

    extract( $user_inputs );

    if ( !$object_name || !$object_hash )
    return false;

    $the_object = bof()->object->__get( $object_name );
    $object_item = $the_object->select(
      array(
        "hash" => $object_hash
      )
    );

    if ( !$object_item )
    return false;

    return array(
      "proxied" => $the_object,
      "item" => $object_item,
      "has_source" => !empty( $the_object->bof_client()["buttons"]["source"] ),
    );

  }
  public function get_siblings( $user_inputs, $object_data ){

    extract( $user_inputs );
    extract( $object_data, EXTR_PREFIX_ALL, "object" );

    if ( $object_has_source ){

      // Playlist
      if ( $object_name != "ugc_playlist" && ( 
        ( $page_object_name == "ugc_playlist_single" && $page_object_type == "ugc_playlist" ) ||
        ( $page_object_name == "object_list" && bof()->general->startsWith( $page_object_url, "list/bof_ugc_playlist" ) ) 
      ) ){
        if ( !empty( $user_inputs["queue"]["all"] ) ){

          $qa = $user_inputs["queue"]["all"];
          $lq = end( $qa );

          if ( !empty( $user_inputs["infinite"]["all"] ) ){
            $qa = $user_inputs["infinite"]["all"];
            $lq = end( $qa );
          }

          if ( $page_object_name == "ugc_playlist_single" ){
            if ( $page_object_hash ){
              $playlist = bof()->object->ugc_playlist->select(
                array(
                  "hash" => $page_object_hash
                ),
                array(
                  "clean" => false
                )
              );
            }
            
          } else {
            $purl = parse_url( urldecode( $page_object_url ), PHP_URL_QUERY );
            if ( $purl ){
              parse_str( $purl, $pq );
              if ( !empty( $pq["slug"] ) ){
                $playlist = bof()->object->ugc_playlist->select(
                  array(
                    "seo_url" => $pq["slug"]
                  ),
                  array(
                    "clean" => false
                  )
                );
              }
            }
          }

          if ( !empty( $playlist ) ){
            $lq_item = bof()->object->__get( $lq["object_type"] )->shash( $lq["object_hash"], [ "clean" => false ] );
            if ( $lq_item ){

              $lq_item_i_in_playlist = bof()->db->_select( array(
                "table" => "_u_properties",
                "where" => array(
                  [ "type", "=", "playlist" ],
                  [ "object_name", "=", $lq["object_type"] ],
                  [ "object_id", "=", $lq_item["ID"] ],
                  [ "related_object_name", "=", "ugc_playlist" ],
                  [ "related_object_id", "=", $playlist["ID"] ],
                ),
                array(
                  "single" => true,
                  "limit" => 1,
                  "order_by" => "i",
                  "order" => "ASC"
                )
              ) );

              if ( $lq_item_i_in_playlist ){
                $_objectName = "ugc_property";
                $_whereArray = array(
                  "type" => "playlist",
                  "related_object_name" => "ugc_playlist",
                  "related_object_id" => $playlist["ID"],
                  [ "i", "<", $lq_item_i_in_playlist[0]["i"] ]
                );
                $_selectArray = array(
                  "order_by" => "i",
                  "order" => "DESC",
                  "limit" => 10,
                  "disable_infinite_pagination" => true
                );
                $_offSet = 0;
              }

            }
          }


        }
      }
      // PageBuilder Widgets
      elseif ( $widget_id && $page_object_type && $page_object_hash ){

        $page_object = bof()->object->__get( $page_object_type );
        $page_item = $page_object->select(
          array(
            "hash" => $page_object_hash
          ),
          array(
            "client_widget" => true
          )
        );

        if ( $page_item ){

          if ( $page_object->method_exists( "clean_client_single_widget" ) ){
            $page_widget = $page_object->clean_client_single_widget( $page_item, $widget_id, [], "muse_infinite" );
            if ( !empty( $page_widget ) ){
              $_objectName = $page_widget["object"]["name"];
              $_whereArray = $page_widget["object"]["whereArray"];
              $_selectArray = $page_widget["object"]["selectArray"];
            }
          }

        }

      }
      // User Library
      elseif ( $widget_id === "history" && substr( $page_object_url, 0, strlen( "user_library?tab=history" ) ) === "user_library?tab=history" && bof()->user->get()->ID ){

        $_objectName = "ugc_action";
        $_whereArray = array(
          "user_id" => bof()->user->get()->ID,
          "type" => "stream",
        );
        $_selectArray = array(
          "limit" => 10,
          "order_by" => "time_add",
          "order" => "DESC"
        );
        $_groupBy = "GROUP BY object_name,object_id";

      }
      elseif ( $widget_id === "likes" && substr( $page_object_url, 0, strlen( "user_library?tab=likes" ) ) === "user_library?tab=likes" && bof()->user->get()->ID ){

        $_objectName = "ugc_property";
        $_whereArray = array(
          "user_id" => bof()->user->get()->ID,
          "type" => "like",
        );
        $_selectArray = array(
          "limit" => 10,
          "order_by" => "time_add",
          "order" => "DESC"
        );

      }
      // PageBuilder Widget Liting
      elseif ( substr( $page_object_url, 0, 5 ) == "list/" ){
        $_m = substr( $page_object_url, 5 );
        $_m = explode( "?", $_m );
        $_m = reset( $_m );
        if ( $_m ){
          $parse_list = bof()->bofClient->_ol_parse_request( $_m );
          if ( !empty( $parse_list ) ? $parse_list["widget"] : false ){
            $_objectName = $parse_list["widget"]["object"]["name"];
            $_whereArray = $parse_list["widget"]["object"]["whereArray"];
            $_selectArray = $parse_list["widget"]["object"]["selectArray"];
          }
        }

      }

    }

    elseif ( $object_proxied->method_exists("get_infinite_siblings") ){
      $get_infinite_siblings = $object_proxied->get_infinite_siblings( $object_item, $user_inputs, $object_data );
      if ( $get_infinite_siblings ){
        $_objectName = $get_infinite_siblings["object_name"];
        $_whereArray = $get_infinite_siblings["where_array"];
        $_selectArray = $get_infinite_siblings["select_array"];
        $_offSet = !empty( $get_infinite_siblings["offset"] ) ? $get_infinite_siblings["offset"] : null;
      }
    }

    if ( empty( $_objectName ) )
    return false;

    return array(
      "object_name" => $_objectName,
      "where_array" => $_whereArray,
      "select_array" => $_selectArray,
      "group_by" => !empty( $_groupBy ) ? $_groupBy : null,
      "offset" => !empty( $_offSet ) ? $_offSet : null
    );

  }
  public function exe_query( $user_inputs, $object_data, $query_data ){

    extract( $user_inputs, EXTR_PREFIX_ALL, "user_input" );
    extract( $object_data, EXTR_PREFIX_ALL, "object" );
    extract( $query_data, EXTR_PREFIX_ALL, "query" );

    if ( !empty( $query_object_name ) ){

      $_whereArray = !empty( $query_where_array ) ? $query_where_array : [];
      $_selectArray = !empty( $query_select_array ) ? $query_select_array : [];

      $_selectArray["offset"] = !empty( $query_offset ) ? $query_offset : 0;
      if ( $user_input_page_object_page > 1 && empty( $_selectArray["disable_infinite_pagination"] ) ) $_selectArray["offset"] += ( $user_input_page_object_page - 1 ) * 10;
      $_selectArray = array_merge( $_selectArray, [ "public" => true ] );
      $_selectArray = array_merge( $_selectArray, [ "as_widget" => true ] );
      $_selectArray["empty_select"] = true;
      $_selectArray["limit"] = 10;
      if ( $user_input_widget_page ) $_selectArray["offset"] += ( $user_input_widget_page - 1 ) * $_selectArray["limit"];

      if ( !empty( $user_input_queue["by_object"][ $query_object_name ] ) ){
        $_whereArray[] = [ "hash", "NOT IN", "'". implode( "','", $user_input_queue["by_object"][ $query_object_name ] ) . "'", true ];
      }

      if ( !empty( $query_group_by ) )
      $_selectArray["group"] = $query_group_by;

      $items = bof()->object->__get( $query_object_name )->select(
        $_whereArray,
        $_selectArray
      );

      if ( $items ){

        foreach( $items as &$_item ){
          $_item["cover"] = $_item["cover"] ? $_item["cover"]["image_thumb"] : null;
          $_item["ot"] = !empty( $_item["ot"] ) ? $_item["ot"] : $user_input_object_name;
          $_item["hash"] = $_item["raw"]["hash"];
          unset( $_item["raw"] );
        }

        $o = array(
          "items" => $items
        );

        if ( bof()->object->core_setting->get( "debug" ) ){
          $o["whereArray"] = $_whereArray;
          $o["selectArray"] = $_selectArray;
        }

        return $o;

      }

    }


  }
  public function exe_related( $user_inputs, $object_data, $limit ){

    extract( $user_inputs, EXTR_PREFIX_ALL, "user_input" );
    extract( $object_data, EXTR_PREFIX_ALL, "object" );

    if ( empty( $user_input_seed ) ? true : empty( $user_input_seed["all"] ) )
    return;

    $seeds = $user_input_seed["all"];
    $seed_count = count( $seeds );
    $maximum_seeds = 4;
    $minimum_per_seed = 5;
    $maximum_items = $maximum_seeds * $minimum_per_seed;

    $per_item = ceil( $maximum_items / $seed_count );
    if ( $per_item < $minimum_per_seed ) $per_item = $minimum_per_seed;
    $item_count = $maximum_items / $per_item;

    $random_is = [ rand( 0, $seed_count - 1 ) ];
    while( count( $random_is ) < $item_count ){
      $_rand = rand( 0, $seed_count - 1 );
      if ( !in_array( $_rand, $random_is, true ) )
      $random_is[] = $_rand;
    }

    foreach( $random_is as $random_i ){

      $seed = $seeds[ $random_i ];
      $seed_item = bof()->object->__get( $seed["object_type"] )->select(
        array(
          "hash" => $seed["object_hash"]
        ),
        array(
          "muse_infinite_related" => true
        )
      );

      if ( !$seed_item ) return;

      if ( !bof()->object->__get( $seed["object_type"] )->method_exists( "get_infinite_related" ) ) continue;

      $seed_related_items = bof()->object->__get( $seed["object_type"] )->get_infinite_related( $seed_item, array(
        "per_item" => $per_item,
        "queue" => $user_input_queue,
        "infinite" => $user_input_infinite,
        "related" => !empty( $related_items ) ? $related_items : null
      ) );

      if ( $seed_related_items ){
        // shuffle( $seed_related_items );
        $related_items = !empty( $related_items ) ? array_merge( $related_items, $seed_related_items ) : $seed_related_items;
      }

    }

    if ( !empty( $related_items ) ){
      foreach( $related_items as &$related_item ){
        $related_item = array(
          "title" => $related_item["title"],
          "sub_data" => $related_item["sub_data"],
          "cover" => $related_item["cover"],
          "ot" => $related_item["ot"],
          "hash" => $related_item["raw"]["hash"]
        );
      }
    }

    return !empty( $related_items ) ? $related_items : false;

  }

}

?>
