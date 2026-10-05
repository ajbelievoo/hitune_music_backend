<?php

if ( !defined( "bof_root" ) ) die;

class podcast_cover_downloader {

	protected $PID = null;
	protected $GID = null;

	public function setup(){

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

		$this->setup_cronjob();

	}

	protected function setup_admin(){

		// Link & Page
		bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){

			$highlights = $loader->highlights->getData();
			$highlights[ "scrappers_sec" ][ "items" ][ "scrapper_links" ][ "args" ][ "childs" ][] = array(
				"title" => "Podcast Cover DLer",
				"icon" => "downloading",
				"link" => "podcast_cover_downloader"
			);
			bof()->highlights->setData( $highlights );

		} );
		bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

			if ( is_array( $method_result ) ){
				$method_result[ "podcast_cover_downloader" ] = array(
					"title" => "Podcast Cover Downloader",
					"url" => "^podcast_cover_downloader$",
					"link" => "podcast_cover_downloader",
					"theme_file" => "parts/content_setting",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/setting/podcast_cover_dler/",
							"key" => "setting"
						)
					),
					"__sb_family" => "extensions",
				);
			}

		} );
		bof()->listen( "bofAdmin", "setting_pre", function ($method_args, $method_result, $loader) {

			$setting = array(
				"groups" => array(
					"main" => array(

						"title" => "Get covers",
						"tip" => "You can setup this tool to download podcast's cover images and host them yourself",
						"icon" => "downloading",
						"inputs" => array(
							"active" => array(
								"title" => "Active",
								"col_name" => "podcast_cover_dler",
								"tip" => "This tool will be executed by Cronjob if left checked",
								"input" => array(
									"name" => "active",
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
				"action_btn_title" => "Save"
			);

			bof()->bofAdmin->_add_setting("podcast_cover_dler", $setting);

		});

	}

	public function execute_for_show($show){

		if (empty($show["rss_data_decoded"]["image"]))
		throw new Exception("No remote cover URL found");

		$handle_url = bof()->object->file->handle_url(
			$show["rss_data_decoded"]["image"],
			array(
				"object_type" => "p_show"
			)
		);

		if ( !$handle_url[0] || empty( $handle_url[1]["file_id"] ) )
		throw new Exception("Failed to download remote cover");

		$file_id = $handle_url[1]["file_id"];
		$finalize = bof()->object->file->finalize_upload(
			"image",
			"p_show",
			"p_show{$show["ID"]}",
			$file_id,
			null
		);

		bof()->object->p_show->update(
			array(
				"ID" => $show["ID"]
			),
			array(
				"cover_id" => $file_id
			)
		);

		return $file_id;

	}
	public function _cli( $string ){
		bof()->cronjob->log_p( $this->PID, $this->GID, $string );
	}
	protected function setup_cronjob(){

		bof()->listen( "cronjob", "_clean_database_get_map_after", function( $method_args, &$map, $loader ){
			$map["_bof_tool_podcast_cover_dler"] = [];
		} );
		bof()->listen( "cronjob", "get_jobs_after", function( $method_args, &$jobs, $loader ){

			if ( $loader->object->db_setting->get( "podcast_cover_dler" ) ){

				$jobs["podcast_cover_dler"] = array(
					"title" => "Podcast cover downloader",
					"interval" => 10,
					"exe" => function( $PID, $GID, $loader ){

						$this->PID = $PID;
						$this->GID = $GID;

						$shows_without_cover = bof()->object->p_show->select(
							array(
								"has_cover" => false,
								[ "ID", "NOT IN", "SELECT ID FROM _bof_tool_podcast_cover_dler", true ]
							),
							array(
								"limit" => 30,
								"single" => false
							)
						);

						if ( !$shows_without_cover )
						fall( "No unchecked show without a cover", [ "skipped" => true ] );

						$failed = $ok = 0;

						foreach( $shows_without_cover as $show_without_cover ){

							try {
								bof()->podcast_cover_downloader->execute_for_show( $show_without_cover );
								$sta = 1;
								$ok++;
							} catch( Exception|bofException $err ){
								$sta = 0;
								$errMsg = $err->getMessage();
								$failed++;
							}

							bof()->podcast_cover_downloader->_cli( "{$show_without_cover["title"]} -> " . ( $sta ? " Got one" : " Failed: {$errMsg}" ) );

							bof()->db->_insert(array(
								"table" => "_bof_tool_podcast_cover_dler",
								"set" => array(
									[ "ID", $show_without_cover["ID"] ],
									[ "sta", $sta ]
								)
							));

						}
						
						return "Checked for ".($failed+$ok)." item(s), downloaded ".($ok)." item(s), failed to download ".($failed)." item(s)";

					}
				);

			}

		} );

	}

}

?>
