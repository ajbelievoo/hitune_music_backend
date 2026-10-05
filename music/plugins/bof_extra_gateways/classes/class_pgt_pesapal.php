<?php

if (!defined("bof_root")) die;

class pgt_pesapal extends bof_type_class
{

  protected $client = false;
  protected $test = false;
  protected $id = false;
  protected $key = false;
  protected $token = false;

  public function setup_admin()
  {

    bof()->pgt->add_setting("pesapal", array(
      "gateway_pesapal_test" => array(
        "title" => "Sandbox mode",
        "col_name" => "gateway_pesapal_test",
        "tip" => "<a href='https://developer.pesapal.com/api3-demo-keys.txt' target='_blank'>Click here</a> to find test api keys for sandbox mode",
        "input" => array(
          "name" => "gateway_pesapal_test",
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

      "gateway_pesapal_id" => array(
        "title" => "Merchant consumer_key",
        "col_name" => "gateway_pesapal_id",
        "input" => array(
          "name" => "gateway_pesapal_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_pesapal_key" => array(
        "title" => "Merchant consumer_secret",
        "col_name" => "gateway_pesapal_key",
        "input" => array(
          "name" => "gateway_pesapal_key",
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

        $method_result["gateway_pesapal"] = array(
          "title" => "Pesapal Payment Gateway",
          "url" => "^gateway_pesapal",
          "link" => "gateway_pesapal",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_pesapal/",
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
        "title" => "Pesapal",
        "link"  => "gateway_pesapal"
      );
      bof()->highlights->setData($highlights);
    });
  }
  public function setup()
  {

    bof()->listen( "pgt", "setup", function ($method_args, &$gateways, $loader) {
      bof()->pgt->gateway_add( "pesapal", array(
        "db_name" => "pesapal",
        "code_name" => "ch",
        "title" => "Pesapal",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=pesapal.com&sz=256",
        "supported_currencies" => [ "ZAR", "USD", "KES", "ZK", "TSH", "USH" ]
      ) );
    } );
  }

  protected function getClient()
  {

    if (!bof()->object->db_setting->get("gateway_pesapal")) return false;
    if (!($this->id = bof()->object->db_setting->get("gateway_pesapal_id"))) return false;
    if (!($this->key = bof()->object->db_setting->get("gateway_pesapal_key"))) return false;
    $this->test = bof()->object->db_setting->get("gateway_pesapal_test") ? true : false;
    $this->token = bof()->object->db_setting->get("gateway_pesapal_token");
    if (!empty($this->client)) return $this->client;

    $this->client = true;
    return $this->client;
  }

  public function get_link($amount, $currency, $order_no, $redirect_address)
  {

    $client = $this->getClient();
    if (!$client) return false;

    try {

      $regIPN = $this->__request("URLSetup/RegisterIPN", array(
        "url" => $redirect_address,
        "ipn_notification_type" => "GET"
      ) );

      $ipn_id = $regIPN["ipn_id"];

      $req = $this->__request("Transactions/SubmitOrderRequest", array(
        "id" => $order_no,
        "currency" => $currency["iso_code"],
        "amount" => $amount,
        "description" => "Wallet charge",
        "callback_url" => $redirect_address,
        "cancellation_url" => $redirect_address,
        "notification_id" => $ipn_id,
        "billing_address" => array(
          "email_address" => bof()->user->check()->data["email"],
        )
      ) );

    } catch (bofException $err) {
      return false;
    }

    return array(
      "output" => array(
        "type" => "link",
        "link" => $req["redirect_url"],
      ),
      "txn" => $req["order_tracking_id"]
    );

  }
  public function check_payment($payment)
  {

    $client = $this->getClient();
    if (!$client) return false;

    try {
      $req = $this->__request("Transactions/GetTransactionStatus?orderTrackingId={$payment["gateway_id"]}", null);
    } catch (bofException $err) {
      return false;
    }

    if ($req["status_code"] != "1")
    throw new Exception("unpaid");

    if ($req["payment_status_description"] != "COMPLETED")
    throw new Exception("unpaid");

    return array(
      "amount" => $req["amount"],
      "currency" => strtoupper($req["currency"]),
      "data" => array()
    );
  }

  protected function __request($endpoint, $postArray)
  {

    $baseUrl = "https://pay.pesapal.com/v3/api/";
    if ( $this->test ){
      $baseUrl = "https://cybqa.pesapal.com/pesapalv3/api/";
    }

    $headers = [];

    if ( $this->token && $endpoint != "Auth/RequestToken" ){
      $headers[] = "Authorization: Bearer {$this->token}";
    }

    $curl = bof()->curl->exe(array(
      "url"  => $baseUrl . $endpoint,
      "type" => "json",
      "json" => true,
      "agent" => "rkhm",
      "posts" => $postArray ? json_encode( $postArray ) : null,
      "headers" => $headers
    ));

    if ( $curl["http_code"] == 401 && $endpoint !== "Auth/RequestToken" ){

      try {

        $req = $this->__request("Auth/RequestToken", array(
          "consumer_key" => $this->id,
          "consumer_secret" => $this->key
        ) );

        if ( !empty( $req["token"] ) ){
          bof()->object->db_setting->set( "gateway_pesapal_token", $req["token"] );
          $this->token = $req["token"];
        }
        else {
          throw new Exception("consumer_key||consumer_secret is invalid. Unable to create token (2)");
        }

      } catch (bofException|Exception $err) {

        throw new Exception("consumer_key||consumer_secret is invalid. Unable to create token");

      }

      return $this->__request( $endpoint, $postArray );

    }

    if ($curl["http_code"] != 200 ? true : (empty($curl["data"]["status"]) ? true : $curl["data"]["status"] !== "200"))
      throw new Exception(!empty($curl["data"]["error"]["message"]) ? $curl["data"]["error"]["message"] : "failed");

    return $curl["data"];
  }
}
