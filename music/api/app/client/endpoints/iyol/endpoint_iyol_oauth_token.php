<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/v1/oauth/token
 * RFC 6749 token endpoint — grants: authorization_code, refresh_token,
 * client_credentials. Emits raw OAuth-format JSON (no BOF wrapper).
 */
function endpoint_iyol_oauth_token( $loader, $excuter, $args ){

  $send = function( $data, $http=200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
      header( "Cache-Control: no-store" );
      header( "Pragma: no-cache" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES );
    exit;
  };

  $err = function( $code, $desc, $http=400 ) use ( $send ){
    $send( array( "error" => $code, "error_description" => $desc ), $http );
  };

  if ( $_SERVER["REQUEST_METHOD"] !== "POST" )
    $err( "invalid_request", "POST required", 405 );

  $iyol = bof()->iyolme;
  if ( !$iyol->enabled() )
    $err( "temporarily_unavailable", "IyolMe integration is disabled", 503 );

  $grant         = $loader->nest->user_input( "post", "grant_type", "string" );
  $client_id     = $loader->nest->user_input( "post", "client_id", "string" );
  $client_secret = $loader->nest->user_input( "post", "client_secret", "string" );

  if ( !$grant || !$client_id )
    $err( "invalid_request", "grant_type and client_id required" );

  $client = $iyol->ensure_client();
  $client = ( $client && $client["client_id"] === $client_id ) ? $client : $iyol->oauth_client( $client_id );
  if ( !$iyol->oauth_client_ok( $client, $client_secret ) )
    $err( "invalid_client", "Invalid client credentials", 401 );

  switch ( $grant ){

    case "authorization_code":
      $code         = $loader->nest->user_input( "post", "code", "string" );
      $redirect_uri = $loader->nest->user_input( "post", "redirect_uri", "string" );
      if ( !$code ) $err( "invalid_request", "code required" );

      $row = $iyol->consume_code( $client_id, $code, $redirect_uri ?: null );
      if ( !$row ) $err( "invalid_grant", "Code is invalid, expired or already used" );

      $tokens = $iyol->issue_tokens( $client_id, (int)$row["user_id"], $row["scope"] ?: "profile" );
      if ( !$tokens ) $err( "server_error", "Could not issue tokens", 500 );

      $tokens["user"] = $iyol->user_payload( (int)$row["user_id"] );
      $send( $tokens );
      break;

    case "refresh_token":
      $refresh = $loader->nest->user_input( "post", "refresh_token", "string" );
      if ( !$refresh ) $err( "invalid_request", "refresh_token required" );

      $tokens = $iyol->refresh( $client_id, $refresh );
      if ( !$tokens ) $err( "invalid_grant", "Refresh token is invalid, expired or revoked" );
      $send( $tokens );
      break;

    case "client_credentials":
      $scope = $loader->nest->user_input( "post", "scope", "string" ) ?: "attribution reels";
      $tokens = $iyol->issue_tokens( $client_id, null, $scope );
      if ( !$tokens ) $err( "server_error", "Could not issue token", 500 );
      $send( $tokens );
      break;

    default:
      $err( "unsupported_grant_type", "Supported: authorization_code, refresh_token, client_credentials" );

  }

}

?>
