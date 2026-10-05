<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_theme_activate( $loader, $excuter, $args ){

  $get_themes = $loader->theme->get_all();
  $requested_theme_id = $loader->nest->user_input( "post", "ID", "in_array", [ "values" => array_keys( $get_themes ) ] );

  if ( $requested_theme_id ){
    $loader->theme->set( $requested_theme_id );
  }

  $loader->api->set_message( "ok" );

}

?>
