<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_storage_test( $loader, $excuter, $args ){

  if ( bof()->user->check()->ID != 1 ){
    $loader->api->set_error("Only root-admin can do this");
    return;
  }

  $id = bof()->nest->user_input( "post", "ID", "int" );

  if ( !$id )
  return;

  $storage = bof()->object->storage->sid( $id );

  if ( !$storage )
  return;

  $copyForTest = "files/tmp/" . uniqid() . ".png";
  copy( bof_root . "/app/core/third/sample_for_storage_test.png", base_root . "/" . $copyForTest );

  try {
    $move = bof()->transit
    ->set_debug( true )
    ->set_storage( $storage )
    ->set_file( $copyForTest )
    ->move(array(
      "dirname" => "main_dir",
      "subdir" => "sub_dir",
      "filename" => "testing",
      "extension" => "png"
    ));
  } catch( bofException|Exception|Error|S3Exception $err ){
    $loader->api->set_error("Error: " . $err->getMessage() );
    return;
  }

  if ( !$move[0] ){
    $loader->api->set_error("Error: " .  $move[1] );
    return;
  }

  $url = bof()->transit
  ->set_storage( $storage )
  ->set_file( $move[1] )
  ->url();

  $loader->api->set_message( $url );

}

?>
