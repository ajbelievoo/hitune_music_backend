<?php

if ( !defined( "bof_root" ) ) die;

class object_m_album extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "m_album",
      "label" => "Music Album",
      "icon" => "album",
      "db_table_name" => "_c_m_albums",
      "db_rel_table_name" => "_c_m_albums_relations",
      "db_rel_table_col_name" => "album_id",
      "widgetable" => true,
      "browsable" => true,
      "blacklistable" => true,
      "fulltext_search" => true
    );
  }
  public function columns(){

    $columns = array(

      "title" => array(
        "public" => true,
        "label" => "Title",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_file_cover"]["image_thumb"];
              $displayData["data"] .= "<span class='sub'>{$item["type"]} album</span>";
              return $displayData;
            }
          ),
          "object" => array(
            "type" => "text",
            "required" => true,
            "seo_slug_source" => true
          )
        ),
      ),
      "type" => array(
        "public" => true,
        "label" => "Type",
        "input" => array(
          "type" => "select_i",
          "options" => array(
            [ "studio", "Studio" ],
            [ "compilation", "Compilation" ],
            [ "single", "Single" ],
            [ "mixtape", "Mixtape" ],
          ),
        ),
        "validator" => array(
          "in_array",
          array(
            "values" => array(
              "studio",
              "compilation",
              "single",
              "mixtape"
            ),
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true,
            "multi" => true
          ),
          "filters" => array(
            "type" => array(
              "title" => "Album Type",
              "input" => array(
                "value" => "__all__",
                "type" => "select_i",
                "options" => array(
                  [ "__all__", "All" ],
                  [ "studio", "Studio" ],
                  [ "compilation", "Compilation" ],
                  [ "single", "Single" ],
                  [ "mixtape", "Mixtape" ],
                )
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => array(
                    "__all__",
                    "studio",
                    "compilation",
                    "single",
                    "mixtape"
                  ),
                ),
              ),
            )
          ),
        ),
        "bofClient" => array(
          "filters" => array(
            "type" => "_bofAdmin"
          )
        ),
        "selectors" => array(
          "type" => [ "type", "=" ],
          "typeNot" => [ "type", "!=" ],
        ),
      ),
      "uploader_id" => array(
        "label" => "Uploader",
        "tip" => "Users can become uploader of an album by uploading it for the first time",
        "bofInput" => array(
          "object",
          array(
            "type" => "user"
          ),
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => true
          ),
          "filters" => array(
            "col_uploader" => array(
              "title" => "Uploader",
              "bofInput" => array(
                "object",
                array(
                  "type" => "user",
                  "multi" => true,
                )
              )
            ),
            "has_uploader" => array(
              "title" => "Has Uploader",
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
          "uploader_id" => [ "uploader_id", "=" ],
          "col_uploader" => [ "uploader_id", "by_column" ],
          "has_uploader" => function( $val ){

              if ( $val == 1 )
              return [ "uploader_id", ">", "0" ];

              return array(
                "oper" => "OR",
                "cond" => array(
                  [ "uploader_id", null, null, true ],
                  [ "uploader_id", "=", 0 ]
                )
              );

          },
        ),
        "relations" => array(
          "uploader" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "m_album",
              "child_object_selector_column" => "uploader_id",
              "limit" => 1
            ),
          ),
          "ugc_uploader" => array(
  	        "exec" => array(
  	          "type" => "direct",
  	          "parent_object" => "m_album",
  	          "child_object" => "ugc_property",
  	          "child_object_selector_column" => "object_id",
  	          "child_object_where_array" => array(
  	            "type" => "upload",
  	            [ "object_name", "=", "m_album" ]
  	          ),
  	          "delete_child_too" => true
  	        ),
  	      ),
        ),
      ),
      "artist_id" => array(
        "label" => "Artist",
        "validator" => "int",
        "bofInput" => array(
          "object",
          array(
            "type" => "m_artist"
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true,
            "multi" => true,
          ),
          "filters" => array(
            "col_artist" => array(
              "title" => "Artist(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_artist",
                  "multi" => true,
                )
              )
            ),
          ),
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = $item["bof_dir_artist"]["name_styled"];
              if ( !empty( $item["bof_dir_artist"]["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_dir_artist"]["bof_file_cover"]["image_thumb"];
              return $displayData;
            }
          )
        ),
        "bofClient" => array(
          "filters" => array(
            "col_artist" => "_bofAdmin"
          )
        ),
        "selectors" => array(
          "artist_id" => [ "artist_id", "=" ],
          "col_artist" => [ "artist_id", "by_column" ],
        ),
        "relations" => array(
          "artist" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "m_artist",
              "parent_object_stats_column" => "s_albums",
              "child_object" => "m_album",
              "child_object_selector_column" => "artist_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
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
            return false;
          }
        )
      ),
      "description" => array(
        "public" => true,
        "label" => "Description",
        "validator" => array(
          "editor_js",
          array(
            "empty()",
          ),
        ),
        "input" => array(
          "type" => "text_editor"
        ),
        "bofAdmin" => array(
          "object" => array(
            "group" => "description"
          )
        )
      ),
      "time_play" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
      ),
      "time_release" => array(
        "public" => true,
        "label" => "Release time",
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
        "input" => array(
          "type" => "time"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "multi" => true
          ),
          "filters" => array(
            "release_year_range" => array(
              "title" => "Release time",
              "input" => array(
                "type" => "range_two",
                "min" => 1920,
                "max" => 2024
              ),
              "validator" => array(
                "year_range",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "bofClient" => array(
          "filters" => array(
            "release_year_range" => array(
              "title" => "Release time",
              "tip" => "Release time in form of <b>min_year-max_year</b> for example: 1960-2022",
              "input" => array(
                "type" => "text",
                "placeholder" => "19xx-2024"
              ),
              "validator" => array(
                "year_range",
                array(
                  "empty()"
                )
              )
            ),
          )
        ),
        "selectors" => array(
          "release_year_range" => function( $val ){

            if ( !$val ? true : !is_string( $val ) )
            return;

            list( $min, $max ) = explode( "-", $val );

            return array(
              "oper" => "AND",
              "cond" => array(
                [ "time_release", ">", $min . ( "-01-01" ) ],
                [ "time_release", "<", $max+1 . ( "-01-01" ) ],
              )
            );

          }
        )
      ),
      "time_spotify" => array(
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

    );

    if ( bof()->music->hide_single_albums() ){
      unset( $columns["type"]["input"]["options"][2] );
      unset( $columns["type"]["bofAdmin"]["filters"]["type"]["input"]["options"][3] );
    }

    return $columns;

  }
  public function stats_columns(){
    return array(
      "views",
      "views_unique",
      "likes",
      "popularity",
      "tracks",
      "tracks_duration" => array(
        "label" => "tracks duration",
      ),
      "sales"
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "code" => array(
        "from" => array(
          "title"
        )
      ),
      "hash",
      "time_add",
      "seo" => array(
        "o_title_format" => array(
          "title" => "album title",
          "type" => "album type",
          "artist_name" => "artist name"
        )
      ),
      "cover",
      "translations" => array(
        "title"
      ),
      "price"
    );
  }
  public function selectors(){
    return array(
      "query" => function( $val ){
        if ( !$val ) return;
        bof()->nest->validate( $val, "string" );
        if ( bof()->object->core_setting->get("search_index_type") == "fulltext" )
        return [ "title", "MATCH", strtolower( $val ) ];
        return [ "title", "LIKE%lower", strtolower( $val ) ];
      },
    );
  }
  public function relations(){
    return array(
      "genres" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_album_genres" => array(
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
                  $displayData["data"] = "<span>{$_genre["name"]}</span>";
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
            "rel_genre" => array(
              "title" => "Genre(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_genre",
                  "sub_type" => "master",
                  "multi" => true,
                  "autoload" => false,
                )
              )
            )
          )
        ),

        "selectors" => array(
          "rel_genre" => function( $val ){
            $val = is_array( $val ) ? implode( ",", $val ) : $val;
            if ( bof()->object->m_genre->genre_hiearchy() )
            return [ "ID", "IN", "SELECT album_id FROM _c_m_albums_relations WHERE type = 'genre' AND target_id IN ( SELECT genre_id FROM _c_m_genres_hiearchy WHERE hook_id IN ( {$val} ) )", true ];
            else
            return [ "ID", "IN", "SELECT album_id FROM _c_m_albums_relations WHERE type = 'genre' AND target_id IN ( {$val} )", true ];
          },
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "genre",
          "parent_object" => "m_album",
          "child_object" => "m_genre",
          "child_object_stats_column" => "s_albums"
        ),

      ),
      "tags" => array(

        "bofAdmin" => array(

          "objects" => array(

            "m_album_tags" => array(
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
          "rel_tag" => [ "ID", "parent_with_relations", "rel_parent" => "m_album", "hub_type" => "tag" ],
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "tag",
          "parent_object" => "m_album",
          "child_object" => "m_tag",
          "child_object_stats_column" => "s_albums"
        ),

      ),
      "langs" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_album_langs" => array(
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
          "rel_lang" => [ "ID", "parent_with_relations", "rel_parent" => "m_album", "hub_type" => "lang" ],
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "lang",
          "parent_object" => "m_album",
          "child_object" => "m_lang",
          "child_object_stats_column" => "s_albums"
        ),

      ),
      "ft_artists" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_album_ft_artists" => array(
              "label" => "Featured Artist(s)",
              "column_name" => "ft_artist_ids",
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
          "hub_type" => "ft_artist",
          "parent_object" => "m_album",
          "child_object" => "m_artist",
        ),

      ),
      "tracks" => array(

        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_album",
          "parent_object_stats_column" => "s_tracks",
          "child_object" => "m_track",
          "child_object_selector_column" => "album_id",
          "delete_child_too" => true,
          "limit" => 100
        ),

      ),
      "likers" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_album",
          "parent_object_stats_column" => "s_likes",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "like",
            [ "object_name", "=", "m_album" ]
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
        "edit_page_url" => "music_album",
        "list_page_url" => "music_albums",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "object_groups" => array(
        [ "description", "Description" ],
        [ "price", "Price" ],
      ),
      "list" => array(
        "title" => null,
        "price" => null,
        "artist_id" => null,
        "genres" => null,
        "stats" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Stats",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            if( $item["price"] )
            $displayData["data"] .= "<li><b>Sales</b>" . ($item["s_sales"]?number_format($item["s_sales"]):"-") . "</li>";
            $displayData["data"] .= "<li><b>Views</b>" . number_format($item["s_views"]) . "</li>";
            $displayData["data"] .= "<li><b>Unique Views</b>" . number_format($item["s_views_unique"]) . "</li>";
            $displayData["data"] .= "<li><b>Tracks</b>" . number_format($item["s_tracks"]) . "</li>";
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
      ),
      "object" => array(
        "title" => null,
        "cover_id" => null,
        "bg_id" => null,
      ),
      "buttons_renderer" => function( $item, $buttons ){

        $buttons["list_tracks"] = array(
          "label" => "List tracks",
          "link" => "music_tracks?col_album={$item["ID"]}"
        );

        $buttons["add_track"] = array(
          "label" => "Add track",
          "link" => "music_track/__new?album_id={$item["ID"]}&artist_id={$item["artist_id"]}"
        );

        return $buttons;

      },
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $item_name == "artist_id" && empty( $item_data["input"]["value"] ) && $request["type"] == "new" ){
          $givenID = bof()->nest->user_input( "get", "artist_id", "int" );
          if ( $givenID ) $item_data["input"]["value"] = $givenID;
        }

      },
    );
  }
  public function bof_client(){
    return array(
      "public_browse" => true,
      "single_url_prefix" => "music/album",
      "list_url" => "albums",
      "buttons" => array(
        "link" => true,
        "share" => true,
        "purchase" => true,
        "play" => true,
        "playlist" => true,
        "like" => true,
        "download_child" => true,
        "extra_after" => array(
          "visit_artist" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $artist = bof()->object->m_artist->select(["ID"=>$item["artist_id"]]);
              $related_artists = bof()->object->m_artist->select(["m_album_ft_artists"=>$item["ID"]],["limit"=>20]);

              if ( empty( $related_artists ) ){
                $button = array(
                  "hook" => "open_artist",
                  "icon" => "open-in-app",
                  "url" => $artist["url"]
                );
                return $button;
              }

              $button = array(
                "hook" => "open_artist",
                "icon" => "open-in-app",
                "childs" => array(
                  array(
                    "title" => $artist["name_styled"],
                    "icon" => "open-in-app",
                    "url" => $artist["url"]
                  )
                )
              );

              foreach( $related_artists as $related_artist ){
                $button["childs"][] = array(
                  "title" => $related_artist["name_styled"],
                  "icon" => "open-in-app",
                  "url" => $related_artist["url"]
                );
              }

              return $button;

            }
          ),
          "visit_tags" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              $tags = !empty( $item["bof_rel_tags"] ) ? $item["bof_rel_tags"] : bof()->object->m_tag->select(["m_album_tags"=>$item["ID"]],["limit"=>20]);
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
              $genres = !empty( $item["bof_rel_genres"] ) ? $item["bof_rel_genres"] : bof()->object->m_genre->select(["m_album_genres"=>$item["ID"]],["limit"=>20]);
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
          "manage" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              if ( bof()->user->get()->ID ? $item["uploader_id"] == bof()->user->get()->ID : false ){

                $button = array(
                  "hook" => "manage",
                  "icon" => "cog-box",
                  "childs" => array(
                    array(
                      "hook" => "edit",
                      "icon" => "lead-pencil",
                      "action" => "item_single_edit",
                      "attr" => "data-hash='{$item["hash"]}' data-item_ot='m_album'",
                    ),
                    array(
                      "hook" => "delete",
                      "icon" => "lead-pencil",
                      "action" => "item_single_edit_delete",
                      "attr" => "data-hash='{$item["hash"]}' data-item_ot='m_album'",
                    )
                  )
                );

              }
              return $button;

            }
          ),
        )
      ),
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $has_genre = null;
    $has_artist = null;
    $as_widget = false;
    $client_single = false;
    extract( $whereArgs );

    $search = false;
    $listing = false;
    $purchase_check = false;
    $match_page = false;
    $muse_source = false;
    $search_terms = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $search_terms ){
      $_eq[ "artist" ] = array(
        "clean" => false
      );
    }

    if ( $search || $listing ){
      $_eq[ "cover" ] = [];
      $_eq[ "artist" ] = array(
        "_eq" => [ "cover" => [] ]
      );
      $_eq["genres"] = array(
        "limit" => 3,
      );
      $_eq["uploader"] = [];
    }

    if ( $muse_source ){
      $_eq["artist"] = [];
    }

    if ( $client_single ){
      $_eq[ "cover" ] = [];
      $_eq[ "bg" ] = [];
      $_eq[ "artist" ] = array(
        "_eq" => [ "cover" => [] ],
        "public" => true,
      );
      $_eq[ "genres" ] = array(
        "limit" => 2,
        "public" => true,
        "order_by" => "s_childs",
        "order" => "DESC"
      );
      $_eq[ "uploader" ] = [
        "public" => true
      ];
    }

    if ( $as_widget ){
      $_eq[ "cover" ] = [];
      $_eq[ "artist" ] = [];
    }

    if ( $match_page ){
      $_eq[ "artist" ] = [ "clean"=>false ];
    }

    if ( $purchase_check ){
      $_eq["artist"] = array(
        "_eq" => array(
          "manager" => array(
            "_eq" => array(
              "manager_role" => array(
                "type" => "artist"
              )
            )
          )
        )
      );
    }

    if ( bof()->music->hide_single_albums() && ( $match_page || $search || $listing || $as_widget ) ){
      $whereArgs = array_merge(
        $whereArgs ? $whereArgs : [],
        array(
          [ "`type`", "!=", "'single'", true ]
        )
      );
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function create( $whereArray, $insertArray, $updateArray=false ){

    if ( bof()->music->is_blacklisted( "album", $whereArray, $this ) )
    return false;

    $create_data = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray, true );
    $db_id = $create_data["ID"];


    return $db_id;

  }
  public function insert( $setArray ){

    $setArray["hash"] = !empty( $setArray["hash"] ) ? $setArray["hash"] : bof()->object->m_album->get_free_hash();
    return bof()->object->_insert( $this, $setArray );

  }
  public function update( $whereArray, $updateArray, $exeRelations=true ){

    if ( !empty( $updateArray["cover_id"] ) ){

      $old_items = $this->_bof_this->select( $whereArray, [ "single" => false, "limit" => false, "clean" => false ] );
      if ( $old_items ){

        $old_items_cover_ids = [];
        foreach( $old_items as $old_item ){
          if ( !empty( $old_item["cover_id"] ) ? !in_array( $old_item["cover_id"], $old_items_cover_ids, true ) : false )
          $old_items_cover_ids[] = $old_item["cover_id"];
        }

        foreach( $old_items as $old_item ){
          $old_item_tracks = bof()->object->m_track->select( [ "album_id" => $old_item["ID"] ], [ "single" => false, "limit" => false, "clean" => false ] );
          if ( $old_item_tracks ){
            foreach( $old_item_tracks as $old_item_track ){

              $changeCover = false;
              if ( empty( $old_item_track["cover_id"] ) ){
                $changeCover = true;
              }
              elseif ( $old_items_cover_ids ? in_array( $old_item_track["cover_id"], $old_items_cover_ids ) : false ){
                $changeCover = true;
              }
              else {
                if ( !( bof()->object->file->sid( $old_item_track["cover_id"] ) ) )
                $changeCover = true;
              }

              if ( !empty( $changeCover ) )
              bof()->object->m_track->update( [ "ID" => $old_item_track["ID"] ], [ "cover_id" => $updateArray["cover_id"] ], false );

            }
          }
        }

      }

    }
    return bof()->object->_update( $this, $whereArray, $updateArray, $exeRelations );

  }
  public function clean( $item, $args ){

    $search = false;
    $_eq = [];
    $purchase_check = false;
    $muse_source = false;
    $match_page = false;
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
      $remote_cover = reset( $_images );
    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["title"],
        "image" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : false
      );
    }

    if ( $match_page ){
      $item = array(
        "title" => $item["title"],
        "type" => $item["type"],
        "artist_name" => !empty( $item["bof_dir_artist"]["name"] ) ? $item["bof_dir_artist"]["name"] : null,
        "seo_url" => $item["seo_url"],
        "cover_id" => $item["cover_id"],
        "remote_cover" => !empty( $remote_cover ) ? $remote_cover : null
      );
    }

    if ( $muse_source ){

      if ( !empty( $item["spotify_id"] ) ? ( bof()->object->db_setting->get( "spotify_automation" ) && php_sapi_name() !== 'cli' ) : false ){
        bof()->spotify_helper->record( true, array(
          "create_album_get_artist_for_genres" => true,
          "create_track_get_artist_for_genres" => true,
          "update_album_get_artist_for_genres" => true
        ) );
        $update = bof()->spotify_helper->get_album( $item["spotify_id"] );
        bof()->spotify_helper->record( false );
      }

      $tracks = bof()->object->m_track->select(
        array(
          "album_id" => $item["ID"]
        ),
        array(
          "order_by" => "album_index",
          "order" => "ASC",
          "limit" => 100,
          "_eq" => [ "sources" => [], "cover" => [] ],
          "muse_source" => true
        )
      );

      if ( $tracks ){
        foreach( $tracks as $track ){
          if ( !empty( $track["sources"] ) ){
            $item["sources"] = empty( $item["sources"] ) ? $track["sources"] : array_merge( $item["sources"], $track["sources"] );
          }
        }
      }

    }

    if ( $purchase_check && !empty( $item["bof_dir_artist"]["bof_dir_manager"] ) ){
      $item["manager"] = $item["bof_dir_artist"]["bof_dir_manager"];
    }

    return $item;

  }
  public function clean_client_single( $item, $args ){

    if ( !empty( $item["spotify_id"] ) ? ( bof()->object->db_setting->get( "spotify_automation" ) && php_sapi_name() !== 'cli'  ) : false ){
      bof()->spotify_helper->record( true, array(
        "create_album_get_artist_for_genres" => true,
        "create_track_get_artist_for_genres" => true,
        "update_album_get_artist_for_genres" => true
      ) );
      $update = bof()->spotify_helper->get_album( $item["spotify_id"] );
      bof()->spotify_helper->record( false );
    }

    $item["head_play_title"] = $item["title"];

    $item["liked"] = false;
    if ( bof()->user->get()->ID ){
      $item["liked"] = bof()->object->ugc_property->select(
        array(
          "user_id" => bof()->user->get()->ID,
          "type" => "like",
          "object_name" => "m_album",
          "object_id" => $item["ID"]
        )
      ) ? true : false;
    }

    if ( !empty( $item["description_html"] ) ){
      $widgets["description"] = array(
        "ID" => "desc",
        "display" => array(
          "classes" => [ "desc" ],
          "type" => "html",
          "title" => bof()->object->language->turn("description",[],["uc_first"=>true,"lang"=>"users"]),
          "html" => $item["description_html"]
        )
      );
    }

    $widgets["albums_tracks"] = $this->_bof_this->clean_client_single_widget( $item, "albums_tracks" );
    $item = array(
      "widgets" => $widgets,
      "data" => $item
    );

    return $item;

  }
  public function clean_as_widget( $item, $args ){
    return array(
      "title" => $item["title"],
      "sub_data" => $item["bof_dir_artist"]["name"],
      "sub_link" => $item["bof_dir_artist"]["url"],
      "cover" =>  !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"] : null,
      "cover_url" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : null,
      "raw" => $item
    );
  }
  public function clean_client_single_widget( $item, $widget_name, $args=[], $caller="self" ){

    if ( $widget_name == "albums_tracks" ){
      $tracks = bof()->object->m_track->select(
        array(
          "album_id" => $item["ID"]
        ),
        array(
          "as_widget" => true,
          "as_album_widget" => true,
          "order_by" => "album_index",
          "order" => "ASC",
          "limit" => 100,
          "_eq" => [ "artist" => [], "cover" => [] ]
        )
      );
      if ( $tracks ){
        foreach( $tracks as &$track ){
          if ( !empty( $track["raw"]["bof_rel_ft_artists"] ) ){
            $track["sub_data"] .= "</a>";
            foreach( $track["raw"]["bof_rel_ft_artists"] as $__i => $ft_artist ){
              $track["sub_data"] .= "<span class='dot'>.</span><a href='{$ft_artist["url"]}'>" . $ft_artist["name"];
              if ( $__i < count( $track["raw"]["bof_rel_ft_artists"] ) - 1 )
              $track["sub_data"] .= "</a>";
            }
          }
          $track["buttons"] = bof()->bofClient->__parse_item_buttons( "m_track", bof()->object->m_track, $track["raw"], bof()->object->m_track->bof_client()["buttons"] );
          $track["buttons"]["play"]["AsAction"] = true;
        }
      }
    }

    $widgets = array(
      "albums_tracks" => array(
        "ID" => "albums_tracks",
        "display" => array(
          "type" => "table",
          "title" => "",
          "link" => false,
          "pagination" => false,
          "table_columns" => array(
            "duration_hr"
          ),
          "table_labels" => array(
            [ "val" => "Duration", "class" => "duration" ]
          ),
          "table_hide_cover" => true,
          "table_count" => true
        ),
        "object" => array(
          "name" => "m_track",
          "whereArray" => array(
            "album_id" => $item["ID"]
          ),
          "selectArray" => array(
            "order_by" => "album_index",
            "order" => "ASC"
          )
        ),
        "items" => !empty( $tracks ) ? $tracks : []
      )
    );

    return !empty( $widgets[ $widget_name ] ) ? $widgets[ $widget_name ] : null;

  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["title"] => 1,
    );

    if ( !empty( $item["bof_dir_artist"]["name"] ) )
    $o[ $item["bof_dir_artist"]["name"] ] = 0.17;

    return $o;

  }
  public function search( $args ){

    $query = null;
    extract( $args );

    if ( !$query )
    throw new Exception( "invalid_query" );

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

      $search_spotify = bof()->spotify_helper->search( $query, "album" );
      if ( $search_spotify ){
        foreach( $search_spotify as &$si )
        $si["sub_data"] = $si["artists"][0]["name"];
      }
      $search = bof()->spotify_helper->merge_results( $search, $search_spotify, "album" );

    }

    return $search;

  }

  public function playlisting( $item ){

    return array(
      "m_track",
      bof()->object->m_track->select(
        array(
          "album_id" => $item["ID"]
        ),
        array(
          "limit" => 100
        )
      )
    );

  }
  public function get_sources( $item, $args=[] ){

    $sources = [];
    $tracks = bof()->object->m_track->select(
      array(
        "album_id" => $item["ID"]
      ),
      array(
        "order_by" => "album_index",
        "order" => "ASC",
        "limit" => 100,
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

  public function get_sources_data( $item, $args=[] ){

    $data = array(
      "ID" => null,
      "cover" => !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"]["image_thumb"] : null,
      "back" => !empty( $item["bof_file_cover"] ) ? reset( $item["bof_file_cover"]["image_strings"]["_raw"] ) : null,
      "title" => $item["title"],
      "link" => $item["url"],
      "sub_title" => $item["bof_dir_artist"]["name"],
      "sub_link" => $item["bof_dir_artist"]["url"],
      "duration" => !empty( $item["duration"] ) ? $item["duration"] : null,
      "buttons" => bof()->bofClient->__parse_item_buttons( "m_track", $this->_bof_this, $item, $this->_bof_this->bof_client()["buttons"] ),
      "ot" => "m_track",
      "hash" => $item["hash"],
      "lyrics" => false,
      "preview" => array(
        "type" => "image",
        "image" => !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"]["image_strings"][1]["html"] : null
      )
    );
    return $data;

  }
  public function download_child( $button, $item, $args ){

    $tracks = bof()->object->m_track->select(
      array(
        "album_id" => $item["ID"]
      ),
      array(
        "muse_source" => true,
        "order_by" => "album_index",
        "order" => "ASC",
        "_eq" => array(
          "sources" => array(
            "for_download" => true
          ),
        ),
        "limit" => false,
        "single" => false
      )
    );

    if ( $tracks ){
      $tracks_dir_sources = [];
      foreach( $tracks as $track ){

        if ( !empty( $track["bof_dir_sources"] ) ){

          usort( $track["bof_dir_sources"], function($a, $b) {
            return $a['download_able'] < $b['download_able'] ? 1 : 0;
          });

          $_source = reset( $track["bof_dir_sources"] );
          $_source["real_ot"] = "m_track";
          $_source["real_oh"] = $track["hash"];
          $tracks_dir_sources = array_merge( $tracks_dir_sources, [ $_source ] );
        }

      }
      $item["bof_dir_sources"] = $tracks_dir_sources;
    }

    return $item;

  }
  public function check_role_access( $item, $premium_access ){

    $m_artist = null;
    $m_genre = null;
    $m_tag = null;
    extract( $premium_access );

    if ( $m_artist ){

      if ( in_array( $item["artist_id"], $m_artist, true ) || in_array( $item["album_artist_id"], $m_artist, true ) )
      return true;

    }
    if ( $m_tag ){

      $get_item_tags = bof()->object->m_tag->select(
        array(
          "m_album_tags" => $item["ID"]
        ),
        array(
          "limit" => false,
          "single" => false
        )
      );

      if ( $get_item_tags ){
        foreach( $get_item_tags as $item_tag ){
          if ( in_array( $item_tag["ID"], $m_tag ) )
          return true;
        }
      }

    }
    if ( $m_genre ){

      $get_item_genres = bof()->object->m_genre->select(
        array(
          "m_album_genres" => $item["ID"]
        ),
        array(
          "limit" => false,
          "single" => false
        )
      );

      if ( $get_item_genres ){
        foreach( $get_item_genres as $item_genre ){
          if ( in_array( $item_genre["ID"], $m_genre ) )
          return true;
        }
      }

    }

    return false;

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
      "album_id" => $item["ID"]
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
