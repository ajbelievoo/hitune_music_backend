<?php

if (!defined("bof_root")) die;

class pgt_chapa extends bof_type_class
{

  protected $client = false;
  protected $test = false;
  protected $id = false;
  protected $key = false;
  protected $key2 = false;

  public function setup_admin()
  {

    bof()->pgt->add_setting("chapa", array(
      "gateway_chapa_test" => array(
        "title" => "Test mode",
        "col_name" => "gateway_chapa_test",
        "input" => array(
          "name" => "gateway_chapa_test",
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

      "gateway_chapa_id" => array(
        "title" => "Public Key",
        "col_name" => "gateway_chapa_id",
        "input" => array(
          "name" => "gateway_chapa_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_chapa_key" => array(
        "title" => "Secret Key",
        "col_name" => "gateway_chapa_key",
        "input" => array(
          "name" => "gateway_chapa_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_chapa_key2" => array(
        "title" => "Encryption key",
        "col_name" => "gateway_chapa_key2",
        "input" => array(
          "name" => "gateway_chapa_key2",
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

        $method_result["gateway_chapa"] = array(
          "title" => "Chapa Payment Gateway",
          "url" => "^gateway_chapa",
          "link" => "gateway_chapa",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_chapa/",
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
        "title" => "Chapa",
        "link"  => "gateway_chapa"
      );
      bof()->highlights->setData($highlights);
    });
  }
  public function setup()
  {

    bof()->listen("pgt", "setup", function ($method_args, &$gateways, $loader) {
      bof()->pgt->gateway_add("chapa", array(
        "db_name" => "chapa",
        "code_name" => "ch",
        "title" => "Chapa",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=chapa.co&sz=256",
        "supported_currencies" => ["ETB", "USD"]
      ));
    });
  }

  protected function getClient()
  {

    if (!bof()->object->db_setting->get("gateway_chapa")) return false;
    if (!($this->id = bof()->object->db_setting->get("gateway_chapa_id"))) return false;
    if (!($this->key = bof()->object->db_setting->get("gateway_chapa_key"))) return false;
    if (!($this->key2 = bof()->object->db_setting->get("gateway_chapa_key2"))) return false;
    $this->test = bof()->object->db_setting->get("gateway_chapa_test") ? true : false;
    if (!empty($this->client)) return $this->client;

    $this->client = true;
    return $this->client;
  }

  public function get_link($amount, $currency, $order_no, $redirect_address)
  {

    $client = $this->getClient();
    if (!$client) return false;

    $postArray = array(
      "amount" => $amount,
      "currency" => $currency["iso_code"],
      "email" => bof()->user->check()->data["email"],
      "tx_ref" => $order_no,
      "callback_url" => $redirect_address,
      "return_url" => $redirect_address,
    );

    try {
      $req = $this->__request("transaction/initialize", $postArray);
    } catch (bofException $err) {
      return false;
    }

    if (empty($req["data"]["checkout_url"]))
      return false;

    return array(
      "output" => array(
        "type" => "link",
        "link" => $req["data"]["checkout_url"]
      ),
      "txn" =>  $order_no
    );
  }
  public function check_payment($payment)
  {

    $client = $this->getClient();
    if (!$client) return false;

    try {
      $req = $this->__request("transaction/verify/{$payment["_key"]}", null);
    } catch (bofException $err) {
      return false;
    }

    if ($req["data"]["status"] !== "success")
      return false;

    return array(
      "amount" => $req["data"]["amount"],
      "currency" => strtoupper($req["data"]["currency"]),
      "data" => array()
    );
  }

  protected function __request($endpoint, $postArray)
  {

    $curl = bof()->curl->exe(array(
      "url"  => "https://api.chapa.co/v1/{$endpoint}",
      "posts" => $postArray,
      "headers" => array(
        "Authorization: Bearer {$this->key}"
      ),
    ));

    if ($curl["http_code"] != 200 ? true : (empty($curl["data"]["status"]) ? true : $curl["data"]["status"] !== "success"))
      throw new Exception(!empty($curl["data"]["message"]) ? $curl["data"]["message"] : "failed");

    return $curl["data"];
  }
}
