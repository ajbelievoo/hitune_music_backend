<?php

if ( !defined( "bof_root" ) ) die;

class pgt_yoomoney extends bof_type_class {

  protected $client = false;
  protected $id = false;
  protected $key = false;
  protected $test = false;

  public function setup_admin(){

    bof()->pgt->add_setting("yoomoney", array(
      "gateway_yoomoney_test" => array(
        "title" => "Test mode",
        "col_name" => "gateway_yoomoney_test",
        "input" => array(
          "name" => "gateway_yoomoney_test",
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
      "gateway_yoomoney_id" => array(
        "title" => "Shop ID",
        "tip" => "Get it from <a href='https://yookassa.ru/my/shop-settings' target='_blank'>here</a>. Make sure your default currency matches your chosen currency on YooMoney",
        "col_name" => "gateway_yoomoney_id",
        "input" => array(
          "name" => "gateway_yoomoney_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_yoomoney_key" => array(
        "title" => "API Secret Key",
        "tip" => "Get it from <a href='https://yookassa.ru/my/merchant/integration/api-keys' target='_blank'>here</a>",
        "col_name" => "gateway_yoomoney_key",
        "input" => array(
          "name" => "gateway_yoomoney_key",
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

        $method_result[ "gateway_yoomoney" ] = array(
          "title" => "yoomoney Payment Gateway",
          "url" => "^gateway_yoomoney",
          "link" => "gateway_yoomoney",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_yoomoney/",
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
        "title" => "YooMoney",
        "link"  => "gateway_yoomoney"
      );
      bof()->highlights->setData( $highlights );

    } );

  }
  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "yoomoney", array(
        "db_name" => "yoomoney",
        "code_name" => "ym",
        "title" => "YooMoney",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=yookassa.ru&sz=256",
        "supported_currencies" => [ "USD", "EUR", "RUB", "GBP" ]
      ) );
    } );

  }

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_yoomoney" ) ) return false;
    if ( !( $this->id = bof()->object->db_setting->get( "gateway_yoomoney_id" ) ) ) return false;
    if ( !( $this->key = bof()->object->db_setting->get( "gateway_yoomoney_key" ) ) ) return false;
    $this->test = bof()->object->db_setting->get( "gateway_yoomoney_test" );
    if ( !empty( $this->client ) ) return $this->client;

    require_once( bof_extra_gateways_root . "/classes/third/yoomoney/autoload.php" );

		$this->client = new \YooKassa\Client();
    $this->client->setAuth( $this->id, $this->key );

		return $this->client;

  }
  public function get_link( $amount, $currency, $order_no, $redirect_address ){

    $client = $this->getClient();
		if ( !$client ) return false;

    $paymentArray = array(
      'amount' => array(
        'value' => $amount,
        'currency' => strtoupper( $currency["iso_code"] ),
      ),
      'confirmation' => array(
        'type' => 'redirect',
        'return_url' => $redirect_address,
      ),
      'capture' => true,
      'description' => 'Payment No. ' . $order_no,
      'metadata' => array(
        'order_id' => $order_no,
      ),
      'receipt' => array(
        "customer" => array(
          "email" => bof()->user->check()->data["email"]
        ),
        'items' => array(
          [
            'description' => 'Payment No. ' . $order_no,
            'quantity' => '1.00',
            'amount' => [
              'value' => $amount,
              'currency' => strtoupper( $currency["iso_code"] )
            ],
            'vat_code' => '2',
          ],
        )
      )
    );

    if ( $this->test )
    $paymentArray["test"] = true;

    $payment = $client->createPayment(
      $paymentArray,
      uniqid('', true)
    );

    if ( !$payment ? true : empty( $payment->getID() ) )
    return false;

    return array(
      "output" => array(
        "type" => "link",
        "link" => $payment->getConfirmation()->getConfirmationUrl()
      ),
      "txn" => $payment->getID()
    );

  }
  public function check_payment( $payment ){

    $client = $this->getClient();
		if ( !$client ) return false;

    $payment = $client->getPaymentInfo( $payment["gateway_id"] );

    if ( $payment->getStatus() != "succeeded" )
    throw new Exception("unpaid");

    if ( !$payment->getPaid() )
    throw new Exception("unpaid");

    return array(
      "amount" => floatval( $payment->getAmount()->getValue() ),
      "currency" => $payment->getAmount()->getCurrency(),
      "data" => array()
    );

  }


}

?>
