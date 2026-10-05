<?php

if ( !defined( "bof_root" ) ) die;

class pgt_paystack extends bof_type_class {

  protected $client = false;
  protected $id = false;
  protected $key = false;

  public function setup_admin(){

    bof()->pgt->add_setting("paystack", array(
      "gateway_paystack_id" => array(
        "title" => "Public Key",
        "col_name" => "gateway_paystack_id",
        "input" => array(
          "name" => "gateway_paystack_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_paystack_key" => array(
        "title" => "Secret Key",
        "col_name" => "gateway_paystack_key",
        "input" => array(
          "name" => "gateway_paystack_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
    ));
    bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

      if ( is_array( $method_result ) ){

        $method_result[ "gateway_paystack" ] = array(
          "title" => "Paystack Payment Gateway",
          "url" => "^gateway_paystack",
          "link" => "gateway_paystack",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_paystack/",
              "key" => "setting"
            )
          ),
          "events" => (object)[],
          "__sb_family" => "business",
        );

      }

    } );
    bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){

      $sb_family = $method_args[0];

      $highlights = bof()->highlights->getData();

      $highlights[ "business_links" ][ "items" ][ "payment_gateways" ][ "args" ][ "childs" ][] = array(
        "icon"  => "credit_card",
        "title" => "Paystack",
        "link"  => "gateway_paystack"
      );
      bof()->highlights->setData( $highlights );

    } );

  }
  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "paystack", array(
        "db_name" => "paystack",
        "code_name" => "ps",
        "title" => "Paystack",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=paystack.com&sz=256",
        "supported_currencies" => [ "NGN", "USD", "GHS", "ZAR", "KES" ]
      ) );
    } );

  }

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_paystack" ) ) return false;
    if ( !( $this->id = bof()->object->db_setting->get( "gateway_paystack_id" ) ) ) return false;
    if ( !( $this->key = bof()->object->db_setting->get( "gateway_paystack_key" ) ) ) return false;
    if ( !empty( $this->client ) ) return $this->client;

    require_once( bof_extra_gateways_root . "/classes/third/paystack/autoload.php" );
    $this->client = $paystack = new Yabacon\Paystack( $this->key );
		return $this->client;

  }
  public function get_link( $amount, $currency, $order_no, $redirect_address ){

    $client = $this->getClient();
		if ( !$client ) return false;

    $amount *= 100;

    try {
      $tranx = $client->transaction->initialize([
        'amount'       => $amount,
        'email'        => bof()->user->check()->data["email"],
        'currency'     => $currency["iso_code"],
        'reference'    => $order_no,
        'callback_url' => $redirect_address
      ]);
    }
    catch( \Yabacon\Paystack\Exception\ApiException $e ){
      return false;
    }

    return array(
      "output" => array(
        "type" => "link",
        "link" => $tranx->data->authorization_url
      ),
      "txn" =>  $tranx->data->access_code
      // reference
    );

  }
  public function check_payment( $payment ){

    $client = $this->getClient();
		if ( !$client ) return false;

		try {
      $tranx = $client->transaction->verify([
        'reference' => $payment["_key"],
      ]);
    }
		catch( \Yabacon\Paystack\Exception\ApiException $e ){
      return false;
    }

    if ( 'success' != $tranx->data->status )
    return false;

    return array(
      "amount" => $tranx->data->amount/100,
      "currency" => strtoupper( $tranx->data->currency ),
      "data" => array()
    );

  }

}

?>
