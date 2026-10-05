<?php

if ( !defined( "bof_root" ) ) die;

class pgt_offline extends bof_type_class {

  public function setup(){

    bof()->listen( "pgt", "setup", function( $method_args, &$gateways, $loader ){
      bof()->pgt->gateway_add( "offline", array(
        "db_name" => "offline",
        "code_name" => "bc",
        "hook" => "offline_transfer",
        "icon_t" => "icon",
        "icon_v" => "credit-card-outline",
        "supported_currencies" => [ "all" ]
      ) );
    } );

  }

  public function get_link( $amount, $currency, $order_no, $redirect_address ){

    $bank_data = bof()->object->db_setting->get( "gateway_offline_detail" );
    return array(
      "output" => array(
        "type" => "html",
        "content" => str_replace( [ "%amount%", PHP_EOL ], [ $amount, "<br>" ], $bank_data )
      )
    );

  }

}

?>
