<?php

if (!defined("bof_root")) die;

class pgt_kkiapay extends bof_type_class
{

  protected $client = false;
  protected $public = false;
  protected $private = false;
  protected $secret = false;
  protected $test = false;

  public function setup_admin()
  {

    bof()->pgt->add_setting("kkiapay", array(
      "gateway_kkiapay_test" => array(
        "title" => "Sandbox",
        "tip" => "<a href='https://docs.kkiapay.me/v1/v/en-1.0.0/compte/kkiapay-sandbox-guide-de-test' target='_blank'>Sandbox</a> allows you to test everything before going live",
        "col_name" => "gateway_kkiapay_test",
        "input" => array(
          "name" => "gateway_kkiapay_test",
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
      "gateway_kkiapay_id" => array(
        "title" => "Public API Key",
        "col_name" => "gateway_kkiapay_id",
        "input" => array(
          "name" => "gateway_kkiapay_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_kkiapay_id2" => array(
        "title" => "Private Api Key",
        "col_name" => "gateway_kkiapay_id2",
        "input" => array(
          "name" => "gateway_kkiapay_id2",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_kkiapay_key" => array(
        "title" => "Secret API Key",
        "col_name" => "gateway_kkiapay_key",
        "input" => array(
          "name" => "gateway_kkiapay_key",
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

        $method_result["gateway_kkiapay"] = array(
          "title" => "KKiaPay Payment Gateway",
          "url" => "^gateway_kkiapay",
          "link" => "gateway_kkiapay",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_kkiapay/",
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
        "title" => "KKiaPay",
        "link"  => "gateway_kkiapay"
      );
      bof()->highlights->setData($highlights);
    });
  }
  public function setup()
  {

    bof()->listen("pgt", "setup", function ($method_args, &$gateways, $loader) {
      bof()->pgt->gateway_add("kkiapay", array(
        "db_name" => "kkiapay",
        "code_name" => "kk",
        "title" => "KKiaPay",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=kkiapay.me&sz=256",
        "supported_currencies" => ["CFA", "XOF"]
      ));
    });

    bof()->object->endpoint->add("bof_gateway_kkiapay", array(
      "url" => "bof_gateway_kkiapay.js",
      "response_type" => "file",
      "response_data" => array(
        "path" => bof_extra_gateways_root . "/assets/js/bof_gateway_kkiapay.js"
      ),
    ));
  }

  protected function getClient()
  {

    if (!bof()->object->db_setting->get("gateway_kkiapay")) return false;
    if (!($this->public = bof()->object->db_setting->get("gateway_kkiapay_id"))) return false;
    if (!($this->private = bof()->object->db_setting->get("gateway_kkiapay_id2"))) return false;
    if (!($this->secret = bof()->object->db_setting->get("gateway_kkiapay_key"))) return false;
    $this->test = bof()->object->db_setting->get("gateway_kkiapay_test") ? true : false;
    if (!empty($this->client)) return $this->client;

    require_once(bof_extra_gateways_root . "/classes/third/kkiapay/autoload.php");

    $this->client = new \Kkiapay\Kkiapay(
      $this->public,
      $this->private,
      $this->secret,
      $this->test ? true : false
    );

    return $this->client;
  }
  public function get_link($amount, $currency, $order_no, $redirect_address)
  {

    $client = $this->getClient();
    if (!$client) return false;

    return array(
      "output" => array(
        "type" => "script",
        "link" => web_address . "api/bof_gateway_kkiapay.js",
        "data" => array(
          "amount" => $amount,
          "callback" => $redirect_address,
          "key" => $this->public,
          "sandbox" => $this->test ? true : false
        )
      ),
      "txn" => $order_no
    );
  }
  public function check_payment($payment)
  {

    $client = $this->getClient();
    if (!$client) return false;

    if (
      !($payment_id = bof()->nest->user_input("get", "transaction_id", "string", ["strict" => true, "strict_regex" => "[0-9a-zA-Z\-_]"]))
    ) return false;

    $verify = $client->verifyTransaction($payment_id);

    if ($verify->status != "SUCCESS")
    throw new Exception("unpaid");

    return array(
      "amount" => $verify->amount,
      "currency" => "XOF",
      "data" => array()
    );
  }
}
