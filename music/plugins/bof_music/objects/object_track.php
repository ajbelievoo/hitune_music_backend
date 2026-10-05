<?php

if ( !defined( "bof_root" ) ) die;

class object_m_track extends bof_type_object {

  public function bof(){
    return array(
      "name" => "m_track",
      "label" => "Music Track",
      "icon" => "music_note",
      "db_table_name" => "_c_m_tracks",
      "db_rel_table_name" => "_c_m_tracks_relations",
      "db_rel_table_col_name" => "track_id",
      "widgetable" => true,
      "browsable" => true,
      "blacklistable" => true,
      "fulltext_search" => true
    );
  }
  public function columns(){
    return array(

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
      "duration" => array(
        "public" => true,
        "label" => "Duration",
        "tip" => "Duration of track in <b>seconds</b>",
        "validator" => array(
          "int",
          array(
            "empty()"
          ),
        ),
        "input" => array(
          "type" => "digit"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true,
            "multi" => true,
          )
        ),
      ),
      "explicit" => array(
        "public" => true,
        "label" => "Explicit",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true,
          ),
        ),
        "input" => array(
          "type" => "checkbox"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "multi" => true
          )
        ),
      ),
      "uploader_id" => array(
        "label" => "Uploader",
        "tip" => "Users can become uploader of a track by uploading it for the first time",
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
              "child_object" => "m_track",
              "child_object_selector_column" => "uploader_id",
              "limit" => 1
            ),
          ),
          "ugc_uploader" => array(
  	        "exec" => array(
  	          "type" => "direct",
  	          "parent_object" => "m_track",
  	          "child_object" => "ugc_property",
  	          "child_object_selector_column" => "object_id",
  	          "child_object_where_array" => array(
  	            "type" => "upload",
  	            [ "object_name", "=", "m_track" ]
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
            "multi" => true
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
              "parent_object_stats_column" => "s_tracks",
              "child_object" => "m_track",
              "child_object_selector_column" => "artist_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "album_id" => array(
        "label" => "Album",
        "bofInput" => array(
          "object",
          array(
            "type" => "m_album"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){

              $displayData["data"] = $item["bof_dir_album"]["title"];
              $displayData["data"] .= "<span class='sub'>" . $item["bof_dir_album"]["bof_dir_artist"]["name_styled"] . "</span>";
              if ( !empty( $item["bof_dir_album"]["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_dir_album"]["bof_file_cover"]["image_thumb"];
              return $displayData;

            },
          ),
          "object" => array(
            "required" => true,
            "multi" => true,
          ),
          "filters" => array(
            "col_album" => array(
              "title" => "Album(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_album",
                  "multi" => true,
                )
              )
            ),
          )
        ),
        "bofClient" => array(
          "filters" => array(
            "col_album" => "_bofAdmin"
          )
        ),
        "selectors" => array(
          "album_id" => [ "album_id", "=" ],
          "col_album" => [ "album_id", "by_column" ],
        ),
        "relations" => array(
          "album" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "m_album",
              "parent_object_stats_column" => "s_tracks",
              "child_object" => "m_track",
              "child_object_selector_column" => "album_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "album_index" => array(
        "public" => true,
        "label" => "Track # in album",
        "input" => array(
          "type" => "digit",
        ),
        "validator" => array(
          "int",
          array(
            "empty()",
            "forceZero" => true,
            "min" => 0,
          ),
        ),
        "bofAdmin" => array(
          "object" => []
        ),
      ),
      "album_cd" => array(
        "public" => true,
        "label" => "Track's CD #",
        "tip" => "In case this track's album have more than 1 disc, which disc is this track on",
        "input" => array(
          "type" => "digit"
        ),
        "validator" => array(
          "int",
          array(
            "empty()",
            "forceZero" => true,
            "min" => 0,
          ),
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => true
          )
        ),
      ),
      "album_artist_id" => array(
        "bofAdmin" => array(
          "filters" => array(
            "col_album_artist" => array(
              "title" => "Album Artist(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_artist",
                  "multi" => true,
                )
              )
            ),
          )
        ),
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "selectors" => array(
          "col_album_artist" => [ "album_artist_id", "by_column" ],
        ),
      ),
      "album_price" => array(
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "selectors" => array(
          "album_price" => [ "album_price", "=" ],
          "has_album_price" => function( $val ){

            if ( $val > 0 )
            return [ "album_price", ">", "0" ];

            return array(
              "oper" => "OR",
              "cond" => array(
                [ "album_price", "=", "" ],
                [ "album_price", "=", "0" ],
                [ "album_price", null, null, true ]
              )
            );

          },
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
            return array(
              "oper" => "AND",
              "cond" => array(
                [ "spotify_id", "!=", "" ],
                [ "spotify_id", "NOT", null, true ]
              )
            );

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
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true
        )
      ),
      /*"musixmatch_id" => array(
        "label" => "Musixmatch ID",
        "tip" => "Can be used for better indexing and automation",
        "input" => array(
          "type" => "text",
        ),
        "validator" => array(
          "int",
          array(
            "empty()",
            "forceNull" => true
          ),
        ),
        "bofAdmin" => array(
          "object" => [],
          "filters" => array(
            "has_musixmatch_id" => array(
              "title" => "Has Musixmatch ID",
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
          "musixmatch_id" => [ "musixmatch_id", "=" ],
          "has_musixmatch_id" => function( $val ){

            if ( $val > 0 )
            return array(
              "oper" => "AND",
              "cond" => array(
                [ "musixmatch_id", "!=", "" ],
                [ "musixmatch_id", "NOT", null, true ]
              )
            );

            return array(
              "oper" => "OR",
              "cond" => array(
                [ "musixmatch_id", "=", "" ],
                [ "musixmatch_id", null, null, true ]
              )
            );

          },
        ),
      ),*/

      "lyrics" => array(
        "label" => "Lyrics",
        "validator" => array(
          "string",
          array(
            "empty()",
            "strip_emoji" => false,
            "allow_eol" => true
          ),
        ),
        "input" => array(
          "type" => "textarea"
        ),
        "bofAdmin" => array(
          "object" => array(
            "group" => "lyrics"
          )
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

      "_david" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true,
          ),
        ),
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

        "label" => "Release time",
        "public" => true,
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
        "input" => array(
          "type" => "time",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "multi" => true
          ),
          "filters" => array(
            "release_year_range" => array(
              "title" => "Release time",
              "tip" => "Release time in form of <b>min_year-max_year</b> for example: 1960-2022",
              "input" => array(
                "type" => "text",
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
      /*"time_musixmatch" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
        "selectors" => array(
          "musixmatch_scraped_range" => [ "time_spotify", "timestamp_dyna_range" ],
        ),
      ),*/

    );
  }
  public function stats_columns(){
    return array(
      "views",
      "views_unique",
      "plays",
      "plays_unique",
      "likes",
      "popularity",
      "muse_report",
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
          "title" => "title",
          "duration" => "duration",
          "album_title" => "album title",
          "artist_name" => "artist name",
        )
      ),
      "cover",
      "translations" => array(
        "title"
      ),
      "price" => array(
        "parent" => "album_id",
        "parent_name" => "album"
      ),
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
      "rel_genre" => function( $val ){
        $val = is_array( $val ) ? implode( ",", $val ) : $val;
        if ( bof()->object->m_genre->genre_hiearchy() )
        return [ "ID", "IN", "SELECT track_id FROM _c_m_tracks_relations WHERE type = 'genre' AND target_id IN ( SELECT genre_id FROM _c_m_genres_hiearchy WHERE hook_id IN ( {$val} ) )", true ];
        else
        return [ "ID", "IN", "SELECT track_id FROM _c_m_tracks_relations WHERE type = 'genre' AND target_id IN ( {$val} )", true ];
      },
      "rel_tag" => [ "ID", "parent_with_relations", "rel_parent" => "m_track", "hub_type" => "tag" ],
      "rel_artist" => [ "ID", "parent_with_relations", "rel_parent" => "m_track", "hub_type" => "ft_artist" ],
      "in_playlist" =>function( $val ){

        if ( !$val )
        return;

        $val = is_array( $val ) ? implode( ",", $val ) : $val;
        return [ "ID", "IN", "SELECT object_id FROM `_u_properties` WHERE type = 'playlist' AND related_object_name = 'ugc_playlist' AND related_object_id IN ( {$val} ) ", true ];

      }
    );
  }
  public function relations(){
    return array(
      "genres" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_track_genres" => array(
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
                  "multi" => true
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
        "exec" => array(
          "type" => "hub",
          "hub_type" => "genre",
          "parent_object" => "m_track",
          "child_object" => "m_genre",
          "child_object_stats_column" => "s_tracks"
        ),
      ),
      "tags" => array(

        "bofAdmin" => array(

          "objects" => array(

            "m_track_tags" => array(
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
                  "multi" => true
                )
              ),
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
          "rel_tag" => [ "ID", "parent_with_relations", "rel_parent" => "m_track", "hub_type" => "tag" ],
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "tag",
          "parent_object" => "m_track",
          "child_object" => "m_tag",
          "child_object_stats_column" => "s_tracks"
        ),

      ),
      "langs" => array(

        "bofAdmin" => array(

          "objects" => array(

            "m_track_langs" => array(
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
                  "multi" => true
                )
              ),
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
            "rel_lang" => "_bofAdmin"
          )
        ),

        "selectors" => array(
          "rel_lang" => [ "ID", "parent_with_relations", "rel_parent" => "m_track", "hub_type" => "lang" ],
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "lang",
          "parent_object" => "m_track",
          "child_object" => "m_lang",
          "child_object_stats_column" => "s_tracks"
        ),

      ),
      "ft_artists" => array(

        "bofAdmin" => array(
          "objects" => array(

            "m_track_ft_artists" => array(
              "label" => "Featured Artist(s)",
              "column_name" => "ft_artist_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_artist",
                  "multi" => true
                ),
              ),
            ),

          ),
          "filters" => array(
            "rel_artist" => array(
              "title" => "Featured Artist(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "m_artist",
                  "multi" => true,
                  "autoload" => false
                )
              )
            ),
          ),
        ),
        "bofClient" => array(
          "filters" => array(
            "rel_artist" => "_bofAdmin"
          )
        ),

        "exec" => array(
          "type" => "hub",
          "hub_type" => "ft_artist",
          "parent_object" => "m_track",
          "child_object" => "m_artist",
        ),

      ),
      "sources" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_track",
          "child_object" => "m_track_source",
          "child_object_selector_column" => "track_id",
          "delete_child_too" => true,
          "limit" => 100
        ),
      ),
      "likers" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_track",
          "parent_object_stats_column" => "s_likes",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "like",
            [ "object_name", "=", "m_track" ]
          ),
          "delete_child_too" => true
        ),
      ),
      "uploader_p" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "m_track",
          "child_object" => "ugc_property",
          "child_object_selector_column" => "object_id",
          "child_object_where_array" => array(
            "type" => "upload",
            [ "object_name", "=", "m_track" ]
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
        "edit_page_url" => "music_track",
        "list_page_url" => "music_tracks",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true,
        ),
      ),
      "filters" => array(
        "in_playlist" => array(
          "title" => "In playlist",
          "bofInput" => array(
            "object",
            array(
              "type" => "ugc_playlist",
            )
          )
        ),
      ),
      "list" => array(
        "title" => null,
        "album_id" => null,
        "stats" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Stats",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            if( $item["price"] )
            $displayData["data"] .= "<li><b>Sales</b>" . ($item["s_sales"]?number_format($item["s_sales"]):"-") . "</li>";
            $displayData["data"] .= "<li><b>Plays</b>" . ($item["s_plays"]?number_format($item["s_plays"]):"-") . "</li>";
            $displayData["data"] .= "<li><b>Unique Plays</b>" . ($item["s_plays_unique"]?number_format($item["s_plays_unique"]):"-") . "</li>";
            $displayData["data"] .= "<li><b>Views</b>" . number_format($item["s_views"]) . "</li>";
            $displayData["data"] .= "<li><b>Unique Views</b>" . number_format($item["s_views_unique"]) . "</li>";
            $displayData["data"] .= "<li><b>Likes</b>" . number_format($item["s_likes"]) . "</li>";
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "genres" => null,
        "price" => null
      ),
      "object" => array(
        "title" => null,
        "album_id" => null,
        "artist_id" => null,
        "duration" => null,
        "cover_id" => null,
        "bg_id" => null,
        "album_cd" => null,
        "album_index" => null,
        "m_track_genres" => null,
        "m_track_tags" => null,
        "m_track_ft_artists" => null,
        "uploader_id" => null,
      ),
      "object_groups" => array(
        [ "price", "Price" ],
        [ "description", "Description" ],
        [ "lyrics", "Lyrics" ],
      ),
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $item_name == "artist_id" && empty( $item_data["input"]["value"] ) && $request["type"] == "new" ){
          $givenID = bof()->nest->user_input( "get", "artist_id", "int" );
          if ( $givenID ) $item_data["input"]["value"] = $givenID;
        }
        if ( $item_name == "album_id" && empty( $item_data["input"]["value"] ) && $request["type"] == "new" ){
          $givenID = bof()->nest->user_input( "get", "album_id", "int" );
          if ( $givenID ) $item_data["input"]["value"] = $givenID;
        }

      },
      "buttons_renderer" => function( $item, $buttons ){

        $buttons["list_sources"] = array(
          "label" => "List sources",
          "link" => "music_sources?col_track={$item["ID"]}"
        );

        $buttons["add_source"] = array(
          "label" => "Add source",
          "link" => "music_source/__new?target_id={$item["ID"]}"
        );

        return $buttons;

      },
    );
  }
  public function bof_client(){
    return array(
      "public_browse" => true,
      "single_url_prefix" => "music/track",
      "list_url" => "tracks",
      "buttons" => array(
        "link" => true,
        "share" => true,
        "purchase" => true,
        "play" => true,
        "playlist" => true,
        "like" => true,
        "source" => true,
        "purchase" => true,
        "download" => true,
        "extra_after" => array(
          "visit_album" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $album = !empty( $item["bof_dir_album"] ) ? $item["bof_dir_album"] : bof()->object->m_album->select(["ID"=>$item["album_id"]]);

              if ( bof()->music->hide_single_albums() && $album["type"] == "single" )
              return false;

              $button = array(
                "hook" => "open_album",
                "icon" => "open-in-app",
                "url" => $album["url"]
              );

              return $button;

            }
          ),
          "visit_artist" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $artist = bof()->object->m_artist->select(["ID"=>$item["artist_id"]]);
              $related_artists = bof()->object->m_artist->select(["m_track_ft_artists"=>$item["ID"]],["limit"=>20]);

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
              $tags = !empty( $item["bof_rel_tags"] ) ? $item["bof_rel_tags"] : bof()->object->m_tag->select(["m_track_tags"=>$item["ID"]],["limit"=>20]);
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
              $genres = !empty( $item["bof_rel_genres"] ) ? $item["bof_rel_genres"] : bof()->object->m_genre->select(["m_track_genres"=>$item["ID"]],["limit"=>20]);
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
                      "attr" => "data-hash='{$item["hash"]}' data-item_ot='m_track'",
                    ),
                    array(
                      "hook" => "delete",
                      "icon" => "lead-pencil",
                      "action" => "item_single_edit_delete",
                      "attr" => "data-hash='{$item["hash"]}' data-item_ot='m_track'",
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
    $has_album = null;
    $purchase_check = false;
    extract( $whereArgs );

    $search = false;
    $listing = false;
    $client_single = false;
    $client_widget = false;
    $as_widget = false;
    $as_album_widget = false;
    $as_topTrack_widget = false;
    $muse_infinite_related = false;
    $muse_source = false;
    $match_page = false;
    $search_terms = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $search_terms ){
      $_eq[ "artist" ] = [ "clean" => false ];
      $_eq[ "album" ] = [ "clean" => false ];
    }
    if ( $listing || $client_single ){
      $_eq[ "cover" ] = [];
      $_eq[ "album" ] = array(
        "_eq" => [ "cover" => [], "artist" => [] ]
      );
      $_eq[ "genres" ] = [
      ];
      $_eq[ "uploader" ] = [];
    }
    if ( $client_single ){
      $_eq[ "cover" ] = [];
      $_eq[ "bg" ] = [];
      $_eq[ "album" ] = array(
        "_eq" => [ "cover" => [] ],
        "public" => true
      );
      $_eq[ "artist" ] = array(
        "_eq" => [ "cover" => [] ],
        "public" => true
      );
      $_eq[ "genres" ] = array(
        "public" => true,
        "order_by" => "s_childs",
        "order" => "DESC"
      );
      $_eq[ "ft_artists" ] = array();
    }
    if ( $as_widget && !$as_album_widget &&!$as_topTrack_widget ){
      $_eq[ "cover" ] = [];
      $_eq[ "artist"] = [];
    }
    if ( $as_album_widget ){
      $_eq[ "artist" ] = [];
      $_eq[ "ft_artists" ] = [];
    }
    if ( $as_topTrack_widget ){
      $_eq[ "cover"] = [];
      $_eq[ "album" ] = [];
    }
    if ( $muse_source ){
      $_eq[ "artist" ] = [];
    }
    if ( $client_widget ){
      $_eq[ "album" ] = array();
      $_eq[ "artist" ] = array();
      $_eq[ "ft_artists" ] = [];
    }
    if ( $muse_infinite_related ){
      $_eq["cover" ] = [];
      $_eq["artist"] = array(
        "_eq" => array(
          "genres" => array(
            "limit" => 10
          )
        )
      );
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
    if ( $match_page ){
      $_eq["artist"] = [ "clean" => false ];
      $_eq["album"] = [ "clean" => false ];
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function create( $whereArray, $insertArray, $updateArray=false, $exeRelations=true ){

    if ( bof()->music->is_blacklisted( "track", $whereArray, $this ) )
    return false;

    $db_id = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray, false, $exeRelations );

    if ( $db_id && !empty( $insertArray["time_release"] ) && ( !empty( $insertArray["artist_id"] ) || !empty( $insertArray["ft_artist_ids"] ) ) ){
      $effected_artists = !empty( $insertArray["artist_id"] ) ? [ $insertArray["artist_id"] ] : [];
      if ( !empty( $insertArray["ft_artist_ids"] ) ){
        foreach( explode( ",", $insertArray["ft_artist_ids"] ) as $_ft_artist_id ){
          $effected_artists[] = $_ft_artist_id;
        }
      }
      if ( $effected_artists ){
        foreach( $effected_artists as $_effected_artist_id )
        bof()->object->m_artist->check_time_release( $_effected_artist_id, $insertArray["time_release"] );
      }
    }

    return $db_id;

  }
  public function insert( $setArray, $exeRelations=true ){

    $setArray["hash"] = !empty( $setArray["hash"] ) ? $setArray["hash"] : bof()->object->m_track->get_free_hash();
    return bof()->object->_insert( $this, $setArray, $exeRelations );

  }
  public function clean( $item, $args ){

    $search = false;
    $in_album_page = false;
    $thumb_as_cover = false;
    $purchase_check = false;
    $muse_source = false;
    $match_page = false;
    $_eq = [];
    extract( $args );

    $item["duration_hr"] = bof()->general->duration_hr( $item["duration"] )["string"];

    if ( isset( $item["lufs"] ) && $item["lufs"] !== null )
    $item["loudness"] = array(
      "lufs" => (float) $item["lufs"],
      "peak_db" => isset( $item["peak_db"] ) && $item["peak_db"] !== null ? (float) $item["peak_db"] : null
    );

    if ( in_array( "cover", array_keys( $_eq ), true ) && empty( $item["bof_file_cover"] ) && !empty( $item["spotify_cover_decoded"] ) && client_auto_images && !david ){
      foreach( $item["spotify_cover_decoded"] as $_s_cover )
      $_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
      $_images = array_keys( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_strings"] = bof()->image->html( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_thumb"] = end( $_images );
      $item["bof_file_cover"]["image_original"] = reset( $_images );
    }

    if ( $match_page && empty( $item["cover_id"] ) && !empty( $item["spotify_cover_decoded"] ) && client_auto_images ){
      foreach( $item["spotify_cover_decoded"] as $_s_cover )
      $_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
      $_images = array_keys( $_spotify_covers_raw );
      $remote_cover = reset( $_images );
    }

    if ( in_array( "cover", array_keys( $_eq ), true ) && empty( $item["bof_file_cover"] ) && !empty( $item["_david_decoded"] ) && david ? !empty( $item["_david_decoded"]["data"]["snippet"]["thumbnails"] ) : false ){

      foreach( $item["_david_decoded"]["data"]["snippet"]["thumbnails"] as $_y_cover )
      $_youtube_covers_raw[ $_y_cover["url"] ] = [ $_y_cover["width"], $_y_cover["height"] ];
      $_images = array_keys( $_youtube_covers_raw );
      $item["bof_file_cover"]["image_strings"] = bof()->image->html( $_youtube_covers_raw );
      $item["bof_file_cover"]["image_thumb"] = end( $_images );
      $item["bof_file_cover"]["image_original"] = reset( $_images );

    }

    if ( in_array( "cover", array_keys( $_eq ), true ) && empty( $item["bof_file_cover"] ) && !empty( $item["_david_decoded"] ) && david ? !empty( $item["_david_decoded"]["data"]["images"] ) : false ){

      foreach( $item["_david_decoded"]["data"]["images"] as $_y_cover )
      $_youtube_covers_raw[ $_y_cover["url"] ] = [ $_y_cover["width"], $_y_cover["height"] ];
      $_images = array_keys( $_youtube_covers_raw );
      $item["bof_file_cover"]["image_strings"] = bof()->image->html( $_youtube_covers_raw );
      $item["bof_file_cover"]["image_thumb"] = end( $_images );
      $item["bof_file_cover"]["image_original"] = reset( $_images );

    }

    if ( !empty( $item["bof_file_cover"] ) && $thumb_as_cover ){
      $item["bof_file_cover"] = $item["bof_file_cover"]["image_thumb"];
    }

    if ( $purchase_check && !empty( $item["bof_dir_artist"]["bof_dir_manager"] ) ){
      $item["manager"] = $item["bof_dir_artist"]["bof_dir_manager"];
    }

    $item["premium"] = !empty( $item["price"] ) || ( $item["album_price"] && empty( $item["price_setting_decoded"]["disable_parent"] ) );

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["title"],
        "image" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : false
      );
    }
    if ( $muse_source ){

      $item["sources"] = array(
        $item["hash"] => array(
          "ot" => "m_track",
          "sources" => $item["bof_dir_sources"],
          "data" => $this->_bof_this->get_sources_data( $item ),
          "raw" => $item
        )
      );

    }
    if ( $match_page ){
      $item = array(
        "title" => $item["title"],
        "code" => $item["code"],
        "duration" => $item["duration"],
        "album_title" => !empty( $item["bof_dir_album"]["title"] ) ? $item["bof_dir_album"]["title"] : null,
        "artist_name" => !empty( $item["bof_dir_artist"]["name"] ) ? $item["bof_dir_artist"]["name"] : null,
        "seo_url" => $item["seo_url"],
        "cover_id" => $item["cover_id"],
        "remote_cover" => !empty( $remote_cover ) ? $remote_cover : null
      );
    }

    return $item;

  }
  public function clean_as_widget( $item, $args ){

    return array(
      "title" => $item["title"],
      "sub_data" => !empty( $item["bof_dir_artist"]["name"] ) ? $item["bof_dir_artist"]["name"] : null,
      "sub_link" => !empty( $item["bof_dir_artist"]["url"] ) ? $item["bof_dir_artist"]["url"] : null,
      "cover" =>  !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"] : null,
      "cover_url" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : null,
      "ot" => "m_track",
      "raw" => $item
    );

  }
  public function clean_client_single( $item, $args ){

    $widgets = [];
    $page = [];

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

    $showAlbumWidget = true;

    if ( bof()->music->hide_single_albums() ? ( !empty( $item["bof_dir_album"] ) ? $item["bof_dir_album"]["type"] == "single" : false ) : false )
    $showAlbumWidget = false;

    if ( $showAlbumWidget )
    $widgets["track_album"] = $this->_bof_this->clean_client_single_widget( $item, "track_album", [], "self" );
    $widgets["track_artist"] = $this->_bof_this->clean_client_single_widget( $item, "track_artist", [], "self" );

    if ( !empty( $item["bof_rel_ft_artists"] ) ){
      foreach( $item["bof_rel_ft_artists"] as $i => &$ft_artist ){
        $widgets["track_ft{$i}_artist"] = $this->_bof_this->clean_client_single_widget( $item, "track_ft{$i}_artist", [], "self" );
      }
    }

    $item["liked"] = false;
    if ( bof()->user->get()->ID ){
      $item["liked"] = bof()->object->ugc_property->select(
        array(
          "user_id" => bof()->user->get()->ID,
          "type" => "like",
          "object_name" => "m_track",
          "object_id" => $item["ID"]
        )
      ) ? true : false;
    }

    if ( client_give_attribute && !empty( $item["_david_decoded"]["ID"] ) ){
      $item["copyright"] = "We don't host this video or are affiliated with the uploader in anyway. Click to visit <a target='_blank' href='https://youtube.com/watch?v={$item["_david_decoded"]["ID"]}'>".(!empty($item["_david_decoded"]["data"]["snippet"]["channelTitle"])?$item["_david_decoded"]["data"]["snippet"]["channelTitle"]:$item["_david_decoded"]["data"]["channel_title"])."'s Youtube channel <span class='mdi mdi-open-in-new'></span></a>";
    }

    return array(
      "data" => $item,
      "widgets" => $widgets,
      "page" => $page
    );

  }
  public function clean_client_single_widget( $item, $widget_name, $args, $caller="self" ){

    $widgets["track_album"] = array(
      "ID" => "track_album",
      "display" => array(
        "type" => "slider",
        "title" => $item["bof_dir_album"]["title"],
        "sub_data" => bof()->object->language->turn("related_by_album",[],["uc_first"=>true,"lang"=>"users"]),
        "link" => $item["bof_dir_album"]["url"],
        "pagination" => true,
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => false,
      ),
      "object" => array(
        "name" => "m_track",
        "whereArray" => array(
          "col_album" => $item["album_id"],
        ),
        "selectArray" => array(
          "limit" => 10,
          "order_by" => "album_index",
          "order" => "ASC",
        )
      ),
    );
    $widgets["track_artist"] = array(
      "ID" => "track_artist",
      "display" => array(
        "type" => "slider",
        "title" => $item["bof_dir_artist"]["name"],
        "sub_data" => bof()->object->language->turn("related_by_artist",[],["uc_first"=>true,"lang"=>"users"]),
        "link" => $item["bof_dir_artist"]["url"],
        "pagination" => true,
        "slider_size" => "medium",
        "slider_rows" => 1,
        "slider_mason" => false,
      ),
      "object" => array(
        "name" => "m_track",
        "whereArray" => array(
          "col_artist" => $item["artist_id"],
        ),
        "selectArray" => array(
          "limit" => 10,
          "order_by" => "spotify_popularity",
          "order" => "DESC",
        )
      ),
    );

    if ( !empty( $item["bof_rel_ft_artists"] ) ){
      foreach( $item["bof_rel_ft_artists"] as $i => &$ft_artist ){

        $widgets["track_ft{$i}_artist"] = array(
          "ID" => "track_ft{$i}_artist",
          "display" => array(
            "type" => "slider",
            "title" => $ft_artist["name"],
            "sub_data" => bof()->object->language->turn("related_by_ft_artist",[],["uc_first"=>true,"lang"=>"users"]),
            "link" => $ft_artist["url"],
            "pagination" => true,
            "slider_size" => "medium",
            "slider_rows" => 1,
            "slider_mason" => false,
          ),
          "object" => array(
            "name" => "m_track",
            "whereArray" => array(
              "col_artist" => $ft_artist["ID"],
            ),
            "selectArray" => array(
              "limit" => 10,
              "order_by" => "spotify_popularity",
              "order" => "DESC",
            )
          ),
        );

      }
    }

    if ( !empty( $widgets[ $widget_name ] ) )
    return $widgets[ $widget_name ];


  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["title"] => 1,
    );

    if ( !empty( $item["bof_dir_artist"]["name"] ) )
    $o[ $item["bof_dir_artist"]["name"] ] = 0.17;

    if ( !empty( $item["bof_dir_album"]["title"] ) )
    $o[ $item["bof_dir_album"]["title"] ] = 0.19;

    return $o;

  }

  public function get_sources_data( $item, $args=[] ){

    $data = array(
      "ID" => null,
      "cover" => !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"]["image_thumb"] : null,
      "cover_big" => !empty( $item["bof_file_cover"]["image_original"] ) ? $item["bof_file_cover"]["image_original"] : null,
      "back" => !empty( $item["bof_file_cover"]["image_strings"] ) ? reset( $item["bof_file_cover"]["image_strings"]["_raw"] ) : null,
      "title" => $item["title"],
      "link" => $item["url"],
      "sub_title" => $item["bof_dir_artist"]["name"],
      "sub_link" => $item["bof_dir_artist"]["url"],
      "duration" => $item["duration"],
      "youtube_id" => !empty( $item["youtube_id"] ) ? $item["youtube_id"] : null,
      "loudness" => isset( $item["lufs"] ) && $item["lufs"] !== null ? array(
        "lufs" => (float) $item["lufs"],
        "peak_db" => isset( $item["peak_db"] ) && $item["peak_db"] !== null ? (float) $item["peak_db"] : null
      ) : null,
      "buttons" => bof()->bofClient->__parse_item_buttons( "m_track", $this->_bof_this, $item, $this->_bof_this->bof_client()["buttons"] ),
      "ot" => "m_track",
      "hash" => $item["hash"],
      "lyrics" => $item["lyrics"] ? $item["lyrics"] : bof()->lyric->is_automated(),
      "preview" => array(
        "type" => "image",
        "image" => !empty( $item["bof_file_cover"]["image_strings"] ) ? $item["bof_file_cover"]["image_strings"][1]["html"] : null
      )
    );
    return $data;

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
      "artist_id" => $item["artist_id"]
    );

    if ( !empty( $exclude["hash"] ) ){
      $_whereArray[] = array(
        "hash",
        "NOT IN",
        "'".(implode("','",$exclude["hash"]))."'",
        true
      );
    }

    $related_items = $this->_bof_this->select(
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

    if ( !empty( $item["bof_dir_artist"]["bof_rel_genres"] ) ? ( empty( $related_items ) ? true : count( $related_items ) < $per_item ) : false  ){

      $genreIDS = [];
      foreach( $item["bof_dir_artist"]["bof_rel_genres"] as $_genre )
      $genreIDS[] = $_genre["ID"];

      unset( $_whereArray["artist_id"] );
      $_whereArray["rel_genre"] = $genreIDS;

      $related_items_by_genre = $this->_bof_this->select(
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

      if ( $related_items_by_genre )
      $related_items = !empty( $related_items ) ? array_merge( $related_items, $related_items_by_genre ) : $related_items_by_genre;
    }

    if ( empty( $related_items ) ? true : count( $related_items ) < $per_item ){

        $_whereArray = [];
        if ( !empty( $exclude["hash"] ) ){
          $_whereArray[] = array(
            "hash",
            "NOT IN",
            "'".(implode("','",$exclude["hash"]))."'",
            true
          );
        }

        $random = $this->_bof_this->select(
          $_whereArray,
          array(
            "order_by" => " ",
            "order" => "RAND()",
            "empty_select" => true,
            "empty_select()",
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
  
        if ( $random )
        $related_items = !empty( $related_items ) ? array_merge( $related_items, $random ) : $random;

    }

    return $related_items;

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
          "m_track_tags" => $item["ID"]
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
          "m_track_genres" => $item["ID"]
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

}

?>
