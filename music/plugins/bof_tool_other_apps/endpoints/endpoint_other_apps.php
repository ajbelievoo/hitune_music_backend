<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_other_apps( $loader, $excuter, $args ){

  $items = [];
  try {
    $apps = bof()->object->other_app->select( [], [
      "empty_select" => true,
      "limit" => 50,
      "order_by" => "ID",
      "order" => "DESC",
    ] );

    if ( $apps ){
      foreach( $apps as $app ){

        $app = bof()->object->other_app->publicize( $loader, $app, [] );

        $items[] = array(
          "ID" => $app["ID"],
          "package_id" => !empty( $app["package_id"] ) ? $app["package_id"] : "",
          "name" => !empty( $app["name"] ) ? $app["name"] : "",
          "icon" => !empty( $app["icon"] ) ? $app["icon"] : "",
          "downloads" => !empty( $app["downloads"] ) ? $app["downloads"] : "0",
          "rating" => isset( $app["rating"] ) ? $app["rating"] : "0.0",
          "url" => !empty( $app["url"] ) ? $app["url"] : "#"
        );

      }
    }
  } catch ( Exception $e ){}

  $loader->api->set_message( "ok", array(
    "items" => $items,
    "seo" => array(
      "title" => "Other Apps"
    )
  ) );

}
