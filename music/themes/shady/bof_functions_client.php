<?php

if ( !defined( "bof_root" ) ) die;

$bof->listen( "bofClient", "_os_after", function( $method_args, $method_results, $loader ){

  $object_name = $method_args[0];
  $json = $loader->execute->get_data( "json" );

  $objects = [ "m_album", "m_artist", "a_book", "a_narrator", "a_translator", "a_writer", "p_show", "p_podcaster", "m_track", "m_mix", "p_episode", "r_station",
  "a_genre", "a_language", "a_tag", "m_genre", "m_tag", "p_category", "p_tag", "r_category", "r_country", "r_language", "r_region", "b_post", "b_tag", "b_category",
  "mb_pack", "mb_beat", "mb_producer", "mb_tag", "mb_genre", "mb_mood" ];

  $json["widgets"] = !empty( $json["widgets"] ) ? $json["widgets"] : [];
  $json["widgets"] = array_merge( array(
    array(
      "ID" => uniqid(),
      "display" => array(
        "type" => "ads",
        "title" => null,
        "sub_data" => null,
        "link" => null,
        "pagination" => null,
        "bg_img" => null,
        "slider_size" => null,
        "slider_mason" => false,
        "slider_rows" => null,
        "list_columns" => null,
        "table_columns" => null,
        "table_labels" => null,
        "html" => "<bof_thingie class='size_970x250'>theme_{$object_name}_top</bof_thingie>",
        "classes" => "type_ads no_title no_bg_img"
      ),
      "items" => []
    )
  ), $json["widgets"] );

  if ( !in_array( $object_name, $objects, true ) ) return false;

  $new_stats = [];
  $new_data = [];
  if ( $json ? !empty( $json["data"] ) : false ){

    $data = $json["data"];

    if ( $object_name == "m_album" ){

      $new_data = array(
        "sub_title" => $data["bof_dir_artist"]["name"],
        "sub_cover" => !empty( $data["bof_dir_artist"]["bof_file_cover"] ) ? $data["bof_dir_artist"]["bof_file_cover"]["image_strings"]["12"]["html"] : false,
        "sub_link"  => $data["bof_dir_artist"]["url"],
      );

      if ( !empty( $data["time_release"] ) )
      $new_stats[] = [ "clock-time-two", turn( "release_date" ) . ": <b>" . $data["time_release"] . "</b>", true ];

      if ( !empty( $data["s_likes"] ) ? $data["s_likes"] > 10 : false )
      $new_stats[] = [ "heart-outline", turn("likes") . ": <b>" . number_format( $data["s_likes"] ) . "</b>", true ];

      if ( !empty( $data["s_tracks"] ) )
      $new_stats[] = [ "music-box-multiple-outline", turn( "tracks" ) . ": <b>" . number_format( $data["s_tracks"] ) . "</b>", true ];


    }
    elseif ( $object_name == "mb_pack" ){

      $new_data = array(
        "sub_title" => $data["bof_dir_producer"]["name"],
        "sub_cover" => !empty( $data["bof_dir_producer"]["bof_file_cover"] ) ? $data["bof_dir_producer"]["bof_file_cover"]["image_strings"]["12"]["html"] : false,
        "sub_link"  => $data["bof_dir_producer"]["url"],
      );

      if ( !empty( $data["time_release"] ) )
      $new_stats[] = [ "clock-time-two", turn( "release_date" ) . ": <b>" . $data["time_release"] . "</b>", true ];

      if ( !empty( $data["s_likes"] ) ? $data["s_likes"] > 10 : false )
      $new_stats[] = [ "heart-outline", turn("likes") . ": <b>" . number_format( $data["s_likes"] ) . "</b>", true ];

      if ( !empty( $data["s_tracks"] ) )
      $new_stats[] = [ "music-box-multiple-outline", turn( "tracks" ) . ": <b>" . number_format( $data["s_tracks"] ) . "</b>", true ];


    }
    elseif ( $object_name == "m_artist" ){

      if ( !empty( $data["managed"] ) )
      $new_stats[] = [ "check-decagram", "verified" ];

      if ( !empty( $data["time_release"] ) )
      $new_stats[] = [ "clock-time-two", turn( "last_release" ) . ": <b>" . $data["bof_time_release_hr"] . "</b>", true ];

      if ( !empty( $data["s_subscribers"] ) ? $data["s_subscribers"] > 10 : false )
      $new_stats[] = [ "account-multiple-outline", turn("subscribers") . ": <b>" . number_format( $data["s_subscribers"] ) . "</b>", true ];

      if ( !empty( $data["s_albums"] ) )
      $new_stats[] = [ "album", turn( "albums" ) . ": <b>" . number_format( $data["s_albums"] ) . "</b>", true ];

      if ( empty( $new_stats ) )
      $new_stats[] = [ "clock-time-two", turn( "time_add" ) . ": <b>" . $data["bof_time_add_hr"] . "</b>", true ];

    }
    elseif ( $object_name == "mb_producer" ){

      if ( !empty( $data["managed"] ) )
      $new_stats[] = [ "check-decagram", "verified" ];

      if ( !empty( $data["time_release"] ) )
      $new_stats[] = [ "clock-time-two", turn( "last_release" ) . ": <b>" . $data["bof_time_release_hr"] . "</b>", true ];

      if ( !empty( $data["s_subscribers"] ) ? $data["s_subscribers"] > 10 : false )
      $new_stats[] = [ "account-multiple-outline", turn("subscribers") . ": <b>" . number_format( $data["s_subscribers"] ) . "</b>", true ];

      if ( !empty( $data["s_albums"] ) )
      $new_stats[] = [ "album", turn( "albums" ) . ": <b>" . number_format( $data["s_albums"] ) . "</b>", true ];

      if ( empty( $new_stats ) )
      $new_stats[] = [ "clock-time-two", turn( "time_add" ) . ": <b>" . $data["bof_time_add_hr"] . "</b>", true ];

    }
    elseif ( $object_name == "p_podcaster" ){

      if ( !empty( $data["managed"] ) )
      $new_stats[] = [ "check-decagram", "verified" ];

      if ( !empty( $data["time_release"] ) )
      $new_stats[] = [ "clock-time-two", turn( "last_release" ) . ": <b>" . substr( $data["time_release"], 0, 10 ) . "</b>", true ];

      if ( !empty( $data["s_subscribers"] ) ? $data["s_subscribers"] > 10 : false )
      $new_stats[] = [ "account-multiple-outline", turn("subscribers") . ": <b>" . number_format( $data["s_subscribers"] ) . "</b>", true ];

      if ( !empty( $data["s_shows"] ) )
      $new_stats[] = [ "podcast", turn( "shows" ) . ": <b>" . number_format( $data["s_shows"] ) . "</b>", true ];

      if ( empty( $new_stats ) && !empty( $data["bof_time_add_hr"] ) )
      $new_stats[] = [ "clock-time-two", turn( "time_add" ) . ": <b>" . $data["bof_time_add_hr"] . "</b>", true ];

    }
    elseif ( $object_name == "a_book" ){

      $new_data = array(

        "sub_title" => !empty( $data["bof_rel_writers"][0]["name"] ) ? $data["bof_rel_writers"][0]["name"] : null,
        "sub_cover" => !empty( $data["bof_rel_writers"][0]["bof_file_cover"] ) ? $data["bof_rel_writers"][0]["bof_file_cover"]["image_strings"]["12"]["html"] : false,
        "sub_link"  => !empty( $data["bof_rel_writers"][0]["url"] ) ? $data["bof_rel_writers"][0]["url"] : null,

        "stats" => array(
          array(
            "icon" => "calendar",
            "value" => $data["time_publish"],
          ),
          array(
            "icon" => "heart",
            "value" => $data["s_likes"] . " " . $loader->object->language->turn( "likes", [], [ "lang" => "users" ] ),
          ),
          array(
            "icon" => "play",
            "value" => $data["s_plays"] . " " . $loader->object->language->turn( "plays", [], [ "lang" => "users" ] ),
          ),
        )

      );

    }
    elseif ( in_array( $object_name, [ "a_narrator", "a_translator", "a_writer" ], true ) ){

      $new_data = array(

        "stats" => array(
          array(
            "icon" => "bell",
            "value" => $data["s_subscribers"] . " " . $loader->object->language->turn( "subscribers", [], [ "lang" => "users" ] ),
          ),
          array(
            "icon" => "book",
            "value" => $data["s_books"] . " " . $loader->object->language->turn( "books", [], [ "lang" => "users" ] ),
          ),
        )

      );

    }
    elseif ( $object_name == "p_show" ){

      $new_data = array(
        "sub_title" => $data["bof_dir_podcaster"]["name"],
        "sub_cover" => !empty( $data["bof_dir_podcaster"]["bof_file_cover"] ) ? $data["bof_dir_podcaster"]["bof_file_cover"]["image_strings"]["12"]["html"] : false,
        "sub_link"  => $data["bof_dir_podcaster"]["url"],
      );

      if ( !empty( $data["time_release"] ) )
      $new_stats[] = [ "clock-time-two", turn( "last_release" ) . ": <b>" . substr( $data["time_release"], 0, 10 ) . "</b>", true ];

      if ( !empty( $data["s_likes"] ) ? $data["s_likes"] > 10 : false )
      $new_stats[] = [ "heart-outline", turn("likes") . ": <b>" . number_format( $data["s_likes"] ) . "</b>", true ];

      if ( !empty( $data["s_episodes"] ) )
      $new_stats[] = [ "podcast", turn( "episodes" ) . ": <b>" . number_format( $data["s_episodes"] ) . "</b>", true ];

      if ( empty( $new_stats ) )
      $new_stats[] = [ "clock-time-two", turn( "time_add" ) . ": <b>" . $data["bof_time_add_hr"] . "</b>", true ];

    }
    elseif ( in_array( $object_name, [ "a_genre", "a_language", "a_tag", "m_genre", "m_tag", "p_category", "p_tag", "r_category", "r_country", "r_language", "r_region" ], true ) ){
      $a_tag = true;
    }
    elseif ( $object_name == "m_track" || $object_name == "m_mix" ){

      $new_data[ "sub_title" ] = $data["bof_dir_artist"]["name"];
      $new_data[ "sub_cover" ] = !empty( $data["bof_dir_artist"]["bof_file_cover"] ) ? $data["bof_dir_artist"]["bof_file_cover"]["image_strings"]["12"]["html"] : false;
      $new_data[ "sub_link"  ] = $data["bof_dir_artist"]["url"];

    }
    elseif ( $object_name == "mb_beat" ){

      $new_data[ "sub_title" ] = $data["bof_dir_producer"]["name"];
      $new_data[ "sub_cover" ] = !empty( $data["bof_dir_producer"]["bof_file_cover"] ) ? $data["bof_dir_producer"]["bof_file_cover"]["image_strings"]["12"]["html"] : false;
      $new_data[ "sub_link"  ] = $data["bof_dir_producer"]["url"];

    }
    elseif ( $object_name == "p_episode" ){

      $new_data[ "sub_title" ] = $data["bof_dir_podcaster"]["name"];
      $new_data[ "sub_cover" ] = !empty( $data["bof_dir_podcaster"]["bof_file_cover"] ) ? $data["bof_dir_podcaster"]["bof_file_cover"]["image_strings"]["12"]["html"] : false;
      $new_data[ "sub_link"  ] = $data["bof_dir_podcaster"]["url"];

    }
    elseif ( $object_name == "b_post" ){
      $new_data["content"] = $data["content_html"];
    }

    if ( in_array( $object_name, [ "p_podcaster", "p_show", "p_episode", "r_station" ], true ) ? !empty( $data["bof_rel_categories"] ) : false ){
      $new_data["before_stats"] = [];
      if ( !empty( $data["bof_rel_categories"] ) ){
        foreach( $data["bof_rel_categories"] as $genre )
        $new_data["before_stats"][] = array(
          "name" => $genre["name"],
          "url" => $genre["url"],
        );
      }
    }
    if ( in_array( $object_name, [ "m_album", "m_artist", "m_track", "a_book", "a_narrator", "a_translator", "a_writer", "m_mix", "mb_beat", "mb_producer" ], true ) ? !empty( $data["bof_rel_genres"] ) : false ){
      $new_data["before_stats"] = [];
      if ( !empty( $data["bof_rel_genres"] ) ){
        foreach( $data["bof_rel_genres"] as $genre )
        $new_data["before_stats"][] = array(
          "name" => $genre["name"],
          "url" => $genre["url"],
        );
      }
    }
    if ( in_array( $object_name, [ "m_artist", "a_narrator", "a_translator", "a_writer", "p_creator" ], true ) ){
      $json["page"]["classes"] = !empty( $json["page"]["classes"] ) ? $json["page"]["classes"] . " creator" : "creator";
    }

    if ( in_array( "price", array_keys( $data ), true ) ){

      $new_data["price"] = $data["price"];
      $new_data["price_hr"] = $data["price_hr"];

    }

    $new_data["title"] = isset( $data["title"] ) ? $data["title"] : ( isset( $data["name_styled"] ) ? $data["name_styled"] : $data["name"] );
    foreach( [ "buttons", "hash", "ot", "head_play_title", "liked", "subscribed", "tracks", "albums", "related_artists" ] as $_k ){
      if ( in_array( $_k, array_keys( $data ), true ) ){
        $new_data[ $_k ] = $data[ $_k ];
      }
    }

    $new_data[ "object_name" ] = $loader->object->language->turn( $object_name, [], [ "lang" => "users" ] );
    $new_data[ "background" ] = !empty( $data["bof_file_bg"]["image_strings"] ) ? $data["bof_file_bg"]["image_strings"]["1"]["html"] : false;
    $new_data[ "cover" ] = !empty( $data["bof_file_cover"]["image_strings"] ) ? $data["bof_file_cover"]["image_strings"]["3"]["html"] : false;

    if ( empty( $new_data["background"] ) && !empty( $data["bof_file_cover"]["data_decoded"]["dominant_color"]["rgb"] ) )
    $new_data["background_color"] = $data["bof_file_cover"]["data_decoded"]["dominant_color"]["rgb"];

    if ( !empty( $data["copyright"] ) )
    $new_data["copyright"] = $data["copyright"];

    if ( !empty( $data["redirect"] ) )
    $new_data["redirect"] = $data["redirect"];

    if ( !empty( $new_stats ) ){
      $new_data["stats"] = [];
      foreach( array_slice( $new_stats, 0, 3 ) as $statArray ){
        $new_data["stats"][] = array(
          "icon" => $statArray[0],
          "value" => !empty( $statArray[2] ) ? $statArray[1] : "<b>" . turn( $statArray[1] ) . "</b>"
        );
      }
    }

  }

  $json_old_data = $json["data"];

  if ( !empty( $new_data ) )
  $json["data"] = $new_data;

  if ( !empty( $new_data["cover"] ) )
  $json["page"]["classes"] = !empty( $json["page"]["classes"] ) ? $json["page"]["classes"] . " has_cover" : "has_cover";
  else
  $json["page"]["classes"] = !empty( $json["page"]["classes"] ) ? $json["page"]["classes"] . " no_cover" : "no_cover";

  $__m = [ "args" => $method_args, "results" => $method_results, "raw_data" => $json_old_data ];
  $loader->call( "theme_shady", "set_data", $__m, $json );

  $loader->execute->set_data( "json", $json );

} );

// Music Distribution - Inject custom content
$bof->listen( "bofClient", "_os_after", function( $method_args, $method_results, $loader ){

  $object_name = $method_args[0];
  $json = $loader->execute->get_data( "json" );

  $slug = !empty( $json["data"]["seo_url"] ) ? $json["data"]["seo_url"] : ( !empty( $_GET["slug"] ) ? $_GET["slug"] : false );

  // Distribution lives on distribution.hitune.in - bounce any SPA distribution slug there
  if ( $object_name == "page" && substr( $slug, 0, 12 ) == "distribution" && !empty( $json ) ){

    $sub = "/" . ltrim( substr( $slug, 12 ), "/-" );
    $content = '<div style="padding:80px 20px;text-align:center;opacity:.8">Redirecting to distribution.hitune.in...</div>'
      . '<script>window.location.replace("https://distribution.hitune.in' . $sub . '" + window.location.search + window.location.hash);</script>';
    $json["widgets"] = array( array(
      "ID" => "dist_redirect",
      "display" => array(
        "type" => "html",
        "html" => $content,
        "classes" => "type_html c_o_type_page zero_items no_title no_bg_img dist_redirect_widget"
      ),
      "items" => []
    ) );
    $loader->execute->set_data( "json", $json );
  }

  // Artist verification moved to the Distribution Artist Panel
  if ( $object_name == "page" && $slug == "become_verified" && !empty( $json ) ){

    $dest = "https://distribution.hitune.in/index.php?q=artist-panel#verification";
    $content = '<div style="padding:80px 20px;text-align:center">'
      . '<div style="font-size:20px;font-weight:700;margin-bottom:8px">Artist verification has moved</div>'
      . '<p style="opacity:.75;margin:0 0 22px">Verify on HiTune Music, Spotify and Apple Music and see your analytics in the Artist Panel.</p>'
      . '<a href="' . $dest . '" style="display:inline-block;padding:12px 26px;border-radius:12px;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#fff;font-weight:700;text-decoration:none">Open Artist Panel</a></div>'
      . '<script>window.location.replace("' . $dest . '");</script>';
    $json["widgets"] = array( array(
      "ID" => "artist_panel_redirect",
      "display" => array(
        "type" => "html",
        "html" => $content,
        "classes" => "type_html c_o_type_page zero_items no_title no_bg_img dist_redirect_widget"
      ),
      "items" => []
    ) );
    $loader->execute->set_data( "json", $json );
  }

} );

require_once( dirname(__FILE__) . "/bof_functions_shared.php" );
