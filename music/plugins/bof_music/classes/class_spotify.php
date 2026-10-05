<?php

if ( !defined( "bof_root" ) ) die;

class spotify extends bof_type_class {

	public $api_base = "https://api.spotify.com/v1/";

	protected $initiated = false;
	protected $client_id = null;
	protected $client_key = null;
	protected $token = null;

	protected $config = array(
		"cache" => true,
		"cache_load" => true,
		"cache_age" => 72,
		"cache_update" => true,
		"cache_reset" => false,
		"req_count" => 0,
		"req_interval" => 0,
		"bof_para" => []
	);

	public function setup(){

		return bof()->spotify->set_configs();

	}
	public function set_configs(){

		if ( $this->initiated )
		return true;

		$this->initiated = true;
		$this->client_id  = $client_id  = bof()->object->db_setting->get( "spotify_client_id" );
		$this->client_key = $client_key = bof()->object->db_setting->get( "spotify_client_key" );
		$this->token = bof()->object->db_setting->get( "spotify_token" );

		if ( !$client_id || !$client_key )
		return false;

		return true;

	}
	public function set_config( $key, $val ){
		$this->config[ $key ] = $val;
	}

	public function get_keys(){

		return array(
			"client_id" => $this->client_id,
			"client_key" => $this->client_key,
			"token" =>  $this->token
		);

	}

	public function set_token( $token ){
		$this->token = $token;
		bof()->object->db_setting->set( "spotify_token", $token, "string" );
	}

	public function check_result( $data ){
	}

	public function request( $endpoint, $params = array(), $retrying = false, $config = [] ){

		$keys = bof()->spotify->get_keys();

		if ( !$keys["client_id"] || !$keys["client_key"] )
		return false;

		extract( $this->config );
		if ( $config )
		extract( $config );

		$headers = array();
		$params_string = $params ? http_build_query( $params ) : false;

		if ( !empty( $keys["token"] ) )
		$headers = array( "Authorization: Bearer " . $keys["token"] );

		$full_url = $this->api_base . $endpoint . ( !empty( $params_string ) ? "?" . $params_string : "" );
		$exec_curl = bof()->curl->exe( array(
			"url"          => $full_url,
			"hook"         => md5( $full_url ),
			"headers"      => $headers,
			"cache"        => $cache,
			"cache_load"   => $retrying ? false : $cache_load,
			"cache_age"    => $cache_age,
			"proxy" => !empty( $keys["proxy"] ) ? $keys["proxy"] : null
		) );

		$this->config["req_count"]++;

		// Outdated token
		if (  ( !$retrying && !empty( $exec_curl["data"]["error"] ) ) ? $exec_curl["data"]["error"]["status"] == 401 : false ){

			$exec_curl_new_token = bof()->curl->exe( array(
				"url"        => "https://accounts.spotify.com/api/token",
				"posts"      => "grant_type=client_credentials",
				"headers"    => [ "Authorization: Basic " . base64_encode( $keys["client_id"] . ":" . $keys["client_key"] ) ],
				"cache_load" => false,
				"cache" => $cache,
				"proxy" => !empty( $keys["proxy"] ) ? $keys["proxy"] : null
			) );

			if ( empty( $exec_curl_new_token["data"]["access_token"] ) ){
				bof()->spotify->check_result( $exec_curl );
				return false;
			}

			$token = $exec_curl_new_token["data"]["access_token"];
			bof()->spotify->set_token( $token );
			return bof()->spotify->request( $endpoint, $params, true, $config );

		}

		bof()->spotify->check_result( $exec_curl );

		// Reached maximum request limit
		if ( $exec_curl["http_code"] == 429 ){
			$ff = bof()->general->get_full_fall();
			bof()->general->set_full_fall(true);
			fall( "Spotify API limit reached" );
			bof()->general->set_full_fall($ff);
		}

		if ( $this->config["req_interval"] )
		usleep( $this->config["req_interval"] * 1000000 );

		if ( $this->config["cache_reset"] ? $this->config["req_count"] > 10 : false ){
			$this->config["req_count"] = 0;
			bof()->db->reset_cache();
		}

		if ( !empty( $bof_para ) && !empty( $exec_curl["data"]["items"] ) ? $bof_para["type"] == "album_track_more" : false ){
			foreach( $exec_curl["data"]["items"] as &$_i ){
				$_i["album"]["name"] = $bof_para["albumName"];
				$_i["album"]["bof_object_id"] = $bof_para["albumID"];
				$_i["album"]["bof_object_album_artist_id"] = $bof_para["albumArtistID"];
			}
			unset( $_i );
		}

		return $exec_curl["data"];

	}

	public function search( $query, $types, $args=[] ){

		$limit = 10;
		$offset = 0;
		extract( $args );

		$sArray = array(
			"q" => $query,
			"limit" => $limit,
			"offset" => $offset
		);

		if ( $types )
		$sArray["type"] = is_array( $types ) ? implode( ",", $types ) : $types;
	    else
		$sArray["type"] = "artist,album,track";

		$exe_req = bof()->spotify->request( "search", $sArray );

		return $exe_req;

	}

	public function album( $ID ){

		$exe_req = bof()->spotify->request( "albums/{$ID}" );

		if ( $exe_req && !empty( $exe_req["total_tracks"] ) ? $exe_req["total_tracks"] > 50 : false ){
			$other_tracks = bof()->spotify->album_tracks( $ID, $exe_req["bof_object_id"], $exe_req["bof_object_album_artist_id"], $exe_req["name"] );
			if ( !empty( $other_tracks ) )
			$exe_req["tracks"]["items"] = array_merge( $exe_req["tracks"]["items"], $other_tracks );
		}

		return $exe_req;

	}
	public function album_tracks( $ID, $albumID, $albumArtistID, $albumName ){

		$limit = 50;
		$data = [];
		$has_next = true;
		$offset = 50;

		while( $has_next ){

			$exe_req = bof()->spotify->request( "albums/{$ID}/tracks", array(
				"limit" => $limit,
				"offset" => $offset,
			), false, array(
				"bof_para" => array(
					"type" => "album_track_more",
					"albumID" => $albumID,
					"albumArtistID" => $albumArtistID,
					"albumName" => $albumName
				)
			) );

			if ( empty( $exe_req["next"] ) )
			$has_next = false;

			if ( !empty( $exe_req["items"] ) )
			$data = array_merge( $data, $exe_req["items"] );

			$offset += $limit;

		}

		return $data;

	}

	public function artist( $ID ){
		$exe_req = bof()->spotify->request( "artists/{$ID}" );
		return $exe_req;
	}
	public function artist_related( $ID ){
		$exe_req = bof()->spotify->request( "artists/{$ID}/related-artists" );
		return $exe_req;
	}
	public function artist_albums( $ID, $albumTypes=[ "album", "single", "compilation" ], $recursive=false ){

		// TODO
		// $albumTypes=[ "album", "single", "appears_on", "compilation" ]

		$limit = 50;

		if ( $recursive ){

			$data = [];
			$has_next = true;
			$offset = 0;

			while( $has_next ){

				$exe_req = bof()->spotify->request( "artists/{$ID}/albums", array(
					"include_groups" => implode( ",", $albumTypes ),
					"limit" => $limit,
					"offset" => $offset
				) );

				if ( empty( $exe_req["next"] ) )
				$has_next = false;

				$data[] = $exe_req;
				$offset += $limit;

			}

		}
		else {

			$data = $exe_req = bof()->spotify->request( "artists/{$ID}/albums", array(
				"include_groups" => implode( ",", $albumTypes ),
				"limit" => $limit
			) );

		}

		return $data;

	}
	public function artist_top_tracks( $ID, $region="us" ){
		$exe_req = bof()->spotify->request( "artists/{$ID}/top-tracks", [ "country" => $region ] );
		return $exe_req;
	}

	public function playlist( $ID ){

		$exe_req = bof()->spotify->request( "playlists/{$ID}" );
		return $exe_req;

	}
	public function playlist_items( $ID, $recursive=false ){

		$limit = 25;

		if ( $recursive ){

			$data = [];
			$has_next = true;
			$offset = 0;

			while( $has_next ){

				$exe_req = bof()->spotify->request( "playlists/{$ID}/tracks", array(
					"limit" => $limit,
					"offset" => $offset
				) );

				if ( empty( $exe_req["next"] ) )
				$has_next = false;

				$data[] = $exe_req;
				$offset += $limit;

				bof()->db->reset_cache();

			}

		}
		else {

			$data = bof()->spotify->request( "playlists/{$ID}/tracks", array(
				"limit" => $limit
			) );

		}

		return $data;

	}

	public function track( $ID ){

		$exe_req = bof()->spotify->request( "tracks/{$ID}" );
		return $exe_req;

	}

	public function category_playlists( $ID, $recursive=false ){

		$limit = 50;
		$maxRec = $recursive === true ? 5 : $recursive;

		if ( $recursive ){

			$data = [];
			$has_next = true;
			$offset = 0;

			while( $has_next && ( $offset ? $offset/$limit <= $maxRec : true ) ){

				$exe_req = bof()->spotify->request( "browse/categories/{$ID}/playlists", array(
					"limit" => $limit,
					"offset" => $offset
				) );

				if ( empty( $exe_req["next"] ) )
				$has_next = false;

				$data[] = $exe_req;
				$offset += $limit;

			}

		}
		else {

			$data = bof()->spotify->request( "browse/categories/{$ID}/playlists", array(
				"limit" => $limit
			) );

		}

		return $data;

	}
	public function user_playlists( $ID, $recursive=false ){

		$limit = 50;
		$maxRec = $recursive === true ? 50 : $recursive;

		if ( $recursive ){

			$data = [];
			$has_next = true;
			$offset = 0;

			while( $has_next && ( $offset ? $offset/$limit <= $maxRec : true ) ){

				$exe_req = bof()->spotify->request( "users/{$ID}/playlists", array(
					"limit" => $limit,
					"offset" => $offset
				) );

				if ( empty( $exe_req["next"] ) )
				$has_next = false;

				$data[] = $exe_req;
				$offset += $limit;

			}

		}
		else {

			$data = bof()->spotify->request( "users/{$ID}/playlists", array(
				"limit" => $limit
			) );

		}

		return $data;

	}

	public function user( $username ){
		$exe_req = bof()->spotify->request( "users/{$username}" );
		return $exe_req;
	}
	public function browse_categories( $country ){
		$exe_req = bof()->spotify->request( "browse/categories", array(
			"limit" => 50,
			"country" => $country
		) );
		return $exe_req;
	}

}

?>
