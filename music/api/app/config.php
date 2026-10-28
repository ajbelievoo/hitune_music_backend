<?php

// Guard against double-inclusion via differing resolved paths — a second
// pass re-defines constants (warning) and re-applies session ini settings,
// and a bootstrap-time warning cascades into a 500 through BOF's
// error_inspector.
if ( defined( "base_root" ) )
return;

define( "rawmean", false );

define( "base_root", dirname(dirname(dirname(__FILE__))) );
define( "bof_root", rawmean ? "E:/busyowl/frame/" : base_root . "/BOF/" );
define( "plugins_root", base_root . "/plugins/" );
define( "themes_root", base_root . "/themes/" );
define( "root", base_root . "/api/" );

// Share session across subdomains — only safe before a session exists and
// before output; changing these mid-request emits warnings that cascade.
if ( session_status() === PHP_SESSION_NONE && !headers_sent() ){
  ini_set('session.cookie_domain', '.hitune.in');
  session_set_cookie_params([
      'lifetime' => 86400,
      'path' => '/',
      'domain' => '.hitune.in',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Lax'
  ]);
}

require_once( dirname(__FILE__) . "/config_user.php" );
require_once( dirname(__FILE__) . "/config_license.php" );

define( "endpoint_address", web_address . "api/" );
define( "assets_address", endpoint_address . "assets/" );
define( "themes_address", web_address . "themes/" );

define( "bof_assets_address", rawmean ? "http://localhost:666/assets/" : web_address . "BOF/assets/" );

define( "admin_web_address", web_address . "admin/" );
define( "admin_endpoint_address", endpoint_address . "be/" );

define( "david", false );
define( "version", 2081 );
define( "bof_version", 2110 );

?>
