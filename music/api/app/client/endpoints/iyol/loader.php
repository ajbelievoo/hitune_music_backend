<?php

/**
 * IyolMe integration — client endpoint registrations.
 *
 * Public OAuth/v1 surface (for the IyolMe SERVER, cross-machine):
 *   GET|POST /api/oauth/authorize          consent page -> auth code
 *   POST     /api/v1/oauth/token           authorization_code | refresh_token | client_credentials
 *   GET      /api/v1/oauth/userinfo        Bearer JWT -> profile
 *   POST     /api/v1/oauth/revoke          RFC 7009 revocation
 *   GET      /api/v1/audio/attribution     track metadata + rights
 *   POST     /api/v1/iyol/webhook          inbound IyolMe events (HMAC signed)
 *
 * App surface (BOF signed, logged-in HiTune user):
 *   POST /api/iyol_sso_link                mint one-time SSO code
 *   POST /api/iyol_publish                 push rendered reel to IyolMe
 */

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

$iyol_dir = root . "/app/client/endpoints/iyol/";

/* ------------------------- OAuth / public v1 ------------------------- */

bof()->object->endpoint->add( "iyol_oauth_authorize", array(
  "url" => "oauth/authorize",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "html",
  "response_data" => [],
  "executers" => array( $iyol_dir . "endpoint_iyol_authorize.php" )
) );

bof()->object->endpoint->add( "iyol_oauth_token", array(
  "url" => "v1/oauth/token",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_oauth_token.php" )
) );

bof()->object->endpoint->add( "iyol_oauth_userinfo", array(
  "url" => "v1/oauth/userinfo",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_oauth_userinfo.php" )
) );

bof()->object->endpoint->add( "iyol_oauth_revoke", array(
  "url" => "v1/oauth/revoke",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_oauth_revoke.php" )
) );

bof()->object->endpoint->add( "iyol_audio_attribution", array(
  "url" => "v1/audio/attribution",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_attribution.php" )
) );

bof()->object->endpoint->add( "iyol_webhook", array(
  "url" => "v1/iyol/webhook",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_webhook.php" )
) );

bof()->object->endpoint->add( "iyol_my_music", array(
  "url" => "v1/iyol/my_music",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_my_music.php" )
) );

bof()->object->endpoint->add( "iyol_iyolme_login", array(
  "url" => "iyol/iyolme_login",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "html",
  "response_data" => [],
  "executers" => array( $iyol_dir . "endpoint_iyol_iyolme_login.php" )
) );

/* ------------------------- App (public mirror) --------------------- */

bof()->object->endpoint->add( "iyol_stories", array(
  "url" => "iyol/stories",
  "groups" => [ "v1_public" ],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $iyol_dir . "endpoint_iyol_stories.php" )
) );

/* ------------------------- App (signed, user) ------------------------ */

bof()->object->endpoint->add( "iyol_sso_link", array(
  "url" => "iyol_sso_link",
  "groups" => [ "user" ],
  "executers" => array( $iyol_dir . "endpoint_iyol_sso_link.php" )
) );

bof()->object->endpoint->add( "iyol_publish", array(
  "url" => "iyol_publish",
  "groups" => [ "user" ],
  "executers" => array( $iyol_dir . "endpoint_iyol_publish.php" )
) );

bof()->object->endpoint->add( "iyol_status", array(
  "url" => "iyol_status",
  "groups" => [ "user" ],
  "executers" => array( $iyol_dir . "endpoint_iyol_status.php" )
) );

?>
