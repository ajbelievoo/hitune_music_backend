<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_login_social_get( $loader, $excuter, $args ){

  $supported_social_login = $loader->object->core_setting->get( "supported_social_logins" );
  $sls = [];

  if ( $loader->object->db_setting->get( "sl" ) ){

    foreach( $supported_social_login as $_id => $_data ){

      if ( $loader->object->db_setting->get( "sl_{$_data["slang"]}" ) ){
        $sls[] = array(
          "id" => $_id,
          "title" => $_data["_title"],
          "icon" => $_data["_icon"]
        );
      }

    }

  }

  $loader->api->set_message( "ok", array(
    "sls" => $sls,
    "google_off" => bof()->object->db_setting->get( "sl_gg_off", false )
  ) );

}

?>
