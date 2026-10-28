<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/v1/oauth/revoke
 * RFC 7009 token revocation (access JWTs and refresh tokens).
 */
function endpoint_iyol_oauth_revoke( $loader, $excuter, $args ){

  $send = function( $data, $http=200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES );
    exit;
  };

  if ( $_SERVER["REQUEST_METHOD"] !== "POST" )
    $send( array( "error" => "invalid_request" ), 405 );

  $iyol = bof()->iyolme;
  if ( !$iyol->enabled() )
    $send( array( "error" => "temporarily_unavailable" ), 503 );

  $client_id     = $loader->nest->user_input( "post", "client_id", "string" );
  $client_secret = $loader->nest->user_input( "post", "client_secret", "string" );
  $token         = $loader->nest->user_input( "post", "token", "string" );

  $client = $iyol->oauth_client( $client_id );
  if ( !$iyol->oauth_client_ok( $client, $client_secret ) )
    $send( array( "error" => "invalid_client" ), 401 );

  if ( $token ) $iyol->revoke( $token );

  // RFC 7009: always 200 for valid clients, even for unknown tokens
  $send( array( "revoked" => true ) );

}

?>
