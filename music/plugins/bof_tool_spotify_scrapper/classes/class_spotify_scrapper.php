<?php

if ( !defined( "bof_root" ) ) die;

class spotify_scrapper {

	protected $keys = [];
	public function setup(){

		bof()->listen( "spotify", "request_replace", function( $method_args, &$method_result, $loader ){

			if (bof()->object->db_setting->get("spotify_s")) {
				$addr = bof()->object->db_setting->get("spotify_sp");
			}

			if ( empty( $addr ) )
			return null;

			$endpoint = $method_args[0];
			$params = !empty( $method_args[1]) ? $method_args[1] : [];
			$retrying = !empty( $method_args[2]) ? $method_args[2] : false;
			$config = !empty( $method_args[3]) ? $method_args[3] : [];

			if ( bof()->general->startsWidth( $endpoint, "playlists/" ) && !bof()->general->endsWidth( $endpoint, "/tracks" ) ){
				$id = substr( $endpoint, strlen( "playlists/" ) );
				$data = bof()->spotify_scrapper->get_playlist( $id );
				return $data;
			}

			if ( bof()->general->startsWidth( $endpoint, "playlists/" ) && bof()->general->endsWidth( $endpoint, "/tracks" ) ){
				$id = str_replace( [ "playlists/", "/tracks" ], "", $endpoint );
				$data = bof()->spotify_scrapper->get_playlist_items( $id, $params );
				return $data;
			}

			if (bof()->general->startsWidth($endpoint, "artists/") && bof()->general->endsWidth($endpoint, "/related-artists")) {
				$id = substr($endpoint, strlen("artists/"), 22);
				$data = bof()->spotify_scrapper->get_artist_related_artists($id);
				return $data;
			}

			if (bof()->general->startsWidth($endpoint, "artists/") && bof()->general->endsWidth($endpoint, "/top-tracks")) {
				$id = substr($endpoint, strlen("artists/"), 22);
				$data = bof()->spotify_scrapper->get_artist_top_tracks($id);
				return $data;
			}

			if ( defined("stu") ) {
				if ( bof()->general->startsWidth($endpoint, "albums/") && !bof()->general->endsWidth($endpoint, "/tracks") ) {
					$id = substr($endpoint, strlen("albums/"));
					$data = bof()->spotify_scrapper->get_album($id);
					return $data;
				}
			}

			return null;

		} );

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

	}

	protected function setup_admin(){

		bof()->scrapper->setup_admin( array(
			"title" => "Spotify",
			"id" => "spotify",
			"detail" => "This scrapper <b style='color:rgb(var(--c_orange))'>does not fully replace the official API</b>. It only bypasses limited endpoints after <a href='https://developer.spotify.com/blog/2024-11-27-changes-to-the-web-api' target='_blank'>Spotify's API changes</a>",
			"functions" => array(
				"ui_pre" => function( $groups ){

					$check = bof()->boac->spotify_scrapper_keys_can();
					if ( !$check ){
						$groups["setting"]["inputs"]["spotify_s"]["tip"] .= "<br><br>This scrapper only works if your RKHM support is still valid. <a href='https://support.busyowl.co/documentation/spotify_scrapper' target='_blank'>Why? More info here</a>. <br><b style='color:rgb(var(--c_red)); font-size:110%'>Your support has expired. You can't use this scrapper</b>";
					} else {
						$groups["setting"]["inputs"]["spotify_s"]["tip"] .= "<br><br>This scrapper only works if your RKHM support is still valid. <a href='https://support.busyowl.co/documentation/spotify_scrapper' target='_blank'>Why? More info here</a>. <br><b style='color:rgb(var(--c_green)); font-size:110%'>Your support is valid untill: " . $check["time"] . "</b>";

					}

					$groups["setting"]["inputs"]["spotify_s"]["tip"] .= "<br><br><b style='color:rgb(var(--c_orange))'>A working proxy IS MANDATORY/REQUIRED for this scrapper</b>";
					return $groups;
				},
				"be_after" => function( $groups, $inputs ){

					if (!empty($inputs["data"]["spotify_s"])) {
						if (empty($inputs["data"]["spotify_sp"])) {
							$inputs["report"]["fail"]["spotify_s"] = "A proxy is MANDATORY for this scrapper";
						}
						if (!bof()->boac->spotify_scrapper_keys_can()) {
							$inputs["report"]["fail"]["spotify_s"] = "You can't enable this scrapper with expired support. Even if you force it via database, the API response will fail.<br><br><a href='https://support.busyowl.co/documentation/spotify_scrapper' target='_blank'>Why? More info here</a>";
						}
					}

					return $inputs;

				}
			)
		) );
	}

	public function get_track($id){

		$req = $this->_request("getTrack", '{"uri":"spotify:track:' . $id . '"}');

		$unofficial = $req["data"]["data"];

		$trackUnion = $unofficial['trackUnion'] ?? [];
		$albumOfTrack = $trackUnion['albumOfTrack'] ?? [];
		$firstArtist = $trackUnion['firstArtist']['items'][0] ?? [];
		$mainArtist = $firstArtist['profile']['name'] ?? '';
		$artistId = explode(':', $firstArtist['uri'] ?? '')[2] ?? '';

		return [
			'added_at' => $trackUnion['addedAt']['isoString'] ?? null,
			'added_by' => null, // Not available in unofficial response
			'is_local' => false,
			'primary_color' => null,
			'track' => [
				'preview_url' => null,
				'available_markets' => [], // Not available in unofficial
				'explicit' => ($trackUnion['contentRating']['label'] ?? '') === 'EXPLICIT',
				'type' => 'track',
				'episode' => false,
				'track' => true,
				'album' => [
					'available_markets' => [],
					'type' => 'album',
					'album_type' => 'single', // Default assumption
					'href' => "https://api.spotify.com/v1/albums/" . (explode(':', $albumOfTrack['uri'] ?? '')[2] ?? ''),
					'id' => explode(':', $albumOfTrack['uri'] ?? '')[2] ?? '',
					'images' => !empty( $albumOfTrack['coverArt']['sources'] ) ? array_map(function ($img) {
						return [
							'height' => $img['height'] ?? 0,
							'url' => $img['url'] ?? '',
							'width' => $img['width'] ?? 0
						];
					}, $albumOfTrack['coverArt']['sources'] ?? []) : [],
					'name' => $albumOfTrack['name'] ?? '',
					'release_date' => $albumOfTrack['date']['isoString'] ?? null,
					'release_date_precision' => strtolower($albumOfTrack['date']['precision'] ?? 'day'),
					'uri' => $albumOfTrack['uri'] ?? '',
					'artists' => [
						[
							'external_urls' => ['spotify' => "https://open.spotify.com/artist/$artistId"],
							'href' => "https://api.spotify.com/v1/artists/$artistId",
							'id' => $artistId,
							'name' => $mainArtist,
							'type' => 'artist',
							'uri' => $firstArtist['uri'] ?? ''
						]
					],
					'external_urls' => ['spotify' => "https://open.spotify.com/album/" . (explode(':', $albumOfTrack['uri'] ?? '')[2] ?? '')],
					'total_tracks' => count($albumOfTrack['tracks']['items'] ?? [])
				],
				'artists' => array_map(function ($artist) {
					$artistId = explode(':', $artist['uri'] ?? '')[2] ?? '';
					return [
						'external_urls' => ['spotify' => "https://open.spotify.com/artist/$artistId"],
						'href' => "https://api.spotify.com/v1/artists/$artistId",
						'id' => $artistId,
						'name' => $artist['profile']['name'] ?? '',
						'type' => 'artist',
						'uri' => $artist['uri'] ?? ''
					];
				}, $trackUnion['firstArtist']['items'] ?? []),
				'disc_number' => 1, // Not available in unofficial
				'track_number' => $trackUnion['trackNumber'] ?? 1,
				'duration_ms' => $trackUnion['duration']['totalMilliseconds'] ?? 0,
				'external_ids' => null, // Not available
				'external_urls' => [
					'spotify' => "https://open.spotify.com/track/" . ($trackUnion['id'] ?? '')
				],
				'href' => "https://api.spotify.com/v1/tracks/" . ($trackUnion['id'] ?? ''),
				'id' => $trackUnion['id'] ?? '',
				'name' => $trackUnion['name'] ?? '',
				'popularity' => min((int)($trackUnion['playcount'] ?? 0) / 10000, 100),
				'uri' => $trackUnion['uri'] ?? '',
				'is_local' => false
			],
			'video_thumbnail' => [
				'url' => null
			]
		];

	}
	public function get_playlist( $id ){

		$req = $this->_request( "fetchPlaylist", '{"uri":"spotify:playlist:'.$id.'","enableWatchFeedEntrypoint":true,"offset":0,"limit":25}' );

		$playlistData = $req["data"]["data"]["playlistV2"];
		$playlistImageSources = $playlistData["images"]["items"][0]["sources"];

		return array(
			"name" => $playlistData["name"],
			"images" => $playlistImageSources,
			"id" => $id,
			"uri" => "spotify:playlist:{$id}",
			"type" => "playlist"
		);

	}
	public function get_playlist_items( $id, $params ){

		$limit = 25;
		$offset = 0;
		extract( $params );

		$req = $this->_request( "fetchPlaylistContents", '{"uri":"spotify:playlist:'.$id.'","offset":'.$offset.',"limit":'.$limit.'}' );

		$playlistData = $req["data"]["data"]["playlistV2"];
		
		$__pts = !empty( $playlistData["content"] ) ? $playlistData["content"] : $playlistData["contentV2"];

		$tracks = [];

		if (empty($__pts["items"])) {
			return array(
				"items" => [],
				"next" => empty($tracks) ? false : count($tracks) >= 25
			);
		}
		
		foreach (array_reverse( $__pts["items"] ) as $unofficial) {

			$trackData = $unofficial['itemV2']['data'];
			$trackID = explode(':', $trackData['uri'])[2];

			$track_exists = bof()->object->m_track->select(
				array(
					"spotify_id" => $trackID
				),
				array(
					"clean" => false
				)
			);

			if ( $track_exists ){
				$trackFData = array(
					"track" => array(
						"bof_object_type" => "track",
						"bof_object_id" => $track_exists["ID"]
					)
				);
			}
			else {
				try {
					$trackFData = $this->get_track( $trackID );
				} catch( bofException|Exception|Error $err ){
					$trackFData = false;
				}
			}

			if ( !empty( $trackFData ) )
			$tracks[] = $trackFData;

		}

		return array(
			"items" => $tracks,
			"next" => empty( $tracks ) ? false : count( $tracks ) >= 25
		);

	}

	public function get_album( $id ){

		$req = $this->_request( "getAlbum", '{"uri":"spotify:album:'.$id.'","locale":"","offset":0,"limit":50}' );

		$albumData = $req["data"]["data"]["albumUnion"];

		$artists = [];

		foreach( $albumData["artists"]["items"] as $_artist ){
			$artists[] = array(
				"id" => $_artist["id"],
				"name" => $_artist["profile"]["name"],
				"type" => "artist"
			);
		}

		$__pts = !empty( $albumData["tracks"] ) ? $albumData["tracks"] : $albumData["tracksV2"];

		foreach( $__pts["items"] as $_track ){

			$_track = $_track["track"];
			$_track_artists = [];

			foreach( $_track["artists"]["items"] as $_track_artist ){
				$_track_artists[] = array(
					"id" => substr( $_track_artist["uri"], strlen( "spotify:artist:" ) ),
					"name" => $_track_artist["profile"]["name"],
					"type" => "artist"
				);
			}

			$tracks[] = array(
				"artists" => $_track_artists,
				"disc_number" => $_track["discNumber"],
				"duration_ms" => $_track["duration"]["totalMilliseconds"],
				"explicit" => null,
				"id" => substr( $_track["uri"], strlen( "spotify:track:") ),
				"name" => $_track["name"],
				"track_number" => $_track["trackNumber"],
				"type" => "track",
			);

		}

		return array(
			"album_type" => strtolower( $albumData["type"] ),
			"artists" => $artists,
			"id" => $id,
			"images" => $albumData["coverArt"]["sources"],
			"label" => $albumData["label"],
			"name" => $albumData["name"],
			"popularity" => null,
			"release_date" => $albumData["date"]["isoString"],
			"release_date_precision" => strtolower( $albumData["date"]["precision"] ),
			"total_tracks" => $__pts["totalCount"],
			"tracks" => array(
				"total" => $__pts["totalCount"],
				"items" => $tracks
			),
			"type" => "album"
		);

	}
	public function get_artist_related_artists( $id ){

		$req = $this->_request( "queryArtistOverview", '{"uri":"spotify:artist:'.$id.'","locale":"","includePrerelease":true}' );
		$artistData = $req["data"]["data"]["artistUnion"];
		$_as = [];

		if ( !empty( $artistData["relatedContent"]["relatedArtists"]["items"] ) ){
			foreach( $artistData["relatedContent"]["relatedArtists"]["items"] as $_a ){
				$_as[] = array(
					"id" => $_a["id"],
					"name" => $_a["profile"]["name"],
					"type" => "artist",
					"images" => !empty( $_a["visuals"]["avatarImage"]["sources"] ) ? $_a["visuals"]["avatarImage"]["sources"] : []
				);
			}
		}

		return array(
			"artists" => $_as
		);

	}
	public function get_artist_top_tracks( $id ){

		$req = $this->_request( "queryArtistOverview", '{"uri":"spotify:artist:'.$id.'","locale":"","includePrerelease":true}' );
		$artistData = $req["data"]["data"]["artistUnion"];

		$tracks = [];

		if ( !empty( $artistData["discography"]["topTracks"]["items"] ) ){
			foreach( $artistData["discography"]["topTracks"]["items"] as $_aii ){

				if ( empty( $_aii["track"] ) ) continue;
				$track = $_aii["track"];

				$track_album_id = substr( $track["albumOfTrack"]["uri"], strlen("spotify:album:") );

				$check_db_for_album = bof()->object->m_album->select(
					array(
						"spotify_id" => $track_album_id
					),
					array(
						"_eq" => array(
							"artist" => [],
							"artists" => []
						)
					)
				);

				if ( $check_db_for_album ){
					$track_album_data = array(
						"album_type" => strtolower( $check_db_for_album["type"] ),
						"artists" => array(
							array(
								"id" => $check_db_for_album["bof_dir_artist"]["spotify_id"],
								"type" => "artist",
								"name" => $check_db_for_album["bof_dir_artist"]["name"]
							)
						),
						"id" => $track_album_id,
						"name" => $check_db_for_album["title"],
						"type" => "album"
					);
				} else {
					$track_album_data = $this->get_album( $track_album_id );
				}

				$artists = [];

				foreach( $track["artists"]["items"] as $_artist ){
					$artists[] = array(
						"id" => substr( $_artist["uri"], strlen("spotify:artist:") ),
						"name" => $_artist["profile"]["name"],
						"type" => "artist"
					);
				}

				$tracks[] = array(
					"album" => $track_album_data,
					"artists" => $artists,
					"disc_number" => $track["discNumber"],
					"duration_ms" => $track["duration"]["totalMilliseconds"],
					"explicit" => !empty( $track["contentRating"]["label"] ) ? strtolower( $track["contentRating"]["label"] ) == "explicit" : false,
					"id" => $track["id"],
					"name" => $track["name"],
					"popularity" => null,
					"track_number" => null,
					"type" => "track"
				);

			}
		}

		return array(
			"tracks" => $tracks
		);

	}

	protected function _proxy(){

		if ( bof()->object->db_setting->get("spotify_s") ){
			$addr = bof()->object->db_setting->get("spotify_sp");
			return $addr;
		}

	}
	protected function _keys( $forceRenew=false ){

		if ( $this->keys && !$forceRenew ) return $this->keys;

		if ( !$forceRenew ){
			$storedKeys = bof()->object->db_setting->get( "spotify_scrapper_keys" );
			if ( $storedKeys ){
				$this->keys = $storedKeys;
				return $this->keys;
			}
		}

		$this->keys = bof()->boac->spotify_scrapper_keys(
			$this->_proxy()
		);

		bof()->object->db_setting->set( "spotify_scrapper_keys", json_encode( $this->keys ), "json" );

		return $this->keys;

	}

	protected function _request( $operationName, $vars, $args=[], $retrying=false ){

		extract( $this->_keys() );

		$base = "https://api-partner.spotify.com/pathfinder/v2/query";
		$params["variables"] = json_decode( $vars, 1 );
		$params["operationName"] = $operationName;
		$params["extensions"] = array(
			"persistedQuery" => array(
				"sha256Hash" => $$operationName,
				"version" => 1
			)
		);

		$headers[] = "accept-language: en";
		$headers[] = "accept: application/json";
		$headers[] = "host: api-partner.spotify.com";
		$headers[] = "app-platform: WebPlayer";
		// $headers[] = "authorization: {$authorization}";
		$headers[] = "client-token: {$client_token}";
		$headers[] = "origin: https://open.spotify.com";
		$headers[] = "referer: https://open.spotify.com/";
		// $headers[] = "spotify-app-version: {$app_version}";

		$headers[] = "sec-ch-ua: \"Chromium\";v=\"140\", \"Not=A?Brand\";v=\"24\", \"Google Chrome\";v=\"140\"";
		$headers[] = "sec-ch-ua-mobile: ?0";
		$headers[] = "sec-ch-ua-platform: \"Windows\"";
		$headers[] = "sec-fetch-dest: empty";
		$headers[] = "sec-fetch-mode: cors";
		$headers[] = "sec-fetch-site: same-site";
		$headers[] = "priority: u=1, i";

		$headers[] = "content-length: " . strlen( json_encode( $params ) );
		$headers[] = "content-type: application/json;charset=UTF-8";

		$agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36";

		$req = bof()->curl->exe( array(
			"url" => $base,
			"posts" => json_encode( $params ),
			"headers" => $headers,
			"agent" => $agent,
			"proxy" => $this->_proxy(),
			"ctimeout" => 10,
			"cache" => true,
			"cache_save" => true,
			"cache_load" => false,
			"cli_echo" => false
		) );

		if ( php_sapi_name() === "cli" && ( $req["http_code"] == 403 || $req["http_code"] == 400 || $req["http_code"] == 401 || $req["http_code"] == 402 ) ){
			if ( !$retrying ){
				$this->_keys( true );
				return $this->_request( $operationName, $vars, $args, true );
			} else {
				return false;
			}
		}

		return $req;

	}

}

?>
