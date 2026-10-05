<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/iyol_sso_link   (group: user — BOF signed app call)
 *
 * Mints a one-time OAuth authorization code for the logged-in HiTune user.
 * The app hands this code to IyolMe (deep link or API); IyolMe's backend
 * exchanges it server-to-server via POST /api/v1/oauth/token
 * (grant_type=authorization_code) and gets a JWT + user profile — no
 * password re-entry, no browser needed.
 */
function endpoint_iyol_sso_link( $loader, $excuter, $args ){

  $iyol = bof()->iyolme;
  if ( !$iyol->configured() )
    return $loader->api->set_error( "failed", array( "code" => "iyol_disabled" ) );

  $user_id = 0;
  try {
    $u = $loader->user->check();
    $user_id = ( $u && !empty( $u->ID ) ) ? (int)$u->ID : (int) bof()->user->get( "ID" );
  } catch ( \Throwable $e ) {
    $user_id = (int) bof()->user->get( "ID" );
  }
  if ( !$user_id )
    return $loader->api->set_error( "access_denied" );

  $scope = $loader->nest->user_input( "post", "scope", "string" ) ?: "profile email reels";
  $code  = $iyol->create_code( $iyol->client_id(), $user_id, null, $scope );

  $loader->api->set_message( "ok", array(
    "code"         => $code,
    "expires_in"   => 600,
    "token_url"    => web_address . "api/v1/oauth/token",
    "userinfo_url" => web_address . "api/v1/oauth/userinfo"
  ) );

}

?>
