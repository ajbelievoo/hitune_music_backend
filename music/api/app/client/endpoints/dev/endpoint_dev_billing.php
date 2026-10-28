<?php

if ( !defined( "bof_root" ) ) die;

/**
 * Razorpay payment-link redirect target for developer plan subscriptions.
 * Razorpay appends razorpay_payment_id / razorpay_payment_link_id /
 * razorpay_payment_link_status to the callback URL.
 */

function endpoint_dev_billing( $loader, $excuter, $args ){

  $app_hash = $loader->nest->user_input( "get", "app", "md5" );
  if ( !$app_hash ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $app = $loader->developer_api->app( $app_hash );
  if ( !$app || empty( $app["pending_plan_id"] ) || empty( $app["pending_txn"] ) ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $plan = $loader->developer_api->plan( (int)$app["pending_plan_id"] );
  if ( !$plan ){
    $loader->api->set_error( "plan_not_found" );
    return;
  }

  // Verify the payment link actually got paid
  $paid = false;
  try {
    $check = bof()->pgt_razorpay->check_payment( array( "gateway_id" => $app["pending_txn"] ) );
    $paid = ( $check && !empty( $check["amount"] ) && (float)$check["amount"] >= (float)$plan["price"] );
  } catch( Exception | bofException | Error $err ){}

  if ( !$paid ){
    $loader->api->set_error( "failed", array(
      "output_args" => [ "turn" => false ],
      "more" => "Payment not completed yet",
      "code" => "payment_pending"
    ) );
    return;
  }

  // Activate: 30-day rolling period for paid API plans
  $loader->db->_update( array(
    "table" => "_dev_apps",
    "set" => array(
      array( "plan_id", (int)$plan["ID"] ),
      array( "plan_expire", bof()->general->mysql_timestamp( time() + 30 * 86400 ) ),
      array( "pending_plan_id", null ),
      array( "pending_txn", null )
    ),
    "where" => array( array( "ID", "=", (int)$app["ID"] ) )
  ) );

  try {
    bof()->chapar->notify_admin( "developer_plan_activated", array(
      "app" => $app["name"],
      "plan" => $plan["name"],
      "txn" => $app["pending_txn"]
    ) );
  } catch( Exception | Error $err ){}

  $loader->api->set_message( "ok", array(
    "status" => "plan_activated",
    "plan" => $loader->developer_api->clean_plan( $plan )
  ) );

}

?>
