<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_other_app_public( $loader, $excuter, $args ){

  $id = $loader->nest->user_input( "get", "id", "int" );
  $package_id = $loader->nest->user_input( "get", "package_id", "string" );

  $where = [];
  if ( $id )
  $where = [ "ID" => $id ];
  elseif ( $package_id )
  $where = [ "package_id" => $package_id ];

  if ( !$where ){
    $loader->api->set_error( "400" );
    return;
  }

  $app = $loader->object->other_app->select( $where, [ "clean" => false ] );
  if ( !$app ){
    $loader->api->set_error( "404" );
    return;
  }

  if ( empty( $app["name"] ) || empty( $app["url"] ) || empty( $app["icon_url"] ) ){
    $loader->other_apps->update_app( $app["ID"] );
    $app = $loader->object->other_app->select( [ "ID" => $app["ID"] ], [ "clean" => false ] );
  }

  $app = $loader->object->other_app->publicize( $loader, $app, [] );

  $loader->api->set_message( "ok", array(
    "data" => $app,
    "seo" => array(
      "title" => !empty( $app["name"] ) ? $app["name"] : "Other App"
    )
  ) );

}

?>

