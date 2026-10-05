<?php

if ( !defined( "bof_root" ) ) die;

class lyric_musixmatch extends bof_type_class {

	public $api_base = "https://api.musixmatch.com/ws/1.1/";

	protected $initiated = false;
	protected $key   = null;

	public function __construct(){
	}
	public function setup(){

		if ( $this->initiated )
		return true;

		$this->initiated = true;
		$this->key = $key = bof()->object->db_setting->get( "musixmatch_api_key" );

		if ( !$key )
		throw new Exception("No musixmatch key");

		return true;

	}
	public function request( $endpoint, $params = array(), $retrying = false, $config = [] ){

		if ( !$this->key )
		$this->setup();

		$params["apikey"] = $this->key;
		$params["format"] = "json";
		$params_string = $params ? http_build_query( $params ) : false;

		$full_url = $this->api_base . $endpoint . ( !empty( $params_string ) ? "?" . $params_string : "" );
		$exec_curl = bof()->curl->exe( array(
			"url" => $full_url,
			"cache_load" => true,
			"cache" => true,
		) );

		if ( $exec_curl["http_code"] != 200 )
		throw new Exception("Invalid HTTP code: {$exec_curl["http_code"]}");

		if ( empty( $exec_curl["data"]["message"]["body"] ) )
		throw new Exception("Invalid body structure");

		return $exec_curl["data"]["message"]["body"];

	}

	public function track_search( $params ){

		$exe_req = $this->request( "track.search", $params );
		return $exe_req ? $exe_req["track_list"] : $exe_req;

	}
	public function track_lyrics_get( $musixmatch_id ){

		$exe_req = $this->request( "track.lyrics.get", [ "track_id" => $musixmatch_id ] );
		return $exe_req ? $exe_req["lyrics"] : $exe_req;

	}
	public function _track_find( $artist, $track, $time_release ){

		$searchArray = array(
			"q_track" => $track,
			"q_artist" => $artist,
			"f_has_lyrics" => 1,
			"page_size" => 1
		);

		if ( $time_release ){
			$searchArray["f_track_release_group_first_release_date_min"] = date( "Ymd", strtotime( "-3 months", strtotime( $time_release ) ) );
			$searchArray["f_track_release_group_first_release_date_max"] = date( "Ymd", strtotime( "+3 months", strtotime( $time_release ) ) );
		}

		$results = $this->track_search( $searchArray );

		if ( empty( $results[0] ) )
		throw new Exception( "Nothing found" );

		return $results[0]["track"];

	}

	public function fetch( $item ){

		$cached = bof()->db->_select( array(
			"table" => "_c_m_tracks_musixmatch",
			"where" => array(
				[ "track_id", "=", $item["ID"] ]
			),
			"single" => true,
			"limit" => 1
		) );

		$musixmatch_id = null;
		$musixmatch_time = null;

		if ( $cached ){
			$musixmatch_id = $cached["musixmatch_id"];
			$musixmatch_time = $cached["musixmatch_time"];
		}

		if ( !$musixmatch_id ){

			if ( $musixmatch_time ? time() - strtotime( $musixmatch_time ) < 7*24*60*60 : false )
			return false;

			if ( $cached ){
				bof()->db->_update( array(
					"table" => "_c_m_tracks_musixmatch",
					"set" => array(
						[ "musixmatch_time", "now()", true ]
					),
					"where" => array(
						[ "track_id", "=", $item["ID"] ]
					)
				) );
			} else {
				bof()->db->_insert( array(
					"table" => "_c_m_tracks_musixmatch",
					"set" => array(
						[ "track_id", $item["ID"] ],
						[ "musixmatch_time", "now()", true ]
					)
				) );
			}

			try {

				$try_searching_musixmatch = $this->_track_find(
					$item["bof_dir_artist"]["name"],
					$item["title"],
					!empty($item["time_release"]) ? $item["time_release"] : null
				);

				$musixmatch_id = $try_searching_musixmatch["track_id"];

				bof()->db->_update( array(
					"table" => "_c_m_tracks_musixmatch",
					"set" => array(
						[ "musixmatch_id", $musixmatch_id ]
					),
					"where" => array(
						[ "track_id", "=", $item["ID"] ]
					)
				) );

			} catch (Exception $err) {}

			if ( empty( $musixmatch_id ) )
			return false;

		}

		try {
			$get_lyrics = $this->track_lyrics_get($musixmatch_id);
		} catch (Exception $err) {
			return false;
		}

		return array(
			"type" => "musixmatch",
			"lyrics" => $get_lyrics
		);

		
	}

}

?>
