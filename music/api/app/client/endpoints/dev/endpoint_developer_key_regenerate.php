<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_key_regenerate( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $hash  = $loader->nest->user_input( "post", "hash", "md5" );
  if ( !$hash ) $hash = $loader->nest->user_input( "post", "app_hash", "md5" );
  $which = $loader->nest->user_input( "post", "which", "in_array", [ "values" => [ "secret", "publishable" ] ] );

  if ( !$hash || !$which ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $app = $loader->developer_api->app( $hash, $userID );
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $result = $loader->developer_api->regenerate_key( $app, $which );
  if ( !$result ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $loader->api->set_message( "ok", array_merge(
    $result,
    array( "app" => $loader->developer_api->clean_app( $loader->developer_api->app( $hash, $userID ) ) )
  ) );

}

?>
