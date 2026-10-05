<?php

if ( !defined( "bof_root" ) ) die;

class object_transaction extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "transaction",
      "label" => "Transaction",
      "icon" => "transaction",
      "db_table_name" => "_u_transactions",
    );
  }
  public function columns(){
    return array(
      "user_id" => array(
        "label" => "User",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_dir_user"]["bof_file_avatar"] ) )
              $displayData["image_preview"] = $item["bof_dir_user"]["bof_file_avatar"]["image_thumb"];
              $displayData["data"] = $item["bof_dir_user"]["name_styled"];
              $displayData["data"] .= "<span class='sub'>Email: {$item["bof_dir_user"]["email"]}<br>ID: {$item["bof_dir_user"]["ID"]}</span>";
              return $displayData;
            },
          )
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "parent_object_stats_column" => "s_transactions",
              "child_object" => "transaction",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "user_fund" => array(
        "label" => "User<br>Fund",
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => null
          ),
        ),
      ),
      "amount" => array(
        "label" => "Amount",
        "validator" => array(
          "float",
          array(
            "min" => null
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){

              $aa = number_format( (float) abs( $item["amount"] ), 1 );

              if ( $item["amount"] > 0 )
              $displayData["data"] = "<div class='price_wrapper '><span class='_n'>+{$aa}</span> <span class='currency_wrapper'>{$item["currency"]}</span></div>";

              if ( $item["amount"] < 0 )
              $displayData["data"] = "<div class='price_wrapper '><span class='_n'>-{$aa}</span> <span class='currency_wrapper'>{$item["currency"]}</span></div>";

              return $displayData;

            },
          ),
        ),
      ),
      "currency" => array(
        "validator" => array(
          "string",
          array(
            "strict" => true
          )
        )
      ),
      "type" => array(
        "label" => "Type",
        "validator" => "string_abcd",
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag"
          )
        )
      ),
      "object_type" => array(
        "label" => "Item",
        "validator" => array(
          "string_abcd",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){

              if ( in_array( $item["type"], [ "deposit", "disperse", "withdraw" ], true ) || empty( $item["object_id"] ) )
              return;

              $object_item = $item["object_item"];
              $displayData["data"] = !empty( $object_item["title"] ) ? $object_item["title"] : "?";
              $displayData["data"] .= "<span class='sub'>". $item["object_label"] ."</span>";

              return $displayData;

            },
          )
        )
      ),
      "object_id" => array(
        "validator" => array(
          "int",
          array(
            "empty()"
          ),
        ),
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          ),
        ),
      ),
      "revenue" => array(
        "label" => "Your<br>Revenue",
        "validator" => array(
          "float",
          array(
            "min" => null,
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){

              $aa = abs( $item["revenue"] );

              if ( $item["revenue"] > 0 )
              $displayData["data"] = "<div class='price_wrapper up'><span class='_n'>+{$aa}</span></div>";

              if ( $item["revenue"] < 0 )
              $displayData["data"] = "<div class='price_wrapper down'><span class='_n'>-{$aa}</span></div>";

              return $displayData;

            },
          )
        )
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
    );
  }
  public function selectors(){
    return array(
      "user_id"     => [ "user_id", "=" ],
      "col_user"    => [ "user_id", "by_column" ],
      "type"        => [ "type", "=" ],
      "object_type" => [ "object_type", "=" ],
      "object_id"   => [ "object_id", "=" ],
      "completed"   => [ "completed", "=" ],
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => false,
        "pagination" => true,
        "edit_page_url" => "transaction",
        "list_page_url" => "transactions",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "filters" => array(
        "type" => array(
          "title" => "Type",
          "tip" => "<b>Deposit</b>: Created when a payment is approved<br>
          <b>Disperse</b>: Created when a previosuly approved payment is rejected<br>
          <b>Withdrawal</b>: Created when a manager's withdarawl request is marked as complete<br>
          <b>Buy</b>: Created when a user purchase is completed<br>
          <b>Sell</b>: Created when a manager gets his share of sales<br>
          <b>Commission:</b> Created when an affiliate gets his share of sales",
          "input" => array(
            "name" => "type",
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "deposit", "Deposit" ],
              [ "disperse", "Disperse" ],
              [ "withdraw", "Withdrawal" ],
              [ "commission", "Commission" ],
              [ "sell", "Sale" ],
              [ "buy", "Buy" ],
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "deposit", "disperse", "withdraw", "commission", "sell", "buy", "__all__" ] ]
          )
        ),
        "col_user" => array(
          "title" => "User",
          "input" => array(
            "name" => "col_user",
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
      "list" => array(
        "ID" => array(
          "label" => "ID",
          "type" => "simple"
        ),
        "user_id" => null,
        "type" => null,
        "amount" => null,
        "revenue" => null,
        "object_type" => null,
        "time_add" => array(
          "type" => "time",
          "label" => "Creation<br>Time"
        )
      ),
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $listing = false;
    $editing = false;
    $deleting = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing ){
      $_eq[ "user" ] = array(
        "_eq" => array(
          "avatar" => []
        )
      );
      $selectArgs["get_item"] = true;
    }

    $selectArgs[ "_eq" ] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function insert( $setArgs ){

    if ( empty( $setArgs["user_fund"] ) )
    $setArgs["user_fund"] = bof()->object->user->select(["ID"=>$setArgs["user_id"]],["cache_load_rt"=>false])["fund"];

    if ( empty( $setArgs["currency"] ) )
    $setArgs["currency"] = bof()->object->currency->get_default()["iso_code"];

    return bof()->object->_insert( $this, $setArgs );

  }
  public function clean( $item, $args=[] ){

    $get_item = false;
    extract( $args );

    if ( $get_item ){

      if ( $item["object_type"] == "payment" ){
        $item["object_item"] = array(
          "title" => ""
        );
        $item["object_label"] = bof()->object->language->turn( "payment", [], [ "uc_first" => true, "lang" => "users" ] );
      }
      elseif ( $item["object_type"] == "user_withdraw" ){
        $item["object_item"] = array(
          "title" => ""
        );
        $item["object_label"] = bof()->object->language->turn( "withdraw", [], [ "uc_first" => true, "lang" => "users" ] );
      }
      elseif ( !empty( $item["object_type"] ) ){
        $the_object = bof()->object->__get( $item["object_type"] );
        $item["object_item"] = $the_object->select(
          array(
            "ID" => $item["object_id"]
          ),
          array(
            "as_widget" => true
          )
        );
        $item["object_label"] = bof()->object->language->turn( $item["object_type"], [], [ "uc_first" => true, "lang" => "users" ] );
      }
      else {
        $item["object_item"] = array(
          "title" => ""
        );
        $item["object_label"] = "";
      }

    }

    return $item;

  }

}

?>
