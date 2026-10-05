<?php

if (!defined("bof_root")) die;

class pgt_cashfree extends bof_type_class
{

  protected $client = false;
  protected $app_id = false;
  protected $secret_key = false;
  protected $test = false;

  public function setup_admin()
  {

    bof()->pgt->add_setting("cashfree", array(
      "gateway_cashfree_test" => array(
        "title" => "Development Mode",
        "col_name" => "gateway_cashfree_test",
        "input" => array(
          "name" => "gateway_cashfree_test",
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
      "gateway_cashfree_app_id" => array(
        "title" => "AppID",
        "col_name" => "gateway_cashfree_app_id",
        "input" => array(
          "name" => "gateway_cashfree_app_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_cashfree_secret_key" => array(
        "title" => "Secret Key",
        "col_name" => "gateway_cashfree_secret_key",
        "input" => array(
          "name" => "gateway_cashfree_secret_key",
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

        $method_result["gateway_cashfree"] = array(
          "title" => "Cashfree Payment Gateway",
          "url" => "^gateway_cashfree",
          "link" => "gateway_cashfree",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_cashfree/",
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
        "title" => "Cashfree",
        "link"  => "gateway_cashfree"
      );
      bof()->highlights->setData($highlights);
    });
    
  }
  public function setup()
  {

    bof()->listen("pgt", "setup", function ($method_args, &$gateways, $loader) {
      bof()->pgt->gateway_add("cashfree", array(
        "db_name" => "cashfree",
        "code_name" => "cf",
        "title" => "Cashfree",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=cashfree.com&sz=256",
        "supported_currencies" => ["USD", "GBP", "EUR", "AED", "CAD"]
      ));
    });
  }

  protected function getClient()
  {

    if (!bof()->object->db_setting->get("gateway_cashfree")) return false;
    if (!($this->app_id = bof()->object->db_setting->get("gateway_cashfree_app_id"))) return false;
    if (!($this->secret_key = bof()->object->db_setting->get("gateway_cashfree_secret_key"))) return false;
    $this->test = bof()->object->db_setting->get("gateway_cashfree_test") ? true : false;
    if (!empty($this->client)) return $this->client;

    $this->client = true;
    return $this->client;
  }
  public function get_link($amount, $currency, $order_no, $redirect_address)
  {

    $client = $this->getClient();
    if (!$client) return false;

    $req = $this->__request("pg/orders", array(
      "order_amount" => $amount,
      "order_currency" => $currency['iso_code'],
      "customer_details" => array(
        "customer_id" => uniqid(),
        "customer_email" => bof()->user->check()->data["email"],
        "customer_phone" => "9908734801"
      ),
      "order_meta" => array(
        "return_url" => $redirect_address,
        "notify_url" => $redirect_address,
      )
    ));
  }
  public function check_payment($payment)
  {

    $client = $this->getClient();
    if (!$client) return false;

    $curl = bof()->curl->exe(array(
      "url" => "https://api-checkout.cashfree.com/v2/payment/check",
      "posts" => array(
        "api_key" => $this->app_id,
        "site_id" => $this->site_id,
        "transaction_id" => $payment["_key"],
      ),
      "json" => true
    ));

    if ($curl["http_code"] != 200)
      return;

    if (empty($curl["data"]) ? true : empty($curl["data"]["message"]) || empty($curl["data"]["data"]["amount"]))
      return;

    if (!empty($curl["data"]["code"]))
      return;

    if ($curl["data"]["message"] != "SUCCES" && $curl["data"]["message"] != "SUCCESS")
      return;

    if ($curl["data"]["data"]["status"] != "ACCEPTED")
      return;

    return array(
      "amount" => $curl["data"]["data"]["amount"],
      "currency" => $curl["data"]["data"]["currency"],
      "data" => array()
    );
  }

  protected function __request($endpoint, $postArray = null)
  {

    $base_url = $this->test ? "https://sandbox.cashfree.com/" : "https://api.cashfree.com/";

    $curl = bof()->curl->exe(array(
      "url"  => "{$base_url}{$endpoint}",
      "json" => true,
      "posts" => $postArray ? json_encode($postArray) : false,
      "headers" => array(
        "x-client-id: " . $this->app_id,
        "x-client-secret: " . $this->secret_key,
        "x-api-version: " . "2022-09-01",
      ),
    ));

    var_dump($curl["data"]);
    die;


    if ($curl["http_code"] != 200)
      return false;


    return $curl["data"];
  }
}
