<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_developer_apps( $loader, $excuter, $args ){

  if ( !$loader->developer_api->portal_enabled() ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $userID = $loader->user->check()->ID;
  if ( !$userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $apps = array();
  foreach( $loader->developer_api->apps_for_user( $userID ) as $app )
    $apps[] = $loader->developer_api->clean_app( $app );

  $loader->api->set_message( "ok", array(
    "apps" => $apps
  ) );

}

?>
