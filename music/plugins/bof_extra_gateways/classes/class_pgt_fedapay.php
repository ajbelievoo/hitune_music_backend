<?php

if (!defined("bof_root")) die;

class pgt_fedapay extends bof_type_class
{

  protected $client = false;
  protected $test = false;
  protected $id = false;
  protected $key = false;
  protected $token = false;

  public function setup_admin()
  {

    bof()->pgt->add_setting("fedapay", array(
      "gateway_fedapay_test" => array(
        "title" => "Sandbox mode",
        "col_name" => "gateway_fedapay_test",
        "tip" => "<a href='https://docs.fedapay.com/introduction/en/fedapay-en#how-to-get-started-%3F' target='_blank'>Click here</a> for more info. <a href='https://docs-v1.fedapay.com/payments/test' target='_blank'>Click here</a> for test numbers",
        "input" => array(
          "name" => "gateway_fedapay_test",
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

      "gateway_fedapay_id" => array(
        "title" => "API Public key",
        "col_name" => "gateway_fedapay_id",
        "input" => array(
          "name" => "gateway_fedapay_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_fedapay_key" => array(
        "title" => "API Secret key",
        "col_name" => "gateway_fedapay_key",
        "input" => array(
          "name" => "gateway_fedapay_key",
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
    bof()->listen("client_config", "get_pages_after", function ($method_args, &$method_result, $loader) {

      if (is_array($method_result)) {

        $method_result["gateway_fedapay"] = array(
          "title" => "FedaPay Payment Gateway",
          "url" => "^gateway_fedapay",
          "link" => "gateway_fedapay",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_fedapay/",
              "key" => "setting"
            )
          ),
          "events" => (object)[],
          "__sb_family" => "business",
        );
      }
    });
    bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {

      $sb_family = $method_args[0];

      $highlights = bof()->highlights->getData();

      $highlights["business_links"]["items"]["payment_gateways"]["args"]["childs"][] = array(
        "icon"  => "credit_card",
        "title" => "FedaPay",
        "link"  => "gateway_fedapay"
      );
      bof()->highlights->setData($highlights);
    });
  }
  public function setup()
  {

    bof()->listen( "pgt", "setup", function ($method_args, &$gateways, $loader) {
      bof()->pgt->gateway_add( "fedapay", array(
        "db_name" => "fedapay",
        "code_name" => "ch",
        "title" => "FedaPay",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=fedapay.com&sz=256",
        "supported_currencies" => [ "XOF" ]
      ) );
    } );
  }

  protected function getClient()
  {

    if (!bof()->object->db_setting->get("gateway_fedapay")) return false;
    if (!($this->id = bof()->object->db_setting->get("gateway_fedapay_id"))) return false;
    if (!($this->key = bof()->object->db_setting->get("gateway_fedapay_key"))) return false;
    $this->test = bof()->object->db_setting->get("gateway_fedapay_test") ? true : false;
    if (!empty($this->client)) return $this->client;

    require_once(bof_extra_gateways_root . "/classes/third/fedapay/vendor/autoload.php");

    $this->client = true;
    return $this->client;

  }

  public function get_link($amount, $currency, $order_no, $redirect_address)
  {

    $client = $this->getClient();
    if (!$client) return false;

    \FedaPay\Fedapay::setApiKey($this->key);
    if ($this->test){
      \FedaPay\Fedapay::setEnvironment('sandbox');
    } else {
      \FedaPay\Fedapay::setEnvironment('live');
    }

    try {

      $transaction = \FedaPay\Transaction::create([
        'description' => 'Wallet Charge',
        'amount' => $amount,
        'currency' => ['iso' => $currency["iso_code"]],
        'callback_url' => $redirect_address,
        'mode' => 'mtn_open',
        'metadata' => $order_no
      ]);

      $transaction_link = $transaction->generateToken();

    } catch (bofException $err) {
      return false;
    }

    return array(
      "output" => array(
        "type" => "link",
        "link" => $transaction_link->url,
      ),
      "txn" => $transaction->id
    );

  }
  public function check_payment($payment)
  {

    $client = $this->getClient();
    if (!$client) return false;

    try {

      \FedaPay\Fedapay::setApiKey($this->key);
      if ($this->test){
        \FedaPay\Fedapay::setEnvironment('sandbox');
      } else {
        \FedaPay\Fedapay::setEnvironment('live');
      }
      
      $transaction = \FedaPay\Transaction::retrieve($payment["gateway_id"]);
      $currency = \FedaPay\Currency::retrieve($transaction->currency_id);

    } catch( Error|Exception $err ){
      throw new Exception("unpaid");
    }

    if ( $transaction->status != "approved" )
    throw new Exception("unpaid");

    return array(
      "amount" => $transaction->amount,
      "currency" => strtoupper($currency->iso),
      "data" => array()
    );
  }

}
