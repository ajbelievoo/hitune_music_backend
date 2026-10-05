<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_plans( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $plans = array();
  foreach( $loader->developer_api->plans() as $plan )
    $plans[] = $loader->developer_api->clean_plan( $plan );

  $loader->api->set_message( "ok", array(
    "enabled" => true,
    "auto_approve" => $loader->developer_api->auto_approve(),
    "plans" => $plans
  ) );

}

?>
