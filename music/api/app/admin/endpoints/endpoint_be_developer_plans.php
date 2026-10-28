<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_plans — list all API plan tiers incl. inactive (moderator+).
 */

function endpoint_be_developer_plans( $loader, $excuter, $args ){

  $plans = array();
  foreach( $loader->developer_api->plans( false ) as $_p )
    $plans[] = array_merge( $loader->developer_api->clean_plan( $_p ), array(
      "id" => (int)$_p["ID"],
      "active" => $_p["active"] ? true : false,
      "sort" => (int)$_p["sort"]
    ) );

  $loader->api->set_message( "ok", array( "plans" => $plans ) );

}

?>
