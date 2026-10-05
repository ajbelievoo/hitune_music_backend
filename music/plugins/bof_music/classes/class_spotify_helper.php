<?php

if ( !defined( "bof_root" ) ) die;

class spotify_helper extends bof_type_class {

	protected $die_on_issue = true;
	protected $__record = false;
	public $__record_data = [
		"sync_genres" => true,
		"create_album_get_artist_for_genres" => true,
		"create_track_get_artist_for_genres" => false,
		"update_album_get_artist_for_genres" => false
	];

	public function __construct(){

		bof()->listen( "spotify", "request_after", function( $args, &$result, $loader ){
			try {
			  $this->__parse_request_result_array( $result );
		  } catch( Exception $err ){
				if ( $this->die_on_issue ){
					var_dump( $err->getMessage() );
					die;
				}
				throw new Exception( $err->getMessage() );
			}
		} );

	}
	public function set_die_on_issue( $value ){
		$this->die_on_issue = !empty( $value );
	}
	public function setup(){
		return bof()->spotify->setup();
	}
	public function record( $action, $data=null ){
		$this->__record = $action === true;
		if ( $data )
		$this->set_record_data( $data );
	}
	public function set_record_data( $data ){
		if ( !$data ) return;
		foreach( $data as $_k => $_v )
		$this->set_a_record_data( $_k, $_v );
	}
	public function set_a_record_data( $var, $val ){
		$this->__record_data[ $var ] = $val;
	}

	protected function __simplfy_albums( &$arr ){

		if ( !is_array( $arr ) ) return;
		if ( empty( $arr["type"] ) ) return;
		if ( $arr["type"] != "album" ) return;

		if ( !empty( $arr["tracks"] ) ){

			$_arr = $arr;
			unset( $_arr["tracks"] );

			foreach( $arr["tracks"]["items"] as $_i => $_track ){
				$arr["tracks"]["items"][$_i]["album"] = $_arr;
			}

		}

	}
	protected function __simplfy_albums_for_tracks( $arr ){

		if ( !empty( $arr["album"]["tracks"] ) ){
			unset( $arr["album"]["tracks"] );
		}

		if ( !empty( $arr["album"]["artists"] ) ){
			unset( $arr["album"]["artists"] );
		}

		return $arr;

	}
	protected function __parse_request_result_array( &$arr, $mother=true ){

		if ( !$this->__record )
		return;

		if ( !is_array( $arr ) )
		return false;

		if ( !empty( $arr["bof"] ) )
		return false;

		if ( $mother !== true ? $mother >= 7 : false )
		return false;

		if ( $mother === true )
		$this->__simplfy_albums( $arr );

		$parsed  = null;
		$type    = null;
		$records = array(
			"artists" => [],
			"tracks"  => [],
			"albums"  => []
		);

		foreach( $arr as &$_arr ){
			$child_records = $this->__parse_request_result_array( $_arr, $mother === true ? 0 : $mother + 1 );
			if ( !$child_records ) continue;
			foreach( $records as $_gk => &$gv ){
				$gv = array_merge( $gv, $child_records[ $_gk ] );
			}
		}

		if ( !empty( $arr["type"] ) ? $arr["type"] == "artist" : false ){
			$parsed = $this->__parse_artist( $arr );
			$type = "artist";
		}

		if ( !empty( $arr["type"] ) ? $arr["type"] == "album" : false ){
			$parsed = $this->__parse_album( $arr );
			$type = "album";
		}

		if ( !empty( $arr["type"] ) ? $arr["type"] == "playlist" : false ){
			$parsed = $this->__parse_playlist( $arr );
			$type = "playlist";
		}

		if ( !empty( $arr["type"] ) ? $arr["type"] == "track" : false ){
			$_simplify = $this->__simplfy_albums_for_tracks( $arr, true );
			$parsed = $this->__parse_track( $_simplify );
			$type = "track";
		}

		if ( $parsed && $type ){
			$arr["bof"] = "BusyOwlFramework";
			$arr["bof_object_type"] = $type;
			$arr["bof_object_id"] = $parsed;
			$records[ "{$type}s" ][] = $parsed;
		}

		if ( $mother === true )
		$arr["bof_records"] = $records;

		return $records;

	}
	protected function __parse_artist( &$arr ){

		if (
			empty( $arr["name"] ) ||
		  empty( $arr["id"] )
		) return false;

		$code = bof()->general->make_code( $arr["name"] );
		if ( empty( $code ) ){
			$code = md5( $arr["id"] );
		}
		$whereArray = array(
			"code" => $code,
			"spotify_id" => $arr["id"],
		);

		$insertArray = [];

		if ( !bof()->object->m_artist->select_m( $whereArray ) ){
			$insertArray = array(
				"name" => $arr["name"],
				"hash" => bof()->object->m_artist->get_free_hash(),
				"code" => $code,
				"seo_url"  => bof()->object->m_artist->get_free_url( $arr["name"] ),
				"spotify_id" => $arr["id"],
			);
		}

		$data = [];

		if ( isset( $arr["popularity"] ) ? ( is_int( $arr["popularity"] ) || is_numeric( $arr["popularity"] ) ) : false ) $data[ "spotify_popularity" ] = $arr[ "popularity" ];
		if ( !empty( $arr["images"] ) ) $data[ "spotify_cover" ] = json_encode( $arr["images"] );
		if ( !empty( $arr["genres"] ) && !empty( $this->__record_data["sync_genres"] ) ) $data[ "genre_string_array" ] = $arr["genres"];
		if ( isset( $arr["followers"]["total"] ) ) $data["spotify_followers"] = $arr["followers"]["total"];

		try {
			$artist_id = bof()->object->m_artist->create( $whereArray, array_merge( $insertArray, $data ), $data );
		} catch( Exception $err ){
			$artist_id = false;
		}

		return $artist_id;

	}
	protected function __parse_album( &$arr ){

		if (
			empty( $arr["name"] ) ||
			empty( $arr["id"] ) ||
			empty( $arr["album_type"] ) ||
			empty( $arr["artists"][0]["bof_object_id"] )
		) return false;

		$artist = $arr["artists"][0];
		$code = bof()->general->make_code( [ $artist["name"], $arr["name"] . ( ($arr["album_type"] == "single"||$arr["album_type"] == "ep") ? "_single" : "" ) ] );
		$whereArray = array(
			"code" => $code,
			"spotify_id" => $arr["id"]
		);

		$insertArray = [];
		$data = [];

		if ( !bof()->object->m_album->select_m( $whereArray ) ){

			// get artist detail from Spotify ( or db ) to get genres and use them for album
			if ( !empty( $this->__record_data["create_album_get_artist_for_genres"] ) && !empty( $this->__record_data["sync_genres"] ) ){

				$get_artist_genres = bof()->object->m_artist->select(array(
					"spotify_id" => $artist["id"],
				),array(
					"_eq" => [ "genres" => [ "cleanest" => "ID" ] ]
				));

				if ( !empty( $get_artist_genres ) )
				$artist_genres = $get_artist_genres["bof_rel_genres"];

			}

			$insertArray = array(
				"title" => $arr["name"],
				"hash" => bof()->object->m_album->get_free_hash(),
				"code" => $code,
				"seo_url"  => bof()->object->m_album->get_free_url( $artist["name"] . "-" . $arr["name"] ),
				"type" => ( $arr["album_type"] == "album" ) ? "studio" : ( $arr["album_type"] == "ep" ? "single" : $arr["album_type"] ),
				"artist_id" => $artist["bof_object_id"],
				"spotify_id" => $arr["id"],
				"genre_ids" => !empty( $artist_genres ) ? $artist_genres : false
			);

		}
		else {

			if ( !empty( $this->__record_data["update_album_get_artist_for_genres"] ) && !empty( $this->__record_data["sync_genres"] ) ){

				$get_artist_genres = bof()->object->m_artist->select(array(
					"spotify_id" => $artist["id"],
				),array(
					"_eq" => [ "genres" => [ "cleanest" => "ID" ] ]
				));

				if ( !empty( $get_artist_genres["bof_rel_genres"] ) ){
					$artist_genres = $get_artist_genres["bof_rel_genres"];
					$data["genre_ids"] = $artist_genres;
				}

			}

		}

		if ( !empty( $arr["images"] ) ) $data[ "spotify_cover" ] = json_encode( $arr["images"] );
		if ( !empty( $arr["release_date"] ) ) $data[ "time_release" ] = bof()->general->strtotime( $arr["release_date"] ) ? bof()->general->strtotime( $arr["release_date"] )[0] : null;
		if ( !empty( $arr["restrictions"] ) ? $arr["restrictions"]["reason"] == "explicit" : false ) $data["explicit"] = 1;
		if ( !empty( $arr["popularity"] ) ) $data["spotify_popularity"] = $arr["popularity"];

		try {
			$album_id = bof()->object->m_album->create( $whereArray, array_merge( $insertArray, $data ), $data );
		} catch( Exception $err ){
			$album_id = false;
		}

		if ( empty( $album_id ) )
		return false;

		$arr["bof_object_album_artist_id"] = $artist["bof_object_id"];

		if ( !empty( $insertArray ) ){
			if ( count( $arr["artists"] ) > 1 ){
				$_featured_artists_ids = [];
				foreach( array_slice( $arr["artists"], 1 ) as $_featured_artist ){
					if ( empty( $_featured_artist["bof_object_id"] ) ) continue;
					$_featured_artists_ids[] = $_featured_artist["bof_object_id"];
				}
				if ( $_featured_artists_ids ){
					bof()->object->m_album->make_rels( $album_id, $_featured_artists_ids, "ft_artist" );
				}
			}
		}

		return $album_id;

	}
	protected function __parse_playlist( &$arr ){

		if (
			empty( $arr["name"] ) ||
			empty( $arr["id"] )
		) return false;

		return $arr["id"];

	}
	protected function __parse_track( &$arr ){

		if (
			empty( $arr["name"] ) ||
			empty( $arr["id"] ) ||
			empty( $arr["album"]["bof_object_id"] ) ||
			empty( $arr["album"]["bof_object_album_artist_id"] ) ||
			empty( $arr["duration_ms"] )
		){
			return false;
		}

		$album = $arr["album"];
		$album_id = $album["bof_object_id"];
		$album_artist_id = $album["bof_object_album_artist_id"];
		$artist = $arr["artists"][0];
		$artist_id = empty( $arr["artists"][0]["bof_object_id"] ) ? $album_artist_id : $arr["artists"][0]["bof_object_id"];

		$code = bof()->general->make_code( [ $artist["name"], ( $album["name"].( ($album["album_type"]=="single"||$album["album_type"]=="ep") ? "_s" : "" ) ), $arr["name"] ] );
		$whereArray = array(
			"code" => $code,
			"spotify_id" => $arr["id"]
		);

		$insertArray = [];
		if ( !bof()->object->m_track->select_m( $whereArray ) ){

			// get artist detail from Spotify ( or db ) to get genres and use them for album
			if ( !empty( $this->__record_data["create_track_get_artist_for_genres"] ) && !empty( $this->__record_data["sync_genres"] ) ){

				$get_artist_genres = bof()->object->m_artist->select(array(
					"spotify_id" => $artist["id"]
				),array(
					"_eq" => [ "genres" => [ "cleanest" => "ID" ] ]
				));

				if ( !empty( $get_artist_genres["bof_rel_genres"] ) )
				$artist_genres = $get_artist_genres["bof_rel_genres"];

			}

			$insertArray = array(
				"title" => $arr["name"],
				"hash" => bof()->object->m_track->get_free_hash(),
				"code" => $code,
				"seo_url"  => bof()->object->m_track->get_free_url( $artist["name"] . "-" . $album["name"] . "-" . $arr["name"] ),
				"artist_id" => $artist_id,
				"album_id" => $album_id,
				"album_artist_id" => $album_artist_id,
				"spotify_id" => $arr["id"],
				"duration" => round( $arr["duration_ms"] / 1000 ),
				"genre_ids" => !empty( $artist_genres ) ? $artist_genres : false
			);

		}

		$data = [];
		if ( !empty( $arr["album"]["images"] ) ) $data["spotify_cover"] = json_encode( $arr["album"]["images"] );
		if ( !empty( $arr["album"]["release_date"] ) ) $data[ "time_release" ] = bof()->general->strtotime( $arr["album"]["release_date"] ) ? bof()->general->strtotime( $arr["album"]["release_date"] )[0] : null;
		if ( !empty( $arr["explicit"] ) ) $data["explicit"] = 1;
		if ( !empty( $arr["popularity"] ) ) $data["spotify_popularity"] = $arr["popularity"];
		if ( !empty( $arr["disc_number"] ) ) $data["album_cd"] = $arr["disc_number"];
		if ( !empty( $arr["track_number"] ) ) $data["album_index"] = $arr["track_number"];

		try {
			$track_id = bof()->object->m_track->create( $whereArray, array_merge( $insertArray, $data ), $data, false, false );
		} catch( Exception $err ){
			$track_id = false;
		}

		if ( empty( $track_id ) )
		return false;

		$arr["bof_object_track_artist_id"] = $artist_id;
		$arr["bof_object_track_album_id"] = $album_id;
		$arr["bof_object_track_album_artist_id"] = $album_artist_id;

		if ( !empty( $insertArray ) ){
			if ( count( $arr["artists"] ) > 1 ){
				$_featured_artists_ids = [];
				foreach( array_slice( $arr["artists"], 1 ) as $_featured_artist ){
					if ( empty( $_featured_artist["bof_object_id"] ) ) continue;
					$_featured_artists_ids[] = $_featured_artist["bof_object_id"];
				}
				if ( $_featured_artists_ids ){
					bof()->object->m_track->make_rels( $track_id, $_featured_artists_ids, "ft_artist" );
				}
			}
		}

		return $track_id;

	}

	public function get_track( $id, $jobData=[] ){

		if ( !$this->setup() )
		return false;

		$check = true;
		$is_stored = false;
		extract( $jobData );

		if ( $check ){

			$is_stored = $is_stored ? $is_stored : bof()->object->m_track->select(
				array(
					"spotify_id" => $id
				),
				array(
					"cache" => false,
					"cache_load_rt" => false,
					"bof_time" => true
				)
			);

			if ( $is_stored ){
				$check_self = $is_stored["bof_time_spotify_seconds_ago"] ? ( 3*24*60*60 ) < $is_stored["bof_time_spotify_seconds_ago"] : true;
			}

		}

		bof()->music->_cli( "Spotify_helper: Get `Track` id:`{$id}`".
		" self:" . ( ( !$is_stored || !empty( $check_self ) ) ? "Y" : "N" ) );

		// Get track detail from spotify
		if ( !$is_stored || !empty( $check_self ) ){

			$track = bof()->spotify->track( $id );

			if ( !$track ? true : empty( $track["bof_records"]["tracks"][0] ) )
			return "noRecord?";

			bof()->object->m_track->mark_time( [ "spotify_id" => $id ], "spotify", false );

		}

		$track_local_id = $is_stored ? $is_stored["ID"] : $track["bof_records"]["tracks"][0];

		return $track_local_id;

	}
	public function get_artist( $id, $jobData=[] ){

		if ( !$this->setup() )
		return false;

		$artist_related = false;
		$artist_albums = false;
		$artist_albums_singular = true;
		$artist_tracks = false;
		$check = true;
		$is_stored = false;
		extract( $jobData );

		if ( $check ){

			$is_stored = $is_stored ? $is_stored : bof()->object->m_artist->select(
				array(
					"spotify_id" => $id
				),
				array(
					"cache" => false,
					"cache_load_rt" => false,
					"bof_time" => true
				)
			);

			if ( $is_stored ){
				$check_self             = $is_stored["bof_time_spotify_seconds_ago"]             ? ( 3*24*60*60 )  < $is_stored["bof_time_spotify_seconds_ago"]             : true;
				$check_albums           = $is_stored["bof_time_spotify_albums_seconds_ago"]      ? ( 3*24*60*60 )  < $is_stored["bof_time_spotify_albums_seconds_ago"]      : true;
				$check_albums_singular  = $is_stored["bof_time_spotify_discography_seconds_ago"] ? ( 30*24*60*60 ) < $is_stored["bof_time_spotify_discography_seconds_ago"] : true;
				$check_tracks           = $is_stored["bof_time_spotify_tracks_seconds_ago"]      ? ( 3*24*60*60 )  < $is_stored["bof_time_spotify_tracks_seconds_ago"]      : true;
				$check_related          = $is_stored["bof_time_spotify_related_seconds_ago"]     ? ( 14*24*60*60 )  < $is_stored["bof_time_spotify_related_seconds_ago"]     : true;
			}

		}

		bof()->music->_cli( "Spotify_helper: Get `Artist` id:`{$id}`".
		" self:" . ( ( !$is_stored || !empty( $check_self ) ) ? "Y" : "N" ) .
		" related_artists:" . ( ( $artist_related && ( !$is_stored || !empty( $check_related ) ) ) ? "Y" : "N" ) .
		" albums:" . ( ( $artist_albums && ( !$is_stored || !empty( $check_albums ) ) ) ? "Y" : "N"  ) .
		" top_tracks:" . ( ( $artist_tracks && ( !$is_stored || !empty( $check_tracks ) ) ) ? "Y" : "N" ) );

		// Get artist detail from spotify
		if ( !$is_stored || !empty( $check_self ) ){

			$artist = bof()->spotify->artist( $id );

			if ( !$artist ? true : empty( $artist["bof_records"]["artists"][0] ) )
			return "noRecord?";

			bof()->object->m_artist->mark_time( [ "spotify_id" => $id ], "spotify", false );

		}

		$artist_local_id = $is_stored ? $is_stored["ID"] : $artist["bof_records"]["artists"][0];

		// Get artist related artists
		if ( $artist_related && ( !$is_stored || !empty( $check_related ) ) ){

			$related = bof()->spotify->artist_related( $id );
			if ( !empty( $related["bof_records"]["artists"] ) ){
				bof()->object->m_artist->make_rels( $artist_local_id, $related["bof_records"]["artists"], "sim" );
			}

			bof()->object->m_artist->mark_time( [ "spotify_id" => $id ], "spotify_related", false );

		}

		// Get artist albums
		if ( $artist_albums && ( !$is_stored || !empty( $check_albums ) || ( !empty( $check_albums_singular ) && $artist_albums_singular ) ) ){

			$albums_pages = bof()->spotify->artist_albums( $id, [ "album", "single", "compilation" ], true );
			if ( !empty( $albums_pages ) ){
				foreach( $albums_pages as $albums ){
					if ( !empty( $albums["items"] ) ){
						foreach( $albums["items"] as $album ){
							if ( $artist_albums_singular ){
								bof()->spotify_helper->get_album( $album["id"] );
							}
						}
					}
				}
			}

			bof()->object->m_artist->mark_time( [ "spotify_id" => $id ], "spotify_albums", false );

			if ( $artist_albums_singular )
			bof()->object->m_artist->mark_time( [ "spotify_id" => $id ], "spotify_discography", false );

		}

		// Get artist tracks
		if ( $artist_tracks && ( !$is_stored || !empty( $check_tracks ) ) ){

			$topTracks = bof()->spotify->artist_top_tracks( $id );
			bof()->object->m_artist->mark_time( [ "spotify_id" => $id ], "spotify_tracks", false );

		}

		return $artist_local_id;

	}
	public function get_album( $id, $jobData=[] ){

		if ( !$this->setup() )
		return false;

		$check = true;
		extract( $jobData );
		$is_stored = false;

		if ( $check ){
			$is_stored = bof()->object->m_album->select(
				array(
					"spotify_id" => $id
				),
				array(
					"bof_time" => true
				)
			);
			if ( $is_stored ){
				$check_self = !empty( $is_stored["bof_time_spotify_seconds_ago"] ) ? ( 3*24*60*60 ) < $is_stored["bof_time_spotify_seconds_ago"] : true;
			}
		}

		bof()->music->_cli( "Spotify_helper: Get `Album` id:`{$id}`".
		" self:" . ( ( !$is_stored || !empty( $check_self ) ) ? "Y" : "N" ) );

		if ( !$is_stored || !empty( $check_self ) ){

			$album = bof()->spotify->album( $id );

			if ( !$album ? true : empty( $album["bof_records"]["albums"][0] ) )
			return false;

			bof()->object->m_album->mark_time( [ "spotify_id" => $id ], "spotify", false );

		}

		$album_local_id = $is_stored ? $is_stored["ID"] : $album["bof_records"]["albums"][0];

		return $album_local_id;

	}
	public function get_playlist( $id, $jobData=[] ){

		if ( !$this->setup() )
		return false;

		$playlist_create = false;
		$playlist_create_u = false;
		$local_id = false;
		extract( $jobData );

		$check_self = true;

		$check_cache = bof()->db->_select(array(
			"table" => "_c_m_cronjobs_spotify",
			"where" => array(
				[ "cron_id", "=", "0" ],
				[ "spotify_id", "=", "pl{$id}" ],
			),
			"single" => true,
			"limit" => 1
		));

		if ( $check_cache ){
			if ( time() - strtotime( $check_cache["time_check"] ) < (24*60*60) ){
				$check_self = false;
			}
		}

		bof()->music->_cli( "Spotify_helper: Get `Playlist` id:`{$id}`".
		" self:" . ( ( !empty( $check_self ) ) ? "Y" : "N" ) );

		// Get playlist detail from spotify
		if ( !empty( $check_self ) ){

			$playlist = bof()->spotify->playlist( $id );

			if ( !$playlist ? true : empty( $playlist["bof_records"]["playlists"][0] ) )
			return "noRecord?";

			$tracks = bof()->spotify->playlist_items( $id, true );
			$_tracks = [];
			if ( $tracks ){
				foreach( array_reverse( $tracks ) as $tracks_page ){
					if ( empty( $tracks_page["items"] ) ) continue;
					foreach( $tracks_page["items"] as $track ){
						if ( empty( $track["track"] ) ) continue;
						$_track = $track["track"];
						if ( empty( $_track["bof_object_type"] ) || empty( $_track["bof_object_id"] ) ? true : $_track["bof_object_type"] != "track" ) continue;
						$_tracks[] = $_track["bof_object_id"];
					}
				}
			}

			if ( $playlist_create ){

				if ( !$local_id ){

					$by_other = bof()->object->ugc_playlist->select(
						array(
							"spotify_id" => $id
						),
						array(
							"clean" => false,
							"single" => true,
							"limit" => 1
						)
					);

					if ( $by_other ){

						$local_id = $by_other["ID"];

					}
					else {

						try {
							$create_local_playlist = bof()->object->ugc_playlist->create(
								array(),
								array(
									"user_id" => !empty( $playlist_create_u ) ? $playlist_create_u : ( defined("spotify_playlist_user_id") ? spotify_playlist_user_id : 1 ),
									"name" => $playlist["name"],
									"object_type" => "m_track",
									"spotify_id" => $id,
									"extra_data" => json_encode( array(
										"spotify_id" => $id,
										"spotify_images" => !empty( $playlist["images"] ) ? $playlist["images"] : []
									) )
								),
								array(
								)
							);
						} catch( Exception $err ){
							$create_local_playlist = false;
						}

						if ( !empty( $create_local_playlist ) ){
							$local_id = $create_local_playlist;
						}

					}

				}

				if ( !empty( $local_id ) ){
					bof()->object->ugc_playlist->update(
						array(
							"ID" => $local_id
						),
						array(
							"time_update" => bof()->general->mysql_timestamp()
						)
					);
					bof()->object->ugc_property->delete(
						array(
							"type" => "playlist",
							"object_name" => "m_track",
							"related_object_name" => "ugc_playlist",
							"related_object_id" => $local_id
						)
					);
					if ( !empty( $_tracks ) ){
						foreach( $_tracks as $i => $_track ){
							bof()->object->ugc_property->insert(
								array(
									"user_id" => !empty( $playlist_create_u ) ? $playlist_create_u : ( defined("spotify_playlist_user_id") ? spotify_playlist_user_id : 1 ),
									"type" => "playlist",
									"object_name" => "m_track",
									"object_id" => $_track,
									"related_object_name" => "ugc_playlist",
									"related_object_id" => $local_id,
									"i" => $i
								)
							);
						}
					}
				}

			}

			if ( $check_cache ){
				bof()->db->_update(array(
					"table" => "_c_m_cronjobs_spotify",
					"where" => array(
						[ "cron_id", "=", "0" ] ,
						[ "spotify_id", "=", "pl{$id}" ],
					),
					"set" => array(
						[ "time_check", "now()", true ]
					)
				));
			} else {
				bof()->db->_insert(array(
					"table" => "_c_m_cronjobs_spotify",
					"set" => array(
						[ "cron_id", "0" ],
						[ "spotify_id", "pl{$id}" ],
						[ "time_check", "now()", true ]
					)
				));
			}

		}

		return $local_id;

	}
	public function get_cat_playlists( $id, $jobData=[] ){

		$playlist_create = false;
		$local_id = false;
		if ( !$this->setup() )
		return false;

		$check_self = true;

		bof()->music->_cli( "Spotify_helper: Get `Cat_lists` id:`{$id}`".
		" self:" . ( ( !empty( $check_self ) ) ? "Y" : "N" ) );

		if ( $check_self ){

			$playlists_ids = [];
			$playlists_pages = bof()->spotify->category_playlists( $id, true );

			if ( $playlists_pages ){
				foreach( $playlists_pages as $playlists_page ){
					if ( !empty( $playlists_page["playlists"]["items"] ) ){
						foreach( $playlists_page["playlists"]["items"] as $playlists_page_item ){
							if ( !empty( $playlists_page_item["id"] ) ){
								$playlists_ids[] = $playlists_page_item["id"];
							}
						}
					}
				}
			}

			if ( !empty( $playlists_ids ) ){
				foreach( $playlists_ids as $playlists_id ){
					$this->_bof_this->get_playlist( $playlists_id, $jobData );
				}
			}

			bof()->music->_cli( "Found " . ( !$playlists_ids?"0":count($playlists_ids) ) . " items" );

		}

	}
	public function get_user_playlists( $id, $jobData=[] ){

		$playlist_create = false;
		$local_id = false;
		if ( !$this->setup() )
		return false;

		$check_self = true;

		bof()->music->_cli( "Spotify_helper: Get `User_playlists` id:`{$id}`".
		" self:" . ( ( !empty( $check_self ) ) ? "Y" : "N" ) );

		if ( $check_self ){

			$playlists_ids = [];
			$playlists_pages = bof()->spotify->user_playlists( $id, true );

			if ( $playlists_pages ){
				foreach( $playlists_pages as $playlists_page ){
					if ( !empty( $playlists_page["items"] ) ){
						foreach( $playlists_page["items"] as $playlists_page_item ){
							if ( !empty( $playlists_page_item["id"] ) ){
								$playlists_ids[] = $playlists_page_item["id"];
							}
						}
					}
				}
			}

			if ( !empty( $playlists_ids ) ){
				foreach( $playlists_ids as $playlists_id ){
					$this->_bof_this->get_playlist( $playlists_id, $jobData );
				}
			}

			bof()->music->_cli( "Found " . ( $playlists_ids?"0":count($playlists_ids) ) . " items" );

		}

	}

	public function search( $query, $type, $args=[] ){

		if ( !$this->setup() )
		return false;

		$search = bof()->spotify->search( $query, $type, $args );

		if ( !$search )
		return false;

		if ( !$type )
		return $search;

		if ( empty( $search["{$type}s"]["items"] ) )
		return false;

		return $search["{$type}s"]["items"];

	}
	public function merge_results( $organic, $external, $type ){

		$items = [];

		if ( $organic ){
			foreach( $organic as $organic_i ){
				$items[ $organic_i["raw"]["code"] ] = $organic;
			}
		}
		else
		$organic = [];

		if ( $external ){
			foreach( $external as $spotify_item ){

				$spotify_item_code = bof()->general->make_code( $spotify_item["name"] );
				if ( in_array( $spotify_item_code, array_keys( $items ), true ) ) continue;

				$coverArray = [];
				$_spotify_covers_raw = [];

				if ( !empty( $spotify_item["images"] ) ){
					foreach( $spotify_item["images"] as $_s_cover )
					$_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
					$coverArray["image_strings"] = bof()->image->html( $_spotify_covers_raw );
				}

				$organic[] = array(
					"title" => $spotify_item["name"],
					"sub_data" => !empty( $spotify_item["sub_data"] ) ? $spotify_item["sub_data"] : null,
					"cover" => !empty( $coverArray ) ? $coverArray : null,
					"classes" => "no_buttons",
					"raw" => array(
						"url" => "external_music/{$spotify_item["id"]}?source=spotify&type={$type}&id={$spotify_item["id"]}",
					),
				);

			}
		}

		return $organic;

	}

}

?>
