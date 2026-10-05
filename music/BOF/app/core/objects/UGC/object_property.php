<?php

if ( !defined( "bof_root" ) ) die;

class object_ugc_property extends bof_type_object {

  public function bof(){
    return array(
      "name" => "ugc_property",
      "label" => "User Property",
      "icon" => "key",
      "db_table_name" => "_u_properties",
    );
  }
  public function columns(){
    return array(
      "user_id" => array(
        "public" => true,
        "label" => "User",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_dir_user"]["bof_file_avatar"] ) )
              $displayData["image_preview"] = $item["bof_dir_user"]["bof_file_avatar"]["image_thumb"];
              $displayData["data"] = $item["bof_dir_user"]["name_styled"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "col_user" => array(
              "title" => "User(s)",
              "input" => array(
                "name" => "col_user",
                "type" => "bof_input",
              ),
              "bofInput" => array(
                "object",
                array(
                  "type" => "user",
                  "multi" => true,
                  "args" => array(
                    "filter" => "col_user",
                  )
                )
              )
            ),
          ),
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => false
          )
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "user_subs",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
        "selectors" => array(
          "col_user" => [ "user_id", "by_column" ],
          "user_id" => [ "user_id", "=" ]
        )
      ),
      "type" => array(
        "public" => true,
        "label" => "Type",
        "validator" => array(
          "in_array",
          array(
            "values" => [ "like", "playlist", "playlist_k", "purchase", "subscribe", "upload", "pl_collab" ]
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "tag"
          ),
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "type" => array(
              "title" => "Type",
              "input" => array(
                "name" => "type",
                "type" => "select",
                "options" => array(
                  [ "__all__", "All" ],
                  [ "like", "Like" ],
                  [ "playlist", "Playlist-item" ],
                  [ "playlist_k", "Playlist-subs" ],
                  [ "pl_collab", "Playlist-collab" ],
                  [ "subscribe", "Relations" ],
                  [ "purchase", "Purchase" ],
                  [ "upload", "Upload" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "like", "playlist", "playlist_k", "pl_collab", "purchase", "upload", "subscribe" ]
                )
              )
            )
          ),
        ),
        "input" => array(
          "type" => "select",
          "options" => array(
            [ "like", "Like" ],
            [ "playlist", "Playlist-item" ],
            [ "purchase", "Purchase" ],
            [ "create", "Upload" ],
          )
        ),
        "selectors" => array(
          "type" => [ "type", "=" ],
        )
      ),
      "object_name" => array(
        "public" => true,
        "label" => "Object Name",
        "validator" => array(
          "string",
          array(
            "regex" => "[a-zA-Z0-9\-_]"
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "label" => "Target<br>Object",
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){
              if ( !empty( $item["property"] ) ){
                $displayData["image_preview"] = $item["property"]["cover"] ? $item["property"]["cover"]["image_thumb"] : false;
                $displayData["data"] = $item["property"]["title"] . ( !empty( $item["property"]["sub_data"] ) ? "<span class='sub'>{$item["property"]["sub_data"]}</span>" : "" );
              }
              return $displayData;
            },
          ),
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "text",
        ),
        "selectors" => array(
          "object_name" => [ "object_name", "=" ],
          "col_object_name" => [ "object_name", "by_column" ]
        )
      ),
      "object_id" => array(
        "public" => true,
        "label" => "Object ID",
        "validator" => "int",
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "digit",
        ),
        "selectors" => array(
          "object_id" => [ "object_id", "=" ],
        )
      ),
      "related_object_name" => array(
        "public" => true,
        "label" => "Related Object Name",
        "validator" => array(
          "string",
          array(
            "regex" => "[a-zA-Z0-9\-_]",
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "text",
        ),
        "selectors" => array(
          "related_object_name" => [ "related_object_name", "=" ],
          "col_related_object_name" => [ "related_object_name", "by_column" ]
        )
      ),
      "related_object_id" => array(
        "public" => true,
        "label" => "Related Object ID",
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          )
        ),
        "input" => array(
          "type" => "digit",
        ),
        "selectors" => array(
          "related_object_id" => [ "related_object_id", "=" ],
        )
      ),
      "i" => array(
        "public" => true,
        "label" => "Index",
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
        "input" => array(
          "type" => "digit",
        ),
        "selectors" => array(
          "i" => [ "i", "=" ],
        )
      ),
      "extra_data" => array(
        "label" => "ExtraData",
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          )
        ),
      ),
    );
  }
  public function bof_columns(){
    return array(
      "hash",
      "ID",
      "time_add"
    );
  }
  public function selectors(){
    return array(
      "user_id" => [ "user_id", "=" ],
    );
  }
  public function relations(){
    return array(

      "user_likes" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_likes",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "user_id",
          "child_object_where_array" => array(
            "type" => "like"
          ),
          "delete_child_too" => true
        ),
      ),
      "user_subscriptions" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_subscriptions",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "user_id",
          "child_object_where_array" => array(
            "type" => "subscribe",
            [ "object_name", "!=", "user" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "user_followings" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_followings",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "user_id",
          "child_object_where_array" => array(
            "type" => "subscribe",
            [ "object_name", "=", "user" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "user_followers" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user",
          "parent_object_stats_column" => "s_followers",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "subscribe",
            [ "object_name", "=", "user" ]
          ),
          "delete_child_too" => true
        ),
      ),

      "playlist_items" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "ugc_playlist",
          "parent_object_stats_column" => "s_items",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "related_object_id",
          "child_object_where_array" => array(
            "type" => "playlist",
            [ "related_object_name", "=", "ugc_playlist" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "playlist_subscribers" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "ugc_playlist",
          "parent_object_stats_column" => "s_subscribers",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "playlist_k",
            [ "object_name", "=", "ugc_playlist" ]
          ),
          "delete_child_too" => true
        ),
      ),

    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "user_property",
        "list_page_url" => "user_properties",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
    );
  }
  public function bof_client(){
    return array(
      "single_url_prefix" => "ugc_property",
      "buttons" => array(
        "link" => true,
      )
    );
  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = null;
    $listing = null;
    $deleting = null;
    $editing = null;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing ){
      $_eq["user"] = [ "_eq" => [ "avatar" => [] ] ];
      $selectArgs["get_object_item"] = true;
    }

    $selectArgs["_eq"] = $_eq;

    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function insert( $insertArray ){

    $insertArray["hash"] = !empty( $insertArray["hash"] ) ? $insertArray["hash"] : $this->_bof_this->get_free_hash();

    $insert = bof()->object->_insert( $this, $insertArray );

    if ( $insertArray["type"] == "playlist" && $insertArray["related_object_name"] == "ugc_playlist" && !empty( $insertArray["related_object_id"] ) )
    bof()->object->ugc_playlist->catch_type( $insertArray["related_object_id"] );

    return $insert;

  }

  public function clean( $item, $args ){

    $get_object_item = true;
    $library_page = false;
    extract( $args );

    if ( $get_object_item ){

      if ( bof()->object->core_files->validate_key( "object", $item["object_name"] ) ){
        $property_object = bof()->object->__get( $item["object_name"] );
        $property_item = $property_object->select(
          array(
            "ID" => $item["object_id"]
          ),
          array(
            "as_widget" => true,
            "_eq" => array(
              "cover" => []
            )
          )
        );
      }

      if ( !empty( $property_item ) ){
        $property_item["ot"] = $item["object_name"];
        if ( !empty( $property_item["raw"] ) ){
          $property_item["buttons"] = bof()->bofClient->__parse_item_buttons(
            $item["object_name"],
            $property_object,
            $property_item["raw"],
            $property_object->bof_client()["buttons"]
          );
        }
      }
      else {
        return false;
      }

      $item["property"] = $property_item;

    }

    return $item;

  }
  public function clean_as_widget( $item, $args ){

    $library_page = false;
    extract( $args );

    return array(
      "title"    => $item["property"]["title"],
      "sub_data" => !empty( $item["property"]["sub_data"] ) ? $item["property"]["sub_data"] : null,
      "sub_link" => !empty( $item["property"]["sub_link"] ) ? $item["property"]["sub_link"] : null,
      "cover"    => !empty( $item["property"]["cover"] ) ? $item["property"]["cover"] : null,
      "raw"      => $item["property"]["raw"],
      "ot"       => $item["object_name"],
      "on"       => bof()->object->language->turn( $item["object_name"], [], [ "uc_first" => true, "lang" => "users" ] ),
      "buttons"  => $item["property"]["buttons"],
      "hash"     => $item["property"]["raw"]["hash"]
    );

  }

  public function owned( $object_name, $object_item, $include_subs=false, $multiple_purchase=false ){

    $the_object = bof()->object->__get( $object_name );
    $purchase_args = !empty( $the_object->bof_columns()["price"] ) ? $the_object->bof_columns()["price"] : [];

    $object_item["ot"] = $object_name;
    $item_purchasable = $object_item;

    $has_parent = !empty( $purchase_args["parent"] );
    $force_free = empty( $object_item["price"] ) && $has_parent && !empty( $object_item["price_setting_decoded"]["disable_parent"] );
    $exclude_subs = !empty( $object_item["price_setting_decoded"]["disable_subs"] );

    if ( $has_parent ){

      $parent_object_type = $the_object->columns()[ $purchase_args["parent"] ]["bofInput"][1]["type"];
      $parent_object_id = $object_item[ $purchase_args["parent"] ];
      $parent = bof()->object->__get( $parent_object_type )->select( [ "ID" => $parent_object_id ], [ "purchase" => true, "purchase_check" => true, "_eq" => [ "cover" => [] ] ] );
      if ( !empty( $parent ) )
      $parent["ot"] = $parent_object_type;

      if ( empty( $parent ) ){
      }
    
      else if ( empty( $object_item["price"] ) && !$force_free && $parent["price"] )
      $item_purchasable = $parent;

    }

    if ( !bof()->user->get()->logged )
    return array(
      "item"   => $item_purchasable,
      "parent" => !empty( $parent ) ? $parent : null,
      "purchasable" => $item_purchasable,
      "price"  => !empty( $item_purchasable["price"] ) ? $item_purchasable["price"] : null,
      "access" => $force_free ? true : ( !empty( $item_purchasable["price"] ) ? false : true )
    );

    if ( !empty( $item_purchasable["price"] ) ){

      $purchased = bof()->object->ugc_property->select(array(
        "user_id" => bof()->user->get()->ID,
        "type" => "purchase",
        "object_name" => $item_purchasable["ot"],
        "object_id" => $item_purchasable["ID"]
      ));

    }

    $access = $force_free ? true : ( !empty( $item_purchasable["price"] ) ? $purchased : true );

    if ( $include_subs && !$access && !$exclude_subs ){

      $access_by_subs_plan = bof()->object->user_role->has_access( bof()->user->get()->extra["roles"], array(
        "object_item" => $object_item,
        "object_name" => $object_name,
        "object_hash" => $object_item["hash"],
      ) );

      if ( $access_by_subs_plan )
      $access = true;

    }

    return array(
      "item"   => $item_purchasable,
      "parent" => !empty( $parent ) ? $parent : null,
      "purchasable" => ( $access && !$multiple_purchase ) ? null : $item_purchasable,
      "price"  => !empty( $item_purchasable["price"] ) ? $item_purchasable["price"] : null,
      "access" => $access
    );

  }

  public function purchase_subs_plan_recurring( $plan, $user_id=null ){

    if ( !$user_id )
    $user_id = bof()->user->get()->ID;

    $already_subscribed = bof()->object->user_subs->select( array(
      "user_id" => $user_id,
      "subs_plan_id" => $plan["ID"],
      "has_time" => 1
    ) );

    if ( $already_subscribed || !empty( $plan["free"] ) )
    throw new Exception("have_access_already");

    $_ss_to_a = array(
      "weekly" => array("unit" => "week", "quantity" => 1),
      "monthly" => array("unit" => "month", "quantity" => 1),
      "3months" => array("unit" => "month", "quantity" => 3),
      "6months" => array("unit" => "month", "quantity" => 6),
      "yearly" => array("unit" => "year", "quantity" => 1),
      "2years" => array("unit" => "year", "quantity" => 2),
    );

    $get_link = bof()->pgt->setup()->get_link(
      "stripe",
      $plan["_prices"]["final_raw"][$plan["period"]],
      array(
        "type" => "user_subs_plan_rec",
        "period" => $plan["period"],
        "hook" => $plan["hash"],
      ),
      array(
        "recurring" => $_ss_to_a[$plan["period"]],
        "title" => $plan["name"]
      )
    );

    if ( !$get_link[0] )
    throw new Exception("getting_link_failed: {$get_link[1]}");

    return $get_link[1]["link"];

  }
  public function purchase( $object_name, $object_item, $user_id=null, $args=[] ){

    $multiple_purchase = false;
    extract( $args );

    if ( !$user_id )
    $user_id = bof()->user->get()->ID;

    if ( $object_name == "user_subs_plan" ){

      $item_property_access["purchasable"] = $object_item;
      $already_subscribed = bof()->object->user_subs->select( array(
        "user_id" => $user_id,
        "subs_plan_id" => $object_item["ID"],
        "has_time" => 1
      ) );

      if ( $already_subscribed || !empty( $object_item["free"] ) )
      throw new Exception("have_access_already");

    }
    else {

      $item_property_access = bof()->object->ugc_property->owned( $object_name, $object_item, false, $multiple_purchase );

      if ( !$item_property_access["purchasable"] )
      throw new Exception("failed");

      if ( $item_property_access["access"] && !$multiple_purchase )
      throw new Exception("have_access_already");

    }

    $user_data = bof()->object->user->select(
      array(
        "ID" => $user_id
      ),
      array(
        "cache_load_rt" => false,
        "cache_load" => false,
        "cache" => false
      )
    );

    if ( $user_data["fund"] < $item_property_access["purchasable"]["price_d"] )
    throw new Exception("insufficient_fund");

    $transaction = bof()->object->user->remove_fund(
      $user_id,
      $item_property_access["purchasable"]["price_d"],
      array(
        "type" => "buy",
        "object_type" => $item_property_access["purchasable"]["ot"],
        "object_id" => $item_property_access["purchasable"]["ID"],
        "revenue" => $item_property_access["purchasable"]["price_d"]
      )
    );

    bof()->chapar->notify_admin( "new_purchase", array(
      "user_id" => $user_id,
      "item_id" => $object_item["ID"],
      "item_title" => !empty( $object_item["title"] ) ? $object_item["title"] : $object_item["name"],
      "object_name" => bof()->object->__get( $object_name )->bof()["label"],
      "amount" => $item_property_access["purchasable"]["price_d"]
    ) );

    if ( !empty( $item_property_access["purchasable"]["manager"] ) ){
      $manager = $item_property_access["purchasable"]["manager"];
      if ( !empty( $manager["manager_role"] ) ){

        $fixed_fee = !empty( $manager["manager_role"]["fixed_fee"] ) ? $manager["manager_role"]["fixed_fee"] : 0;
        $dyna_fee  = !empty( $manager["manager_role"]["dyna_fee"] ) ? $manager["manager_role"]["dyna_fee"] : 0;

        $calculate_fee = $fixed_fee + ( $dyna_fee ? ( $dyna_fee / 100 * $item_property_access["purchasable"]["price_d"] ) : 0 );
        $calculate_fee = round( $calculate_fee, 1 );
        if ( $calculate_fee > $item_property_access["purchasable"]["price_d"] )
        $calculate_fee = $item_property_access["purchasable"]["price_d"];

        $manager_share = $item_property_access["purchasable"]["price_d"] - $calculate_fee;

        bof()->object->user->add_fund(
          $manager["ID"],
          $manager_share,
          array(
            "type" => "sell",
            "object_type" => $item_property_access["purchasable"]["ot"],
            "object_id" => $item_property_access["purchasable"]["ID"],
            "revenue" => ( -1 * $manager_share ),
            "text" => "Share from sale #{$transaction}",
          )
        );

        bof()->chapar->notify_admin("new_purchase_share", array(
          "user_id" => $manager["ID"],
          "item_id" => $object_item["ID"],
          "item_title" => !empty( $object_item["title"] ) ? $object_item["title"] : $object_item["name"],
          "object_name" => bof()->object->__get( $object_name )->bof()["label"],
          "amount" => $manager_share,
          "role" => "manager"
        ));

      }
    }

    if ( $object_name == "user_subs_plan" ){

      $_rid = bof()->object->user_subs->insert( array(
        "user_id" => $user_id,
        "subs_plan_id" => $object_item["ID"],
        "subs_plan_time_range" => $object_item["period"],
        "subs_plan_price" => $object_item["price_d"],
      ) );

      bof()->chapar->notify( "plan_purchased", array(
        "source" => array(
          "object" => null,
          "id" => null
        ),
        "triggerer" => array(
          "object" => "user_subs",
          "id" => $_rid
        ),
        "message" => array(
          "params" => [ "plan_name" => $object_item["name"], "plan_period" => $object_item["period"] ],
          "link" => "user_edit?tab=transactions"
        ),
      ) );

    }
    else {

      $_rid = bof()->object->ugc_property->insert(array(
        "user_id" => $user_id,
        "type" => "purchase",
        "object_name" => $item_property_access["purchasable"]["ot"],
        "object_id" => $item_property_access["purchasable"]["ID"],
        "extraData" => json_encode( array(
          "transaction_id" => $transaction
        ) )
      ));

      bof()->chapar->notify( "item_purchased", array(
        "source" => array(
          "object" => null,
          "id" => null
        ),
        "triggerer" => array(
          "object" => "ugc_property",
          "id" => $_rid
        ),
        "message" => array(
          "params" => [ "item_name" => $item_property_access["purchasable"]["title"] ],
          "link" => !empty( $item_property_access["purchasable"]["url"] ) ? $item_property_access["purchasable"]["url"] : "user_edit?tab=transactions"
        ),
      ) );

      if ( !empty( $manager["manager_role"] ) ){

        bof()->chapar->notify( "item_sold", array(
          "target" => array(
            "user_id" => $manager["ID"]
          ),
          "source" => array(
            "object" => null,
            "id" => null
          ),
          "triggerer" => array(
            "object" => "ugc_property",
            "id" => $_rid
          ),
          "message" => array(
            "params" => [ "item_name" => $item_property_access["purchasable"]["title"], "amount" => $item_property_access["purchasable"]["price"], "share" => $manager_share ],
            "link" => "user_edit?tab=transactions"
          ),
        ) );

      }

      // Increase sales count
      $cff = bof()->general->get_full_fall();
      bof()->general->set_full_fall( false );
      try {
        $dtn = bof()->object->__get( $item_property_access["purchasable"]["ot"] )->bof()["db_table_name"];
        bof()->db->_update(array(
          "table" => $dtn,
          "set" => array(
            [ "s_sales", "s_sales + 1", true ]
          ),
          "where" => array(
            [ "ID", "=", $item_property_access["purchasable"]["ID"] ]
          )
        ));
      } catch( bofException|Exception $err ){}
      bof()->general->set_full_fall( $cff );

    }

    return array(
      "rid" => $_rid,
      "purchasable" => $item_property_access["purchasable"]
    );

  }

}

?>
