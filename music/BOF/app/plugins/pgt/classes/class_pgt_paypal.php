<?php

if ( !defined( "bof_root" ) ) die;

class pgt_paypal extends bof_type_class {

  protected $id = null;
  protected $key = null;
  protected $mode = null;

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_paypal" ) ) return false;
    if ( !( $this->id = bof()->object->db_setting->get( "gateway_paypal_key" ) ) ) return false;
    if ( !( $this->key = bof()->object->db_setting->get( "gateway_paypal_secret" ) ) ) return false;
    if ( !( $this->mode = bof()->object->db_setting->get( "gateway_paypal_mode" ) ) ) return false;

    require_once( pgt_plugin_root . "/third/paypal-1.14/autoload.php" );

    $client = new \PayPal\Rest\ApiContext(
      new \PayPal\Auth\OAuthTokenCredential( $this->id, $this->key )
    );

    $client->setConfig( array(
      'mode' => $this->mode,
    ) );

    return $client;

  }

  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "paypal", array(
        "db_name" => "paypal",
        "code_name" => "pp",
        "title" => "PayPal",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=paypal.com&sz=256",
        "supported_currencies" => [ "USD", "EUR", "AUD", "CAD", "GBP" ]
      ) );
    } );

  }

  public function formatter( $amount, $currency ){
    return ceil( $amount * 100 ) / 100;
  }
  public function get_link( $amount, $currency, $order_no, $redirect_address ){

    $client = $this->getClient();
		if ( !$client ) return false;

		$title = "Chargin wallet";
		$charge_amount = $amount;

    $payer = new PayPal\Api\Payer();
    $payer->setPaymentMethod('paypal');

    $item = new PayPal\Api\Item();
    $item->setName( $title )
		->setQuantity( 1 )
		->setPrice( $charge_amount )
		->setCurrency( strtoupper( $currency["iso_code"] ) );

    $itemList = new PayPal\Api\ItemList();
    $itemList->setItems(array(
        $item
    ));

    $details = new PayPal\Api\Details();
    $details->setSubtotal( $charge_amount );

    $amount = new PayPal\Api\Amount();
    $amount->setCurrency( strtoupper( $currency["iso_code"] ) )
		->setTotal( $charge_amount )
		->setDetails( $details );

    $transaction = new PayPal\Api\Transaction();
    $transaction->setAmount( $amount )
		->setItemList( $itemList )
		->setDescription( 'Wallet charge For ' . bof()->object->db_setting->get("sitename") )
		->setInvoiceNumber( $order_no );

    $redirectUrls = new PayPal\Api\RedirectUrls();
    $redirectUrls->setReturnUrl( $redirect_address )
		->setCancelUrl( $redirect_address );

    $payment = new PayPal\Api\Payment();
    $payment->setIntent('sale')
		->setPayer( $payer )
		->setRedirectUrls( $redirectUrls )
		->setTransactions(array(
        $transaction
    ));

    $payment->create( $client );

    return array(
      "output" => array(
        "type" => "link",
        "link" => $payment->getApprovalLink()
      ),
      "txn" => $payment->getID()
    );

  }
  public function check_payment( $payment ){

		$client = $this->getClient();
		if ( !$client ) throw new Exception("no_client");

    if (
			!( $payment_id = bof()->nest->user_input( "get", "paymentId", "string", [ "strict" => true, "strict_regex" => "[0-9a-zA-Z\-_]" ] ) ) ||
			!( $payer_id = bof()->nest->user_input( "get", "PayerID", "string", [ "strict" => true, "strict_regex" => "[0-9a-zA-Z\-_]" ] ) ) ||
			!( $token = bof()->nest->user_input( "get", "token", "string", [ "strict" => true, "strict_regex" => "[0-9a-zA-Z\-_]" ] ) )
		) throw new Exception("invalid_args");

    if ( $payment["gateway_id"] != $payment_id )
    throw new Exception("invalid_args: paymentId");

		$pp_payment = PayPal\Api\Payment::get( $payment["gateway_id"], $client );
		$execute = new PayPal\Api\PaymentExecution();
		$execute->setPayerId( $payer_id );

    $result = $pp_payment->execute( $execute, $client );

    if ( $result->transactions[0]->invoice_number != $payment["_key"] )
    throw new Exception("invalid_result: invoice_number");

    if ( !empty( $result->failed_transactions ) )
    throw new Exception("invalid_result: has_failed_transaction");

    if ( $result->state != "approved" )
    throw new Exception("invalid_result: not_approved_by_paypal");

    return array(
      "amount" => $result->transactions[0]->amount->total,
      "currency" => strtoupper( $result->transactions[0]->amount->currency ),
      "data" => array(
        "payer_info" => $result->getPayer(),
        "cart" => $result->cart,
      )
    );

  }

}

?>
