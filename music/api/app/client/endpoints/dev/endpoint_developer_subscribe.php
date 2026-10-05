<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_subscribe( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $plan_hash = $loader->nest->user_input( "post", "plan_hash", "md5" );
  if ( !$plan_hash ) $plan_hash = $loader->nest->user_input( "post", "plan", "string_abcd" );
  $app_hash  = $loader->nest->user_input( "post", "app_hash", "md5" );
  if ( !$app_hash ) $app_hash = $loader->nest->user_input( "post", "hash", "md5" );

  if ( !$plan_hash || !$app_hash ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $app = $loader->developer_api->app( $app_hash, $userID );
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $plan = $loader->developer_api->plan( $plan_hash );
  if ( !$plan || !$plan["active"] ){
    $loader->api->set_error( "plan_not_found" );
    return;
  }

  // Already on this plan and not expired
  if ( (int)$app["plan_id"] === (int)$plan["ID"] ){
    if ( empty( $app["plan_expire"] ) || strtotime( $app["plan_expire"] ) > time() ){
      $loader->api->set_error( "failed", array(
        "output_args" => [ "turn" => false ],
        "more" => "App is already on this plan",
        "code" => "already_subscribed"
      ) );
      return;
    }
  }

  // Enterprise / contact-sales tiers don't self-serve
  if ( $plan["contact_sales"] || $plan["price"] === null ){
    $loader->api->set_message( "ok", array(
      "status" => "contact_sales",
      "contact_sales" => true,
      "plan" => $loader->developer_api->clean_plan( $plan )
    ) );
    return;
  }

  // Free plan -> apply instantly
  if ( (float)$plan["price"] <= 0 ){
    $loader->db->_update( array(
      "table" => "_dev_apps",
      "set" => array(
        array( "plan_id", (int)$plan["ID"] ),
        array( "plan_expire", null )
      ),
      "where" => array( array( "ID", "=", (int)$app["ID"] ) )
    ) );
    $loader->api->set_message( "ok", array(
      "status" => "subscribed",
      "plan" => $loader->developer_api->clean_plan( $plan ),
      "app" => $loader->developer_api->clean_app( $loader->developer_api->app( $app_hash, $userID ) )
    ) );
    return;
  }

  // Paid plan -> Razorpay payment link (same flow as dist checkout)
  if ( !bof()->object->db_setting->get( "gateway_razorpay" )
    || !bof()->object->db_setting->get( "gateway_razorpay_id" )
    || !bof()->object->db_setting->get( "gateway_razorpay_key" ) ){
    $loader->api->set_error( "failed", array(
      "output_args" => [ "turn" => false ],
      "more" => "Payment gateway is not configured",
      "code" => "gateway_not_configured"
    ) );
    return;
  }

  $callback = web_address . "api/dev/billing?app=" . $app["hash"];
  $link = null;
  try {
    $link = bof()->pgt_razorpay->get_link(
      (float)$plan["price"],
      array( "iso_code" => !empty( $plan["currency"] ) ? $plan["currency"] : "INR" ),
      "DEVSUB{$app["ID"]}_{$plan["ID"]}",
      $callback
    );
  } catch( Exception | bofException | Error $err ){}

  if ( !$link || empty( $link["output"]["link"] ) ){
    $loader->api->set_error( "failed", array(
      "output_args" => [ "turn" => false ],
      "more" => "Could not create payment link",
      "code" => "payment_init_failed"
    ) );
    return;
  }

  $loader->db->_update( array(
    "table" => "_dev_apps",
    "set" => array(
      array( "pending_plan_id", (int)$plan["ID"] ),
      array( "pending_txn", $link["txn"] )
    ),
    "where" => array( array( "ID", "=", (int)$app["ID"] ) )
  ) );

  $loader->api->set_message( "ok", array(
    "status" => "payment_required",
    "link" => $link["output"]["link"],
    "plan" => $loader->developer_api->clean_plan( $plan )
  ) );

}

?>
