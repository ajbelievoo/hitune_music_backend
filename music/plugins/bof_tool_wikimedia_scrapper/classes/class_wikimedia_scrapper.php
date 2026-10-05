<?php

if ( !defined( "bof_root" ) ) die;

class wikimedia_scrapper {

	protected $PID = null;
	protected $GID = null;

	protected $supported_objects = array(
		"audiobook" => [ "a_book", "a_writer", "a_translator", "a_narrator"],
		"podcast" => [ "p_show", "p_podcaster" ],
		"radio" => [ "r_station" ],
		"music" => [ "m_artist", "m_album" ],
	);

	public function setup(){

		bof()->object->core_files->add_key( "class", "wikipedia_api", bof_wikimedia_scrapper . "/classes/class_wikipedia_api.php" );

		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();

		$this->setup_cronjob();

	}

	protected function setup_admin(){

		// Link & Page
		bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){

			$highlights = $loader->highlights->getData();
			$highlights[ "scrappers_sec" ][ "items" ][ "scrapper_links" ][ "args" ][ "childs" ][] = array(
				"title" => "Wikimedia Scrapper",
				"icon" => "downloading",
				"link" => "wikimedia_scrapper"
			);
			bof()->highlights->setData( $highlights );

		} );
		bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){

			if ( is_array( $method_result ) ){
				$method_result[ "wikimedia_scrapper" ] = array(
					"title" => "Wikimedia Scrapper",
					"url" => "^wikimedia_scrapper$",
					"link" => "wikimedia_scrapper",
					"theme_file" => "parts/content_setting",
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/setting/wikimedia_scrapper/",
							"key" => "setting"
						)
					),
					"__sb_family" => "extensions",
				);
			}

		} );
		bof()->listen( "bofAdmin", "setting_pre", function( $method_args, $method_result, $loader ){

					// Supported objects & Available objects
					$plugins = bof()->object->db_setting->get( "plugins" );
					$data = bof()->object->db_setting->get( "_t_wm_scraper" );

					$available_objects = [];
					$available_objects_bofFormat = [];

					foreach( $this->supported_objects as $supported_plugin => $supported_objects ){
						if ( !in_array( "bof_{$supported_plugin}", $plugins, true ) ) continue;
						$available_objects = array_merge( $available_objects, $supported_objects );
						foreach( $supported_objects as $supported_object )
						$available_objects_bofFormat[] = [ $supported_object, $loader->object->__get( $supported_object )->bof()["label"] ];
					}

					$setting = array(
				    "groups" => array(
							"main" => array(

					      "title" => "Get covers",
								"tip" => "You can setup this tool to scrap images from Wikimedia for items without an uploaded cover image",
					      "icon" => "carpenter",
					      "inputs" => array(

									"active" => array(
			              "title" => "Active",
			              "tip" => "This tool will be executed by Cronjob if left checked",
			              "input" => array(
			                "name" => "active",
			                "type" => "checkbox",
											"value" => !empty( $data["active"] )
			              ),
			              "validator" => array(
			                "boolean",
			                array(
												"empty()"
			                )
			              )
			            ),
									"objects" => array(
			              "title" => "Active objects",
			              "tip" => "Tools will scrap cover images for selected objects only. <b>Select at least one</b>",
			              "input" => array(
			                "name" => "objects",
			                "type" => "select_m",
											"options" => $available_objects_bofFormat,
											"value" => !empty( $data["objects"] ) ? explode( ",", $data["objects"] ) : null
			              ),
			              "validator" => array(
			                "in_array",
			                array(
												"values" => $available_objects,
												"empty()"
			                )
			              )
			            ),
									"pd" => array(
			              "title" => "Check license",
			              "tip" => "Left unchecked, script will use any found image otherwise only \"Public Domain\" images will be selected",
			              "input" => array(
			                "name" => "pd",
			                "type" => "checkbox",
											"value" => !empty( $data["pd"] )
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
					$setting["functions"]["be_after"] = function( $gs, $is ){

						$data = array(
							"active" => !empty( $is["data"]["active"] ) ? $is["data"]["active"] : false,
							"objects" => !empty( $is["data"]["objects"] ) ? $is["data"]["objects"] : [],
							"pd" => !empty( $is["data"]["pd"] ) ? $is["data"]["pd"] : false,
						);

						bof()->object->db_setting->set( "_t_wm_scraper", json_encode( $data ) );

						return $is;

					};

					bof()->bofAdmin->_add_setting( "wikimedia_scrapper", $setting );

		} );

	}

	protected function _cli( $string ){
		bof()->cronjob->log_p( $this->PID, $this->GID, $string );
	}
	protected function setup_cronjob(){

		bof()->listen( "cronjob", "_clean_database_get_map_after", function( $method_args, &$map, $loader ){
			$map["_bof_tool_wikimedia"] = [];
		} );
		bof()->listen( "cronjob", "get_jobs_after", function( $method_args, &$jobs, $loader ){

			$setting = $loader->object->db_setting->get( "_t_wm_scraper" );
			if ( $setting ? !empty( $setting["active"] ) && !empty( $setting["objects"] ) : false ){

				$jobs["wikimedia_scrapper"] = array(
					"title" => "WikiMedia Scrapper",
					"interval" => 2,
					"exe" => function( $PID, $GID, $loader ){

						$this->PID = $PID;
						$this->GID = $GID;

						$setting = $loader->object->db_setting->get( "_t_wm_scraper" );

						$bofAdmin_objects = array_keys( $loader->bofAdmin->_get_objects() );
						$objects = explode( ",", $setting["objects"] );
						$public_domain_only = !empty( $setting["pd"] );
						$_s = 0;
						$_td = 0;

						foreach( $objects as $object ){

							if ( !in_array( $object, $bofAdmin_objects, true ) )
							continue;

							$the_object = $loader->object->__get( $object );
							$the_object_parsed = $loader->object->parse_caller( $the_object )->parsed;

							$max_items = 10;
							$done_items = 0;
							$more_items = true;

							while( $more_items && $done_items < $max_items ){

								bof()->db->reset_cache();

								$get_item = $the_object->select(
									array(
										"has_cover" => false,
										[ "ID", "NOT IN", "SELECT object_id FROM _bof_tool_wikimedia WHERE object_name = '{$object}' ", true ]
									),
									array(
										"limit" => 1,
										"single" => true,
										"as_widget" => true,
										"cache_load_rt" => false,
										"cache" => false,
										"order_by" => "ID",
										"order" => "ASC"
									)
								);

								$done_items++;
								$_td++;

								if ( !$get_item ){
									$more_items = false;
									break;
								}

								$item = $get_item;

								$logID = $loader->db->_insert(array(
									"table" => "_bof_tool_wikimedia",
									"set" => array(
										[ "object_name", $object ],
										[ "object_id", $item["raw"]["ID"] ]
									),
								));

								$image = null;
								try {
									$try = $loader->wikipedia_api->search( $item["title"] );
									if ( !empty( $try["query"] ) ? !empty( $try["query"]["pages"] ) : false ){

										$_pages = $try["query"]["pages"];
										$_page = reset( $_pages );

										if ( !empty( $_page["original"] ) ? !empty( $_page["original"]["source"] ) : false )
										$image = $_page["original"]["source"];
										
									}

								} catch( Exception $err ){
									$image = null;
								}

								if ( empty( $image ) ){
									$this->_cli( "Searching for {$object}:{$item["title"]} --> Nothing Found" );
									$success = -1;
								}
								elseif ( $public_domain_only ) {

									$filename = pathinfo( $image, PATHINFO_BASENAME );
									$license = $loader->wikipedia_api->license( urldecode( $filename ) );

									try {

										if ( !empty( $license["query"]["pages"]["-1"]["imageinfo"][0]["extmetadata"]["License"]["value"] ) ? $license["query"]["pages"]["-1"]["imageinfo"][0]["extmetadata"]["License"]["value"] == "pd" : false ){
											$free_to_use = true;
											$this->_cli( "Searching for {$object}:{$item["title"]} --> Successful, public_domain found" );
											$success = 1;
										}
									} catch( Exception $err ){
										$free_to_use = false;
									}

									if ( empty( $free_to_use ) ){
										$this->_cli( "Searching for {$object}:{$item["title"]} --> No Public-Domain image" );
										$image = null;
										$success = -2;
									}

								}
								elseif ( !empty( $image ) ){
									$this->_cli( "Searching for {$object}:{$item["title"]} --> Successful" );
									$success = 1;
								}

								if ( !empty( $image ) ){

									try {
										$handle_url = $loader->object->file->handle_url( $image, array(
											"object_type" => $the_object_parsed->columns["cover_id"]["bofInput"][1]["object_type"]
										) );
									} catch( Exception $err ){
										$handle_url = [ 0, 0 ];
									}

									if ( $handle_url[0] ? !empty( $handle_url[1]["file_id"] ) : false ){

										$file_id = $handle_url[1]["file_id"];
										$finalize = $loader->object->file->finalize_upload( "image", $the_object_parsed->columns["cover_id"]["bofInput"][1]["object_type"], "{$object}{$item["raw"]["ID"]}", $file_id, $item["raw"]["cover_id"] );

										if ( $file_id && $finalize ){
											$the_object->update(
												array(
													"ID" => $item["raw"]["ID"]
												),
												array(
													"cover_id" => $file_id
												)
											);
											$_s++;
										}

									}

								}

								$loader->db->_update(array(
									"table" => "_bof_tool_wikimedia",
									"where" => array(
										[ "ID", "=", $logID ]
									),
									"set" => array(
										[ "success", $success ]
									)
								));

							}

						}

						return "Searched for {$_td} item(s), found {$_s} image(s)";

					}
				);

			}

		} );

	}

}

?>
