<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_push_register( $loader, $excuter, $args ){

  $platform = bof()->nest->user_input( "http_header", "x_bof_platform" );
  $push_subscription = $loader->nest->user_input( "post", "push_subscription", "json" );
 
  
  if ( !$push_subscription )
  return;

  if ( $platform == "web" ?
    !empty( $push_subscription["endpoint"] ) &&
    !empty( $push_subscription["keys"]["p256dh"] ) &&
    !empty( $push_subscription["keys"]["auth"] )
  : false ){

    $endpoint = $loader->nest->validate( $push_subscription["endpoint"], "url" );
    $pub_key  = $loader->nest->validate( $push_subscription["keys"]["p256dh"], "string", [ "stirct" => true, "min" => 88, "max" => 88 ] );
    $auth_key = $loader->nest->validate( $push_subscription["keys"]["auth"], "string", [ "stirct" => true, "min" => 24, "max" => 24 ] );

    if ( $endpoint && $pub_key && $auth_key ){

      if ( !$loader->db->_select(array(
        "table" => "_u_push_subs",
        "where" => array(
          [ "user_id", "=", $loader->user->check()->ID ],
          [ "data_hash", "=", md5( json_encode( $push_subscription ) ) ]
        )
      )) ){

        $loader->db->_insert(array(
          "table" => "_u_push_subs",
          "set" => array(
            [ "user_id", $loader->user->check()->ID ],
            [ "data", json_encode( $push_subscription ) ],
            [ "data_hash", md5( json_encode( $push_subscription ) ) ],
          )
        ));

        bof()->chapar->notify_admin("push_not_ok", array(
          "type" => "vapid",
        ));

      }

      $loader->api->set_message( "registered" );

    }

  }
  elseif ( $platform == "ios" ){

    // APN device token validation (64-character hexadecimal)
    $validateRegID = $loader->nest->validate( $push_subscription, "string", array(
      "strict" => true,
      "strict_regex" => "[A-Fa-f0-9]",
      "min_length" => 64,
      "max_length" => 64,
      "only_utf8" => false
    ) );

    if ( !$validateRegID )
    return;

    if ( !$loader->db->_select(array(
      "table" => "_u_push_subs",
      "where" => array(
        [ "user_id", "=", $loader->user->check()->ID ],
        [ "data_hash", "=", md5( json_encode( $push_subscription ) ) ]
      )
    )) ){

      $loader->db->_insert(array(
        "table" => "_u_push_subs",
        "set" => array(
          [ "user_id", $loader->user->check()->ID ],
          [ "platform", $platform ],
          [ "data", json_encode( $push_subscription ) ],
          [ "data_hash", md5( json_encode( $push_subscription ) ) ],
        )
      ));

      bof()->chapar->notify_admin("push_not_ok", array(
          "type" => "apn",
      ));

    }

  }
  elseif ( $platform == "android" ){

    $validateRegID = $loader->nest->validate( $push_subscription, "string", array(
      "strict" => true,
      "strict_regex" => "[A-Za-z0-9_\-\:]",
      "min_length" => 140,
      "only_utf8" => false
    ) );

    if ( !$validateRegID )
    return;

    if ( !$loader->db->_select(array(
      "table" => "_u_push_subs",
      "where" => array(
        [ "user_id", "=", $loader->user->check()->ID ],
        [ "data_hash", "=", md5( json_encode( $push_subscription ) ) ]
      )
    )) ){

      $loader->db->_insert(array(
        "table" => "_u_push_subs",
        "set" => array(
          [ "user_id", $loader->user->check()->ID ],
          [ "platform", $platform ],
          [ "data", json_encode( $push_subscription ) ],
          [ "data_hash", md5( json_encode( $push_subscription ) ) ],
        )
      ));

      bof()->chapar->notify_admin("push_not_ok", array(
          "type" => "fcm",
      ));

    }

    $loader->api->set_message( "registered" );

  }

}

?>
