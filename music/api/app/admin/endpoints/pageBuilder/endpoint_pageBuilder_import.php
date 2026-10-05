<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_pageBuilder_import( $loader, $excuter, $args ){

  $json = $loader->nest->user_input( "post", "json", "json" );

  if ( $json ){
    try {
      $import = $loader->object->page->import( $json );
      $loader->api->set_message( "done" );
    }
    catch ( bofException $err ){
      $loader->api->set_error( $err->getMessage() );
    }
  }

}

?>
