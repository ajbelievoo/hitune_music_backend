<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_menuBuilder_save_structure( $loader, $excuter, $args ){

  $_structure = $loader->nest->user_input( "post", "structure", "json" );
  $_page_id = $loader->nest->user_input( "post", "page_id", "int" );


  if ( $_structure && $_page_id ){

    try {
      $_structure = $loader->object->menu->verify_structure( $_structure );
    } catch( bofException $exception ){
      $loader->api->set_error( $exception->getMessage() );
      return;
    }

    $loader->object->menu->update(
      array(
        "ID" => $_page_id
      ),
      array(
        "structure" => $_structure
      )
    );
    
  }

  $loader->api->set_message( "Links saved", [] );

}

?>
