<?php

if ( !defined( "bof_root" ) ) die;

class object_m_artist extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "m_artist",
      "label" => "Music Artist",
      "icon" => "mic",
      "db_table_name" => "_c_m_artists",
      "db_rel_table_name" => "_c_m_artists_relations",
      "db_rel_table_col_name" => "artist_id",
      "widgetable" => true,
      "browsable" => true,
      "blacklistable" => true,
      "fulltext_search" => true,
    );
  }
  public function columns(){
    return array(

      "name" => array(
        "public" => true,
        "label" => "Name",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
            "only_utf8" => false
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
            "renderer" => function( $displayItem, $item, $displayData ){

              if ( !empty( $item["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_file_cover"]["image_thumb"];

              $displayData["data"] = $item["name_styled"];

              return $displayData;

            }
          ),
          "object" => array(
            "required" => true,
            "seo_slug_source" => true
          )
        ),
      ),
      "spotify_id" => array(
        "label" => "Spotify ID",
        "tip" => "Can be used for better indexing and automation",
        "input" => array(
          "type" => "text",
        ),
        "validator" => array(
          "string_abcd",
          array(
            "empty()",
          ),
        ),
        "bofAdmin" => array(
          "object" => [],
          "filters" => array(
            "has_spotify_id" => array(
              "title" => "Has Spotify ID",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ -1, "no" ],
                  [ 1, "yes" ],
                  [ "__all__", "all" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                [ "values" => [ "-1", "1", "__all__" ] ]
              )
            ),
          )
        ),
        "selectors" => array(
          "spotify_id" => [ "spotify_id", "=" ],
          "has_spotify_id" => function( $val ){

            if ( $val > 0 )
            return [ "spotify_id", "NOT", null, true ];

            return array(
              "oper" => "OR",
              "cond" => array(
                [ "spotify_id", "=", "" ],
                [ "spotify_id", null, null, true ]
              )
            );

          },
        ),
      ),

      "manager_id" => array(
        "label" => "Manager",
        "tip" => "Users can become owner or `manager` of artist(s) after verification. Manager can edit these artist(s) and earn money from sales or streaming",
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => false
          )
        ),
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0,
            "forceNull" => true
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "group" => "owner",
            "multi" => true,
          ),
          "filters" => array(
            "has_manager" => array(
              "title" => "Has Manager",
              "tip" => "Artists with a `connected user as manager` or verified artists",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ -1, "no" ],
                  [ 1, "yes" ],
                  [ "__all__", "all" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                [ "values" => [ "-1", "1", "__all__" ] ]
              )
            ),
            "col_manager" => array(
              "title" => "Manager",
              "bofInput" => array(
                "object",
                array(
                  "type" => "user",
                  "multi" => true,
                )
              )
            ),
          ),
        ),
        "selectors" => array(
          "has_manager" => function( $val ){
              if ( $val )
              return [ "manager_id", "NOT", null, true ];
              return [ "manager_id", null, null, true ];
          },
          "col_manager" => [ "manager_id", "by_column" ],
          "manager_id" => [ "manager_id", "=" ],
          "managed_artists_ids" => [ "manager_id", "by_column" ]
        ),
        "relations" => array(
          "manager" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "parent_object_stats_column" => "s_managed_artists",
              "child_object" => "m_artist",
              "child_object_selector_column" => "manager_id",
              "limit" => 1
            ),
          ),
        ),
      ),

      "spotify_cover" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true,
          ),
        ),
      ),
      "spotify_popularity" => array(
        "label" => "Spotify Popularity",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0,
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "filters" => array(
            "spotify_popularity_min" => array(
              "title" => "Min Spotify popularity",
              "input" => array(
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                  "min" => 1,
                  "max" => 100
                )
              )
            )
          )
        ),
        "selectors" => array(
          "spotify_popularity_min" => function( $val ){
            if ( $val ) return [ "spotify_popularity", ">", $val ];
          }
        )
      ),
      "spotify_followers" => array(
        "label" => "Spotify Followers",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0,
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),

      "has_genre" => array(
        "label" => "Has genre",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "selectors" => array(
          "has_genre" => function( $val ){
            if ( $val ? $val != "no" : false )
            return [ "has_genre", ">", "0" ];
            return [ "has_genre", "=", "0" ];
          }
        ),
        "bofAdmin" => array(
          "filters" => array(
            "has_genre" => array(
              "title" => "Has genre",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  "__all__" => [ "__all__", "ALL" ],
                  "yes" => [ "1", "yes" ],
                  "no" => [ "no", "no" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "__all__", "1", "no" ],
                  "empty()"
                )
              )
            ),
          ),
        ),
      ),

      "time_play" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "time_release" => array(
        "public" => true,
        "label" => "Last Release",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "input" => array(
          "type" => "time"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "time"
          ),
          "object" => []
        ),
      ),
      "time_spotify" => array(
        "label" => "Last spotify sync",
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
        "selectors" => array(
          "spotify_scraped_range" => [ "time_spotify", "timestamp_dyna_range" ],
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "filters" => array(
            "spotify_scraped_range" => array(
              "title" => "Spotify sync time",
              "tip" => "<i>> 24 hours ago</i> means script has synced data with Spotify within past 24 hours, or data is YOUNGER than 24 hours. <i>< 24 hours ago</i> means data is OLDER than 24 hours",
              "input" => array(
                "type" => "select",
                "options" => array(
                  "__all__" => [ "__all__", "-- Select --" ],
                  "1" => [ "1", "> 24 hours ago" ],
                  "2" => [ "2", "> 2 days ago" ],
                  "3" => [ "3", "> 3 days ago" ],
                  "5" => [ "5", "> 5 days ago" ],
                  "7" => [ "7", "> 7 days ago" ],
                  "30" => [ "30", "> 30 days ago" ],
                  "60" => [ "60", "> 60 days ago" ],
                  "90" => [ "90", "> 90 days ago" ],
                  "365" => [ "365", "> past year" ],
                  "-1" => [ "-1", "< 24 hours ago" ],
                  "-2" => [ "-2", "< 2 days ago" ],
                  "-3" => [ "-3", "< 3 days ago" ],
                  "-5" => [ "-5", "< 5 days ago" ],
                  "-7" => [ "-7", "< 7 days ago" ],
                  "-30" => [ "-30", "< 30 days ago" ],
                  "-60" => [ "-60", "< 60 days ago" ],
                  "-90" => [ "-90", "< 90 days ago" ],
                  "-365" => [ "-365", "< past year" ],
                  "0" => [ "0", "Never" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "__all__", "1", "2", "3", "5", "7", "30", "60", "90", "365", "0", "-1", "-2", "-3", "-5", "-7", "-30", "-60", "-90", "-365" ],
                  "empty()"
                )
              )
            ),
          ),
        ),
      ),
      "time_spotify_related" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "time_spotify_discography" => array(
        "label" => "Last spotify discography sync",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "selectors" => array(
          "spotify_albums_scraped_range" => [ "time_spotify_discography", "timestamp_dyna_range" ],
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "filters" => array(
            "spotify_albums_scraped_range" => array(
              "title" => "Spotify discography sync time",
              "tip" => "<i>> 24 hours ago</i> means script has synced data with Spotify within past 24 hours, or data is YOUNGER than 24 hours. <i>< 24 hours ago</i> means data is OLDER than 24 hours",
              "input" => array(
                "type" => "select",
                "options" => array(
                  "__all__" => [ "__all__", "-- Select --" ],
                  "1" => [ "1", "> 24 hours ago" ],
                  "2" => [ "2", "> 2 days ago" ],
                  "3" => [ "3", "> 3 days ago" ],
                  "5" => [ "5", "> 5 days ago" ],
                  "7" => [ "7", "> 7 days ago" ],
                  "30" => [ "30", "> 30 days ago" ],
                  "60" => [ "60", "> 60 days ago" ],
                  "90" => [ "90", "> 90 days ago" ],
                  "365" => [ "365", "> past year" ],
                  "-1" => [ "-1", "< 24 hours ago" ],
                  "-2" => [ "-2", "< 2 days ago" ],
                  "-3" => [ "-3", "< 3 days ago" ],
                  "-5" => [ "-5", "< 5 days ago" ],
                  "-7" => [ "-7", "< 7 days ago" ],
                  "-30" => [ "-30", "< 30 days ago" ],
                  "-60" => [ "-60", "< 60 days ago" ],
                  "-90" => [ "-90", "< 90 days ago" ],
                  "-365" => [ "-365", "< past year" ],
                  "0" => [ "0", "Never" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "__all__", "1", "2", "3", "5", "7", "30", "60", "90", "365", "0", "-1", "-2", "-3", "-5", "-7", "-30", "-60", "-90", "-365" ],
                  "empty()"
                )
              )
            ),
          ),
        ),
      ),
      "time_spotify_tracks" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "time_spotify_automation" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "s_managed" => array(
        "public" => true,
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        )
      ),
      "artist_tracks" => array(
        "public" => true,
        "validator" => array(
          "array",
          array(
            "empty()"
          )
        )
      ),
      "artist_albums" => array(
        "public" => true,
        "validator" => array(
          "array",
          array(
            "empty()"
          )
        )
      ),
      "artist_related_artists" => array(
        "public" => true,
        "validator" => array(
          "array",
          array(
            "empty()"
          )
        )
      ),
    );
  }
  public function stats_columns(){
    return array(
      "views",
      "views_unique",
      "subscribers",
      "popularity",
      "albums",
      "tracks",
      "albums_as_ft" => array(
        "label" => "Albums ( as featured )",
      ),
      "tracks_as_ft" => array(
        "label" => "Tracks ( as featured )",
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "hash",
      "code" => array(
        "from" => array(
          "name"
        ),
        "childs" => array(
          "m_album"
        )
      ),
      "time_add",
      "seo" => array(
        "o_title_format" => array(
          "name" => "name"
        )
      ),
      "social_links",
      "biography",
      "cover",
      "translations" => array(
        "name"
      ),
    );
  }
  public function selectors(){
    return array(
      "query" => function( $val ){
        if ( !$val ) return;
        bof()->nest->validate( $val, "string" );
        if ( bof()->object->core_setting->get("search_index_type") == "fulltext" )
        return [ "name", "MATCH", strtolower( $val ) ];
        return [ "name", "LIKE%lower", strtolower( $val ) ];
      },
      "m_artist_sim_artists" => [ "ID", "related_to_parent", "rel_parent" => "m_artist", "hub_type" => "sim" ],
      "m_album_ft_artists" => [ "ID", "related_to_parent", "rel_parent" => "m_album", "hub_type" => "ft_artist" ],
      "m_track_ft_artists" => [ "ID", "related_to_parent", "rel_parent" => "m_track", "hub_type" => "ft_artist" ],
      "user_managed_artists" => [ "ID", "related_to_parent", "rel_parent" => "user", "hub_type" => "artist_m" ],
    );
  }
  public function relations(){
    return array(
      "genres" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_artist_genres" => array(
              "label" => "Genre(s)",
              "column_name" => "genre_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_genre",
                  "multi" => true
                ),
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => true,
                )
              )
            ),

          ),
          "lists" => array(

            "genres" => array(
              "label" => "Genres",
              "type" => "simple",
              "class" => "tags",
              "renderer" => function( $displayItem, $item, $displayData ){
                $displayData["data"] = "";
                if ( !empty( $item["bof_rel_genres"] ) ){
                  foreach( $item["bof_rel_genres"] as $_genre )
                  $displayData["data"] .= "<span>{$_genre["name"]}</span>";
                }
                return $displayData;
              },
            ),

          ),
          "filters" => array(
            "rel_genre" => array(
              "title" => "Genre(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_genre",
                  "multi" => true,
                  "autoload" => false,
                )
              )
            )
          ),

        ),
        "bofClient" => array(
          "filters" => array(
            "rel_genre" => "_bofAdmin"
          )
        ),

        "selectors" => array(
          "rel_genre" => function( $val ){
            $val = is_array( $val ) ? implode( ",", $val ) : $val;
            return [ "ID", "IN", "SELECT artist_id FROM _c_m_artists_relations WHERE type = 'genre' AND target_id IN ( SELECT genre_id FROM _c_m_genres_hiearchy WHERE hook_id IN ( {$val} ) )", true ];
          },
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "genre",
          "parent_object" => "m_artist",
          "child_object" => "m_genre",
          "child_object_stats_column" => "s_artists"
        ),

      ),
      "tags" => array(

        "bofAdmin" => array(

          "objects" => array(

            "m_artist_tags" => array(
              "label" => "Tag(s)",
              "column_name" => "tag_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_tag",
                  "multi" => true
                ),
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => true,
                )
              )
            ),


          ),
          "filters" => array(
            "rel_tag" => array(
              "title" => "Tag(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_tag",
                  "multi" => true,
                  "autoload" => false,
                )
              ),
              "input" => array(
                "name" => "rel_tag"
              )
            )
          ),

        ),
        "bofClient" => array(
          "filters" => array(
            "rel_tag" => "_bofAdmin"
          )
        ),
        "selectors" => array(
          "rel_tag" => [ "ID", "parent_with_relations", "rel_parent" => "m_artist", "hub_type" => "tag" ],
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "tag",
          "parent_object" => "m_artist",
          "child_object" => "m_tag",
          "child_object_stats_column" => "s_artists"
        ),

      ),
      "langs" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_artist_langs" => array(
              "label" => "Language(s)",
              "column_name" => "lang_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_lang",
                  "multi" => true
                ),
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => true,
                )
              )
            ),

          ),
          "filters" => array(
            "rel_lang" => array(
              "title" => "Language(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_lang",
                  "multi" => true,
                  "autoload" => false,
                )
              )
            )
          ),

        ),
        "bofClient" => array(
          "filters" => array(
            "rel_lang" => array(
              "title" => "Languages(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_lang",
                  "sub_type" => "master",
                  "multi" => true,
                  "autoload" => false,
                )
              )
            )
          )
        ),

        "selectors" => array(
          "rel_lang" => [ "ID", "parent_with_relations", "rel_parent" => "m_artist", "hub_type" => "lang" ],
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "lang",
          "parent_object" => "m_artist",
          "child_object" => "m_lang",
          "child_object_stats_column" => "s_albums"
        ),

      ),
      "sim_artists" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_artist_sim_artists" => array(
              "label" => "Similar Artist(s)",
              "column_name" => "sim_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_artist",
                  "multi" => true
                ),
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => true,
                )
              )
            ),

          ),
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "sim",
          "parent_object" => "m_artist",
          "child_object" => "m_artist",
        ),

      ),
      "albums" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_artist",
          "parent_object_stats_column" => "s_albums",
          "child_object" => "m_album",
          "child_object_selector_column" => "artist_id",
          "delete_child_too" => true,
          "limit" => 100
        ),
      ),
      "tracks" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_artist",
          "parent_object_stats_column" => "s_tracks",
          "child_object" => "m_track",
          "child_object_selector_column" => "artist_id",
          "delete_child_too" => true,
          "limit" => 100
        ),
      ),
      "subscribers" => array(
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
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "music_artist",
        "list_page_url" => "music_artists",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "object_groups" => array(
        [ "owner", "Manager" ]
      ),
      "list" => array(
        "name" => null,
        "genres" => null,
        "stats" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Stats",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            $displayData["data"] .= "<li><b>Views</b>" . number_format($item["s_views"]) . "</li>";
            $displayData["data"] .= "<li><b>Unique Views</b>" . number_format($item["s_views_unique"]) . "</li>";
            $displayData["data"] .= "<li><b>Subscribers</b>" . number_format($item["s_subscribers"]) . "</li>";
            $displayData["data"] .= "<li><b>Albums</b>" . number_format($item["s_albums"]) . "</li>";
            $displayData["data"] .= "<li><b>Tracks</b>" . number_format($item["s_tracks"]) . "</li>";
            if ( !empty( $item["bof_dir_manager"]["username"] ) ){
              $displayData["data"] .= "<li><b>Manager</b> <i style='color:rgba(var(--theme_color),0.6)'>{$item["bof_dir_manager"]["username"]}</i>";
            }
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "time_release" => null,
      ),
      "object" => array(
        "name" => null,
        "cover_id" => null,
        "bg_id" => null,
        "m_artist_genres" => null,
        "m_artist_tags" => null,
        "m_artist_sim_artists" => null,
        "spotify_id" => null,
        "time_release" => null,
        "time_add" => null
      ),
      "buttons_renderer" => function( $item, $buttons ){

        $buttons["list_albums"] = array(
          "label" => "List albums",
          "link" => "music_albums?col_artist={$item["ID"]}"
        );

        $buttons["add_album"] = array(
          "label" => "Add album",
          "link" => "music_album/__new?artist_id={$item["ID"]}"
        );

        $buttons["list_tracks"] = array(
          "label" => "List tracks",
          "link" => "music_tracks?col_artist={$item["ID"]}"
        );

        $buttons["add_track"] = array(
          "label" => "Add track",
          "link" => "music_track/__new?artist_id={$item["ID"]}"
        );

        return $buttons;

      },
    );
  }
  public function bof_client(){
    return array(
      "public_browse" => true,
      "single_url_prefix" => "music/artist",
      "buttons" => array(
        "link" => true,
        "like" => false,
        "share" => true,
        "purchase" => false,
        "play" => true,
        "subscribe" => true,
        "playlist" => false,
        "biography" => true,
        "social_links" => true,
        "extra_after" => array(
          "visit_tags" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              $tags = !empty( $item["bof_rel_tags"] ) ? $item["bof_rel_tags"] : bof()->object->m_tag->select(["m_artist_tags"=>$item["ID"]],["limit"=>20]);
              if ( $tags ){
                $button = array(
                  "hook" => "open_tag",
                  "icon" => "open-in-app",
                  "childs" => array()
                );
                foreach( $tags as $tag ){
                  $button["childs"][] = array(
                    "icon" => "open-in-app",
                    "title" => $tag["name"],
                    "url" => $tag["url"]
                  );
                }
              }

              return $button;

            }
          ),
          "visit_genres" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              $genres = !empty( $item["bof_rel_genres"] ) ? $item["bof_rel_genres"] : bof()->object->m_genre->select(["m_artist_genres"=>$item["ID"]],["limit"=>20]);
              if ( $genres ){
                $button = array(
                  "hook" => "open_genre",
                  "icon" => "open-in-app",
                  "childs" => array()
                );
                foreach( $genres as $genre ){
                  $button["childs"][] = array(
                    "icon" => "open-in-app",
                    "title" => $genre["name"],
                    "url" => $genre["url"]
                  );
                }
              }

              return $button;

            }
          ),
          "visit_manager" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              if ( !empty( $item["manager_id"] ) ){
                $manager = bof()->object->user->select(["ID"=>$item["manager_id"]]);
                $button = array(
                  "hook" => "open_profile",
                  "icon" => "open-in-app",
                  "url" => $manager["url"]
                );
              }

              return $button;

            }
          ),
        )
      )
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $as_widget = false;
    $client_single = false;
    $match_page = false;
    $search_terms = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $search || $listing || $as_widget || $client_single ){
      $_eq["cover"] = [];
    }
    if ( $client_single ){
      $_eq["bg"] = [];
      $_eq["genres"] = array(
        "limit" => 2,
        "public" => true
      );
      $selectArgs["bof_time"] = true;
    }
    if ( $listing ){
      // $_eq["albums_stats"] = true;
      // $_eq["tracks_stats"] = true;
      $_eq["genres"] = array(
        "limit" => 3
      );
      $_eq["manager"] = [];
    }

    if ( $search_terms ){
      $_eq["genres"] = array(
        "limit" => 3,
        "clean" => false
      );
      $_eq["tags"] = array(
        "limit" => 3,
        "clean" => false
      );
    }

    $selectArgs[ "_eq" ] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function create( $whereArray, $insertArray, $updateArray=false ){

    if ( bof()->music->is_blacklisted( "artist", $whereArray, $this ) )
    return false;

    $create_data = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray, true );
    $db_id = $create_data["ID"];

    /*
    if ( $create_data["ID"] && $create_data["type"] == "update" ){

      $recode = bof()->object->m_album->recode(array(
        "artist_id" => $db_id
      ));

      if ( !$recode )
      return false;

    }
    */

    if ( $db_id && !empty( $insertArray["genre_string_array"] ) ){

			$artist_genre_ids = [];

			foreach( $insertArray["genre_string_array"]  as $_genreName ){
				$_genreID = bof()->object->m_genre->get_id( $_genreName );
				if ( $_genreID ) $artist_genre_ids[] = $_genreID;
			}

			if ( $artist_genre_ids ){
				bof()->object->m_artist->make_rels( $db_id, $artist_genre_ids, "genre" );
			}

		}

    return $db_id;

  }
  public function insert( $setArray ){

    $setArray["hash"] = !empty( $setArray["hash"] ) ? $setArray["hash"] : bof()->object->m_artist->get_free_hash();
    // $setArray["code"] = !empty( $setArray["code"] ) ? $setArray["code"] : bof()->general->make_code( $setArray["name"] );
    return bof()->object->_insert( $this, $setArray );

  }
  public function clean( $item, $args ){

    $_eq = [];
    $search = false;
    $listing = false;
    $match_page = false;
    $muse_source = false;
    extract( $args );

    if ( in_array( "cover", array_keys( $_eq ), true ) && empty( $item["bof_file_cover"] ) && !empty( $item["spotify_cover_decoded"] ) && client_auto_images ){
      foreach( $item["spotify_cover_decoded"] as $_s_cover )
      $_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
      $_images = array_keys( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_strings"] = bof()->image->html( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_thumb"] = end( $_images );
    }

    if ( $match_page && empty( $item["cover_id"] ) && !empty( $item["spotify_cover_decoded"] ) && client_auto_images ){
      foreach( $item["spotify_cover_decoded"] as $_s_cover )
      $_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
      $_images = array_keys( $_spotify_covers_raw );
      $item["remote_cover"] = reset( $_images );
    }

    if ( !empty( $item["name"] ) ){
      $_raw_name = $item["name"];
      $item["name_styled"] = $item["name"];
      bof()->nest->validate( $_raw_name, "string", [ "strip_emoji" => false ] );

      $item["name_clean"] = $_raw_name;
      if ( $item["manager_id"] ){
        if ( bof()->getName() == "bof_admin" )
        $item["name_styled"] .= '<span class="material-symbols-outlined verified">verified</span>';
        else
        $item["name_styled"] .= '<span class="mdi mdi-check-decagram verified"></span>';
      }

    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name_clean"],
        "image" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : false
      );
    }
    if ( $muse_source ){

      if ( !empty( $item["spotify_id"] ) ? ( bof()->object->db_setting->get( "spotify_automation" ) && php_sapi_name() !== 'cli' ) : false ){

        bof()->spotify_helper->record( true, array(
          "create_album_get_artist_for_genres" => false,
          "create_track_get_artist_for_genres" => false,
        ) );
        $update = bof()->spotify_helper->get_artist( $item["spotify_id"], array(
          "artist_albums" => false,
          "artist_albums_singular" => false,
          "artist_related" => false,
          "artist_tracks" => true,
        ) );
        bof()->spotify_helper->record( false );

      }

      $tracks = bof()->object->m_artist->get_muse_tracks( $item );

      if ( $tracks ){
        foreach( $tracks as $track ){
          if ( !empty( $track["sources"] ) ){
            $item["sources"] = empty( $item["sources"] ) ? $track["sources"] : array_merge( $item["sources"], $track["sources"] );
          }
        }
      }

    }

    return $item;

  }
  public function clean_as_widget( $item, $args ){

    return array(
      "title" => $item["name_styled"],
      "cover" =>  !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"] : null,
      "cover_url" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : null,
      "raw" => $item
    );

  }
  public function clean_client_single( $item, $args ){

      if ( bof()->object->db_setting->get( "spotify_automation" ) && !empty( $item["spotify_id"] ) && php_sapi_name() !== 'cli' ){

      bof()->spotify_helper->record( true, array(
        "create_album_get_artist_for_genres" => false,
        "create_track_get_artist_for_genres" => false
      ) );

      $update = bof()->spotify_helper->get_artist( $item["spotify_id"], array(
        "artist_albums" => true,
        "artist_albums_singular" => false,
        "artist_related" => true,
        "artist_tracks" => true,
        "is_stored" => $item
      ) );

      bof()->spotify_helper->record( false );

    }

    $item["head_play_title"] = $item["name"];

    $item["subscribed"] = false;
    if ( bof()->user->get()->ID ){
      $item["subscribed"] = bof()->object->ugc_property->select(
        array(
          "user_id" => bof()->user->get()->ID,
          "type" => "subscribe",
          "object_name" => "m_artist",
          "object_id" => $item["ID"]
        )
      ) ? true : false;
    }

    $item["managed"] = !empty( $item["manager_id"] );

    $widgets["top_tracks"]      = $this->_bof_this->clean_client_single_widget( $item, "artist_top_tracks", $args );
    $widgets["studio_albums"]   = $this->_bof_this->clean_client_single_widget( $item, "artist_studio_albums", $args );
    $widgets["single_albums"]   = $this->_bof_this->clean_client_single_widget( $item, "artist_single_albums", $args );
    $widgets["featured_in"]     = $this->_bof_this->clean_client_single_widget( $item, "artist_featured_in", $args );
    $widgets["related_artists"] = $this->_bof_this->clean_client_single_widget( $item, "related_artists", $args );

    // Add direct fields for API (Flutter)
    $item["artist_tracks"] = [];
    $get_popular_tracks = bof()->object->m_track->select(
      array(
        "artist_id" => $item["ID"]
      ),
      array(
        "order_by" => "s_views",
        "order" => "DESC",
        "limit" => 5
      )
    );
    if ( $get_popular_tracks ){
      foreach( $get_popular_tracks as $_track ){
        $item["artist_tracks"][] = array(
          "hash" => $_track["hash"],
          "title" => $_track["title"],
          "sub_title" => !empty( $_track["bof_dir_album"]["title"] ) ? $_track["bof_dir_album"]["title"] : null,
          "duration" => $_track["duration_hr"],
          "cover" => !empty( $_track["bof_file_cover"]["image_thumb"] ) ? $_track["bof_file_cover"]["image_thumb"] : null
        );
      }
    }

    $item["artist_albums"] = [];
    $get_albums = bof()->object->m_album->select(
      array(
        "artist_id" => $item["ID"]
      ),
      array(
        "order_by" => "time_release",
        "order" => "DESC",
        "limit" => 10
      )
    );
    if ( $get_albums ){
      foreach( $get_albums as $_album ){
        $item["artist_albums"][] = array(
          "hash" => $_album["hash"],
          "title" => $_album["title"],
          "sub_title" => ( !empty( $_album["time_release"] ) ? date( "Y", strtotime( $_album["time_release" ] ) ) : "" ) . ( !empty( $_album["s_tracks"] ) ? " • " . $_album["s_tracks"] . " songs" : "" ),
          "cover" => !empty( $_album["bof_file_cover"]["image_thumb"] ) ? $_album["bof_file_cover"]["image_thumb"] : null
        );
      }
    }

    $item["artist_related_artists"] = [];
    $get_related = $this->_bof_this->select(
      array(
        [ "ID", "!=", $item["ID"] ]
      ),
      array(
        "order_by" => "s_subscribers",
        "order" => "DESC",
        "limit" => 5
      )
    );
    if ( $get_related ){
      foreach( $get_related as $_artist ){
        $item["artist_related_artists"][] = array(
          "slug" => $_artist["code"],
          "title" => $_artist["name"],
          "cover" => !empty( $_artist["bof_file_cover"]["image_thumb"] ) ? $_artist["bof_file_cover"]["image_thumb"] : null
        );
      }
    }

    $item["artist_tracks"] = $item["artist_tracks"];
    $item["artist_albums"] = $item["artist_albums"];
    $item["artist_related_artists"] = $item["artist_related_artists"];

    $item = array(
      "widgets" => $widgets,
      "data" => $item,
    );

    return $item;

  }
  public function clean_client_single_widget( $item, $widget_name, $args=[], $caller="self" ){

    $widgets = array(
      "artist_top_tracks" => array(
        "ID" => "artist_top_tracks",
        "widget_type" => "m_track_list",
        "display" => array(
          "type" => "table",
          "title" => bof()->object->language->turn("tracks",[],["uc_first"=>true,"lang"=>"users"]),
          "pagination" => defined("music_artist_top_tracks_more") ? ( music_artist_top_tracks_more ? "list/bof_m_artist?slug={$item["seo_url"]}&widget=artist_top_tracks" : false ) : false,
          "link" => false,
          "table_columns" => array(
            "album" => array(
              "func" => function( $data, $displayData ){
                return $data["raw"]["bof_dir_album"]["title"];
              }
            ),
            "duration_hr"
          ),
          "table_labels" => array(
            [ "val" => "Album", "class" => "secondary_title" ],
            [ "val" => "Duration", "class" => "duration" ],
          ),
          "classes" => [ "topTracks playAsAction" ],
        ),
        "display_ol" => array(
          "type" => "table",
          "title" => "Songs",
          "table_columns" => array(
            "album" => array(
              "func" => function( $data, $displayData ){
                return $data["raw"]["bof_dir_album"]["title"];
              }
            ),
            "duration_hr"
          ),
          "table_labels" => array(
            [ "val" => "Album", "class" => "secondary_title" ],
            [ "val" => "Duration", "class" => "duration" ],
          ),
          "classes" => [ "topTracks playAsAction" ],
        ),
        "object" => array(
          "limit" => 200,
          "name" => "m_track",
          "whereArray" => array(
            "artist_id" => $item["ID"]
          ),
          "selectArray" => array(
            "as_topTrack_widget" => true,
            "order_by" => "spotify_popularity",
            "order" => "DESC",
            "limit" => 5,
          )
        ),
      ),
      "artist_studio_albums" => array(
        "ID" => "artist_studio_albums",
        "widget_type" => "m_album_grid",
        "display" => array(
          "type" => "slider",
          "title" => bof()->object->language->turn("studio_albums",[],["uc_first"=>true,"lang"=>"users"]),
          "pagination" => "list/bof_m_artist?slug={$item["seo_url"]}&widget=artist_studio_albums",
          "link" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => false,
        ),
        "object" => array(
          "name" => "m_album",
          "whereArray" => array(
            "artist_id" => $item["ID"],
            "typeNot" => "single"
          ),
          "selectArray" => array(
            "order_by" => "time_release",
            "order" => "DESC",
            "limit" => 10,
          )
        ),
      ),
      "artist_single_albums" => array(
        "ID" => "artist_single_albums",
        "widget_type" => "m_album_grid",
        "display" => array(
          "type" => "slider",
          "title" => bof()->object->language->turn("single_albums",[],["uc_first"=>true,"lang"=>"users"]),
          "pagination" => "list/bof_m_artist?slug={$item["seo_url"]}&widget=artist_single_albums",
          "link" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => false,
        ),
        "object" => array(
          "name" => "m_album",
          "whereArray" => array(
            "artist_id" => $item["ID"],
            "type" => "single"
          ),
          "selectArray" => array(
            "order_by" => "time_release",
            "order" => "DESC",
            "limit" => 10,
          )
        ),
      ),
      "artist_featured_in" => array(
        "ID" => "artist_featured_in",
        "widget_type" => "m_track_list",
        "display" => array(
          "type" => "slider",
          "title" => bof()->object->language->turn("featured_in",[],["uc_first"=>true,"lang"=>"users"]),
          "pagination" => "list/bof_m_artist?slug={$item["seo_url"]}&widget=artist_featured_in",
          "link" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => false,
        ),
        "object" => array(
          "name" => "m_track",
          "whereArray" => array(
            "rel_artist" => $item["ID"],
          ),
          "selectArray" => array(
            "order_by" => "time_release",
            "order" => "DESC",
            "limit" => 10,
          )
        ),
      ),
      "related_artists" => array(
        "ID" => "related_artists",
        "widget_type" => "m_artist_grid",
        "display" => array(
          "type" => "slider",
          "title" => bof()->object->language->turn("related_artists",[],["uc_first"=>true,"lang"=>"users"]),
          "pagination" => "list/bof_m_artist?slug={$item["seo_url"]}&widget=related_artists",
          "link" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => false,
        ),
        "object" => array(
          "name" => "m_artist",
          "whereArray" => array(
            "m_artist_sim_artists" => $item["ID"],
          ),
          "selectArray" => array(
            "limit" => 10,
          )
        ),
      ),
    );

    if ( ( $caller == "bofClient" && $widget_name == "top_tracks" && !empty( $item["spotify_id"] ) ) ? bof()->object->db_setting->get( "spotify_automation" ) : false ){

      bof()->spotify_helper->record( true, array(
        "create_album_get_artist_for_genres" => false,
        "create_track_get_artist_for_genres" => false
      ) );

      $update = bof()->spotify_helper->get_artist( $item["spotify_id"], array(
        "artist_albums" => true,
        "artist_albums_singular" => true,
        "is_stored" => $item
      ) );

      bof()->spotify_helper->record( false );

    }

    return !empty( $widgets[ $widget_name ] ) ? $widgets[ $widget_name ] : null;

  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["name"] => 1,
    );

    foreach( [ "genres", "tags" ] as $_k ){
      if ( !empty( $item["bof_rel_{$_k}"] ) ){
        foreach( $item["bof_rel_{$_k}"] as $_i ){
          $o[ $_i["name"] ] = $_k == "tags" ? 0.2 : 0.14;
        }
      }
    }

    return $o;

  }

  public function search( $args ){

    $query = null;
    extract( $args );

    $search = $this->_bof_this->select(
      array(
        "query" => $query
      ),
      array(
        "limit" => 10,
        "as_widget" => true
      )
    );

    if ( bof()->object->db_setting->get( "spotify_automation" ) ){

      $search_spotify = bof()->spotify_helper->search( $query, "artist" );
      $search = bof()->spotify_helper->merge_results( $search, $search_spotify, "artist" );

    }

    return $search;

  }
  public function get_muse_tracks( $item ){

    return bof()->object->m_track->select(
      array(
        "artist_id" => $item["ID"]
      ),
      array(
        "order_by" => "spotify_popularity",
        "order" => "DESC",
        "limit" => 10,
        "_eq" => [ "sources" => [], "cover" => [] ],
        "muse_source" => true
      )
    );

  }
  public function get_sources( $item, $args=[] ){

    $sources = [];
    $tracks = bof()->object->m_track->select(
      array(
        "artist_id" => $item["ID"]
      ),
      array(
        "order_by" => "spotify_popularity",
        "order" => "DESC",
        "limit" => 10,
        "_eq" => [ "sources" => [], "cover" => [] ]
      )
    );

    if ( $tracks ){
      foreach( $tracks as $track ){
        $track_sources = bof()->object->m_track->get_sources( $track );
        if ( !empty( $track_sources ) ){
          foreach( $track_sources as $_sk => $_sd )
          $sources[ $_sk ] = $_sd;
        }
      }
    }

    return $sources;

  }

  public function check_time_release( $ID, $time ){

    $artist = $this->_bof_this->select(
      array(
        "ID" => $ID
      ),
      array(
        "clean" => false,
        "limit" => 1,
      )
    );

    if ( $artist["time_release"] ? strtotime( $artist["time_release"] ) < strtotime( $time ) : true ){

      $artist = $this->_bof_this->select(
        array(
          "ID" => $ID
        ),
        array(
          "limit" => 1,
        )
      );

      bof()->object->user->notify_creator_update( "m_artist", $artist["name"], $artist["ID"], $artist["url"] );
      $this->_bof_this->update(
        array(
          "ID" => $ID
        ),
        array(
          "time_release" => $time
        )
      );

    }

  }

  public function get_infinite_related( $item, $args=[] ){

    $per_item = 12;
    $queue = null;
    $infinite = null;
    $related = null;
    $exclude = array();
    extract( $args );

    if ( !empty( $queue["by_object"]["m_track"] ) ){
      foreach( $queue["by_object"]["m_track"] as $_track ){
        $exclude["hash"][] = $_track;
      }
    }

    if ( !empty( $infinite["by_object"]["m_track"] ) ){
      foreach( $infinite["by_object"]["m_track"] as $_track ){
        $exclude["hash"][] = $_track;
      }
    }

    if ( !empty( $related ) ){
      foreach( $related as $_r ){
        $exclude["hash"][] = $_r["raw"]["hash"];
      }
    }

    $_whereArray = array(
      "artist_id" => $item["ID"]
    );

    if ( !empty( $exclude["hash"] ) ){
      $_whereArray[] = array(
        "hash",
        "NOT IN",
        "'".(implode("','",$exclude["hash"]))."'",
        true
      );
    }

    $related_items = bof()->object->m_track->select(
      $_whereArray,
      array(
        "order_by" => " ",
        "order" => "RAND()",
        "limit" => $per_item,
        "single" => false,
        "as_widget" => true,
        "thumb_as_cover" => true,
        "_eq" => array(
          "cover" => [],
          "artist" => []
        ),
        "cache_load_rt" => false
      )
    );

    return !empty( $related_items ) ? $related_items : false;

  }

}

?>
