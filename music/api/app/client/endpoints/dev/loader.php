<?php

/**
 * HiTune Developer API — client endpoint registrations.
 *
 * Two surfaces (see DEVELOPER_API.md):
 *  - /api/developer_*   — signed app-API endpoints (BOF signature + session,
 *                         group "user"/"api") powering the in-app developer
 *                         portal.
 *  - /api/dev/billing   — public Razorpay redirect target (no groups).
 *  - /api/v1/*          — public REST developer API (plain x-api-key /
 *                         Bearer auth handled inside each executer, no BOF
 *                         signing — external devs can't produce signatures).
 */

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

$dev_endpoints_dir = root . "/app/client/endpoints/dev/";
$v1_dir  = root . "/app/client/endpoints/v1/";

// CORS preflight helper shared by all v1 executers
function dev_v1_preflight(){
  if ( $_SERVER["REQUEST_METHOD"] !== "OPTIONS" ) return false;
  $origin = !empty( $_SERVER["HTTP_ORIGIN"] ) ? $_SERVER["HTTP_ORIGIN"] : "*";
  header( "Access-Control-Allow-Origin: {$origin}" );
  header( "Vary: Origin" );
  header( "Access-Control-Allow-Headers: x-api-key, authorization, content-type" );
  header( "Access-Control-Allow-Methods: GET, POST, OPTIONS" );
  header( "Access-Control-Max-Age: 86400" );
  http_response_code( 204 );
  exit;
}

/* ------------------------------------------------------------------ */
/* Developer portal (signed app API)                                    */
/* ------------------------------------------------------------------ */

// Debug endpoint to verify v1 routing
// bof()->object->endpoint->add( "v1_ping", array(
//   "url" => "v1/ping",
//   "response_type" => "json",
//   "executers" => array( $v1_dir . "endpoint_v1_ping.php" )
// ) );

bof()->object->endpoint->add( "developer_plans", array(
  "url" => "developer_plans",
  "groups" => [ "api" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_plans.php" )
) );

bof()->object->endpoint->add( "developer_apps", array(
  "url" => "developer_apps",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_apps.php" )
) );

bof()->object->endpoint->add( "developer_app_create", array(
  "url" => "developer_app_create",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_app_create.php" )
) );

bof()->object->endpoint->add( "developer_app_update", array(
  "url" => "developer_app_update",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_app_update.php" )
) );

bof()->object->endpoint->add( "developer_app_delete", array(
  "url" => "developer_app_delete",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_app_delete.php" )
) );

bof()->object->endpoint->add( "developer_key_regenerate", array(
  "url" => "developer_key_regenerate",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_key_regenerate.php" )
) );

bof()->object->endpoint->add( "developer_usage", array(
  "url" => "developer_usage",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_usage.php" )
) );

bof()->object->endpoint->add( "developer_subscribe", array(
  "url" => "developer_subscribe",
  "groups" => [ "user" ],
  "executers" => array( $dev_endpoints_dir . "endpoint_developer_subscribe.php" )
) );

// Razorpay payment-link redirect target (browser GET, unsigned)
bof()->object->endpoint->add( "dev_billing", array(
  "url" => "dev/billing",
  "response_type" => "json",
  "executers" => array( $dev_endpoints_dir . "endpoint_dev_billing.php" )
) );

/* ------------------------------------------------------------------ */
/* Public REST API v1 (key/token auth inside executers)                 */
/* ------------------------------------------------------------------ */

// Re-enabling v1 endpoints with NO groups to bypass all BOF group comparators
// Authentication is handled inside each endpoint via developer_api->v1_auth()

bof()->object->endpoint->add( "v1_ping", array(
  "url" => "v1/ping",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_ping.php" )
) );

bof()->object->endpoint->add( "v1_search", array(
  "url" => "v1/search",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_search.php" )
) );

bof()->object->endpoint->add( "v1_catalog", array(
  "url" => "v1/catalog",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_search.php" )
) );

bof()->object->endpoint->add( "v1_token", array(
  "url" => "v1/token",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_token.php" )
) );

bof()->object->endpoint->add( "v1_plans", array(
  "url" => "v1/plans",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_plans.php" )
) );

bof()->object->endpoint->add( "v1_charts", array(
  "url" => "v1/charts",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_charts.php" )
) );

bof()->object->endpoint->add( "v1_contests", array(
  "url" => "v1/contests",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_contests.php" )
) );

bof()->object->endpoint->add( "v1_radio", array(
  "url" => "v1/radio",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_radio.php" )
) );

bof()->object->endpoint->add( "v1_clips", array(
  "url" => "v1/clips",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_clips.php" )
) );

bof()->object->endpoint->add( "v1_genres", array(
  "url" => "v1/genres",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_genres.php" )
) );

bof()->object->endpoint->add( "v1_track_stream", array(
  "url" => array(
    "regex" => "/^v1\/tracks\/([a-zA-Z0-9\-_]{32})\/stream\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_track_stream.php" )
) );

bof()->object->endpoint->add( "v1_track", array(
  "url" => array(
    "regex" => "/^v1\/tracks\/([a-zA-Z0-9\-_]{32})\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_track.php" )
) );

bof()->object->endpoint->add( "v1_album", array(
  "url" => array(
    "regex" => "/^v1\/albums\/([a-zA-Z0-9\-_]{32})\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_album.php" )
) );

bof()->object->endpoint->add( "v1_artist", array(
  "url" => array(
    "regex" => "/^v1\/artists\/([a-zA-Z0-9\-_]{32})\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_artist.php" )
) );

bof()->object->endpoint->add( "v1_playlist", array(
  "url" => array(
    "regex" => "/^v1\/playlists\/([a-zA-Z0-9\-_]{32})\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_playlist.php" )
) );

bof()->object->endpoint->add( "v1_stream", array(
  "url" => array(
    "regex" => "/^v1\/stream\/([a-zA-Z0-9\-_=\.]{40,200})\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_stream.php" )
) );

bof()->object->endpoint->add( "v1_embed", array(
  "url" => array(
    "regex" => "/^v1\/embed\/(track|album|playlist|artist)\/([a-zA-Z0-9\-_]{32})\/?$/"
  ),
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_embed.php" )
) );

bof()->object->endpoint->add( "v1_oembed", array(
  "url" => "v1/oembed",
  "groups" => [],
  "skip_key_check" => true,
  "response_type" => "json",
  "executers" => array( $v1_dir . "endpoint_v1_oembed.php" )
) );

?>
