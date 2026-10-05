<?php

if (!defined("bof_root")) die;

class pgt_payu extends bof_type_class {

  protected $key = false;
  protected $salt = false;
  protected $client_id = false;
  protected $client_secret = false;
  protected $merchant_id = false;
  protected $test = false;

  public function setup_admin(){

    bof()->pgt->add_setting("payu", array(
      "gateway_payu_test" => array(
        "title" => "Test mode",
        "col_name" => "gateway_payu_test",
        "input" => array(
          "name" => "gateway_payu_test",
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
      "gateway_payu_key" => array(
        "title" => "Merchant Key",
        "col_name" => "gateway_payu_key",
        "input" => array(
          "name" => "gateway_payu_key",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_payu_salt" => array(
        "title" => "Merchant Salt",
        "col_name" => "gateway_payu_salt",
        "input" => array(
          "name" => "gateway_payu_salt",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_payu_client_id" => array(
        "title" => "OAuth Client ID (optional)",
        "tip" => "Only needed for PayU payment-links / subscriptions API.",
        "col_name" => "gateway_payu_client_id",
        "input" => array(
          "name" => "gateway_payu_client_id",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_payu_client_secret" => array(
        "title" => "OAuth Client Secret (optional)",
        "col_name" => "gateway_payu_client_secret",
        "input" => array(
          "name" => "gateway_payu_client_secret",
          "type" => "text",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
          )
        )
      ),
      "gateway_payu_merchant_id" => array(
        "title" => "Merchant ID (optional)",
        "col_name" => "gateway_payu_merchant_id",
        "input" => array(
          "name" => "gateway_payu_merchant_id",
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

    bof()->listen("client_config", "get_pages_after", function($method_args, &$method_result, $loader){

      if (is_array($method_result)){

        $method_result["gateway_payu"] = array(
          "title" => "PayU Payment Gateway",
          "url" => "^gateway_payu",
          "link" => "gateway_payu",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/gateway_payu/",
              "key" => "setting"
            )
          ),
          "events" => (object)[],
          "__sb_family" => "business",
        );

      }

    });

    bof()->listen("highlights", "display_pre", function($method_args, $method_result, $loader){

      $sb_family = $method_args[0];

      $highlights = bof()->highlights->getData();

      $highlights["business_links"]["items"]["payment_gateways"]["args"]["childs"][] = array(
        "icon"  => "credit_card",
        "title" => "PayU",
        "link"  => "gateway_payu"
      );
      bof()->highlights->setData($highlights);

    });

  }

  public function setup(){

    bof()->listen("pgt", "setup", function($method_args, &$gateways, $loader){
      bof()->pgt->gateway_add("payu", array(
        "db_name" => "payu",
        "code_name" => "pu",
        "title" => "PayU",
        "icon_t" => "image",
        "icon_v" => "https://www.google.com/s2/favicons?domain=payu.in&sz=256",
        "supported_currencies" => ["INR", "USD"]
      ));
    });

  }

  protected function getConfig(){

    if (!bof()->object->db_setting->get("gateway_payu")) return false;
    if (!($this->key = bof()->object->db_setting->get("gateway_payu_key"))) return false;
    if (!($this->salt = bof()->object->db_setting->get("gateway_payu_salt"))) return false;
    $this->client_id = bof()->object->db_setting->get("gateway_payu_client_id") ?: false;
    $this->client_secret = bof()->object->db_setting->get("gateway_payu_client_secret") ?: false;
    $this->merchant_id = bof()->object->db_setting->get("gateway_payu_merchant_id") ?: false;
    $mode = bof()->object->db_setting->get("gateway_payu_mode");
    $this->test = (bof()->object->db_setting->get("gateway_payu_test") || $mode === "test");
    return true;

  }

  protected function baseUrl(){
    return $this->test ? "https://test.payu.in" : "https://secure.payu.in";
  }

  protected function requestHash($params){
    $seq = [
      $params["key"],
      $params["txnid"],
      $params["amount"],
      $params["productinfo"],
      $params["firstname"],
      $params["email"],
      $params["udf1"] ?? "",
      $params["udf2"] ?? "",
      $params["udf3"] ?? "",
      $params["udf4"] ?? "",
      $params["udf5"] ?? "",
      "", "", "", "", "",
      $this->salt
    ];
    return hash("sha512", implode("|", $seq));
  }

  protected function responseHash($params){
    $seq = [
      $this->salt,
      $params["status"] ?? "",
      "", "", "", "",
      $params["udf5"] ?? "",
      $params["udf4"] ?? "",
      $params["udf3"] ?? "",
      $params["udf2"] ?? "",
      $params["udf1"] ?? "",
      $params["email"] ?? "",
      $params["firstname"] ?? "",
      $params["productinfo"] ?? "",
      $params["amount"] ?? "",
      $params["txnid"] ?? "",
      $params["key"] ?? ""
    ];
    return hash("sha512", implode("|", $seq));
  }

  protected function payuApi($command, $var1){

    $hash = hash("sha512", implode("|", [
      $this->key,
      $command,
      $var1,
      $this->salt
    ]));

    $postUrl = $this->baseUrl() . "/merchant/postservice.php?form=2";

    $curl = bof()->curl->exe(array(
      "url" => $postUrl,
      "posts" => http_build_query(array(
        "key"     => $this->key,
        "command" => $command,
        "var1"    => $var1,
        "hash"    => $hash,
      )),
      "headers" => array(
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
      ),
      "json" => false,
      "cache" => false
    ));

    if ($curl["http_code"] != 200 || empty($curl["data"]))
      throw new Exception("payu_verify_failed");

    return $curl["data"];

  }

  public function get_link($amount, $currency, $order_no, $redirect_address, $args = []){

    if (!$this->getConfig()) return false;

    $txnid = !empty($args["payment_num"]) ? $args["payment_num"] : substr(md5(uniqid()), 0, 20);
    $amount = number_format((float)$amount, 2, ".", "");

    $user = bof()->user->check()->data;
    $email = !empty($user["email"]) ? $user["email"] : "customer@example.com";
    $firstname = !empty($user["username"]) ? $user["username"] : (!empty($user["name"]) ? $user["name"] : "Customer");

    $productinfo = "HiTune Wallet Top-up";
    if (!empty($args["plan_id"])){
      $productinfo = "HiTune Subscription";
    }

    $params = array(
      "key"         => $this->key,
      "txnid"       => $txnid,
      "amount"      => $amount,
      "productinfo" => $productinfo,
      "firstname"   => $firstname,
      "email"       => $email,
      "phone"       => !empty($user["phone"]) ? $user["phone"] : "9999999999",
      "udf1"        => !empty($args["payment_id"]) ? (string)$args["payment_id"] : "",
      "udf2"        => $order_no,
      "surl"        => $redirect_address,
      "furl"        => $redirect_address,
    );

    $params["hash"] = $this->requestHash($params);

    $action = $this->baseUrl() . "/_payment";

    $html = '<form id="payu_form" method="post" action="' . htmlspecialchars($action) . '" style="display:none;">' . "\n";
    foreach ($params as $k => $v){
      $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '" />' . "\n";
    }
    $html .= '</form>' . "\n";
    $html .= '<script type="text/javascript">document.getElementById("payu_form").submit();</script>';

    return array(
      "output" => array(
        "type" => "html",
        "content" => $html
      ),
      "txn" => $txnid
    );

  }

  public function check_payment($payment){

    if (!$this->getConfig()) return false;

    $txnid = $payment["gateway_id"];
    if (empty($txnid)) throw new Exception("payu_missing_txn");

    $post = $_POST;

    // If PayU posted the response, verify the hash and status first.
    if (!empty($post["txnid"]) && $post["txnid"] == $txnid && !empty($post["hash"])){

      $expected = $this->responseHash($post);

      if (!hash_equals(strtolower($expected), strtolower($post["hash"])))
        throw new Exception("payu_hash_mismatch");

      $status = strtolower($post["status"]);
      if ($status === "success"){
        return array(
          "amount" => (float)$post["amount"],
          "currency" => strtoupper($payment["gateway_currency"]),
          "data" => $post
        );
      }
      if ($status === "pending"){
        return "pending";
      }
      throw new Exception("payu_status_{$status}");
    }

    // Server-side reconciliation as source of truth.
    $result = $this->payuApi("verify_payment", $txnid);

    if (empty($result["transaction_details"]) || !is_array($result["transaction_details"])){
      $msg = !empty($result["msg"]) ? $result["msg"] : (!empty($result["message"]) ? $result["message"] : "empty");
      throw new Exception("payu_verify_no_data: " . $msg);
    }

    $details = $result["transaction_details"];
    $txData = !empty($details[$txnid]) ? $details[$txnid] : array_shift($details);

    if (empty($txData) || !is_array($txData))
      throw new Exception("payu_verify_not_found");

    if (empty($txData["status"]))
      throw new Exception("payu_verify_no_status");

    $status = strtolower($txData["status"]);

    if ($status === "success" || $status === "captured"){
      return array(
        "amount" => (float)($txData["amt"] ?? $txData["transaction_amount"] ?? $payment["gateway_amount"]),
        "currency" => strtoupper($payment["gateway_currency"]),
        "data" => $txData
      );
    }

    if ($status === "pending" || $status === "not found"){
      return "pending";
    }

    throw new Exception("payu_status_{$status}");

  }

}

?>
