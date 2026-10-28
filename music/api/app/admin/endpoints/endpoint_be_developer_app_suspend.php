<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_app_suspend — suspend an app (admin). Suspension kills its
 * keys instantly: all bearer tokens are revoked too.
 * POST: hash
 */

function endpoint_be_developer_app_suspend( $loader, $excuter, $args ){

  $hash = $loader->nest->user_input( "post", "hash", "md5" );
  $app = $hash ? $loader->developer_api->app( $hash ) : null;
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $loader->db->_update( array(
    "table" => "_dev_apps",
    "set" => array( array( "status", "suspended" ) ),
    "where" => array( array( "ID", "=", (int)$app["ID"] ) )
  ) );

  // revoke live tokens
  $loader->db->_delete( array(
    "table" => "_dev_tokens",
    "where" => array( array( "app_id", "=", (int)$app["ID"] ) )
  ) );

  $loader->api->set_message( "ok", array( "status" => "suspended" ) );

}

?>
