<?php

if ( !defined( "bof_root" ) ) die;

class pgt_stripe extends bof_type_class {

  protected $publishable_key = null;
  protected $secret = null;
  protected $mode = null;

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_stripe" ) ) return false;
    if ( !( $this->publishable_key = bof()->object->db_setting->get( "gateway_stripe_key" ) ) ) return false;
    if ( !( $this->secret = bof()->object->db_setting->get( "gateway_stripe_secret" ) ) ) return false;
    if ( !( $this->mode = bof()->object->db_setting->get( "gateway_stripe_mode" ) ) ) return false;

    require_once( pgt_plugin_root . "/third/stripe-10.4.0/autoload.php" );

    \Stripe\Stripe::setApiKey( $this->secret );

    return true;

  }

  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "stripe", array(
        "db_name" => "stripe",
        "code_name" => "st",
        "title" => "Stripe",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=stripe.com&sz=256",
        "supported_currencies" => [ "USD", "EUR", "AUD", "CAD", "GBP", "PLN", "RON", "HUF" ]
      ) );
    } );

  }

  public function formatter( $amount, $currency ){
    return ceil( $amount * 100 ) / 100;
  }
  public function get_link( $amount, $currency, $order_no, $redirect_address, $args=[] ){

    $gt_fee = false;
    $gt_fee_per = false;
    $gt_base_amount = 0;
    $recurring = false;
    $title = false;
    extract( $args );

    $client = $this->getClient();
    if ( !$client ) return false;

    try {

      if ( $gt_fee ){

        $db_tax_object_id = bof()->object->db_setting->get("gateway_stripe_tid");
        $db_tax_fee_per = bof()->object->db_setting->get("gateway_stripe_ta");

        if ( $db_tax_object_id && $db_tax_fee_per == $gt_fee_per ){
          $tax_object_id = $db_tax_object_id;
        } else {

          $tax_object = \Stripe\TaxRate::create( array(
            "display_name" => bof()->object->db_setting->get("gateway_stripe_taxLabel","Tax"),
            "inclusive" => true,
            "percentage" => $gt_fee_per
          ) );

          if ( $tax_object["id"] ){
            $tax_object_id = $tax_object["id"];
            bof()->object->db_setting->set( "gateway_stripe_tid", $tax_object_id );
            bof()->object->db_setting->set( "gateway_stripe_ta", $gt_fee_per );
          }

        }

      }

      $customer_email = !empty( $args['customer_email'] ) ? $args['customer_email'] : null;

      $metadata = array(
        'payment_num' => !empty( $args['payment_num'] ) ? $args['payment_num'] : '',
        'payment_hash' => $order_no,
      );
      if ( !empty( $args['payment_id'] ) ) $metadata['payment_id'] = (string) $args['payment_id'];
      if ( !empty( $args['user_id'] ) ) $metadata['user_id'] = (string) $args['user_id'];
      if ( !empty( $args['plan_id'] ) ) $metadata['plan_id'] = (string) $args['plan_id'];
      if ( !empty( $args['plan_period'] ) ) $metadata['plan_period'] = $args['plan_period'];

      $session_array = array(
        'client_reference_id' => $order_no,
        'line_items' => array(
          array(
            "price_data" => array(
              'currency' => $currency["iso_code"],
              'unit_amount' => ceil( $amount * 100 ),
              'product_data' => array(
                'name' => $title ? $title : bof()->object->db_setting->get("gateway_stripe_desc","Wallet charge"),
              ),
            ),
            'quantity' => 1,
          )
        ),
        'custom_text' => array(),
        'mode' => 'payment',
        'success_url' => $redirect_address,
        'cancel_url' => $redirect_address,
        'metadata' => $metadata,
      );

      if ( $customer_email ) $session_array['customer_email'] = $customer_email;

      if ( $recurring ){
        $session_array["line_items"][0]["price_data"]["recurring"] = array(
          'interval' => $recurring["unit"],
          'interval_count' => $recurring["quantity"]
        );
        $session_array["mode"] = "subscription";
      }

      if ( ( $cText = bof()->object->db_setting->get("gateway_stripe_cText1") ) )
      $session_array["custom_text"]["submit"] = array(
        "message" => $cText
      );

      if ( ( $cText = bof()->object->db_setting->get("gateway_stripe_cText2") ) )
      $session_array["custom_text"]["after_submit"] = array(
        "message" => $cText
      );

      if ( !empty( $tax_object_id ) )
      $session_array["line_items"][0]["tax_rates"] = [ $tax_object_id ];

      $checkout_session = \Stripe\Checkout\Session::create( $session_array );

    } catch (\Stripe\Error\InvalidRequest $e) {
      throw new Exception( "Stripe: Failed: InvalidRequest" . $e->getMessage() );
    } catch (\Stripe\Error\Authentication $e) {
      throw new Exception( "Stripe: Failed: Authentication" . $e->getMessage() );
    } catch (\Stripe\Error\ApiConnection $e) {
      throw new Exception( "Stripe: Failed: ApiConnection" . $e->getMessage() );
    } catch (\Stripe\Error\Base $e) {
      throw new Exception( "Stripe: Failed: Base" . $e->getMessage() );
    }

    if ( empty( $checkout_session->id ) || empty( $checkout_session->url ) )
    throw new Exception( "Stripe: Failed to create session" );

    return array(
      "output" => array(
        "type" => "link",
        "link" => $checkout_session->url
      ),
      "txn" => $checkout_session->id
    );

  }
  public function check_payment( $payment ){

    $client = $this->getClient();
    if ( !$client ) return false;

    $checkout_session = \Stripe\Checkout\Session::retrieve( $payment["gateway_id"] );

    if ( empty( $checkout_session ) )
    throw new Exception( "stripe_check_payment_failed" );

    if ( $checkout_session["status"] != "complete" || $checkout_session["payment_status"] != "paid" )
    throw new Exception( "stripe_reported_not_paid" );

    $output = array(
      "amount" => $checkout_session["amount_total"] / 100,
      "currency" => strtoupper( $checkout_session["currency"] ),
      "data" => array(
        "customer_details" => $checkout_session["customer_details"],
        "payment_intent" => $checkout_session["payment_intent"],
        "payment_method_types" => $checkout_session["payment_method_types"]
      ),
      "mode" => "pay",
    );

    if ( !empty( $checkout_session["mode"] ) ? $checkout_session["mode"] == "subscription" : false ){

      $output["mode"] = "sub";
      $invoice = \Stripe\Invoice::retrieve( $checkout_session["invoice"] );

      $_sid = null;
      if ( ( !empty( $invoice["billing_reason"] ) && !empty( $invoice["lines"]["data"][0]["subscription"] ) ) ? $invoice["billing_reason"] == "subscription_create" : false ){
        $_sid = $invoice["lines"]["data"][0]["subscription"];
      }
      else if ( ( !empty( $invoice["billing_reason"] ) && !empty( $invoice["lines"]["data"][0]["id"] ) ) ? $invoice["billing_reason"] == "subscription_update" : false ){
        $_sid = $invoice["lines"]["data"][0]["id"];
      }

      $subscription = \Stripe\Subscription::retrieve( $_sid );
      $output["sub_id"] = $_sid;

      $_utc = $subscription["current_period_end"];
      $utcDateTime = new DateTime(bof()->general->mysql_timestamp($_utc), new DateTimeZone("UTC"));
      $utcDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
      $output["time_recur"] = $_utc;

    }

    return $output;

  }
  public function check_sub( $id ){

    $client = $this->getClient();
    if ( !$client ) return false;

    $subscription = \Stripe\Subscription::retrieve( $id );
    $_utc = $subscription["current_period_end"];
    $utcDateTime = new DateTime(bof()->general->mysql_timestamp($_utc), new DateTimeZone("UTC"));
    $utcDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $subscription["current_period_end_local"] = $_utc;
    return $subscription;

  }
  public function check_invoice( $id ){

    $client = $this->getClient();
    if ( !$client ) return false;

    $invoice = \Stripe\Invoice::retrieve( $id );

    $_utc = $invoice["created"];
    $utcDateTime = new DateTime(bof()->general->mysql_timestamp($_utc), new DateTimeZone("UTC"));
    $utcDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $invoice["created_local"] = $_utc;

    return $invoice;

  }
  public function cancel_sub( $id ){

    $client = $this->getClient();
    if ( !$client ) return false;

    try {
      $stripe = new \Stripe\StripeClient( $this->secret );
      $cancel = $stripe->subscriptions->cancel($id, []);
    } catch (\Stripe\Error\InvalidRequest $e) {
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      return false;
    } catch (Stripe\Exception\InvalidRequestException $e) {
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      return false;
    } catch (\Stripe\Error\Base $e) {
      return false;
    }

    return true;

  }

}

?>
