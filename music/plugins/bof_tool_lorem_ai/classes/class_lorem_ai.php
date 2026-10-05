<?php

if ( !defined( "bof_root" ) ) die;

class lorem_ai {

	protected $supported_objects = null;
	protected $active_setting_object = null;
	protected $cronjob_cache = array(
		"logs" => []
	);
	protected $item_limit = 6;

	public function get_active_setting_object(){
		return $this->active_setting_object;
	}
	public function get_supported_objects(){
		$objects = $this->supported_objects;
		foreach( $objects as $oName => &$oArgs ){
			$oArgs["inputs"] = array_merge( !empty( $oArgs["inputs"] ) ? $oArgs["inputs"] : [], array(
				"seo_slug" => array(
					"prompt" => "Create a search engine friendly, relevant SEO-SLUG",
					"label" => "SEO - Slug"
				),
				"seo_title" => array(
					"prompt" => "Create a short, search engine friendly title",
					"label" => "SEO - Title"
				),
				"seo_description" => array(
					"prompt" => "Create a short, search engine friendly description",
					"label" => "SEO - Description"
				),
				"seo_tags" => array(
					"prompt" => "Create 3 search engine friendly tags. Separate with ,",
					"label" => "SEO - Tags"
				),
			) );
		}
		return $objects;
	}
	public function get_logs(){
		return $this->cronjob_cache["logs"];
	}
	public function set_item_limit( $num ){
		$this->item_limit = $num;
	}

	public function setup(){

		$this->supported_objects = array(
			"m_artist" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Cyberpunk digital art of the music artist %query%, vivid colors, futuristic",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for music artist %query%",
						"type" => "img",
						"img_size" => "large"
					),
					"bio_birthday" => array(
						"prompt" => "Birthday of artist in yyyy/mm/dd format",
					),
					"bio_city" => array(
						"prompt" => "Name birth cirty of artist",
					),
					"bio_content" => array(
						"prompt" => "Create an artist Biography",
					),
					"bio_country" => array(
						"prompt" => "Name birth country of artist",
					),
					"bio_deathday" => array(
						"prompt" => "If artist is dead, return death day in yyyy/mm/dd format",
					),
					"bio_name" => array(
						"prompt" => "Real (legal) name of artist",
					),
					"m_artist_genres" => array(
						"prompt" => "Artist genres. Separate with ;",
					),
					"m_artist_tags" => array(
						"prompt" => "5 Tags for artist. Separate with ;"
					),
				),
				"prompt" => "You are given an artist name. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( in_array( $action, [ "bio_city", "bio_country", "bio_name" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, $value ];

					if ( in_array( $action, [ "bio_birthday", "bio_deathday" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, date( "Y-m-d", strtotime( $value ) ) ];

					if ( $action == "bio_content" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "m_artist_genres", "m_artist_tags", "m_artist_langs" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, substr( $action, strlen("m_artist_"), -1 ), $value, $overwrite );
					}

				},
			),
			"m_album" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Cyberpunk digital art of the music album `%query%`, vivid colors, futuristic",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for music album `%query%`",
						"type" => "img",
						"img_size" => "large"
					),
					"description" => array(
						"prompt" => "Background story of album",
					),
					"time_release" => array(
						"prompt" => "Release time of album in yyyy/mm/dd format",
					),
					"m_album_genres" => array(
						"prompt" => "Album genres. Separate with ;",
					),
					"m_album_tags" => array(
						"prompt" => "5 Tags for album. Separate with ;"
					),
				),
				"prompt" => "You are given an `artist name - album name`. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					$artist = bof()->object->m_artist->sid( $item["artist_id"] );
					return "{$artist["name"]} - {$item["title"]}";
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( in_array( $action, [ "time_release" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, date( "Y-m-d", strtotime( $value ) ) ];

					if ( $action == "description" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "m_album_genres", "m_album_tags", "m_album_langs" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, substr( $action, strlen("m_album_"), -1 ), $value, $overwrite );
					}

				},
			),
			"m_track" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Cyberpunk digital art of the music track `%query%`, vivid colors, futuristic",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for music track `%query%`",
						"type" => "img",
						"img_size" => "large"
					),
					"description" => array(
						"prompt" => "Background story of track",
					),
					"time_release" => array(
						"prompt" => "Release time of track in yyyy/mm/dd format",
					),
					"m_track_genres" => array(
						"prompt" => "Track genres. Separate with ;",
					),
					"m_track_tags" => array(
						"prompt" => "5 Tags for track. Separate with ;"
					),
				),
				"prompt" => "You are given an `artist name - track name`. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					$artist = bof()->object->m_artist->sid( $item["artist_id"] );
					return "{$artist["name"]} - {$item["title"]}";
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( in_array( $action, [ "time_release" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, date( "Y-m-d", strtotime( $value ) ) ];

					if ( $action == "description" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "m_track_genres", "m_track_tags", "m_track_langs" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, substr( $action, strlen("m_track_"), -1 ), $value, $overwrite );
					}

				},
			),
			"m_genre" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a cover inspired by elements of '%query%' genre",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a music genre. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"p_show" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Cyberpunk digital art of the podcast show `%query%`, vivid colors, futuristic",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for podcast show `%query%`",
						"type" => "img",
						"img_size" => "large"
					),
					"description" => array(
						"prompt" => "About the podcast",
					),
					"p_show_categories" => array(
						"prompt" => "Podcast categories. Separate with ;",
					),
					"p_show_tags" => array(
						"prompt" => "5 Tags for podcast. Separate with ;"
					),
				),
				"prompt" => "You are given a `podcast name`. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					$creator = bof()->object->p_podcaster->sid( $item["creator_id"] );
					return $item["title"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( $action == "description" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "p_show_categories", "p_show_tags" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, $action == "p_show_tags" ? "tag" : "category", $value, $overwrite );
					}

				},
			),
			"p_podcaster" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Cyberpunk digital art of the podcaster %query%, vivid colors, futuristic",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for podcaster %query%",
						"type" => "img",
						"img_size" => "large"
					),
					"bio_birthday" => array(
						"prompt" => "Birthday of podcaster in yyyy/mm/dd format",
					),
					"bio_city" => array(
						"prompt" => "Name birth cirty of podcaster",
					),
					"bio_content" => array(
						"prompt" => "Create an podcaster Biography",
					),
					"bio_country" => array(
						"prompt" => "Name birth country of podcaster",
					),
					"bio_deathday" => array(
						"prompt" => "If podcaster is dead, return death day in yyyy/mm/dd format",
					),
					"bio_name" => array(
						"prompt" => "Real (legal) name of podcaster",
					),
					"p_podcaster_categories" => array(
						"prompt" => "podcaster categories. Separate with ;",
					),
					"p_podcaster_tags" => array(
						"prompt" => "5 Tags for podcaster. Separate with ;"
					),
				),
				"prompt" => "You are given an podcaster name. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( in_array( $action, [ "bio_city", "bio_country", "bio_name" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, $value ];

					if ( in_array( $action, [ "bio_birthday", "bio_deathday" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, date( "Y-m-d", strtotime( $value ) ) ];

					if ( $action == "bio_content" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "p_podcaster_categories", "p_podcaster_tags" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, $action == "p_podcaster_categories" ? "category" : "tag", $value, $overwrite );
					}

				},
			),
			"p_tag" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a cover inspired by elements of '%query%' tag",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a podcast tag. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"p_category" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a cover inspired by elements of '%query%' category",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a podcast category. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"r_station" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Cyberpunk digital art of the radio station `%query%`, vivid colors, futuristic",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for radio statiooon `%query%`",
						"type" => "img",
						"img_size" => "large"
					),
					"description" => array(
						"prompt" => "About radio station",
					),
					"r_station_categories" => array(
						"prompt" => "Radio categories. Separate with ;",
					),
				),
				"prompt" => "You are given a `radio station name`. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return "{$item["title"]}";
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( $action == "description" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "r_station_categories" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, "category", $value, $overwrite );
					}

				},
			),
			"r_category" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a painting inspired by elements of '%query%' category",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a category. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"r_country" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a painting inspired by elements of '%query%' country",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a country. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"r_city" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a painting inspired by elements of '%query%' city",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a city. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"r_language" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a painting inspired by elements of '%query%' language",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a language. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"a_book" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Victorian-era painting of book `%query%`. A mixture of old and modern",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "A wallpaper in victorian-era style, of book `%query%`. A mixture of old and modern",
						"type" => "img",
						"img_size" => "large"
					),
					"description" => array(
						"prompt" => "Background story of book",
					),
					"time_publish" => array(
						"prompt" => "Release time of book in yyyy/mm/dd format",
					),
					"a_book_genres" => array(
						"prompt" => "Book genres. Separate with ;",
					),
					"a_book_tags" => array(
						"prompt" => "5 Tags for book. Separate with ;"
					),
					"a_book_languages" => array(
						"prompt" => "Main language of book ISO code"
					),
				),
				"prompt" => "You are given an `writer name - book name`. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					$writer = bof()->object->a_writer->select( ["a_book_writers"=>$item["ID"]] );
					return "{$writer["name"]} - {$item["title"]}";
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( in_array( $action, [ "time_publish" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, date( "Y-m-d", strtotime( $value ) ) ];

					if ( $action == "description" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "a_book_genres", "a_book_tags", "a_book_languages" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, substr( $action, strlen("a_book_"), -1 ), $value, $overwrite );
					}

				},
			),
			"a_writer" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Victorian-era painting of writer %query%, face portrait, A mixture of old and modern",
						"type" => "img",
						"img_size" => "medium"
					),
					"bg_id" => array(
						"prompt" => "Create a wallpaper for writer %query%, victorian-era",
						"type" => "img",
						"img_size" => "large"
					),
					"bio_birthday" => array(
						"prompt" => "Birthday of writer in yyyy/mm/dd format",
					),
					"bio_city" => array(
						"prompt" => "Name birth cirty of writer",
					),
					"bio_content" => array(
						"prompt" => "Create an writer biography",
					),
					"bio_country" => array(
						"prompt" => "Name birth country of writer",
					),
					"bio_deathday" => array(
						"prompt" => "If writer is dead, return death day in yyyy/mm/dd format",
					),
					"bio_name" => array(
						"prompt" => "Real (legal) name of writer",
					),
					"a_writer_genres" => array(
						"prompt" => "writer genres. Separate with ;",
					),
					"a_writer_tags" => array(
						"prompt" => "5 Tags for writer. Separate with ;"
					),
					"a_writer_languages" => array(
						"prompt" => "writer languages. Separate with ;"
					),
				),
				"prompt" => "You are given an writer name. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){

					if ( in_array( $action, [ "bio_city", "bio_country", "bio_name" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, $value ];

					if ( in_array( $action, [ "bio_birthday", "bio_deathday" ], true ) && ( $overwrite || empty( $item[ $action ] ) ) )
					return [ $action, date( "Y-m-d", strtotime( $value ) ) ];

					if ( $action == "bio_content" ){
						$_v = $value ? json_decode( $value, true ) : false;
						$_v = !empty( $_v["html"] );
						if ( $_v || $overwrite )
						return [ $action, bof()->editorjs->editorjsize( $value ) ];
					}

					if ( in_array( $action, [ "a_writer_genres", "a_writer_tags", "a_writer_languages" ], true ) ){
						return bof()->lorem_ai->handle_insert_tags( $object, $item, substr( $action, strlen("a_writer_"), -1 ), $value, $overwrite );
					}

				},
			),
			"a_genre" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a painting inspired by elements of '%query%' genre",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a book genre. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
			"a_tag" => array(
				"inputs" => array(
					"cover_id" => array(
						"prompt" => "Create a painting inspired by elements of '%query%' genre",
						"type" => "img",
						"img_size" => "medium"
					),
				),
				"prompt" => "You are given a book tag. Analyze for following information and return them in JSON format",
				"query_function" => function( $item ){
					return $item["name"];
				},
				"insert_function" => function( $item, $action, $value, $overwrite, $object ){},
			),
		);

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

		$this->setup_cronjob();

	}
	protected function setup_admin(){

		$this->setup_admin_highlight();
		$this->setup_admin_app_pages();
		$this->setup_admin_endpoints();
		$this->setup_object_buttons();

		bof()->listen( "bofAdmin", "setting_pre", function( $method_args ){

			if ( bof()->general->startsWith( $method_args[0], "lorem_ai_" ) && !in_array( $method_args[0], [ "lorem_ai_self", "lorem_ai_aiapi", "lorem_ai_playground" ], true ) ){
				$_on = substr( $method_args[0], 9 );
				if ( bof()->nest->validate( $_on, "in_array", [ "values" => array_keys( $this->supported_objects ) ] ) )
				$this->setup_admin_object_settings( $_on );
			}

			if ( $method_args[0] == "lorem_ai_self" )
			$this->setup_admin_self_settings();

			if ( $method_args[0] == "lorem_ai_aiapi" )
			$this->setup_admin_aiapi_settings();

		} );

	}
	protected function setup_cronjob(){

		bof()->listen( "cronjob", "get_jobs_after", function( $method_args, &$jobs, $loader ){

			if ( !bof()->object->db_setting->get("lorem_ai") )
			return;

			bof()->call( "lorem_ai", "setup_cronjob" );

			$jobs["lorem_ai"] = array(
				"title" => "Lorem AI",
				"interval" => 5,
				"exe" => function( $PID, $GID ){
					bof()->lorem_ai->run_cronjob( $PID, $GID );
				}
			);

		} );

	}
	protected function setup_object_buttons(){

		bof()->listen( "bofAdmin", "object_list_after", function( $method_args, $method_result ){
			$type = $method_args[0];
			if ( in_array( $type, array_keys( $this->get_supported_objects() ), true ) ){
				$data = bof()->response->json->get();
				if ( !empty( $data["items"] ) ){
					foreach( $data["items"] as &$item ){
						$item["buttons"]["z_lorem"] = array(
							"label" => "Lorem AI - Run",
							"link" => "lorem_ai_item/{$type}/{$item["ID"]}/",
						);
					}
				}
				bof()->response->json->set($data);
			}
		} );

	}
	protected function cronjob_log( $string ){

		if ( empty( $this->cronjob_cache["PID"] ) || empty( $this->cronjob_cache["GID"] ) ){
			$this->cronjob_cache["logs"][] = $string;
			return;
		}

		bof()->cronjob->log_p( $this->cronjob_cache["PID"], $this->cronjob_cache["GID"], $string );

	}

	protected function setup_admin_highlight(){

		bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){

			$sb_family = $method_args[0];

			if ( $sb_family == "extensions" ){

				$so_childs = array(
					array(
						"title" => "Cronjob",
						"icon" => "settings",
						"link" => "lorem_ai_setting"
					),
					array(
						"title" => "AI API setting",
						"icon" => "settings",
						"link" => "lorem_ai_aiapi"
					)
				);

				$_dff = bof()->general->get_full_fall();
				bof()->general->set_full_fall(false);

				foreach( $this->get_supported_objects() as $so_name => $so_args ){
					try {
						$so_self = bof()->object->__get( $so_name );
						$so_childs[] = array(
							"title" => $so_self->bof()["label"],
							"icon" => $so_self->bof()["icon"],
							"link" => "lorem_ai/{$so_name}"
						);
					} catch( bofException|Exception $err ){
						continue;
					}
				}

				$so_childs[] = array(
					"title" => "Playground",
					"icon" => "stream",
					"link" => "lorem_ai_playground"
				);

				bof()->general->set_full_fall( $_dff );

				bof()->highlights
				->new_item( "extensions_links", array(
					"icon" => "psychology",
					"title" => "Lorem AI",
					"ID" => "lorem_ai",
					"ext_classes" => "small",
					"childs" => $so_childs
				), false );
			}

		} );

	}
	protected function setup_admin_app_pages(){
		bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

			$method_result[ "lorem_ai_item" ] = array(
				"title" => "Lorem AI - Item Runner",
				"url" => "^lorem_ai_item\/(.*?)\/(.*?)\/$",
				"link" => "lorem_ai_item",
				"theme_file" => "parts/content_setting",
				"extenders" => (object) array(
					"bof_lorem_ai_item_css" => (object) array(
						"type" => "css",
						"name" => "bof_lorem_ai_item",
						"path" => web_address . "plugins/bof_tool_lorem_ai/assets/lorem_ai_item.css",
						"dir" => false
					),
					"bof_lorem_ai_item_js" => (object) array(
						"type" => "js",
						"name" => "bof_lorem_ai_item",
						"path" => web_address . "plugins/bof_tool_lorem_ai/assets/lorem_ai_item.js",
						"dir" => false
					)
				),
				"events" => (object) array(
					"displaying" => "bof_lorem_ai_item.displaying",
					"ready" => "bof_lorem_ai_item.set",
					"unloading" => "bof_lorem_ai_item.unset",
				),
				"__sb_family" => "extensions",
			);

			$method_result[ "lorem_ai" ] = array(
				"title" => "Lorem AI",
				"url" => "^lorem_ai\/(.*?)$",
				"link" => "lorem_ai",
				"theme_file" => "parts/content_setting",
				"becli" => array(
					(object) array(
						"endpoint" => "bofAdmin/setting/lorem_ai_\$bof ? urlData^url^match^0\$/",
						"key" => "setting"
					)
				),
				"extenders" => (object) array(
					"bof_lorem_ai_css" => (object) array(
						"type" => "css",
						"name" => "bof_lorem_ai",
						"path" => web_address . "plugins/bof_tool_lorem_ai/assets/lorem_ai.css",
						"dir" => false
					),
					"bof_lorem_ai_js" => (object) array(
						"type" => "js",
						"name" => "bof_lorem_ai",
						"path" => web_address . "plugins/bof_tool_lorem_ai/assets/lorem_ai.js",
						"dir" => false
					)
				),
				"events" => (object) array(
					"displaying" => "bof_lorem_ai.displaying",
					"ready" => "bof_lorem_ai.set",
					"unloading" => "bof_lorem_ai.unset",
				),
				"__sb_family" => "extensions",
			);

			$method_result[ "lorem_ai_setting" ] = array(
				"title" => "Lorem AI - Cronjoob",
				"url" => "^lorem_ai_setting$",
				"link" => "lorem_ai_setting",
				"theme_file" => "parts/content_setting",
				"becli" => array(
					(object) array(
						"endpoint" => "bofAdmin/setting/lorem_ai_self/",
						"key" => "setting"
					)
				),
				"__sb_family" => "extensions",
			);

			$method_result[ "lorem_ai_aiapi" ] = array(
				"title" => "Lorem AI - AI APIs",
				"url" => "^lorem_ai_aiapi$",
				"link" => "lorem_ai_aiapi",
				"theme_file" => "parts/content_setting",
				"becli" => array(
					(object) array(
						"endpoint" => "bofAdmin/setting/lorem_ai_aiapi/",
						"key" => "setting"
					)
				),
				"__sb_family" => "extensions",
			);

			$method_result[ "lorem_ai_playground" ] = array(
				"title" => "Lorem AI - AI APIs",
				"url" => "^lorem_ai_playground$",
				"link" => "lorem_ai_playground",
				// "theme_file" => "parts/content_setting",
				"theme_file" => web_address . "plugins/bof_tool_lorem_ai/assets/playground",
				"theme_args" => (object) array(
					"use_base" => false,
				),
				"extenders" => (object) array(
					"lorem_playground_css" => (object) array(
						"type" => "css",
						"name" => "lorem_play",
						"path" => web_address . "plugins/bof_tool_lorem_ai/assets/lorem_ai_play.css",
						"dir" => false
					),
					"lorem_playground_js" => (object) array(
						"type" => "js",
						"name" => "lorem_play",
						"path" => web_address . "plugins/bof_tool_lorem_ai/assets/lorem_ai_play.js",
						"dir" => false
					)
				),
				"events" => (object) array(
					"displaying" => "lorem_play.displaying",
					"ready" => "lorem_play.set",
					"unloading" => "lorem_play.unset",
				),
				"body_class" => [ "hide_highlights", "no_main_content_padding", "hide_header" ],
				"becli" => array(
					(object) array(
						"endpoint" => "lorem_ai_playground",
						"key" => "play"
					)
				),
				"__sb_family" => "extensions",
			);

		} );
	}
	protected function setup_admin_object_settings( $object_name ){

		$this->active_setting_object = $object_name;

		$object = bof()->object->__get( $object_name );
		$object_args = $this->get_supported_objects()[ $object_name ];
		$object_parsedAdmin = bof()->bofAdmin->object_list_parse_caller( $object, false );
		$object_parsed = bof()->bofAdmin->object_parse_caller( $object );

		$object_filters_db = bof()->object->db_setting->get( "lao_{$object_name}_fs" );
		$object_actions_db = bof()->object->db_setting->get( "lao_{$object_name}_as" );
		$object_prompts_db = bof()->object->db_setting->get( "lao_{$object_name}_ps" );

		if ( $object_filters_db ) $object_filters_db = json_decode( $object_filters_db, true );
		if ( $object_actions_db ) $object_actions_db = json_decode( $object_actions_db, true );
		if ( $object_prompts_db ) $object_prompts_db = json_decode( $object_prompts_db, true );

		$object_filters = [];
		foreach( $object_parsedAdmin["filters"] as $_k => $_v ){

			$_v["input"]["name"] = "__f__{$_k}";

			if ( !empty( $object_filters_db[$_k] ) )
			$_v["input"]["value"] = $object_filters_db[$_k];

			if ( !empty( $_v["bofInput"] ) ){
				$_v["validator"] = array(
					"int_imploded",
					array(
						"empty()"
					)
				);
				$_v = bof()->bofInput->parse( $_v )["data"];
			}

			$object_filters[ $_k ] = $_v;
		}

		if ( !empty( $object_args["inputs"] ) ){
			foreach( $object_args["inputs"] as $oa => $oArgs ){
				$_label = !empty( $oArgs["label"] ) ? $oArgs["label"] : $object_parsed["items"][$oa]["label"];
				$object_action_items[] = array(
					"title" => $_label,
					"tip" => ( !empty( $oArgs["type"] ) ? $oArgs["type"] == "img" : false ) ? "<b>".ucfirst($oArgs["type"])."</b>. Should Lorem AI generate an image for {$_label}" : "Should Lorem AI generate relative content for {$_label}?",
					"input" => array(
						"type" => "checkbox",
						"name" => "__a__{$oa}",
						"value" => !empty( $object_actions_db[ $oa ] )
					),
					"validator" => array(
						"boolean",
						array(
							"empty()"
						)
					)
				);
				$object_action_items[] = array(
					"title" => $_label . " - Overwrite",
					"tip" => "Should existing data be replaced with data from the API?",
					"input" => array(
						"type" => "checkbox",
						"name" => "__a__{$oa}_ow",
						"value" => !empty( $object_actions_db[ $oa . "_ow" ] )
					),
					"validator" => array(
						"boolean",
						array(
							"empty()"
						)
					)
				);
				$object_action_items[] = array(
					"title" => $_label . " - Prompt",
					"tip" => "Change the prompt carefully or you can break the response format. To reset to default, empty the textarea",
					"input" => array(
						"type" => "textarea",
						"name" => "__p__{$oa}",
						"value" => !empty( $object_prompts_db[ $oa ] ) ? $object_prompts_db[ $oa ] : $oArgs["prompt"]
					),
					"validator" => array(
						"string",
						array(
							"empty()"
						)
					)
				);
			}
		}

		$_mp = bof()->object->db_setting->get( "lao_{$object_name}_p" );

		bof()->bofAdmin->_add_setting( "lorem_ai_{$object_name}", array(
			"groups" => array(
				"active" => array(
					"title" => "Active",
					"icon" => "settings",
					"inputs" => array(
						array(
							"title" => "Active",
							"tip" => "Should Lorem AI generate relative content for `{$object->bof()["label"]}`",
							"col_name" => "lao_{$object_name}",
							"input" => array(
								"type" => "checkbox",
								"name" => "lao_{$object_name}",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()",
									"int" => true
								)
							)
						),
						array(
							"title" => "Prompt",
							"tip" => "Change the prompt carefully or you might break response format. Empty the textarea and save the page to reset prompt back to default",
							"input" => array(
								"type" => "textarea",
								"name" => "main_p",
								"value" => !empty( $_mp ) ? $_mp : $object_args["prompt"]
							),
							"validator" => array(
								"string",
								array(
									"empty()",
								)
							)
						)
					)
				),
				"detail" => array(
					"title" => "Actions",
					"icon" => "settings",
					"inputs" => $object_action_items
				),
				"filters" => array(
					"title" => "Filters",
					"tip" => "Establish filters to ensure the selection of specific items; otherwise, all items will be chosen",
					"icon" => "filter_alt",
					"inputs" => $object_filters
				),
			),
			"functions" => array(
				"be_after" => function( $groups, $inputs ){

					$object_name = bof()->lorem_ai->get_active_setting_object();

					$_filters = $_prompts = $_actions = [];
					foreach( $inputs["data"] as $_k => $_v ){

						if ( bof()->general->startsWith( $_k, "__f__" ) ? !empty( $_v ) && $_v != "__all__" : false )
						$_filters[ substr( $_k, 5 ) ] = $_v;
						if ( bof()->general->startsWith( $_k, "__a__" ) ? !empty( $_v ) : false )
						$_actions[ substr( $_k, 5 ) ] = true;

						if ( bof()->general->startsWith( $_k, "__p__" ) ? !empty( $_v ) : false ){
							$_k_def_prompt = $this->get_supported_objects()[ $object_name ]["inputs"][ substr( $_k, 5 ) ]["prompt"];
							if ( $_v != $_k_def_prompt ) $_prompts[ substr( $_k, 5 ) ] = $_v;
						}

					}

					$inputs["set"]["lao_{$object_name}_as"] = json_encode( $_actions );
					$inputs["set"]["lao_{$object_name}_fs"] = json_encode( $_filters );
					$inputs["set"]["lao_{$object_name}_ps"] = json_encode( $_prompts );

					$inputs["set"]["lao_{$object_name}_p"] = ( !empty( $inputs["data"]["main_p"] ) ? $inputs["data"]["main_p"] != $this->get_supported_objects()[ $object_name ]["prompt"] : false ) ?
					  $inputs["data"]["main_p"] :
					  false;

					return $inputs;

				}
			)
		) );

	}
	protected function setup_admin_self_settings(){

		$_dff = bof()->general->get_full_fall();
		bof()->general->set_full_fall(false);

		$so_childs = [];
		foreach( $this->get_supported_objects() as $so_name => $so_args ){
			try {
				$so_self = bof()->object->__get( $so_name );
				$so_childs[] = array(
					"title" => $so_self->bof()["label"],
					"tip" => "Cronjob. Should Lorem AI generate content for `{$so_self->bof()["label"]}` in background? Click <a href='lorem_ai/{$so_name}'>here</a> for more control",
					"col_name" => "lao_{$so_name}",
					"input" => array(
						"type" => "checkbox",
						"name" => "lao_{$so_name}",
					),
					"validator" => array(
						"boolean",
						array(
							"empty()",
							"int" => true
						)
					)
				);
			} catch( bofException|Exception $err ){
				continue;
			}
		}

		bof()->general->set_full_fall( $_dff );

		bof()->bofAdmin->_add_setting( "lorem_ai_self", array(
			"groups" => array(
				"active" => array(
					"title" => "Active",
					"tip" => "Activate LoremAI by cronjobs?",
					"icon" => "settings",
					"inputs" => array(
						array(
							"title" => "Active",
							"col_name" => "lorem_ai",
							"input" => array(
								"type" => "checkbox",
								"name" => "lorem_ai"
							),
							"validator" => array(
								"boolean",
								array(
									"empty()",
									"int" => true
								)
							)
						)
					)
				),
				"objects_active" => array(
					"title" => "Active - Objects",
					"icon" => "settings",
					"inputs" => $so_childs
				),
			)
		) );

	}
	protected function setup_admin_aiapi_settings(){

		$arr = bof()->ai->get_admin_aiapi_settings( "lorem_ai" );

		unset( $arr["groups"]["playht"], $arr["groups"]["twotee"], $arr["groups"]["elevenlabs"], $arr["groups"]["services"]["inputs"]["lorem_ai_s_core"] );

		bof()->bofAdmin->_add_setting(
			"lorem_ai_aiapi",
			$arr
		);

	}
	protected function setup_admin_endpoints(){
		bof()->object->endpoint->add( "lorem_ai_item_runner", array(
      "url" => "lorem_ai_item_runner",
      "groups" => [ "admin" ],
      "executers" => array(
        bof_lorem_ai . "/endpoints/endpoint_lorem_ai_item_runner.php"
      )
    ) );
		bof()->object->endpoint->add( "lorem_ai_playground", array(
      "url" => "lorem_ai_playground",
      "groups" => [ "admin" ],
      "executers" => array(
        bof_lorem_ai . "/endpoints/endpoint_lorem_ai_playground.php"
      )
    ) );
	}

	public function handle_insert_tags( $object, $item, $tagType, $value, $overwrite ){

		$object_prefix = explode( "_", $object )[0];
		$tagIDS = [];
		foreach( explode( ";", $value ) as $tagName ){
			$tagID = bof()->object->__get( "{$object_prefix}_{$tagType}" )->get_id( trim( $tagName ) );
			if ( $tagID )
			$tagIDS[] = $tagID;
		}

		if ( !$overwrite ){
			$t_object = bof()->object->__get( $object );
			$pre_ids = bof()->db->_select(array(
				"table" => $t_object->bof()["db_rel_table_name"],
				"where" => array(
					[ $t_object->bof()["db_rel_table_col_name"], "=", $item["ID"] ],
					[ "type", "=", $tagType ]
				),
				"limit" => false,
				"single" => false
			));
			if ( $pre_ids ){
				foreach( $pre_ids as $pre_id )
				$tagIDS[] = $pre_id["target_id"];
			}
		}

		return !empty( $tagIDS ) ? [ "{$tagType}_ids", $tagIDS ] : false;

	}

	public function run_cronjob( $PID, $GID ){

		$this->cronjob_cache["PID"] = $PID;
		$this->cronjob_cache["GID"] = $GID;

		$supported_objects = bof()->lorem_ai->get_supported_objects();
		foreach( array_keys( $supported_objects ) as $supported_object ){
			try {
				$this->run_object( $supported_object );
			} catch( bofException $err ){
				$this->cronjob_log( "{$supported_object} -> {$err->getMessage()}" );
				continue;
			}
		}

		return "Executed";

	}
	public function parse_object( $name ){

		// Getting prompt ready
		$lorem_args = $this->get_supported_objects()[ $name ];
		$lorem_actions = $lorem_args["inputs"];

		$the_object = bof()->object->__get( $name );
		$the_object_parsed = bof()->object->parse_caller( $the_object )->parsed;

		$user_filters_raw = bof()->object->db_setting->get( "lao_{$name}_fs" );
		$user_filters = $user_filters_raw ? json_decode( $user_filters_raw, true ) : false;
		$user_actions_raw = bof()->object->db_setting->get( "lao_{$name}_as" );
		$user_actions_messy = $user_actions_raw ? array_keys( json_decode( $user_actions_raw, true ) ) : false;
		$user_actions_prompts_raw = bof()->object->db_setting->get( "lao_{$name}_ps" );
		$user_actions_prompts = $user_actions_prompts_raw ? json_decode( $user_actions_prompts_raw, true ) : false;
		$user_main_prompt = !empty( bof()->object->db_setting->get( "lao_{$name}_p" ) ) ? bof()->object->db_setting->get( "lao_{$name}_p" ) : $lorem_args["prompt"];

		$actions = $actions_overwrite = $actions_prompts = $actions_images = [];

		foreach( $user_actions_messy as $actionName ){

			$actionArgs = !empty( $lorem_actions[ $actionName ] ) ? $lorem_actions[ $actionName ] : null;

			if ( bof()->general->endswith( $actionName, "_ow" ) ){
				$actions_overwrite[] = str_replace( "_ow", "", $actionName );
			}
			elseif ( empty( $actionArgs["type"] ) ) {
				$actions[$actionName] = !empty( $user_actions_prompts[$actionName] ) ? $user_actions_prompts[$actionName] : $actionArgs["prompt"];
				$actions_prompts[] = "-{$actionName}: {$actions[$actionName]}";
			}
			elseif ( !empty( $actionArgs["type"] ) ? $actionArgs["type"] == "img" : false ){
				$actions_images[ $actionName ] = array(
					"prompt" => !empty( $user_actions_prompts[$actionName] ) ? $user_actions_prompts[$actionName] : $actionArgs["prompt"],
					"object_type" => $the_object_parsed->columns[ $actionName ]["bofInput"][1]["object_type"]
				);
			}

		}

		if ( empty( $actions ) && empty( $actions_images ) )
		throw new bofException( "No actions" );

		if ( $actions_prompts ){
			$actions_prompts = implode( "\n", $actions_prompts );
			$final_prompt = $user_main_prompt . "\n\n" . $actions_prompts . "\n\n" . "Just respond with the raw JSON string. Don't talk or give additional information. If you don't have information about given query, Just respond with these two words: \"NOT FOUND\"";
		} else {
			$final_prompt = null;
		}

		return array(
			"args" => $lorem_args,
			"args_actions" => $lorem_actions,
			"user_filters" => $user_filters,
			"user_actions_messy" => $user_actions_messy,
			"user_actions_prompts" => $user_actions_prompts,
			"user_main_prompt" => $user_main_prompt,
			"actions" => $actions,
			"actions_overwrite" => $actions_overwrite,
			"actions_prompts" => $actions_prompts,
			"actions_images" => $actions_images,
			"final_prompt" => $final_prompt
		);

	}
	public function run_object( $name ){

		if ( !bof()->object->db_setting->get( "lao_{$name}" ) )
		throw new bofException( "Not active" );

		$lorem_object = $this->parse_object( $name );

		$_wheres = array_merge(
			$lorem_object["user_filters"] ? $lorem_object["user_filters"] : [],
			array(
				[
					"ID",
					"NOT IN",
					"SELECT item_id FROM _bof_lorem_ai_cache WHERE object_name = '{$name}'",
					true
				]
			)
		);

		bof()->call( "lorem_ai", "run_object_where", $name, $_wheres );

		// Getting items ready
		$items = bof()->object->__get( $name )->select(
			$_wheres,
			array(
				"empty_select" => true,
				"limit" => $this->item_limit,
				"clean" => false,
				"single" => false
			)
		);

		if ( !$items )
		throw new bofException( "No items" );

		foreach( $items as $item ){
			try {
				$ok = $this->run_item( $name, $item, $lorem_object );
			} catch( bofException|Exception|Warning $err ){
				$ok = false;
			}
			bof()->db->_insert(array(
				"table" => "_bof_lorem_ai_cache",
				"set" => array(
					[ "object_name", $name ],
					[ "item_id", $item["ID"] ],
					[ "sta", $ok ? 1 : 0 ]
				)
			));
		}

	}
	public function run_item( $object_name, $item, $lorem_object=null ){

		$lorem_object = $lorem_object ? $lorem_object : $this->parse_object( $object_name );
		extract( $lorem_object, EXTR_PREFIX_ALL, "lo" );

		$itemQuery = $lo_args["query_function"]( $item );
		$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} {$itemQuery}" );

		if ( $lo_actions_images ){
			foreach( $lo_actions_images as $action_imageName => $action_imageArgs ){

				if ( empty( $item[ $action_imageName ] ) || in_array( $action_imageName, $lo_actions_overwrite, true ) ){

					$ask_the_ai_err = $ask_the_ai = null;

					try {
						$ask_the_ai = bof()->ai->__reset()->set_setting_db_var( "lorem_ai" )->image->generateFromText( str_replace( [ "%query%", "{subject}" ], $itemQuery, $action_imageArgs["prompt"] ), array(
							"size" => $lo_args_actions[ $action_imageName ]["img_size"]
						) );
					} catch( bofException|Exception $err ){
					  $ask_the_ai_err = $err->getMessage();
					}

					if ( $ask_the_ai ){

						try {
							$handle_url = bof()->object->file->handle_url( $ask_the_ai, array(
								"object_type" => $action_imageArgs["object_type"]
							) );
						} catch( Exception $err ){
							$handle_url = [ 0, 0 ];
						}

						if ( $handle_url[0] ? !empty( $handle_url[1]["file_id"] ) : false ){
							$file_id = $handle_url[1]["file_id"];
							$finalize = bof()->object->file->finalize_upload(
								"image",
								$action_imageArgs["object_type"],
								"{$object_name}{$item["ID"]}",
								$file_id,
								$item[$action_imageName]
							);
							if ( $file_id && $finalize ){
								bof()->object->__get( $object_name )->update(
									array(
										"ID" => $item["ID"]
									),
									array(
										$action_imageName => $file_id
									),
								);
							}
							$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} {$itemQuery}: Gotten image:{$action_imageName}: {$ask_the_ai}" );
						}

					} else {
						$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} {$itemQuery}: Failed to get image:{$action_imageName} -> " . ( !empty($ask_the_ai_err)?$ask_the_ai_err:"?" ) );
					}

				} else {
					$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} {$itemQuery}: image:{$action_imageName}: Already exists, overwrite is turned off. Skip" );
				}

			}
		}

		if ( !$lo_final_prompt ){
			$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} No Final-Prompt for details" );
			return true;
		}

		$ask_the_ai_err = $ask_the_ai = null;
		try {
			$ask_the_ai = bof()->ai->__reset()->set_setting_db_var("lorem_ai")->text->generateFromText( $itemQuery, array(
				"prompt_system" => $lo_final_prompt,
				"model" => bof()->object->db_setting->get("lorem_ai_openai_core"),
				"json" => true
			) );
		}
		catch( bofException|Exception $err ){
			$ask_the_ai_err = $err->getMessage();
			$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} {$itemQuery}: Failed to get data from OpenAI 1 -> {$ask_the_ai_err}" );
		}

		$_updateArray = [];
		$_seo_data = $item["seo_data"] ? json_decode( $item["seo_data"], true ) : [];
		foreach( array_keys( $lo_actions ) as $action ){

			$_parse = null;
			$action_overwrite = $lo_actions_overwrite ? in_array( $action, $lo_actions_overwrite, true ) : false;

			if ( !empty( $ask_the_ai[ $action ] ) ? !in_array( strtolower( $ask_the_ai[ $action ] ), [ "not found", "not_found", "not-found" ], true ) : false ){

				$_val = $ask_the_ai[ $action ];

				if ( $action == "seo_slug" ){
					if ( $action_overwrite ){
						$new_slug = bof()->general->make_url( $_val );
						$slug_exist_already = bof()->object->__get( $object_name )->select(["seo_url"=>$new_slug]);
						if ( !$slug_exist_already ) $_parse = [ "seo_url", $new_slug ];
					}
				}
				elseif ( in_array( $action, [ "seo_title", "seo_tags", "seo_description" ], true ) ){
					$seo_action = substr( $action, 4 );
					if ( empty( $_seo_data[ $seo_action ] ) || $action_overwrite )
					$_seo_data[ $seo_action ] = $_val;
					$_parse = [ "seo_data", $_seo_data ];
				}
				else {
					$_parse = $lo_args["insert_function"]( $item, $action, $_val, $action_overwrite, $object_name );
				}

				if ( $_parse )
				$_updateArray[ $_parse[0] ] = $_parse[1];

			}

		}

		if ( !empty( $_updateArray ) ){
			$this->cronjob_log( "{$object_name} -> item: #{$item["ID"]} {$itemQuery}: Got data: " . json_encode( $_updateArray ) );
			bof()->object->__get( $object_name )->update(
				array(
					"ID" => $item["ID"]
				),
				$_updateArray,
			);
		}

		return true;

	}

}

?>
