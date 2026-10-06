<?php

if ( !defined( "bof_root" ) ) die;

/**
 * Public renderer for HTML-form payment gateways (PayU etc).
 * URL: api/pay_render/<payment_num>/<payment_hash>/
 * Serves the auto-submitting checkout form stored on the payment row.
 */
function endpoint_pay_render( $loader, $excuter, $args ){

  $url = $loader->request->get_requested_url();
  list( $payment_num, $payment_hash ) = explode( "/", rtrim( substr( $url, strlen("pay_render/") ), "/" ) );

  header( "Content-Type: text/html; charset=utf-8" );

  if (
    !bof()->nest->validate( $payment_num, "string", array( "strict" => true, "strict_regex" => "[a-zA-Z0-9]" ) ) ||
    !bof()->nest->validate( $payment_hash, "md5" )
  ){
    http_response_code( 404 );
    echo "Invalid payment link";
    return;
  }

  $payment = bof()->object->payment->select( array(
    "_num" => $payment_num,
    "_key" => $payment_hash,
    [ "time_add", ">", "SUBDATE( now(), INTERVAL 24 HOUR )", true ]
  ) );

  if ( !$payment || $payment["paid"] || empty( $payment["gateway_req_data"] ) ){
    http_response_code( 404 );
    echo "Payment link expired or already processed";
    return;
  }

  echo $payment["gateway_req_data"];

}

?>
