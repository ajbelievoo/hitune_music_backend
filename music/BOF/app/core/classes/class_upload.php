<?php

if ( !defined("root" ) ) die;

class upload extends bof_type_class {

	protected $c_types = [];
	protected $s_types = [];

	public function add_c_type( $name, $data ){
		$this->c_types[ $name ] = $data;
	}
	public function get_c_type( $name ){
		return !empty( $this->c_types[ $name ] ) ? $this->c_types[ $name ] : null;
	}
	public function get_s_type( $name ){
		return $this->s_types[ $name ];
	}
	public function get_s_types(){
		return $this->s_types;
	}
	public function get_c_types(){
		return $this->c_types;
	}

	public function setup(){

		if ( empty( $this->s_types ) ){
			$this->s_types = array(
				"audio" => array(
					"data" => array(
						"ID" => "audio",
						"icon" => "music-note-outline",
						"name" => bof()->object->language->turn( "upload_audio", [], [ "uc_first" => true, "lang" => "users" ] ),
						"analyze_inputs" => function( $content, $given_sources ){

							if ( empty( $given_sources["files"] ) )
							throw new bofException( "invalid_files" );

							$files = $given_sources["files"];
							$valid_sources = [];
							foreach( $files as $file ){

								$analyze = bof()->upload->get_s_type( "audio" )["data"]["analyze_source"]( $file );

								$valid_sources[] = array(
									"data" => $file,
									"tags" => $analyze
								);

							}

							return $valid_sources;

						},
						"analyze_source" => function( &$file ){

							if ( empty( $file["type"] ) || empty( $file["success"] ) || empty( $file["file_id"] ) || empty( $file["file_pass"] ) ? true : $file["type"] != "audio" )
							throw new bofException( "invalid_files" );

							$file_data = bof()->object->file->select( array(
								"ID" => $file["file_id"],
								"pass" => $file["file_pass"],
								"user_id" => bof()->user->get()->data["ID"]
							) );

							if ( empty( $file_data ) ? true : $file_data["used"] || $file_data["time_moved"] || $file_data["type"] != "audio" )
							throw new bofException( "invalid_files" );

							$tags = bof()->id3->read_tags( $file_data["abs_path"] );

							if ( empty( $tags["bitrate"] ) )
							throw new bofException( "invalid_files" );

							$file["s_title"] = "{$file_data["name"]}.{$file_data["extension"]}";

							return !empty( $tags["tags"] ) ? $tags["tags"] : [];

						},
					),
					"upload" => array(
						"type" => "upload"
					),
				),
				"video" => array(
					"data" => array(
						"ID" => "video",
						"icon" => "video-outline",
						"name" => bof()->object->language->turn( "upload_video", [], [ "uc_first" => true, "lang" => "users" ] ),
						"analyze_inputs" => function( $content, $given_sources ){

							if ( empty( $given_sources["files"] ) )
							throw new bofException( "invalid_files" );

							$files = $given_sources["files"];
							$valid_sources = [];
							foreach( $files as $file ){

								$analyze = bof()->upload->get_s_type( "video" )["data"]["analyze_source"]( $file );

								$valid_sources[] = array(
									"data" => $file,
									"tags" => $analyze
								);

							}

							return $valid_sources;

						},
						"analyze_source" => function( &$file ){

							if ( empty( $file["type"] ) || empty( $file["success"] ) || empty( $file["file_id"] ) || empty( $file["file_pass"] ) ? true : $file["type"] != "video" )
							throw new bofException( "invalid_files" );

							$file_data = bof()->object->file->select( array(
								"ID" => $file["file_id"],
								"pass" => $file["file_pass"],
								"user_id" => bof()->user->get()->data["ID"]
							) );

							if ( empty( $file_data ) ? true : $file_data["used"] || $file_data["time_moved"] || $file_data["type"] != "video" )
							throw new bofException( "invalid_files" );

							$tags = bof()->id3->read_tags( $file_data["abs_path"], "video" );

							if ( empty( $tags["width"] ) ? true : $tags["width"] < 100 )
							throw new bofException( "invalid_files" );

							$file["s_title"] = "{$file_data["name"]}.{$file_data["extension"]}";

							return !empty( $tags["tags"] ) ? $tags["tags"] : [];

						},
					),
					"upload" => array(
						"type" => "upload"
					)
				),
				"youtube" => array(
					"data" => array(
						"ID" => "youtube",
						"icon" => "youtube",
						"name" => bof()->object->language->turn( "import_youtube", [], [ "uc_first" => true, "lang" => "users" ] ),
						"analyze_inputs" => function( $content, $given_sources ){

							if ( empty( $given_sources["inputs"]["youtube_id"] ) )
							throw new bofException( "invalid_youtube_id" );

							$input = $given_sources["inputs"]["youtube_id"];

							$is_valid_url = bof()->nest->validate( $input, "url", array(
								"default_scheme" => false,
								"acceptable_schemes" => [ "https" ]
							) );

							// URL to ID
							if ( $is_valid_url ){

								$parse_url = parse_url( $input );
								if ( empty( $parse_url["query"] ) )
								throw new bofException( "invalid_youtube_url" );

								parse_str( $parse_url["query"], $queries );
								if ( empty( $queries ) ? true : empty( $queries["v"] ) )
								throw new bofException( "invalid_youtube_url" );

								$input = $queries["v"];

							}

							// Validate ID
							if ( !preg_match( '/^[a-zA-Z0-9_-]{11}$/' , $input ) )
							throw new bofException( "invalid_youtube_id" );

							// Get ID data
							$req_api_data = bof()->youtube->get_video_clean( $input );
							if ( !$req_api_data[0] ? true : empty( $req_api_data[1] ) )
							throw new bofException( $req_api_data[1] == "not_found" ? "invalid_youtube_id" : "youtube_request_failed" );

							$videoData = $req_api_data[1];
							return array(
								array(
									"data" => array_merge(
										$videoData,
										array(
											"type" => "youtube"
										)
									),
									"tags" => $analyze = bof()->upload->get_s_type( "youtube" )["data"]["analyze_source"]( $videoData )
								)
							);

						},
						"analyze_source" => function( &$videoData ){

							$coverString = null;

							if ( empty( $videoData ) ? ture : empty( $videoData["title"] ) )
							throw new bofException( "invalid_data" );

							if ( !empty( $videoData["live"] ) )
							throw new bofException( "invalid_data" );

							return array(
								"title" => $videoData["title"],
								"artist_name" => $videoData["channel_name"],
								"album_artist_name" => $videoData["channel_name"],
								"album_time" => !empty( $videoData["time_publish"] ) ? substr( bof()->general->mysql_timestamp( strtotime( $videoData["time_publish"] ) ), 0, 10 ) : null,
								"cover_string" => $coverString,
								"genres" => $videoData["tags"]
							);

						},
					),
					"upload" => array(
						"inputs" => array(
							"youtube_id" => array(
								"title" => bof()->object->language->turn( "youtube_id", [], [ "uc_first" => true, "lang" => "users" ] ),
								"tip" => bof()->object->language->turn( "youtube_id_url_tip", [], [ "uc_first" => true, "lang" => "users" ] ),
								"input" => array(
									"name" => "youtube_id",
									"type" => "text"
								)
							)
						)
					)
				),
				"soundcloud" => array(
					"data" => array(
						"ID" => "soundcloud",
						"icon" => "soundcloud",
						"name" => bof()->object->language->turn( "import_soundcloud", [], [ "uc_first" => true, "lang" => "users" ] ),
						"analyze_inputs" => function( $content, $given_sources ){

							if ( empty( $given_sources["inputs"]["soundcloud_embed"] ) )
							throw new bofException( "invalid_soundcloud_embed" );

							$input = $given_sources["inputs"]["soundcloud_embed"];

							if (
								!preg_match( "/<iframe(.*?)<\/iframe>/i", $input ) ||
								!preg_match( "/https%3A\/\/api.soundcloud.com\/(.*?)\/(.*?)&/i", $input, $m ) ||
								!preg_match( "/. <a href=\"(.*?)\" title=/i", $input, $l )
							)
							throw new bofException( "invalid_soundcloud_embed" );

							if ( !ctype_digit( $m[2] ) && !is_int( $m[2] ) )
							throw new bofException( "invalid_soundcloud_embed" );

							if ( $m[1] != "tracks" )
							throw new bofException( "invalid_soundcloud_embed_nt" );

							if ( !bof()->nest->validate( $l[1], "url" ) ? true : substr( $l[1], 0, strlen("https://soundcloud.com/") ) != "https://soundcloud.com/" )
							throw new bofException( "invalid_soundcloud_embed" );

							$embed_id = $m[2];
							$track_link = $l[1];

							return array(
								array(
									"data" => array(
										"type" => "soundcloud",
										"embed_id" => $embed_id,
										"track_link" => $track_link
									),
									"tags" => bof()->upload->get_s_type( "soundcloud" )["data"]["analyze_source"]( $embed_id )
								)
							);

						},
						"analyze_source" => function( $embed_id ){

							try {

								$trackData = bof()->soundcloud->get_track( $embed_id );
								if ( $trackData[0] && !empty( $trackData[1] ) )
								$trackData = $trackData[1];

								$coverString = null;
								if ( !empty( $trackData["artwork_url"] ) ){

									$get_cover = bof()->curl->exe( array(
										"url" => $trackData["artwork_url"],
										"agent" => "chrome",
										"type" => "image"
									) );

									if ( $get_cover ? !empty( $get_cover["body"] ) : false )
									$coverString = $get_cover["body"];

								}


								$trackData = array(
									"title" => !empty( $trackData["title"] ) ? $trackData["title"] : null,
									"artist_name" => !empty( $trackData["publisher_metadata"]["artist"] ) ? $trackData["publisher_metadata"]["artist"] : ( !empty( $trackData["user"]["username"] ) ? $trackData["user"]["username"] : null ),
									"album_artist_name" => !empty( $trackData["publisher_metadata"]["artist"] ) ? $trackData["publisher_metadata"]["artist"] : ( !empty( $trackData["user"]["username"] ) ? $trackData["user"]["username"] : null ),
									"album_time" => !empty( $trackData["created_at"] ) ? $trackData["created_at"] : null,
									"cover_string" => $coverString,
									"genres" => null
								);

							} catch( bofException $err ){}

							if ( empty( $trackData ) )
							$trackData = array(
								"embed_id" => $embed_id,
								"link" => $track_link
							);

							return $trackData;

						},

					),
					"upload" => array(
						"inputs" => array(
							"soundcloud_embed" => array(
								"title" => bof()->object->language->turn( "soundcloud_embed", [], [ "uc_first" => true, "lang" => "users" ] ),
								"tip" => bof()->object->language->turn( "soundcloud_embed_tip", [], [ "uc_first" => true, "lang" => "users" ] ),
								"input" => array(
									"name" => "soundcloud_embed",
									"type" => "textarea",
									"placeholder" => "<iframe"
								)
							)
						)
					)
				),
			);
		}

		return $this->_bof_this;

	}

	public function get_contents(){

		$raw = $this->c_types;
		foreach( $raw as $k => &$v ){

			if ( empty( bof()->user->get()->extra["roles"]["upload_{$k}"] ) ){
				unset( $raw[$k] );
				continue;
			}

			if ( empty( $v["sources"]["data"] ) ) continue;

			if ( !empty( $v["sources"]["supported"] ) ){
				foreach( $v["sources"]["supported"] as $___i => $_c_type_s_supported ){
					if ( empty( bof()->user->get()->extra["roles"]["upload_{$k}_types"] ) ? true : !in_array( $_c_type_s_supported, bof()->user->get()->extra["roles"]["upload_{$k}_types"], true ) ){
						unset( $v["sources"]["supported"][$___i] );
					}
				}
				if ( !empty( $v["sources"]["supported"] ) )
				$v["sources"]["supported"] = array_values( $v["sources"]["supported"] );
			}

			foreach( $v["sources"]["data"] as $_k => &$_v ){
				if ( empty( $_v["inputs"] ) ) continue;
				foreach( $_v["inputs"] as &$__v ){
					if ( empty( $__v["bofInput"] ) ) continue;
					$__v["bofInput"][1]["_maxFiles"] = 100;
					$__v["input"] = array(
						"name" => "{$k}_{$_k}",
						"type" => "bof_file"
					);
					$__v = bof()->bofInput->parse( $__v, [ "translate" => true ] )["data"];
				}
			}

		}

		if ( empty( $raw ) )
		throw new bofException( "no_access" );

		return $raw;

	}
	public function get_sources(){
		return $this->s_types;
	}

	protected function check_access(){

		$user = bof()->user->get();
		if ( empty( $user->extra["roles"]["upload"] ) && empty( array_intersect( $user->extra["role_ids"], array( "artist", "podcaster" ) ) ) )

		throw new bofException( "no_access" );

	}
	public function get_config(){

		$this->check_access();

		return array(
			"_cs" => $this->_bof_this->get_contents(),
			"_ss" => $this->_bof_this->get_sources(),
			"_ts" => array(
				"1" => bof()->object->language->turn( "ups1t", [], [ "lang" => "users" ] ),
				"2" => bof()->object->language->turn( "ups2t", [], [ "lang" => "users" ] ),
				"3u" => bof()->object->language->turn( "ups3ut", [], [ "lang" => "users" ] ),
				"3i" => bof()->object->language->turn( "ups3it", [], [ "lang" => "users" ] ),
			)
		);

	}
	public function verify_sources( $given_content, $given_source, $given_sources ){

		$this->check_access();

		if ( empty( $given_content["ID"] ) || empty( $given_source["ID"] ) || empty( $given_sources ) )
		throw new bofException("invalid_data");

		$content_type = $given_content["ID"];
		$source_type = $given_source["ID"];

		$supported = $this->_bof_this->get_config();

		if ( !in_array( $content_type, array_keys( $supported["_cs"] ), true ) )
		throw new bofException("invalid_data");
		$content = $supported["_cs"][ $content_type ];

		if ( !in_array( $source_type, $content["sources"]["supported"], true ) || !in_array( $source_type, array_keys( $supported["_ss"] ), true ) )
		throw new bofException("invalid_data");
		$source = $supported["_ss"][ $source_type ];

		$verify = $source["data"]["analyze_inputs"]( $content, $given_sources );

		foreach( $verify as $verified_item ){
			$ID = uniqid();
			$verified_item["ID"] = $ID;
			$verify_ided[ $ID ] = $verified_item;
		}

		if ( !empty( $content["get_group_inputs"] ) ){
			$step4_5 = $content["get_group_inputs"]( $verify_ided );
			if ( !empty( $step4_5 ) ){
				foreach( $step4_5 as $_k => &$_i ){

					$_i["input"]["name"] = $_k;

					if ( !empty( $_i["bofInput"] ) ){
						$_i = bof()->bofInput->parse( $_i, [ "translate" => true ]  )["data"];
					}

					$_i["label"] = bof()->object->language->turn( $_i["label"], [], [ "uc_first" => true, "lang" => "users" ] );
					if ( !empty( $_i["tip"] ) )
					$_i["tip"] = bof()->object->language->turn( $_i["tip"], [], [ "uc_first" => true, "lang" => "users" ] );

				}
			}
		}

		$verify_content_filtered = $content["analyze_inputs"]( $verify_ided, $content["get_item_inputs"]() );

		foreach( $verify_content_filtered as $_id => &$_v ){

			unset( $_v["tags"] );
			$_v_inputs = $_v["inputs"];
			$_v["inputs"] = [];

			$_v["s_title"] = "unknown";

			foreach( $_v_inputs as $_k => &$_i ){
				$_i["label"] = bof()->object->language->turn( $_i["label"], [], [ "uc_first" => true, "lang" => "users" ] );
				if ( !empty( $_i["tip"] ) )
				$_i["tip"] = bof()->object->language->turn( $_i["tip"], [], [ "uc_first" => true, "lang" => "users" ] );
				$_i["input"]["name"] = "{$_id}_{$_k}";
				if ( !empty( $_i["bofInput"] ) ){
					$_i = bof()->bofInput->parse( $_i, [ "translate" => true ]  )["data"];
				}
				if ( !empty( $_i["display_on"] ) ){
					$_do = $_i["display_on"];
					$_i["display_on"] = [];
					foreach( $_do as $_doK => $_doV )
					$_i["display_on"][ "{$_id}_{$_doK}" ] = $_doV;
				}
				$_v["inputs"]["{$_id}_{$_k}"] = $_i;
			}

			if ( !empty( $_v["groups"] ) ){
				foreach( $_v["groups"] as &$_g ){
					$_g[1] = bof()->object->language->turn( $_g[1], [], [ "uc_first" => true, "lang" => "users" ] );
				}
			}

		}

		return array(
			"verified" => $verify_content_filtered,
			"step4_5" => !empty( $step4_5 ) ? $step4_5 : null
		);

	}
	public function verify_content_group( $prependGroupName=false ){

		$this->check_access();

		$given_content = bof()->nest->user_input( "post", "content_data", "json" );
		$given_source = bof()->nest->user_input( "post", "source_data", "json" );
		$supported = $this->_bof_this->get_config();

		if ( empty( $given_content["ID"] ) || empty( $given_source["ID"] ) )
		throw new bofException("invalid_data");

		$content_type = $given_content["ID"];
		$source_type = $given_source["ID"];

		if ( !in_array( $content_type, array_keys( $supported["_cs"] ), true ) )
		throw new bofException("invalid_data");
		$content = $supported["_cs"][ $content_type ];

		if ( !in_array( $source_type, $content["sources"]["supported"], true ) || !in_array( $source_type, array_keys( $supported["_ss"] ), true ) )
		throw new bofException("invalid_data");
		$source = $supported["_ss"][ $source_type ];

		$group_inputs = $content["get_group_inputs"]();
		if ( empty( $group_inputs ) )
		throw new bofException("invalid_data");

		$check_form = bof()->bofInput->__check_form( $group_inputs, array(
			"input_name_prefix" => $prependGroupName ? "group" : null
		) );

		if ( !$check_form["ok"] )
		throw new bofException("check_the_form",0,null,$check_form);

		$verified_source = bof()->nest->user_input( "post", "verified", "json" );
		$content["modify_item_inputs"]( $verified_source, $check_form["data"] );
		$check_form["verified"] = $verified_source;

		$item = $content["check_series"]( $check_form["data"] );

		if ( !empty( $item["uploader_id"] ) ? $item["uploader_id"] != bof()->user->check()->ID : false ){
			throw new bofException( "already_uploaded" );
		}

		return $check_form;

	}
	public function verify_submit(){

		$this->check_access();

		$given_content = bof()->nest->user_input( "post", "content_data", "json" );
		$given_source = bof()->nest->user_input( "post", "source_data", "json" );
		$supported = $this->_bof_this->get_config();

		if ( empty( $given_content["ID"] ) || empty( $given_source["ID"] ) )
		throw new bofException("invalid_data");

		$content_type = $given_content["ID"];
		$source_type = $given_source["ID"];

		if ( !in_array( $content_type, array_keys( $supported["_cs"] ), true ) )
		throw new bofException("invalid_data");
		$content = $supported["_cs"][ $content_type ];

		if ( !in_array( $source_type, $content["sources"]["supported"], true ) || !in_array( $source_type, array_keys( $supported["_ss"] ), true ) )
		throw new bofException("invalid_data");
		$source = $supported["_ss"][ $source_type ];

		$c_source_id = bof()->nest->user_input( "post", "source_id", "string" );
		if ( empty( $c_source_id ) )
		throw new bofException("invalid_data");

		$verified_source = bof()->nest->user_input( "post", "verified_source", "json" );
		$source["data"]["analyze_source"]( $verified_source );

		$inputs = $content["get_item_inputs"]();
		$check_form = bof()->bofInput->__check_form( $inputs, array(
			"input_name_prefix" => $c_source_id
		) );

		if ( !$check_form["ok"] )
		throw new bofException("invalid_form_item",0,null,$check_form);
		$data = $check_form["data"];

		if ( !empty( $content["get_group_inputs"]() ) ){

			$check_group_form = $this->_bof_this->verify_content_group(true);

			if ( !$check_group_form["ok"] )
			throw new bofException("invalid_data");
			$group_data = $check_group_form["data"];

		}

		$data["duration"] = !empty( $data["duration"] ) ? $data["duration"] : null;
		if ( !empty( $verified_source["type"] ) && !empty( $verified_source["file_id"] ) ? in_array( $verified_source["type"], [ "audio", "video" ], true ) : false ){
			try {
				$getFile = bof()->object->file->sid( $verified_source["file_id"] );
				$duration = bof()->id3->read_tags( $getFile["abs_path"] )["duration"];
				$data["duration"] = $duration;
			} catch( Exception|bofException $err ){}
		}

		$content["submit"](
			$verified_source,
			$data,
			!empty( $group_data ) ? $group_data : null,
			$check_form
		);

		bof()->chapar->notify_admin("upload_ok", array(
			"data" => $data,
			"source" => $verified_source,
			"form" => $check_form,
			"group_data" => !empty( $group_data ) ? $group_data : null
		));

		return [];

	}

}

?>
