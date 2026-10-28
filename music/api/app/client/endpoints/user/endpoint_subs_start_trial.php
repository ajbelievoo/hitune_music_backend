<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_subs_start_trial( $loader, $excuter, $args ){

  // Spotify-style free trial: grant data.trial_days of a plan to a user
  // that has never held that plan before. No payment involved.

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $plan_hash = $loader->nest->user_input( "post", "hash", "md5" );
  if ( !$plan_hash )
  $plan_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( !$plan_hash ){
    $loader->api->set_error( "invalid_request" );
    return;
  }

  $plan = $loader->object->user_subs_plan->select( array(
    "hash" => $plan_hash,
    "active" => 1,
    "free" => 0
  ), array(
    "single" => true
  ) );

  if ( !$plan ){
    $loader->api->set_error( "plan_not_found" );
    return;
  }

  $plan_data = !empty( $plan["data"] ) ? json_decode( $plan["data"], true ) : null;
  $trial_days = is_array( $plan_data ) && !empty( $plan_data["trial_days"] ) ? intval( $plan_data["trial_days"] ) : 0;

  if ( $trial_days <= 0 ){
    $loader->api->set_error( "no_trial" );
    return;
  }

  // Trial can be claimed only once per plan per user
  $had_sub = $loader->db->_select( array(
    "table" => "_u_subs",
    "where" => array(
      [ "user_id", "=", $userID ],
      [ "subs_plan_id", "=", $plan["ID"] ]
    ),
    "limit" => 1,
    "single" => true
  ) );

  if ( $had_sub ){
    $loader->api->set_error( "trial_used" );
    return;
  }

  $expire = date( "Y-m-d H:i:s", strtotime( "+{$trial_days} days" ) );

  $loader->db->_insert( array(
    "table" => "_u_subs",
    "set" => array(
      [ "user_id", $userID ],
      [ "subs_plan_id", $plan["ID"] ],
      [ "subs_plan_time_range", "trial" ],
      [ "subs_plan_price", 0 ],
      [ "gateway_name", "trial" ],
      [ "time_expire", $expire ]
    )
  ) );

  bof()->chapar->notify_admin( "subs_trial_started", array(
    "plan" => $plan["name"],
    "user_id" => $userID,
    "days" => $trial_days
  ) );

  $loader->api->set_message( "ok", array(
    "status" => "trial_started",
    "plan" => !empty( $plan["plan_key"] ) ? $plan["plan_key"] : strtolower( preg_replace( "/[^a-zA-Z0-9]+/", "_", trim( $plan["name"] ) ) ),
    "plan_id" => $plan["ID"],
    "trial_days" => $trial_days,
    "expires" => strtotime( $expire )
  ) );

}

?>
