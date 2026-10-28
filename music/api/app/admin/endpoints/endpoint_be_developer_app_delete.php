<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_app_delete — permanently remove an app + its tokens (admin).
 * POST: hash
 */

function endpoint_be_developer_app_delete( $loader, $excuter, $args ){

  $hash = $loader->nest->user_input( "post", "hash", "md5" );
  $app = $hash ? $loader->developer_api->app( $hash ) : null;
  if ( !$app ){
    $loader->api->set_error( "not_found" );
    return;
  }

  $loader->developer_api->delete_app( $app );

  $loader->api->set_message( "ok", array( "deleted" => true ) );

}

?>
