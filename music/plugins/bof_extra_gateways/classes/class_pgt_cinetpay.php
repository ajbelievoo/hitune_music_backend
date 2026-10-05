<?php

if (!defined("bof_root")) die;

class pgt_cinetpay extends bof_type_class
{

  protected $client = false;
  protected $api_key = false;
  protected $site_id = false;
  protected $secret_key = false;
  protected $test = false;

  public function setup_admin()
  {

    bof()->pgt->add_setting("cinetpay", array(
      "gateway_cinetpay_api_key" => array(
        "title" => "API Key",
        "col_name" => "gateway_cinetpay_api_key",
        "input" => array(
          "name" => "gateway_cinetpay_api_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_cinetpay_site_id" => array(
        "title" => "Site ID",
        "col_name" => "gateway_cinetpay_site_id",
        "input" => array(
          "name" => "gateway_cinetpay_site_id",
          "type" => "text",
        ),
        "validator" => array(
          "int",
          array(
            "empty()",
          )
        )
      ),
      "gateway_cinetpay_secret_key" => array(
        "title" => "Secret Key",
        "col_name" => "gateway_cinetpay_secret_key",
        "input" => array(
          "name" => "gateway_cinetpay_secret_key",
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

        $method_result["gateway_cinetpay"] = array(
          "title" => "CinetPay Payment Gateway",
          "url" => "^gateway_cinetpay",
          "link" => "gateway_cinetpay",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_cinetpay/",
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
        "title" => "CinetPay",
        "link"  => "gateway_cinetpay"
      );
      bof()->highlights->setData($highlights);
    });
  }
  public function setup()
  {

    bof()->listen("pgt", "setup", function ($method_args, &$gateways, $loader) {
      bof()->pgt->gateway_add("cinetpay", array(
        "db_name" => "cinetpay",
        "code_name" => "cp",
        "title" => "CinetPay",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=cinetpay.com&sz=256",
        "supported_currencies" => ["XOF", "XAF", "CDF", "GNF", "USD"]
      ));
    });

    bof()->object->endpoint->add("bof_gateway_cinetpay", array(
      "url" => "bof_gateway_cinetpay.js",
      "response_type" => "file",
      "response_data" => array(
        "path" => bof_extra_gateways_root . "/assets/js/bof_gateway_cinetpay.js"
      ),
    ));
  }

  protected function getClient()
  {

    if (!bof()->object->db_setting->get("gateway_cinetpay")) return false;
    if (!($this->api_key = bof()->object->db_setting->get("gateway_cinetpay_api_key"))) return false;
    if (!($this->site_id = bof()->object->db_setting->get("gateway_cinetpay_site_id"))) return false;
    if (!($this->secret_key = bof()->object->db_setting->get("gateway_cinetpay_secret_key"))) return false;
    $this->test = bof()->object->db_setting->get("gateway_cinetpay_test") ? true : false;
    if (!empty($this->client)) return $this->client;

    $this->client = true;
    return $this->client;
  }
  public function get_link($amount, $currency, $order_no, $redirect_address)
  {

    $client = $this->getClient();
    if (!$client) return false;

    $curl = bof()->curl->exe(array(
      "url" => "https://api-checkout.cinetpay.com/v2/payment",
      "posts" => json_encode(array(
        "api_key" => $this->api_key,
        "site_id" => $this->site_id,
        "transaction_id" => $order_no,
        "amount" => $amount,
        "currency" => $currency["iso_code"],
        "description" => "Ajouter des fonds",
        "notify_url" => $redirect_address,
        "return_url" => $redirect_address,
        "channels" => "ALL",
        "mode" => $this->test ? 'SANDBOX' : 'PRODUCTION'
      )),
      "json" => true
    ));

    if ($curl["http_code"] != 200)
      return;

    if (empty($curl["data"]) ? true : empty($curl["data"]["code"]) || empty($curl["data"]["message"]) || empty($curl["data"]["data"]["payment_url"]))
      return;

    return array(
      "output" => array(
        "type" => "link",
        "link" => $curl["data"]["data"]["payment_url"]
      ),
      "txn" =>  md5($curl["data"]["data"]["payment_token"])
    );

    /*
    seamless version
    return array(
      "output" => array(
        "type" => "script",
        "link" => web_address . "api/bof_gateway_cinetpay.js",
        "data" => array(
          "api_key" => (string) $this->api_key,
          "site_id" => $this->site_id,
          "notify_url" => $redirect_address,
          "mode" => $this->test ? 'SANDBOX' : 'PRODUCTION'
        ),
        "transaction" => array(
          "transaction_id" => $order_no,
          "amount" => $amount,
          "currency" => $currency["iso_code"],
          "channels" => "ALL",
          "description" => "Ajouter des fonds"
        ),
        "redirect_url" => $redirect_address
      ),
      "txn" => $order_no
    );
    */
  }
  public function check_payment($payment)
  {

    $client = $this->getClient();
    if (!$client) return false;

    $curl = bof()->curl->exe(array(
      "url" => "https://api-checkout.cinetpay.com/v2/payment/check",
      "posts" => json_encode(array(
        "api_key" => $this->api_key,
        "site_id" => $this->site_id,
        "transaction_id" => $payment["_key"],
      )),
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
}
