<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_verify_purchase( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $product_id = $loader->nest->user_input( "post", "product_id", "string" );
  $purchase_token = $loader->nest->user_input( "post", "purchase_token", "string" );
  $platform = $loader->nest->user_input( "post", "platform", "in_array", [ "values" => [ "android", "ios" ] ] );

  if ( !$product_id || !$purchase_token || !$platform ){
    $loader->api->set_error( "invalid_purchase" );
    return;
  }

  $token_hash = hash( "sha256", $platform . ":" . $purchase_token );

  // Idempotent: same token already processed -> return existing state
  $existing = $loader->db->_select( array(
    "table" => "_u_iap_receipts",
    "where" => array(
      [ "token_hash", "=", $token_hash ]
    ),
    "single" => true
  ) );

  if ( $existing ){
    $loader->api->set_message( "ok", array(
      "status" => $existing["status"],
      "plan" => $loader->client_config->get_user_plan( $userID )["plan"],
      "duplicate" => true
    ) );
    return;
  }

  // Verify with the store when credentials are configured.
  // Set the "iap_strict" setting to reject purchases that cannot be verified.
  $store_verified = null;
  $store_data = null;

  if ( $platform == "android" ){

    $sa_path = root . "/app/google-play-service-account.json";
    if ( !is_file( $sa_path ) ) $sa_path = root . "/app/fcm-service-account.json";
    $package = $loader->object->db_setting->get( "iap_google_package" );

    if ( is_file( $sa_path ) && $package ){
      try {
        require_once( bof_root . "/app/core/third/googleapi/vendor/autoload.php" );
        $gclient = new Google\Client();
        $gclient->setAuthConfig( $sa_path );
        $gclient->addScope( "https://www.googleapis.com/auth/androidpublisher" );
        $token = $gclient->fetchAccessTokenWithAssertion();
        if ( !empty( $token["access_token"] ) ){
          $resp = $loader->curl->exe( array(
            "url" => "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$package}/purchases/subscriptionsv2/tokens/" . urlencode( $purchase_token ),
            "headers" => [ "Authorization: Bearer " . $token["access_token"] ],
            "cache_load" => false,
            "cache_save" => false
          ) );
          $store_data = !empty( $resp["data"] ) ? $resp["data"] : null;
          // subscriptionState: SUBSCRIPTION_STATE_ACTIVE / IN_GRACE_PERIOD / ON_HOLD count as entitled
          if ( !empty( $store_data["subscriptionState"] ) && in_array( $store_data["subscriptionState"], [ "SUBSCRIPTION_STATE_ACTIVE", "SUBSCRIPTION_STATE_IN_GRACE_PERIOD", "SUBSCRIPTION_STATE_ON_HOLD" ], true ) )
          $store_verified = true;
          elseif ( !empty( $store_data["error"] ) )
          $store_verified = false;
        }
      } catch( Exception | Error $err ){}
    }

  }
  elseif ( $platform == "ios" ){

    $shared_secret = $loader->object->db_setting->get( "iap_apple_shared_secret" );

    if ( $shared_secret ){
      foreach( [ "https://buy.itunes.apple.com/verifyReceipt", "https://sandbox.itunes.apple.com/verifyReceipt" ] as $verify_url ){
        try {
          $resp = $loader->curl->exe( array(
            "url" => $verify_url,
            "posts" => json_encode( array(
              "receipt-data" => $purchase_token,
              "password" => $shared_secret,
              "exclude-old-transactions" => true
            ) ),
            "json" => true,
            "cache_load" => false,
            "cache_save" => false
          ) );
          $store_data = !empty( $resp["data"] ) ? $resp["data"] : null;
          if ( isset( $store_data["status"] ) && $store_data["status"] == 21007 ) continue; // sandbox receipt -> retry sandbox
          $store_verified = !empty( $store_data["status"] ) ? $store_data["status"] == 0 : false;
          break;
        } catch( Exception | Error $err ){}
      }
    }

  }

  if ( $store_verified === false || ( $store_verified === null && $loader->object->db_setting->get( "iap_strict" ) ) ){

    $loader->db->_insert( array(
      "table" => "_u_iap_receipts",
      "set" => array(
        [ "user_id", $userID ],
        [ "platform", $platform ],
        [ "product_id", $product_id ],
        [ "purchase_token", substr( $purchase_token, 0, 4000 ) ],
        [ "token_hash", $token_hash ],
        [ "status", "rejected" ]
      )
    ) );

    $loader->api->set_error( "verification_failed" );
    return;
  }

  // Map product_id -> subscription plan via plan data.iap_product_id / data.iap map
  $matched_plan = null;
  $period = null;

  $plans = $loader->db->_select( array(
    "table" => "_u_subs_plans",
    "where" => array(
      [ "active", "=", 1 ]
    ),
    "single" => false,
    "limit" => false
  ) );

  if ( $plans ){
    foreach( $plans as $plan ){

      $data = !empty( $plan["data"] ) ? json_decode( $plan["data"], true ) : null;
      if ( !is_array( $data ) ) continue;

      $iap_ids = array();
      if ( !empty( $data["iap_product_id"] ) ) $iap_ids[] = $data["iap_product_id"];
      if ( !empty( $data["iap"] ) && is_array( $data["iap"] ) ){
        foreach( $data["iap"] as $_pid => $_p ){
          $iap_ids[] = is_array( $_p ) ? $_pid : $_p;
        }
      }

      if ( in_array( $product_id, $iap_ids, true ) ){
        $matched_plan = $plan;
        if ( !empty( $data["iap"][ $product_id ]["period"] ) )
        $period = $data["iap"][ $product_id ]["period"];
        break;
      }

    }
  }

  // Fallback: infer period from product id suffix
  if ( !$period ){
    if ( preg_match( "/year|annual/i", $product_id ) ) $period = "yearly";
    elseif ( preg_match( "/week/i", $product_id ) ) $period = "weekly";
    else $period = "monthly";
  }

  if ( !$matched_plan ){

    // Record anyway for manual review
    $loader->db->_insert( array(
      "table" => "_u_iap_receipts",
      "set" => array(
        [ "user_id", $userID ],
        [ "platform", $platform ],
        [ "product_id", $product_id ],
        [ "purchase_token", substr( $purchase_token, 0, 4000 ) ],
        [ "token_hash", $token_hash ],
        [ "status", "unmatched" ]
      )
    ) );

    $loader->api->set_error( "unknown_product" );
    return;

  }

  $ranges = array(
    "weekly" => "+1 week",
    "monthly" => "+1 month",
    "3months" => "+3 months",
    "6months" => "+6 months",
    "yearly" => "+1 year",
    "2years" => "+2 years"
  );
  if ( empty( $ranges[ $period ] ) ) $period = "monthly";

  // Prefer the store-reported expiry when the receipt was verified
  $expire = null;
  if ( $store_verified && $platform == "android" && !empty( $store_data["lineItems"][0]["expiryTime"] ) )
  $expire = date( "Y-m-d H:i:s", strtotime( $store_data["lineItems"][0]["expiryTime"] ) );

  // Extend an existing active subscription of the same plan, otherwise start fresh
  $active_sub = $loader->db->_select( array(
    "table" => "_u_subs",
    "where" => array(
      [ "user_id", "=", $userID ],
      [ "time_expire", ">", "NOW()", true ]
    ),
    "order_by" => "time_expire",
    "order" => "DESC",
    "single" => true
  ) );

  if ( !$expire ){
    $base = ( $active_sub && $active_sub["subs_plan_id"] == $matched_plan["ID"] ) ? strtotime( $active_sub["time_expire"] ) : time();
    $expire = date( "Y-m-d H:i:s", strtotime( $ranges[ $period ], $base ) );
  }

  $loader->db->_insert( array(
    "table" => "_u_subs",
    "set" => array(
      [ "user_id", $userID ],
      [ "subs_plan_id", $matched_plan["ID"] ],
      [ "subs_plan_time_range", $period ],
      [ "subs_plan_price", 0 ],
      [ "gateway_name", "iap_" . $platform ],
      [ "gateway_sub_id", $token_hash ],
      [ "time_expire", $expire ]
    )
  ) );

  $loader->db->_insert( array(
    "table" => "_u_iap_receipts",
    "set" => array(
      [ "user_id", $userID ],
      [ "platform", $platform ],
      [ "product_id", $product_id ],
      [ "purchase_token", substr( $purchase_token, 0, 4000 ) ],
      [ "token_hash", $token_hash ],
      [ "subs_plan_id", $matched_plan["ID"] ],
      [ "status", "verified" ]
    )
  ) );

  bof()->chapar->notify_admin( "iap_verified", array(
    "product_id" => $product_id,
    "plan" => $matched_plan["name"],
    "user_id" => $userID
  ) );

  $_plan_data = !empty( $matched_plan["data"] ) ? json_decode( $matched_plan["data"], true ) : null;
  $plan_key = !empty( $_plan_data["plan_key"] ) ? $_plan_data["plan_key"] : strtolower( preg_replace( "/[^a-zA-Z0-9]+/", "_", trim( $matched_plan["name"] ) ) );

  $loader->api->set_message( "ok", array(
    "status" => "verified",
    "plan" => $plan_key,
    "plan_id" => $matched_plan["ID"],
    "expires" => strtotime( $expire ),
    "store_verified" => $store_verified === true
  ) );

}

?>
