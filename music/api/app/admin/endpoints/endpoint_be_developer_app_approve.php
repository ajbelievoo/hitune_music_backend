<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_app_approve — set app status to active (admin).
 * POST: hash
 */

function endpoint_be_developer_app_approve( $loader, $excuter, $args ){

  $hash = $loader->nest->user_input( "post", "hash", "md5" );
  $app = $hash ? $loader->developer_api->app( $hash ) : null;
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $loader->db->_update( array(
    "table" => "_dev_apps",
    "set" => array( array( "status", "active" ) ),
    "where" => array( array( "ID", "=", (int)$app["ID"] ) )
  ) );

  $loader->api->set_message( "ok", array( "status" => "active" ) );

}

?>
