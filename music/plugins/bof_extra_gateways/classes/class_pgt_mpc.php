<?php

if ( !defined( "bof_root" ) ) die;

class pgt_mpc extends bof_type_class {

  protected $client = false;
  protected $key = false;
  protected $email = false;
  protected $token = false;

  public function setup_admin(){

    bof()->pgt->add_setting("mpc", array(
      "gateway_mpc_key" => array(
        "title" => "API-KEY",
        "col_name" => "gateway_mpc_key",
        "input" => array(
          "name" => "gateway_mpc_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_mpc_email" => array(
        "title" => "Merchant Email",
        "col_name" => "gateway_mpc_email",
        "input" => array(
          "name" => "gateway_mpc_email",
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

        $method_result[ "gateway_mpc" ] = array(
          "title" => "MoneyPoolsCash Payment Gateway",
          "url" => "^gateway_mpc",
          "link" => "gateway_mpc",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_mpc/",
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
        "title" => "MoneyPoolsCash",
        "link"  => "gateway_mpc"
      );
      bof()->highlights->setData( $highlights );

    } );

  }
  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "mpc", array(
        "db_name" => "mpc",
        "code_name" => "mp",
        "title" => "MoneyPoolsCash",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=moneypoolscash.com&sz=256",
        "supported_currencies" => [ "USD" ]
      ) );
    } );

  }

  protected function getClient(){

    if ( !bof()->object->db_setting->get( "gateway_mpc" ) ) return false;
    if ( !( $this->key = bof()->object->db_setting->get( "gateway_mpc_key" ) ) ) return false;
    if ( !( $this->email = bof()->object->db_setting->get( "gateway_mpc_email" ) ) ) return false;
    // $this->token = bof()->object->db_setting->get( "gateway_mpc_token" );
    $this->token = false;
    if ( !empty( $this->client ) ) return $this->client;

    $this->client = true;
		return $this->client;

  }

  public function get_link( $amount, $currency, $order_no, $redirect_address ){

    $client = $this->getClient();
    if ( !$client ) return false;

    try {
      $req = $this->__request( "payrequest", array(
        "merchant_email" => $this->email,
        "amount" => $amount,
        "currency" => $currency["iso_code"],
        "return_url" => $redirect_address,
        "cancel_url" => $redirect_address,
        "merchant_ref" => $order_no
      ) );
    } catch( bofException $err ){
      return false;
    }

    if ( empty( $req["status"] ) ? true : strtolower( $req["status"] ) != 'success' )
    return false;

    return array(
      "output" => array(
        "type" => "link",
        "link" => $req["data"]["redirect_url"]
      ),
      "txn" =>  $req["data"]["trx"]
    );

  }
  public function check_payment( $payment ){

    $client = $this->getClient();
		if ( !$client ) return false;
		try {
      $req = $this->__request( "gettrx", array(
        'merchant_email' => $this->email,
        'trx' => $payment["gateway_id"],
        'merchant_ref' => $payment["_key"],
      ) );
    } catch( bofException $err ){
      return false;
    }

    if ( empty( $req["data"]["status"] ) ? true : $req["data"]["status"] !== 'completed' )
    throw new Exception("Not paid yet");

    return array(
      "amount" => (float) $req["data"]["amount"],
      "currency" => strtoupper( $req["data"]["currency"] ),
      "data" => array()
    );

  }

  protected function __getNewToken(){

    $curl = bof()->curl->exe( array(
      "url"  => "https://moneypoolscash.com/gettoken?merchant_email={$this->email}",
      "headers" => array(
        "API-KEY: {$this->key}"
      ),
      "cache" => false,
      "cache_load" => false
    ) );

    if ( $curl["http_code"] != 200 )
    return false;

    if ( empty( $curl["data"]["code"] ) ? true : $curl["data"]["code"] != 200 )
    return false;

    if ( empty( $curl["data"]["status"] ) ? true : strtolower( $curl["data"]["status"] ) != 'success' )
    return false;

    $token = $this->token = $curl["data"]["data"]["token"];
    bof()->object->db_setting->set( "gateway_mpc_token", $token );
    return $token;

  }
  protected function __request( $endpoint, $postArray ){

    if ( !$this->token ){
      if ( !$generateToken = $this->__getNewToken() )
      throw new Exception("Failed to generate token");
    }

    $curl = bof()->curl->exe( array(
      "url"  => "https://moneypoolscash.com/{$endpoint}",
      "posts" => json_encode($postArray),
      "custom_request" => "get",
      "posts_force_get" => true,
      "json" => true,
      "headers" => array(
        "API-KEY: {$this->key}",
        "token: {$this->token}",
      ),
    ) );

    if ( $curl["http_code"] != 200 )
    throw new Exception( "invalid http_code" );

    if ( empty( $curl["data"]["code"] ) ? true : $curl["data"]["code"] != 200 )
    throw new Exception( "invalid data_code" );

    return $curl["data"];

  }

}

?>
