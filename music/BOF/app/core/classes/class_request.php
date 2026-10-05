<?php

if ( !defined( "bof_root" ) ) die;

class request {

  private $url = false;
  private $userAgent = false;
  private $endpoint_name = null;
  private $endpoint_by_request_data = null;
  private $cache = [];

  public function check( $set=true ){

    $check = (object)[];
    $check->user = bof()->user->check( $set );
    $check->endpoint = bof()->endpoint->check( $set );
    return $check;

  }
  public function log(){

    $log = bof()->request->make_log();
    $db_id = bof()->request->insert_log( $log );

  }
  public function make_log(){

    // Endpoint data
		$endpoint_name = bof()->endpoint->get()->name;
    $endpoint_data = bof()->endpoint->get()->data;
    $endpoint_args = bof()->endpoint->get()->args;

    // User ID
    $user_check = bof()->user->get();
		$user_id = $user_check->logged ? $user_check->data["ID"] : 0;

    // Url
		$request_url   = bof()->request->get_requested_url();

		// User inputs
		$request_cookies = json_encode( $_COOKIE, JSON_UNESCAPED_UNICODE );
		$request_posts   = in_array( $endpoint_name, [ "user_auth" ], true ) ? null : json_encode( $_POST, JSON_UNESCAPED_UNICODE );
		$request_params  = json_encode( $_GET, JSON_UNESCAPED_UNICODE );
    $request_heads   = json_encode( bof()->request->get_httpHeaders(), JSON_UNESCAPED_UNICODE );
		$request_sessid  = bof()->session->getID();

    // User IP
		$ip_data = bof()->request->get_userIP();

		// Parsed agent-data
		$agent_data_string = bof()->request->get_userAgent();
    $agent_string      = $agent_data_string["string"];
    $agent_data        = $agent_data_string["data"];
		$agent_os          = !empty( $agent_data["os"]["name"] )      ? strtolower( $agent_data["os"]["name"] )      : null;
		$agent_browser     = !empty( $agent_data["browser"]["name"] ) ? strtolower( $agent_data["browser"]["name"] ) : null;
		$agent_engine      = !empty( $agent_data["engine"]["name"] )  ? strtolower( $agent_data["engine"]["name"] )  : null;
		$agent_model       = !empty( $agent_data["device"]["model"] ) ? strtolower( $agent_data["device"]["model"] ) : null;
		$agent_type        = !empty( $agent_data["device"]["type"] )  ? strtolower( $agent_data["device"]["type"] )  : null;

    // Referer
		$referer = null;
		$referer_full = null;
		if ( ( $referer_full = bof()->nest->user_input( "server", "HTTP_REFERER", "url", [ "default_scheme" => false, "remove_fragment" => true ] ) ) ){
			$referer = str_replace( "www.", "", parse_url( $referer_full, PHP_URL_HOST ) );
		}

    return array(
      "endpoint_name" => $endpoint_name,
      "endpoint_data" => !empty( $endpoint_data ) ? ( is_array( $endpoint_data ) ? json_encode( $endpoint_data ) : $endpoint_data ) : null,
      "user_id" => $user_id,
      "request_url" => $request_url,
      "request_sessid" => $request_sessid,
      "request_cookies" => $request_cookies,
      "request_posts" => $request_posts,
      "request_params" => $request_params,
      "request_headers" => $request_heads,
      "ip" => $ip_data["string"],
      "ip_country" => !empty( $ip_data["country"] ) ? $ip_data["country"] : null,
      "agent" => $agent_string,
      "agent_model" => $agent_model,
      "agent_type" => $agent_type,
      "agent_os" => $agent_os,
      "agent_browser" => $agent_browser,
      "agent_engine" => $agent_engine,
      "referer" => $referer,
      "referer_full" => $referer_full,
      "sta" => 0,
      "result" => null
    );

  }
  public function insert_log( $args ){

    foreach( $args as $k => $v ){
      $set_args[] = [ $k, $v ];
    }

    return bof()->db->_insert( array(
      "table" => bof()->object->core_setting->get( "request_log_table_name", null, [ "invalid_death" => true ] ),
      "set"   => $set_args
    ) );

  }

  public function get_requested_url( $force_recheck = false ){

    if ( $force_recheck ) $this->url = false;
    if ( $this->url !== false ) return $this->url;
    $server_request_uri = bof()->nest->user_input( "server", "REQUEST_URI", "string" );

    // remove prefix ( for Apache aliases )
    $server_prefix = bof()->nest->user_input( "server", "CONTEXT_PREFIX", "string" );
    if ( $server_prefix ? substr( $server_request_uri, 0, strlen( $server_prefix ) ) == $server_prefix : false )
    $server_request_uri = substr( $server_request_uri, strlen( $server_prefix ) );

    // Nginx support
    if ( bof()->nest->user_input( "get", "_st", "equal", [ "value" => "nginx" ] ) ?
      ( ( $_lc = bof()->nest->user_input( "get", "_lc", "in_array", [ "values" => [ "root", "api", "aapi", "admin" ] ] ) ) ?
        ( bof()->nest->user_input( "server", "SERVER_SOFTWARE", "string", [ "regex" => "/nginx/" ] ) ) :
      false ) :
    false
    ){
      if ( ( $get_q = bof()->nest->user_input( "get", "q", "string" ) ) ){
        $get_q_t = explode( "?", $get_q );
        $get_q = reset( $get_q_t );
        if ( !empty( $get_q_t[1] ) ){
          $_wqe = explode( "=", $get_q_t[1] );
          if ( count( $_wqe ) == 2 )
          $_GET[ $_wqe[0] ] = $_wqe[1];
        }
        $_ms = [ "root" => 1, "api" => 5, "aapi" => 8, "admin" => 7 ];
        $_GET["q"] = substr( $get_q, $_ms[ $_lc ] );
      }
    }

    // decode & parse url string
    $server_request_uri_decoded = $server_request_uri ? urldecode( $server_request_uri ) : null;
    $server_request_uri_parsed = $server_request_uri_decoded ? parse_url( $server_request_uri_decoded, PHP_URL_PATH ) : null;
    $_parsed = $server_request_uri_decoded ? parse_url( $server_request_uri_decoded, PHP_URL_QUERY ) : null;
    if ( $_parsed ) parse_str( $_parsed, $server_request_uri_gets );

    // get best request type
    if ( !empty( $server_request_uri_gets["q"] ) )
    $request = substr( $server_request_uri_parsed, 1 );
    elseif ( ( $get_q = bof()->nest->user_input( "get", "q", "string" ) ) )
    $request = $get_q;
    elseif ( $server_request_uri_parsed ? preg_match( "/index.php/", $server_request_uri_parsed ) : false )
    $request = false;

    $this->url = !empty( $request ) ? $request : null;

    return $this->url;

  }
  public function get_userAgent(){

    if ( !empty( $this->userAgent ) )
    return $this->userAgent;

		$agentString = bof()->nest->user_input( "server", "HTTP_USER_AGENT", "string" );
		if ( !$agentString ){

      $this->userAgent = array(
        "string" => false,
        "data"   => false
      );

      return $this->userAgent;

    }

		require_once( realpath( bof_root . "/app/core/third/WhichBrowser_Parser-PHP_v2.1.1/autoload.php" ) );
		$agent_data = json_decode(strtolower(json_encode(new WhichBrowser\Parser( $agentString ))),1);

    $this->userAgent = array(
      "string" => $agentString,
      "data"   => $agent_data
    );

    return $this->userAgent;

	}
  public function get_userIP(){

    $IP = bof()->nest->user_input( "server", "REMOTE_ADDR", "ip", [], "0" );

    if ( defined( "cf_cache" ) ? cf_cache : false ){
      
      if ( ( $http_IP = bof()->nest->user_input( "http_header", "cf_connecting_ip", "ip" ) ) ){
        $IP = $http_IP;
      }
      elseif ( ( $http_IP = bof()->nest->user_input( "http_header", "X_FORWARDED_FOR", "ip" ) ) ){
        $IP = $http_IP;
      }

    }

    if ( bof()->object->core_setting->get( "ip_get_data" ) ){

      try {
        $check = bof()->db->_select( array(
          "table" => "_bof_log_ips",
          "where" => array(
            [ "IP", "=", $IP ]
          ),
          "limit" => 1,
          "single" => true
        ) );
      } catch ( Exception $err ){
        $check = false;
      }

      if ( $check ){
        $IP_Data = $check;
      }
      else {

        try {

          $IP_Data = bof()->request->get_userIP_Data( $IP );
          if ( $IP_Data === false ){
            bof()->db->_insert( array(
              "table" => "_bof_log_ips",
              "ignore" => true,
              "set" => array(
                [ "IP", $IP ],
                [ "source", "ip-api" ],
                [ "continent", null ],
                [ "country", null ],
                [ "region", null ],
                [ "lat", null ],
                [ "lon", null ],
                [ "full_response", null ],
                [ "time_expire", bof()->general->mysql_timestamp( time() + (1*24*60*60) ) ]
              )
            ) );
          } else {
            bof()->db->_insert( array(
              "table" => "_bof_log_ips",
              "ignore" => true,
              "set" => array(
                [ "IP", $IP ],
                [ "source", "ip-api" ],
                [ "continent", $IP_Data["continent"] && strlen($IP_Data["continent"])==2 ? $IP_Data["continent"] : null ],
                [ "country", $IP_Data["country"] && strlen($IP_Data["country"])==2 ? $IP_Data["country"] : null ],
                [ "region", $IP_Data["region"] && strlen($IP_Data["region"])==2 ? $IP_Data["region"] : null ],
                [ "lat", $IP_Data["lat"] ],
                [ "lon", $IP_Data["lon"] ],
                [ "full_response", json_encode( $IP_Data["full_response"] ) ],
                [ "time_expire", bof()->general->mysql_timestamp( time() + (3*24*60*60) ) ]
              )
            ) );
          }

        } catch ( Exception $err ){
        }

      }

    }

    return [
      "string" => $IP,
      "country" => !empty( $IP_Data["country"] ) ? $IP_Data["country"] : "_U"
    ];

  }
  public function get_userIP_Data( $IPAddr ){

    if ( !$IPAddr )
    return false;

    $data = bof()->curl->exe( array(
      "url" => "http://ip-api.com/json/{$IPAddr}",
      "ctimeout" => 1,
      "timeout" => 5,
    ) )["data"];

    if ( !empty( $data ) ){

      // sanitize API data
      foreach( $data as $i => $v ){
        bof()->nest->validate( $v, "string" );
        $data[ $i ] = $v;
      }

      return array(
        "IP" => $IPAddr,
        "continent" => !empty( $data["continentCode"] ) ? $data["continentCode"] : null,
        "country" => !empty( $data["countryCode"] ) ? $data["countryCode"] : null,
        "region" => !empty( $data["region"] ) ? $data["region"] : null,
        "lat" => !empty( $data["loc"] ) ? $data["loc"] : null,
        "lon" => !empty( $data["loc"] ) ? $data["loc"] : null,
        "full_response" => $data
      );

    }

    return false;

  }
  public function get_httpHeaders(){

    $headers = [];
    foreach( $_SERVER as $_k => $_v ){
      if ( substr( str_replace( "-", "_", strtolower( $_k ) ), 0, 11 ) == "http_x_bof_" ){
        $headers[ substr( str_replace( "-", "_", strtolower( $_k ) ), 11 ) ] = $_v;
      }
    }
    return $headers;

  }
  public function is_mobile(){

    $platform = !empty( bof()->request->get_httpHeaders()["platform"] ) ? bof()->request->get_httpHeaders()["platform"] : null;

    if ( in_array( $platform, [ "android", "ios" ], true ) )
    return true;

    if ( in_array( $platform, [ "windows", "mac" ], true ) )
    return false;

    if ( !empty( bof()->request->get_userAgent()["data"]["device"]["type"] ) ? bof()->request->get_userAgent()["data"]["device"]["type"] != "desktop" : false )
    return true;

    return false;

  }
  public function match_page( $pages, $check_slug=false ){

    $this->cache["page"] = 404;

    $requested_query = bof()->request->get_requested_url();
    if ( !$requested_query ) $requested_query = "/";

    // var_dump( $requested_query );
    // echo "<hr><hr>";

    foreach( $pages as $_page_k => $_page ){

      // var_dump( $_page_k );
      // echo "<br>";
      // var_dump( $_page );
      // echo "<br>";
      // var_dump( preg_match( "/".( str_replace( [ "/", "\\\\/" ], [ "\\/", "\\/" ], $_page["url"] ) )."/", $requested_query ) );
      // echo "<hr>";

      if ( $_page["url"] === true || empty( $_page["url"] ) ) continue;
      if ( preg_match( "/". str_replace( [ "/", "\\\\/" ], [ "\\/", "\\/" ], $_page["url"] ) ."/", $requested_query, $match ) ){

        if ( $check_slug && !empty( $match ) ? count( $match ) >= 2 : false ){

          if ( empty( $match[1] ) || ( empty( $_page["object"] ) && empty( $_page["free_content"] ) ) )
          return false;

          $object_item = null;

          if ( empty( $_page["free_content"] ) ){

            $object_name = $_page["object"];
            $the_object = bof()->object->__get( $object_name );
            $object_item = $the_object->select(
              array(
                ( !empty( $_page["object_column"] ) ? $_page["object_column"] : "seo_url" ) => $match[1],
              ),
              array(
                "indexed" => true,
                "match_page" => true,
                "limit" => 1,
              )
            );

            if ( !$object_item )
            return false;

          }

          return array( $_page_k, array(
            "item" => $object_item,
            "object" => !empty( $object_name ) ? $object_name : null,
            "page" => $_page,
          ) );

        }

        return array( $_page_k, array(
          "page" => $_page
        ) );

      }

    }
    // die;

    return false;

  }

}

?>
