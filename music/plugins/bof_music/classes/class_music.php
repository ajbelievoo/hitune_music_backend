<?php

if ( !defined( "bof_root" ) ) die;

class music {

	public $cache = [];
	public function set_cache( $var, $val ){
		$this->cache[ $var ] = $val;
	}
	public function get_cache( $var, $default_val = null, $resetAfter=false ){
		$val = in_array( $var, array_keys( $this->cache ), true ) ? $this->cache[ $var ] : $default_val;
		if ( $resetAfter ) $this->reset_cache();
		return $val;
	}
	public function reset_cache(){
		$this->cache = [];
	}

	protected $CID = null;
	protected $PID = null;
	protected $GID = null;

	public function set_external_cronjob( $PID, $GID ){
		$this->PID = $PID;
		$this->GID = $GID;
	}
	public function setup(){

		bof()->object->core_files->add_object( "m_cronjob", bof_music_root . "/objects/object_cronjob.php" );
		bof()->object->core_files->add_object( "m_cronjob_spotify", bof_music_root . "/objects/object_cronjob_spotify.php" );
		bof()->object->core_files->add_object( "m_artist", bof_music_root . "/objects/object_artist.php" );
		bof()->object->core_files->add_object( "m_album", bof_music_root . "/objects/object_album.php" );
		bof()->object->core_files->add_object( "m_track", bof_music_root . "/objects/object_track.php" );
		bof()->object->core_files->add_object( "m_track_source", bof_music_root . "/objects/object_track_source.php" );
		bof()->object->core_files->add_object( "m_genre", bof_music_root . "/objects/object_genre.php" );
		bof()->object->core_files->add_object( "m_lang", bof_music_root . "/objects/object_lang.php" );
		bof()->object->core_files->add_object( "m_tag", bof_music_root . "/objects/object_tag.php" );

		bof()->object->core_files->add_key( "class", "spotify", bof_music_root . "/classes/class_spotify.php" );
		bof()->object->core_files->add_key( "class", "spotify_helper", bof_music_root . "/classes/class_spotify_helper.php" );
		bof()->object->core_files->add_key( "class", "youtube_helper", bof_music_root . "/classes/class_youtube_helper.php" );
		bof()->object->core_files->add_key( "class", "lyric", bof_music_root . "/classes/class_lyric.php" );
		bof()->object->core_files->add_key( "class", "lyric_musixmatch", bof_music_root . "/classes/class_lyric_musixmatch.php" );
		bof()->object->core_files->add_key( "class", "lyric_lyricsovh", bof_music_root . "/classes/class_lyric_lyricsovh.php" );
		bof()->object->core_files->add_key( "class", "lyric_azlyrics", bof_music_root . "/classes/class_lyric_azlyrics.php" );
		bof()->object->core_files->add_key( "class", "lyric_lrclib", bof_music_root . "/classes/class_lyric_lrclib.php" );

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

		else
		$this->setup_client();

		$this->setup_artist_manager_role();
		$this->setup_upload();
		$this->setup_cronjob();

	}

	// both ends
	protected function setup_artist_manager_role(){

		bof()->listen( "object_user", "relations_after", function( $method_args, &$relations, $loader ){
			$relations["managed_artists"] = array(

				"bofAdmin" => array(
					"objects" => array(
						"managed_artists_ids" => array(
							"label" => "Managed Artist(s)",
							"column_name" => "managed_artists_ids",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_artist",
									"multi" => true
								),
							),
							"bofAdmin" => array(
								"object" => array(
									"group" => "manager"
								)
							)
						),
					),
				),
				"exec" => array(
					"type" => "direct",
					"parent_object" => "user",
					"parent_object_stats_column" => "s_managed_artists",
					"parent_object_direct_edit_column" => "managed_artists_ids",
					"child_object" => "m_artist",
					"child_object_selector_column" => "manager_id"
				),

			);
		} );
		bof()->listen( "object_user", "bof_admin_after", function( $method_args, &$bof_admin, $loader ){

			$bof_admin["buttons_renderers"] = !empty($bof_admin["buttons_renderers"]) ? $bof_admin["buttons_renderers"] : [];
			$bof_admin["buttons_renderers"][] = function( $item, &$buttons ){

				$buttons["zz10"] = array(
					"label" => "Managed Artists",
					"link" => "music_artists?col_manager={$item["ID"]}"
				);

			};

			$bof_admin["filters"]["role_type"]["input"]["options"][] = [ "artist", "Artist-Manager" ];
			$bof_admin["filters"]["role_type"]["validator"][1]["values"][] = "artist";

			$groups = $bof_admin["object_groups"];
			foreach( $groups as $_g ){
				if ( $_g[0] == "manager" )
				$has_manager_group = true;
			}

			if ( empty( $has_manager_group ) )
			$bof_admin["object_groups"][] = [ "manager", "Manager" ];

		} );
		bof()->listen( "object_user", "selectors_after", function( $method_args, &$method_result, $loader ){

			$method_result["own_artist"] = [ "s_managed_artists", ">", "0" ];

		} );
		bof()->listen( "object_user", "select_role_type_after", function( $method_args, &$method_result, $loader ){

			$val = $method_args[0];

			if ( $val == "artist" )
			$method_result = [ "s_managed_artists", ">", "0" ];

		} );
		bof()->listen( "object_user", "stats_columns_after", function( $method_args, &$columns, $loader ){
			$columns["managed_artists"] = array(
				 "label" => "Managed artists",
			);
		} );

		bof()->listen( "object_user_role", "parse_moderator_roles_pre", function( &$method_args, &$method_result, $loader ){

			list( $roles, $user_id, $user_data ) = $method_args;
			$roles = !empty( $roles ) ? $roles : [];

			$is_artist_owner = !empty( $user_data["s_managed_artists"] );

			if ( $is_artist_owner ){

				$managedArtists = bof()->object->m_artist->select(
					array(
						"manager_id" => $user_id
					),
					array(
						"clean" => false,
						"limit" => false,
					)
				);

				if ( !empty( $managedArtists ) ){

					$managedArtistsIDS = array_map( function( $item ){
						return $item["ID"];
					}, $managedArtists );

					$_moderator = array(
						"type" => "some",
						"objects" => array(
							"m_artist",
							"m_album",
							"m_track",
							"transaction"
						),
						"objects_args" => array(
							"m_artist" => array(
								"edit" => true,
								"list" => true,
								"delete" => true,
								"ID_in" => $managedArtistsIDS
							),
							"m_album" => array(
								"edit" => true,
								"list" => true,
								"delete" => true,
								"col_artist" => $managedArtistsIDS
							),
							"m_track" => array(
								"edit" => true,
								"list" => true,
								"delete" => true,
								"col_artist" => $managedArtistsIDS
							),
							"transaction" => array(
								"list" => true,
								"col_user" => $user_id
							),
						)
					);

					$roles[] = array(
						"ID" => "artist_manager",
						"name" => "Artist Manager",
						"comment" => "",
						"type" => "moderator",
						'bofAdmin_access' => json_encode( $_moderator ),
						'access' => null,
						'comparators' => null,
						'time_add' => null,
						'bofAdmin_access_decoded' => $_moderator
					);

					$method_args[0] = $roles;

				}

			}

		} );
		bof()->listen( "object_user_role", "parse_users_after", function( $method_args, &$method_result, $loader ){

			$user_data = $method_args[0];
			$is_artist_owner = !empty( $user_data["s_managed_artists"] );

			if ( $is_artist_owner ){

				$method_result["_raw"]["artist"] = array(
					'ID' => "artist",
					'name' => "Artist Manager",
					'comment' => "",
					'type' => 'moderator',
					'bofAdmin_access' => null,
					'access' => null,
					'comparators' => null,
					'time_add' => null,
					'bofAdmin_access_decoded' => null,
				);

			}

		} );
		bof()->listen( "object_user_role", "parse_managers_pre", function( &$method_args, $method_result, $loader ){

			$default_manager_role = $loader->object->user_role->select(
				array(
					"type" => "artist",
					"def" => 1
				),
				array(
					"no_bof_time" => true
				)
			);
			$method_args[0]["role_ids"] .= ",{$default_manager_role["ID"]}";

		} );
		bof()->listen( "object_user_role", "columns_after", function( $method_args, &$columns, $loader ){
			$columns["type"]["bofAdmin"]["filters"]["type"]["input"]["options"][] = [ "artist", "Artist Manager" ];
			$columns["type"]["bofAdmin"]["filters"]["type"]["validator"][1]["values"][] = "artist";
			$columns["type"]["input"]["options"][] = [ "artist", "Artist Manager" ];
			$columns["type"]["validator"][1]["values"][] = "artist";
		} );
		bof()->listen( "object_user_role", "parse_user_roles_get_map_after", function( $method_args, &$map, $loader ){
			$map[1][] = "upload_music";
			$map[1][] = "verify_m";
			$map[1][] = "verify_m_aa";
			$map["combine"][] = "verify_m_nur";
			$map["combine"][] = "upload_music_types";
		} );
		bof()->listen( "object_user_role", "bof_admin_after", function( $method_args, &$bof_admin, $loader ){

			$bof_admin["object"]["m_fixed_fee"] = array(
				"label" => "Fixed Transaction Fee",
				"multi" => false,
				"display_on" => array(
					"type" => [ "equal", "artist" ]
				),
				"tip" => "You can set a fixed fee for artist sales. Meaning you will take away this amount of money from manager sales and turn it into profit. For example if a track price is $50 and fixed fee for the manager is $5, user will pay $50, you get $5 and manager gets $45",
				"input" => array(
					"name" => "m_fixed_fee",
					"type" => "digit",
				),
				"validator" => array(
					"int",
					array(
						"empty()",
						"min" => 0,
					)
				),
				"accept_zero" => true
			);
			$bof_admin["object"]["m_dynamic_fee"] = array(
				"label" => "Dynamic Transaction Fee",
				"multi" => false,
				"display_on" => array(
					"type" => [ "equal", "artist" ]
				),
				"tip" => "You can set a fee in percentage for artist sales. Meaning you can take away a part of managers sales as fee and turn it into profit. For example if a track price is $50 and that track's manager fee is 20%, users will pay $50 for the track, you get 20% = $10 and manager gets the rest = $40. If both `Fixed` & `Dynamic` fees are enabled, manager will get ( item_price - fixed_fee ) * ( 1 - ( dynamic_fee / 100 ) ). For example if price is $50, fixed fee is $5 and dynamic fee is 20%, manager will get ( 50 - 5 ) * ( 1 - ( 20 / 100 ) ) = $36",
				"input" => array(
					"name" => "m_dynamic_fee",
					"type" => "digit",
				),
				"validator" => array(
					"int",
					array(
						"empty()",
						"min" => 0,
						"max" => 100
					)
				),
				"accept_zero" => true
			);
			$bof_admin["object"]["m_streaming_royalty"] = array(
				"label" => "Streaming Roaylty",
				"multi" => false,
				"display_on" => array(
					"type" => [ "equal", "artist" ]
				),
				"tip" => "Pay artist managers a fixed amount of money everytime their art work gets streamed",
				"input" => array(
					"name" => "m_streaming_royalty",
					"type" => "digit",
				),
				"validator" => array(
					"float",
					array(
						"empty()",
						"min" => 0,
						"maxPer" => 5
					)
				),
				"accept_zero" => true
			);

			$_old = $bof_admin["object"];
			$bof_admin["object"] = [];
			foreach( $_old as $_k => $_v ){
				$bof_admin["object"][$_k] = $_v;
				if ( $_k == "user_upload" ){
					$bof_admin["object"]["user_upload_music"] = array(
						"label" => "Upload music",
						"tip" => "Users belonging this user-role can upload music to your app",
						"multi" => false,
						"display_on" => array(
							"type" => [ "equal", "user" ],
							"user_upload" => [ "equal", 1 ]
						),
						"input" => array(
							"name" => "user_upload_music",
							"type" => "checkbox"
						),
						"validator" => array(
							"boolean",
							array(
								"empty()"
							)
						)
					);
					$bof_admin["object"]["user_upload_music_types"] = array(
						"label" => "Upload music - sources",
						"tip" => "Users belonging this user-role can upload chosen sources as music to your app",
						"multi" => false,
						"display_on" => array(
							"type" => [ "equal", "user" ],
							"user_upload" => [ "equal", 1 ],
							"user_upload_music" => [ "equal", 1 ],
						),
						"input" => array(
							"name" => "user_upload_music_types",
							"type" => "select_m",
							"options" => array(
								[ "audio", "Audio" ],
								[ "video", "Video" ],
								[ "soundcloud", "SoundCloud" ],
								[ "youtube", "YouTube" ],
							)
						),
						"validator" => array(
							"in_array",
							array(
								"empty()",
								"values" => [ "soundcloud", "youtube", "audio", "video" ],
							)
						)
					);
				}
				elseif ( $_k == "user_verify" ){
					$bof_admin["object"]["user_verify_m"] = array(
						"label" => "Verification - Music manager",
						"tip" => "Users belonging this user-role can submit their data to become artist manager! Then they can earn from their linked artists sales",
						"multi" => false,
						"display_on" => array(
							"type" => [ "equal", "user" ],
							"user_verify" => [ "equal", 1 ]
						),
						"input" => array(
							"name" => "user_verify_m",
							"type" => "checkbox"
						),
						"validator" => array(
							"boolean",
							array(
								"empty()"
							)
						)
					);
					$bof_admin["object"]["user_verify_m_aa"] = array(
						"label" => "Verification - Music manager - Auto approved",
						"tip" => "If checked, users will automatically get approved when they submit their documents",
						"multi" => false,
						"display_on" => array(
							"type" => [ "equal", "user" ],
							"user_verify" => [ "equal", 1 ],
							"user_verify_m" => [ "equal", 1 ],
						),
						"input" => array(
							"name" => "user_verify_m_aa",
							"type" => "checkbox"
						),
						"validator" => array(
							"boolean",
							array(
								"empty()"
							)
						)
					);
					$bof_admin["object"]["user_verify_m_nur"] = array(
						"label" => "Verification - Music manager - Addiotianl user-role",
						"tip" => "If checked, users will be assigned this user-role when their request is approved",
						"multi" => false,
						"display_on" => array(
							"type" => [ "equal", "user" ],
							"user_verify" => [ "equal", 1 ],
							"user_verify_m" => [ "equal", 1 ],
						),
						"bofInput" => array(
							"object",
							array(
								"type" => "user_role",
								"multi" => false,
								"autoload" => false
							)
						),
						"input" => array(
							"name" => "user_verify_m_nur",
						),
					);
				}
			}

			$bof_admin["object"]["user_premium_m_artist"] = array(
				"label" => "Premium Music - Access by artist",
				"tip" => "Users belonging this user-role will have access to premium tracks and albums belonging to selected artists",
				"multi" => false,
				"display_on" => array(
					"type" => [ "equal", "user" ],
					"user_premium" => [ "equal", "some" ]
				),
				"input" => array(
					"name" => "user_premium_m_artist",
				),
				"bofInput" => array(
					"object",
					array(
						"type" => "m_artist",
						"multi" => true,
						"autoload" => false
					)
				)
			);
			$bof_admin["object"]["user_premium_m_genre"] = array(
				"label" => "Premium Music - Access by genre",
				"tip" => "Users belonging this user-role will have access to premium tracks and albums belonging to selected genres",
				"multi" => false,
				"display_on" => array(
					"type" => [ "equal", "user" ],
					"user_premium" => [ "equal", "some" ]
				),
				"input" => array(
					"name" => "user_premium_m_genre",
				),
				"bofInput" => array(
					"object",
					array(
						"type" => "m_genre",
						"multi" => true,
						"autoload" => false
					)
				)
			);
			$bof_admin["object"]["user_premium_m_tag"] = array(
				"label" => "Premium Music - Access by tag",
				"tip" => "Users belonging this user-role will have access to premium tracks and albums belonging to selected tags",
				"multi" => false,
				"display_on" => array(
					"type" => [ "equal", "user" ],
					"user_premium" => [ "equal", "some" ]
				),
				"input" => array(
					"name" => "user_premium_m_tag",
				),
				"bofInput" => array(
					"object",
					array(
						"type" => "m_tag",
						"multi" => true,
						"autoload" => false
					)
				)
			);

			bof()->music->set_cache( "ui_renderer", $bof_admin["object_ui_renderer"] );
			bof()->music->set_cache( "be_renderer", $bof_admin["object_be_renderer"] );
			$bof_admin["object_ui_renderer"] = function( $object, $parsed, $args, $request, &$_inputs, &$data ){

				$cache_ui_renderer_func = bof()->music->get_cache( "ui_renderer", null, true );
				if ( $cache_ui_renderer_func )
				$cache_ui_renderer_func( $object, $parsed, $args, $request, $_inputs, $data );

				$_data = $request["type"] == "single" ? reset( $request["content"] ) : false;

				if ( !empty( $_data["data_decoded"]["artist"] ) ){
					$_inputs["m_fixed_fee"]["input"]["value"] = isset( $_data["data_decoded"]["artist"]["fixed_fee"] ) ? $_data["data_decoded"]["artist"]["fixed_fee"] : null;
					$_inputs["m_dynamic_fee"]["input"]["value"] = isset( $_data["data_decoded"]["artist"]["dyna_fee"] ) ? $_data["data_decoded"]["artist"]["dyna_fee"] : null;
					$_inputs["m_streaming_royalty"]["input"]["value"] = isset( $_data["data_decoded"]["artist"]["streaming_royalty"] ) ? $_data["data_decoded"]["artist"]["streaming_royalty"] : null;
				}

			};
			$bof_admin["object_be_renderer"] = function( &$_inputs, $request ){

				$cache_be_renderer_func = bof()->music->get_cache( "be_renderer", null, true );
				if ( $cache_be_renderer_func )
				$cache_be_renderer_func( $_inputs, $request );

				if ( empty( $_inputs["report"]["fail"] ) ){

					$_data = !empty( $_inputs["set"]["data"] ) ? json_decode( $_inputs["set"]["data"], 1 ) : [];
					$_artist_data = [];
					if ( isset( $_inputs["data"]["m_fixed_fee"] ) ) $_artist_data["fixed_fee"] = $_inputs["data"]["m_fixed_fee"];
					if ( !empty( $_inputs["data"]["m_dynamic_fee"] ) ) $_artist_data["dyna_fee"] = $_inputs["data"]["m_dynamic_fee"];
					if ( !empty( $_inputs["data"]["m_streaming_royalty"] ) ) $_artist_data["streaming_royalty"] = $_inputs["data"]["m_streaming_royalty"];
					$_data["artist"] = $_artist_data;
					$_inputs["set"]["data"] = $_inputs["update"]["data"] = json_encode( $_data );

				}

			};

		} );

		bof()->listen( "object_user_request", "_get_tabs_pre", function( $method_args, &$method_result, $loader ){

			if ( bof()->user->check()->logged ? ( !empty( bof()->user->get()->extra["roles"]["verify"] ) && !empty( bof()->user->get()->extra["roles"]["verify_m"] ) ) : false ){
				$loader->object->user_request->_add_tab( "m_artist", array(
					"becli" => array(
						"endpoint" => "user_verify?tab=m_artist&action=submit"
					),
					"inputs" => array(
						"real_name" => array(
							"required" => true,
							"hook" => "real_name",
							"input" => array(
								"type" => "text"
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
						),
						"stage_name" => array(
							"required" => true,
							"hook" => "stage_name",
							"input" => array(
								"type" => "text"
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
						),
						"document" => array(
							"required" => true,
							"hook" => "attach_document",
							"bofInput" => array(
								"file",
								array(
									"type" => "image",
									"object_type" => "document",
									"object_name" => "user_request",
									"object_id" => null,
									"protect" => true,
									"translate" => true
								)
							),
						),
						"ad" => array(
							"required" => true,
							"hook" => "additional_data",
							"input" => array(
								"type" => "textarea"
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
						),
					),
					"type" => "m_artist",
				) );
			}

		} );
		bof()->listen( "object_ugc_property", "relations_after", function( $method_args, &$relations, $loader ){
			$relations = array_merge( array(
				"artist_subscribers" => array(
	        "exec" => array(
	          "type" => "direct",
	          "parent_object" => "m_artist",
	          "parent_object_stats_column" => "s_subscribers",
	          "child_object" => "ugc_property",
	          "child_object_selector_column" => "object_id",
	          "child_object_where_array" => array(
	            "type" => "subscribe",
	            [ "object_name", "=", "m_artist" ]
	          ),
	          "delete_child_too" => true
	        ),
	      ),
	      "track_likers" => array(
	        "exec" => array(
	          "type" => "direct",
	          "parent_object" => "m_track",
	          "parent_object_stats_column" => "s_likes",
	          "child_object" => "ugc_property",
	          "child_object_selector_column" => "object_id",
	          "child_object_where_array" => array(
	            "type" => "like",
	            [ "object_name", "=", "m_track" ]
	          ),
	          "delete_child_too" => true
	        ),
	      ),
	      "album_likers" => array(
	        "exec" => array(
	          "type" => "direct",
	          "parent_object" => "m_album",
	          "parent_object_stats_column" => "s_likes",
	          "child_object" => "ugc_property",
	          "child_object_selector_column" => "object_id",
	          "child_object_where_array" => array(
	            "type" => "like",
	            [ "object_name", "=", "m_album" ]
	          ),
	          "delete_child_too" => true
	        ),
	      ),
				"album_uploader" => array(
	        "exec" => array(
	          "type" => "direct",
	          "parent_object" => "m_album",
	          "child_object" => "ugc_property",
	          "child_object_selector_column" => "object_id",
	          "child_object_where_array" => array(
	            "type" => "upload",
	            [ "object_name", "=", "m_album" ]
	          ),
	          "delete_child_too" => true
	        ),
	      ),
				"track_uploader" => array(
	        "exec" => array(
	          "type" => "direct",
	          "parent_object" => "m_track",
	          "child_object" => "ugc_property",
	          "child_object_selector_column" => "object_id",
	          "child_object_where_array" => array(
	            "type" => "upload",
	            [ "object_name", "=", "m_track" ]
	          ),
	          "delete_child_too" => true
	        ),
	      ),
			), $relations );
		} );

	}
	protected function setup_upload(){

		bof()->listen( "upload", "setup_after", function( $method_args, &$method_result, $loader ){

			$loader->upload->add_c_type( "music", array(
				"data" => array(
					"ID" => "music",
					"name" => $loader->object->language->turn( "music", [], [ "uc_first" => true, "lang" => "users" ] ),
					"icon" => "music",
					"step4_5" => $loader->object->language->turn( "edit_album", [], [ "uc_first" => true, "lang" => "users" ] ),
					"step5" => $loader->object->language->turn( "edit_tracks", [], [ "uc_first" => true, "lang" => "users" ] )
				),
				"single_object" => "m_track",
				"group_object" => "m_album",
				"creator_object" => "m_artist",
				"sources" => array(
					"supported" => array( "audio", "video", "youtube", "soundcloud" ),
					"data" => array(
						"audio" => array(
							"inputs" => array(
								"file" => array(
									"bofInput" => array(
										"file",
										array(
											"type" => "audio",
											"object_type" => "m_track_source"
										)
									),
								)
							)
						),
						"video" => array(
							"inputs" => array(
								"file" => array(
									"bofInput" => array(
										"file",
										array(
											"type" => "video",
											"object_type" => "m_track_source"
										)
									),
								)
							)
						)
					)
				),
				"inputs" => array(
					"group" => array(),
					"single" => array(),
				),
				"step1.5" => array(
					"album" => array(
						"title" => $loader->object->language->turn( "album", [], [ "uc_first" => true, "lang" => "users" ] ),
						"icon" => "album"
					),
					"single" => array(
						"title" => $loader->object->language->turn( "single_tracks", [], [ "uc_first" => true, "lang" => "users" ] ),
						"icon" => "music-circle-outline"
					)
				),
				"get_group_inputs" => function( $verify_ided=null, $step1_5="single" ){

					$step1_5 = bof()->nest->user_input( "post", "step1_5", "in_array", [ "values" => [ "single", "album" ] ], $step1_5 );

					if ( $step1_5 == "single" )
					return false;

					$inputs = array(

						"cover" => array(
							"label" => "cover",
							"bofInput" => array(
								"file",
								array(
									"type" => "image",
									"object_type" => "m_album_c",
									"object_name" => "m_album"
								)
							),
							"required" => false
						),
						"title" => array(
							"label" => "title",
							"input" => array(
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
						),
						"type" => array(
							"label" => "album_type",
							"input" => array(
								"name" => "type",
								"type" => "select_i",
								"options" => array(
									[ "studio", "studio" ],
									[ "mixtape", "mixtape" ],
									[ "compilation", "compilation" ]
								),
								"value" => "studio"
							),
							"validator" => array(
								"in_array",
								array(
									"values" => [ "studio", "mixtape", "compilation" ]
								)
							)
						),
						"artist_name" => array(
							"label" => "artist_name",
							"input" => array(
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
						),
						"release_date" => array(
							"label" => "release_date",
							"input" => array(
								"type" => "time",
								"time_type" => "ymd"
							),
							"validator" => array(
								"datetime",
							),
						),

						"genres" => array(
							"label" => "genres",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_genre",
									"multi" => true
								)
							),
							"required" => false
						),
						"tags" => array(
							"label" => "tags",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_tag",
									"multi" => true
								)
							),
							"required" => false
						),
						"languages" => array(
							"label" => "languages",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_lang",
									"multi" => true
								)
							),
							"required" => false
						),

						"description" => array(
							"label" => "description",
							"input" => array(
								"name" => "description",
								"type" => "textarea",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
									"strip_emoji" => false,
								),
							),
							"required" => false
						),
						"price" => array(
							"label" => "price",
							"input" => array(
								"name" => "price",
								"type" => "text",
							),
							"validator" => array(
								"float",
								array(
									"empty()",
									"min" => 0,
									"forceZero" => true
								),
							),
							"required" => false
						),

					);

					if ( !empty( $verify_ided ) ){
						foreach( $verify_ided as $verified ){
							if ( !empty( $verified["tags"]["album_title"] ) ){
								$the_verified = $verified["tags"];
							}
						}
					}
					if ( !empty( $the_verified ) ){

						$inputs[ "title" ]["input"]["value"] = !empty( $the_verified["album_title"] ) ? $the_verified["album_title"] : null;
						$inputs[ "artist_name" ]["input"]["value"] = !empty( $the_verified["album_artist_name"] ) ? $the_verified["album_artist_name"] : null;
						$inputs[ "release_date" ]["input"]["value"] = !empty( $the_verified["album_time"] ) ? $the_verified["album_time"] : ( !empty( $inputs[ "release_date" ]["input"]["value"] ) ? $inputs[ "release_date" ]["input"]["value"] : null );

						if ( !empty( $the_verified["cover_string"] ) ){

							$_saveFile = bof()->object->file->handle_string( $the_verified["cover_string"], array(
								"object_type" => "m_album_c"
							) );

							if ( $_saveFile ? $_saveFile[0] : false ){
								$inputs[ "cover" ]["input"]["value"] = $_saveFile[1]["file_id"];
							}

						}

					}

					return $inputs;

				},
				"modify_item_inputs" => function( &$items, $group=null ){

					if ( !$items ? true : !is_array( $items ) )
					return;

					foreach( $items as $id => &$item ){

						if ( empty( $item["ID"] ) || empty( $item["inputs"] ) ) return;
						$inputs = &$item["inputs"];

						$inputs["{$item["ID"]}_genres"]["input"]["value"] = explode( ",", $group["genres"] );
						$inputs["{$item["ID"]}_genres"] = bof()->bofInput->parse( $inputs["{$item["ID"]}_genres"] )["data"];

						$inputs["{$item["ID"]}_tags"]["input"]["value"] = explode( ",", $group["tags"] );
						$inputs["{$item["ID"]}_tags"] = bof()->bofInput->parse( $inputs["{$item["ID"]}_tags"] )["data"];

						$inputs["{$item["ID"]}_release_date"]["input"]["value"] = $group["release_date"];

						$inputs["{$item["ID"]}_price_force_free"]["input"]["type"] = "checkbox";

						unset(
							$inputs["{$item["ID"]}_cover"],
							$inputs["{$item["ID"]}_album_id"],
							$inputs["{$item["ID"]}_album_type"],
							$inputs["{$item["ID"]}_album_order"]["display_on"],
							$inputs["{$item["ID"]}_album_cd"]["display_on"]
						);

					}

				},
				"check_series" => function( $data ){

					$album = bof()->object->m_album->select(array(
						"code" => bof()->general->make_code( [ $data["artist_name"], $data["title"] ] )
					));

					if ( $album )
					return $album;

				},
				"get_item_inputs" => function(){
					return array(

						"cover" => array(
							"label" => "cover",
							"input" => array(),
							"bofInput" => array(
								"file",
								array(
									"type" => "image",
									"object_type" => "m_track_c",
									"object_name" => "m_track"
								)
							),
							"required" => false,
							"group" => "all",
						),
						"title" => array(
							"label" => "title",
							"required" => true,
							"input" => array(
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
							"group" => "basic"
						),
						"artist_name" => array(
							"label" => "artist_name",
							"required" => true,
							"input" => array(
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"strip_emoji" => false,
								),
							),
							"group" => "basic"
						),
						"featured_artists" => array(
							"label" => "featured_artists",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_artist",
									"multi" => true
								)
							),
							"required" => false,
							"group" => "basic"
						),
						"release_date" => array(
							"label" => "release_date",
							"input" => array(
								"type" => "time",
								"time_type" => "ymd"
							),
							"validator" => array(
								"datetime",
							),
							"group" => "basic"
						),
						"description" => array(
							"label" => "description",
							"input" => array(
								"type" => "textarea",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
									"strip_emoji" => false,
								),
							),
							"required" => false,
							"group" => "basic"
						),

						"lyrics" => array(
							"label" => "lyrics",
							"input" => array(
								"type" => "textarea",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
									"strip_emoji" => false,
								),
							),
							"required" => false,
							"group" => "lyrics"
						),

						"album_type" => array(
							"label" => "album_type",
							"input" => array(
								"type" => "select_i",
								"options" => array(
									[ "single", "single" ],
									[ "other", "other" ],
								),
								"value" => "single"
							),
							"validator" => array(
								"in_array",
								array(
									"values" => [ "single", "other" ],
									"empty()"
								)
							),
							"required" => false,
							"group" => "album",
						),
						"album_id" => array(
							"label" => "album",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_album"
								)
							),
							"display_on" => array(
								"album_type" => [ "equal", "other" ]
							),
							"required" => false,
							"group" => "album",
						),
						"album_cd" => array(
							"label" => "album_cd",
							"input" => array(
								"type" => "text",
							),
							"validator" => array(
								"int",
								array(
									"min" => 0,
									"empty()",
									"forceZero" => true
								)
							),
							"display_on" => array(
								"album_type" => [ "equal", "other" ]
							),
							"required" => false,
							"group" => "album"
						),
						"album_order" => array(
							"label" => "album_order",
							"input" => array(
								"type" => "text",
							),
							"validator" => array(
								"int",
								array(
									"min" => 0,
									"empty()",
									"forceZero" => true
								)
							),
							"display_on" => array(
								"album_type" => [ "equal", "other" ]
							),
							"required" => false,
							"group" => "album"
						),

						"genres" => array(
							"label" => "genres",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_genre",
									"multi" => true
								)
							),
							"required" => false,
							"group" => "tags"
						),
						"tags" => array(
							"label" => "tags",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_tag",
									"multi" => true
								)
							),
							"required" => false,
							"group" => "tags"
						),
						"languages" => array(
							"label" => "languages",
							"bofInput" => array(
								"object",
								array(
									"type" => "m_lang",
									"multi" => true
								)
							),
							"required" => false,
							"group" => "tags"
						),

						"price" => array(
							"label" => "price",
							"input" => array(
								"name" => "price",
								"type" => "text",
							),
							"validator" => array(
								"float",
								array(
									"empty()",
									"min" => 0,
									"forceZero" => true
								),
							),
							"required" => false,
							"group" => "price"
						),
						"price_force_free" => array(
							"label" => "price_force_free",
							"input" => array(
								"name" => "price_force_free",
								"type" => "hidden",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()",
								),
							),
							"required" => false,
							"group" => "price"
						),

					);
				},
				"analyze_inputs" => function( $verify_ided, $item_inputs ){

					$step1_5 = bof()->nest->user_input( "post", "step1_5", "in_array", [ "values" => [ "single", "album" ] ], "single" );

					foreach( $verify_ided as &$item ){

						$item["inputs"] = $item_inputs;

						if ( !empty( $item["tags"]["cover_string"] ) &&  $step1_5 == "single" ){

							$_saveFile = bof()->object->file->handle_string( $item["tags"]["cover_string"], array(
								"object_type" => "m_track_c"
							) );

							if ( $_saveFile ? $_saveFile[0] : false )
							$item["inputs"]["cover"]["input"]["value"] = $_saveFile[1]["file_id"];

						}
						elseif ( !empty( $item["data"]["covers"]["standard"]["url"] ) ){

							$_saveFile = bof()->object->file->handle_url( $item["data"]["covers"]["standard"]["url"], array(
								"object_type" => "m_track_c"
							) );

							if ( $_saveFile ? $_saveFile[0] : false )
							$item["inputs"]["cover"]["input"]["value"] = $_saveFile[1]["file_id"];

						}

						if ( !empty( $item["tags"]["title"] ) )
						$item["inputs"]["title"]["input"]["value"] = $item["tags"]["title"];

						if ( !empty( $item["tags"]["artist_name"] ) )
						$item["inputs"]["artist_name"]["input"]["value"] = $item["tags"]["artist_name"];

						if ( !empty( $item["tags"]["description"] ) )
						$item["inputs"]["description"]["input"]["value"] = $item["tags"]["description"];

						if ( !empty( $item["tags"]["lyrics"] ) )
						$item["inputs"]["lyrics"]["input"]["value"] = $item["tags"]["lyrics"];

						if ( !empty( $item["tags"]["album_order"] ) )
						$item["inputs"]["album_order"]["input"]["value"] = $item["tags"]["album_order"];

						if ( !empty( $item["tags"]["album_time"] ) )
						$item["inputs"]["release_date"]["input"]["value"] = $item["tags"]["album_time"];

						$item["groups"] = array(
							[ "basic", "basic" ],
							[ "album", "album" ],
							[ "tags", "tags" ],
							[ "lyrics", "lyrics" ],
							[ "price", "price" ],
						);

					}

					return $verify_ided;

				},
				"submit" => function( $source, $item, $group, $check_form ){

					$hasGroup = !empty( $group );

					if ( !$hasGroup && $item["album_type"] == "single" ){

						$group = array(
							"cover" => $item["cover"],
							"title" => $item["title"],
							"type" => "single",
							"artist_name" => $item["artist_name"],
							"release_date" => $item["release_date"],
							"genres" => $item["genres"],
							"tags" => $item["tags"],
							"languages" => $item["languages"],
							"description" => $item["description"],
							"price" => $item["price"],
						);

						$item["price"] = null;

					}
					elseif ( !$hasGroup && $item["album_type"] == "other" && !empty( $item["album_id"] ) ){

						$get_album = bof()->object->m_album->select(["ID"=>$item["album_id"]]);
						if ( !$get_album )
						throw new bofException( "invalid_data" );

						$album_id = $get_album["ID"];
						$album_artist_id = $get_album["artist_id"];
						$group = $get_album;

					}
					elseif ( !$hasGroup && $item["album_type"] == "other" ){
						$c_source_id = bof()->nest->user_input( "post", "source_id", "string" );
						throw new bofException( "select_an_album", 0, null, array(
							"bad_inputs" => array(
								"{$c_source_id}_album_id"
							)
						) );
					}

					$code = bof()->general->make_code( [ $item["artist_name"], $group["title"], $item["title"] ] );
					if ( bof()->object->m_track->select( [ "code" => $code ] ) ){
						if ( !bof()->music->allow_dups() ){
							throw new bofException( "already_uploaded" );
						}
					}

					// ========= Group ( Album )
					// Artist
					if ( empty( $album_artist_id ) ){

						$code = bof()->general->make_code( $group["artist_name"] );
						$whereArray = array(
							"code" => $code,
						);
						$insertArray = [];

						if ( !bof()->object->m_artist->select_m( $whereArray ) ){
							$insertArray = array(
								"name" => $group["artist_name"],
								"hash" => bof()->object->m_artist->get_free_hash(),
								"code" => $code,
								"seo_url"  => bof()->object->m_artist->get_free_url( $group["artist_name"] ),
							);
						}

						$album_artist_id = bof()->object->m_artist->create( $whereArray, $insertArray, [] );

					}

					// Self
					if ( empty( $album_id ) ){

						$code = bof()->general->make_code( [ $group["artist_name"], $group["title"] . ( $group["type"] == "single" ? "_single" : "" ) ] );
						$whereArray = array( "code" => $code );
						$insertArray = [];

						if ( !bof()->object->m_album->select_m( $whereArray ) ){
							$insertArray = array(
								"title" => $group["title"],
								"hash" => bof()->object->m_album->get_free_hash(),
								"code" => $code,
								"seo_url"  => bof()->object->m_album->get_free_url( $group["artist_name"] . "-" . $group["title"] ),
								"type" => $group["type"],
								"artist_id" => $album_artist_id,
								"cover_id" => $group["cover"] ? $group["cover"] : null,
								"time_release" => $group["release_date"],
								"genre_ids" => $group["genres"],
								"tag_ids" => $group["tags"],
								"language_ids" => $group["languages"],
								"description" => $group["description"] ? bof()->editorjs->editorjsize( $group["description"] ) : null,
								"price" => $group["price"] ? $group["price"] : null,
								"uploader_id" => bof()->user->get()->ID,
							);
						}

						$album_id = bof()->object->m_album->create( $whereArray, $insertArray, [] );

						if ( !bof()->object->ugc_property->select( array(
							"user_id" => bof()->user->get()->ID,
							"type" => "upload",
							"object_name" => "m_album",
							"object_id" => $album_id,
						) ) ){

							bof()->object->ugc_property->insert( array(
								"user_id" => bof()->user->get()->ID,
								"type" => "upload",
								"object_name" => "m_album",
								"object_id" => $album_id,
							) );

						}

					}

					// ========= Item ( track )
					// Artist
					$code = bof()->general->make_code( $item["artist_name"] );
					$whereArray = array(
						"code" => $code,
					);
					$insertArray = [];

					if ( !bof()->object->m_artist->select_m( $whereArray ) ){
						$insertArray = array(
							"name" => $item["artist_name"],
							"hash" => bof()->object->m_artist->get_free_hash(),
							"code" => $code,
							"seo_url"  => bof()->object->m_artist->get_free_url( $item["artist_name"] ),
						);
					}

					$artist_id = bof()->object->m_artist->create( $whereArray, $insertArray, [] );

					// Self
					$code = bof()->general->make_code( [ $item["artist_name"], ( $group["title"] . ( $group["type"] == "single" ? "_s" : "" ) ), $item["title"] ] );
					$whereArray = array();
					if ( !bof()->music->allow_dups() ){
						$whereArray["code"] = $code;
					} else {
						if ( bof()->object->m_track->select_m( array(
							"code" => $code
						) ) ){
							$code = $code . rand(1,9) . rand(1,9) . rand(1,9) . rand(1,9) . rand(1,9) . rand(1,9);
						}
					}
					$insertArray = [];

					$cover_id = null;
					if ( $item["cover"] ) $cover_id = $item["cover"];
					elseif ( $group["cover"] ) $cover_id = $group["cover"];

					if ( !bof()->object->m_track->select_m( $whereArray ) || bof()->music->allow_dups()  ){
						$insertArray = array(
							"title" => $item["title"],
							"hash" => bof()->object->m_track->get_free_hash(),
							"code" => $code,
							"seo_url"  => bof()->object->m_track->get_free_url( $item["artist_name"] . "-" . $group["title"] . "-" . $item["title"] ),
							"artist_id" => $artist_id,
							"cover_id" => $cover_id,
							"time_release" => $item["release_date"],
							"genre_ids" => $item["genres"],
							"tag_ids" => $item["tags"],
							"language_ids" => $item["languages"],
							"description" => $item["description"] ? bof()->editorjs->editorjsize( $item["description"] ) : null,
							"lyrics" => $item["lyrics"] ? $item["lyrics"] : null,
							"price" => $item["price"] ? $item["price"] : null,
							"price_setting" => json_encode(
								array(
									"disable_parent" => $group["price"] ? $item["price_force_free"] : false
								)
							),
							"ft_artist_ids" => $item["featured_artists"],
							"album_id" => $album_id,
							"album_artist_id" => $album_artist_id,
							"album_index" => $item["album_order"] ? $item["album_order"] : null,
							"album_cd" => $item["album_cd"] ? $item["album_cd"] : null,
							"album_price" => $group["price"] ? $group["price"] : null,
							"uploader_id" => bof()->user->get()->ID,
							"duration" => !empty( $item["duration"] ) ? $item["duration"] : null
						);
					}

					$track_id = bof()->object->m_track->create( $whereArray, $insertArray, [] );

					bof()->object->ugc_property->insert( array(
						"user_id" => bof()->user->get()->ID,
						"type" => "upload",
						"object_name" => "m_track",
						"object_id" => $track_id,
					) );

					if ( $source["type"] == "youtube" ){
						$sourceData = array(
							"youtube_id" => $source["id"]
						);
					}
					elseif ( $source["type"] == "soundcloud" ){
						$sourceData = array(
							"soundcloud_id" => $source["embed_id"]
						);
					}
					else {
						$sourceData = array(
							"file_type" => "local",
							"local_file" => $source["file_id"],
						);
					}

					bof()->object->m_track_source->create(
						[],
						array(
							"target_id" => $track_id,
							"type" => $source["type"],
							"data" => $sourceData,
						),
						[]
					);

				},
				"fill_item_inputs" => function( $inputs, $object_name, $object_hash ){

					$object_item = bof()->object->__get( $object_name )->select( array(
						"hash" => $object_hash,
						"uploader_id" => bof()->user->get()->ID
					),array (
						"_eq" => array(
							"genres" => [],
							"tags" => [],
							"ft_artists" => [],
							"langs" => []
						)
					) );

					if ( empty( $object_item ) )
					return false;

					if ( !empty( $object_item["cover_id"] ) )
					$inputs["cover"]["input"]["value"] = $object_item["cover_id"];

					$inputs["cover"]["group"] = "basic";

					$inputs["release_date"]["input"]["value"] = $object_item["time_release"] ? substr( $object_item["time_release"], 0, 10 ) : null;
					$inputs["title"]["input"]["value"] = $object_item["title"];
					$inputs["description"]["input"]["value"] = !empty( $object_item["description_html"] ) ? strip_tags($object_item["description_html"],"") : null;
					$inputs["lyrics"]["input"]["value"] = $object_item["lyrics"];
					$inputs["album_cd"]["input"]["value"] = $object_item["album_cd"];
					$inputs["album_order"]["input"]["value"] = $object_item["album_index"];
					$inputs["price"]["input"]["value"] = $object_item["price"];
					$inputs["genres"]["input"]["value"] = $object_item["bof_rel_genres"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_genres"] ) ) : null;
					$inputs["tags"]["input"]["value"] = $object_item["bof_rel_tags"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_tags"] ) ) : null;
					$inputs["languages"]["input"]["value"] = $object_item["bof_rel_langs"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_langs"] ) ) : null;
					$inputs["featured_artists"]["input"]["value"] = $object_item["bof_rel_ft_artists"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_ft_artists"] ) ) : null;
					$inputs["genre_ids"] = $inputs["genres"];
					$inputs["tag_ids"] = $inputs["tags"];
					$inputs["lang_ids"] = $inputs["languages"];
					$inputs["ft_artist_ids"] = $inputs["featured_artists"];
					$inputs["cover_id"] = $inputs["cover"];

					unset( $inputs["artist_name"], $inputs["album_type"], $inputs["album_id"], $inputs["artist_name"], $inputs["price_force_free"], $inputs["price"], $inputs["languages"], $inputs["genres"], $inputs["tags"], $inputs["featured_artists"], $inputs["cover"] );
					return array(
						"inputs" => $inputs,
						"groups" => array(
							[ "basic", "basic" ],
							[ "album", "album" ],
							[ "tags", "tags" ],
							[ "lyrics", "lyrics" ],
						)
					);

				},
				"fill_group_inputs" => function( $inputs, $object_name, $object_hash ){

					$object_item = bof()->object->__get( $object_name )->select( array(
						"hash" => $object_hash,
						"uploader_id" => bof()->user->get()->ID
					),array (
						"_eq" => array(
							"genres" => [],
							"tags" => [],
							"ft_artists" => [],
							"langs" => []
						)
					) );

					if ( empty( $object_item ) )
					return false;

					if ( !empty( $object_item["cover_id"] ) )
					$inputs["cover"]["input"]["value"] = $object_item["cover_id"];

					$inputs["release_date"]["input"]["value"] = $object_item["time_release"] ? substr( $object_item["time_release"], 0, 10 ) : null;
					$inputs["title"]["input"]["value"] = $object_item["title"];
					$inputs["description"]["input"]["value"] = !empty( $object_item["description_html"] ) ? strip_tags($object_item["description_html"],"") : null;

					$inputs["genres"]["input"]["value"] = $object_item["bof_rel_genres"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_genres"] ) ) : null;
					$inputs["tags"]["input"]["value"] = $object_item["bof_rel_tags"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_tags"] ) ) : null;
					$inputs["languages"]["input"]["value"] = $object_item["bof_rel_langs"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_langs"] ) ) : null;
					//$inputs["featured_artists"]["input"]["value"] = $object_item["bof_rel_ft_artists"] ? implode( ",", array_map( function( $val ){ return $val["ID"]; }, $object_item["bof_rel_ft_artists"] ) ) : null;

					$inputs["genre_ids"] = $inputs["genres"];
					$inputs["tag_ids"] = $inputs["tags"];
					$inputs["lang_ids"] = $inputs["languages"];
					//$inputs["ft_artist_ids"] = $inputs["featured_artists"];
					$inputs["cover_id"] = $inputs["cover"];

					unset( $inputs["artist_name"], $inputs["type"], $inputs["price_force_free"], $inputs["price"], $inputs["languages"], $inputs["genres"], $inputs["tags"], $inputs["featured_artists"], $inputs["cover"] );

					return array(
						"inputs" => $inputs,
						"groups" => array(
							[ "basic", "basic" ],
						)
					);

				}

			) );

		} );

		bof()->listen( "source", "get_contents_after", function( $method_args, &$method_result, $loader ){
			$method_result["m_track_source"] = $loader->object->__get( "m_track_source" );
		} );

	}

	// admin
	protected function setup_admin(){

		$this->setup_bofAdmin();
		$this->setup_admin_app_pages();
		$this->setup_admin_highlights();
		$this->setup_admin_settings();
		$this->setup_admin_endpoints();

	}
	protected function setup_bofAdmin(){

		bof()->bofAdmin->_add_object( "m_artist", [ "social_links" => true, "biography" => true ] );
		bof()->bofAdmin->_add_object( "m_cronjob", [ "seo" => false ] );
		bof()->bofAdmin->_add_object( "m_genre" );
		bof()->bofAdmin->_add_object( "m_lang" );
		bof()->bofAdmin->_add_object( "m_album" );
		bof()->bofAdmin->_add_object( "m_track" );
		bof()->bofAdmin->_add_object( "m_track_source", [ "seo" => false ] );
		bof()->bofAdmin->_add_object( "m_tag" );
		bof()->listen( "object_menu", "get_app_pages_after", function( $args, &$pages ){
			$pages["user_verify?tab=m_artist"] = "Artist-verification page";
		} );

	}
	protected function setup_admin_app_pages(){
		bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

			if ( is_array( $method_result ) ){

				$method_result[ "music_automation" ] = array(
					"title" => "Music Automation",
					"url" => "^music_automation$",
					"link" => "music_automation",
					"theme_file" => "parts/content_setting",
					"extenders" => (object) array(
						"m_automation_css" => (object) array(
							"type" => "css",
							"name" => "m_automation_css",
							// "path" => admin_endpoint_address . ( bof()->object->core_setting->get( "nginx_server" ) ? "m_automation_css" : "m_automation.css" ),
							"path" => web_address . "plugins/bof_music/assets/admin_music_automation.css",
							"dir" => false
						),
						"m_automation_js" => (object) array(
							"type" => "js",
							"name" => "m_automation_js",
							// "path" => admin_endpoint_address . ( bof()->object->core_setting->get( "nginx_server" ) ? "m_automation_js" : "m_automation.js" ),
							"path" => web_address . "plugins/bof_music/assets/admin_music_automation.js",
							"dir" => false
						)
					),
					"events" => (object) array(
						"ready" => "m_automation_js.set",
						"unloading" => "m_automation_js.unset",
					),
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/setting/music_automation/",
							"key" => "setting"
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_artists" ] = array(
					"title" => "Music Artists",
					"url" => "^music_artists$",
					"link" => "music_artists",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_artist/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_artist" ] = array(
					"title" => "Music Artist",
					"url" => "^music_artist\/(.*?)$",
					"link" => "music_artist",
					"link_par" => "music_artists",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_artist/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_albums" ] = array(
					"title" => "Music Albums",
					"url" => "^music_albums$",
					"link" => "music_albums",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_album/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_album" ] = array(
					"title" => "Music Album",
					"url" => "^music_album\/(.*?)$",
					"link" => "music_album",
					"link_par" => "music_albums",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_album/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_tracks" ] = array(
					"title" => "Music Tracks",
					"url" => "^music_tracks$",
					"link" => "music_tracks",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_track/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_track" ] = array(
					"title" => "Music Track",
					"url" => "^music_track\/(.*?)$",
					"link" => "music_track",
					"link_par" => "music_tracks",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_track/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_track_sources" ] = array(
					"title" => "Music Sources",
					"url" => "^music_sources$",
					"link" => "music_sources",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_track_source/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_track_source" ] = array(
					"title" => "Music Source",
					"url" => "^music_source\/(.*?)$",
					"link" => "music_source",
					"link_par" => "music_sources",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_track_source/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_langs" ] = array(
					"title" => "Music Languages",
					"url" => "^music_langs$",
					"link" => "music_langs",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_lang/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_lang" ] = array(
					"title" => "Music Language",
					"url" => "^music_lang\/(.*?)$",
					"link" => "music_lang",
					"link_par" => "music_langs",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_lang/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_genres" ] = array(
					"title" => "Music Genres",
					"url" => "^music_genres$",
					"link" => "music_genres",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_genre/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_genre" ] = array(
					"title" => "Music Genre",
					"url" => "^music_genre\/(.*?)$",
					"link" => "music_genre",
					"link_par" => "music_genres",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_genre/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_tags" ] = array(
					"title" => "Music tags",
					"url" => "^music_tags$",
					"link" => "music_tags",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_tag/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_tag" ] = array(
					"title" => "Music tag",
					"url" => "^music_tag\/(.*?)$",
					"link" => "music_tag",
					"link_par" => "music_tags",
					"theme_file" => "parts/content_single",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_tag/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

				$method_result[ "music_cronjobs" ] = array(
					"title" => "Music Cron Jobs",
					"url" => "^music_cronjobs$",
					"link" => "music_cronjobs",
					"theme_file" => "parts/content_table",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/list/m_cronjob/?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
					"__sb_family" => "content",
				);
				$method_result[ "music_cronjob" ] = array(
					"title" => "Music Cron Job",
					"url" => "^music_cronjob\/(.*?)$",
					"link" => "music_cronjob",
					"link_par" => "music_cronjobs",
					"theme_file" => "parts/content_single",
					"extenders" => (object) array(
            "admin_music_cronjob_js" => (object) array(
              "type" => "js",
              "name" => "admin_music_cronjob",
              // "path" => admin_endpoint_address . ( bof()->object->core_setting->get( "nginx_server" ) ? "admin_music_cronjob_js" : "admin_music_cronjob.js" ),
							"path" => web_address . "plugins/bof_music/assets/admin_music_cronjob.js",
              "dir" => false
            ),
						"admin_music_cronjob_css" => (object) array(
              "type" => "css",
              "name" => "admin_music_cronjob",
              // "path" => admin_endpoint_address . ( bof()->object->core_setting->get( "nginx_server" ) ? "admin_music_cronjob_css" : "admin_music_cronjob.css" ),
							"path" => web_address . "plugins/bof_music/assets/admin_music_cronjob.css",
              "dir" => false
            ),
          ),
					"events" => (object) array(
            "ready" => "admin_music_cronjob.set",
            "unloading" => "admin_music_cronjob.unset",
          ),
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/object/m_cronjob/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
							"key" => "entity",
						)
					),
					"__sb_family" => "content",
				);

			}

		} );
	}
	protected function setup_admin_highlights(){

		bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {

			$sb_family = $method_args[0];

			bof()->highlights->add_extension_link(array(
				"title" => "Music",
				"link" => "music_automation",
				"icon" => "library_music"
			));

			$highlights = bof()->highlights->getData();

			$highlights["users_links"]["items"]["users_links"]["args"]["childs"][] = array(
				"icon"  => "verified",
				"title" => "Artist Managers",
				"link"  => "user_list?role_type=artist"
			);
			$highlights["users_links"]["items"]["users_requests"]["args"]["childs"][] = array(
				"icon"  => "verified",
				"title" => "Artist-Manager Requests",
				"link"  => "user_requests?type=m_artist"
			);

			$highlights["setting_cron_links"]["items"]["cronjob_links"]["args"]["childs"] = array_merge(
				array_slice($highlights["setting_cron_links"]["items"]["cronjob_links"]["args"]["childs"], 0, 1),
				array(
					array(
						"icon"  => "library_music",
						"title" => "Music - Cronjobs",
						"link"  => "music_cronjobs"
					),
					array(
						"title" => "Music - Automation",
						"icon" => "audio_file",
						"link" => "music_automation"
					),
				),
				array_slice($highlights["setting_cron_links"]["items"]["cronjob_links"]["args"]["childs"], 1)
			);

			$highlights[ "scrappers_sec" ][ "items" ][ "scrapper_links" ][ "args" ][ "childs" ][] = array(
				"title" => "AzLyrics Scrapper",
				"icon" => "pest_control",
				"link" => "music_automation"
			);
			
			bof()->highlights->setData($highlights);

			if ($sb_family == "content") {
				bof()->highlights
					->new_item("content_stats", array(
						"icon" => "music_note",
						"title" => "Tracks",
						"tip" => "Music tracks",
						"value" => number_format($loader->object->m_track->count([], []))
					));
			}

			if ($sb_family == "users") {
				bof()->highlights
					->new_item("users_stats", array(
						"icon" => "verified",
						"title" => "Artist Managers",
						"tip" => "Number of artist-managers",
						"value" => number_format($loader->object->user->count(["own_artist" => 1], []))
					));
			}

			bof()->highlights
				->new_item("content_links", array(
					"icon" => "music_note",
					"title" => "Music",
					"ID" => "music",
					"childs" => array(
						array(
							"title" => "List Artists",
							"icon" => "mic",
							"link" => "music_artists"
						),
						array(
							"title" => "List Albums",
							"icon" => "album",
							"link" => "music_albums"
						),
						array(
							"title" => "List Tracks",
							"icon" => "audiotrack",
							"link" => "music_tracks"
						),
						array(
							"title" => "List Sources",
							"icon" => "save",
							"link" => "music_sources"
						),
						array(
							"title" => "List Tags",
							"icon" => "language",
							"link" => "music_tags"
						),
						array(
							"title" => "List Genres",
							"icon" => "category",
							"link" => "music_genres"
						),
						array(
							"title" => "List Languages",
							"icon" => "category",
							"link" => "music_langs"
						),
						array(
							"title" => "Automation",
							"icon" => "smart_toy",
							"link" => "music_automation"
						),
						array(
							"title" => "Cronjobs",
							"icon" => "precision_manufacturing",
							"link" => "music_cronjobs"
						),
					)
				), false);
		});
		bof()->listen("bofAdmin", "dash_cards", function ($method_args, &$method_result, $loader) {
			$method_result[] = array(
				"title" => "Music Cronjobs",
				"icon" => "precision_manufacturing",
				"value" => bof()->object->m_cronjob->count([], [])
			);
			$method_result[] = array(
				"title" => "Music Managers",
				"icon" => "verified",
				"value" => bof()->object->m_artist->count(["has_manager" => 1], [])
			);
		});
	}
	protected function setup_admin_settings(){

		bof()->bofAdmin->_add_setting( "music_automation", array(
			"groups" => array(
				"spotify" => array(

					"title" => "Spotify",
					"icon" => "source",

					"inputs" => array(

						"spotify_client_id" => array(
							"title" => "Client ID",
							"tip" => "Client ID is the unique identifier of your application. <a href=\"https://developer.spotify.com/documentation/general/guides/app-settings/\">more info</a>",
							"col_name" => "spotify_client_id",
							"input" => array(
								"name"          => "spotify_client_id",
								"type"        => "text",
								"placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),

						"spotify_client_key" => array(
							"title" => "Client Secret",
							"tip" => "Client Secret is the key that you pass in secure calls to the Spotify Accounts and Web API services. <a href=\"https://developer.spotify.com/documentation/general/guides/app-settings/\">more info</a>",
							"col_name" => "spotify_client_key",
							"input" => array(
								"name" => "spotify_client_key",
								"type" => "text",
								"placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),

						"spotify_automation" => array(
							"title" => "Full Automation",
							"tip" => "Click <a href='https://support.busyowl.co/documentation/music-automation' target='_blank'>here</a> for documentation. Script will include Spotify search results in website search results. Albums and artists tracks will be fetched from Spotify as users explore your website",
							"col_name" => "spotify_automation",
							"input" => array(
								"name" => "spotify_automation",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()"
								)
							)
						),

					)
				),
				"lyrics" => array(

					"title" => "Lyrics",
					"icon" => "lyrics",

					"inputs" => array(

						"lyrics_source" => array(
							"title" => "Automated Lyrics Source",
							"tip" => "Choose the API source you wish to use for automated lyrics<br><br>
							<a href='https://docs.musixmatch.com/lyrics-api/introduction' target='_blank' style='color:rgb(var(--theme_color))'>MusixMatch</a>: Based on official API. Requires API key. Lyrics can't be saved in database. Free version returns 30% of lyrics<br>
							<a href='https://azlyrics.com' target='_blank' style='color:rgb(var(--theme_color))'>AZLyrics</a>: Based on scrapper. Lyrics are saved in database. <b><i>( For personal use only )</i></b><br>
							<a href='https://lyrics.ovh' target='_blank' style='color:rgb(var(--theme_color))'>Lyrics.ovh</a>: Based on official API. Lyrics are saved in database. Completely free",
							"col_name" => "lyrics_source",
							"input" => array(
								"name" => "lyrics_source",
								"type" => "select_i",
								"options" => array(
									[ "none", "Disabled" ],
									[ "musixmatch", "Musixmatch" ],
									[ "azlyrics", "AZLyrics" ],
									[ "lyricsovh", "lyrics.ovh" ]
								),
								"value" => "none"
							),
							"validator" => array(
								"in_array",
								array(
									"values" => [ "none", "musixmatch", "azlyrics", "lyricsovh" ]
								)
							)
						),

						"musixmatch_api_key" => array(
							"title"    => "Musixmatch API Key",
							"tip"      => "Musixmatch API Key. <a href=\"https://developer.musixmatch.com/documentation\">more info</a>",
							"col_name" => "musixmatch_api_key",
							"input" => array(
								"name"        => "musixmatch_api_key",
								"type"        => "text",
								"placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),

						"azlyrics_proxy" => array(
							"title"    => "AZLyrics Proxy",
							"tip"      => "AZLyrics doesn't have official API. You can define a dedicated proxy for it here. Expected format: <b>type://ip:port</b>. Example: socks5://22.22.22.22:2080 or http://ramin:khaz@32.23.32.23:8080",
							"col_name" => "azlyrics_proxy",
							"input" => array(
								"name"        => "azlyrics_proxy",
								"type"        => "text",
								"placeholder" => "type://u:p@ip:port",
							),
							"validator" => array(
								"string",
								array(
									"empty()",
								)
							)
						),

					)
				),
				"youtube" => array(

					"title" => "Youtube",
					"icon" => "youtube_activity",

					"inputs" => array(

						"youtube_api_keys" => array(
							"title" => "API Key",
							"tip" => "Enter your 'YouTube Data API v3' API key(s)",
							"col_name" => "youtube_api_keys",
							"input" => array(
								"name" => "youtube_api_keys",
								"type" => "textarea",
								"placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),

						"youtube_api_regionCode" => array(
							"title" => "API Region code",
							"tip" => "Youtube requires a region-code to present search results. By default it's US. You can change it to a valid <a href='https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes'>Alpha-2 Country Code</a>",
							"col_name" => "youtube_api_regionCode",
							"input" => array(
								"name" => "youtube_api_regionCode",
								"type" => "text",
								"placeholder" => "US",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),

						"youtube_automation" => array(
							"title" => "Enable YouTube as Music Source",
							"tip" => "If enabled, the system will automatically search for and use YouTube videos as the source for tracks that do not have a YouTube source already. This ensures that users can listen to music directly from YouTube if no other source is available",
							"col_name" => "youtube_automation",
							"input" => array(
								"name" => "youtube_automation",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()"
								)
							)
						),

						"youtube_recheck" => array(
							"title" => "Enable Replacment of Broken YouTube Videos",
							"tip" => "If enabled, the system will automatically report broken or unavailable YouTube videos back to the server. This allows the system to find and replace broken videos, ensuring continuous availability of music for users. You can <a href='player_setting'>click here</a> to set limits",
							"col_name" => "youtube_recheck",
							"input" => array(
								"name" => "youtube_recheck",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()"
								)
							)
						),

						"youtube_remove" => array(
							"title" => "Enforce 30-Day YouTube ID Storage Limit",
							"tip" => "If enabled, the system will automatically delete stored YouTube IDs after 30 days to comply with YouTube API policies",
							"col_name" => "youtube_remove",
							"input" => array(
								"name" => "youtube_remove",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()"
								)
							)
						),

					)
				),
				"youtube_dl" => array(

					"title" => "Youtube Download",
					"tip" => "Download the video ( using <a href='https://youtube-dl.org/' target='_blank'>Youtube-dl</a> ), convert it to MP3 file ( using <a href='https://ffmpeg.org/' target='_blank'>FFmpeg</a> ) and play that instead of playing the Youtube video for users. It can be illegal depending on your location and usage. Read Youtube terms for more data",
					"icon" => "youtube_activity",

					"inputs" => array(
						"ut" => array(
							"title" => "Active",
							"tip" => "Make sure you have installed and fully tested FFmpeg & youtube-dl and also defined them in <a href='http://localhost:666/admin/cli_setting'>CLI-apps setting page</a>",
							"col_name" => "ut",
							"input" => array(
								"name" => "ut",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()"
								)
							)
						),
					)
				),
				"soundcloud" => array(

					"title" => "Soundcloud",
					"icon" => "cloud",

					"inputs" => array(

						"soundcloud_api_id" => array(
							"title" => "Client ID",
							"col_name" => "soundcloud_api_id",
							"input" => array(
								"name"          => "soundcloud_api_id",
								"type"        => "text",
								"placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),
						"soundcloud_api_key" => array(
							"title" => "Client Secret",
							"col_name" => "soundcloud_api_key",
							"input" => array(
								"name" => "soundcloud_api_key",
								"type" => "text",
								"placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
							),
							"validator" => array(
								"string",
								array(
									"strict" => true,
									"empty()",
								)
							)
						),

						"soundcloud_automation" => array(
							"title" => "Enable SoundCloud as Music Source",
							"tip" => "If enabled, the system will automatically search for and use SoundCloud tracks as the source for tracks that do not have a SoundCloud source already. This ensures that users can listen to music directly from SoundCloud if no other source is available",
							"col_name" => "soundcloud_automation",
							"input" => array(
								"name" => "soundcloud_automation",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()"
								)
							)
						),

					)
				),
			),
			"functions" => array(
				"ui_pre" => function( $groups ){
					if ( defined("bof_tool_spotify_multi_accounts") ){
						$groups["spotify"]["inputs"]["spotify_client_id"]["tip"] = "<b style='color:rgb(var(--c_red))'>You have installed <a href='music_spotify_accounts'>'Spotify Multiple Account'</a> tool. Visit the tool's setting page to define client-id & client-sercret</b>";
						$groups["spotify"]["inputs"]["spotify_client_id"]["input"]["type"] = "empty";
						$groups["spotify"]["inputs"]["spotify_client_key"]["input"]["type"] = "hidden";
					}
					return $groups;
				}
			)
	    ) );

		bof()->listen( "bofAdmin", "_get_stats_after", function( $method_args, &$list ){
			$list["music"] = array(
				"title" => "Music",
				"icon" => "album",
				"functions" => array(
					"exe_item" => function ($stats_name, $item_type, $item_name, $item_data) {

						if ($item_name == "cards") {

							$cards = array();
							$cards[] = array(
								"icon" => "mic",
								"title" => "Artists",
								"value" => bof()->object->m_artist->count([], [])
							);
							$cards[] = array(
								"icon" => "album",
								"title" => "Albums",
								"value" => bof()->object->m_album->count([], [])
							);
							$cards[] = array(
								"icon" => "library_music",
								"title" => "Tracks",
								"value" => bof()->object->m_track->count([], [])
							);
							$cards[] = array(
								"icon" => "category",
								"title" => "Tags",
								"value" => bof()->object->m_tag->count([], [])
							);
							$cards[] = array(
								"icon" => "category",
								"title" => "Genres",
								"value" => bof()->object->m_genre->count([], [])
							);
							$cards[] = array(
								"icon" => "language",
								"title" => "Languages",
								"value" => bof()->object->m_lang->count([], [])
							);
							$cards[] = array(
								"icon" => "play_circle",
								"title" => "Track Sources",
								"value" => bof()->object->m_track_source->count([], [])
							);

							$item_data["cards"] = $cards;
						}

						return $item_data;
					}
				),
				"rows" => array(
					array(
						"cards" => array(
							"size" => "12",
							"id" => "cards"
						)
					),
					array(
						"new_albums" => array(
							"size" => "6",
							"id" => "new_albums"
						),
						"album_types" => array(
							"size" => "6",
							"id" => "album_types"
						),
					),
					array(
						"new_artists" => array(
							"size" => "6",
							"id" => "new_artists"
						),
						"new_tracks" => array(
							"size" => "6",
							"id" => "new_tracks"
						),
					),
					array(
						"new_track_sources" => array(
							"size" => "6",
							"id" => "new_track_sources"
						),
						"track_sources_types" => array(
							"size" => "6",
							"id" => "track_sources_types"
						),
					),
					array(
						"track_sources_downloadable" => array(
							"size" => "6",
							"id" => "track_sources_downloadable"
						),
						"track_sources_streamable" => array(
							"size" => "6",
							"id" => "track_sources_streamable"
						),
					),
				),
				"items" => array(
					"cards" => array(
						"col" => "cards",
						"type" => "cards",
						"cards" => array()
					),

					"new_albums" => array(
						"col" => "new_albums",
						"type" => "graph",
						"title" => "New Albums",
						"graph" => array(
							"type" => "xy_basic",
							"table" => "_c_m_albums",
							"xy_basic_time_col" => "time_add",
							"xy_basic_val_col" => "COUNT(*)",
						),
					),
					"album_types" => array(
						"col" => "album_types",
						"type" => "graph",
						"title" => "Album Types",
						"graph" => array(
							"type" => "pie_basic",
							"table" => "_c_m_albums",
							"pie_var_col" => "type",
							"pie_val_col" => "COUNT(*)",
							"pie_time_col" => "time_add"
						),
					),
					"new_artists" => array(
						"col" => "new_artists",
						"type" => "graph",
						"title" => "New Artists",
						"graph" => array(
							"type" => "xy_basic",
							"table" => "_c_m_artists",
							"xy_basic_time_col" => "time_add",
							"xy_basic_val_col" => "COUNT(*)",
						),
					),
					"new_tracks" => array(
						"col" => "new_tracks",
						"type" => "graph",
						"title" => "New Tracks",
						"graph" => array(
							"type" => "xy_basic",
							"table" => "_c_m_tracks",
							"xy_basic_time_col" => "time_add",
							"xy_basic_val_col" => "COUNT(*)",
						),
					),
					"new_tracks_sources" => array(
						"col" => "new_track_sources",
						"type" => "graph",
						"title" => "New Track Sources",
						"graph" => array(
							"type" => "xy_basic",
							"table" => "_c_m_tracks_sources",
							"xy_basic_time_col" => "time_add",
							"xy_basic_val_col" => "COUNT(*)",
						),
					),
					"track_sources_types" => array(
						"col" => "track_sources_types",
						"type" => "graph",
						"title" => "Track Sources",
						"tip" => "By type",
						"graph" => array(
							"type" => "pie_basic",
							"table" => "_c_m_tracks_sources",
							"pie_var_col" => "type",
							"pie_val_col" => "COUNT(*)",
							"pie_time_col" => "time_add"
						),
					),
					"track_sources_downloadable" => array(
						"col" => "track_sources_downloadable",
						"type" => "graph",
						"title" => "Track Sources",
						"tip" => "Download-able",
						"graph" => array(
							"type" => "pie_basic",
							"table" => "_c_m_tracks_sources",
							"pie_var_col" => "download_able",
							"pie_val_col" => "COUNT(*)",
							"pie_time_col" => "time_add",
							"labels" => array(
								"1" => "Yes",
								"-2" => "No",
								"-1" => "Only in-app",
							)
						),
					),
					"track_sources_streamable" => array(
						"col" => "track_sources_streamable",
						"type" => "graph",
						"title" => "Track Sources",
						"tip" => "Stream-able",
						"graph" => array(
							"type" => "pie_basic",
							"table" => "_c_m_tracks_sources",
							"pie_var_col" => "stream_able",
							"pie_val_col" => "COUNT(*)",
							"pie_time_col" => "time_add",
							"labels" => array(
								"1" => "Yes",
								"-2" => "No",
								"-1" => "Only in-app",
							)
						),
					),

				),
			);
		} );

	}
	protected function setup_admin_endpoints(){

		bof()->object->endpoint->add( "spotify_browse_endpoint", array(
          "url" => "spotify_browse_endpoint",
          "groups" => [ "admin" ],
          "executers" => array(
            bof_music_root . "/endpoints/endpoint_spotify_browse_endpoint.php"
          )
	    ) );

    }

	// client
	protected function setup_client(){
		$this->setup_bofClient();
		bof()->listen("_custom", "muse_report", function ($args, $_rep, $loader, $rep) {

			extract($args);
			if ($object_type != "m_track" || $source["type"] != "youtube")
			return;

			if ( !bof()->object->db_setting->get( "youtube_recheck" ) )
			return;

			$artist = bof()->object->m_artist->sid($item["artist_id"]);

			$_nd = array(
				"title" => $item["title"],
				"sub_title" => $artist["name"],
				"duration" => $item["duration"]
			);

			$request_video = bof()->youtube->find_video($_nd);

			if ( !$request_video[0] )
			return;

			bof()->object->m_track_source->delete(["ID"=>$source["ID"]]);
			bof()->object->m_track_source->insert(array(
				"target_id" => $item["ID"],
				"type" => "youtube",
				"data" => json_encode( [ "youtube_id" => $request_video[1] ] ),
				"stream_able" => 1,
				"download_able" => -2,
				"encrypted" => 0
			));

			$rep = !empty( $rep ) ? $rep : [];
			$rep["new_youtube_id"] = $request_video[1];
			return $rep;

		});
	}
	protected function setup_bofClient(){

		bof()->bofClient->_add_object( "m_album" );
		bof()->bofClient->_add_object( "m_artist" );
		bof()->bofClient->_add_object( "m_track" );
		bof()->bofClient->_add_object( "m_genre" );
		bof()->bofClient->_add_object( "m_lang" );
		bof()->bofClient->_add_object( "m_tag" );
		$this->setup_spotify_search();

	}

	// cronjobs
	protected function setup_spotify_search(){

		bof()->listen( "search", "exe_after", function( $args, $output ){
			$args = $args[0];
			if ( bof()->object->db_setting->get( "spotify_automation" ) ){
				$iis = 0;
				$query = $args["query"];
				$search_spotify = bof()->spotify_helper->search( $query, false );
				foreach( [ "artist", "album" ] as $_k ){
					if ( !empty( $search_spotify["{$_k}s"]["items"] ) ){
						$organic = [];
						foreach( $search_spotify["{$_k}s"]["items"] as $_sr ){
							$_t = $_sr[ "name" ];
							similar_text( $_t, $query, $_sr_sim );
							if ( $_sr_sim > 70 ){

								$coverArray = [];
								$_spotify_covers_raw = [];
								$iis++;

								if ( bof()->object->__get( "m_{$_k}" )->select(
									array(
										"spotify_id" => $_sr["id"],
									),
									array(
										"limit" => 1,
										"clean" => false,
										"single" => true
									)
								) ) continue;
								
								if (
									!empty($_sr["images"])
								) {
									foreach ($_sr["images"] as $_s_cover)
									$_spotify_covers_raw[$_s_cover["url"]] = [$_s_cover["width"], $_s_cover["height"]];
									$coverArray["image_strings"] = bof()->image->html($_spotify_covers_raw);
								}

								$organic[] = array(
									"title" => $_t,
									"sub_title" => "hi!",
									"cover" => !empty($coverArray) ? $coverArray["image_strings"][4]["html"] : null,
									"id" => uniqid(),
									"ot" => "m_" . $_k,
									"url" => "external_music/{$_sr["id"]}?source=spotify&type={$_k}&id={$_sr["id"]}",
									"hash" => md5(uniqid()),
									"classes" => "no_buttons no_action"
								);

							}
						}
						if ( !empty( $organic ) ){
							if ( empty( $output["widgets"]["m_{$_k}"] ) ){
								$output["widgets"]["m_{$_k}"] = array(
									"ID" => "m_{$_k}",
									"display" => array(
										"type" => "slider",
										"title" => bof()->object->language->turn( "m_{$_k}" ),
										"pagination" => false,
										"slider_size" => "medium",
										"slider_rows" => 1,
										"slider_mason" => false,
										"o_type" => "m_{$_k}",
										"classes" => "type_slider c_o_type_m_{$_k} o_type_m_{$_k} has_title no_bg_img size_medium rows_1 liquid linked search_result_widget"
									),
									"object" => array(
										"name" => "m_{$_k}"
									),
									"items" => array(
										1 => []
									),
									"buttons" => array()
								);
							}
							$output["widgets"]["m_{$_k}"]["items"][1] = array_merge(
								$organic,
								$output["widgets"]["m_{$_k}"]["items"][1]
							);
						}
					}
				}
				if ( $iis && !empty( $output["widgets"]["nada"] ) )
				unset( $output["widgets"]["nada"] );
			}
			return $output;
		} );

	}
	protected function setup_cronjob(){

		bof()->listen( "cronjob", "_clean_database_get_map_after", function( $method_args, &$map, $loader ){

			$map["_c_m_tracks_sources"] = [];
			$map["_c_m_tracks_relations"] = [];
			$map["_c_m_tracks"] = [];
			$map["_c_m_tags"] = [];
			$map["_c_m_langs"] = [];
			$map["_c_m_genres"] = [];
			$map["_c_m_events"] = [];
			$map["_c_m_cronjobs"] = [];
			$map["_c_m_cronjobs_spotify"] = [];
			$map["_c_m_artists_relations"] = [];
			$map["_c_m_artists"] = [];
			$map["_c_m_albums_relations"] = [];
			$map["_c_m_albums"] = [];

		} );

		bof()->listen( "cronjob", "get_jobs_after", function( $method_args, &$jobs, $loader ){

			if ( !bof()->plugin_exists("youtube") )
			bof()->plugin("youtube");

			$m_cronjobs = bof()->object->m_cronjob->select(
				array(
					"active" => 1
				),
				array(
					"limit" => false,
					"single" => false,
					"clean" => false
				)
			);

			if ( $m_cronjobs ){
				foreach( $m_cronjobs as $m_cronjob ){
					$jobs["music_{$m_cronjob["ID"]}"] = array(
						"title" => "Music Plugin - {$m_cronjob["name"]}",
						"interval" => $m_cronjob["execution_interval"],
						"exe" => function( $PID, $GID, $loader, $args ){
							return $loader->music->cron_runner( $args["cronjob_id"], $PID, $GID );
						},
						"args" => array(
							"cronjob_id" => $m_cronjob["ID"]
						)
					);
				}
			}

			if ( bof()->object->db_setting->get( "youtube_remove" ) ){
				$jobs["music_youtube_remove"] = array(
					"title" => "Music Plugin - Youtube Remover",
					"interval" => 24*60,
					"exe" => function( $PID, $GID ){

						$sources = bof()->object->m_track_source->select(
							array(
								"type" => "youtube",
								[ "time_add", "<", "SUBDATE( now(), INTERVAL 30 DAY )", true ]
							),
							array(
								"limit" => false,
								"single" => false,
								"clean" => false
							)
						);

						if ( !$sources )
						fall( "No outdated videos", [ "skipped" => true ] );

						$c=0;

						foreach( $sources as $source ){
							bof()->object->m_track_source->delete(
								array(
									"ID" => $source["ID"]
								)
							);
							bof()->cronjob->log_p( $PID, $GID, "Removed {$source["ID"]}" );
							$c++;
						}

						return "Removed {$c} outdated videos";

					}
				);
			}

			$jobs["music_youtube_downloader"] = array(
				"title" => "Music Plugin - Youtube Downloader",
				"interval" => 1,
				"exe" => function( $PID, $GID, $loader ){

					$pendings = $loader->object->m_track_source->select(
						array(
							"queue" => 1,
							"type" => "audio"
						),
						array(
							"limit" => 5,
							"single" => false,
							"clean" => false
						)
					);

					if ( !$pendings )
					fall( "No pending downloads", [ "skipped" => true ] );

					$done = 0;
					foreach( $pendings as $pending ){
						$data = json_decode( $pending["data"], true );
						if ( ( $data["file_type"] ?? "" ) != "youtube_pending" ) continue;
						if ( empty( $data["youtube_id"] ) ) continue;

						$loader->cronjob->log_p( $PID, $GID, "Downloading {$data["youtube_id"]} for track {$pending["target_id"]}" );

						// Mark as downloading
						$loader->object->m_track_source->update(
							array( "ID" => $pending["ID"] ),
							array( "queue" => 2 )
						);

						try {
							$download_and_convert = $loader->youtube->download( $data["youtube_id"] );
							
							$rules = $loader->object->file->get_rules("audio", "m_track_source", ["get_host" => true]);
							$convert_file_id = $loader->object->file->insert(
								array(
									"type" => "audio",
									"host_id" => "1",
									"dest_host_id" => $rules["file_host"],
									"path" => $loader->object->file->clean_path($download_and_convert, true),
									"object_type" => "m_track_source",
								)
							);

							$loader->object->m_track_source->update(
								array( "ID" => $pending["ID"] ),
								array(
									"data" => json_encode(array(
										"file_type" => "local",
										"local_file" => $convert_file_id,
									)),
									"queue" => 1 // Set back to 1 for _bgp processing
								)
							);
							
							$done++;
						} catch( Exception $err ){
							$loader->cronjob->log_p( $PID, $GID, "Failed to download {$data["youtube_id"]}: " . $err->getMessage() );
							$loader->object->m_track_source->update(
								array( "ID" => $pending["ID"] ),
								array( "queue" => 0, "data" => json_encode(array("error" => $err->getMessage())) )
							);
						}
					}

					return "Downloaded {$done} tracks";
				}
			);

		} );

	}
	public function _cli( $string ){
		if ( $this->PID && $this->GID )
		bof()->cronjob->log_p( $this->PID, $this->GID, $string );
	}
	public function cron_runner( $cronjob_id, $PID, $GID ){

		bof()->spotify_helper->set_die_on_issue( false );
		bof()->spotify_helper->record( true );
		bof()->spotify_helper->set_record_data( array(
			"sync_genres" => true,
			"create_album_get_artist_for_genres" => true,
			"create_track_get_artist_for_genres" => true,
			"update_album_get_artist_for_genres" => true
		) );
		bof()->spotify_helper->setup();
		bof()->spotify->set_config( "req_interval", defined("stu") ? 0.05 : 0.22 );
		bof()->spotify->set_config( "cache_reset", true );
		bof()->spotify->set_config( "cache_age", 1 );
		bof()->db->reset_cache();

		$this->CID = $cronjob_id;
		$this->PID = $PID;
		$this->GID = $GID;

		$cronjob = bof()->object->m_cronjob->select(
			array(
				"ID" => $cronjob_id
			),
			array(
				"parse" => true,
			)
		);

		if ( empty( $cronjob["api_ids_parsed"] ) ? true : !is_array( $cronjob["api_ids_parsed"] ) )
		fall( "No queued item", [ "skipped" => true ] );

		$default_job_args = array( "check" => true );
		$cronjob_args = !empty( $cronjob["data_decoded"] ) ? array_merge( $cronjob["data_decoded"], $default_job_args ) : $default_job_args;

		bof()->spotify_helper->set_a_record_data( "sync_genres", true );
		if ( $cronjob_args ? ( in_array( "sync_genres", array_keys( $cronjob_args ), true ) ? empty( $cronjob_args["sync_genres"] ) : false ) : false )
		bof()->spotify_helper->set_a_record_data( "sync_genres", false );

		$ok=0;
		foreach( $cronjob["api_ids_parsed"] as $spotify_id ){

			bof()->db->reset_cache();

			try {

				if ( $cronjob["api_name"] == "spotify" && $cronjob["object_type"] == "artist" ) $local_id = bof()->spotify_helper->get_artist( $spotify_id, $cronjob_args );
				if ( $cronjob["api_name"] == "spotify" && $cronjob["object_type"] == "album" ) $local_id = bof()->spotify_helper->get_album( $spotify_id, $cronjob_args );
				if ( $cronjob["api_name"] == "spotify" && $cronjob["object_type"] == "track" ) $local_id = bof()->spotify_helper->get_track( $spotify_id, $cronjob_args );
				if ( $cronjob["api_name"] == "spotify" && $cronjob["object_type"] == "playlist" ) $local_id = bof()->spotify_helper->get_playlist( $spotify_id, $cronjob_args );
				if ( $cronjob["api_name"] == "spotify" && $cronjob["object_type"] == "cat_lists" ) $local_id = bof()->spotify_helper->get_cat_playlists( $spotify_id, $cronjob_args );
				if ( $cronjob["api_name"] == "spotify" && $cronjob["object_type"] == "user_lists" ) $local_id = bof()->spotify_helper->get_user_playlists( $spotify_id, $cronjob_args );
				if ( $cronjob["api_name"] == "youtube" && $cronjob["object_type"] == "track" ){
					$local_id = bof()->youtube_helper->get_track_video( $spotify_id, $cronjob_args, bof()->music );
					$spotify_id = $spotify_id["spotify_id"];
				}

				if ( $cronjob["api_name"] != "spotify" ){
					$out = array(
						"local_id" => null,
						"spotify_id" => null,
						"item" => $spotify_id
					);
					bof()->call( "_custom", "music_cronjob_item", array_merge( $cronjob, array(
						"PID" => $PID,
						"GID" => $GID
					) ), $out );
					if ( !empty( $out["local_id"] ) ){
						$local_id = $out["local_id"];
						$spotify_id = $out["spotify_id"];
					}
				}

				if ( empty( $local_id ) || $local_id == "noRecord?" || $local_id == "noYoutubeID" )
				throw new Exception("NoRecord");
				$ok++;
			} catch( Exception $err ){
				$this->_cli( "======> FAILED: " . $err->getMessage() );
				$local_id = null;
			}

			if ( $local_id != "skip_record" ){
				bof()->object->m_cronjob_spotify->create(
					[ "ID" => "{$cronjob_id}_{$spotify_id}" ],
					array(
						"cron_id" => $cronjob_id,
						"spotify_id" => $spotify_id,
						"time_check" => bof()->general->mysql_timestamp(),
						"local_id" => $local_id
					),
					array(
						"cron_id" => $cronjob_id,
						"spotify_id" => $spotify_id,
						"time_check" => bof()->general->mysql_timestamp(),
						"local_id" => $local_id
					)
				);
			}

			bof()->db->_update( array(
				"table" => "_bof_setting",
				"set" => array(
					[ "time_update", "now()", true ]
				),
				"where" => array(
					[ "var", "=", "crond_stat" ]
				)
			) );

		}

		bof()->spotify_helper->record( false );

		return "Checked {$ok} item(s)";

	}

	// other
	public function is_blacklisted( $objectType, $whereArray, $caller ){

		return false;

		if ( !in_array( $objectType, [ "artist", "album", "track", "playlist" ], true ) )
		fall( "Class_music: is_blacklisted: invalid object_type: {$objectType}" );

		$blacklist = [];
		$blacklist_raw = bof()->object->db_setting->get( "m_blacklist_{$objectType}" );
		if ( $blacklist_raw ){
			foreach( $blacklist_raw as $blacklist_line ){
				$blacklist_line_exploded = explode( ":", $blacklist_line );
				$blacklist_line_type = $blacklist_line_exploded[0];
				$blacklist_line_hook = implode( ":", array_slice( $blacklist_line_exploded, 1 ) );
				$blacklist[ $blacklist_line_type ][] = $blacklist_line_hook;
			}
		}

		foreach( $whereArray as $_k => $_v ){
			if ( !empty( $blacklist[ $_k ] ) ? in_array( $_v, $blacklist[ $_k ], true ) : false )
			return true;
		}

		return false;

	}
	public function hide_single_albums(){
		return bof()->getName() == "bof_client" && ( defined( "music_hide_single_albums" ) ? music_hide_single_albums : false );
	}
	public function allow_dups(){
		return defined("allow_music_tracks_dups") ? allow_music_tracks_dups : false;
	}

	public function get_user_subscriptions( $user_id = null ){

		if ( !$user_id ) $user_id = bof()->user->get()->ID;
		if ( !$user_id ) return [];

		$subs = bof()->object->user_subs->select(
			array(
				"user_id" => $user_id,
				"has_time" => 1,
			),
			array(
				"single" => false,
				"limit" => false,
				"clean" => false,
			)
		);

		if ( !$subs ) return [];
		return is_array( $subs ) ? $subs : [ $subs ];
	}

	public function get_user_features( $user_id = null ){

		$subs = $this->get_user_subscriptions( $user_id );
		$features = [];

		foreach( $subs as $sub ){
			$plan = bof()->object->user_subs_plan->select( array( "ID" => $sub["subs_plan_id"] ) );
			if ( !$plan ) continue;
			$_features = [];
			if ( !empty( $plan["features_decoded"] ) ) $_features = $plan["features_decoded"];
			elseif ( !empty( $plan["data_decoded"]["features"] ) ) $_features = $plan["data_decoded"]["features"];
			foreach( $_features as $k => $v ){
				if ( !isset( $features[$k] ) ) $features[$k] = $v;
				elseif ( is_bool( $features[$k] ) && $v ) $features[$k] = true;
				elseif ( is_numeric( $v ) && $v > $features[$k] ) $features[$k] = $v;
			}
		}

		return $features;
	}

	public function has_feature( $feature, $user_id = null ){

		$features = $this->get_user_features( $user_id );
		return !empty( $features[ $feature ] );
	}

	public function set_track_youtube_id( $track_id, $youtube_id ){

		if ( !$track_id || !$youtube_id || !is_string( $youtube_id ) ) return;

		bof()->db->_update( array(
			"table" => "_c_m_tracks",
			"where" => array( [ "ID", "=", $track_id ] ),
			"set"   => array( [ "youtube_id", $youtube_id ] )
		) );

	}

	public function track_loudness( $track, $measure=true ){

		// $track: _c_m_tracks row array or track ID
		$track_id = is_array( $track ) ? ( !empty( $track["ID"] ) ? $track["ID"] : null ) : $track;
		if ( !$track_id ) return null;

		$lufs = $peak = null;

		if ( is_array( $track ) ){
			if ( isset( $track["lufs"] ) && $track["lufs"] !== null ) $lufs = (float) $track["lufs"];
			if ( isset( $track["peak_db"] ) && $track["peak_db"] !== null ) $peak = (float) $track["peak_db"];
		}

		if ( $lufs === null ){
			$_raw = bof()->db->_select( array(
				"table" => "_c_m_tracks",
				"where" => array( [ "ID", "=", $track_id ] ),
				"columns" => "lufs, peak_db",
				"single" => true
			) );
			if ( $_raw ){
				if ( $_raw["lufs"] !== null ) $lufs = (float) $_raw["lufs"];
				if ( $_raw["peak_db"] !== null ) $peak = (float) $_raw["peak_db"];
			}
		}

		if ( $lufs === null ){

			// Resolve through the track's local audio source -> file data
			$sources = bof()->object->m_track_source->select(
				array(
					"target_id" => $track_id,
					"type" => "audio"
				),
				array(
					"single" => false,
					"limit" => 20,
					"clean" => false,
					"cache_load" => false
				)
			);

			if ( $sources ){
				foreach( $sources as $source ){

					$_src_data = !empty( $source["data_decoded"] ) ? $source["data_decoded"] : ( !empty( $source["data"] ) ? json_decode( $source["data"], true ) : null );
					$_file_id = !empty( $_src_data["local_file"] ) ? $_src_data["local_file"] : null;
					if ( !$_file_id ) continue;

					$_file = bof()->object->file->select(
						array( "ID" => $_file_id ),
						array( "clean" => false, "cache_load_rt" => false )
					);

					$_fdata0 = !empty( $_file["data_decoded"] ) ? $_file["data_decoded"] : ( !empty( $_file["data"] ) ? json_decode( $_file["data"], true ) : null );
					if ( !empty( $_fdata0["lufs"] ) ){
						$lufs = (float) $_fdata0["lufs"];
						if ( isset( $_fdata0["peak_db"] ) && $_fdata0["peak_db"] !== null )
						$peak = (float) $_fdata0["peak_db"];
						break;
					}

					// Measure on the fly when the file is stored locally as a real audio file
					if ( $measure && $_file["host_id"] == 1 && !empty( $_file["path"] ) && !in_array( $_file["extension"], [ "m3u8", "ts" ], true ) ){

						static $_measured_this_request = 0;
						if ( !bof()->response->getType( "cli" ) ){
							if ( $_measured_this_request >= 2 ) break;
							$_measured_this_request++;
						}

						$_abs = base_root . "/" . $_file["path"];
						if ( !is_file( $_abs ) ) continue;

						$_res = bof()->ffmpeg->measure_loudness( $_abs );
						if ( !$_res ) continue;

						$lufs = $_res["lufs"];
						$peak = $_res["peak_db"];

						$_fdata = !empty( $_fdata0 ) ? $_fdata0 : [];
						$_fdata["lufs"] = $lufs;
						$_fdata["peak_db"] = $peak;
						bof()->object->file->update(
							array( "ID" => $_file["ID"] ),
							array( "data" => json_encode( $_fdata ) )
						);

						break;
					}

				}
			}

		}

		if ( $lufs === null ) return null;

		// Persist on the track row so later lookups are cheap
		if ( is_array( $track ) ? ( !isset( $track["lufs"] ) || $track["lufs"] === null ) : true ){
			bof()->db->_update( array(
				"table" => "_c_m_tracks",
				"where" => array( [ "ID", "=", $track_id ] ),
				"set" => array(
					[ "lufs", $lufs ],
					[ "peak_db", $peak ]
				)
			) );
		}

		return array( "lufs" => $lufs, "peak_db" => $peak );

	}
}

?>