<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

$bof_instance = new BusyOwlFramework(array(
  "name" => "bof_client",
  "plugins" => array(
    "youtube" => [],
    "soundcloud" => [],
    "ffmpeg" => [],
    "google" => [],
    "social_login" => [],
    "id3" => [],
    "chapar" => [],
    "pgt" => [],
    "ai" => [],
    "nodejs" => []
  )
));

function bof(){
  global $bof_instance;
  return $bof_instance;
}
function turn( $hook, $params=[], $args=[] ){
  return bof()->object->language->turn( $hook, $params, array_merge(
    $args,
    array(
      "uc_first" => true,
      "lang" => "users"
    )
  ) );
}

bof()->__setup();

bof()->object->core_setting->set( "supported_platforms", array( "web", "mobile" ), true );

require_once( root . "/app/client/_setup/endpoint_groups.php" );
require_once( root . "/app/client/_setup/classes.php" );
require_once( root . "/app/client/_setup/objects.php" );
require_once( root . "/app/client/_setup/db.php" );
require_once( root . "/app/client/_setup/plugins.php" );

// Developer API module (portal endpoints + public /api/v1/* surface) - load BEFORE legacy endpoints
require_once( root . "/app/client/endpoints/dev/loader.php" );

// Legacy endpoints (including search, now at /api/app_search)
require_once( root . "/app/client/_setup/endpoints.php" );

// Music Distribution Module
require_once( root . "/app/client/endpoints/dist/loader.php" );

// IyolMe integration (OAuth provider + reel push + sound registry)
require_once( root . "/app/client/endpoints/iyol/loader.php" );

// Global outbound proxy (cURL Proxy equivalent) — set `_bof_setting` `curl_proxy`
// to "host:port" or JSON {address,port,username,password,type:http|socks4|socks5}
$_curl_proxy = bof()->object->db_setting->get( "curl_proxy" );
if ( $_curl_proxy ){
  $_curl_proxy_parsed = is_string( $_curl_proxy ) ? json_decode( $_curl_proxy, true ) : $_curl_proxy;
  bof()->curl->set_args( array( "proxy" => is_array( $_curl_proxy_parsed ) ? $_curl_proxy_parsed : $_curl_proxy ) );
}

bof()->object->core_setting->set( "session_table_name", "_bof_cache_sessions" );
bof()->object->core_setting->set( "request_log_table_name", "_bof_log_requests" );
bof()->object->core_setting->set( "api_request_log_table_name", "_bof_log_api_requests", true );

?>
