<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bofAdmin_stats( $loader, $excuter, $args ){

  $loader->bofAdmin->_set_stats( array(
    "dashboard" => array(
      "title" => "Dashboard",
      "functions" => array(
        "exe_item" => function( $stats_name, $item_type, $item_name, $item_data ){

          if ( $item_name == "dash_cards" ){

            $cards = array();
            $cards[] = array(
              "icon" => "person",
              "title" => "Users",
              "value" => bof()->object->user->count([],[])
            );
            $cards[] = array(
              "icon" => "article",
              "title" => "Blog Posts",
              "value" => bof()->object->b_post->count([],[])
            );
            $cards[] = array(
              "icon" => "online_prediction",
              "title" => "Online Users",
              "value" => bof()->object->user->count(array(
                [ "time_online", ">", "SUBDATE( now(), INTERVAL 10 MINUTE )", true ]
              ),[])
            );
            bof()->call( "bofAdmin", "dash_cards", $item_data, $cards );

            $item_data["cards"] = $cards;

          }

          return $item_data;

        }
      ),
      "rows" => array(
        array(
          "cards_col" => array(
            "size" => "12",
            "id" => "cards_col"
          )
        ),
        array(
          "dash_left" => array(
            "size" => "7",
            "id" => "dash_left"
          ),
          "dash_right" => array(
            "size" => "5",
            "id" => "dash_right"
          ),
        ),
        array(
          "payments" => array(
            "size" => "12",
            "id" => "payments"
          ),
        ),
        array(
          "user_streams_types" => array(
            "size" => "12",
            "id" => "user_streams_types"
          ),
        ),
      ),
      "items" => array(
        "dash_cards" => array(
          "col" => "cards_col",
          "type" => "cards",
          "cards" => array()
        ),
        "new_users" => array(
          "col" => "dash_left",
          "type" => "graph",
          "title" => "New Users",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_list",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "api_reqs_map" => array(
          "col" => "dash_right",
          "type" => "graph",
          "title" => "API Requests",
          "tip" => "Per country in last month",
          "graph" => array(
            "type" => "map",
            "table" => "_bof_log_api_requests",
            "map_country_col" => "ip_country",
            "map_val_col" => "COUNT(*)",
            "force_range" => 30
          ),
        ),
        "payments" => array(
          "col" => "payments",
          "type" => "graph",
          "title" => "Succesfull Payments",
          "tip" => "By gateway",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_payments",
            "xy_stacked_time_col" => "time_pay",
            "xy_stacked_val_col" => "SUM(amount)",
            "xy_stacked_type_col" => "gateway_name",
            "tooltip_append" => bof()->object->currency->get_default()["symbol"]
          ),
        ),
        "user_streams_types" => array(
          "col" => "user_streams_types",
          "type" => "graph",
          "title" => "User Streams",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_actions",
            "xy_stacked_time_col" => "time_add",
            "xy_stacked_val_col" => "COUNT(*)",
            "xy_stacked_type_col" => "object_name",
            "extraWhere" => "type = 'stream'",
            "labels" => array(
              "m_track" => "Track",
              "p_episode" => "Podcast",
              "r_station" => "Radio",
              "a_book" => "Audiobook"
            )
          ),
        ),
      ),
    ),
    "visits" => array(
      "title" => "Visits",
      "functions" => array(
        "exe_item" => function( $stats_name, $item_type, $item_name, $item_data ){

          if ( $item_name == "cards" ){

            $cards = array();
            $cards[] = array(
              "icon" => "online_prediction",
              "title" => "Online Users",
              "value" => bof()->object->user->count(array(
                [ "time_online", ">", "SUBDATE( now(), INTERVAL 10 MINUTE )", true ]
              ),[])
            );
            $cards[] = array(
              "icon" => "dns",
              "title" => "Cached IPs",
              "value" => bof()->db->_select( array(
                "table" => "_bof_log_ips",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
            );
            $cards[] = array(
              "icon" => "lock_open",
              "title" => "Client Sessions",
              "value" => bof()->db->_select( array(
                "table" => "_bof_cache_sessions",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
            );
            $cards[] = array(
              "icon" => "lock_open",
              "title" => "Admin Sessions",
              "value" => bof()->db->_select( array(
                "table" => "_bof_cache_sessions_admin",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
            );
            $cards[] = array(
              "icon" => "turn_right",
              "title" => "Client API Requests",
              "value" => bof()->db->_select( array(
                "table" => "_bof_log_api_requests",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
            );
            $cards[] = array(
              "icon" => "turn_right",
              "title" => "Client Requests",
              "value" => bof()->db->_select( array(
                "table" => "_bof_log_requests",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
            );
            $cards[] = array(
              "icon" => "turn_left",
              "title" => "Admin API Requests",
              "value" => bof()->db->_select( array(
                "table" => "_bof_log_api_requests_admin",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
            );
            $cards[] = array(
              "icon" => "turn_left",
              "title" => "Admin Requests",
              "value" => bof()->db->_select( array(
                "table" => "_bof_log_requests_admin",
                "columns" => "COUNT(*) as c",
                "single" => true,
                "limit" => 1
              ) )["c"]
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
          ),
        ),
        array(
          "dash_left" => array(
            "size" => "7",
            "id" => "dash_left"
          ),
          "dash_right" => array(
            "size" => "5",
            "id" => "dash_right"
          ),
        ),
        array(
          "dash_left2" => array(
            "size" => "5",
            "id" => "dash_left2"
          ),
          "dash_right2" => array(
            "size" => "7",
            "id" => "dash_right2"
          ),
        ),
        array(
          "dash_left3" => array(
            "size" => "7",
            "id" => "dash_left3"
          ),
          "dash_right3" => array(
            "size" => "5",
            "id" => "dash_right3"
          ),
        ),
        array(
          "dash_left4" => array(
            "size" => "6",
            "id" => "dash_left4"
          ),
          "dash_right4" => array(
            "size" => "6",
            "id" => "dash_right4"
          ),
        ),
        array(
          "top_os" => array(
            "size" => "6",
            "id" => "top_os"
          ),
          "top_browser" => array(
            "size" => "6",
            "id" => "top_browser"
          ),
          "top_device" => array(
            "size" => "6",
            "id" => "top_device"
          ),
          "top_endpoints" => array(
            "size" => "6",
            "id" => "top_endpoints"
          ),
        ),
      ),
      "items" => array(
        "cards" => array(
          "col" => "cards",
          "type" => "cards",
          "cards" => array()
        ),
        "ip_reqs" => array(
          "col" => "dash_left",
          "type" => "graph",
          "title" => "Recorded IPs",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_log_ips",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "ip_reqs_map" => array(
          "col" => "dash_right",
          "type" => "graph",
          "title" => "Recorded IPs",
          "tip" => "Per country",
          "graph" => array(
            "type" => "map",
            "table" => "_bof_log_ips",
            "map_country_col" => "country",
            "map_val_col" => "COUNT(*)",
          ),
        ),
        "api_reqs_map" => array(
          "col" => "dash_left2",
          "type" => "graph",
          "title" => "API Requests",
          "tip" => "Per country",
          "graph" => array(
            "type" => "map",
            "table" => "_bof_log_api_requests",
            "map_country_col" => "ip_country",
            "map_val_col" => "COUNT(*)",
          ),
        ),
        "api_reqs" => array(
          "col" => "dash_right2",
          "type" => "graph",
          "title" => "API Requests",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_log_api_requests",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "admin_api_reqs" => array(
          "col" => "dash_left3",
          "type" => "graph",
          "title" => "Admin - API Requests",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_log_api_requests_admin",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "admin_api_reqs_map" => array(
          "col" => "dash_right3",
          "type" => "graph",
          "title" => "Admin - API Requests",
          "tip" => "Per country",
          "graph" => array(
            "type" => "map",
            "table" => "_bof_log_api_requests_admin",
            "map_country_col" => "ip_country",
            "map_val_col" => "COUNT(*)",
          ),
        ),
        "sessions" => array(
          "col" => "dash_left4",
          "type" => "graph",
          "title" => "Sessions",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_cache_sessions",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "admin_sessions" => array(
          "col" => "dash_right4",
          "type" => "graph",
          "title" => "Admin sessions",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_cache_sessions_admin",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "top_os" => array(
          "col" => "top_os",
          "type" => "graph",
          "title" => "Operating Systems",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_log_requests",
            "pie_var_col" => "agent_os",
            "pie_val_col" => "COUNT(*)",
            "pie_time_col" => "time_add"
          ),
        ),
        "top_device" => array(
          "col" => "top_device",
          "type" => "graph",
          "title" => "Device types",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_log_requests",
            "pie_var_col" => "agent_type",
            "pie_val_col" => "COUNT(*)",
            "pie_time_col" => "time_add"
          ),
        ),
        "top_browser" => array(
          "col" => "top_browser",
          "type" => "graph",
          "title" => "Browsers",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_log_requests",
            "pie_var_col" => "agent_browser",
            "pie_val_col" => "COUNT(*)",
            "pie_time_col" => "time_add"
          ),
        ),
        "top_endpoints" => array(
          "col" => "top_endpoints",
          "type" => "graph",
          "title" => "Endpoints",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_log_api_requests",
            "pie_var_col" => "endpoint_name",
            "pie_val_col" => "COUNT(*)",
            "pie_time_col" => "time_add"
          ),
        ),
      ),
    ),
    "system" => array(
      "title" => "System",
      "functions" => array(
        "exe_item" => function( $stats_name, $item_type, $item_name, $item_data ){

          if ( $item_name == "cards" ){

            $cards = array();
            $cards[] = array(
              "icon" => "payments",
              "title" => "Cronjobs",
              "value" => count( bof()->cronjob->get_jobs() )
            );
            $cards[] = array(
              "icon" => "credit_score",
              "title" => "Total Files",
              "value" => bof()->object->file->count([],[])
            );
            $cards[] = array(
              "icon" => "credit_score",
              "title" => "Files Size",
              "value" => bof()->general->filesize_hr( bof()->object->file->count([],["columns"=>"SUM(size)"]) )
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
          "files_count" => array(
            "size" => "6",
            "id" => "files_count"
          ),
          "files_size" => array(
            "size" => "6",
            "id" => "files_size"
          ),
        ),
        array(
          "curl" => array(
            "size" => "6",
            "id" => "curl"
          ),
          "cronjob" => array(
            "size" => "6",
            "id" => "cronjob"
          ),
        ),
        array(
          "file_types" => array(
            "size" => "6",
            "id" => "file_types"
          ),
          "file_types2" => array(
            "size" => "6",
            "id" => "file_types2"
          ),
          "file_objects" => array(
            "size" => "6",
            "id" => "file_objects"
          ),
          "file_objects2" => array(
            "size" => "6",
            "id" => "file_objects2"
          ),
          "cronjob2" => array(
            "size" => "6",
            "id" => "cronjob2"
          ),
        ),
      ),
      "items" => array(
        "cards" => array(
          "col" => "cards",
          "type" => "cards",
          "cards" => array()
        ),
        "files_count" => array(
          "col" => "files_count",
          "type" => "graph",
          "title" => "New Files",
          "tip" => "By Number",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_files",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "files_size" => array(
          "col" => "files_size",
          "type" => "graph",
          "title" => "New Files",
          "tip" => "By Size",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_files",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "ROUND(SUM(size)/1024)",
          ),
        ),
        "curl" => array(
          "col" => "curl",
          "type" => "graph",
          "title" => "cURL Requests",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_log_curls",
            "xy_basic_time_col" => "time_start",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "cronjob" => array(
          "col" => "cronjob",
          "type" => "graph",
          "title" => "Cronjob executions",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_bof_log_cronjob_g",
            "xy_basic_time_col" => "time_start",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "file_types" => array(
          "col" => "file_types",
          "type" => "graph",
          "title" => "Files Types",
          "tip" => "By count",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_files",
            "pie_var_col" => "type",
            "pie_val_col" => "COUNT(*)",
            "pie_time_col" => "time_add"
          ),
        ),
        "file_types2" => array(
          "col" => "file_types2",
          "type" => "graph",
          "title" => "Files Types",
          "tip" => "By size - in MB",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_files",
            "pie_var_col" => "type",
            "pie_val_col" => "ROUND(SUM(size)/(1024*1024))",
            "pie_time_col" => "time_add"
          ),
        ),
        "file_objects" => array(
          "col" => "file_objects",
          "type" => "graph",
          "title" => "Files Objects",
          "tip" => "By count",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_files",
            "pie_var_col" => "used_in_object",
            "pie_val_col" => "COUNT(*)",
            "pie_time_col" => "time_add"
          ),
        ),
        "file_objects2" => array(
          "col" => "file_objects2",
          "type" => "graph",
          "title" => "Files Objects",
          "tip" => "By size - in MB",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_files",
            "pie_var_col" => "used_in_object",
            "pie_val_col" => "ROUND(SUM(size)/(1024*1024))",
            "pie_time_col" => "time_add"
          ),
        ),
        "cronjob2" => array(
          "col" => "cronjob2",
          "type" => "graph",
          "title" => "Cronjobs",
          "tip" => "By execution time - in seconds",
          "graph" => array(
            "type" => "pie_basic",
            "table" => "_bof_log_cronjob_g",
            "pie_var_col" => "code",
            "pie_val_col" => "SUM(TIMESTAMPDIFF(SECOND, time_start, time_end ))",
            "pie_time_col" => "time_start"
          ),
        ),
      ),
    ),
    "users" => array(
      "title" => "Users",
      "functions" => array(
        "exe_item" => function( $stats_name, $item_type, $item_name, $item_data ){

          if ( $item_name == "cards" ){

            $cards = array();
            $cards[] = array(
              "icon" => "person",
              "title" => "Total Users",
              "value" => bof()->object->user->count([],[])
            );
            $cards[] = array(
              "icon" => "how_to_reg",
              "title" => "Verified Users",
              "value" => bof()->object->user->count(array(
                [ "time_verify", "NOT", null, true ]
              ),[])
            );
            $cards[] = array(
              "icon" => "online_prediction",
              "title" => "Online Users",
              "value" => bof()->object->user->count(array(
                [ "time_online", ">", "SUBDATE( now(), INTERVAL 10 MINUTE )", true ]
              ),[])
            );
            $cards[] = array(
              "icon" => "queue_music",
              "title" => "Playlists",
              "value" => bof()->object->ugc_playlist->count([],[])
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
          "new_users" => array(
            "size" => "6",
            "id" => "new_users"
          ),
          "user_requests" => array(
            "size" => "6",
            "id" => "user_requests"
          ),
        ),
        array(
          "user_properties" => array(
            "size" => "6",
            "id" => "user_properties"
          ),
          "user_uploads" => array(
            "size" => "6",
            "id" => "user_uploads"
          ),
        ),
        array(
          "user_playlists" => array(
            "size" => "6",
            "id" => "user_playlists"
          ),
          "user_notifications" => array(
            "size" => "6",
            "id" => "user_notifications"
          ),
        ),
        array(
          "user_streams" => array(
            "size" => "6",
            "id" => "user_streams"
          ),
          "user_downloads" => array(
            "size" => "6",
            "id" => "user_downloads"
          ),
        ),
        array(
          "user_streams_types" => array(
            "size" => "12",
            "id" => "user_streams"
          ),
        ),
      ),
      "items" => array(
        "cards" => array(
          "col" => "cards",
          "type" => "cards",
          "cards" => array()
        ),
        "user_properties" => array(
          "col" => "user_properties",
          "type" => "graph",
          "title" => "User Properties",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_properties",
            "xy_stacked_time_col" => "time_add",
            "xy_stacked_val_col" => "COUNT(*)",
            "xy_stacked_type_col" => "type",
          ),
        ),
        "user_uploads" => array(
          "col" => "user_uploads",
          "type" => "graph",
          "title" => "User Uploads",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_properties",
            "xy_stacked_time_col" => "time_add",
            "xy_stacked_val_col" => "COUNT(*)",
            "xy_stacked_type_col" => "object_name",
            "extraWhere" => "type = 'upload'"
          ),
        ),
        "new_users" => array(
          "col" => "new_users",
          "type" => "graph",
          "title" => "New Users",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_list",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "user_playlists" => array(
          "col" => "user_playlists",
          "type" => "graph",
          "title" => "Playlists",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_playlists",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "user_requests" => array(
          "col" => "user_requests",
          "type" => "graph",
          "title" => "Reports",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_requests",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "user_notifications" => array(
          "col" => "user_notifications",
          "type" => "graph",
          "title" => "Notifications",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_notifications",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "user_streams" => array(
          "col" => "user_streams",
          "type" => "graph",
          "title" => "Streams",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_actions",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
            "extraWhere" => "type = 'stream'"
          ),
        ),
        "user_downloads" => array(
          "col" => "user_downloads",
          "type" => "graph",
          "title" => "Downloads",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_actions",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
            "extraWhere" => "type = 'download'"
          ),
        ),
        "user_streams_types" => array(
          "col" => "user_streams_types",
          "type" => "graph",
          "title" => "User Streams",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_actions",
            "xy_stacked_time_col" => "time_add",
            "xy_stacked_val_col" => "COUNT(*)",
            "xy_stacked_type_col" => "object_name",
            "extraWhere" => "type = 'stream'",
            "labels" => array(
              "m_track" => "Track",
              "p_episode" => "Podcast",
              "r_station" => "Radio",
              "a_book" => "Audiobook"
            )
          ),
        ),
      ),
    ),
    "financial" => array(
      "title" => "Financial",
      "functions" => array(
        "exe_item" => function( $stats_name, $item_type, $item_name, $item_data ){

          if ( $item_name == "cards" ){

            $cards = array();
            $cards[] = array(
              "icon" => "payments",
              "title" => "Payments",
              "value" => bof()->object->payment->count([],[])
            );
            $cards[] = array(
              "icon" => "credit_score",
              "title" => "Approved Payments",
              "value" => bof()->object->payment->count(["approved"=>1],[])
            );
            $cards[] = array(
              "icon" => "credit_score",
              "title" => "Approved Amount",
              "value" => bof()->object->payment->count(["approved"=>1],["columns"=>"SUM(amount)"]) . bof()->object->currency->get_default()["symbol"]
            );
            $cards[] = array(
              "icon" => "loyalty",
              "title" => "Total Subscribers",
              "value" => bof()->object->user_subs->count([],[])
            );
            $cards[] = array(
              "icon" => "card_membership",
              "title" => "Active Subscribers",
              "value" => bof()->object->user_subs->count(["has_time"=>true],[])
            );
            $cards[] = array(
              "icon" => "receipt_long",
              "title" => "Total Transactions",
              "value" => bof()->object->transaction->count([],[])
            );
            $cards[] = array(
              "icon" => "shopping_basket",
              "title" => "Total Sales",
              "value" => bof()->object->transaction->count(["type"=>"buy"],[])
            );
            $cards[] = array(
              "icon" => "monetization_on",
              "title" => "Total Revenue",
              "value" => bof()->object->transaction->count([],["columns"=>"SUM(revenue)"]) . bof()->object->currency->get_default()["symbol"]
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
          "all_payments" => array(
            "size" => "12",
            "id" => "all_payments"
          ),
        ),
        array(
          "payments" => array(
            "size" => "6",
            "id" => "payments"
          ),
          "transactions" => array(
            "size" => "6",
            "id" => "transactions"
          ),
        ),
        array(
          "transactions_r" => array(
            "size" => "6",
            "id" => "transactions_r"
          ),
          "subscribes" => array(
            "size" => "6",
            "id" => "subscribes"
          ),
        ),
        array(
          "purchases" => array(
            "size" => "6",
            "id" => "purchases"
          ),
          "purchases2" => array(
            "size" => "6",
            "id" => "purchases2"
          ),
        ),
      ),
      "items" => array(
        "cards" => array(
          "col" => "cards",
          "type" => "cards",
          "cards" => array()
        ),
        "all_payments" => array(
          "col" => "all_payments",
          "type" => "graph",
          "title" => "Payments",
          "tip" => "By status",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_payments",
            "xy_stacked_time_col" => "time_add",
            "xy_stacked_val_col" => "SUM(amount)",
            "xy_stacked_type_col" => "approved",
            "labels" => array(
              "0" => "rejected",
              "1" => "approved"
            ),
            "tooltip_append" => bof()->object->currency->get_default()["symbol"]
          ),
        ),
        "payments" => array(
          "col" => "payments",
          "type" => "graph",
          "title" => "Succesfull Payments",
          "tip" => "By gateway",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_payments",
            "xy_stacked_time_col" => "time_pay",
            "xy_stacked_val_col" => "SUM(amount)",
            "xy_stacked_type_col" => "gateway_name",
            "tooltip_append" => bof()->object->currency->get_default()["symbol"],
            "extraWhere" => "approved = 1"
          ),
        ),
        "transactions" => array(
          "col" => "transactions",
          "type" => "graph",
          "title" => "Transactions",
          "tip" => "By count",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_transactions",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "transactions_r" => array(
          "col" => "transactions_r",
          "type" => "graph",
          "title" => "Transactions",
          "tip" => "By revenue",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_transactions",
            "xy_basic_time_col" => "time_add",
            "xy_basic_val_col" => "SUM(revenue)",
          ),
        ),
        "subscribes" => array(
          "col" => "subscribes",
          "type" => "graph",
          "title" => "Subscribed users",
          "tip" => "By count",
          "graph" => array(
            "type" => "xy_basic",
            "table" => "_u_subs",
            "xy_basic_time_col" => "time_purchased",
            "xy_basic_val_col" => "COUNT(*)",
          ),
        ),
        "purchases" => array(
          "col" => "purchases",
          "type" => "graph",
          "title" => "Purchases",
          "tip" => "By type",
          "graph" => array(
            "type" => "xy_stacked",
            "table" => "_u_transactions",
            "xy_stacked_time_col" => "time_add",
            "xy_stacked_val_col" => "SUM(revenue)",
            "xy_stacked_type_col" => "object_type",
            "extraWhere" => "type = 'buy'",
            "tooltip_append" => bof()->object->currency->get_default()["symbol"]
          ),
        ),

      ),
    ),
  ) );

  $loader->bofAdmin->stats( substr( $loader->request->get_requested_url(), strlen( "bofAdmin/stats/" ), -1 ) );

}

?>
