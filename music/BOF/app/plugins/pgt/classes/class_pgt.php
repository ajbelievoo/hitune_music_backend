<?php

if ( !defined( "bof_root" ) ) die;

class pgt extends bof_type_class {

  protected $gateways = array();

  public function setup(){
    return $this->_bof_this;
  }
  public function active_gateways(){

    $all = $this->gateways;
    if ( !$all ) return;

    foreach( $all as $gtn => $gt_args ){
      if ( bof()->object->db_setting->get( "gateway_{$gt_args["db_name"]}" ) )
      $activated[] = $gtn;
    }

    if ( empty( $activated ) ) return;
    return $activated;

  }
  public function list_gateways(){

    $gts = $this->_bof_this->active_gateways();
    if ( !$gts ) return;

    foreach( $gts as $gt_name ){
      $gt_data = $this->gateways[ $gt_name ];
      if ( empty( $gt_data["title"] ) && !empty( $gt_data["hook"] ) )
      $gt_data["title"] = bof()->object->language->turn( $gt_data["hook"], [], [ "uc_first" => true, "lang" => "users" ] );
      $list[ $gt_name ] = array(
        "title" => $gt_data["title"],
        "code" => $gt_data["code_name"],
        "icon_t" => $gt_data["icon_t"],
        "icon_v" => $gt_data["icon_v"],
        "fee" => bof()->object->db_setting->get( "gateway_{$gt_data["db_name"]}_fee", 0 ),
        "placeholder" => bof()->object->db_setting->get( "gateway_{$gt_data["db_name"]}_def", 100 ),
        "name" => $gt_name,
      );
    }

    return $list;

  }
  public function gateway_add( $name, $args=[] ){
    $args[ "name" ] = $name;
    $this->gateways[ $name ] = $args;
  }

  public function get_link( $gateway, $amount, $purchase_data=null, $args=null ){

    $recurring = false;
    extract( $args ? $args : [] );

    $payment_id = null;

    try {

      $gts = bof()->pgt->active_gateways();
      if ( !$gts ) throw new Exception( "no_active_gateways" );

      if ( !in_array( $gateway, $gts, true ) )
      throw new Exception( "invalid_gateway" );

      // Verify purchase
      if (!empty($purchase_data)) {

        if (!is_array($purchase_data) ? true : empty($purchase_data["type"]) || empty($purchase_data["hook"]) || empty($purchase_data["period"]))
        throw new Exception("invalid_req1");

        if (!bof()->nest->validate($purchase_data["period"], "in_array", ["values" => ["weekly", "monthly", "3months", "6months", "yearly", "2years"]]))
        throw new Exception("invalid_req2");

        if (!bof()->nest->validate($purchase_data["hook"], "md5"))
        throw new Exception("invalid_req4");

        $u_subs_plan = bof()->object->user_subs_plan->shash($purchase_data["hook"]);
        if (!$u_subs_plan)
        throw new Exception("invalid_req5");

        if (!empty($u_subs_plan["_prices"]["final"][$purchase_data["period"]]) ? (
          $u_subs_plan["_prices"]["final"][$purchase_data["period"]] != $amount &&
          $u_subs_plan["_prices"]["final_raw"][$purchase_data["period"]] != $amount
        ) : false)
        throw new Exception("invalid_req6 {$u_subs_plan["_prices"]["final_raw"][$purchase_data["period"]]} != {$amount}");

        if ( !in_array( $purchase_data["type"], [ "user_subs_plan", "user_subs_plan_rec" ], true ) )
        throw new Exception("invalid_req3");

        unset( $purchase_data["hook"] );
        $purchase_data["ID"] = $u_subs_plan["ID"];

      }

      $gt_data = $this->gateways[ $gateway ];
      $gt_fee_per = bof()->object->db_setting->get( "gateway_{$gt_data["db_name"]}_fee", 0 );
      $gt_min_amount = bof()->object->db_setting->get( "gateway_{$gt_data["db_name"]}_min", 0 );
      $gt_fee = $amount * ( $gt_fee_per ? $gt_fee_per / 100 : 0 );
      $gt_fee = $gt_fee ? round( $gt_fee, 4 ) : 0;
      $gt_base_amount = $amount;
      $gt_amount = $amount + $gt_fee;

      if ( $gt_min_amount ? floatval( $gt_min_amount ) > floatval( $gt_base_amount ) : false )
      throw new Exception( bof()->object->language->turn( "less_than_min", [ "min" => $gt_min_amount ], [ "uc_first" => true, "lang" => "users" ] ) );

      if ( !$gt_base_amount )
      throw new Exception( bof()->object->language->turn( "less_than_min", [ "min" => ">0" ], [ "uc_first" => true, "lang" => "users" ] ) );

      // Decide on currency
      $default_currency = bof()->object->currency->get_default();
      $active_currencies = bof()->object->currency->select( array( [ "_default", "=", "0" ], "active" => 1 ), array( "limit" => false,"single" => false ) );

      if ( in_array( $default_currency["iso_code"], $gt_data["supported_currencies"], true ) || in_array( "all", $gt_data["supported_currencies"], true ) )
      $gt_currency = $default_currency;
      elseif ( $active_currencies ){
        foreach( $active_currencies as $active_currency ){
          if ( in_array( $active_currency["iso_code"], $gt_data["supported_currencies"], true ) ){
            $gt_currency = $active_currency;
            break;
          }
        }
      }

      if ( empty( $gt_currency ) )
      throw new Exception( "gt_no_supported_currency" );

      if ( bof()->__get( "pgt_" . $gateway )->method_exists("formatter") ){
        $gt_amount = bof()->__get( "pgt_" . $gateway )->formatter( $gt_amount, $gt_currency );
      } else {
        $gt_amount = round( $gt_amount, 4 );
      }

      if ( $default_currency["iso_code"] != $gt_currency["iso_code"] ){
        $parse_new_currency = bof()->object->currency->parse_price( $gt_amount, array(
          "target_currency" => $gt_currency
        ) );
        $gt_amount = $parse_new_currency["user"]["price"];
      }

      if ( !$gt_amount )
      throw new Exception( bof()->object->language->turn( "less_than_min", [ "min" => ">0" ], [ "uc_first" => true, "lang" => "users" ] ) );


      // Record payment
      $payment_hash = bof()->object->payment->get_free_hash( "_key" );
      $payment_num = substr( md5(time().rand(1,100000000000000)), 0, 12 );
      $payment_id = bof()->object->payment->insert( array(
        "_num" => $payment_num,
        "_key" => $payment_hash,
        "user_id" => bof()->user->check()->ID,
        "amount" => $amount,
        "currency" => $default_currency["iso_code"],
        "mode" => $recurring ? "sub" : "pay",
        "gateway_name" => $gateway,
        "gateway_amount" => $gt_amount,
        "gateway_currency" => $gt_currency["iso_code"],
        "purchase_data" => $purchase_data ? json_encode( $purchase_data ) : null
      ) );

      $extra_args = array(
        "payment_num"   => $payment_num,
        "payment_id"    => $payment_id,
        "user_id"       => bof()->user->check()->ID,
        "customer_email"=> bof()->user->check()->data["email"],
      );
      if ( !empty( $purchase_data["ID"] ) ) $extra_args["plan_id"] = $purchase_data["ID"];
      if ( !empty( $purchase_data["period"] ) ) $extra_args["plan_period"] = $purchase_data["period"];

      $data = bof()->__get( "pgt_" . $gateway )->get_link(
        $gt_amount,
        $gt_currency,
        $payment_hash,
        web_address . "api/payment_result_check/{$gateway}/{$payment_num}/{$payment_hash}/",
        array_merge(
          $args ? $args : [],
          array(
            "gt_data" => $gt_data,
            "gt_fee_per" => $gt_fee_per,
            "gt_fee" => $gt_fee,
            "gt_base_amount" => $gt_base_amount
          ),
          $extra_args
        )
      );

      // HTML-form gateways (e.g. PayU) return an auto-submitting form, not a
      // URL. Persist the form on the payment row and expose it through a
      // public pay_render URL so apps/webview can open it as a real link.
      if ( !empty( $data["output"]["type"] ) && $data["output"]["type"] == "html" && !empty( $data["output"]["content"] ) ){
        $data["output"]["link"] = web_address . "api/pay_render/{$payment_num}/{$payment_hash}/";
        // gateway_req_data column is JSON-validated — wrap the form, don't pass a raw string
        $data["req_data"] = array( "html" => $data["output"]["content"] );
      }

      if ( !empty( $data["txn"] ) || !empty( $data["req_data"] ) )
      bof()->object->payment->update(
        array(
          "ID" => $payment_id
        ),
        array(
          "gateway_id" => !empty( $data["txn"] ) ? $data["txn"] : null,
          "gateway_req_data" => !empty( $data["req_data"] ) ? ( is_string( $data["req_data"] ) ? $data["req_data"] : json_encode( $data["req_data"] ) ) : null
        )
      );

      if ( empty( $data ) ? true : empty( $data["output"] ) )
      throw new Exception( "Failed to get payment link" );

      bof()->chapar->notify_admin("pay_ini_ok", array(
        "gateway_name" => $gateway,
        "amount" => $amount,
        "payment_id" => !empty( $data["output"] ) ? $data["output"] : null,
      ));

      $sta = true;

    } catch( Exception $err ){

      bof()->chapar->notify_admin("pay_ini_failed", array(
        "gateway_name" => $gateway,
        "amount" => $amount,
        "error" => $err->getMessage()
      ));

      $sta = false;
      $data = [ "output" => $err->getMessage() ];

    }

    return array(
      $sta,
      $data["output"],
      $payment_id
    );

  }
  public function check_payment( $gateway, $payment ){

    try {

      $gts = bof()->pgt->active_gateways();
      if ( !$gts ) throw new Exception( "no_active_gateways" );

      if ( !in_array( $gateway, $gts, true ) )
      throw new Exception( "invalid_gateway" );

      $gt_data = $this->gateways[ $gateway ];

      $check_payment = bof()->__get( "pgt_" . $gateway )->check_payment(
        $payment
      );

      if ( $check_payment === "pending" )
      return $check_payment;

      if ( $check_payment["amount"] < $payment["gateway_amount"] )
      throw new Exception( "under_paid: {$check_payment["amount"]} < {$payment["gateway_amount"]} {$payment["gateway_currency"]}" );

      if ( $check_payment["currency"] != $payment["gateway_currency"] )
      throw new Exception( "paid_in_differnt_currency" );

      $updateArray = array(
        "time_pay" => bof()->general->mysql_timestamp(),
        "paid" => 1,
        "gateway_data" => !empty( $check_payment["data"] ) ? json_encode( $check_payment["data"] ) : null
      );

      if ( $payment["mode"] == "sub" ){

        if ( $check_payment["mode"] != "sub" )
        throw new Exception( "single_time_payment" );

        $updateArray["sub_id"] = $check_payment["sub_id"];
        $updateArray["time_recur"] = bof()->general->mysql_timestamp( $check_payment["time_recur"] );

      }

      bof()->object->payment->update(
        array(
          "ID" => $payment["ID"]
        ),
        $updateArray
      );

      bof()->chapar->notify_admin("pay_check_ok", array(
        "gateway_name" => $gateway,
        "payment" => $payment,
        "user_id" => $payment["user_id"]
      ));

      if ( bof()->object->db_setting->get( "gateway_{$gateway}_auto" ) ){
        bof()->object->payment->_approve( $payment["ID"] );
        bof()->chapar->notify_admin("pay_check_approved", array(
          "gateway_name" => $gateway,
          "payment" => $payment,
          "user_id" => $payment["user_id"]
        ));
      }

    } catch( Exception $err ){

      bof()->chapar->notify_admin("pay_check_failed", array(
        "gateway_name" => $gateway,
        "payment" => $payment,
        "user_id" => $payment["user_id"],
        "error" => $err->getMessage()
      ));
      return [ false, $err->getMessage() ];

    }

    return true;

  }
  public function check_subscriptions( $PID, $GID ){

    $active_subs = bof()->object->user_subs->select(
      array(
        "recurring" => true,
      ),
      array(
        "limit" => false,
        "single" => false,
        "clean" => false
      )
    );

    if ( !$active_subs )
    fall( "No active subs", [ "skipped" => true ] );

    $c = $p = $d = 0;

    foreach ($active_subs as $sub) {
      $_sub = bof()->pgt_stripe->check_sub( $sub["gateway_sub_id"] );

      bof()->cronjob->log_p( $PID, $GID, "Checking sub -> ID #{$sub["ID"]}" );
      $c++;

      if ( $_sub["status"] == "canceled" ){

        $d++;
        bof()->cronjob->log_p( $PID, $GID, "Subscription is canceled" );
        bof()->object->user_subs->update(
          array(
            "ID" => $sub["ID"]
          ),
          array(
            "time_expire" => bof()->general->mysql_timestamp(),
            "gateway_time_recur" => false,
          )
        );

      }
      elseif ( $_sub["current_period_end_local"] > strtotime( $sub["gateway_time_recur"] ) ){

        bof()->cronjob->log_p( $PID, $GID, "New invoice {$_sub["latest_invoice"]}" );

        $_invoice = bof()->pgt_stripe->check_invoice( $_sub["latest_invoice"] );

        if ( !$_invoice["paid"] ){
          bof()->cronjob->log_p( $PID, $GID, "Invoice is not marked as paid" );
          continue;
        }

        if ( bof()->object->payment->select(
          array(
            "gateway_name" => "stripe",
            "sub_id" => $sub["gateway_sub_id"],
            "gateway_id" => $_sub["latest_invoice"]
          ),
          array(
            "clean" => false
          )
        ) ){
          bof()->cronjob->log_p( $PID, $GID, "Invoice is already recorded" );
          continue;
        }

        $default_currency = bof()->object->currency->get_default();

        $payment_hash = bof()->object->payment->get_free_hash("_key");
        $payment_num = substr(md5(time() . rand(1, 100000000000000)), 0, 12);
        $payment_id = bof()->object->payment->insert(array(
          "_num" => $payment_num,
          "_key" => $payment_hash,
          "user_id" => $sub["user_id"],
          "amount" => $sub["subs_plan_price"],
          "currency" => $default_currency["iso_code"],
          "mode" => "sub",
          "gateway_name" => "stripe",
          "gateway_amount" => $_invoice["amount_paid"]/100,
          "gateway_id" => $_sub["latest_invoice"],
          "gateway_currency" => strtoupper( $_invoice["currency"] ),
          "gateway_data" => json_encode(array(
            "invoice_id" => $_sub["latest_invoice"],
            "charge_id" => $_invoice["charge"]
          )),
          "purchase_data" => json_encode(array(
            "sub_id" => $sub["ID"]
          )),
          "paid" => 1,
          "approved" => 1,
          "time_pay" => bof()->general->mysql_timestamp( $_invoice["created_local"] ),
          "time_approve" => bof()->general->mysql_timestamp(),
          "sub_id" => $sub["gateway_sub_id"],
        ));

        bof()->object->user_subs->update(
          array(
            "ID" => $sub["ID"]
          ),
          array(
            "payment_id" => $payment_id,
            "payment_time" => bof()->general->mysql_timestamp( $_invoice["created_local"] ),
            "payment_count" => $sub["payment_count"] + 1,
            "time_expire" => bof()->general->mysql_timestamp( $_sub["current_period_end_local"] ),
            "gateway_time_recur" => bof()->general->mysql_timestamp( $_sub["current_period_end_local"] ),
          )
        );

        bof()->cronjob->log_p( $PID, $GID, "New payment recoded ID #{$payment_id}. Extended sub to: " . bof()->general->mysql_timestamp( $_sub["current_period_end_local"] ) );
        $p++;

      } else {
        bof()->cronjob->log_p( $PID, $GID, "Nothing is changed with this sub" );
      }
    }

    return "Checked {$c} subscriptions. Found {$p} new payments. Found {$d} canceled subs";

  }

  public function add_setting( $gateway_name, $extra_inputs ){

    bof()->bofAdmin->_add_setting( "gateway_{$gateway_name}", array(
      "groups" => array(
        "gateway" => array(

          "title" => ucfirst( $gateway_name ),
          "icon" => "credit_card",

          "inputs" => array_merge(
            array(
              "gateway_{$gateway_name}" => array(
                "title" => "Enable",
                "tip" => "Do you want to enable `".(ucfirst($gateway_name))."` as a payment gateway?",
                "col_name" => "gateway_{$gateway_name}",
                "input" => array(
                  "name" => "gateway_{$gateway_name}",
                  "type" => "checkbox",
                ),
                "validator" => array(
                  "boolean",
                  array(
                    "empty()",
                  )
                )
              ),
            ),
            $extra_inputs,
            array(
              "gateway_{$gateway_name}_fee" => array(
                "title" => "Fee",
                "tip" => "Set a fee in percentage ( 0 to 100 ). Script will automatically add the fee to user payment",
                "col_name" => "gateway_{$gateway_name}_fee",
                "input" => array(
                  "name" => "gateway_{$gateway_name}_fee",
                  "type" => "digit",
                ),
                "validator" => array(
                  "int",
                  array(
                    "empty()",
                    "min" => 0,
                    "max" => 100,
                    "forceZero" => true
                  )
                )
              ),
              "gateway_{$gateway_name}_min" => array(
                "title" => "Minimum payment",
                "tip" => "The smallest amount needed to complete a payment",
                "col_name" => "gateway_{$gateway_name}_min",
                "input" => array(
                  "name" => "gateway_{$gateway_name}_min",
                  "type" => "digit",
                ),
                "validator" => array(
                  "float",
                  array(
                    "empty()",
                    "forceNull" => true
                  )
                )
              ),
              "gateway_{$gateway_name}_def" => array(
                "title" => "Default payment amount",
                "tip" => "The default amount in amount field",
                "col_name" => "gateway_{$gateway_name}_def",
                "input" => array(
                  "name" => "gateway_{$gateway_name}_def",
                  "type" => "digit",
                  "value" => 100
                ),
                "validator" => array(
                  "float",
                  array(
                    "empty()",
                    "forceNull" => true
                  )
                )
              ),
              "gateway_{$gateway_name}_auto" => array(
                "title" => "Auto Approve",
                "tip" => "If checked, all payments approved by razorpay will be automatically approved in your website. Otherwise an admin has to manually approve payments",
                "col_name" => "gateway_{$gateway_name}_auto",
                "input" => array(
                  "name" => "gateway_{$gateway_name}_auto",
                  "type" => "checkbox",
                ),
                "validator" => array(
                  "boolean",
                  array(
                    "empty()",
                    "forceDigit" => true,
                    "forceInt" => true,
                    "int" => true,
                  )
                )
              ),
            )
          )
        ),
      )
    ) );

  }

}

?>
