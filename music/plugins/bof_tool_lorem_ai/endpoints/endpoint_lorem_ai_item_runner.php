<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_lorem_ai_item_runner( $loader, $excuter, $args ){

  $object = bof()->nest->user_input( "post", "object", "in_array", [ "values" => array_keys( bof()->lorem_ai->get_supported_objects() ) ] );
  $item_id = bof()->nest->user_input( "post", "item", "int" );

  if ( !$object || !$item_id )
  return;

  $item = bof()->object->__get($object)->sid( $item_id, array(
    "clean" => false
  ) );

  if ( !$item )
  return;

  bof()->lorem_ai->run_item( $object, $item );

  $loader->api->set_message( "ok", array(
    "logs" => bof()->lorem_ai->get_logs()
  ) );

}

?>
