<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_email_test( $loader, $excuter, $args ){

  if ( bof()->user->check()->ID != 1 ){
    $loader->api->set_error("Only root-admin can do this");
    return;
  }

  $receiver = bof()->nest->user_input( "post", "receiver", "email" );
  $text = bof()->nest->user_input( "post", "text", "string" );

  if ( !$receiver || !$text ){
    $loader->api->set_error("Enter valid text/receiver");
    return;
  }

  $loader->chapar->set_debug( true );

  try {
    $send = $loader->chapar->exe( "test_email", array(
      "source" => array(
        "object" => null,
        "id" => null,
      ),
      "target" => array(
        "email" => $receiver,
        "user_id" => null,
      ),
      "message" => array(
        "type" => "test",
        "texts" => array(
          "title" => "Test email",
          "email_title" => "Test email",
          "email_content" => $text
        )
      ),
      "methods" => array(
        "email" => true
      )
    ) );
  } catch( Exception $err ){
    $loader->api->set_error("Failed: " .$err->getMessage());
    return;
  }

  $loader->api->set_message("ok");

}

?>
