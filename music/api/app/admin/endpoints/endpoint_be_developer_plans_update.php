<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_plans_update — edit an API plan tier (admin).
 * POST: plan (key/hash/ID) + any of: name, price, currency,
 *       monthly_requests, rate_limit, max_apps, allow_stream,
 *       stream_quality, monthly_streams, commercial_use,
 *       embed_whitelabel, contact_sales, active, sort
 *       (pass empty string for NULL-able numeric fields to clear them)
 */

function endpoint_be_developer_plans_update( $loader, $excuter, $args ){

  $plan_in = $loader->nest->user_input( "post", "plan", "string" );
  $plan = $plan_in ? $loader->developer_api->plan( $plan_in ) : null;
  if ( !$plan ){
    $loader->api->set_error( "plan_not_found" );
    return;
  }

  $set = array();

  $_string = array( "name", "currency", "stream_quality" );
  $_ints   = array( "monthly_requests", "rate_limit", "max_apps", "monthly_streams", "sort" );
  $_bool   = array( "allow_stream", "commercial_use", "embed_whitelabel", "contact_sales", "active" );

  foreach( $_string as $_f ){
    $v = $loader->nest->user_input( "post", $_f, "string" );
    if ( $v !== null ) $set[] = array( $_f, $v === "" ? null : $v );
  }

  foreach( $_ints as $_f ){
    if ( !isset( $_POST[ $_f ] ) ) continue;
    if ( $_POST[ $_f ] === "" ){
      $set[] = array( $_f, null );
      continue;
    }
    $v = $loader->nest->user_input( "post", $_f, "int", [ "min" => 0 ] );
    $set[] = array( $_f, $v === null ? null : $v );
  }

  foreach( $_bool as $_f ){
    if ( !isset( $_POST[ $_f ] ) ) continue;
    $set[] = array( $_f, $loader->nest->user_input( "post", $_f, "boolean" ) ? 1 : 0 );
  }

  if ( isset( $_POST["price"] ) ){
    $v = $loader->nest->user_input( "post", "price", "float", [ "min" => 0 ] );
    $set[] = array( "price", $_POST["price"] === "" ? null : $v );
  }

  if ( isset( $_POST["features"] ) ){
    $f = json_decode( $_POST["features"], true );
    $set[] = array( "features", is_array( $f ) ? json_encode( $f ) : null );
  }

  if ( !$set ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $loader->db->_update( array(
    "table" => "_dev_plans",
    "set" => $set,
    "where" => array( array( "ID", "=", (int)$plan["ID"] ) )
  ) );

  $loader->api->set_message( "ok", array(
    "plan" => $loader->developer_api->clean_plan( $loader->developer_api->plan( (int)$plan["ID"] ) )
  ) );

}

?>
