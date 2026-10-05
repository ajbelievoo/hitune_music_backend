<?php

if ( !defined( "bof_root" ) ) die;

class object_payment extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "payment",
      "label" => "Payment",
      "icon" => "payment",
      "db_table_name" => "_u_payments"
    );
  }
  public function columns(){
    return array(
      "_num" => array(
        "validator" => "string",
      ),
      "_key" => array(
        "validator" => "md5",
      ),
      "mode" => array(
        "label" => "Mode",
        "validator" => array(
          "in_array",
          array(
            "values" => [ "sub", "pay" ]
          )
        )
      ),
      "user_id" => array(
        "label" => "User",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
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
              "parent_object_stats_column" => "s_payments",
              "child_object" => "payment",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "amount" => array(
        "label" => "Amount",
        "validator" => "float",
        "bofAdmin" => array(
          "list" => array(
            "type" => "currency"
          )
        ),
      ),
      "currency" => array(
        "label" => "Currency",
        "validator" => "string",
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
      "sub_id" => array(
        "validator" => array(
          "string",
          array(
            "empty()",
          ),
        ),
      ),
      "gateway_name" => array(
        "label" => "Gateway<br>Name",
        "validator" => "string_abcd",
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag"
          )
        )
      ),
      "gateway_id" => array(
        "validator" => array(
          "string",
          array(
            "empty()",
          ),
        ),
      ),
      "gateway_amount" => array(
        "validator" => "float",
      ),
      "gateway_currency" => array(
        "label" => "Currency",
        "validator" => "string",
      ),
      "gateway_data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          ),
        ),
      ),
      "gateway_req_data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          ),
        ),
      ),
      "purchase_data" => array(
        "label" => "Direct<br>Purchase",
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "label" => "Direct<br>Purchase",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = "-";
              if ( !empty( $item["purchase_data_decoded"]["type"] ) ){
                $plan = bof()->object->user_subs_plan->sid($item["purchase_data_decoded"]["ID"]);
                $displayData["data"] = "Subscription";
                $displayData["sub_data"] = "<b>".($plan?$plan["name"]:"?")."</b> - {$item["purchase_data_decoded"]["period"]}";
              }
              return $displayData;
            },
          )
        )
      ),
      "paid" => array(
        "label" => "Paid",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean_d"
          )
        ),
      ),
      "approved" => array(
        "label" => "Approved",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => [ "approve", "unapprove" ],
            ),
          )
        )
      ),
      "time_pay" => array(
        "label" => "Payment<br>Time",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "time_f",
          ),
        ),
      ),
      "time_approve" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "time_reject" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "time_recur" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
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
      "_num"        => [ "_num", "=" ],
      "_key"        => [ "_key", "=" ],
      "user_id"     => [ "user_id", "=" ],
      "gateway_id"  => [ "gateway_id", "=" ],
      "gateway_name"  => [ "gateway_name", "=" ],
      "sub_id"      => [ "sub_id", "=" ],
      "paid"        => [ "paid", "=" ],
      "approved"    => [ "approved", "=" ],
      "query"       => [ "name", "LIKE%lower" ],
      "col_user"    => [ "user_id", "by_column" ]
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => false,
        "edit" => false,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "payment",
        "list_page_url" => "payments",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "buttons" => array(
        "mark_paid" => array(
          "id" => "mark_paid",
          "label" => "Mard as paid",
          "payload" => array(
            "post" => array(
              "__action" => "mark_paid"
            )
          )
        ),
        "mark_unpaid" => array(
          "id" => "mark_unpaid",
          "label" => "Mard as unpaid",
          "payload" => array(
            "post" => array(
              "__action" => "mark_unpaid"
            )
          )
        ),
        "approve" => array(
          "id" => "approve",
          "label" => "Approve",
          "payload" => array(
            "post" => array(
              "__action" => "approve"
            )
          )
        ),
        "unapprove" => array(
          "id" => "unapprove",
          "label" => "Reject",
          "payload" => array(
            "post" => array(
              "__action" => "unapprove"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["approved"] ){
          unset( $buttons["approve"] );
          unset( $buttons["mark_paid"] );
          unset( $buttons["mark_unpaid"] );
          unset( $buttons["delete"] );
        }
        else {

          unset( $buttons["unapprove"] );
          if ( $item["paid"] )
          unset( $buttons["mark_paid"] );
          else{
            unset( $buttons["mark_unpaid"] );
            unset( $buttons["approve"] );
          }

        }

        return $buttons;

      },
      "filters" => array(

        "paid" => array(
          "title" => "Paid",
          "input" => array(
            "name" => "paid",
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "1", "Yes" ],
              [ "0", "no" ]
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "0", "1", "__all__" ] ]
          )
        ),

        "sub_id" => array(
          "title" => "Sub ID",
          "input" => array(
            "type" => "text",
            "name" => "sub_id",
          ),
          "validator" => array(
            "string",
            array(
              "empty()"
            )
          )
        ),

        "approved" => array(
          "title" => "Approved",
          "input" => array(
            "name" => "approved",
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "1", "Yes" ],
              [ "0", "no" ]
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "0", "1", "__all__" ] ]
          )
        ),

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
      "list" => array(
        "ID" => array(
          "type" => "simple",
          "label" => "ID",
        ),
        "user_id" => null,
        "amount" => null,
        "gateway_name" => null,
        "purchase_data" => null,
        "paid" => null,
        "approved" => null,
        "time_pay" => null,
      ),
      "actions" => array(
        "mark_paid" => function( $ids ){
          bof()->object->payment->update(
            array(
              "ID_in" => $ids,
              "paid" => 0
            ),
            array(
              "paid" => 1
            )
          );
          return [ 1, "Marked" ];
        },
        "mark_unpaid" => function( $ids ){
          bof()->object->payment->update(
            array(
              "ID_in" => $ids,
              "paid" => 1
            ),
            array(
              "paid" => 0
            )
          );
          return [ 1, "Marked" ];
        },
        "approve" => function( $ids ){
          bof()->object->payment->_approve( $ids );
          return [ 1, "Marked" ];
        },
        "unapprove" => function( $ids ){
          bof()->object->payment->_reject( $ids );
          return [ 1, "Marked" ];
        },
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

    if ( $listing )
    $_eq[ "user" ] = [ "_eq" => [ "avatar" => [] ] ];

    if ( $deleting )
    $whereArgs[] = [ "approved", "!=", "1" ];

    $selectArgs[ "_eq" ] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }

  public function _approve( $ids ){

    $ids = is_array( $ids ) ? $ids : [ $ids ];

    foreach( $ids as $id ){

      $id_data = bof()->object->payment->select(["ID"=>$id]);
      if ( $id_data ? !$id_data["approved"] : false ){

        bof()->object->user->add_fund( $id_data["user_id"], $id_data["amount"], array(
          "object_type" => "payment",
          "object_id" => $id,
          "text" => "Payment #{$id_data["ID"]} approved"
        ) );

        // mark payment
        bof()->object->payment->update(array(
          "ID" => $id,
        ),array(
          "approved" => 1,
          "paid" => 1,
          "time_approve" => bof()->general->mysql_timestamp(),
          "time_reject" => null
        ));

        if ( !empty( $id_data["purchase_data_decoded"] ) ){

          $plan = bof()->object->user_subs_plan->sid( $id_data["purchase_data_decoded"]["ID"] );
          $plan_period_price = $plan["_prices"]["final"][ $id_data["purchase_data_decoded"]["period"] ];

          $plan["ot"] = "user_subs_plan";
          $plan["price_d"] = $plan_period_price;
          $plan["period"] = $id_data["purchase_data_decoded"]["period"];

          try {
            $subID = bof()->object->ugc_property->purchase( "user_subs_plan", $plan, $id_data["user_id"] );
          } catch( Exception $err ){}

          if ( !empty( $subID["rid"] ) ){
            $id_data["purchase_data_decoded"]["sub_id"] = $subID["rid"];
            $this->_bof_this->update(array(
              "ID" => $id
            ),array(
              "purchase_data" => json_encode( $id_data["purchase_data_decoded"] )
            ),array(
              "cache_load_rt" => false
            ));
            bof()->object->user_subs->update(
              array(
                "ID" => $subID["rid"]
              ),
              array(
                "payment_id" => $id,
                "payment_time" => bof()->general->mysql_timestamp(),
                "payment_count" => 1,
                "gateway_name" => $id_data["gateway_name"],
                "gateway_sub_id" => !empty( $id_data["sub_id"] ) ? $id_data["sub_id"] : null,
                "gateway_time_recur" => !empty( $id_data["time_recur"] ) ? $id_data["time_recur"] : null,
              )
            );
          }

        }

        $s = bof()->chapar->notify( "payment_ok", array(
          "target" => array(
            "user_id" => $id_data["user_id"]
          ),
          "source" => array(
            "object" => "payment",
            "id" => $id
          ),
          "triggerer" => array(
            "object" => null,
            "id" => null
          ),
          "message" => array(
            "params" => array(
              "order_id" => $id_data["_num"],
              "amount" => "{$id_data["amount"]} {$id_data["currency"]}"
            ),
            "link" => "user_edit?tab=transactions"
          ),
        ) );

        bof()->chapar->unnotify( array(
          "source_object" => "payment",
          "source_id" => $id,
          "hook" => "payment_rejected",
          "user_id" =>$id_data["user_id"]
        ) );

      }

    }

  }
  public function _reject( $ids ){

    $ids = is_array( $ids ) ? $ids : [ $ids ];

    foreach( $ids as $id ){
      $id_data = bof()->object->payment->select(["ID"=>$id]);
      if ( $id_data ? $id_data["approved"] : false ){

        $approve_transaction = bof()->object->transaction->select(array(
          "user_id" => $id_data["user_id"],
          "object_type" => "payment",
          "object_id" => $id,
          "type" => "deposit",
        ),array(
          "order_by" => "time_add",
          "order" => "DESC"
        ));

        $text = $approve_transaction ? "Payment #{$id_data["ID"]} Transaction #{$approve_transaction["ID"]} reversed" : "Payment #{$id_data["ID"]} rejected";

        if ( !empty( $id_data["purchase_data_decoded"] ) ){
          if ( !empty( $id_data["purchase_data_decoded"]["sub_id"] ) ){
            bof()->object->user_subs->delete(array(
              "ID" => $id_data["purchase_data_decoded"]["sub_id"]
            ));
          }
        }
        else {

          bof()->object->user->remove_fund( $id_data["user_id"], $id_data["amount"], array(
            "object_type" => "payment",
            "object_id" => $id,
            "text" => $text
          ) );

        }


        // mark payment
        bof()->object->payment->update(array(
          "ID" => $id,
        ),array(
          "approved" => 0,
          "paid" => 0,
          "time_reject" => bof()->general->mysql_timestamp(),
          "time_approve" => null
        ));

        bof()->chapar->notify( "payment_rejected", array(
          "target" => array(
            "user_id" => $id_data["user_id"]
          ),
          "source" => array(
            "object" => "payment",
            "id" => $id
          ),
          "triggerer" => array(
            "object" => null,
            "id" => null
          ),
          "message" => array(
            "params" => array(
              "order_id" => $id_data["_num"],
              "amount" => "{$id_data["amount"]} {$id_data["currency"]}",
              "link" => "user_edit?tab=transactions"
            )
          ),
        ) );

        bof()->chapar->unnotify( array(
          "source_object" => "payment",
          "source_id" => $id,
          "hook" => "payment_ok",
          "user_id" =>$id_data["user_id"]
        ) );

      }
    }

  }

  public function price( $amount ){

  }

}

?>
