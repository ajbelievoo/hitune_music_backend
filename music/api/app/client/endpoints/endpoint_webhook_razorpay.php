<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_webhook_razorpay( $loader, $excuter, $args ){

  $secret = bof()->object->db_setting->get( "gateway_razorpay_webhook_secret" );
  if ( !$secret ){
    http_response_code( 500 );
    $loader->api->set_error( "razorpay_webhook_not_configured", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $payload = @file_get_contents( 'php://input' );
  $sig_header = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

  $expected = hash_hmac( 'sha256', $payload, $secret );
  if ( !hash_equals( $expected, $sig_header ) ){
    http_response_code( 400 );
    $loader->api->set_error( "razorpay_webhook_verify_failed", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $event = json_decode( $payload, true );
  if ( !$event || empty( $event['event'] ) ){
    http_response_code( 400 );
    $loader->api->set_error( "razorpay_webhook_invalid_payload", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $event_type = $event['event'];
  $event_id = !empty( $event['id'] ) ? $event['id'] : md5( $payload );

  // Idempotency check
  $existing = bof()->db->query(
    "SELECT ID FROM _u_payment_webhooks WHERE gateway='razorpay' AND event_id='" . bof()->db->real_escape_string( $event_id ) . "' LIMIT 1",
    null,
    true
  );
  if ( $existing && $existing->num_rows > 0 ){
    http_response_code( 200 );
    $loader->api->set_message( "ok" );
    return;
  }

  $payload_escaped = bof()->db->real_escape_string( $payload );
  bof()->db->query(
    "INSERT INTO _u_payment_webhooks (gateway,event_id,event_type,payload,processed) " .
    "VALUES ('razorpay','" . bof()->db->real_escape_string( $event_id ) . "','" . bof()->db->real_escape_string( $event_type ) . "','" . $payload_escaped . "',0)",
    null,
    true
  );
  $webhook_log_id = (int) bof()->db->insert_id;

  $error = null;
  try {
    switch( $event_type ){
      case 'payment_link.paid':
        $entity = !empty( $event['payload']['payment_link']['entity'] ) ? $event['payload']['payment_link']['entity'] : null;
        if ( $entity ) pgt_razorpay_process_link_paid( $entity );
        break;
      case 'payment.captured':
        $entity = !empty( $event['payload']['payment']['entity'] ) ? $event['payload']['payment']['entity'] : null;
        if ( $entity ) pgt_razorpay_process_payment_captured( $entity );
        break;
      default:
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

function pgt_razorpay_process_link_paid( $entity ){

  $link_id = !empty( $entity['id'] ) ? $entity['id'] : null;
  $reference_id = !empty( $entity['reference_id'] ) ? $entity['reference_id'] : null;
  if ( !$link_id && !$reference_id ) return;

  $payment = null;
  if ( $link_id ){
    $payment = bof()->object->payment->select(
      array( "gateway_name" => "razorpay", "gateway_id" => $link_id )
    );
  }
  if ( !$payment && $reference_id ){
    $payment = bof()->object->payment->select(
      array( "gateway_name" => "razorpay", "_key" => $reference_id )
    );
  }
  if ( !$payment || $payment["paid"] ) return;

  $check = bof()->pgt->setup()->check_payment( "razorpay", $payment );
  if ( $check !== true && $check !== "pending" ){
    throw new Exception( is_array( $check ) ? $check[1] : "razorpay_check_failed" );
  }

  if ( $check === "pending" ) return;

  if ( bof()->object->db_setting->get( "gateway_razorpay_auto" ) ){
    bof()->object->payment->_approve( $payment["ID"] );
  } else {
    bof()->object->payment->update(
      array( "ID" => $payment["ID"] ),
      array(
        "paid" => 1,
        "time_pay" => bof()->general->mysql_timestamp(),
        "gateway_data" => json_encode( array( "payment_link" => $entity ) )
      )
    );
  }
}

function pgt_razorpay_process_payment_captured( $entity ){

  $notes = !empty( $entity['notes'] ) ? $entity['notes'] : array();
  $payment_num = !empty( $notes['payment_num'] ) ? $notes['payment_num'] : null;
  $payment_hash = !empty( $notes['payment_hash'] ) ? $notes['payment_hash'] : null;
  $payment_id = !empty( $notes['payment_id'] ) ? (int) $notes['payment_id'] : null;

  $payment = null;
  if ( $payment_id ){
    $payment = bof()->object->payment->select( array( "ID" => $payment_id ) );
  }
  if ( !$payment && $payment_num ){
    $payment = bof()->object->payment->select( array( "_num" => $payment_num ) );
  }
  if ( !$payment && $payment_hash ){
    $payment = bof()->object->payment->select( array( "_key" => $payment_hash ) );
  }
  if ( !$payment || $payment["paid"] ) return;

  if ( $payment["gateway_name"] !== "razorpay" ) return;

  $amount = !empty( $entity['amount'] ) ? $entity['amount'] / 100 : 0;
  $currency = !empty( $entity['currency'] ) ? strtoupper( $entity['currency'] ) : $payment["currency"];

  bof()->object->payment->update(
    array( "ID" => $payment["ID"] ),
    array(
      "paid" => 1,
      "time_pay" => bof()->general->mysql_timestamp(),
      "gateway_amount" => $amount,
      "gateway_currency" => $currency,
      "gateway_data" => json_encode( array( "payment" => $entity ) )
    )
  );

  if ( bof()->object->db_setting->get( "gateway_razorpay_auto" ) ){
    bof()->object->payment->_approve( $payment["ID"] );
  }
}
