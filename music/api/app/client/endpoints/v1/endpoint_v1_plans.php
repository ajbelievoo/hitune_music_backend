<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/plans
 * Public developer-plan catalog. Auth: publishable key or Bearer token.
 */

function endpoint_v1_plans( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  $plans = array();
  foreach( $loader->developer_api->plans() as $plan )
    $plans[] = $loader->developer_api->clean_plan( $plan );

  $loader->developer_api->v1_send( array( "plans" => $plans ) );

}

?>
