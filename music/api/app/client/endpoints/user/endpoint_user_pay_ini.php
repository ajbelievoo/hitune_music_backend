<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_pay_ini( $loader, $excuter, $args ){

  $gateways = $loader->pgt->setup()->list_gateways();

  $loader->api->set_message( "ok", array(
    "gateways" => $gateways,
    "currency" => bof()->object->currency->get_default(["public"=>true]),
    "seo" => array(
      "title" => bof()->object->language->turn( "add_funds", [], [ "uc_first" => true, "lang" => "users" ] )
    )
  ) );

}

?>
