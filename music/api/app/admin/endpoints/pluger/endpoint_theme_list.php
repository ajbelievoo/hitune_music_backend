<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_theme_list( $loader, $excuter, $args ){

  $get_themes = $loader->theme->get_all();

  $loader->api->set_message( "ok", [ "list" => $get_themes ] );

}

?>
