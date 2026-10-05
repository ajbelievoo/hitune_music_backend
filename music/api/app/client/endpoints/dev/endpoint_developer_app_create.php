<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_app_create( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $name     = $loader->nest->user_input( "post", "name", "string", [ "strip_emoji" => false ] );
  $website  = $loader->nest->user_input( "post", "website", "string" );
  $platform = $loader->nest->user_input( "post", "platform", "in_array", [ "values" => [ "web", "android", "ios", "server" ] ] );
  $origins  = $loader->nest->user_input( "post", "origins", "string" );   // comma-separated domains
  $bundles  = $loader->nest->user_input( "post", "bundles", "string" );   // comma-separated bundle ids

  $result = $loader->developer_api->create_app( $userID, array(
    "name" => $name,
    "website" => $website,
    "platform" => $platform,
    "origins" => $origins ? array_values( array_filter( array_map( "trim", explode( ",", $origins ) ) ) ) : array(),
    "bundles" => $bundles ? array_values( array_filter( array_map( "trim", explode( ",", $bundles ) ) ) ) : array()
  ) );

  if ( !empty( $result["error"] ) ){
    $loader->api->set_error( $result["error"], array(
      "output_args" => [ "turn" => false ],
      "limit" => !empty( $result["limit"] ) ? $result["limit"] : null,
      "plan" => !empty( $result["plan"] ) ? $result["plan"] : null
    ) );
    return;
  }

  $loader->api->set_message( "ok", array(
    "app" => $result["app"],
    "client_secret" => $result["app"]["client_secret"]
  ) );

}

?>
