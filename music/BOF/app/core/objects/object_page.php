<?php

if (!defined("bof_root")) die;

class object_page extends bof_type_object
{

  public function bof()
  {
    return array(
      "name" => "page",
      "label" => "Page",
      "icon" => "grid_view",
      "db_table_name" => "_d_pages",
      "query_be_share" => true
    );
  }
  public function columns()
  {
    return array(
      "name" => array(
        "public" => true,
        "label" => "Page<br>Name",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ($displayItem, $item, $displayData) {
              $displayData["sub_data"] = ($item["pre_design"] ? "<b style = 'color:rgb(var(--theme_color))'>Custom page</b>" : "") . $item["comment"];
              return $displayData;
            },
          ),
          "object" => array(
            "seo_slug_source" => true,
            "required" => true
          )
        )
      ),
      "comment" => array(
        "label" => "Comment",
        "tip" => "A few words to remember this page by",
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true,
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "textarea"
        ),
        "bofAdmin" => array(
          "object" => array()
        )
      ),
      "class" => array(
        "public" => true,
        "label" => "CSS classes",
        "tip" => "Enter custom CSS classes space separated. For example <b>no_sidebar fw_container class3 class4</b><br>Shady custom classes: <br><b>no_sidebar</b>: Will remove sidebar from page<br><b>fw_container</b>: Will make widgets full-width in no_sidebar pages<br><b>no_footer</b>: Will hide footer",
        "validator" => array(
          "string",
          array(
            "empty()"
          ),
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "object" => array()
        )
      ),
      "pre_design" => array(
        "public" => true,
        "validator" => array(
          "string",
          array(
            "empty()"
          ),
        ),
      ),
      "active" => array(
        "label" => "Active",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "input" => array(
          "type" => "checkbox"
        ),
        "bofAdmin" => array(
          /*"list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => [ "activate", "deactivate" ]
            )
          ),*/
          "object" => []
        )
      ),
      "public" => array(
        "label" => "Force Public",
        "tip" => "If your app is private (requiring users to log in for access), enabling this option allows all visitors, even non-logged-in users, to access this specific page, effectively making it public",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "input" => array(
          "type" => "checkbox"
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "private" => array(
        "label" => "Private",
        "tip" => "If checked, only logged-in users can access this page",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "input" => array(
          "type" => "checkbox"
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "private_rules" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),
      "time_update" => array(
        "label" => "Last<br>Update",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "time"
          )
        )
      ),
    );
  }
  public function bof_columns()
  {
    return array(
      "ID",
      "hash",
      "time_add",
      "seo",
    );
  }
  public function stats_columns()
  {
    return array(
      "widgets" => array(
        "label" => "Widgets",
      ),
    );
  }
  public function relations()
  {
    return array(
      "widgets" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "page",
          "parent_object_stats_column" => "s_widgets",
          "child_object" => "page_widget",
          "child_object_selector_column" => "page_id",
          "delete_child_too" => true,
        ),
      ),
    );
  }
  public function selectors()
  {
    return array(
      "query" => ["name", "LIKE%lower"],
      "active" => function ($val) {
        if ($val === 0 || $val === "0" || $val === false)
          return ["active", "=", "0"];
        elseif ($val === 1 || $val === "1" || $val === true)
          return ["active", "=", "1"];
      }
    );
  }
  public function bof_admin()
  {
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "page",
        "list_page_url" => "pages",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "filters" => array(
        "active" => array(
          "name" => "active",
          "title" => "Status",
          "input" => array(
            "name" => "active",
            "type" => "select_i",
            "options" => array(
              [0, "in-active"],
              [1, "active"],
              ["__all__", "all"]
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => ["__all__", "0", "1"]
            )
          )
        ),
      ),
      "buttons" => array(
        "activate" => array(
          "id" => "activate",
          "label" => "Activate",
          "payload" => array(
            "post" => array(
              "__action" => "activate"
            )
          )
        ),
        "deactivate" => array(
          "id" => "deactivate",
          "label" => "De-Activate",
          "payload" => array(
            "post" => array(
              "__action" => "deactivate"
            )
          )
        ),
      ),
      "buttons_renderer" => function ($item, $buttons) {

        if ($item["active"])
          unset($buttons["activate"]);

        if (!$item["active"])
          unset($buttons["deactivate"]);

        if (!$item["pre_design"])
          $buttons["export"] = array(
            "ID" => "export",
            "label" => "Export",
            "attr" => "data-id='{$item["ID"]}'"
          );

        return $buttons;
      },
      "list" => array(
        "name" => null,
        "seo_url" => array(
          "type" => "simple",
          "label" => "URL"
        ),
        "time_update" => null,
      ),
      "object" => array(
        "name" => null,
        "comment" => null,
        "class" => null,
        "active" => null,
        "public" => null,
        "private" => null,
        "private_rules" => array(
          "label" => "Private - UserRoles",
          "tip" => "If selected, only users belonging to selected user-roles can access the page. Otherwise all logged-in users can access this page",
          "display_on" => array(
            "private" => ["equal", "1"]
          ),
          "bofInput" => array(
            "object",
            array(
              "type" => "user_role",
              "multi" => true,
              "autoload" => false
            )
          )
        ),
      ),
      "object_be_renderer" => function ($_inputs, $request) {

        $_inputs["set"]["private_rules"] = $_inputs["update"]["private_rules"] = json_encode(array(
          "user_roles" => !empty($_inputs["data"]["private_rules"]) ? $_inputs["data"]["private_rules"] : null
        ));

        return $_inputs;
      },
      "object_ui_renderer" => function ($object, $parsed_object, $object_args, $request, $display, &$data) {

        $theme = bof()->theme->get();
        if (!empty($theme["admin_assets"]["page_builder"])) {
          $data["theme_extra"] = !empty($data["theme_extra"]) ? $data["theme_extra"] : [];
          foreach ($theme["admin_assets"]["page_builder"] as $at => $al) {
            foreach ($al as $aa) {
              $data["theme_extra"][$at][] = $aa;
            }
          }
        }

        $data["indexed_non_default_langs"] = bof()->object->language->get_all();

        $content = $request["type"] == "single" ? $data["request"]["content"][$data["request"]["IDS"][0]] : null;
        $data["display"]["private_rules"]["input"]["value"] = $data["display"]["private_rules"]["bofInput"][1]["value"] = !empty($content["private_rules_decoded"]["user_roles"]) ? $content["private_rules_decoded"]["user_roles"] : false;

        $_parse_input = bof()->bofInput->parse($data["display"]["private_rules"]);
        $data["display"]["private_rules"] = $_parse_input["data"];
      },
      "actions" => array(
        "deactivate" => function ($ids) {
          bof()->object->page->update(array(
            "ID_in" => $ids
          ), array(
            "active" => 0
          ));
          return [true, "deactivated"];
        },
        "activate" => function ($ids) {
          bof()->object->page->update(array(
            "ID_in" => $ids
          ), array(
            "active" => 1
          ));
          return [true, "activated"];
        },
        "export" => function ($ids) {

          if (count(explode(",", $ids)) > 1)
            return;

          $exportData = bof()->object->page->export($ids);

          return [true, "ok", array(
            "json" => $exportData
          )];
        }
      ),
    );
  }
  public function bof_client()
  {
    return array(
      "single_url_prefix" => false,
    );
  }

  public $widgets = array(
    "rules" => array(
      "user_roles_only" => array(
        "group" => "z",
        "title" => "User Roles ( Only )",
        "tip" => "If selected, this widget will ONLY appear for users belonging to selected user groups. Left empty will result in widget being displayed for everyone",
        "bofInput" => array(
          "object",
          array(
            "type" => "user_role"
          )
        ),
        "input" => array(
          "name" => "user_roles_only"
        )
      ),
      "user_roles_exclude" => array(
        "group" => "z",
        "title" => "User Roles ( Exclude )",
        "tip" => "If selected, this widget will NOT appear for users belonging to selected user groups. Left empty will result in widget being displayed for everyone",
        "bofInput" => array(
          "object",
          array(
            "type" => "user_role"
          )
        ),
        "input" => array(
          "name" => "user_roles_exclude"
        )
      ),
    ),
    "groups" => array(
      "content" => array(
        "title" => "Content",
        "icon" => "save",
        "inputs" => array(
          "wid_title" => array(
            "group" => "a",
            "title" => "Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_title",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()"
              )
            )
          ),
          "wid_sub_data" => array(
            "group" => "a",
            "title" => "Lower Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_sub_data",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()"
              )
            )
          ),
          "wid_link" => array(
            "group" => "a",
            "title" => "Link",
            "tip" => "You can set an internal or extenal link for `Title` of this widget",
            "input" => array(
              "type" => "text",
              "name" => "wid_link",
            ),
            "validator" => array(
              "string",
              array(
                "empty()"
              )
            )
          ),
          "wid_pagination" => array(
            "group" => "a",
            "title" => "Pagination",
            "tip" => "Allow users to explore more of this content. Adds a link to `Title` of this widget to allow pagination. `Link` will over-ride this option, leave that empty if you wish to enable pagination",
            "input" => array(
              "type" => "checkbox",
              "name" => "wid_pagination",
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()"
              )
            )
          ),
          "wid_bg_img" => array(
            "group" => "b",
            "title" => "Background Image",
            "input" => array(
              "name" => "wid_bg_img",
            ),
            "bofInput" => array(
              "file",
              array(
                "type" => "image",
                "object_type" => "page_widget_bg"
              )
            )
          ),
          "wid_limit" => array(
            "group" => "b",
            "title" => "Limit",
            "tip" => "Number of displayed items",
            "input" => array(
              "type" => "text",
              "name" => "wid_limit",
              "value" => 10
            ),
            "validator" => array(
              "int",
              array(
                "min" => 1,
                "max" => 100
              )
            )
          ),
          "wid_type" => array(
            "group" => "b",
            "title" => "Type",
            "input" => array(
              "type" => "select_i",
              "name" => "wid_type",
              "value" => "slider",
              "options" => array(
                ["slider", "Slider"],
                ["table", "Table"],
                ["list", "List"],
                // [ "chart", "Chart" ]
              )
            ),
            "validator" => array(
              "in_array",
              array(
                "values" => ["slider", "table", "list", "chart"]
              )
            )
          ),
          "wid_slider_size" => array(
            "group" => "b",
            "title" => "Slider - Item size",
            "display_on" => array(
              "wid_type" => ["equal", "slider"]
            ),
            "input" => array(
              "type" => "select_i",
              "name" => "wid_slider_size",
              "value" => "medium",
              "options" => array(
                ["small", "Small"],
                ["medium", "Medium"],
                ["large", "Large"],
              )
            ),
            "validator" => array(
              "in_array",
              array(
                "empty()",
                "values" => ["small", "medium", "large"]
              )
            )
          ),
          "wid_slider_mason" => array(
            "group" => "b",
            "title" => "Slider - Static",
            "tip" => "If enabled, items will be divied into `slider rows` and users can `slide` to see them all. Otherwise they will make as many rows as required",
            "display_on" => array(
              "wid_type" => ["equal", "slider"]
            ),
            "input" => array(
              "type" => "checkbox",
              "name" => "wid_slider_mason",
            ),
            "validator" => array(
              "boolean",
              array(
                "empty()",
              )
            )
          ),
          "wid_slider_rows" => array(
            "group" => "b",
            "title" => "Slider - Number of rows",
            "tip" => "Items will be divied into rows. For example if you set `limit` to 20 and `number of rows` to 5, there will be 4 items in each row",
            "display_on" => array(
              "wid_type" => ["equal", "slider"],
              "wid_slider_mason" => ["equal", false]
            ),
            "input" => array(
              "type" => "select_i",
              "name" => "wid_slider_rows",
              "value" => "1",
              "options" => array(
                ["1", "1"],
                ["2", "2"],
                ["3", "3"],
                ["4", "4"],
                ["5", "5"],
                ["6", "6"],
              )
            ),
            "validator" => array(
              "int",
              array(
                "empty()",
                "min" => 1,
                "max" => 6
              )
            )
          ),
          "wid_list_columns" => array(
            "group" => "b",
            "title" => "List - Columns",
            "display_on" => array(
              "wid_type" => ["equal", "list"]
            ),
            "input" => array(
              "type" => "select_i",
              "name" => "wid_list_columns",
              "options" => array(
                ["1", "1"],
                ["2", "2"],
                ["3", "3"],
              )
            ),
            "validator" => array(
              "in_array",
              array(
                "empty()",
                "values" => ["1", "2", "3"]
              )
            )
          ),
        ),
        "groups" => array(
          "a" => "General",
          "b" => "Layout"
        )
      ),
      "design" => array(
        "title" => "Design",
        "icon" => "palette",
        "inputs" => array()
      ),
      "other" => array(
        "title" => "Other",
        "icon" => "carpenter",
        "inputs" => array()
      ),
    ),
    "items" => array(
      "search_form" => array(
        "group" => "other",
        "icon" => "search",
        "label" => "Search",
        "groups" => array(
          "a" => "General"
        ),
        "inputs" => array(
          "wid_title" => array(
            "group" => "a",
            "title" => "Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_title",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false
              )
            )
          ),
          "wid_sub_data" => array(
            "group" => "a",
            "title" => "Lower Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_sub_data",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false
              )
            )
          ),
          "wid_bg_img" => array(
            "group" => "a",
            "title" => "Background Image",
            "input" => array(
              "name" => "wid_bg_img",
            ),
            "bofInput" => array(
              "file",
              array(
                "type" => "image",
                "object_type" => "page_widget_bg"
              )
            )
          ),
        )
      ),
      "html" => array(
        "group" => "other",
        "icon" => "code",
        "label" => "Html",
        "groups" => array(
          "a" => "General",
          "b" => "HTML"
        ),
        "inputs" => array(
          "wid_title" => array(
            "group" => "a",
            "title" => "Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_title",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "wid_sub_data" => array(
            "group" => "a",
            "title" => "Lower Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_sub_data",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "wid_bg_img" => array(
            "group" => "a",
            "title" => "Background Image",
            "input" => array(
              "name" => "wid_bg_img",
            ),
            "bofInput" => array(
              "file",
              array(
                "type" => "image",
                "object_type" => "page_widget_bg"
              )
            )
          ),
          "html" => array(
            "group" => "b",
            "title" => "HTML",
            "input" => array(
              "name" => "html",
              "type" => "textarea"
            ),
            "validator" => array(
              "html",
              array(
                "empty()"
              )
            )
          ),
        )
      ),
      "text" => array(
        "group" => "other",
        "icon" => "text_format",
        "label" => "EditorJS",
        "groups" => array(
          "a" => "General",
          "b" => "Content"
        ),
        "inputs" => array(
          "wid_title" => array(
            "group" => "a",
            "title" => "Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_title",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "wid_sub_data" => array(
            "group" => "a",
            "title" => "Lower Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_sub_data",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "wid_bg_img" => array(
            "group" => "a",
            "title" => "Background Image",
            "input" => array(
              "name" => "wid_bg_img",
            ),
            "bofInput" => array(
              "file",
              array(
                "type" => "image",
                "object_type" => "page_widget_bg"
              )
            )
          ),
          "editor_js" => array(
            "group" => "b",
            "title" => "Content",
            "input" => array(
              "name" => "editor_js",
              "type" => "text_editor"
            ),
            "validator" => array(
              "editor_js",
              array(
                "empty()"
              )
            )
          ),
        )
      ),
      "ads" => array(
        "group" => "other",
        "icon" => "directions",
        "label" => "Ads",
        "groups" => array(
          "a" => "General",
          "b" => "Ads"
        ),
        "inputs" => array(
          "wid_title" => array(
            "group" => "a",
            "title" => "Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_title",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "wid_sub_data" => array(
            "group" => "a",
            "title" => "Lower Title",
            "input" => array(
              "type" => "text",
              "name" => "wid_sub_data",
            ),
            "validator" => array(
              "string",
              array(
                "strip_emoji" => false,
                "empty()",
              )
            )
          ),
          "wid_bg_img" => array(
            "group" => "a",
            "title" => "Background Image",
            "input" => array(
              "name" => "wid_bg_img",
            ),
            "bofInput" => array(
              "file",
              array(
                "type" => "image",
                "object_type" => "page_widget_bg"
              )
            )
          ),
          "place_id" => array(
            "group" => "b",
            "title" => "ID",
            "input" => array(
              "name" => "place_id",
              "type" => "text",
            ),
            "validator" => array(
              "string",
              []
            )
          ),
          "banner_size" => array(
            "group" => "b",
            "title" => "Banner size",
            "input" => array(
              "name" => "banner_size",
              "type" => "select_i",
              "options" => [
                ["970x250", "970x250"],
                ["970x90", "970x90"],
                ["728x90", "728x90"],
                ["468x60", "468x60"],
                ["336x280", "336x280"],
                ["300x600", "300x600"],
                ["300x250", "300x250"],
                ["250x250", "250x250"],
                ["234x60", "234x60"],
                ["200x200", "200x200"],
                ["160x600", "160x600"],
                ["120x600", "120x600"],
                ["120x240", "120x240"],
              ]
            ),
            "validator" => array(
              "in_array",
              array(
                "values" => ["970x250", "970x90", "728x90", "468x60", "336x280", "300x600", "300x250", "250x250", "234x60", "200x200", "160x600", "120x600", "120x240"]
              )
            )
          ),
        )
      ),
    ),
  );
  public $pre_designs = array();

  public function _get_widgets($args = [])
  {

    bof()->object->page->_add_widget("grid", array(
      "group" => "design",
      "icon" => "grid_view",
      "label" => "Grid Widget",
      "groups" => array(
        "a" => "Columns",
        "c" => "Design",
      ),
      "inputs" => array(
        "fitMain" => array(
          "group" => "c",
          "title" => "No padding",
          "tip" => "In case you want the grid widget to ignore paddings and have same width as the parent, check this input. Can be useful in case you want inner design widgets or background to fit the whole view area",
          "input" => array(
            "name" => "fitMain",
            "type" => "checkbox"
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true
            )
          )
        ),
        "columns" => array(
          "group" => "a",
          "title" => "Columns",
          "input" => array(
            "name" => "columns",
            "type" => "select_i_c",
            "options" => array(
              ["6_6", "<span class='_cs'><span class='_c c6'></span><span class='_c c6'></span></span><span class='label'>Two columns</span>"],
              ["4_8", "<span class='_cs'><span class='_c c4'></span><span class='_c c8'></span></span><span class='label'>Two columns</span>"],
              ["8_4", "<span class='_cs'><span class='_c c8'></span><span class='_c c4'></span></span><span class='label'>Two columns</span>"],
              ["4_4_4", "<span class='_cs'><span class='_c c4'></span><span class='_c c4'></span><span class='_c c4'></span></span><span class='label'>Three columns</span>"],
              ["6_3_3", "<span class='_cs'><span class='_c c6'></span><span class='_c c3'></span><span class='_c c3'></span></span><span class='label'>Three columns</span>"],
              ["3_6_3", "<span class='_cs'><span class='_c c3'></span><span class='_c c6'></span><span class='_c c3'></span></span><span class='label'>Three columns</span>"],
              ["3_3_6", "<span class='_cs'><span class='_c c3'></span><span class='_c c3'></span><span class='_c c6'></span></span><span class='label'>Three columns</span>"],
            )
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => ["6_6", "4_8", "8_4", "4_4_4", "6_3_3", "3_6_3", "3_3_6"]
            )
          )
        ),
        "wid_bg_img" => array(
          "group" => "c",
          "title" => "Background Image",
          "input" => array(
            "name" => "wid_bg_img",
          ),
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "page_widget_bg"
            )
          )
        ),
        "background_color" => array(
          "label" => "Background color",
          "tip" => "Can be HEX, RGB, HSL or even linear gradient css",
          "name" => "background_color",
          "group" => "c",
          "input" => array(
            "type" => "text",
            "name" => "background_color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
      )
    ));

    bof()->object->page->_add_widget("ft_list", array(
      "group" => "design",
      "icon" => "format_list_bulleted",
      "label" => "Feature<br>list",
      "groups" => array(
        "a" => "General",
        "c" => "Design",
        "b" => "List"
      ),
      "inputs" => array(
        "wid_title" => array(
          "group" => "a",
          "title" => "Title",
          "input" => array(
            "type" => "text",
            "name" => "wid_title",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "wid_sub_data" => array(
          "group" => "a",
          "title" => "Lower Title",
          "input" => array(
            "type" => "text",
            "name" => "wid_sub_data",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "wid_bg_img" => array(
          "group" => "c",
          "title" => "Background Image",
          "input" => array(
            "name" => "wid_bg_img",
          ),
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "page_widget_bg"
            )
          )
        ),
        "color" => array(
          "label" => "Font color",
          "name" => "color",
          "group" => "c",
          "input" => array(
            "type" => "text",
            "name" => "color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "background_color" => array(
          "label" => "Background color",
          "tip" => "Can be HEX, RGB, HSL or even linear gradient css",
          "name" => "background_color",
          "group" => "c",
          "input" => array(
            "type" => "text",
            "name" => "background_color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "features" => array(
          "name" => "ft_handle",
          "group" => "b",
          "content" => "<div id='ft_list'></div><div class='btn btn-primary'>+ Add</div>",
          "input" => array(
            "type" => "hidden",
            "name" => "features",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
      )
    ));

    bof()->object->page->_add_widget("steps_list", array(
      "group" => "design",
      "icon" => "map",
      "label" => "Step<br>list",
      "groups" => array(
        "a" => "General",
        "c" => "Design",
        "b" => "List"
      ),
      "inputs" => array(
        "wid_title" => array(
          "group" => "a",
          "title" => "Title",
          "input" => array(
            "type" => "text",
            "name" => "wid_title",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "wid_sub_data" => array(
          "group" => "a",
          "title" => "Lower Title",
          "input" => array(
            "type" => "text",
            "name" => "wid_sub_data",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "wid_bg_img" => array(
          "group" => "c",
          "title" => "Background Image",
          "input" => array(
            "name" => "wid_bg_img",
          ),
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "page_widget_bg"
            )
          )
        ),
        "color" => array(
          "label" => "Font color",
          "name" => "color",
          "group" => "c",
          "input" => array(
            "type" => "text",
            "name" => "color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "background_color" => array(
          "label" => "Background color",
          "tip" => "Can be HEX, RGB, HSL or even linear gradient css",
          "name" => "background_color",
          "group" => "c",
          "input" => array(
            "type" => "text",
            "name" => "background_color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "features" => array(
          "name" => "ft_handle",
          "group" => "b",
          "content" => "<div id='ft_list'></div><div class='btn btn-primary'>+ Add</div>",
          "input" => array(
            "type" => "hidden",
            "name" => "features",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
      )
    ));

    $cta_widget = array(
      "group" => "design",
      "icon" => "ads_click",
      "label" => "Call to<br>Action",
      "groups" => array(
        "a" => "General",
        "c" => "Design",
        "d" => "Background",
        "b" => "Links"
      ),
      "inputs" => array(
        "wid_title" => array(
          "group" => "a",
          "title" => "Title",
          "input" => array(
            "type" => "text",
            "name" => "wid_title",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "wid_sub_data" => array(
          "group" => "a",
          "title" => "Lower Title",
          "input" => array(
            "type" => "text",
            "name" => "wid_sub_data",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "wid_bg_img" => array(
          "group" => "d",
          "title" => "Background Image",
          "input" => array(
            "name" => "wid_bg_img",
          ),
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "page_widget_bg"
            )
          )
        ),
        "background_img_url" => array(
          "label" => "Background image - URL",
          "name" => "background_img_url",
          "group" => "d",
          "input" => array(
            "type" => "text",
            "name" => "background_img_url",
          ),
          "validator" => array(
            "url",
            array(
              "empty()",
            )
          )
        ),
        "background_img_dim" => array(
          "label" => "Dim background image",
          "tip" => "If chosen, the background image will be dimmed to fit the design better",
          "name" => "background_img_dim",
          "group" => "d",
          "input" => array(
            "name" => "background_img_dim",
            "type" => "select_i_c",
            "value" => "none",
            "options" => array(
              ["none", "<span class='_h none'></span>"],
              ["radial_high_high", "<span class='_h radial_high_high'></span>"],
              ["radial_med_high", "<span class='_h radial_med_high'></span>"],
              ["radial_lil_high", "<span class='_h radial_lil_high'></span>"],
              ["radial_high_med", "<span class='_h radial_high_med'></span>"],
              ["radial_med_med", "<span class='_h radial_med_med'></span>"],
              ["radial_lil_med", "<span class='_h radial_lil_med'></span>"],
              ["radial_high_lil", "<span class='_h radial_high_lil'></span>"],
              ["radial_med_lil", "<span class='_h radial_med_lil'></span>"],
              ["radial_lil_lil", "<span class='_h radial_lil_lil'></span>"],
              ["linear_lil_med", "<span class='_h linear_lil_med'></span>"],
              ["linear_lil_high", "<span class='_h linear_lil_high'></span>"],
              ["linear_med_high", "<span class='_h linear_med_high'></span>"],
              ["linear_high_med", "<span class='_h linear_high_med'></span>"],
              ["linear_high_lil", "<span class='_h linear_high_lil'></span>"],
              ["linear_high_lil_high", "<span class='_h linear_high_lil_high'></span>"],
              ["linear_lil_high_lil", "<span class='_h linear_lil_high_lil'></span>"],
              ["linear_r_lil_med", "<span class='_h linear_r_lil_med'></span>"],
              ["linear_r_lil_high", "<span class='_h linear_r_lil_high'></span>"],
              ["linear_r_med_high", "<span class='_h linear_r_med_high'></span>"],
              ["linear_r_high_med", "<span class='_h linear_r_high_med'></span>"],
              ["linear_r_high_lil", "<span class='_h linear_r_high_lil'></span>"],
              ["linear_r_high_lil_high", "<span class='_h linear_r_high_lil_high'></span>"],
              ["linear_r_lil_high_lil", "<span class='_h linear_r_lil_high_lil'></span>"],
            )
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => ["none", "radial_high_high", "radial_med_high", "radial_lil_high", "radial_high_med", "radial_med_med", "radial_lil_med", "radial_high_lil", "radial_med_lil", "radial_lil_lil", "linear_lil_med", "linear_lil_high", "linear_med_high", "linear_high_med", "linear_high_lil", "linear_high_lil_high", "linear_lil_high_lil", "linear_r_lil_med", "linear_r_lil_high", "linear_r_med_high", "linear_r_high_med", "linear_r_high_lil", "linear_r_high_lil_high", "linear_r_lil_high_lil"],
              "empty()"
            )
          )
        ),
        "background_color" => array(
          "label" => "Background color",
          "tip" => "Can be HEX, RGB, HSL or even linear gradient css",
          "name" => "background_color",
          "group" => "d",
          "input" => array(
            "type" => "text",
            "name" => "background_color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "font_color" => array(
          "label" => "Font color",
          "name" => "font_color",
          "group" => "c",
          "input" => array(
            "type" => "text",
            "name" => "font_color",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "font_size" => array(
          "label" => "Font size",
          "name" => "font_size",
          "group" => "c",
          "input" => array(
            "type" => "select_i",
            "name" => "font_size",
            "options" => array(
              ["medium", "Medium"],
              ["large", "Large"],
              ["vlarge", "Very large"],
            ),
            "value" => "medium"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => ["medium", "large", "vlarge"]
            )
          )
        ),
        "img" => array(
          "group" => "c",
          "title" => "Image",
          "input" => array(
            "name" => "img",
          ),
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "page_img"
            )
          )
        ),
        "img_place" => array(
          "label" => "Image place",
          "name" => "img_place",
          "group" => "c",
          "input" => array(
            "type" => "select_i",
            "name" => "img_place",
            "options" => array(
              ["left", "Left"],
              ["right", "Right"],
            ),
            "value" => "left"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => ["right", "left"]
            )
          )
        ),
        "height" => array(
          "label" => "Height",
          "name" => "height",
          "group" => "c",
          "input" => array(
            "type" => "select_i",
            "name" => "height",
            "options" => array(
              ["auto", "Auto"],
              ["full", "Full height"]
            ),
            "value" => "auto"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => ["auto", "full"]
            )
          )
        ),
        "btn_title_1" => array(
          "group" => "b",
          "title" => "Primary button title",
          "input" => array(
            "type" => "text",
            "name" => "btn_title_1",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "btn_link_1" => array(
          "group" => "b",
          "title" => "Primary button link",
          "input" => array(
            "type" => "text",
            "name" => "btn_link_1",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
        "btn_title_2" => array(
          "group" => "b",
          "title" => "Secondary button title",
          "input" => array(
            "type" => "text",
            "name" => "btn_title_2",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
              "empty()",
            )
          )
        ),
        "btn_link_2" => array(
          "group" => "b",
          "title" => "Secondary button link",
          "input" => array(
            "type" => "text",
            "name" => "btn_link_2",
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
            )
          )
        ),
      )
    );

    $langs = bof()->object->language->get_all();
    if ($langs) {

      $_inputs_old = $cta_widget["inputs"];
      $_inputs_new = [];

      foreach ($langs as $lang) {
        foreach ($_inputs_old as $in_name => $in_input) {
          $_inputs_new[$in_name] = $in_input;
          if (substr($in_name, 0, strlen("btn_title_")) == "btn_title_") {
            $in_input["input"]["name"] .= "_{$lang["code2"]}";
            $in_input["title"] .= " - {$lang["name"]} translation";
            $_inputs_new["{$in_name}_{$lang["code2"]}"] = $in_input;
          }
        }
      }

      $cta_widget["inputs"] = $_inputs_new;
    }

    bof()->object->page->_add_widget("cta", $cta_widget);

    $raw = false;
    $cache = false;
    extract($args);

    foreach (bof()->bofAdmin->_get_objects() as $object_name => $object_args) {

      $object = bof()->object->__get($object_name);
      if (!empty($object->bof()["widgetable"])) {
        bof()->object->page->_add_widget($object_name, array(
          "group" => "content",
          "icon" => $object->bof()["icon"],
          "label" => str_replace(" ", "<br>", $object->bof()["label"]),
          "object" => $object->bof()["name"],
        ));
      }
    }

    $widgets_raw = $this->widgets;
    foreach ($widgets_raw["items"] as $_wk => &$_wd) {
      if (!empty($_wd["object"])) {

        $object_parsed = bof()->bofAdmin->object_list_parse_caller(
          bof()->object->__get($_wd["object"]),
          false
        );
        $object_filters = $object_parsed["filters"];
        $object_filters["ID_in"] = array(
          "title" => "Selected",
          "bofInput" => array(
            "object",
            array(
              "type" => $_wd["object"],
              "multi" => true
            )
          ),
        );
        $object_filters["order_by"] = array(
          "title" => "Sort by",
          "input" => array(
            "name" => "order_by",
            "type" => "select",
            "options" => $object_parsed["sorters_bofFormat"]
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => array_keys($object_parsed["sorters"])
            )
          )
        );

        if (!empty($object_filters)) {
          foreach ($object_filters as $object_filter_name => &$object_filter) {
            $object_filter["group"] = "e";
            $object_filter["input"]["name"] = $object_filter_name;
            if ($raw == false && !empty($object_filter["bofInput"]))
              $object_filter = bof()->bofInput->parse($object_filter)["data"];
          }
          $_wd["inputs"] = $object_filters;
          $_wd["groups"]["e"] = "Filters";
        }

        $_wd["inputs"]["wid_table_column"] = array(
          "group" => "b",
          "title" => "Table - Extra Column",
          "display_on" => array(
            "wid_type" => ["equal", "table"]
          ),
          "input" => array(
            "name" => "wid_table_column",
            "type" => "select_m",
            "options" => $object_parsed["sorters_bofFormat"]
          ),
          "validator" => array(
            "array_in_array",
            array(
              "empty()",
              "select_m()",
              "values" => array_keys($object_parsed["sorters"])
            )
          )
        );
        $_wd["inputs"]["wid_table_title"] = array(
          "group" => "b",
          "title" => "Table - Extra Columns Title",
          "tip" => "Select a `title` for every selected `Extra Column`. Enter semicolon <b>( ; )</b> seperated. <a href=''>Documentation</a>",
          "display_on" => array(
            "wid_type" => ["equal", "table"]
          ),
          "input" => array(
            "name" => "wid_table_title",
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => true,
              "empty()"
            )
          )
        );
      }
    }

    $indexed_non_default_langs = bof()->object->language->get_all();

    foreach ($widgets_raw["groups"] as $_gk => &$_gd) {
      if (empty($_gd["inputs"])) continue;
      $_gd_new_inputs = [];
      foreach ($_gd["inputs"] as $_gd_input_name => $_gd_input) {

        if ($raw == false && !empty($_gd_input["bofInput"]))
          $_gd_input = bof()->bofInput->parse($_gd_input)["data"];

        $_gd_new_inputs[$_gd_input_name] = $_gd_input;

        foreach (["wid_title", "wid_sub_data"] as $__col) {
          if (!empty($_gd_input["input"]["name"]) ? $_gd_input["input"]["name"] == $__col : false) {
            if ($indexed_non_default_langs) {
              foreach ($indexed_non_default_langs as $indexed_non_default_lang) {
                $_gd_input_t = $_gd_input;
                $_gd_input_t["title"] .= " - {$indexed_non_default_lang["name"]} translation";
                $_gd_input_t["input"]["name"] .= "_{$indexed_non_default_lang["code2"]}";
                $_gd_input_t["validator"][1][] = "empty()";
                $_gd_new_inputs[$_gd_input_name . "_{$indexed_non_default_lang["code2"]}"] = $_gd_input_t;
              }
            }
          }
        }
      }
      $_gd["inputs"] = $_gd_new_inputs;
    }

    foreach ($widgets_raw["items"] as $itemName => &$itemArgs) {
      if (empty($itemArgs["inputs"])) continue;
      $_new_inputs = [];
      foreach ($itemArgs["inputs"] as $itemName => $itemInput) {

        if ($raw == false && !empty($itemInput["bofInput"]))
          $itemInput = bof()->bofInput->parse($itemInput)["data"];

        $_new_inputs[$itemName] = $itemInput;

        foreach (["wid_title", "wid_sub_data"] as $__col) {
          if (!empty($itemInput["input"]["name"]) ? $itemInput["input"]["name"] == $__col : false) {
            if ($indexed_non_default_langs) {
              foreach ($indexed_non_default_langs as $indexed_non_default_lang) {
                $_gd_input_t = $itemInput;
                $_gd_input_t["title"] .= " - {$indexed_non_default_lang["name"]} translation";
                $_gd_input_t["input"]["name"] .= "_{$indexed_non_default_lang["code2"]}";
                $_new_inputs[$itemName . "_{$indexed_non_default_lang["code2"]}"] = $_gd_input_t;
              }
            }
          }
        }
      }
      $itemArgs["inputs"] = $_new_inputs;
    }

    return $widgets_raw;
  }
  public function _add_widget($name, $data)
  {
    $this->widgets["items"][$name] = $data;
  }
  public function _add_widget_group($name, $data)
  {
    $this->widgets["groups"][$name] = $data;
  }
  public function _add_pre_design($name, $data)
  {
    $this->pre_designs[$name] = $data;
  }
  public function _get_pre_designs($args = [])
  {
    return $this->pre_designs;
  }

  public function select($whereArgs = [], $selectArgs = [])
  {

    $listing = false;
    $editing = false;
    $deleting = false;
    $verified = false;
    $client_single = false;
    $indexed = false;
    $_eq = [];
    extract($selectArgs);

    if ($editing)
      $_eq["widgets"] = true;

    if ($indexed || $client_single)
      $whereArgs["active"] = true;

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select($this, $whereArgs, $selectArgs);
  }
  public function insert($setArray)
  {

    $setArray["hash"] = !empty($setArray["hash"]) ? $setArray["hash"] : bof()->object->page->get_free_hash();
    return bof()->object->_insert($this, $setArray);
  }
  public function clean($item, $args)
  {

    $editing = false;
    $listing = false;
    $client_single = false;
    $search = false;
    $_eq = [];
    extract($args);

    if ($client_single)
      $_eq["widgets"] = true;

    if (!empty($_eq["widgets"])) {
      $item["widgets"] = bof()->object->page_widget->select(
        array(
          "page_id" => $item["ID"],
        ),
        array(
          "limit" => 100,
          "order_by" => "LPAD(i,10,0)",
          "order" => "ASC",
          "single" => false,
          "client_single" => $client_single,
          "cache" => !bof()->object->core_setting->get("debug")
        )
      );
    }

    if ($editing) {
      $item["_pre_design"] = null;
      if (!empty($item["pre_design"])) {
        $pre_designs = $this->_bof_this->_get_pre_designs();
        if (!empty($pre_designs[$item["pre_design"]])) {
          $item["_pre_design"] = $pre_designs[$item["pre_design"]];
        }
      }
    }

    if ($search) {
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"],
        "image" => false
      );
    }

    return $item;
  }
  public function clean_as_widget($item, $args)
  {

    return array(
      "title" => $item["name"],
      "cover" => null,
      "raw" => $item
    );
  }
  public function clean_client_single($item, $args)
  {

    if ($item["private"] && php_sapi_name() !== 'cli') {
      if (!bof()->user->get()->logged) {
        bof()->api->set_error("403", ["output_args" => ["turn" => false]]);
        throw new bofException("403");
        return false;
      } elseif (!empty($item["private_rules_decoded"]["user_roles"])) {
        if (empty(array_intersect(explode(",", $item["private_rules_decoded"]["user_roles"]), bof()->user->check()->extra["role_ids"])))
          return false;
      }
    }

    $widgets = [];
    if (!empty($item["widgets"])) {
      $widgets = $item["widgets"];
      unset($item["widgets"]);
    }

    if ($item["seo_url"] == "search") {

      $query = bof()->nest->user_input("get", "query", "string");
      $type = bof()->nest->user_input("get", "type", "bofClient_object");
      $page = bof()->nest->user_input( "get", "page", "int", [ "min" => 2, "max" => 10 ], 1 );

      if ($query && $type) {

        $perform_search = bof()->search->__perform_search(
          bof()->search->__parse_query($query),
          array(
            "object_type" => $type,
            "limit" => 30,
            "page" => $page
          )
        );

        if ( $perform_search ){

          $object_results = bof()->object->__get($type)->select(
            array(
              "ID_in" => array_map(function ($item) {
                return $item["object_id"];
              }, $perform_search)
            ),
            array(
              "single" => false,
              "limit" => false,
              "as_widget" => true,
            )
          );
  
          if ($object_results) {
            $widgets = [];
            $object_results = bof()->search->sort($object_results, $perform_search);
            $widgets[1] = array(
              "ID" => uniqid(),
              "i" => "1",
              "display"  => array(
                "type" => "slider",
                "title" => $query,
                "sub_data" => null,
                "link" => count($object_results)>=30 && $page < 10 ? ( "search?query={$query}&type={$type}&page=" . ($page+1) ) : false,
                "pagination" => null,
                "bg_img" => null,
                "slider_size" => "medium",
                "slider_mason" => true,
                "slider_rows" => 1,
                "list_columns" => null,
                "table_columns" => null,
                "table_labels" => null,
                "link_on_bottom" => true
              ),
              "items" => $object_results,
            );
          }

        }

      }
    }

    $widgets_org = $widgets;

    foreach ($widgets as $i => $widget) {

      if (!bof()->general->numeric($widget["i"]))
        unset($widgets[$i]);

      if (
        (!empty($widget["display"]["type"]) ? $widget["display"]["type"] == "grid" : false) ||
        (!empty($widget["args"]["gridType"]))
      ) {
        usort($widgets_org, function ($a, $b) {
          return $a['i'] <=> $b['i'];
        });
        foreach ($widgets_org as $_i => $_widget) {
          if (substr($_widget["i"], 0, strlen($widget["ID"])) == $widget["ID"]) {
            $widgets[$i]["display"]["widgets"][] = $_widget;
          }
        }
      }
    }

    $output = array(
      "widgets" => $widgets,
      "data" => $item,
      "page" => array(
        "classes" => !empty($item["class"]) ? explode(" ", $item["class"]) : []
      )
    );

    if ($item["pre_design"]) {
      $pre_designs = $this->_bof_this->_get_pre_designs();
      if (!empty($pre_designs[$item["pre_design"]]["exec"])) {
        $pre_designs[$item["pre_design"]]["exec"]($output);
      }
    }

    return $output;
  }
  public function clean_client_single_widget($item, $widget_id, $args = [], $caller = "self")
  {

    return bof()->object->page_widget->select(
      array(
        "page_id" => $item["ID"],
        "unique_id" => $widget_id
      ),
      array(
        "client_single" => true
      )
    );
  }

  public function export($id)
  {

    $get_page = bof()->object->page->sid($id, ["clean" => false]);
    $get_page_widgets = bof()->object->page_widget->select(
      array(
        "page_id" => $id
      ),
      array(
        "limit" => false,
        "clean" => false
      )
    );

    $page_simplified = array(
      "name" => $get_page["name"],
      "comment" => $get_page["comment"],
      "class" => $get_page["class"],
      "s_widgets" => $get_page["s_widgets"],
      "url" => $get_page["seo_url"]
    );

    if ($get_page_widgets) {
      $page_simplified["widgets"] = array_map(function ($item) {

        $args = json_decode($item["args"], 1);

        array_walk($args, function (&$val, $var) {
          if (in_array($var, ["wid_bg_img", "img"], true) && $val) {
            $getFile = bof()->object->file->sid($val);
            $val = $getFile ? $getFile["image_original"] : null;
          }
        });

        return array(
          "i" => $item["i"],
          "name" => $item["name"],
          "args" => json_encode($args)
        );
      }, $get_page_widgets);
    }

    $page_simplified_encoded = json_encode($page_simplified);
    return $page_simplified_encoded;
  }
  public function import($json, $ignoreWidgetErrors = true)
  {

    foreach (["class", "comment", "name", "url", "s_widgets"] as $_m_k) {
      if (!in_array($_m_k, array_keys($json), true))
        throw new bofException("invalidData");
    }

    if (empty($json["widgets"]) ? true : !is_array($json["widgets"]))
      throw new bofException("invalidData: noWidgetsFound");

    $wC = $wF = 0;

    foreach ($json["widgets"] as $widget) {

      foreach (["i", "name", "args"] as $_m_k) {
        if (!in_array($_m_k, array_keys($widget), true))
          throw new bofException("invalidData: widgetKeys");
      }

      $widget_args_validate = bof()->nest->validate($widget["args"], "json");
      if (!$widget_args_validate) throw new bofException("invalidData: widgetArgs");

      try {
        $validate_widget_args = bof()->object->page_widget->verify_args($widget["name"], $widget["args"]);
        if ($validate_widget_args) {
          $wC++;
          $validate_widget_args["wid_id"] = $widget["args"]["wid_id"];
          $widget["args"] = $validate_widget_args;
          $widget["args"]["wid_name"] = $widget["name"];
          $validated_widgets[] = $widget;
        }
      } catch (bofException $err) {
        $wF++;
        if (!$ignoreWidgetErrors)
          throw new bofException("invalidData: WidgetArgs: {$widget["name"]}: " . $err->getMessage() . ": " . $err->getExtra()["reason"]);
      }
    }

    if (empty($validated_widgets))
      throw new bofException("invalidData: NoValidWidget");

    $checkURL = $this->_bof_this->select(array(
      "seo_url" => $json["url"]
    ));

    if ($checkURL)
      throw new bofException("A page with URL: {$json["url"]} already exists");

    $pageID = bof()->object->page->insert(array(
      "name" => $json["name"],
      "class" => $json["class"],
      "comment" => $json["comment"],
      "seo_url" => $json["url"]
    ));

    $__i = 0;
    foreach ($validated_widgets as $validated_widget) {
      $__i++;

      $validated_widget_images = [];
      foreach (["wid_bg_img" => "page_widget_bg", "img" => "page_img"] as $bofInputFileKey => $bofInputFileType) {
        if (!empty($validated_widget["args"][$bofInputFileKey])) {
          $validated_widget_images[$bofInputFileKey] = array(
            "address" => $validated_widget["args"][$bofInputFileKey],
            "type" => $bofInputFileType
          );
          $validated_widget["args"][$bofInputFileKey] = null;
        }
      }

      $validated_widget_i = $validated_widget["i"];
      if ( !ctype_digit( $validated_widget_i ) ? count( explode( "_", $validated_widget_i ) ) == 2 : false ){
        list( $vwpid, $vwpo ) = explode( "_", $validated_widget_i );
        $validated_widget_i = substr( md5( $vwpid ), 0, 10 ) . "_" . $vwpo;
      }

      $widgetID = bof()->object->page_widget->insert(array(
        "page_id" => $pageID,
        "unique_id" => substr( md5( $validated_widget["args"]["wid_id"] ), 0, 10 ),
        "i" => $validated_widget_i,
        "name" => $validated_widget["name"],
        "args" => json_encode($validated_widget["args"]),
        "active" => 1
      ));

      if (!empty($validated_widget_images)) {

        foreach ($validated_widget_images as $bofInputFileKey => $bofInputFileTypeAndData) {

          $_dl = bof()->object->file->handle_url($bofInputFileTypeAndData["address"], array(
            "type" => "image",
            "object_type" => $bofInputFileTypeAndData["type"],
          ));

          if ($_dl ? $_dl[0] : false) {

            $_validate_file = bof()->object->file->finalize_upload(
              "image",
              $bofInputFileTypeAndData["type"],
              "page_widget" . $widgetID,
              $_dl[1]["file_id"],
              false
            );

            if ($_validate_file) {
              $validated_widget["args"][$bofInputFileKey] = $_dl[1]["file_id"];
            }
          }
        }

        bof()->object->page_widget->update(
          array(
            "ID" => $widgetID
          ),
          array(
            "args" => json_encode($validated_widget["args"])
          )
        );
      }
    }
  }

  public function seo($item)
  {
    $item = "me";
    return $item;
  }
  public function admin_list($_result, $request)
  {
    $_result["_pre_designs"] = $this->_bof_this->_get_pre_designs();
    return $_result;
  }
}
