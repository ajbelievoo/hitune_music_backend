<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_pageBuilder_widget_delete( $loader, $excuter, $args ){

  $wid_id = $loader->nest->user_input( "post", "wid_id", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9]", "min" => 10, "max" => 10 ] );
  if ( !$wid_id ){
    $loader->api->set_error( "Invalid Request", [] );
    return;
  }

  $wid_data = $loader->object->page_widget->select(["unique_id"=>$wid_id]);
  if ( !$wid_data ){
    $loader->api->set_error( "Invalid Request", [] );
    return;
  }

  $loader->object->page_widget->delete( array(
    "ID" => $wid_data["ID"]
  ) );

  $loader->api->set_message( "Deleted", [] );

}

?>
