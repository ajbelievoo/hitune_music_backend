<?php

if ( !defined( "bof_root" ) ) die;

class pgt_razorpay extends bof_type_class {

  protected $client = false;
  protected $id = false;
  protected $key = false;
  protected $test = false;

  public function setup_admin(){

    bof()->pgt->add_setting("razorpay", array(
      "gateway_razorpay_test" => array(
        "title" => "Test mode",
        "col_name" => "gateway_razorpay_test",
        "input" => array(
          "name" => "gateway_razorpay_test",
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
      "gateway_razorpay_id" => array(
        "title" => "API ID",
        "tip" => "<a href='https://docs.cloud.razorpay.com/commerce/docs/creating-api-key' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_razorpay_id",
        "input" => array(
          "name" => "gateway_razorpay_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_razorpay_key" => array(
        "title" => "API Key",
        "tip" => "<a href='https://docs.cloud.razorpay.com/commerce/docs/creating-api-key' target='_blank'>Click here for more info</a>",
        "col_name" => "gateway_razorpay_key",
        "input" => array(
          "name" => "gateway_razorpay_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_razorpay_webhook_secret" => array(
        "title" => "Webhook Secret",
        "tip" => "Webhook secret from Razorpay dashboard. Endpoint: https://yourdomain.com/api/webhook/razorpay/",
        "col_name" => "gateway_razorpay_webhook_secret",
        "input" => array(
          "name" => "gateway_razorpay_webhook_secret",
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

        $method_result[ "gateway_razorpay" ] = array(
          "title" => "Razorpay Payment Gateway",
          "url" => "^gateway_razorpay",
          "link" => "gateway_razorpay",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_razorpay/",
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
        "title" => "Razorpay",
        "link"  => "gateway_razorpay"
      );
      bof()->highlights->setData( $highlights );

    } );

  }
  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "razorpay", array(
        "db_name" => "razorpay",
        "code_name" => "rp",
        "title" => "Razorpay",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=razorpay.com&sz=256",
        "supported_currencies" => [ "USD", "EUR", "AUD", "CAD", "GBP", "INR" ]
      ) );
    } );

  }

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_razorpay" ) ) return false;
    if ( !( $this->id = bof()->object->db_setting->get( "gateway_razorpay_id" ) ) ) return false;
    if ( !( $this->key = bof()->object->db_setting->get( "gateway_razorpay_key" ) ) ) return false;
    $this->test = bof()->object->db_setting->get( "gateway_razorpay_test" );
    if ( !empty( $this->client ) ) return $this->client;

    require_once( bof_extra_gateways_root . "/classes/third/razorpay/autoload.php" );
		$this->client = new \Razorpay\Api\Api( $this->id, $this->key );
		return $this->client;

  }
  public function get_link( $amount, $currency, $order_no, $redirect_address, $args=[] ){

    $client = $this->getClient();
		if ( !$client ) return false;
    $amount *= 100;

    $notes = array(
      "payment_hash" => $order_no,
    );
    if ( !empty( $args["payment_num"] ) ) $notes["payment_num"] = $args["payment_num"];
    if ( !empty( $args["payment_id"] ) ) $notes["payment_id"] = (string) $args["payment_id"];
    if ( !empty( $args["user_id"] ) ) $notes["user_id"] = (string) $args["user_id"];

    $order = $client->paymentlink->create(array(
      'reference_id'  => $order_no,
      'amount'   => $amount,
      'currency' => strtoupper( $currency["iso_code"] ),
      'callback_url' => $redirect_address,
      'callback_method' => 'get',
      'notes' => $notes,
    ));

    if ( !$order ? true : empty( $order["id"] ) )
    return false;

    return array(
      "output" => array(
        "type" => "link",
        "link" => $order["short_url"]
      ),
      "txn" => $order["id"]
    );

  }
  public function check_payment( $payment ){

    $client = $this->getClient();
		if ( !$client ) return false;

    $payment_data = $client->paymentlink->fetch( $payment["gateway_id"] );

    if ( $payment_data["status"] != "paid" )
    throw new Exception("unpaid");

    return array(
      "amount" => $payment_data["amount_paid"]/100,
      "currency" => strtoupper( $payment_data["currency"] ),
      "data" => array()
    );

  }


}

?>
