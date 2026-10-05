<?php

if ( !defined( "bof_root" ) ) die;

class sitemap_generator {

	protected $objects_conf = array(
		"page" => array(
			"where" => array(
				"active" => 1
			),
			"p" => "1.0",
			"c" => "daily"
		),
		"b_post" => array(
			"where" => array(
				"published" => 1
			),
			"p" => "1.0",
			"c" => "weekly"
		),
		"p_creator" => array(
			"p" => "0.7",
			"c" => "monthly"
		),
		"m_artist" => array(
			"p" => "0.7",
			"c" => "weekly"
		),
		"m_album" => array(
			"p" => "0.6",
			"c" => "monthly"
		),
		"a_book" => array(
			"p" => "0.6"
		),
		"r_station" => array(
			"p" => "0.6",
			"where" => array(
				"active" => 1
			)
		),
		"ugc_playlist" => array(
			"where" => array(
				"is_private" => 0
			)
		)
	);

	public function setup(){
		if ( bof()->getName() == "bof_admin" )
		$this->setup_admin();
		$this->setup_cronjob();
	}
	
	protected function setup_admin(){

		bof()->object->endpoint->add("sitemap_generator_execute", array(
			"url" => "sitemap_generator_execute",
			"groups" => ["admin"],
			"executers" => array(
				bof_sitemap_generator . "/endpoints/endpoint_sitemap_generator_execute.php"
			)
		));

		bof()->object->endpoint->add("sitemap_generator_cancel", array(
			"url" => "sitemap_generator_cancel",
			"groups" => ["admin"],
			"executers" => array(
				bof_sitemap_generator . "/endpoints/endpoint_sitemap_generator_cancel.php"
			)
		));

		bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {
			bof()->highlights
			->new_item(
				"extensions_links",
				array(
					"ID" => "zzzzz2",
					"icon" => "map",
					"title" => "Sitemap Generator",
					"link" => "sitemap_generator_setting",
					"ext_classes" => "small",
				),
				false
			);
		});

		bof()->listen("client_config", "get_pages_after", function ($method_args, &$method_result, $loader) {

			if (is_array($method_result)) {
				$method_result["sitemap_generator"] = array(
					"title" => "Sitemap Generator Setting",
					"url" => "^sitemap_generator_setting$",
					"link" => "sitemap_generator_setting",
					"theme_file" => "parts/content_setting",
					"extenders" => (object) array(
						"bof_sitemap_generator_css" => (object) array(
							"type" => "css",
							"name" => "bof_sitemap_generator",
							"path" => web_address . "/plugins/bof_tool_sitemap_generator/assets/admin.css",
							"dir" => false
						),
						"bof_sitemap_generator_js" => (object) array(
							"type" => "js",
							"name" => "bof_sitemap_generator",
							"path" => web_address . "/plugins/bof_tool_sitemap_generator/assets/admin.js" ,
							"dir" => false
						)
					),
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/setting/sitemap_generator/",
							"key" => "setting"
						)
					),
					"events" => (object) array(
						"ready" => "bof_sitemap_generator.set",
						"unloading" => "bof_sitemap_generator.unset",
					),
					"__sb_family" => "extensions",
				);
			}
		});

		bof()->bofAdmin->_add_setting("sitemap_generator", array(
			"functions" => array(
				"ui_pre" => function ($groups) {

					$objects = bof()->bofAdmin->_get_objects();
					foreach ($objects as $object_name => $object) {
						if (empty($object["seo"])) continue;
						$the_object = bof()->object->__get( $object_name );
						$groups["sitemap"]["inputs"]["objects"]["input"]["options"][] = [ $object_name, $the_object->bof()["label"] ];
						if ( !in_array( $object_name, [ "b_post", "ugc_playlist" ], true ) ){
							$groups["sitemap"]["inputs"]["objects"]["input"]["options"][] = [ "{$object_name}_widgets", "{$the_object->bof()["label"]}'s widgets" ];
						}
					}

					return $groups;

				},
				"ui_after" => function ($groups, &$_output) {

					$_output["state"] = bof()->object->db_setting->get("_smg_state",0);
					if ($_output["state"] == 2) {
						$_smg_state = bof()->object->db_setting->select(["var" => "_smg_state"]);
						if (!empty($_smg_state["time_update"]) ? (time() - strtotime($_smg_state["time_update"]) > 6 * 60 * 60) : false) {
							$_output["state"] = 0;
							bof()->object->db_setting->set("_smg_state", 0);
						}
					}
				},
				"be_pre" => function ($groups) {

					$objects = bof()->bofAdmin->_get_objects();
					foreach ($objects as $object_name => $object) {
						if (empty($object["seo"])) continue;
						$the_object = bof()->object->__get( $object_name );
						$groups["sitemap"]["inputs"]["objects"]["validator"][1]["values"][] = $object_name;
						if ( !in_array( $object_name, [ "b_post", "ugc_playlist" ], true ) ){
							$groups["sitemap"]["inputs"]["objects"]["validator"][1]["values"][] = "{$object_name}_widgets";
						}
					}

					return $groups;

				},
				"be_after" => function( $groups, $inputs ){
					if ( !empty( $inputs["set"]["_smg_objects"] ) ? preg_match( "/__all__/", $inputs["set"]["_smg_objects"] ) : false )
					$inputs["set"]["_smg_objects"] = "__all__";
					return $inputs;
				}
			),
			"groups" => array(
				"sitemap" => array(
					"title" => "Setting",
					"icon" => "table_chart",
					"inputs" => array(
						"objects" => array(
							"title" => "Objects",
							"tip" => "Choose the objects you wish to include in sitemap",
							"col_name" => "_smg_objects",
							"input" => array(
								"name" => "objects",
								"type" => "select_m",
								"options" => array(
									[ "__all__", "All" ]
								),
								"value" => "__all__"
							),
							"validator" => array(
								"in_array",
								array(
									"values" => [ "__all__" ]
								)
							)
						),
					)
				),
			),
			"action_btn_title" => "Save setting"
		));

	}

	protected function add_link( $PID, $GID, $sitemap_path, $loc, $lastmod, $changefreq, $priority ){

		//$loc = mb_convert_encoding($loc, 'UTF-8', 'auto');
		//$loc = rawurlencode($loc);
		//$loc = str_replace('%2F', '/', $loc);
		//$loc = htmlspecialchars($loc, ENT_XML1, 'UTF-8');

		if ( substr( $loc, 0, 1 ) == "/" )
		$loc = substr( $loc, 1 );
		$loc = str_replace( "//", "/", $loc );

		$encoded_url = rawurlencode($loc);
		$encoded_url = str_replace(['%3A', '%2F', '%3F', '%26', '%3D'], [':', '/', '?', '&', '='], $encoded_url);
		$escaped_url = htmlspecialchars($encoded_url, ENT_XML1, 'UTF-8');

		file_put_contents($sitemap_path, ( '  <url>' . PHP_EOL .
		'    <loc>' . web_address . $escaped_url . '</loc>' . PHP_EOL .
		'    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL .
		'    <changefreq>' . $changefreq . '</changefreq>' . PHP_EOL .
		'    <priority>' . $priority . '</priority>' . PHP_EOL .
		'  </url>' . PHP_EOL ) , FILE_APPEND);

	}
	public function run_cronjob($PID, $GID){

		bof()->object->db_setting->set( "_smg_state", 2 );
		bof()->db->disable_cache();

		$objects = bof()->object->db_setting->get("_smg_objects");
		if (!$objects)
			fall("No objects selected");

		if ($objects == "__all__") {

			$objects = [];
			foreach (bof()->bofAdmin->_get_objects() as $object_name => $object) {
				if (empty($object["seo"])) continue;
				$objects[] = $object_name;
				if (!in_array($object_name, ["b_post", "ugc_playlist"], true))
					$objects[] = "{$object_name}_widgets";
			}
		} else {
			$objects = explode(",", $objects);
		}

		$sitemaps = [];

		bof()->file->rmdir( base_root . "/sitemaps" );
		bof()->file->mkdir( base_root . "/sitemaps" );

		$sitemap_path = base_root . "/sitemaps/sitemap_def.xml";
		$sitemaps[] = "sitemap_def.xml";

		file_put_contents($sitemap_path, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
		file_put_contents($sitemap_path, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL, FILE_APPEND);
		foreach (bof()->object->menu->get_app_pages() as $default_app_pageURL => $default_app_pageLabel) {

			if (preg_match("/(user_auth|upload|user_area|user_pay|user_withdrawal|user_library|user_edit|user_verify|affiliate)(?:\?.*)?/", $default_app_pageURL))
				continue;

			$this->add_link( 
				$PID, 
				$GID, 
				$sitemap_path, 
				$default_app_pageURL, 
				date("Y-m-d"), 
				"weekly", 
				"0.8" 
			);

		}
		file_put_contents($sitemap_path, '</urlset>', FILE_APPEND);

		$td = 0;
		foreach ($objects as $object) {

			if ( bof()->general->endswith($object, "_widgets") )
			continue;

			$the_object = bof()->object->__get( $object );
			$object_conf = !empty( $this->objects_conf[ $object ] ) ? $this->objects_conf[ $object ] : [];
			$object_has_items = true;

			$done = 0;
			$done_r = 100;
			$done_M = 19600;

			$ID = 0;

			$sitemap_path = base_root . "/sitemaps/sitemap_{$object}.xml";
			$sitemaps[] = "sitemap_{$object}.xml";

			file_put_contents($sitemap_path, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
			file_put_contents($sitemap_path, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL, FILE_APPEND);
	
			while( $object_has_items ){

				$object_item = $the_object->select(
					array_merge(
						array(
							[ "ID", ">", $ID ]
						),
						!empty( $object_conf["where"] ) ? $object_conf["where"] : []
					),
					array(
						"single" => true,
						"limit" => 1,
						"order_by" => "ID",
						"order" => "ASC"
					)
				);

				if ( !$object_item ){
					$object_has_items = false;
					bof()->cronjob->log_p($PID, $GID, "Object {$object} all items added");
					break;
				}

				$ID = $object_item["ID"];

				$__time = false;
				if ( !empty( $object_item["time_add"] ) )
				$__time = $object_item["time_add"];
			    elseif( !empty( $object_item["time_publish"] ) )
				$__time = $object_item["time_publish"];
				elseif( !empty( $object_item["time_created"] ) )
				$__time = $object_item["time_created"];

				$__time = !empty( $__time ) ? strtotime( $__time ) : time();

				$this->add_link( 
					$PID,
					$GID, 
					$sitemap_path,
					$object_item["url"], 
					date("Y-m-d", $__time), 
					!empty( $object_conf["c"] ) ? $object_conf["c"] : "monthly", 
					!empty( $object_conf["p"] ) ? $object_conf["p"] : "0.4" 
				);

				$done++;

				if (in_array("{$object}_widgets", $objects, true)&&!in_array($object,["m_track","p_episode","a_book"],true)) {

					$item_page = $the_object->select(
						array(
							"ID" => $object_item["ID"]
						),
						array(
							"client_single" => true,
						)
					);

					if ( !empty( $item_page["widgets"] ) ){
						foreach( $item_page["widgets"] as $item_page_widget ){

							if ( empty( $item_page_widget["display"]["pagination"] ) ) continue;
							$parse = bof()->bofClient->__parse_widget( $object, $the_object, $item_page_widget );
							if (!empty($parse) ? !empty($parse["display"]["link"]) : false) {
								$this->add_link(
									$PID,
									$GID,
									$sitemap_path,
									$parse["display"]["link"],
									date("Y-m-d", $__time),
									!empty($object_conf["c"]) ? $object_conf["c"] : "monthly",
									"0.3"
								);
								$done++;
							}
							
						}
					}

				}

				$dh = $done ? floor( $done / 100 ) * 100 : 0;
				$dM = $done ? floor( $done / 20000 ) * 20000 : 0;
				if ( $dh > $done_r ){
					$done_r = $dh;
					bof()->cronjob->log_p($PID, $GID, "Object {$object} added {$done} items so far");
				}
				if ( $dM > $done_M ){

					file_put_contents($sitemap_path, '</urlset>', FILE_APPEND);

					$sitemap_path = base_root . "/sitemaps/sitemap_{$object}".(floor( $done / 20000 )).".xml";
					$sitemaps[] = "sitemap_{$object}".(floor( $done / 20000 )).".xml";

					file_put_contents($sitemap_path, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
					file_put_contents($sitemap_path, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL, FILE_APPEND);

					$done_M = $dM;

				}


			}

			file_put_contents($sitemap_path, '</urlset>', FILE_APPEND);

			bof()->db->_update(array(
				"table" => "_bof_setting",
				"set" => array(
					[ "time_update", "now()", true ]
				),
				"where" => array(
					[ "var", "=", "crond_stat" ],
				),
				"cache_load_rt" => false
			));

			$td = $td + $done;

		}

		$sitemaps_path = base_root . "/sitemaps.xml";
		if ( is_file( $sitemaps_path ) ) unlink( $sitemaps_path );

		file_put_contents( $sitemaps_path, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL );
		file_put_contents( $sitemaps_path, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL, FILE_APPEND );
		foreach( $sitemaps as $sitemap ){
			file_put_contents( 
				$sitemaps_path,
				'  <sitemap>'.PHP_EOL.
				'    <loc>'.web_address.'sitemaps/'.$sitemap.'</loc>'.PHP_EOL.
				'    <lastmod>'.date("Y-m-d").'</lastmod>'.PHP_EOL.
				'  </sitemap>' . PHP_EOL,
				FILE_APPEND
		    );
		}
		file_put_contents( $sitemaps_path, '</sitemapindex>', FILE_APPEND );

		bof()->object->db_setting->set("_smg_state", "0");

		return "Added ".number_format($td)." items into " . ($sitemaps?count($sitemaps):0) . " sitemaps";

	}
	protected function setup_cronjob(){
		bof()->listen("cronjob", "get_jobs_after", function ($method_args, &$jobs, $loader) {

			$state = bof()->object->db_setting->get("_smg_state");

			if ($state === 1 || $state === "1") {

				$jobs["sitemap_generator"] = array(
					"title" => "Sitemap Generator",
					"interval" => 1,
					"exe" => function ($PID, $GID) {
						return bof()->sitemap_generator->run_cronjob($PID, $GID);
					}
				);
			}
		});
	}

}

?>
