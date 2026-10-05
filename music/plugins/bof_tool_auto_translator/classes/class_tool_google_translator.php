<?php

if (!defined("bof_root")) die;

class tool_google_translator
{

	public function setup()
	{

		if (bof()->getName() == "bof_admin")
			$this->setup_admin();
	}
	protected function setup_admin()
	{

		bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {

			$highlights = $loader->highlights->getData();
			$highlights["extensions_links"]["items"]["tools_links"]["args"]["childs"][] = array(
				"title" => "Google Translator",
				"icon" => "g_translate",
				"link" => "tool_google_translator"
			);
			bof()->highlights->setData($highlights);
		});
		bof()->listen("client_config", "get_pages_after", function ($method_args, &$method_result, $loader) {

			if (is_array($method_result)) {
				$method_result["g_translate"] = array(
					"title" => "Google Translator",
					"url" => "^tool_google_translator$",
					"link" => "tool_google_translator",
					"theme_file" => "parts/content_setting",
					"extenders" => (object) array(
						"g_translate_admin_css" => (object) array(
							"type" => "css",
							"name" => "gtranslate_admin_css",
							"path" => web_address . "/plugins/bof_tool_google_translator/theme/admin_theme.css",
							"dir" => false
						),
						"g_translate_admin_js" => (object) array(
							"type" => "js",
							"name" => "bof_gtranslate_admin_js",
							"path" => web_address . "/plugins/bof_tool_google_translator/theme/admin_theme.js",
							"dir" => false
						)
					),
					"becli" => array(
						(object) array(
							"endpoint" => "bofAdmin/setting/g_translate/",
							"key" => "setting"
						)
					),
					"events" => (object) array(
						"ready" => "bof_gtranslate_admin_js.set",
						"unloading" => "bof_gtranslate_admin_js.unset",
					),
					"__sb_family" => "extensions",
				);
			}
		});

		$setting = array(
			"functions" => array(
				"ui_pre" => function ($groups) {

					$langs = bof()->object->language->select(
						array(
							"code2_not" => "en"
						),
						array(
							"limit" => false,
							"single" => false,
							"clean" => false
						)
					);

					if (!empty($langs)) {
						foreach ($langs as $lang) {
							$groups["gtranslate"]["inputs"]["target_lang"]["input"]["options"][] = [$lang["code2"], $lang["name"]];
						}
					}

					return $groups;
				},
				"be_after" => function ($groups, $items) {
					if (empty($items["report"]["fail"])) {

						$data = $items["data"];
						$translate = bof()->tool_google_translator->translate($data);

						if (empty($translate)) {
							$items["has_more"] = false;
						} else {
							$items["has_more"] = true;
							$items["done_job_data"] = $translate;
						}
					}
					return $items;
				}
			),
			"groups" => array(
				"gtranslate" => array(
					"title" => "Google Translator",
					"icon" => "g_translate",
					"inputs" => array(
						"target_lang" => array(
							"title" => "Target language",
							"tip" => "Select the language you want to translate to. Language should have been added in \"Setting -> Languages\" before you can translate into it",
							"input" => array(
								"name" => "target_lang",
								"type" => "select_i",
								"options" => []
							),
							"validator" => array(
								"string",
								array(
									"min_length" => 2,
									"max_length" => 2
								)
							)
						),
						"translate_language_items" => array(
							"title" => "Translate language items",
							"tip" => "Translate untranslated items available in \"Setting -> Languages -> Target language\"",
							"input" => array(
								"name" => "translate_language_items",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()",
								)
							)
						),
						"translate_pb_items" => array(
							"title" => "Translate PageBuilder items",
							"tip" => "Translate untranslated titles & sub-titles available in \"Setting -> PageBuilder\"",
							"input" => array(
								"name" => "translate_pb_items",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()",
								)
							)
						),
						"translate_mb_items" => array(
							"title" => "Translate MenuBuilder items",
							"tip" => "Translate untranslated titles available in \"Setting -> MenuBuilder\"",
							"input" => array(
								"name" => "translate_mb_items",
								"type" => "checkbox",
							),
							"validator" => array(
								"boolean",
								array(
									"empty()",
								)
							)
						),
					)
				),
				"gtranslate_result" => array(
					"title" => "Google Translator:: Query",
					"icon" => "g_translate",
					"inputs" => [],
				),
			),
			"action_btn_title" => "Start"
		);
		bof()->bofAdmin->_add_setting("g_translate", $setting);
	}

	public function translate($data)
	{

		$target_language = $data["target_lang"];
		$translate_language_items = $data["translate_language_items"];
		$translate_mb_items = $data["translate_mb_items"];
		$translate_pb_items = $data["translate_pb_items"];

		$translate = false;

		if ($translate_language_items)
			$translate = bof()->tool_google_translator->translate_items($target_language);

		if ($translate)
			return $translate;

		if ($translate_pb_items)
			$translate = bof()->tool_google_translator->translate_pb($target_language);

		if ($translate)
			return $translate;

		if ($translate_mb_items)
			$translate = bof()->tool_google_translator->translate_mb($target_language);

		if ($translate)
			return $translate;

		return false;
	}
	public function translate_items($target_language)
	{

		$get_untranslated_item = bof()->object->language_item->select(
			array(
				"lang_code2" => "en",
				["hook", "NOT IN", "SELECT hook FROM _d_languages_items as x2 WHERE x2.lang_code2 = '{$target_language}' AND x2.text IS NOT NULL AND x2.text != '' AND x2.text != ' '", true]
			),
			array()
		);

		if (!$get_untranslated_item)
			return false;

		$text = $get_untranslated_item["text"];

		$translate = bof()->google_translate->translate($text, "en", $target_language);
		if (empty($translate)) $translate = $text;

		bof()->object->language_item->create(
			array(
				"full_hook" => $target_language . "_" . $get_untranslated_item["hook"],
			),
			array(
				"lang_code2" => $target_language,
				"text" => $translate,
				"hook" => $get_untranslated_item["hook"]
			),
			array(
				"lang_code2" => $target_language,
				"text" => $translate,
				"hook" => $get_untranslated_item["hook"]
			),
		);

		return "Translated <i>{$text}</i> to : <b>{$translate}</b>";
	}
	public function translate_pb($target_language)
	{

		$get_all_widgets = bof()->object->page_widget->select(
			array(),
			array(
				"empty_select" => true,
				"single" => false,
				"limit" => false
			)
		);

		if (empty($get_all_widgets))
			return false;

		foreach ($get_all_widgets as $widget) {
			$widget_a = $widget["args_decoded"];
			if (empty($widget_a)) continue;
			if (empty($widget_a["wid_title"])) continue;
			if (!empty($widget_a["wid_title_{$target_language}"])) continue;
			$untranslated_widget = $widget;
			break;
		}

		if (empty($untranslated_widget))
			return false;

		$varNames = ["wid_title", "wid_sub_data", "btn_title_1", "btn_title_2"];

		foreach ($varNames as $varName) {
			if (!empty($widget_a[$varName]) && empty($widget_a["{$varName}_{$target_language}"])) {

				$translateVarText = bof()->google_translate->translate($widget_a[$varName], "en", $target_language);
				if (!empty($translateVarText))
					$widget_a["{$varName}_{$target_language}"] = $translateVarText;
				sleep(1);
			}
		}

		if (!empty($widget_a["features"])) {
			try {
				$features = json_decode(urldecode($widget_a["features"]), true);
				foreach ($features as &$feature) {
					foreach (["title", "text"] as $varName2) {
						if (!empty($feature[$varName2]) && empty($feature["{$varName2}_{$target_language}"])) {
							$translateVarText2 = bof()->google_translate->translate($feature[$varName2], "en", $target_language);
							if (!empty($translateVarText2))
								$feature["{$varName2}_{$target_language}"] = $translateVarText2;
							sleep(1);
						}
					}
				}
				$widget_a["features"] = rawurlencode(json_encode($features));
			} catch (Exception | bofException | Error $err) {
			}
		}

		bof()->object->page_widget->update(
			array(
				"ID" => $untranslated_widget["ID"]
			),
			array(
				"args" => json_encode($widget_a)
			)
		);

		return "Translated <i>{$widget_a["wid_title"]}</i> widget";
	}
	public function translate_mb($target_language)
	{

		$get_all_menus = bof()->object->menu->select(
			array(),
			array(
				"empty_select" => true,
				"single" => false,
				"limit" => false
			)
		);

		if (empty($get_all_menus))
			return false;

		foreach ($get_all_menus as $menu) {

			if (empty($menu["structure_decoded"])) continue;
			foreach ($menu["structure_decoded"] as &$menu_ip) {

				if (!empty($menu_ip["title"]) && empty($menu_ip["title_{$target_language}"])) {

					$translate = bof()->google_translate->translate($menu_ip["title"], "en", $target_language);
					$menu_ip["title_{$target_language}"] = $translate ? $translate : $menu_ip["title"];
					$translatedMenu = $menu;
					break;
				}
				if (!empty($menu_ip["childs"])) {
					foreach ($menu_ip["childs"] as &$child) {
						if (!empty($child["title"]) && empty($child["title_{$target_language}"])) {

							$translate = bof()->google_translate->translate($child["title"], "en", $target_language);
							$child["title_{$target_language}"] = $translate ? $translate : $child["title"];
							$translatedMenu = $menu;
							break;
						}
					}
					if (!empty($translatedMenu))
						break;
				}
			}
			if (!empty($translatedMenu))
				break;
		}

		if (!empty($translatedMenu)) {

			bof()->object->menu->update(
				array(
					"ID" => $translatedMenu["ID"]
				),
				array(
					"structure" => json_encode($translatedMenu["structure_decoded"])
				),
				array(
					"structure" => json_encode($translatedMenu["structure_decoded"])
				),
			);

			return "Translated <i>{$translatedMenu["name"]}</i>'s item'";
		}
	}
}
