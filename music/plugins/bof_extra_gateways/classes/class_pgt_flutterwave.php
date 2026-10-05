<?php

if ( !defined( "bof_root" ) ) die;

class pgt_flutterwave extends bof_type_class {

  protected $client = false;
  protected $methods = false;
  protected $public = false;
  protected $private = false;
  protected $encryption_key = false;
  protected $test = false;

  public function _get_methods($keyOnly=false){

    $methods = [
      "card"=> [
        "card",
        "Card payment"
      ],
      "account"=> [
        "account",
        "Bank account (direct debit)"
      ],
      "banktransfer"=> [
        "banktransfer",
        "Bank transfer"
      ],
      "mpesa"=> [
        "mpesa",
        "M-Pesa"
      ],
      "mobilemoneyghana"=> [
        "mobilemoneyghana",
        "Mobile money Ghana"
      ],
      "mobilemoneyfranco"=> [
        "mobilemoneyfranco",
        "Mobile money Francophone Africa"
      ],
      "mobilemoneyuganda"=> [
        "mobilemoneyuganda",
        "Mobile money Uganda"
      ],
      "mobilemoneyrwanda"=> [
        "mobilemoneyrwanda",
        "Mobile money Rwanda"
      ],
      "mobilemoneyzambia"=> [
        "mobilemoneyzambia",
        "Mobile money Zambia"
      ],
      "barter"=> [
        "barter",
        "Barter payment"
      ],
      "nqr"=> [
        "nqr",
        "QR payment"
      ],
      "ussd"=> [
        "ussd",
        "USSD"
      ],
      "credit"=> [
        "credit",
        "Credit payment"
      ]
    ];

    return $keyOnly ? array_keys( $methods ) : array_values( $methods );

  }
  public function setup_admin(){

    $_fw_pm_os = $this->_get_methods();
    $_fw_pm_vs = $this->_get_methods(true);

    bof()->pgt->add_setting("flutterwave", array(
      "gateway_flutterwave_test" => array(
        "title" => "Test mode",
        "tip" => "<a href='https://developer.flutterwave.com/docs/integration-guides/testing-helpers' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_flutterwave_test",
        "input" => array(
          "name" => "gateway_flutterwave_test",
          "type" => "checkbox",
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          )
        )
      ),
      "gateway_flutterwave_public" => array(
        "title" => "Public key",
        "tip" => "<a href='https://developer.flutterwave.com/docs/integration-guides/authentication' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_flutterwave_public",
        "input" => array(
          "name" => "gateway_flutterwave_public",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_flutterwave_private" => array(
        "title" => "Secret key",
        "tip" => "<a href='https://developer.flutterwave.com/docs/integration-guides/authentication' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_flutterwave_private",
        "input" => array(
          "name" => "gateway_flutterwave_private",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_flutterwave_encryption_key" => array(
        "title" => "Encryption key",
        "tip" => "<a href='https://developer.flutterwave.com/docs/integration-guides/authentication' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_flutterwave_encryption_key",
        "input" => array(
          "name" => "gateway_flutterwave_encryption_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_flutterwave_methods" => array(
        "title" => "Payment methods",
        "tip" => "Also need to be enabled in your dashboard. Your default currency should match payment method's supported currency. <a href='https://developer.flutterwave.com/docs/collecting-payments/payment-methods/' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_flutterwave_methods",
        "input" => array(
          "name" => "gateway_flutterwave_methods",
          "type" => "select_m",
          "options" => $_fw_pm_os
        ),
        "validator" => array(
          "in_array",
          array(
            "empty()",
            "values" => $_fw_pm_vs
          )
        )
      ),
    ));
    bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

      if ( is_array( $method_result ) ){

        $method_result[ "gateway_flutterwave" ] = array(
          "title" => "Flutterwave Payment Gateway",
          "url" => "^gateway_flutterwave",
          "link" => "gateway_flutterwave",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_flutterwave/",
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
        "title" => "Flutterwave",
        "link"  => "gateway_flutterwave"
      );
      bof()->highlights->setData( $highlights );

    } );

  }
  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "flutterwave", array(
        "db_name" => "flutterwave",
        "code_name" => "fw",
        "title" => "Flutterwave",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=flutterwave.com&sz=256",
        "supported_currencies" => [ "USD", "EUR", "NGN", "GHS", "GBP", "KES", "UGX", "XAF", "RWF", "ZMW", "GBP", "EGP" ]
      ) );
    } );

    bof()->object->endpoint->add( "bof_gateway_flutterwave", array(
      "url" => "bof_gateway_flutterwave.js",
      "response_type" => "file",
      "response_data" => array(
        "path" => bof_extra_gateways_root . "/assets/js/bof_gateway_flutterwave.js"
      ),
    ) );

  }

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_flutterwave" ) ) return false;
    if ( !( $this->public = bof()->object->db_setting->get( "gateway_flutterwave_public" ) ) ) return false;
    if ( !( $this->private = bof()->object->db_setting->get( "gateway_flutterwave_private" ) ) ) return false;
    if ( !( $this->encryption_key = bof()->object->db_setting->get( "gateway_flutterwave_encryption_key" ) ) ) return false;
    $this->test = bof()->object->db_setting->get( "gateway_flutterwave_test" );
    $this->methods = bof()->object->db_setting->get( "gateway_flutterwave_methods" );
    if ( !empty( $this->client ) ) return $this->client;

		$this->client = true;
		return $this->client;

  }
  public function get_link( $amount, $currency, $order_no, $redirect_address ){

    $client = $this->getClient();
		if ( !$client ) return false;
    return array(
      "output" => array(
        "type" => "script",
        "link" => web_address . "api/bof_gateway_flutterwave.js",
        "data" => array(
          "public_key" => $this->public,
          "tx_ref" => $order_no,
          "amount" => $amount,
          "currency" => $currency["iso_code"],
          "payment_options" => implode( ", ", explode( ",", $this->methods ) ),
          "redirect_url" => $redirect_address,
          "customer" => array(
            "email" => bof()->user->check()->data["email"],
            "name" => bof()->user->check()->data["username"]
          ),
          "customizations"=> array(
            "title" => "Charging Wallet",
            "description" => 'Wallet charge For ' . bof()->object->db_setting->get("sitename"),
          )
        )
      ),
      "txn" => $order_no
    );

  }
  public function check_payment( $payment ){

    $client = $this->getClient();
		if ( !$client ) return false;

    $transaction_id = bof()->nest->user_input( "get", "transaction_id", "int" );

    $response = false;
    if ( $transaction_id ){
      $response = bof()->curl->exe( array(
  			"url" => "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify",
  			"headers" => array(
  				"Content-Type: application/json",
  				"Authorization: Bearer {$this->private}"
  			)
  		) )["data"];
    }

    if ( empty( $response ) ? true :
      ( empty( $response["data"]["tx_ref"] ) ? true : $response["data"]["tx_ref"] != $payment["_key"] ) ||
      ( empty( $response["data"]["status"] ? true : $response["data"]["status"] !== "successful" ) ) )
    throw new Exception("unpaid");

    return array(
      "amount" => $response["data"]["amount"],
      "currency" => $response["data"]["currency"],
      "data" => $response["data"]
    );

  }


}

?>
