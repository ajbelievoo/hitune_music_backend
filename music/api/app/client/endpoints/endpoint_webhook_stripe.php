<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_webhook_stripe( $loader, $excuter, $args ){

  $secret = bof()->object->db_setting->get( "gateway_stripe_webhook_secret" );
  if ( !$secret ){
    http_response_code( 500 );
    $loader->api->set_error( "stripe_webhook_not_configured", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  require_once( pgt_plugin_root . "/third/stripe-10.4.0/autoload.php" );

  $payload = @file_get_contents( 'php://input' );
  $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

  try {
    $event = \Stripe\Webhook::constructEvent( $payload, $sig_header, $secret );
  } catch( Exception $e ){
    http_response_code( 400 );
    $loader->api->set_error( "stripe_webhook_verify_failed: " . $e->getMessage(), [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $event_id = $event->id;
  $event_type = $event->type;

  // Idempotency check
  $existing = bof()->db->query(
    "SELECT ID FROM _u_payment_webhooks WHERE gateway='stripe' AND event_id='" . bof()->db->real_escape_string( $event_id ) . "' LIMIT 1",
    null,
    true
  );
  if ( $existing && $existing->num_rows > 0 ){
    http_response_code( 200 );
    $loader->api->set_message( "ok" );
    return;
  }

  // Log event
  $payload_escaped = bof()->db->real_escape_string( $payload );
  bof()->db->query(
    "INSERT INTO _u_payment_webhooks (gateway,event_id,event_type,payload,processed) " .
    "VALUES ('stripe','" . bof()->db->real_escape_string( $event_id ) . "','" . bof()->db->real_escape_string( $event_type ) . "','" . $payload_escaped . "',0)",
    null,
    true
  );
  $webhook_log_id = (int) bof()->db->insert_id;

  $error = null;

  try {
    switch( $event_type ){

      case 'checkout.session.completed':
        $session = $event->data->object;
        pgt_stripe_process_checkout_completed( $session );
        break;

      case 'invoice.paid':
        $invoice = $event->data->object;
        pgt_stripe_process_invoice_paid( $invoice );
        break;

      case 'invoice.payment_failed':
        $invoice = $event->data->object;
        pgt_stripe_process_invoice_failed( $invoice );
        break;

      case 'customer.subscription.deleted':
      case 'customer.subscription.canceled':
        $subscription = $event->data->object;
        pgt_stripe_process_subscription_cancelled( $subscription );
        break;

      case 'customer.subscription.updated':
        $subscription = $event->data->object;
        pgt_stripe_process_subscription_updated( $subscription );
        break;

      default:
        // No action, event logged for record.
        break;
    }
  } catch( Exception $e ){
    $error = $e->getMessage();
  }

  if ( $error ){
    bof()->db->query(
      "UPDATE _u_payment_webhooks SET processing_error='" . bof()->db->real_escape_string( $error ) . "' WHERE ID=" . (int) $webhook_log_id,
      null,
      true
    );
    http_response_code( 500 );
    $loader->api->set_error( $error, [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  bof()->db->query(
    "UPDATE _u_payment_webhooks SET processed=1 WHERE ID=" . (int) $webhook_log_id,
    null,
    true
  );

  http_response_code( 200 );
  $loader->api->set_message( "ok" );

}

function pgt_stripe_process_checkout_completed( $session ){

  $session_id = $session->id;
  $client_ref = $session->client_reference_id;

  $payment = bof()->object->payment->select(
    array(
      "gateway_name" => "stripe",
      "gateway_id" => $session_id,
    )
  );

  if ( !$payment && $client_ref ){
    $payment = bof()->object->payment->select(
      array(
        "gateway_name" => "stripe",
        "_key" => $client_ref,
      )
    );
  }

  if ( !$payment ){
    throw new Exception( "payment_not_found" );
  }

  if ( $payment["paid"] ){
    return;
  }

  // Call existing Stripe check_payment to fill subscription/invoice details.
  $check = bof()->pgt->setup()->check_payment( "stripe", $payment );
  if ( $check !== true && $check !== "pending" ){
    throw new Exception( is_array( $check ) ? $check[1] : "stripe_check_failed" );
  }

  if ( $check === "pending" ){
    // Stripe may report paid later; do not approve yet.
    return;
  }

  // Auto-approve for Stripe if configured or always approve through webhook.
  if ( bof()->object->db_setting->get( "gateway_stripe_auto" ) ){
    bof()->object->payment->_approve( $payment["ID"] );
  } else {
    // Mark paid but require admin approval if not auto.
    bof()->object->payment->update(
      array( "ID" => $payment["ID"] ),
      array(
        "paid" => 1,
        "time_pay" => bof()->general->mysql_timestamp(),
        "gateway_data" => json_encode( array(
          "session" => $session->toArray(),
          "event" => "checkout.session.completed"
        ) )
      )
    );
  }

}

function pgt_stripe_process_invoice_paid( $invoice ){

  $subscription_id = !empty( $invoice->subscription ) ? $invoice->subscription : null;
  if ( !$subscription_id ) return;

  $sub = bof()->object->user_subs->select(
    array( "gateway_sub_id" => $subscription_id ),
    array( "single" => false, "limit" => false, "clean" => false )
  );

  if ( !$sub || !is_array( $sub ) ) return;

  $default_currency = bof()->object->currency->get_default();

  foreach( $sub as $s ){

    // Avoid duplicate invoice recording.
    $existing_payment = bof()->object->payment->select(
      array(
        "gateway_name" => "stripe",
        "sub_id" => $subscription_id,
        "gateway_id" => $invoice->id,
      ),
      array( "clean" => false )
    );
    if ( $existing_payment ) continue;

    $payment_hash = bof()->object->payment->get_free_hash( "_key" );
    $payment_num = substr( md5( time() . rand( 1, 100000000000000 ) ), 0, 12 );

    $payment_id = bof()->object->payment->insert( array(
      "_num" => $payment_num,
      "_key" => $payment_hash,
      "user_id" => $s["user_id"],
      "amount" => $s["subs_plan_price"],
      "currency" => $default_currency["iso_code"],
      "mode" => "sub",
      "gateway_name" => "stripe",
      "gateway_amount" => $invoice->amount_paid / 100,
      "gateway_id" => $invoice->id,
      "gateway_currency" => strtoupper( $invoice->currency ),
      "gateway_data" => json_encode( array(
        "invoice_id" => $invoice->id,
        "subscription_id" => $subscription_id,
      ) ),
      "purchase_data" => json_encode( array( "sub_id" => $s["ID"] ) ),
      "paid" => 1,
      "approved" => 1,
      "time_pay" => bof()->general->mysql_timestamp( $invoice->created ),
      "time_approve" => bof()->general->mysql_timestamp(),
      "sub_id" => $subscription_id,
    ) );

    $new_expire = bof()->general->mysql_timestamp( $invoice->period_end );
    bof()->object->user_subs->update(
      array( "ID" => $s["ID"] ),
      array(
        "payment_id" => $payment_id,
        "payment_time" => bof()->general->mysql_timestamp( $invoice->created ),
        "payment_count" => $s["payment_count"] + 1,
        "time_expire" => $new_expire,
        "gateway_time_recur" => $new_expire,
      )
    );

    bof()->object->transaction->insert( array(
      "user_id" => $s["user_id"],
      "amount" => $s["subs_plan_price"],
      "currency" => $default_currency["iso_code"],
      "type" => "deposit",
      "object_type" => "payment",
      "object_id" => $payment_id,
      "revenue" => 0,
      "data" => json_encode( array( "stripe_invoice" => $invoice->id ) )
    ) );

  }

}

function pgt_stripe_process_invoice_failed( $invoice ){

  $subscription_id = !empty( $invoice->subscription ) ? $invoice->subscription : null;
  if ( !$subscription_id ) return;

  $sub = bof()->object->user_subs->select(
    array( "gateway_sub_id" => $subscription_id ),
    array( "single" => false, "limit" => false, "clean" => false )
  );

  if ( !$sub || !is_array( $sub ) ) return;

  foreach( $sub as $s ){
    // Log failed payment; do not cancel immediately, allow grace.
    bof()->chapar->notify_admin( "stripe_invoice_failed", array(
      "user_subs_id" => $s["ID"],
      "user_id" => $s["user_id"],
      "invoice_id" => $invoice->id,
      "subscription_id" => $subscription_id,
    ) );
  }

}

function pgt_stripe_process_subscription_cancelled( $subscription ){

  $subscription_id = $subscription->id;
  $sub = bof()->object->user_subs->select(
    array( "gateway_sub_id" => $subscription_id ),
    array( "single" => false, "limit" => false, "clean" => false )
  );

  if ( !$sub || !is_array( $sub ) ) return;

  $now = bof()->general->mysql_timestamp();
  foreach( $sub as $s ){
    bof()->object->user_subs->update(
      array( "ID" => $s["ID"] ),
      array(
        "time_expire" => $now,
        "gateway_time_recur" => false,
      )
    );
  }

}

function pgt_stripe_process_subscription_updated( $subscription ){

  $subscription_id = $subscription->id;
  $sub = bof()->object->user_subs->select(
    array( "gateway_sub_id" => $subscription_id ),
    array( "single" => false, "limit" => false, "clean" => false )
  );

  if ( !$sub || !is_array( $sub ) ) return;

  if ( !empty( $subscription->current_period_end ) ){
    $new_expire = bof()->general->mysql_timestamp( $subscription->current_period_end );
    foreach( $sub as $s ){
      bof()->object->user_subs->update(
        array( "ID" => $s["ID"] ),
        array(
          "time_expire" => $new_expire,
          "gateway_time_recur" => $new_expire,
        )
      );
    }
  }

}
