<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/oauth/userinfo
 * Bearer JWT -> OpenID-style user profile. Raw JSON (no BOF wrapper).
 */
function endpoint_iyol_oauth_userinfo( $loader, $excuter, $args ){

  $send = function( $data, $http=200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
      header( "Cache-Control: no-store" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES );
    exit;
  };

  $iyol = bof()->iyolme;
  if ( !$iyol->enabled() )
    $send( array( "error" => "temporarily_unavailable" ), 503 );

  $payload = $iyol->verify_bearer();
  if ( !$payload )
    $send( array( "error" => "invalid_token", "error_description" => "Missing, invalid or expired bearer token" ), 401 );

  if ( empty( $payload["uid"] ) )
    $send( array( "error" => "insufficient_scope", "error_description" => "Token is not bound to a user" ), 403 );

  $user = $iyol->user_payload( (int)$payload["uid"] );
  if ( !$user )
    $send( array( "error" => "invalid_token", "error_description" => "User no longer exists" ), 401 );

  $send( $user );

}

?>
