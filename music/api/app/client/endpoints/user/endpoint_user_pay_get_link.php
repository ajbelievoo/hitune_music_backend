<?php

if (!defined("bof_root")) die;

function endpoint_user_pay_get_link($loader, $excuter, $args){

  $gateway = $loader->nest->user_input("post", "gateway", "string");
  $amount = $loader->nest->user_input("post", "amount", "float");
  $purchase_data = $loader->nest->user_input("post", "purchase_data", "json");
  if ($gateway && $amount) {

    $get_link = $loader->pgt->setup()->get_link($gateway, $amount, $purchase_data);
    if ($get_link[0]) {
      $loader->api->set_message("ok", $get_link[1]);
    } 
    else {
      $loader->api->set_error($get_link[1], ["output_args" => ["turn" => false]]);
    }

  }

}
