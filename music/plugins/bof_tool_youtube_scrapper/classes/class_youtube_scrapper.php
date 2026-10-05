<?php

if ( !defined( "bof_root" ) ) die;

class youtube_scrapper {

	public function setup(){

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

		bof()->listen( "youtube", "clean_search_replace", function( $method_args, &$method_result, $loader ){

	    $query = $method_args[0];
			$results = $this->search( $query );
			return $results;

		} );

		bof()->listen( "youtube", "get_video_clean_replace", function( $method_args, &$method_result, $loader ){

			$ID = $method_args[0];
			return $this->getVideo( $ID, [] );

		} );

	}

	protected function setup_admin(){

		bof()->scrapper->setup_admin( array(
			"title" => "YouTube",
			"id" => "youtube"
		) );
		return;

		bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){

			$highlights = $loader->highlights->getData();
			$highlights[ "setting_links" ][ "items" ][ "tools_links" ][ "args" ][ "childs" ][] = array(
				"title" => "YouTube scrapper Proxy",
				"icon" => "cloud_sync",
				"link" => "yts_proxy"
			);
			$highlights[ "setting_links" ][ "items" ][ "general_links" ][ "args" ][ "childs" ][] = array(
				"title" => "YouTube scrapper Proxy",
				"icon" => "cloud_sync",
				"link" => "yts_proxy"
			);
			bof()->highlights->setData( $highlights );

		} );
		bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

			if ( is_array( $method_result ) ){
				$method_result[ "yts_proxy" ] = array(
					"title" => "YouTube Scrapper Proxy",
					"url" => "^yts_proxy$",
					"link" => "yts_proxy",
					"theme_file" => "parts/content_setting",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/setting/yts_proxy/",
							"key" => "setting"
						)
					),
					"__sb_family" => "setting",
				);
			}

		} );

		$setting = array(
	    "groups" => array(
				"setting" => array(
		      "title" => "YouTube Scrapper Proxy",
		      "icon" => "cloud_sync",
		      "inputs" => array(
						"yscup" => array(
              "title" => "Active",
							"tip" => "If left inactive, and you have `cURL proxy tool` installed and defined a proxy there, that proxy will be used. Otherwise no proxy will be used",
							"col_name" => "yscup",
              "input" => array(
                "name" => "yscup",
                "type" => "checkbox",
              ),
              "validator" => array(
                "boolean",
                array(
									"empty()",
									"int" => true
                )
              )
            ),
						"yscup_type" => array(
              "title" => "Type",
							"col_name" => "yscup_type",
              "input" => array(
                "name" => "yscup_type",
                "type" => "select_i",
								"value" => "http",
								"options" => array(
									[ "http", "HTTP" ],
									[ "socks4", "SOCKS4" ],
									[ "socks5", "SOCKS5" ],
								)
              ),
              "validator" => array(
                "in_array",
                array(
									"values" => [ "http", "socks4", "socks5" ]
                )
              )
            ),
						"yscup_addr" => array(
							"title" => "Address",
							"col_name" => "yscup_addr",
							"input" => array(
								"name" => "yscup_addr",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
						"yscup_port" => array(
							"title" => "Port",
							"col_name" => "yscup_port",
							"input" => array(
								"name" => "yscup_port",
								"type" => "digit",
							),
							"validator" => array(
								"int",
								array(
									"empty()"
								)
							)
						),
						"yscup_username" => array(
							"title" => "Username",
							"col_name" => "yscup_username",
							"input" => array(
								"name" => "yscup_username",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
						"yscup_password" => array(
							"title" => "Password",
							"col_name" => "yscup_password",
							"input" => array(
								"name" => "yscup_password",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
          )
		    ),
			),
			"action_btn_title" => "Save"
	  );

		bof()->bofAdmin->_add_setting( "yts_proxy", $setting );

	}

	protected function get_proxy( $curlArray ){

		if ( bof()->object->db_setting->get("youtube_s") ){

			$addr = bof()->object->db_setting->get("youtube_sp");

			if ( $addr ){
				$curlArray["proxy"] = $addr;
			}

		}

		return $curlArray;

	}

	public function search( $query ){

		$query = urlencode( $query );

		$get_results = bof()->curl->exe( $this->get_proxy( array(
			"url"   => "https://www.youtube.com/results?search_query={$query}&page=&utm_source=opensearch",
			"agent" => "chrome",
			"cache" => false,
			"cache_load" => false,
			"cache_save" => false,
			"type" => "scrap",
			"json" => false
		) ) );
		$result = null;

		try {
			$result = $this->__parse_html_search( $get_results["body"] );
		} catch ( Exception $err ){
			return [ false, $err->getMessage() ];
		}

		return [ true, $result ];

	}
	public function getVideo( $id, $args ){

		$get_results = bof()->curl->exe( $this->get_proxy( array(
			"url"   => "https://www.youtube.com/watch?v={$id}",
			"agent" => "chrome",
			"cache" => false,
			"cache_load" => false,
			"cache_save" => false,
			"type" => "scrap",
			"json" => false
		) ) );
		$result = null;

		try {
			$result = $this->__parse_html_video( $get_results["body"] );
		} catch ( Exception $err ){
			return [ false, $err->getMessage() ];
		}

		return [ true, $result ];

	}

	protected function __parse_html_video( $string ){

		$data = $this->__get_js_data_type_1( $string );

		if ( empty( $data ) )
		throw new Exception( "YouTube Unofficial: Request failed (1)" );

		if ( empty( $data["contents"]["twoColumnWatchNextResults"]["results"]["results"]["contents"] ) )
		throw new Exception( "not_found" );

		$_content = $data["contents"]["twoColumnWatchNextResults"]["results"]["results"]["contents"];
		$_id = $data["currentVideoEndpoint"]["watchEndpoint"]["videoId"];

		return array(
			"id" => $_id,
			"title" => !empty( $_content[0]["videoPrimaryInfoRenderer"]["title"]["runs"][0]["text"] ) ? $_content[0]["videoPrimaryInfoRenderer"]["title"]["runs"][0]["text"] : null,
			"date" => !empty( $_content[0]["videoPrimaryInfoRenderer"]["dateText"]["simpleText"] ) ? $_content[0]["videoPrimaryInfoRenderer"]["dateText"]["simpleText"] : null,
			"description" => !empty( $_content[1]["videoSecondaryInfoRenderer"]["description"]["runs"][0]["text"] ) ? $_content[1]["videoSecondaryInfoRenderer"]["description"]["runs"][0]["text"] : null,
			"channel_id" => !empty( $_content[1]["videoSecondaryInfoRenderer"]["owner"]["videoOwnerRenderer"]["title"]["runs"][0]["navigationEndpoint"]["browseEndpoint"]["browseId"] ) ? $_content[1]["videoSecondaryInfoRenderer"]["owner"]["videoOwnerRenderer"]["title"]["runs"][0]["navigationEndpoint"]["browseEndpoint"]["browseId"] : null,
			"channel_name" => !empty( $_content[1]["videoSecondaryInfoRenderer"]["owner"]["videoOwnerRenderer"]["title"]["runs"][0]["text"] ) ? $_content[1]["videoSecondaryInfoRenderer"]["owner"]["videoOwnerRenderer"]["title"]["runs"][0]["text"] : null,
			"covers" => json_decode( str_replace( "arsGpnTs4ZA", $_id, '{"default":{"url":"https://i.ytimg.com/vi/arsGpnTs4ZA/default.jpg","width":120,"height":90},"medium":{"url":"https://i.ytimg.com/vi/arsGpnTs4ZA/mqdefault.jpg","width":320,"height":180},"high":{"url":"https://i.ytimg.com/vi/arsGpnTs4ZA/hqdefault.jpg","width":480,"height":360},"standard":{"url":"https://i.ytimg.com/vi/arsGpnTs4ZA/sddefault.jpg","width":640,"height":480},"maxres":{"url":"https://i.ytimg.com/vi/arsGpnTs4ZA/maxresdefault.jpg","width":1280,"height":720}}' ), 1 ),
			"live" => !empty( $_content[0]["videoPrimaryInfoRenderer"]["viewCount"]["videoViewCountRenderer"]["isLive"] ),
			"tags" => null
		);

	}
	protected function __parse_html_search( $string ){

		$data = $this->__get_js_data_type_1( $string );

		if ( empty( $data ) )
		throw new Exception( "YouTube Unofficial: Request failed (1)" );

		$contents = [];
		foreach( $data["contents"]["twoColumnSearchResultsRenderer"]["primaryContents"]["sectionListRenderer"]["contents"][0]["itemSectionRenderer"]["contents"] as $content ){

			if ( empty( $content["videoRenderer"] ) ) continue;
			if ( count( $contents ) >= 6 ) continue;
			$content = $content["videoRenderer"];

			$duration_ = explode( ":", $content["lengthText"]["simpleText"] );
			if ( count( $duration_ ) == 3 ) $duration = ( $duration_[0]*60*60 ) + ( $duration_[1]*60 ) + ( $duration_[2] );
			elseif ( count( $duration_ ) == 2 ) $duration = ( $duration_[0]*60 ) + ( $duration_[1] );
			else $duration = $duration_[0];

			$contents[] = array(
				"id"       => $content["videoId"],
				"title"    => $content["title"]["runs"][0]["text"],
				"duration" => $duration,
			);

		}

		if ( empty( $contents ) )
		throw new Exception( "not_found" );

		return $contents;

	}

	protected function __get_js_data_type_1( $string ){

		if ( !$string )
		return false;

		foreach( explode( PHP_EOL, str_replace( [ "\n", "\r\n" ], PHP_EOL, $string ) ) as $__l ){

			if ( !preg_match( "/ytInitialData/", $__l ) ) continue;

			$var_data = substr( $__l, strpos( $__l, "ytInitialData" ) );
			if ( substr( $var_data, 0, strlen( "ytInitialData = " ) ) != "ytInitialData = " ) continue;

			$string_data = substr( $var_data, strlen( "ytInitialData = " ) );
			$string_data = explode( ';</script>', $string_data );
			$string_data = reset( $string_data );

			$maybe_data = $this->__try_to_extract_full_json( $string_data );
			if ( $maybe_data ) return $maybe_data;

		}

		return false;

	}
	protected function __try_to_extract_full_json( $string ){

		$string = trim( $string );
		$by_ends = explode( "}", $string );

		foreach( array_reverse( $by_ends, true ) as $i => $be ){

			$by_end_string = substr( $string, 0, strlen( $string ) - strlen( $be ) );
			$by_end_string_decoded = false;

			try {
				$by_end_string_decoded = json_decode( $by_end_string, true );
			} catch( Exception $err ){}

			if ( json_last_error() === JSON_ERROR_NONE ){
				return $by_end_string_decoded;
			}

		}

		return false;

	}


}

?>
