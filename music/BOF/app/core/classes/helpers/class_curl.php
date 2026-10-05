<?php

if ( !defined( "root" ) ) die;

class curl extends bof_type_class {

	protected $__count = 0;
	protected $__size = 0;
	protected $__time = 0;
	protected $__cache_count = 0;
	protected $default_args = array();

	public function __get_stats(){
		return array(
			"count" => $this->__count,
			"size" => $this->__size,
			"time" => $this->__time,
			"cached" => $this->__cache_count
		);
	}
	public function set_args( $args ){

		$this->default_args = array_merge( $this->default_args, $args );

	}
	public function exe( $args ){

		$url        = null;
		$referer    = null;
		$posts      = null;
		$posts_force_get = false;
		$headers    = null;
		$cookies    = null;
		$agent      = null;
		$timeout    = 30;
		$ctimeout   = 5;
		$return     = true;
		$follow     = true;
		$follow_max = 10;
		$sslverify  = false;
		$buffersize = false;
		$auth       = false;
		$auth_pass  = false;
		$nobody     = false;
		$type       = "json";
		$custom_request = false;
		$http_version = null;
		$proxy      = [];
		$hook       = null;
		$cache      = null;
		$cache_load = null;
		$cache_load_expected_header = 200;
		$cache_update = false;
		$cache_age  = 2;
		$get_header = true;
		$decode_gz = false;
		$range = false;
		$echo = true;
		$json = false;
		$force_ipv4 = true;

		if ( $this->default_args );
		extract( $this->default_args );
		extract( $args );

		if ( empty( $posts ) && !empty( $post ) )
		$posts = $post;

		if ( $json )
		$headers = array_merge( $headers ? $headers : [], array(
			'content-type: application/json',
			'accept: application/json',
		) );

		if ( $agent == "chrome" )
		$agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36";

		elseif ( $agent == "iphoneChrome" )
		$agent = "Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1";

		$hook = !$hook ? md5( json_encode( $args ) ) : $hook;

		if ( $echo && bof()->response->getType( "cli" ) )
		bof()->response_cli->echo( "CURL: Requesting {$url}", "gray" );

		$this->__count++;

		if ( $cache_load === true ? true : ( $cache_load === false ? false : bof()->object->core_setting->get( "curl_cache_load" ) ) ){

			$check_cache = bof()->db->_select([
				"table" => "_bof_log_curls",
				"where" => array(
					[ "hook", "=", $hook ],
					[ "time_start", ">", "SUBDATE( now(), INTERVAL {$cache_age} HOUR )", true ],
					[ "response_header_code", "=", $cache_load_expected_header ]
				),
				"limit" => 1,
				"single" => true
			]);

			if ( $check_cache ){

				if ( $cache_update ){
					bof()->db->_update([
						"table" => "_bof_log_curls",
						"set" => array(
							[ "used", ( !empty( $check_cache["used"] ) ? $check_cache["used"] : 0 ) + 1 ],
							[ "time_used", "now()", true ]
						),
						"where" => array(
							[ "ID", "=", $check_cache["ID"] ],
						),
					]);
				}

				if ( $echo && bof()->response->getType( "cli" ) )
				bof()->response_cli->echo( "CURL: Loaded from cache", "gray" );

				$this->__cache_count++;

				return [
					"http_code" => $check_cache["response_header_code"],
					"header"    => $check_cache["response_header"],
					"body"      => $check_cache["response_body"],
					"data"      => $type == "json" ? json_decode( $check_cache["response_body"], true ) : $check_cache["response_body"],
					"size"      => $check_cache["response_body_size"],
				];

			}

		}

		$c = curl_init();

		curl_setopt( $c, CURLOPT_URL, $url );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, $return );
		curl_setopt( $c, CURLOPT_FOLLOWLOCATION, $follow );

		if ( $force_ipv4 )
		curl_setopt( $c, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

		if ( $get_header )
		curl_setopt( $c, CURLOPT_HEADER, true );

		if ( $follow_max )
		curl_setopt( $c, CURLOPT_MAXREDIRS, $follow_max );

		if ( $timeout )
		curl_setopt( $c, CURLOPT_TIMEOUT, $timeout );

		if ( $ctimeout )
		curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, $ctimeout );

		if ( $referer )
		curl_setopt( $c, CURLOPT_REFERER, $referer );

		if ( $agent )
		curl_setopt( $c, CURLOPT_USERAGENT, $agent );

		if ( $headers )
		curl_setopt( $c, CURLOPT_HTTPHEADER, $headers );

		if ( $cookies )
		curl_setopt( $c, CURLOPT_COOKIE, $cookies );

		if ( $range ){
			curl_setopt( $c, CURLOPT_BINARYTRANSFER, true );
			curl_setopt( $c, CURLOPT_RANGE, $range );
		}

		if ( $buffersize )
		curl_setopt( $c, CURLOPT_BUFFERSIZE, $buffersize );

		if ( $nobody )
		curl_setopt( $c, CURLOPT_NOBODY, true );

		if ( $custom_request )
		curl_setopt( $c, CURLOPT_CUSTOMREQUEST, $custom_request );

		if ( $http_version )
		curl_setopt( $c, CURLOPT_HTTP_VERSION, $http_version );

		if ( $posts ){
			if ( !$posts_force_get )
			curl_setopt( $c, CURLOPT_POST, true );
			curl_setopt( $c, CURLOPT_POSTFIELDS, $posts );
		}

		if ( $auth ){
			curl_setopt( $c, CURLOPT_HTTPAUTH, $auth );
			if ( $auth_pass )
			curl_setopt( $c, CURLOPT_USERPWD, $auth_pass );
		}

		if ( !$sslverify ){
			curl_setopt( $c, CURLOPT_SSL_VERIFYHOST, false );
	    curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
		}

		if ( !empty( $proxy ) ){

			if ( is_array( $proxy ) ){

				curl_setopt( $c, CURLOPT_PROXY, $proxy["address"] );

				if ( !empty( $proxy["username"] ) && !empty( $proxy["password"] ) )
				curl_setopt( $c, CURLOPT_PROXYUSERPWD, "{$proxy["username"]}:{$proxy["password"]}" );

				if ( !empty( $proxy["port"] ) )
				curl_setopt( $c, CURLOPT_PROXYPORT, $proxy["port"] );

				if ( !empty( $proxy["type"] ) ){
					if ( $proxy["type"] == "http" ) $proxy["type"] = CURLPROXY_HTTP;
					if ( $proxy["type"] == "socks4" ) $proxy["type"] = CURLPROXY_SOCKS4;
					if ( $proxy["type"] == "socks5" ) $proxy["type"] = CURLPROXY_SOCKS5;
					curl_setopt( $c, CURLOPT_PROXYTYPE, $proxy["type"] );
				}

			}
			else {

				curl_setopt( $c, CURLOPT_PROXY, $proxy );

			}

		}

		$time_start = microtime(true);
		$response = curl_exec( $c );
		$time_exe = microtime(true) - $time_start;

		if ( curl_errno( $c ) ) {

			if ( $echo && bof()->response->getType( "cli" ) )
			bof()->response_cli->echo( "CURL: Failed " . curl_errno( $c ), "gray" );

			$err = curl_error($c);

			curl_close( $c );

			return [
				"url"       => $url,
				"args"      => $args,
				"http_code" => 0,
				"header"    => null,
				"body"      => null,
				"data"      => null,
				"size"      => 0,
				"error"     => $err
			];

		}

		$http_code = curl_getinfo( $c, CURLINFO_HTTP_CODE );

		if ( $get_header ){
			$header_size = curl_getinfo( $c, CURLINFO_HEADER_SIZE );
			$header = substr( $response, 0, $header_size );
			$body = $data = substr( $response, $header_size );
		}
		else {
			$body = $data = $response;
		}

		if ( $decode_gz && preg_match( "/Content-Encoding: gzip/", $header ) ){
			$body = $data = gzdecode( $data );
		}

		if ( $type == "json" )
		$data = json_decode( $body, 1 );
		$size = strlen( $body );
		curl_close( $c );

		$this->__size += $size;
		$this->__time += $time_exe;

		if ( ( $cache === true ? true : ( $cache === false ? false : bof()->object->core_setting->get( "curl_cache" ) ) ) && bof()->defined("db") ){

			$_as = array_merge( $this->default_args, $args );
			unset( $_as["url"] );
			bof()->db->_insert([
				"table" => "_bof_log_curls",
				"set"   => [
					[ "url", $url ],
					[ "options", json_encode( $_as ) ],
					[ "request_body", json_encode( $posts ) ],
					[ "request_header", json_encode( $headers ) ],
					[ "response_body", $body ],
					[ "response_body_size", $size ],
					[ "response_header", $header ],
					[ "response_header_code", $http_code ],
					[ "time_exe", $time_exe ],
					[ "time_expire", bof()->general->mysql_timestamp( time() + ( $cache_age*60*60 ) ) ],
					[ "hook", $hook ]
				]
			]);
		}

		if ( $echo && bof()->response->getType( "cli" ) )
		bof()->response_cli->echo( "CURL: Executed in {$time_exe} ms. Http-code: {$http_code}", "gray" );

		return [
			"http_code" => $http_code,
			"header"    => !empty( $header ) ? $header : null,
			"body"      => $body,
			"data"      => $data,
			"size"      => $size
		];

	}
	public function download( $link, $args=[] ){

		// parse link to get filename && extension
		$__lps = explode( "/", $link );
		$__lp = end( $__lps );
		$__lpp = explode( ".", $__lp );
		$filename  = reset( $__lpp );
		$extension = end( $__lpp );
		$sub_directory = null;
		$chunksize = 10 * (1024 * 1024);
		$agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
		extract( $args );

		if ( !$parts = parse_url($link) )
		throw new Exception( "bad_url" );

		switch( $parts['scheme'] ){
			case 'https':
				$scheme = 'ssl://';
				$port = 443;
				break;
			case 'http':
				default:
				$scheme = '';
				$port = 80;
		}

		// make temp file
		$tmp_file = root . "/tmp_" . uniqid();
    $i_handle = fsockopen( $scheme . $parts['host'], $port, $errstr, $errcode, 5 );
    $o_handle = fopen( $tmp_file, 'wb' );

    if ( $i_handle == false || $o_handle == false )
		throw new Exception( "bad_handles" );

		if ( !is_writable( $tmp_file ) )
		throw new Exception( "not_writeable" );

    if ( !empty( $parts['query'] ) )
    $parts['path'] .= '?' . $parts['query'];

    $request = "GET {$parts['path']} HTTP/1.1\r\n";
    $request .= "Host: {$parts['host']}\r\n";
    $request .= "User-Agent: {$agent}\r\n";
    $request .= "Keep-Alive: 115\r\n";
    $request .= "Connection: keep-alive\r\n\r\n";
    fwrite( $i_handle, $request );

    $headers = array();
    while(!feof($i_handle)) {
        $line = fgets($i_handle);
        if ($line == "\r\n") break;
        $headers[] = $line;
    }

    $length = 0;
    foreach($headers as $header) {
        if (stripos($header, 'Content-Length:') === 0) {
            $length = (int)str_replace('Content-Length: ', '', $header);
            break;
        }
    }

    $cnt = 0;
    while( !feof( $i_handle ) ){

        $buf = '';
        $buf = fread($i_handle, $chunksize);
        $bytes = fwrite($o_handle, $buf);

        if ($bytes == false)
				throw new Exception( "failed_to_write" );

        $cnt += $bytes;
        if ($cnt >= $length) break;

    }

    fclose( $i_handle );
    fclose( $o_handle );

		if ( !file_exists( $tmp_file ) )
		throw new Exception( "not_exist" );

		// save
		$saved = bof()->file->save( $tmp_file, array(
			"filename"      => $filename,
			"extension"     => $extension,
			"sub_directory" => $sub_directory,
		) );

		if ( !$saved )
		throw new Exception( "failed_to_save" );

		return $saved;

	}

}

?>
