<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_push_register( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $platform = $loader->nest->user_input( "post", "platform", "in_array", [ "values" => [ "android", "ios", "web" ] ] );
  if ( !$platform )
  $platform = bof()->nest->user_input( "http_header", "x_bof_platform" );

  $fcm_token = $loader->nest->user_input( "post", "fcm_token", "string" );
  $push_subscription = $loader->nest->user_input( "post", "push_subscription", "json" );

  // Web push subscription passthrough -> existing handler
  if ( $platform == "web" && $push_subscription ){
    require_once( dirname(__FILE__) . "/endpoint_user_push_register.php" );
    endpoint_user_push_register( $loader, $excuter, $args );
    return;
  }

  $token = $fcm_token ? $fcm_token : ( is_string( $push_subscription ) ? $push_subscription : null );
  if ( !$token || !in_array( $platform, [ "android", "ios" ], true ) )
  return;

  // iOS may carry a raw APNs device token (64-hex) or an FCM token (Flutter default)
  $is_apns = $platform == "ios" && preg_match( "/^[A-Fa-f0-9]{64}$/", $token );

  if ( !$is_apns ){
    $valid = $loader->nest->validate( $token, "string", array(
      "strict" => true,
      "strict_regex" => "[A-Za-z0-9_\-\:]",
      "min_length" => 64,
      "only_utf8" => false
    ) );
    if ( !$valid ) return;
  }

  $data_hash = md5( $platform . ":" . $token );

  if ( !$loader->db->_select(array(
    "table" => "_u_push_subs",
    "where" => array(
      [ "user_id", "=", $userID ],
      [ "data_hash", "=", $data_hash ]
    )
  )) ){

    $loader->db->_insert(array(
      "table" => "_u_push_subs",
      "set" => array(
        [ "user_id", $userID ],
        [ "platform", $platform ],
        [ "data", json_encode( $token ) ],
        [ "data_hash", $data_hash ],
      )
    ));

  }

  $loader->api->set_message( "registered" );

}

?>
