<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_menuBuilder_get_link( $loader, $excuter, $args ){

  $type = $loader->nest->user_input( "post", "type", "string" );
  $ID = $loader->nest->user_input( "post", "ID", "int" );

  if ( $ID && $type ? in_array( $type, array_keys( $loader->bofAdmin->_get_objects() ), true ) : false ){

    $object = $loader->object->__get( $type );
    $check_id = $object->select(
      array(
        "ID" => $ID
      ),
      array(
        "columns" => "seo_url",
        "clean" => false
      )
    );
    
    if ( $check_id ){
      $link = str_replace( "\/", "/", $object->bof_client()["single_url_prefix"] ) . "/" . $check_id["seo_url"];
      if ( $type == "page" ) $link = substr( $link, 1 );
      $loader->api->set_message( "Found link", [ "link" => $link ] );
      return;
    }

  }

  $loader->api->set_error( "Found nothing", [] );

}

?>
