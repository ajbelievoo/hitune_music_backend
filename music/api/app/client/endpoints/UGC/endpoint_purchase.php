<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_purchase( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "purchase" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( $object_name && $object_hash ){

    $object_item = $loader->object->__get( $object_name )->select( array(
      "hash" => $object_hash
    ), [ "purchase" => true, "purchase_check" => true, "endpoint" => "purchase", "_eq" => [ "cover" => [] ] ] );

    if ( $object_item ){

      try {
        $loader->object->ugc_property->purchase( $object_name, $object_item );
      } catch( Exception $err ){
        $loader->api->set_error( $err->getMessage(), [ "more" => bof()->object->language->turn( $err->getMessage() . "_tip" ) , "output_args" => [ "uc_first" => true, "lang" => "users" ] ] );
        return;
      }

      $loader->api->set_message( "success", [ "more" => bof()->object->language->turn( "purchase_ok_tip" ) , "output_args" => [ "uc_first" => true, "lang" => "users" ] ] );
      return;

    }

  }

  $loader->api->set_error( "failed" );

}

?>
