<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_plan_set — override/comp an app's API plan (admin).
 * POST: hash, plan (key/hash/ID), expire_days (optional, 0 = never)
 */

function endpoint_be_developer_plan_set( $loader, $excuter, $args ){

  $hash = $loader->nest->user_input( "post", "hash", "md5" );
  $plan_in = $loader->nest->user_input( "post", "plan", "string" );
  $expire_days = $loader->nest->user_input( "post", "expire_days", "int", [ "min" => 0 ], 0 );

  $app = $hash ? $loader->developer_api->app( $hash ) : null;
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $plan = $plan_in ? $loader->developer_api->plan( $plan_in ) : null;
  if ( !$plan ){
    $loader->api->set_error( "plan_not_found" );
    return;
  }

  $set = array( array( "plan_id", (int)$plan["ID"] ) );
  if ( (float)$plan["price"] > 0 )
    $set[] = array( "plan_expire", bof()->general->mysql_timestamp( time() + ( $expire_days ? $expire_days : 365 ) * 86400 ) );
  else
    $set[] = array( "plan_expire", null );

  $loader->db->_update( array(
    "table" => "_dev_apps",
    "set" => $set,
    "where" => array( array( "ID", "=", (int)$app["ID"] ) )
  ) );

  $loader->api->set_message( "ok", array(
    "app" => $loader->developer_api->clean_app( $loader->developer_api->app( $hash ) )
  ) );

}

?>
