<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

$bof_instance = new BusyOwlFramework(array(
  "name" => "bof_admin",
  "plugins" => array(
    "id3" => [],
    "ffmpeg" => [],
    "chapar" => [],
    "pgt" => [],
    "google-translate" => [],
    "ai" => [],
    "nodejs" => []
  )
));

function bof(){
  global $bof_instance;
  return $bof_instance;
}

bof()->__setup();

require_once( root . "/app/admin/_setup/endpoint_groups.php" );
require_once( root . "/app/admin/_setup/endpoints.php" );
require_once( root . "/app/admin/_setup/classes.php" );
require_once( root . "/app/admin/_setup/objects.php" );
require_once( root . "/app/admin/_setup/db.php" );
require_once( root . "/app/admin/_setup/parasites.php" );
require_once( root . "/app/admin/_setup/plugins.php" );

bof()->object->core_setting->set( "session_lock_agent", bof()->object->core_setting->get( "admin_session_lock_agent" ) );
bof()->object->core_setting->set( "session_lock_ip", bof()->object->core_setting->get( "admin_session_lock_ip" ) );
bof()->object->core_setting->set( "session_max", bof()->object->core_setting->get( "admin_session_max" ) );
bof()->object->core_setting->set( "session_expire", bof()->object->core_setting->get( "admin_session_expire" ) );
bof()->object->core_setting->set( "session_cc", 100 );

// Global outbound proxy — `_bof_setting` `curl_proxy`: "host:port" or
// JSON {address,port,username,password,type:http|socks4|socks5}
$_curl_proxy = bof()->object->db_setting->get( "curl_proxy" );
if ( $_curl_proxy ){
  $_curl_proxy_parsed = is_string( $_curl_proxy ) ? json_decode( $_curl_proxy, true ) : $_curl_proxy;
  bof()->curl->set_args( array( "proxy" => is_array( $_curl_proxy_parsed ) ? $_curl_proxy_parsed : $_curl_proxy ) );
}

bof()->object->core_setting->set( "session_table_name", "_bof_cache_sessions_admin" );
bof()->object->core_setting->set( "request_log_table_name", "_bof_log_requests_admin" );
bof()->object->core_setting->set( "api_request_log_table_name", "_bof_log_api_requests_admin", true );
bof()->object->core_setting->set( "supported_platforms", array( "web" ), true );

?>
