<?php

if (!defined("bof_root")) die;

class object_m_cronjob extends bof_type_object
{

  // BusyOwlFramework handshake
  public function bof()
  {
    return array(
      "name" => "m_cronjob",
      "label" => "Music Cronjob",
      "icon" => "precision_manufacturing",
      "db_table_name" => "_c_m_cronjobs",
      "db_empty_select" => true,
    );
  }
  public function columns()
  {
    return array(
      "name" => array(
        "label" => "Name",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ($displayItem, $item, $displayData) {
              $displayData["sub_data"] = $item["comment"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true,
          )
        ),
      ),
      "comment" => array(
        "label" => "Comment",
        "tip" => "A few words about this cronjob. Visible to admins only",
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true,
            "strip_emoji" => false
          )
        ),
        "input" => array(
          "type" => "textarea"
        ),
        "bofAdmin" => array(
          "object" => []
        ),
      ),
      "execution_interval" => array(
        "label" => "Execution interval",
        "tip" => "<b>Minutes</b> between execution of this job. For example if you enter 1, this job will be executed once per minute. If you enter 60, this job will be executed once per 60 minutes",
        "validator" => "float",
        "input" => array(
          "type" => "digit",
          "value" => 10
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true,
            "group" => "config"
          ),
        ),
      ),
      "update_interval" => array(
        "label" => "Update interval",
        "tip" => "How often should script re-check each given ID? Enter in <b>Days</b>. For example, if you enter 1, script will check given IDs once, every day. If you enter 30, every given ID will be checked once per month",
        "validator" => "float",
        "input" => array(
          "type" => "digit",
          "value" => 14
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true,
            "group" => "config"
          ),
        ),
      ),
      "item_limit" => array(
        "label" => "Item limit",
        "tip" => "How many items should be selected from query in each run? Higher number equals higher execution time & resource usage",
        "validator" => "int",
        "input" => array(
          "type" => "digit",
          "value" => 5
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true,
            "group" => "config"
          ),
        ),
      ),
      "dynamic" => array(
        "label" => "Dynamic IDS",
        "tip" => "You can either enter API IDs yourself ( static ) or you can setup the cronjob to get source IDS from database ( dynamic ). <a href='https://support.busyowl.co/documentation/automation' target='_blank'>Click here for documentation</a> ",
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
          "object" => array(
            "display_on" => array(
              "object_type" => ["in_array", ["artist", "track", "album"]],
            ),
            "group" => "ids"
          )
        ),
      ),
      "object_type" => array(
        "label" => "Object type",
        "validator" => "string_abcd",
        "input" => array(
          "type" => "select",
          "options" => array(
            ["artist", "Artist"],
            ["album", "Album"],
            ["track", "Track"],
            ["playlist", "Playlist"],
            ["user_lists", "User Playlists"],
            ["cat_lists", "Category Playlists"]
          )
        ),
        "bofAdmin" => array(
          "filters" => array(

            "object_type" => array(
              "name" => "object_type",
              "title" => "Object type",
              "input" => array(
                "name" => "object_type",
                "type" => "select",
                "options" => array(
                  ["artist", "Artist"],
                  ["track", "Track"],
                  ["album", "Album"],
                  ["playlist", "Playlist"],
                  ["user_lists", "User Playlists"],
                  ["cat_lists", "Category Playlists"],
                  "__all__" => ["__all__", "All"]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "string_abcd",
                array()
              )
            ),

          ),
          "list" => array(
            "type" => "tag"
          ),
          "object" => array(
            "required" => true,
            "group" => "config"
          )
        ),
      ),
      "object_filters" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true,
          ),
        ),
      ),
      "api_name" => array(
        "label" => "API Name",
        "validator" => "string_abcd",
        "input" => array(
          "type" => "select",
          "options" => array(
            ["spotify", "Spotify"],
            ["youtube", "YouTube"],
            // [ "soundcloud", "Soundcloud" ]
          )
        ),
        "bofAdmin" => array(
          "filters" => array(
            "api_name" => array(
              "name" => "api_name",
              "title" => "Source name",
              "input" => array(
                "type" => "select",
                "options" => array(
                  ["spotify", "Spotify"],
                  ["youtube", "Youtube"],
                  // [ "youtube_dl", "Youtube DL" ],
                  // [ "torrent", "Torrent" ],
                  // [ "soundcloud", "SoundCloud" ],
                  // [ "vimeo", "Vimeo" ],
                  // [ "genius", "Genius" ],
                  "__all__" => ["__all__", "All"]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => ["__all__", "spotify", "youtube"]
                )
              )
            ),
          ),
          "list" => array(
            "type" => "tag"
          ),
          "object" => array(
            "required" => true,
            "group" => "config"
          )
        ),
      ),
      "api_ids" => array(
        "label" => "Static IDs",
        "tip" => "Enter the API IDs manually<br><div class='btn btn-primary' id='spotify_browse_button'>Browse Spotify</div><br><br>Artist example:<br>6eUKZXaKkcviH0Ku9w2n3V<br><br>Album example:<br>5y1leoacRWkUaxsoB8Txyq<br><br>Track example:<br>11dFghVXANMlKmJXsNCbNl<br><br>Playlist example:<br>37i9dQZF1DWZeKCadgRdKQ<br><br>User example:<br>spotify<br>another_username<br><br>Category example:<br>dinner<br>rock",
        "input" => array(
          "type" => "textarea",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
            "strict" => true,
            "strict_regex" => "/^[0-9a-zA-Z]{5,1000}$/mu",
            "strict_regex_raw" => true,
            "allow_eol" => true
          ),
        ),
        "bofAdmin" => array(
          "object" => array(
            "display_on" => array(
              "dynamic" => ["equal", false]
            ),
            "group" => "ids"
          )
        ),
      ),
      "cache" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true,
          ),
        ),
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true,
          ),
        ),
      ),
      "time_update" => array(
        "label" => "Update Time",
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          /*"list" => array(
            "type" => "time"
          )*/
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
          "type" => "checkbox",
        ),
        "selectors" => array(
          "active" => ["active", "="],
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "filters" => array(
            "active" => array(
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
          "list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => ["activate", "deactivate"]
            )
          ),
          "object" => array(
            "group" => "config"
          )
        ),
      ),
    );
  }
  public function bof_columns()
  {
    return array(
      "ID",
      "time_add"
    );
  }
  public function selectors()
  {
    return array(
      "object_type" => ["object_type", "="],
      "api_name"    => ["api_name", "="],
      "query"       => ["name", "LIKE%lower"],
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
        "edit_page_url" => "music_cronjob",
        "list_page_url" => "music_cronjobs",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit" => false,
        ),
      ),
      "list" => array(
        "name" => null,
        "api_name" => null,
        "object_type" => null,
        "sta" => array(
          "label" => "Stats",
          "type" => "simple",
          "class" => "details",
          "renderer" => function ($displayItem, $item, $displayData) {

            $averageTimePerItem = array(
              "artist" => 1,
              "playlist" => 25,
              "album" => 10,
              "track" => 1,
              "user_lists" => 25 * 10,
              "cat_lists" => 25 * 250
            )[$item["object_type"]];

            if ($item["object_type"] == "artist" ? !empty($item["data_decoded"]["artist_albums"]) : false) $averageTimePerItem += 59;
            if ($item["object_type"] == "artist" ? !empty($item["data_decoded"]["artist_related"]) : false) $averageTimePerItem += 5;
            if ($item["object_type"] == "artist" ? !empty($item["data_decoded"]["artist_tracks"]) : false) $averageTimePerItem += 10;

            $items_per_day = $item["item_limit"] * (1440 / $item["execution_interval"]);
            $items_per_day_required_seconds = $items_per_day * $averageTimePerItem;
            $limit_ratio = $items_per_day_required_seconds > 24 * 60 * 60 ? 24 * 60 * 60 / $items_per_day_required_seconds : 1;
            $items_per_day_max = floor($items_per_day * $limit_ratio);
            $items_per_day_include_cache = $items_per_day_max * $item["update_interval"];

            $displayData["data"] = "<ul>";
            $displayData["data"] .= "<li><b>Execution interval</b> {$item["execution_interval"]} minute(s)</li>";
            $displayData["data"] .= "<li><b>Update interval</b> {$item["update_interval"]} day(s)</li>";
            $displayData["data"] .= "<li><b>Max up-to-date items</b> ~" . number_format($items_per_day_include_cache) . "</li>";
            $displayData["data"] .= "<li><b>Query type</b> " . ($item["dynamic"] ? "Dynamic" : "Static") . "</li>";
            $displayData["data"] .= "<li><b>Query item count</b> " . number_format($item["count"]["all"]) . "</li>";
            if ( $item["count"]["all"] < 1000000 ){
              $displayData["data"] .= "<li><b style='color:rgba(var(--c_green),1);font-weight:600'>Checked item count</b> " . number_format($item["count"]["checked"]) . "</li>";
              $displayData["data"] .= "<li><b>Queued for check count</b> " . number_format($item["count"]["queued"]) . "</li>";
            }
            $displayData["data"] .= "";
            $displayData["data"] .= "</ul>";
            return $displayData;
          }
        ),
        "active" => null,
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
        else
          unset($buttons["deactivate"]);

        if ($item["active"])
          $buttons["logs"] = array(
            "label" => "Logs",
            "id" => "logs",
            "link" => "cronjobs?code=music_{$item["ID"]}"
          );

        return $buttons;
      },
      "actions" => array(
        "activate" => function ($ids) {
          bof()->object->m_cronjob->update(
            array(
              "ID_in" => $ids
            ),
            array(
              "active" => 1
            )
          );
          return [true, "activated"];
        },
        "deactivate" => function ($ids) {
          bof()->object->m_cronjob->update(
            array(
              "ID_in" => $ids
            ),
            array(
              "active" => 0
            )
          );
          return [true, "deactivated"];
        }
      ),
      "object" => array(
        "name" => null,
        "comment" => null,
        "execution_interval" => null,
        "update_interval" => null,
        "item_limit" => null,
        "api_name" => null,
        "object_type" => null,
        "dynamic" => null,
        "api_ids" => null,
        "spotify_artist_job_data" => array(
          "label" => "Spotify artist config",
          "tip" => "Extra configuration for this job",
          "input" => array(
            "type" => "select_m",
            "options" => array(
              ["artist_related", "Get related aritsts"],
              ["artist_albums", "Get albums"],
              ["artist_tracks", "Get top tracks"],
            )
          ),
          "display_on" => array(
            "api_name" => ["equal", "spotify"],
            "object_type" => ["equal", "artist"]
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "config"
            )
          )
        ),
        "spotify_playlist_job_data" => array(
          "label" => "Create playlists",
          "tip" => "If checked, script will create playlists as well, otherwise data will be pulled but playlists won't be created",
          "input" => array(
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true
            )
          ),
          "display_on" => array(
            "api_name" => ["equal", "spotify"],
            "object_type" => ["in_array", ["playlist", "user_lists", "cat_lists"]]
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "config"
            )
          )
        ),
        "spotify_playlist_job_data2" => array(
          "label" => "Create playlists - Owner user",
          "tip" => "If a user is selected, playlist will be created for the selected user as the owner of the playlist on your website. Otherwise <a href='https://support.busyowl.co/documentation/config-userphp-options' target='_blank'>user_config.php</a> configuration is followed",
          "bofInput" => array(
            "object",
            array(
              "type" => "user"
            ),
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true
            )
          ),
          "display_on" => array(
            "api_name" => ["equal", "spotify"],
            "object_type" => ["in_array", ["playlist", "user_lists", "cat_lists"]],
            "spotify_playlist_job_data" => ["equal", true],

          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "config"
            )
          )
        ),
        "spotify_sync_genres" => array(
          "label" => "Sync genres",
          "tip" => "If checked, script will sync genres with Spotify if available",
          "input" => array(
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true
            )
          ),
          "display_on" => array(
            "api_name" => ["equal", "spotify"],
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "config"
            )
          )
        ),
        "youtube_download_vidz" => array(
          "label" => "Download videos",
          "tip" => "If checked, script will also download videos. <b>Make sure youtube-dl & ffmpeg works before enabling</b>",
          "input" => array(
            "type" => "checkbox",
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true
            )
          ),
          "display_on" => array(
            "api_name" => ["equal", "youtube"],
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "config"
            )
          )
        ),
        "active" => null,
      ),
      "object_groups" => array(
        ["config", "Config"],
        ["ids", "Query"],
      ),
      "object_ui_renderer" => function ($object, $parsed, $args, $request, &$_inputs, &$data) {


        if ($request["type"] == "multi")
          return $object_structure;

        $item = $request["type"] == "single" ? reset($request["content"]) : false;

        if (!empty($item["data_decoded"])) {
          $_artist_job_data = [];
          if (!empty($item["data_decoded"]["artist_related"])) $_artist_job_data[] = "artist_related";
          if (!empty($item["data_decoded"]["artist_albums"])) $_artist_job_data[] = "artist_albums";
          if (!empty($item["data_decoded"]["artist_tracks"])) $_artist_job_data[] = "artist_tracks";
          $_inputs["spotify_artist_job_data"]["input"]["value"] = $_artist_job_data;
          if (!empty($item["data_decoded"]["playlist_create"]))
            $_inputs["spotify_playlist_job_data"]["input"]["value"] = true;
          if (!empty($item["data_decoded"]["playlist_create_u"])){
            $_inputs["spotify_playlist_job_data2"]["input"]["value"] = $item["data_decoded"]["playlist_create_u"];
            $_inputs["spotify_playlist_job_data2"]["input"] = bof()->bofInput->parse( $_inputs["spotify_playlist_job_data2"] )["data"]["input"];
          }
          if (!empty($item["data_decoded"]["download_vidz"]))
            $_inputs["youtube_download_vidz"]["input"]["value"] = true;
          if (!in_array("sync_genres", array_keys($item["data_decoded"])) ? true : !empty($item["data_decoded"]["sync_genres"]))
            $_inputs["spotify_sync_genres"]["input"]["value"] = true;
        }

        // filters
        $filters["artist"] = bof()->bofAdmin->object_list_parse_caller(bof()->object->__get("m_artist"))["filters"];
        $filters["album"] = bof()->bofAdmin->object_list_parse_caller(bof()->object->__get("m_album"))["filters"];
        $filters["track"] = bof()->bofAdmin->object_list_parse_caller(bof()->object->__get("m_track"))["filters"];

        foreach ($filters as $_filter_g => $_filters) {
          foreach ($_filters as $_filter_k => $_filter) {
            $_filter["input"]["name"] = "{$_filter_g}_{$_filter_k}";
            $_filter["tip"] = "You have selected `Dyanmic` which means script will look into database for <b>" . ucfirst($_filter_g) . "s</b>. Here you can filter those items by their <b>{$_filter["title"]}</b>" . (!empty($_filter["tip"]) ? ". " . $_filter["tip"] : "");
            $_filter["label"] = "Dynamic Filter -> " . ucfirst($_filter_g) . " -> {$_filter["title"]}";
            $_filter["display_on"] = array(
              "dynamic" => ["equal", true],
              "object_type" => ["equal", $_filter_g]
            );
            $_filter["group"] = "ids";
            unset($_filter["title"]);
            if (isset($item["object_filters_decoded"][$_filter_k]))
              $_filter["input"]["value"] = $item["object_filters_decoded"][$_filter_k];

            if (!empty($_filter["bofInput"])) {
              $_parse_input = bof()->bofInput->parse($_filter);
              $_filter = $_parse_input["data"];
            }

            $filter_inputs["{$_filter_g}_{$_filter_k}"] = $_filter;
          }
        }

        $_inputs = array_merge($_inputs, $filter_inputs);

        return $data;
      },
      "object_be_renderer" => function ($inputs, $request) {

        if ($request["type"] == "multi")
          return $inputs;

        $object_type = $inputs["data"]["object_type"];

        $data = array();

        foreach ($this->bof_admin()["object"]["spotify_artist_job_data"]["input"]["options"] as $__k => $__t) {
          if (isset($_POST["spotify_artist_job_data_{$__t[0]}"]))
            $data[$__t[0]] = true;
        }

        if ($inputs["data"]["api_name"] == "youtube") {

          if (empty($inputs["data"]["dynamic"]))
            $inputs["report"]["fail"]["api_ids"] = "You can only set `Dyanmic IDs` as query for YouTube";
          if ($inputs["data"]["object_type"] != "track")
            $inputs["report"]["fail"]["object_type"] = "You can only set `Track` as object-type for YouTube";

          $data["download_vidz"] = !empty($inputs["data"]["youtube_download_vidz"]);
        } elseif ($inputs["data"]["api_name"] == "spotify") {
          $data["playlist_create"] = !empty($inputs["data"]["spotify_playlist_job_data"]);
          $data["playlist_create_u"] = $inputs["data"]["spotify_playlist_job_data2"];
          $data["sync_genres"] = !empty($inputs["data"]["spotify_sync_genres"]);
        }

        if (empty($inputs["data"]["dynamic"])) {

          if (empty($inputs["data"]["api_ids"]) && empty($_POST["api_ids"]))
            $inputs["report"]["fail"]["api_ids"] = "Can't be empty";
        } else {

          $inputs["data"]["api_ids"] = $inputs["set"]["api_ids"] = $inputs["update"]["api_ids"] = false;

          $object_filters = bof()->bofAdmin->object_list_parse_caller(bof()->object->__get("m_{$object_type}"))["filters"];

          foreach ($object_filters as $_filter_k => $_filter) {

            $_filter["input"]["name"] = $_filter_input_name = "{$object_type}_{$_filter_k}";

            if (!empty($_filter["bofInput"])) {
              $_filter_value = bof()->bofInput->validate($_filter);
            } else {
              list($_filter_exists, $_filter_value) = bof()->bofInput->__get_value("{$object_type}_{$_filter_k}", $_filter, "post");
            }

            if ($_filter_value || $_filter_value === 0 || $_filter_value === "0" ? $_filter_value != "__all__" : false)
              $dynamic_filters[$_filter_k] = $_filter_value;
          }

          $inputs["data"]["object_filters"] = $inputs["set"]["object_filters"] = $inputs["update"]["object_filters"] = !empty($dynamic_filters) ? json_encode($dynamic_filters) : false;
        }

        $inputs["data"]["data"] = $inputs["set"]["data"] = $inputs["update"]["data"] = $data ? json_encode($data) : "";

        return $inputs;
      },
    );
  }

  public function select($whereArgs = [], $selectArgs = [])
  {

    $active = null;
    extract($whereArgs);

    $editing = false;
    $listing = false;
    extract($selectArgs);

    if ($active == 2)
      unset($whereArgs["active"]);

    if (!empty($whereArgs["active_outdated"])) {

      $selectArgs["limit"] = !empty($selectArgs["limit"]) ? $selectArgs["limit"] : 10;
      $whereArgs["active"] = 1;
      $whereArgs["custom"] = array(
        "oper" => "OR",
        "cond" => array(
          ["time_update", null, null, true],
          ["time_update", "<", "SUBDATE( now(), INTERVAL update_interval HOUR )", true]
        )
      );

      unset($whereArgs["active_outdated"]);
    }

    if ($listing) {
      $selectArgs["count"] = true;
    }

    return bof()->object->_select($this, $whereArgs, $selectArgs);
  }
  public function clean($item, $args)
  {

    $parse = false;
    $count = false;
    extract($args);

    if ($parse || $count) {

      $item["api_ids_parsed"] = [];
      if (!$item["dynamic"]) {

        $item["static_ids"] = $item["static_ids_checked"] = $item["static_ids_queued"] = [];
        if (!empty($item["api_ids"])) {

          foreach (bof()->general->explode_by_line($item["api_ids"]) as $api_id) {
            $api_id_time_check = bof()->object->m_cronjob_spotify->time($item["ID"] . "_" . trim($api_id), $item["update_interval"]);
            $item["static_ids"][] = $api_id;
            if (!$api_id_time_check)
              $item["static_ids_checked"][] = $api_id;
            else
              $item["static_ids_queued"][] = $api_id;
          }

          if (!empty($item["static_ids_queued"]))
            $item["api_ids_parsed"] = array_slice($item["static_ids_queued"], 0, $item["item_limit"]);
        }

        $item["count"] = array(
          "all" => $item["static_ids"] ? count($item["static_ids"]) : 0,
          "checked" => $item["static_ids_checked"] ? count($item["static_ids_checked"]) : 0,
          "queued" => $item["static_ids_queued"] ? count($item["static_ids_queued"]) : 0,
        );
      } else {

        $_filters = $item["object_filters_decoded"];
        $_filters["has_spotify_id"] = true;

        if ($parse) {

          $item["api_ids_parsed"] = bof()->object->__get("m_{$item["object_type"]}")->select(
            array_merge(
              $_filters,
              array(
                [ "spotify_id", "NOT", null, true ],
                [ "spotify_id", "!=", "''", true ],
                ["spotify_id", "NOT IN", "SELECT spotify_id FROM _c_m_cronjobs_spotify WHERE cron_id = {$item["ID"]} AND time_check IS NOT NULL AND time_check > SUBDATE( now(), INTERVAL {$item["update_interval"]} DAY )", true]
              )
            ),
            array(
              "from_cronjob" => $item,
              "empty_select" => true,
              "limit"  => $item["item_limit"],
              "single" => false,
              "order_by" => "RAND()",
              "order" => " ",
              "cleaner" => $item["api_name"] == "spotify" ? function ($item) {
                return $item["spotify_id"];
              } : function ($item) {
                return $item;
              }
            )
          );
        }
        if ($count) {

          $_all = bof()->object->__get("m_{$item["object_type"]}")->count(
            array_merge(
              $_filters,
              array()
            ),
            array(
              "cache" => true,
              "from_cronjob" => $item,
            )
          );
          $item["count"] = array(
            "all" => $_all,
            "checked" => $_all > 1000000 ? "?" : bof()->object->__get("m_{$item["object_type"]}")->count(
              array_merge(
                $_filters,
                array(
                  ["spotify_id", "IN", "SELECT spotify_id FROM _c_m_cronjobs_spotify WHERE cron_id = {$item["ID"]} AND time_check IS NOT NULL AND time_check > SUBDATE( now(), INTERVAL {$item["update_interval"]} DAY )", true]
                  // [ "spotify_id", "NOT EXISTS", "SELECT 1 FROM _c_m_cronjobs_spotify AS cjs WHERE cjs.cron_id = {$item["ID"]} AND cjs.time_check IS NOT NULL AND cjs.time_check > SUBDATE(NOW(), INTERVAL {$item["update_interval"]} DAY) AND cjs.spotify_id = spotify_id", true ]
                )
              ),
              array(
                "cache" => false,
                "from_cronjob" => $item,
              )
            ),
            "queued" => $_all > 1000000 ? "?" : bof()->object->__get("m_{$item["object_type"]}")->count(
              array_merge(
                $_filters,
                array(
                  ["spotify_id", "NOT IN", "SELECT spotify_id FROM _c_m_cronjobs_spotify WHERE cron_id = {$item["ID"]} AND time_check IS NOT NULL AND time_check > SUBDATE( now(), INTERVAL {$item["update_interval"]} DAY )", true]
                  // [ "spotify_id", "EXISTS", "SELECT 1 FROM _c_m_cronjobs_spotify AS cjs WHERE cjs.cron_id = {$item["ID"]} AND cjs.time_check IS NOT NULL AND cjs.time_check > SUBDATE(NOW(), INTERVAL {$item["update_interval"]} DAY) AND cjs.spotify_id = spotify_id", true ]
                )
              ),
              array(
                "cache" => false,
                "from_cronjob" => $item,
              )
            )
          );
        }
      }
    }

    return $item;
  }
}
