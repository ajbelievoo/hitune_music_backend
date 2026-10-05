<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_payment_check( $loader, $excuter, $args ){

  list( $gateway_name, $payment_num, $payment_hash ) = explode( "/", substr( $loader->request->get_requested_url(), strlen("payment_result_check/"), -1 ) );

  $sta = "failed";

  if (
    bof()->nest->validate( $gateway_name, "string" ) &&
    bof()->nest->validate( $payment_num, "string" ) &&
    bof()->nest->validate( $payment_hash, "md5" )
  ){

    $payment = bof()->object->payment->select(
      array(
        "_key" => $payment_hash,
        "_num" => $payment_num,
        "gateway_name" => $gateway_name,
        [ "time_add", ">", "SUBDATE( now(), INTERVAL 6 HOUR )", true ],
        [ "gateway_id", "NOT", null, true ]
      )
    );

    if ( $payment ){

      if ( $payment["paid"] ){
        $sta = "paid";
        $paid = true;
      }
      else {

        $sta = null;
        $check = bof()->pgt->setup()->check_payment( $gateway_name, $payment );

        if ( $check === true ){
          $paid = true;
          $sta = "paid";
        }
        elseif ( $check === "pending" ){
          $sta = "pending";
        }
        else {
          $err = $check[1];
          $sta = "failed";
        }

      }

    }

  }

  $loader->response_html->set( array(
    "bodyClass" => array( $sta ),
    "metaDatas" => array(
      "title" => array(
        "wrapper" => "title",
        "content" => bof()->object->language->turn( "payment_result", [], [ "uc_first" => true, "lang" => "users" ] )
      )
    ),
    "content" => array(
      "inline_styles" => array(
        "wrapper" => array(
          "tag" => "style"
        ),
        "content" => '
body {
    background: #fbfbfb;
    font-size: 10pt;
    font-family: "Roboto", sans-serif;
    color: #838383;
}
#pay_result {
    width: 500px;
    height: 400px;
    height: fit-content;
    max-width: 100%;
    padding: 60px;
    padding-bottom: 30px;
    margin: auto;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    box-shadow: 0 0 8px 2px #f7f7f7;
    border-radius: 10px;
    box-sizing: border-box;
    text-align: center;
}
#pay_result .icon_wrapper {
    color: green;
    font-size: 60pt;
    line-height: 1;
}
#pay_result .title {
    font-size: 260%;
    font-weight: 500;
    color: green;
    margin: 20px 0;
}
body.failed #pay_result .icon_wrapper,
body.failed #pay_result .title {
    color: maroon
}
body.pending #pay_result .icon_wrapper,
body.pending #pay_result .title {
    color: orange
}
#pay_result .transaction_number {
    margin: 0 0 40px;
    border-bottom: 2px dashed #efefef;
    padding-bottom: 40px;
    font-size: 163%;
    opacity: 0.5;
}
#pay_result .numbers_wrapper {
    display: flex;
    flex-wrap: nowrap;
    flex-direction: column;
}
#pay_result .numbers_wrapper .number_wrapper {
    display: flex;
    white-space: nowrap;
    justify-content: space-between;
    font-size: 110%;
    margin-bottom: 15px;
    font-weight: 400;
}
#pay_result .numbers_wrapper .number_wrapper:last-child {
    margin-bottom: 0;
}
#pay_result .numbers_wrapper .number_wrapper .r:after {
    content: ":";
    font-weight: 600;
    margin-left: 3px;
    opacity: 0.3;
}
#pay_result .numbers_wrapper .number_wrapper .l {
    font-weight: 500;
}
#pay_result a {
    color: inherit;
    letter-spacing: 1px;
    display: block;
    margin-top: 30px;
    opacity: 0.4;
    transition: 200ms ease all;
}
#pay_result a:hover {
    opacity: 1
}'
      ),
      "main" => array(
        "content" => $sta == "paid" ? ( '<div ID="pay_result">
          <div class="icon_wrapper"><span class="mdi mdi-check-circle-outline"></span></div>
          <div class="title">'.bof()->object->language->turn( "payment_ok", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
          <div class="transaction_number">'.bof()->object->language->turn( "transaction_number", [], [ "uc_first" => true, "lang" => "users" ] ).': #'.$payment_num.'</div>
          <div class="numbers_wrapper">
            <div class="number_wrapper">
              <div class="big r">'.bof()->object->language->turn( "amount_paid", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
              <div class="l">'.($payment["gateway_amount"]).'</div>
            </div>
            <div class="number_wrapper">
              <div class="r">'.bof()->object->language->turn( "currency", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
              <div class="l">'.($payment["gateway_currency"]).'</div>
            </div>
          </div>
          <a href="'.web_address.'user_edit?tab=transactions">'.bof()->object->language->turn( "back_to_home", [], [ "uc_first" => true, "lang" => "users" ] ).'</a>
        </div>' ) : (
          $sta == "pending" ? (
            ( '<div ID="pay_result">
              <div class="icon_wrapper"><span class="mdi mdi-alert-outline"></span></div>
              <div class="title">'.bof()->object->language->turn( "payment_pending", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
              <div class="err">'.bof()->object->language->turn( "payment_pending_det", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
              <a href="'.web_address.'user_edit?tab=transactions">'.bof()->object->language->turn( "back_to_home", [], [ "uc_first" => true, "lang" => "users" ] ).'</a>
            </div>' )
          ) : ( '<div ID="pay_result">
            <div class="icon_wrapper"><span class="mdi mdi-alert-outline"></span></div>
            <div class="title">'.bof()->object->language->turn( "payment_failed", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
            <div class="err">'.bof()->object->language->turn( "payment_failed_det", [], [ "uc_first" => true, "lang" => "users" ] ).': '.(!empty($err)?$err:"Unkown").'</div>
            <a href="'.web_address.'user_edit?tab=transactions">'.bof()->object->language->turn( "back_to_home", [], [ "uc_first" => true, "lang" => "users" ] ).'</a>
          </div>' )
        )
      ),
    ),
    "styles" => array(
      "mdi" => array(
        "address" => "https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css"
      ),
      "font" => array(
        "address" => "https://fonts.googleapis.com/css?family=Roboto:300,400,500&display=swap"
      )
    ),
  ) );

}

?>
