<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_login( $loader, $excuter, $args ){

  $errors = [];
  if ( !( $email = $loader->nest->user_input( "post", "__email__", "email" ) ) ) $errors[] = "Invalid Email";
  if ( !( $password = $loader->nest->user_input( "post", "__password__", "password" ) ) ) $errors[] = "Invalid Password";

  if (!empty($errors)) {
    $loader->api->set_error($errors);
    bof()->chapar->notify_admin("admin_login_failed", array(
      "email" => $email,
      "error" => implode( ", ", $errors )
    ));
    return;
  }

  $auth = $loader->object->user->authorize( "email", $email, $password, "admin" );

  if ( !$auth ){
    $loader->api->set_error( "Failed to authorize you" );
    bof()->chapar->notify_admin("admin_login_failed", array(
      "email" => $email,
      "error" => "Invalid email/password"
    ));
    return;
  }

  $platform = $loader->nest->user_input( "http_header", "x_bof_platform" );
  $device_type = $platform == !empty( $loader->request->get_userAgent()["data"]["device"]["type"] ) ? strtolower( $loader->request->get_userAgent()["data"]["device"]["type"] ) : null;

  $sess_data = $loader->session->create( $auth["user"]["ID"], array(
    "platform_type" => $platform,
    "device_type" => $device_type,
    "extra_data" => $auth
  ) );

  bof()->chapar->notify_admin("admin_login_ok", array(
    "email" => $email,
  ));

  $loader->api->set_message( "Welcome", array(
    "sess_id" => $sess_data["id"],
    "sess_key" => $sess_data["key"],
    "redirect" => $auth["redirect"]
  ) );

}

?>
