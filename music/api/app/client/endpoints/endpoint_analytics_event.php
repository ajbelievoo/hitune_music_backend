<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_analytics_event( $loader, $excuter, $args ){

  $event = $loader->nest->user_input( "post", "event", "string", [ "strict" => true ] );
  if ( !$event ){
    $loader->api->set_error( "invalid_event", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $allowed = [ "play", "pause", "skip", "like", "unlike", "download", "share", "playlist_add", "search", "login", "register", "subscribe", "purchase", "ad_impression", "ad_click" ];
  if ( !in_array( $event, $allowed, true ) ){
    $loader->api->set_error( "invalid_event", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $object_type = $loader->nest->user_input( "post", "object_type", "string", [ "empty()", "strict" => true ] );
  $object_id = $loader->nest->user_input( "post", "object_id", "int", [ "empty()" ] );
  $metadata_raw = $loader->nest->user_input( "post", "metadata", "string", [ "empty()" ] );
  $metadata = $metadata_raw ? json_encode( json_decode( $metadata_raw, true ) ) : null;

  $user_id = bof()->user->get()->ID;
  $session_id = session_id();
  $ip = bof()->request->get_userIP()["string"];

  bof()->db->query(
    "INSERT INTO _u_analytics_events (user_id,session_id,event,object_type,object_id,metadata,ip) VALUES (" .
    ( $user_id ? (int) $user_id : "NULL" ) . "," .
    "'" . bof()->db->real_escape_string( $session_id ) . "'," .
    "'" . bof()->db->real_escape_string( $event ) . "'," .
    ( $object_type ? "'" . bof()->db->real_escape_string( $object_type ) . "'" : "NULL" ) . "," .
    ( $object_id ? (int) $object_id : "NULL" ) . "," .
    ( $metadata ? "'" . bof()->db->real_escape_string( $metadata ) . "'" : "NULL" ) . "," .
    "'" . bof()->db->real_escape_string( $ip ) . "')",
    null,
    true
  );

  $loader->api->set_message( "ok" );

}
