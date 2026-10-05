<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_delete( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $password = $loader->nest->user_input( "post", "password", "password" );
  if ( !$password ){
    $loader->api->set_error( "password_required" );
    return;
  }

  $userData = $loader->object->user->select(
    array(
      "ID" => $userID
    ),
    array(
      "cache_load_rt" => false,
      "clean" => false
    )
  );

  if ( !$userData ) return;

  $verify = $loader->object->user->verify_password( $password, $userData["password"] );
  if ( !$verify ){
    $loader->api->set_error( "wrong_old_password" );
    return;
  }

  $loader->object->user->delete( array( "ID" => $userID ) );

  bof()->chapar->notify_admin( "user_delete_ok", array() );

  $loader->api->set_message( "deleted" );

}

?>
