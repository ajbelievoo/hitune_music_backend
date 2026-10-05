<?php

require_once( dirname(__FILE__) . "/env_loader.php" );

define( "web_address", bof_env("WEB_ADDRESS", "https://music.hitune.in/") );

define( "db_host", bof_env("DB_HOST", "localhost") );
define( "db_user", bof_env("DB_USER", "musicpro") );
define( "db_pass", bof_env("DB_PASS", "") );
define( "db_name", bof_env("DB_NAME", "musicpro") );

define( "client_private", bof_env_bool("CLIENT_PRIVATE", false) );
define( "client_constructing", bof_env_bool("CLIENT_CONSTRUCTING", false) );
define( "client_give_attribute", bof_env_int("CLIENT_GIVE_ATTRIBUTE", 1) );
define( "client_auto_images", bof_env_int("CLIENT_AUTO_IMAGES", 1) );

define( "session_ip_lock", bof_env_bool("SESSION_IP_LOCK", false) );
define( "session_pf_lock", bof_env_bool("SESSION_PF_LOCK", false) );
define( "session_max", bof_env_int("SESSION_MAX", 10) );
define( "session_life", bof_env_bool("SESSION_LIFE", false) );
define( "session_cc", bof_env_int("SESSION_CC", 33) );

define( "production", bof_env_bool("PRODUCTION", true) );

define( "api_send_diagnostics", bof_env("API_SEND_DIAGNOSTICS", "advanced") );

define( "session_live", bof_env_bool("SESSION_LIVE", true) );

define( "cf_cache", bof_env_bool("CF_CACHE", false) );
define( "nginx", bof_env_bool("NGINX", false) );
define( "admin_ip_lock", bof_env_bool("ADMIN_IP_LOCK", false) );

$__bof_env_timezone = bof_env("TIMEZONE", "Africa/Abidjan");
if (in_array($__bof_env_timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
    date_default_timezone_set($__bof_env_timezone);
} else {
    date_default_timezone_set("Africa/Abidjan");
}

define( "admin_ua_lock", bof_env_bool("ADMIN_UA_LOCK", false) );
define( "admin_nu_lock", bof_env_int("ADMIN_NU_LOCK", 1) );
define( "admin_ti_lock", bof_env_bool("ADMIN_TI_LOCK", false) );
define( "fulltext_search", bof_env("FULLTEXT_SEARCH", "inverted_indexing") );
define( "youtube_piped", bof_env("YOUTUBE_PIPED", "0") );
define( "maintenance", bof_env_bool("MAINTENANCE", false) );
?>
