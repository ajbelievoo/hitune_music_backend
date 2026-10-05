<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_openai_test( $loader, $excuter, $args ){

  if ( bof()->user->check()->ID != 1 ){
    $loader->api->set_error("Only root-admin can do this");
    return;
  }

  $key = bof()->nest->user_input( "post", "key", "string" );
  if ( !$key ) return;

  try {
    $models = bof()->ai->__reset()
    ->set_key( "openai_key", $key )
    ->openai
    ->models();
  }
  catch( bofException|Exception $err ){
    $loader->api->set_message( array(
      "FAILED",
      $err->getMessage()
    ) );
    return;
  }

  $mNames = [];
  foreach( $models["data"] as $model )
  $mNames[] = $model["id"];

  $test[] = "Available models: " . implode( "; ", $mNames );
  $test[] = "Saying 'Hi!' to gpt3";

  try {
    $sayHello = bof()->ai->__reset()
    ->set_key( "openai_key", $key )
    ->set_setting( "text.openai_model", "_gpt_3" )
    ->openai
    ->generate_text_from_text( "Hi!" );
  }
  catch( bofException|Exception $err ){
    $loader->api->set_message( array(
      "Available models: " . implode( " ---- ", $mNames ).
      "FAILED",
      $err->getMessage()
    ) );
    return;
  }

  $test[] = "Response: <b>{$sayHello}</b>";

  $loader->api->set_message( $test );

}

?>
