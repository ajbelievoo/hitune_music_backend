<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_usage( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $hash   = $loader->nest->user_input( "post", "hash", "md5" );
  if ( !$hash ) $hash = $loader->nest->user_input( "post", "app_hash", "md5" );
  $period = $loader->nest->user_input( "post", "period", "int", [ "min" => 1, "max" => 365 ], 30 );

  if ( !$hash ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $app = $loader->developer_api->app( $hash, $userID );
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $plan = $app["plan_id"] ? $loader->developer_api->plan( (int)$app["plan_id"] ) : $loader->developer_api->plan( "sandbox" );
  $quota = $loader->developer_api->quota( $app, $plan );

  $loader->api->set_message( "ok", array(
    "daily" => $loader->developer_api->usage_daily( (int)$app["ID"], $period ),
    "quota" => array(
      "plan" => $plan["plan_key"],
      "requests_used" => $quota["requests_used"],
      "requests_cap" => $quota["requests_cap"],
      "streams_used" => $quota["streams_used"],
      "streams_cap" => $quota["streams_cap"],
      "rate_limit" => (int)$plan["rate_limit"]
    )
  ) );

}

?>
