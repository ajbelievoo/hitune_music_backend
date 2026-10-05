<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/v1/token
 * grant_type=client_credentials&client_id=ht_live_xxx&client_secret=ht_sec_yyy
 * -> { access_token, token_type, expires_in }
 */

function endpoint_v1_token( $loader, $excuter, $args ){

  dev_v1_preflight();

  if ( $_SERVER["REQUEST_METHOD"] !== "POST" )
    return $loader->developer_api->v1_error( "invalid_request", 405, "POST required" );

  $grant = $loader->nest->user_input( "post", "grant_type", "string" );
  if ( $grant !== "client_credentials" )
    return $loader->developer_api->v1_error( "invalid_request", 400, "grant_type must be client_credentials" );

  $client_id = $loader->nest->user_input( "post", "client_id", "string" );
  $client_secret = $loader->nest->user_input( "post", "client_secret", "string" );

  if ( !$client_id || !$client_secret )
    return $loader->developer_api->v1_error( "invalid_request", 400, "client_id and client_secret required" );

  $result = $loader->developer_api->issue_token( $client_id, $client_secret );

  if ( !empty( $result["error"] ) )
    return $loader->developer_api->v1_error(
      $result["error"] === "invalid_client" ? "invalid_key" : "forbidden",
      $result["error"] === "invalid_client" ? 401 : 403,
      $result["error"] === "invalid_client" ? "Invalid client credentials" : "Application is not active",
      !empty( $result["status"] ) ? array( "status" => $result["status"] ) : array()
    );

  $loader->developer_api->v1_send( $result );

}

?>
