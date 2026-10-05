<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_sitemap_generator_execute( $loader, $excuter, $args ){

  if ( bof()->user->check()->ID != 1 ){
    $loader->api->set_error("Only root-admin can do this");
    return;
  }

  $state = bof()->object->db_setting->get( "_smg_state" );
  if ( $state ? $state > 0 : false ){
    $loader->api->set_error("Already in-progress or queued");
    return;
  }

  bof()->object->db_setting->set( "_smg_state", 1 );
  $loader->api->set_message("ok");

}

?>
