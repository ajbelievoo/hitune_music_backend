<?php

if ( !defined( "bof_root" ) ) die;

class bulk_importer {

	protected $cache = [ "y" => 0, "n" => 0 ];

	public function setup(){

		if ( bof()->getName() != "bof_admin" )
		return;

		bof()->object->core_files->add_object( "bi_item", bof_bulk_importer . "/objects/object_item.php" );

		$this->setup_admin();
		$this->setup_admin_app_pages();

		$this->setup_cronjob();

	}
	protected function setup_admin(){

		bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {

			$highlights = bof()->highlights->getData();
			$highlights["content_links"]["items"]["music"]["args"]["childs"][] = array(
				"icon"  => "category",
				"title" => "Bulk Import",
				"link"  => "bulk_importer"
			);
			$highlights["extensions_links"]["items"]["tools_links"]["args"]["childs"][] = array(
				"icon" => "category",
				"title" => "Music Bulk Import",
				"link" => "bulk_importer"
			);
			bof()->highlights->setData($highlights);
		});

		bof()->object->endpoint->add( "bulk_importer", array(
			"url" => "bulk_importer",
			"groups" => [ "admin" ],
			"executers" => array(
				bof_bulk_importer . "/endpoints/endpoint_bulk_importer.php"
			)
		) );

		bof()->object->endpoint->add( "bulk_importer_save", array(
			"url" => "bulk_importer_save",
			"groups" => [ "admin" ],
			"executers" => array(
				bof_bulk_importer . "/endpoints/endpoint_bulk_importer_save.php"
			)
		) );

		bof()->object->endpoint->add( "bulk_importer_sync", array(
			"url" => "bulk_importer_sync",
			"groups" => [ "admin" ],
			"executers" => array(
				bof_bulk_importer . "/endpoints/endpoint_bulk_importer_sync.php"
			)
		) );

		bof()->object->endpoint->add( "bulk_importer_mark", array(
			"url" => "bulk_importer_mark",
			"groups" => [ "admin" ],
			"executers" => array(
				bof_bulk_importer . "/endpoints/endpoint_bulk_importer_mark.php"
			)
		) );

		bof()->object->endpoint->add( "bulk_importer_upload_cover", array(
			"url" => "bulk_importer_upload_cover",
			"groups" => [ "admin" ],
			"executers" => array(
				bof_bulk_importer . "/endpoints/endpoint_bulk_importer_upload_cover.php"
			)
		) );

	}
	protected function setup_admin_app_pages(){

		bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

			if ( is_array( $method_result ) ){
				$method_result[ "bulk_importer" ] = array(
					"title" => "Bulk Importer",
					"url" => "^bulk_importer$",
					"link" => "bulk_importer",
					"theme_file" => web_address . "plugins/bof_tool_bulk_importer/assets/theme/bulk_importer",
          "theme_args" => (object) array(
            "use_base" => false,
          ),
          "extenders" => (object) array(
            "bulk_importer_css" => (object) array(
              "type" => "css",
              "name" => "bulk_importer_css",
              "path" => web_address . "plugins/bof_tool_bulk_importer/assets/css/bulk_importer.css",
              "dir" => false
            ),
            "bulk_importer_js" => (object) array(
              "type" => "js",
              "name" => "bulk_importer",
              "path" => web_address . "plugins/bof_tool_bulk_importer/assets/javascript/bulk_importer.js",
              "dir" => false
            )
          ),
          "body_class" => [ "hide_highlights", "no_main_content_padding", "hide_header" ],
					"becli" => array(
						(object) array(
							"endpoint" => "bulk_importer?\$bof ? urlData^url^query_s\$",
							"key" => "content",
						)
					),
          "events" => (object) array(
						"displaying" => "bulk_importer.displaying",
						"ready" => "bulk_importer.set",
            "unloading" => "bulk_importer.unset",
          ),
					"__sb_family" => "content",
				);
				$method_result[ "bulk_importer_upload" ] = array(
					"title" => "Bulk Uploader",
					"url" => "^bulk_importer_upload$",
					"link" => "bulk_importer_upload",
					"theme_file" => web_address . "plugins/bof_tool_bulk_importer/assets/theme/bulk_importer_upload",
          "theme_args" => (object) array(
            "use_base" => false,
          ),
          "extenders" => (object) array(
            "bulk_importer_upload_css" => (object) array(
              "type" => "css",
              "name" => "bulk_importer_upload_css",
              "path" => web_address . "plugins/bof_tool_bulk_importer/assets/css/bulk_importer_upload.css",
              "dir" => false
            ),
            "bulk_importer_upload_js" => (object) array(
              "type" => "js",
              "name" => "bulk_importer_upload",
              "path" => web_address . "plugins/bof_tool_bulk_importer/assets/javascript/bulk_importer_upload.js",
              "dir" => false
            )
          ),
          "body_class" => [],
          "events" => (object) array(
            "ready" => "bulk_importer_upload.set",
            "unloading" => "bulk_importer_upload.unset",
          ),
					"__sb_family" => "content",
				);
			}

		} );

	}

	public function resync_files( $PID, $GID ){

		$_n = $this->rename_dirs( $PID, $GID );

		bof()->db->_update(array(
			"table" => "_bof_tool_bulk_importer_files",
			"set" => array(
				[ "to_be", "0" ]
			)
		));

		$_o = $this->rescan_dir( $PID, $GID );

		bof()->db->_delete(array(
			"table" => "_bof_tool_bulk_importer_files",
			"where" => array(
				[ "to_be", "=", "0" ]
			)
		));

		return $_o;

	}
	protected function rename_dirs( $PID, $GID, $dir_path=null ){

		if ( !$dir_path )
		$dir_path = base_root . "/files/bulk_importer";

		$files = scandir( $dir_path );
		if ( empty( $files ) ) return $files;

		foreach( $files as $file ){

			$file_realpath = realpath( $dir_path . "/" . $file );

			if ( $file == "." || $file == ".." ) continue;

			if ( is_file( $file_realpath ) ){
				$this->rename_ent( "file", $PID, $GID, $file_realpath, $dir_path, $file );
			}

			if ( is_dir( $file_realpath ) ){
				$this->rename_dirs( $PID, $GID, $file_realpath );
				$this->rename_ent( "folder", $PID, $GID, $file_realpath, $dir_path, $file );
			}

		}

	}
	protected function rename_ent( $type, $PID, $GID, $realpath, $dirname, $filename ){

		$filename_escaped = preg_replace(
			'/[^a-zA-Z0-9 \-_()\.]/u',
			'',
			$filename
		);

		if ( $filename_escaped )
		$filename_escaped = trim( $filename_escaped );

		if ( empty( $filename_escaped ) ? true : mb_strlen( $filename_escaped, "utf-8" ) < 3 )
		$filename_escaped = uniqid();

		if ( $filename_escaped !== $filename ){
			rename( $realpath, $dirname . "/" . $filename_escaped );
			bof()->cronjob->log_p( $PID, $GID, "Renaming {$type} \"{$filename}\" to \"{$filename_escaped}\"" );
			// $this->cache["y"]++;
		} else {
			// bof()->cronjob->log_p( $PID, $GID, "NoRenaming {$type} {$filename} to {$filename_escaped}" );
			// $this->cache["n"]++;
		}

	}
	protected function rescan_dir($PID, $GID){

		$dir_path = base_root . "/files/bulk_importer";
		$dir_scan = bof()->file->scandir($dir_path, array(
			"search_by_extension" => ["mp3", "wav", "ogg", "flac"]
		));

		if (empty($dir_scan) || empty($dir_scan["files"]))
			throw new Exception("nothing found");

		$files = $dir_scan["files"];

		bof()->cronjob->log_p($PID, $GID, count($files) . " file(s) found in upload folder");

		foreach ($files as $file_full_path) {

			$file_path_info = pathinfo($file_full_path);
			$file_rel_path = bof()->object->file->clean_path($file_full_path, true);
			$file_hash = md5($file_rel_path);

			bof()->cronjob->log_p($PID, $GID, "Checking {$file_rel_path}");

			$file_exists = bof()->db->_select(array(
				"table" => "_bof_tool_bulk_importer_files",
				"where" => array(
					["hash", "=", $file_hash]
				),
				"limit" => 1,
				"single" => true
			));

			if ($file_exists) {
				bof()->cronjob->log_p($PID, $GID, "Already Exists. Continue to next one");
				bof()->db->_update(array(
					"table" => "_bof_tool_bulk_importer_files",
					"where" => array(
						["ID", "=", $file_exists["ID"]]
					),
					"set" => array(
						["to_be", "1"]
					)
				));
			} else {

				$mp3 = $file_id3_tags = false;
				if ($file_path_info["extension"] == "mp3") {
					$file_id3_tags = bof()->id3->read_tags($file_full_path);
					if (!empty($file_id3_tags["format"]) ? $file_id3_tags["format"] == "mp3" : false)
						$mp3 = true;
				}

				bof()->cronjob->log_p($PID, $GID, "New file. " . ($mp3 ? "mp3 file" : "non-mp3 file, requires more process"));

				$coverImage = $coverName = $coverPath = $coverHash = null;
				if (!empty($file_id3_tags["tags"]["cover_string"])) {
					$coverImage = @imagecreatefromstring($file_id3_tags["tags"]["cover_string"]);
					if ($coverImage) {
						$coverName = md5($file_rel_path);
						$coverPath = base_root . "/files/bulk_importer_covers/" . $coverName . ".png";
						imagepng($coverImage, $coverPath);
						imagedestroy($coverImage);
						$coverHash = md5_file($coverPath);
						$coverExists = bof()->db->_select(array(
							"table" => "_bof_tool_bulk_importer_files",
							"where" => array(
								["cover_hash", "=", $coverHash]
							),
							"limit" => 1,
							"single" => true
						));
						if ($coverExists) {
							unlink($coverPath);
							$coverName = $coverExists["cover"];
							bof()->cronjob->log_p($PID, $GID, "Has a cover. Cover is a duplicate");
						} else {
							bof()->cronjob->log_p($PID, $GID, "Has a cover. Cover is new and not a duplicate");
						}
					}
				}

				$file_bi_rel_path = substr($file_rel_path, strlen("files/bulk_importer/"));
				$file_bi_dirname = null;
				if (count(explode("/", $file_bi_rel_path)) > 1) {
					$_sp = explode("/", $file_bi_rel_path);
					$file_bi_dirname = implode("/", array_slice($_sp, 0, count($_sp) - 1));
				}

				$setArray = array(
					["hash", $file_hash],
					["path", $file_rel_path],
					["path_name", $file_path_info["filename"]],
					["path_dir_name", $file_bi_dirname],
					["mp3", $mp3 ? 1 : 0],
				);

				if ($coverName && $coverHash) {
					$setArray[] = ["cover", $coverName];
					$setArray[] = ["cover_hash", $coverHash];
				}

				if ($mp3) {
					$setArray = array_merge(array(
						["duration", $file_id3_tags["duration"]],
						["bitrate", $file_id3_tags["bitrate"]],
						["tag_title", $file_id3_tags["tags"]["title"]],
						["tag_artist", $file_id3_tags["tags"]["artist_name"]],
						["tag_album_order", $file_id3_tags["tags"]["album_order"]],
						["tag_album", $file_id3_tags["tags"]["album_title"]],
						["tag_album_artist", $file_id3_tags["tags"]["album_artist_name"]],
						["tag_time_release", $file_id3_tags["tags"]["album_time"]],
						["tag_genres", !empty($file_id3_tags["tags"]["genres"]) ? implode(";", $file_id3_tags["tags"]["genres"]) : null],
						["tag_cd_order", null],
						["tag_lyrics", null],
						["tag_album_type", "studio"]
					), $setArray);
				}

				bof()->db->_insert(array(
					"table" => "_bof_tool_bulk_importer_files",
					"set" => $setArray
				));
			}
		}

		return "found " . count($files) . " file(s)";
	}
	public function import( $PID, $GID ){

		$files = bof()->db->_select( array(
	    "table" => "_bof_tool_bulk_importer_files",
	    "limit" => false,
			"where" => array(
				[ "sta", "=", "2" ]
			)
	  ) );

		if ( !$files )
		throw new Exception( "Nothing ready to import" );

		$cover_cache = bof()->object->db_setting->get( "bulk_import_cs", [] );
		$cover_rules = bof()->object->file->get_rules( "image", "m_track_c" );

		foreach ( $files as $file ){

			bof()->cronjob->log_p( $PID, $GID, "==============================" );
			bof()->cronjob->log_p( $PID, $GID, "Importing {$file["path_name"]}" );

			try {

				// Cover
				$cover_id = null;
				if ( $file["cover"] ){
					bof()->cronjob->log_p( $PID, $GID, "Has a cover" );
					if ( !empty( $cover_cache[ $file["cover"] ] ) ){
						$cover_id = $cover_cache[ $file["cover"] ];
						bof()->cronjob->log_p( $PID, $GID, "Has a cached cover -> {$cover_id}" );
					} else {
						$cover_id = bof()->object->file->insert( array(
							"type" => "image",
							"pass" => substr( md5( uniqid() . time() ), 0, 10 ),
							"object_type" => "m_track_c",
							"host_id" => 1,
							"dest_host_id" => $cover_rules["file_host"],
							"path" => "/files/bulk_importer_covers/{$file["cover"]}.png",
						) );
						$cover_cache[ $file["cover"] ] = $cover_id;
						bof()->cronjob->log_p( $PID, $GID, "Has a new cover -> {$cover_id}" );
					}
				}

				// tags & genres & ft_artists
				$__all_sub_artists = [];
				$m_tags = [];
				foreach( [ "tag", "genre", "ft_artist", "lang" ] as $m_tag_key ){
					$m_tags[ $m_tag_key ] = [];
					if ( !$file["tag_{$m_tag_key}s"] ) continue;
					$m_tag_vs = explode( ";", $file["tag_{$m_tag_key}s"] );
					foreach( $m_tag_vs as $m_tag_v ){

						if ( empty( trim( $m_tag_v ) ) )
						continue;

						$m_tag_id = null;
						if ( $m_tag_key == "ft_artist" ){

							$ft_artist_name = $m_tag_v;
							$ft_artist_code = bof()->general->make_code( $ft_artist_name );
							if ( ( $ft_artist = bof()->object->m_artist->select( [ "code" => $ft_artist_code ] ) ) ){
								$m_tag_id = $ft_artist["ID"];
							} else {
								$m_tag_id = bof()->object->m_artist->create( ["ignore_blacklist()"], array(
									"name" => $ft_artist_name,
									"hash" => bof()->object->m_artist->get_free_hash(),
									"code" => $ft_artist_code,
									"seo_url"  => bof()->object->m_artist->get_free_url( $ft_artist_name ),
								), [] );
							}

							if ( $m_tag_id )
							$__all_sub_artists[] = $m_tag_id;

						} else {
							$m_tag_id = bof()->object->__get( ( defined("stu") && $m_tag_key == "lang" ) ? "m_promotion" : "m_{$m_tag_key}" )->get_id( $m_tag_v );
						}

						if ( $m_tag_id ){
							$m_tags[ $m_tag_key ][] = $m_tag_id;
							bof()->cronjob->log_p( $PID, $GID, "Created/Found {$m_tag_key}:{$m_tag_v}" );
						}

					}
				}

				// Album Artist
				$album_artist_name = $file["tag_album_artist"];
				$album_artist_code = bof()->general->make_code( $album_artist_name );
				if ( ( $album_artist = bof()->object->m_artist->select( [ "code" => $album_artist_code ] ) ) ){
					$album_artist_id = $album_artist["ID"];
					bof()->cronjob->log_p( $PID, $GID, "Found artist:{$album_artist_name}" );
				} 
				else {
					$album_artist_id = bof()->object->m_artist->create( ["ignore_blacklist()"], array(
						"name" => $album_artist_name,
						"hash" => bof()->object->m_artist->get_free_hash(),
						"code" => $album_artist_code,
						"seo_url"  => bof()->object->m_artist->get_free_url( $album_artist_name ),
					), [] );
					bof()->cronjob->log_p( $PID, $GID, "Created artist:{$album_artist_name}" );
				}

				

				// Album
				$album_title = $file["tag_album"];
				$album_type = $file["tag_album_type"];
				$album_code = bof()->general->make_code( [ $album_artist_name, $album_title . ( $album_type == "single" ? "_single" : "" ) ] );
				if ( ( $album = bof()->object->m_album->select( [ "code" => $album_code ] ) ) ){
					$album_id = $album["ID"];
					bof()->cronjob->log_p( $PID, $GID, "Found album:{$album_title}" );
				} else {
					$album_id = bof()->object->m_album->create( [], array(
						"title" => $album_title,
						"hash" => bof()->object->m_album->get_free_hash(),
						"code" => $album_code,
						"seo_url"  => bof()->object->m_album->get_free_url( $album_artist_name . "-" . $album_title ),
						"type" => $album_type,
						"artist_id" => $album_artist_id,
						"time_release" => $file["tag_time_release"],
						"genre_ids" => $m_tags["genre"],
						"tag_ids" => $m_tags["tag"],
						( defined("stu") ? "promotion_ids" : "lang_ids" ) => $m_tags["lang"],
						"description" => null,
						"price" => null,
					), [] );
					bof()->cronjob->log_p( $PID, $GID, "Created album:{$album_title}" );
				}

				// Artist
				$artist_name = $file["tag_artist"];
				$artist_code = bof()->general->make_code( $artist_name );
				if ( ( $artist = bof()->object->m_artist->select( [ "code" => $artist_code ] ) ) ){
					$artist_id = $artist["ID"];
					bof()->cronjob->log_p( $PID, $GID, "Found artist:{$artist_name}" );
				} else {
					$artist_id = bof()->object->m_artist->create( ["ignore_blacklist()"], array(
						"name" => $artist_name,
						"hash" => bof()->object->m_artist->get_free_hash(),
						"code" => $artist_code,
						"seo_url"  => bof()->object->m_artist->get_free_url( $artist_name ),
					), [] );
					bof()->cronjob->log_p( $PID, $GID, "Created artist:{$artist_name}" );
				}

				if ( defined("stu") ){
					if ( $artist_id != $album_artist_id ){
						bof()->db->query("INSERT IGNORE INTO _c_m_albums_relations ( album_id, target_id, `type` ) VALUES ( '{$album_id}', '{$artist_id}', 'ft_artist' ) ");
						bof()->cronjob->log_p( $PID, $GID, "--> Stu Custom: Added artist {$artist_id} as featured_artist to album" );
					}
				}

				

				// Track
				$code = bof()->general->make_code( [ $artist_name, $album_title, $file["tag_title"] ] );
				if ( ( $track = bof()->object->m_track->select( [ "code" => $code ] ) ) ){
					$ID = $track["ID"];
					bof()->cronjob->log_p( $PID, $GID, "Found track:{$code}" );
					if ( $cover_id ){
						bof()->object->m_track->create(
							array(
								"ID" => $ID
							),
							array(
								"cover_id" => $cover_id
							)
						);
					}
				} else {
					$ID = bof()->object->m_track->create( [], array(
						"title" => $file["tag_title"],
						"hash" => bof()->object->m_track->get_free_hash(),
						"code" => $code,
						"seo_url"  => bof()->object->m_track->get_free_url( $artist_name . "-" . $album_title . "-" . $file["tag_title"] ),
						"artist_id" => $artist_id,
						"cover_id" => $cover_id,
						"time_release" => $file["tag_time_release"],
						"genre_ids" => $m_tags["genre"],
						"tag_ids" => $m_tags["tag"],
						( defined("stu") ? "promotion_ids" : "lang_ids" ) => $m_tags["lang"],
						"description" => null,
						"lyrics" => null,
						"price" => null,
						"price_setting" => null,
						"ft_artist_ids" => !empty( $m_tags["ft_artist"] ) ? implode( ",", $m_tags["ft_artist"] ) : false,
						"album_id" => $album_id,
						"album_artist_id" => $album_artist_id,
						"album_index" => $file["tag_album_order"] ? $file["tag_album_order"] : null,
						"album_cd" => $file["tag_cd_order"] ? $file["tag_cd_order"] : null,
						"album_price" => null,
						"duration" => $file["duration"]
					), [] );
					bof()->cronjob->log_p( $PID, $GID, "Created track:{$code}" );
				}

				// set track's cover as album cover
				if ( $cover_id ){
					bof()->cronjob->log_p( $PID, $GID, "Set track's cover as album's cover" );
					bof()->object->m_album->update(
						array(
							"ID" => $album_id
						),
						array(
							"cover_id" => $cover_id
						),
					);
				}

				// Source
				$rules = bof()->object->file->get_rules( "audio", "m_track_source", [ "get_host" => true ] );
				$convert_file_id = bof()->object->file->insert(
					array(
						"type" => "audio",
						"host_id" => "1",
						"dest_host_id" => $rules["file_host"],
						"path" => bof()->object->file->clean_path( base_root . "/" . $file["path"], true ),
						"object_type" => "m_track_source",
					)
				);

				$convert_source = bof()->object->m_track_source->create(
					[],
					array(
						"target_id" => $ID,
						"type" => "audio",
						"data" => array(
							"file_type" => "local",
							"local_file" => $convert_file_id,
						),
					),
					[]
				);

				bof()->db->_delete(array(
					"table" => "_bof_tool_bulk_importer_files",
					"where" => array(
						[ "ID", "=", $file["ID"] ]
					)
				));

			} catch ( bofException|Exception|Warning|Error $err ){
				bof()->cronjob->log_p( $PID, $GID, "Failed: " . $err->getMessage() );
			}

		}

		if ( !empty( $cover_cache ) )
		bof()->object->db_setting->set( "bulk_import_cs", json_encode( $cover_cache ), "json" );

	}
	public function convert( $PID, $GID ){

		bof()->plugin("ffmpeg");

		$files = bof()->db->_select( array(
	    "table" => "_bof_tool_bulk_importer_files",
	    "limit" => false,
			"where" => array(
				[ "sta", "=", "1" ],
				[ "mp3", "=", "0" ],
			)
	  ) );

		if ( !$files )
		return;

		foreach( $files as $file ){

			try {
				$this->_convert( $file );
				bof()->cronjob->log_p( $PID, $GID, "Converting {$file["path"]} went ok" );
			} catch( Exception|bofException|Error|Warning $err ){
				bof()->cronjob->log_p( $PID, $GID, "Converting {$file["path"]} failed. " . $err->getMessage() );
			}

			if ( is_file( base_root . "/{$file["path"]}" ) )
			unlink( base_root . "/{$file["path"]}" );

			bof()->db->_delete( array(
		    "table" => "_bof_tool_bulk_importer_files",
				"where" => array(
					[ "ID", "=", $file["ID"] ],
				)
		  ) );

		}

		$this->rescan_dir( $PID, $GID );

	}
	protected function _convert( $file ){

		if ( !is_file( base_root . "/{$file["path"]}" ) )
		throw new Exception( "{$file["path"]} does not exists" );

		$fileExtension = pathinfo( $file["path"], PATHINFO_EXTENSION );

		$convert = bof()->ffmpeg->convert_to_mp3( base_root . "/{$file["path"]}", null, array(
			"dir" => base_root,
			"name" => str_replace( ".{$fileExtension}", "", $file["path"] ),
			"ab" => null
		) );

		if ( !$convert )
		throw new Exception( "convert failed" );

		return true;

	}
	public function validate_file( $file ){

		$invalids = [];

		foreach( [ "title", "artist", "album", "album_artist", "album_type" ] as $requied_simple_string ){
			if ( empty( $file["tag_{$requied_simple_string}"] ) ? true : mb_strlen( $file["tag_{$requied_simple_string}"], "utf-8" ) < 1 )
			$invalids[] = "{$requied_simple_string} is empty/invalid";
		}

		if ( empty( $file["tag_time_release"] ) ? true : !bof()->nest->validate( $file["tag_time_release"], "timestamp" ) )
		$invalids[] = "time_release is empty/invalid";

		if ( empty( $file["tag_album_order"] ) ? false : !bof()->nest->validate( $file["tag_album_order"], "int" ) )
		$invalids[] = "album_order is invalid";

		if ( empty( $file["tag_cd_order"] ) ? false : !bof()->nest->validate( $file["tag_cd_order"], "int" ) )
		$invalids[] = "cd_order is invalid";

		$rules = bof()->object->file->get_rules( "audio", "m_track_source" );

		if ( $file["bitrate"] < $rules["validators"]["br_min"] )
		$invalids[] = "bitrate is too low";

		if ( filesize( base_root . "/" . $file["path"] ) < $rules["validators"]["size_min"]*1000000 )
		$invalids[] = "file-size is too small";

		if ( filesize( base_root . "/" . $file["path"] ) > $rules["validators"]["size_max"]*1000000 )
		$invalids[] = "file-size is too big";

		return $invalids;

	}
	public function clean_covers(){

		$dir_path = base_root . "/files/bulk_importer_covers";
	  $dir_scan = bof()->file->scandir( $dir_path, array(
	    "search_by_extension" => [ "png", "gif", "jpg", "jpeg" ]
	  ) );

		if ( empty( $dir_scan ) || empty( $dir_scan["files"] ) )
		return;

		$files = $dir_scan["files"];

		foreach( $files as $file ){
			$hasParent = bof()->db->_select(array(
				"table" => "_bof_tool_bulk_importer_files",
				"where" => array(
					[ "cover", "=", pathinfo( $file, PATHINFO_FILENAME ) ]
				),
				"cache_load_rt" => false,
				"limit" => 1,
				"columns" => "ID"
			));
			if ( !$hasParent ){
				if ( !is_file( $file ) )
				unlink( $file );
			}
		}

	}

	// cronjobs
	protected function setup_cronjob(){

		bof()->listen( "cronjob", "_clean_database_get_map_after", function( $method_args, &$map, $loader ){

			$map["_bof_tool_bulk_importer_files"] = [];

		} );
		bof()->listen( "cronjob", "get_jobs_after", function( $method_args, &$jobs, $loader ){

			if ( $loader->object->db_setting->get( "bulk_import_state" ) == 1 ){
				$jobs["bulk_importer_scan"] = array(
					"title" => "Bulk Importer - Scanner",
					"exe" => function( $PID, $GID, $loader ){
						$loader->object->db_setting->set( "bulk_import_state", 2 );
						try {
							$_o = bof()->bulk_importer->resync_files( $PID, $GID );
							bof()->bulk_importer->clean_covers();
							$loader->object->db_setting->set( "bulk_import_state", 0 );
						} catch( Exception $err ){
							$loader->object->db_setting->set( "bulk_import_state", 0 );
							throw new Exception( $err->getMessage() );
						}
					  return $_o;
					}
				);
			}

			if ( bof()->db->_select( array(
		    "table" => "_bof_tool_bulk_importer_files",
		    "limit" => false,
				"where" => array(
					[ "sta", "=", "2" ]
				)
		  ) ) ){
				$jobs["bulk_importer"] = array(
					"title" => "Bulk Importer",
					"exe" => function( $PID, $GID, $loader ){
						bof()->bulk_importer->import( $PID, $GID );
					}
				);
			}

			if ( bof()->db->_select( array(
		    "table" => "_bof_tool_bulk_importer_files",
		    "limit" => false,
				"where" => array(
					[ "sta", "=", "1" ],
					[ "mp3", "=", "0" ],
				)
		  ) ) ){
				$jobs["bulk_importer_con"] = array(
					"title" => "Bulk Importer - Converter",
					"exe" => function( $PID, $GID, $loader ){
						bof()->bulk_importer->convert( $PID, $GID );
					}
				);
			}

		} );

	}

}

?>
