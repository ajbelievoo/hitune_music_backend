<?php

class object_core_setting {

  public function __construct(){
    $this->data["session_lock_ip"] = defined("session_ip_lock") ? session_ip_lock : false;
    $this->data["session_lock_agent"] = defined("session_pf_lock") ? session_pf_lock : false;
    $this->data["session_expire"] = defined("session_life") ? ( session_life ? session_life*(60*60) : 0 ) : 0;
    $this->data["session_max"] = defined("session_max") ? session_max : false;
    $this->data["session_cc"] = defined("session_cc") ? session_cc : 10;
    $this->data["cloudflare_cache"] = defined( "cf_cache" ) ? cf_cache === true : false;
    $this->data["nginx_server"] = defined( "nginx" ) ? nginx === true : false;
    $this->data["admin_session_lock_ip"] = defined( "admin_ip_lock" ) ? admin_ip_lock : true;
    $this->data["admin_session_lock_agent"] = defined( "admin_ua_lock" ) ? admin_ua_lock : true;
    $this->data["admin_session_expire"] = defined( "admin_ti_lock" ) ? admin_ti_lock : false;
    $this->data["admin_session_max"] = defined( "admin_nu_lock" ) ? admin_nu_lock : 1;
    $this->data["debug_report"] = defined("debug_report") ? debug_report : false;
    $this->data["search_index_type"] = defined("fulltext_search") ? fulltext_search : "fulltext";
    $this->data["piped_youtube"] = defined("youtube_piped") ? youtube_piped : false;
    $this->data["search_ii_interval"] = defined("search_ii_interval") ? search_ii_interval : 15;
    $this->data["id3_write_cover"] = defined("id3_write_cover") ? id3_write_cover : false;
    $this->data["client_private"] = defined("client_private") ? client_private : false;
  }

  protected $data = array(

    "debug" => !production,
    "debug_report" => false,

    "client_private" => false,

    "session_lock_ip" => false,
    "session_lock_agent" => false,
    "session_expire" => false,
    "session_max" => false,
    "session_cc" => false,

    "admin_session_lock_ip" => false,
    "admin_session_lock_agent" => false,
    "admin_session_expire" => false,
    "admin_session_max" => false,

    "session_table_name" => "_bof_cache_sessions",
    "session_live_user_data" => session_live,

    "curl_cache" => false,
    "curl_cache_load" => false,

    "ip_get_data" => true,
    "id3_write_cover" => false,

    "cloudflare_cache" => false,
    "nginx_server" => false,
    "search_index_type" => null,
    "search_ii_interval" => null,
    "piped_youtube" => false,

    "file_save_base_directory" => "files",
    "request_log_table_name" => "_bof_log_requests",

    "supported_social_logins" => array(
      "google" => array(
        "slang" => "gg",
        "hybirdName" => "Google",
        "_title" => "Google",
        "_icon" => "google"
      ),
      "facebook" => array(
        "slang" => "fb",
        "hybirdName" => "Facebook",
        "_title" => "Facebook",
        "_icon" => "facebook"
      ),
      "instagram" => array(
        "slang" => "ig",
        "hybirdName" => "Instagram",
        "_title" => "Instagram",
        "_icon" => "instagram"
      ),
      "twitter" => array(
        "slang" => "tw",
        "hybirdName" => "Twitter",
        "_title" => "X",
        "_icon" => "twitter"
      ),
      "spotify" => array(
        "slang" => "sp",
        "hybirdName" => "Spotify",
        "_title" => "Spotify",
        "_icon" => "spotify"
      ),
      "dribbble" => array(
        "slang" => "dr",
        "hybirdName" => "Dribbble",
        "_title" => "Dribbble",
        "_icon" => "hubspot"
      ),
      "github" => array(
        "slang" => "gh",
        "hybirdName" => "GitHub",
        "_title" => "GitHub",
        "_icon" => "github"
      ),
      "linkedin" => array(
        "slang" => "li",
        "hybirdName" => "LinkedInOpenID",
        "_title" => "LinkedIn",
        "_icon" => "linkedin"
      ),
      "disqus" => array(
        "slang" => "dq",
        "hybirdName" => "Disqus",
        "_title" => "Disqus",
        "_icon" => "disqus"
      ),
      "reddit" => array(
        "slang" => "rd",
        "hybirdName" => "Reddit",
        "_title" => "Reddit",
        "_icon" => "reddit"
      ),
      "twitch" => array(
        "slang" => "tt",
        "hybirdName" => "TwitchTV",
        "_title" => "Twitch",
        "_icon" => "twitch"
      ),
    ),

    "url_prefixes" => array(),

  );

  public function get( $var, $default_val = null, $args=[] ){

    $invalid_death = false;
    extract( $args );

    if ( !in_array( $var, array_keys( $this->data ), true ) ){
      if ( $invalid_death ) fall("Setting: {$var} is empty and it can't be");
      return $default_val;
    }

    if ( $var == "client_private" ? bof()->request->get_httpHeaders()["platform"] != "web" : false ){
      $this->data[ $var ] = true;
    }

    return $this->data[ $var ];

  }
  public function set( $var, $val, $new=false ){

    if ( !in_array( $var, array_keys( $this->data ) ) && !$new )
    return false;

    $this->data[ $var ] = $val;
    return true;

  }

}

?>
