<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_app_update( $loader, $excuter, $args ){

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

  $fields = array();
  $name    = $loader->nest->user_input( "post", "name", "string", [ "strip_emoji" => false ] );
  $website = $loader->nest->user_input( "post", "website", "string" );
  $origins = $loader->nest->user_input( "post", "origins", "string" );
  $bundles = $loader->nest->user_input( "post", "bundles", "string" );

  if ( $name !== null )    $fields["name"] = $name;
  if ( $website !== null ) $fields["website"] = $website;
  if ( $origins !== null ) $fields["origins"] = array_values( array_filter( array_map( "trim", explode( ",", $origins ) ) ) );
  if ( $bundles !== null ) $fields["bundles"] = array_values( array_filter( array_map( "trim", explode( ",", $bundles ) ) ) );

  if ( $fields )
  $loader->developer_api->update_app( $app, $fields );

  $loader->api->set_message( "ok", array(
    "app" => $loader->developer_api->clean_app( $loader->developer_api->app( $hash, $userID ) )
  ) );

}

?>
