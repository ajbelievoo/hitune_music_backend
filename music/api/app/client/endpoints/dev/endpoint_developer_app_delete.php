<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_app_delete( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $hash = $loader->nest->user_input( "post", "hash", "md5" );
  if ( !$hash ) $hash = $loader->nest->user_input( "post", "app_hash", "md5" );

  if ( !$hash ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $app = $loader->developer_api->app( $hash, $userID );
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $loader->developer_api->delete_app( $app );

  $loader->api->set_message( "ok", array(
    "deleted" => true
  ) );

}

?>
