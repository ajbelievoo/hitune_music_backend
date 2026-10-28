<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_request_source( $loader, $excuter, $args ){

  bof()->call( "muse", "req_source" );

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "play" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  $solve_raaz = $loader->nest->user_input( "post", "solve", "equal", [ "value" => "true" ] );
  $solve_disabled = $loader->nest->user_input( "post", "solve", "string" ) === "false";

  // Cover fallback helper — imported catalog rows mostly have empty cover_id
  // but a populated spotify_cover JSON on the track and/or its album.
  // Picks the ~300px image (big enough for the player UI, small enough to load fast).
  $_cover_from_spotify = function( $item ){

    $candidates = [];
    if ( !empty( $item["spotify_cover"] ) )
    $candidates[] = $item["spotify_cover"];
    if ( !empty( $item["bof_dir_album"]["spotify_cover"] ) )
    $candidates[] = $item["bof_dir_album"]["spotify_cover"];
    if ( !empty( $item["bof_dir_artist"]["spotify_cover"] ) )
    $candidates[] = $item["bof_dir_artist"]["spotify_cover"];

    foreach( $candidates as $_raw ){
      $_arr = is_string( $_raw ) ? json_decode( $_raw, true ) : $_raw;
      if ( empty( $_arr ) || !is_array( $_arr ) ) continue;
      $_best = null; $_best_w = -1;
      foreach( $_arr as $_im ){
        if ( empty( $_im["url"] ) ) continue;
        $_w = intval( isset( $_im["width"] ) ? $_im["width"] : 0 );
        if ( $_w > $_best_w ){
          $_best_w = $_w;
          $_best = $_im["url"];
        }
      }
      if ( $_best ) return $_best;
    }

    return null;

  };

  if ( $object_name && $object_hash ){

    $the_object = $loader->object->__get( $object_name );
    $object_item = $the_object->select(
      array(
        "hash" => $object_hash
      ),
      array(
        "muse_source" => true,
        "_eq" => array(
          "sources" => array(),
          "cover" => [],
          "album" => [
            "cover" => []
          ]
        )
      )
    );

    $sources_by_type = null;
    $sources = [];

    if ( $object_item ? !empty( $object_item["sources"] ) : false ){

      $sources_gs = $object_item["sources"];

      foreach( $sources_gs as $source_G ){

        $sources_data = $source_G["data"];
        $sources_by_type = $loader->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "stream" );

        if ( $sources_by_type === "pending" ){
          // Continue to force source resolution instead of failing with pending error
          // $loader->api->set_error( "failed_pending", [ "pending" => true ] );
          // return;
          continue;
        }

        if ( !empty( $sources_by_type["user"] ) ){

          $source = [];
          $source["data"] = $sources_data;
          
          // Ensure album cover is present from the main object item if missing in source data
          if ( empty( $source["data"]["cover"] ) ){
            if ( !empty( $object_item["bof_file_cover"]["image_thumb"] ) ){
              $source["data"]["cover"] = $object_item["bof_file_cover"]["image_thumb"];
            }
            elseif ( !empty( $object_item["bof_dir_album"]["bof_file_cover"]["image_thumb"] ) ){
              $source["data"]["cover"] = $object_item["bof_dir_album"]["bof_file_cover"]["image_thumb"];
            }
            elseif ( !empty( $object_item["bof_file_cover"]["image_strings"][1]["html"] ) ){
              if ( preg_match( "/src=\"([^\"]+)\"/", $object_item["bof_file_cover"]["image_strings"][1]["html"], $match ) )
                $source["data"]["cover"] = $match[1];
            }
          }
          
          if ( empty( $source["data"]["cover"] ) ){
            $placeholder = bof()->object->db_setting->get( "placeholder" );
            if ( $placeholder ) $source["data"]["cover"] = $placeholder;
          }

          // Spotify CDN cover (cover_id is empty for most imported tracks).
          // Also upgrades undersized scdn covers (e.g. the 64px variant) to the largest available.
          $_sc = $_cover_from_spotify( $object_item );
          if ( $_sc && (
            empty( $source["data"]["cover"] )
            || !is_string( $source["data"]["cover"] )
            || !preg_match( "/^https?:\/\//", $source["data"]["cover"] )
            || ( strpos( $source["data"]["cover"], "scdn.co" ) !== false && $source["data"]["cover"] !== $_sc )
          ) ){
            $source["data"]["cover"] = $_sc;
          }

          if ( !empty( $source["data"]["cover"] ) ){
              $source["data"]["image"] = $source["data"]["cover"];
              $source["data"]["thumb"] = $source["data"]["cover"];
          }

          $source["source"] = $sources_by_type["user"]["muse"];
          $source["types"] = $sources_by_type["all"];

          $source["data"]["ID"] = $sources_by_type["user"]["hash"];

          if ( !empty( $source["source"]["type"][0] ) && !empty( $source["source"]["type"][1] ) ? ( $source["source"]["type"][0] == "youtube" || !empty( $source["source"]["type"][1]["raaz"] ) ) : false ){
            
            $youtube_id = !empty( $source["source"]["type"][1]["ID"] ) ? $source["source"]["type"][1]["ID"] : ( !empty( $source["source"]["type"][1]["youtube_id"] ) ? $source["source"]["type"][1]["youtube_id"] : null );

            if ( !$youtube_id && !empty( $object_item["youtube_id"] ) )
            $youtube_id = $object_item["youtube_id"];

            if ( !$youtube_id && !empty( $source["source"]["type"][1]["youtube_get"] ) ){
              $try_to_fetch_target_id = $loader->youtube->find_video( array(
                "title" => $object_item["title"],
                "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
                "duration" => !empty( $object_item["duration"] ) ? $object_item["duration"] : ( isset( $object_item["raw"]["duration"] ) ? $object_item["raw"]["duration"] : null )
              ) );
              if ( $try_to_fetch_target_id[0] ) $youtube_id = $try_to_fetch_target_id[1];
            }

            if ( $youtube_id ){

              if ( empty( $object_item["youtube_id"] ) )
              bof()->music->set_track_youtube_id( $object_item["ID"], $youtube_id );

              // Server-side stream cache is always consulted; a live
              // resolution is skipped only when client passed solve=false.
              // Note: bof()->youtube_piped is lazy-loaded via __get, so it
              // cannot be probed with empty()/isset().
              // Resolution order: stream-cache peek (instant) -> saavn
              // (~1.4s, the only reliable resolver while this host's IP is
              // YouTube bot-blocked) -> live Piped/yt-dlp chain (breaker-
              // guarded; was previously first and burned 15-60s per play).
              $_stream_set = false;

              try {
                $stream = bof()->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
                  "allow_live" => false
                ) );
                if ( $stream ){
                  $source["source"]["type"] = array(
                    $stream["type"],
                    array(
                      "address" => $stream["url"],
                      "mime" => $stream["mime"],
                      "type" => "stream",
                      "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : ( !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null ),
                      "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
                      "refresh_by" => !empty( $stream["expire"] ) ? intval( $stream["expire"] ) - 120 : null
                    )
                  );
                  $_stream_set = true;
                }
              } catch( Exception | bofException | Error $err ){}

              if ( !$_stream_set && !$solve_disabled ){
                try {
                  $_sv = bof()->hitune_saavn->resolve(
                    $object_item["title"],
                    !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
                    !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null
                  );
                  if ( $_sv && !empty( $_sv["url"] ) ){
                    $source["source"]["type"] = array(
                      "audio",
                      array(
                        "address" => $_sv["url"],
                        "type" => "free",
                        "format" => $_sv["mime"],
                        "duration" => $_sv["duration"],
                        "saavn" => true
                      )
                    );
                    $_stream_set = true;
                    try {
                      bof()->youtube_piped->cache_stream_url( $youtube_id, $_sv["url"], array(
                        "mime" => $_sv["mime"], "type" => "audio", "duration" => $_sv["duration"], "expire_in" => 259200
                      ) );
                    } catch( Exception | bofException | Error $err ){}
                  }
                } catch( Exception | bofException | Error $err ){}
              }

              if ( !$_stream_set && !$solve_disabled ){
                try {
                  $stream = bof()->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
                    "allow_live" => true
                  ) );
                  if ( $stream ){
                    $source["source"]["type"] = array(
                      $stream["type"],
                      array(
                        "address" => $stream["url"],
                        "mime" => $stream["mime"],
                        "type" => "stream",
                        "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : ( !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null ),
                        "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
                        "refresh_by" => !empty( $stream["expire"] ) ? intval( $stream["expire"] ) - 120 : null
                      )
                    );
                  }
                } catch( Exception | bofException | Error $err ){}
              }
            }

            // JioSaavn last-resort: in case a caller set a youtube stub but
            // the block above was skipped, still try the fast resolver.
            if ( !$solve_disabled && empty( $_stream_set ) && !empty( $source["source"]["type"][0] ) && $source["source"]["type"][0] == "youtube" ){
              try {
                $_sv = bof()->hitune_saavn->resolve(
                  $object_item["title"],
                  !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
                  !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null
                );
                if ( $_sv && !empty( $_sv["url"] ) ){
                  $source["source"]["type"] = array(
                    "audio",
                    array(
                      "address" => $_sv["url"],
                      "type" => "free",
                      "format" => $_sv["mime"],
                      "duration" => $_sv["duration"],
                      "saavn" => true
                    )
                  );
                }
              } catch( Exception | bofException | Error $err ){}
            }

            if ( $source["source"]["type"][0] == "youtube" && is_array( $source["source"]["type"][1] ) ){
              
              // Proactively look for YouTube ID to avoid extra solve_raaz call
              if ( !empty( $object_item["bof_dir_sources"] ) ){
                foreach( $object_item["bof_dir_sources"] as $_source ){
                  if ( $_source["type"] == "youtube" && !empty( $_source["data_decoded"]["youtube_id"] ) ){
                    $source["source"]["type"][1]["youtube_id"] = $_source["data_decoded"]["youtube_id"];
                    break;
                  }
                }
              }

              $source["source"]["type"][1]["raaz"] = true;
              if ( !isset( $source["source"]["type"][1]["youtube_piped"] ) )
              $source["source"]["type"][1]["youtube_piped"] = (bool) intval( $loader->object->db_setting->get( "youtube_piped" ) );
              if ( !isset( $source["source"]["type"][1]["youtube_download"] ) )
              $source["source"]["type"][1]["youtube_download"] = (bool) intval( $loader->object->db_setting->get( "ut" ) );
            }

          }

          // MixCloud iFrame — plugin: bof_tool_hitune_extras (setting htx_mixcloud)
          if ( !empty( $source["source"]["type"][0] ) && $source["source"]["type"][0] == "mixcloud" ){

            $_mc_url = null;
            if ( is_array( $source["source"]["type"][1] ) )
            $_mc_url = !empty( $source["source"]["type"][1]["mixcloud_url"] ) ? $source["source"]["type"][1]["mixcloud_url"] : ( !empty( $source["source"]["type"][1]["url"] ) ? $source["source"]["type"][1]["url"] : null );

            if ( $_mc_url && preg_match( "#mixcloud\.com(/[^\s\"'<>]+)#i", $_mc_url, $_mc_m ) ){
              $source["source"]["type"] = array(
                "iframe",
                array(
                  "type" => "iframe",
                  "provider" => "mixcloud",
                  "address" => "https://www.mixcloud.com/widget/iframe/?hide_cover=1&feed=" . urlencode( rtrim( $_mc_m[1], "/" ) . "/" ),
                  "duration" => !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null
                )
              );
            }

          }

          if ( !empty( $source["source"]["type"][0] ) ?
            ( $source["source"]["type"][0] == "audio" || $source["source"]["type"][0] == "video" ) &&
            !empty( $source["source"]["type"][1]["address"] ) &&
            !empty( $sources_by_type["user"]["protected"] )
          : false ){
            if ( preg_match( "/\/files\/protected\//", $source["source"]["type"][1]["address"] ) ){
              $source["source"]["type"][1]["type" ] = "protected";
              $source["source"]["type"][1]["address"] = $loader->source->grant_access( $source_G["ot"], $source_G["raw"]["hash"], $source["data"]["ID"], $source["source"]["type"][1]["address"], "90 MINUTE" );
            }
          }

          if ( !empty( $source["source"]["type"][0] ) && !empty( $source["source"]["type"][1] ) && !empty( $sources_by_type["user"]["hash"] ) ? is_array( $source["source"]["type"][1] ) : false ){
            $source["source"]["type"][1]["_report"] = md5( $sources_by_type["user"]["hash"] . sign_code );
            $source["source"]["type"][1]["_hash"] = $sources_by_type["user"]["hash"];
          }

          if ( empty( $source["data"]["cover"] ) ){
            $placeholder = $loader->object->db_setting->get( "placeholder" );
            if ( $placeholder ){
              $placeholder = $loader->object->file->select( [ "ID" => $placeholder ] );
              $source["data"]["cover"] = $placeholder["image_thumb"];
            }
          }

          $_sc = $_cover_from_spotify( $object_item );
          if ( $_sc && (
            empty( $source["data"]["cover"] )
            || ( is_string( $source["data"]["cover"] ) && strpos( $source["data"]["cover"], "scdn.co" ) !== false && $source["data"]["cover"] !== $_sc )
          ) ){
            $source["data"]["cover"] = $_sc;
          }

          // Never emit cleartext http:// stream URLs — modern clients block them
          if ( !empty( $source["source"]["type"][1]["address"] ) && is_string( $source["source"]["type"][1]["address"] ) )
          $source["source"]["type"][1]["address"] = $loader->general->https_url( $source["source"]["type"][1]["address"] );

          if ( empty( $source["data"]["preview"] ) ? true : (  $source["data"]["preview"]["type"] == "image" && empty(  $source["data"]["preview"]["image"] ) ) ){
            $placeholder = $loader->object->db_setting->get( "placeholder" );
            if ( $placeholder ){
              $placeholder = $loader->object->file->select( [ "ID" => $placeholder ] );
              $source["data"]["preview"] = array(
                "type" => "image",
                "image" => $placeholder["image_strings"][1]["html"]
              );
            }
          }

          $sources[] = $source;
        }

      }
    }

    // If nothing directly playable was resolved, add an iTunes preview stream (real audio, no embed needed)
    $_playable = false;
    foreach( $sources as $_s ){
      if ( !empty( $_s["source"]["type"][0] ) && in_array( $_s["source"]["type"][0], [ "audio", "video" ], true ) && !empty( $_s["source"]["type"][1]["address"] ) ){
        $_playable = true;
        break;
      }
    }

    if ( !$_playable && !empty( $object_item["title"] ) ){

      // JioSaavn fallback (plugin: bof_tool_hitune_extras) — full-length
      // CDN audio; preferred over the 30s iTunes preview.
      try {
        $_sv = bof()->hitune_saavn->resolve(
          $object_item["title"],
          !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
          !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null
        );
        if ( $_sv && !empty( $_sv["url"] ) ){
          array_unshift( $sources, array(
            "source" => array(
              "type" => array( "audio", array(
                "type" => "free",
                "address" => $_sv["url"],
                "format" => $_sv["mime"],
                "saavn" => true
              ) )
            ),
            "data" => array(
              "ID" => $object_hash,
              "title" => $object_item["title"],
              "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
              "duration" => $_sv["duration"],
              "cover" => !empty( $object_item["bof_file_cover"]["image_thumb"] ) ? $object_item["bof_file_cover"]["image_thumb"] : $_cover_from_spotify( $object_item ),
              "image" => !empty( $object_item["bof_file_cover"]["image_thumb"] ) ? $object_item["bof_file_cover"]["image_thumb"] : $_cover_from_spotify( $object_item ),
              "link" => !empty( $object_item["seo_url"] ) ? "music/track/" . $object_item["seo_url"] : null,
            )
          ) );
          $_playable = true;
        }
      } catch( Exception | bofException | Error $err ){}

      $_itunes_preview = null;
      try {
        if ( $_playable )
        throw new Exception( "already resolved" );
        $_q = trim( $object_item["title"] . " " . ( !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : "" ) );
        $_ctx = stream_context_create( [ "http" => [ "timeout" => 8 ] ] );
        $_it = @json_decode( @file_get_contents( "https://itunes.apple.com/search?term=" . urlencode( $_q ) . "&entity=song&limit=5", false, $_ctx ), true );
        if ( !empty( $_it["results"] ) ){
          $_best = null; $_best_score = 0;
          foreach( $_it["results"] as $_r ){
            if ( empty( $_r["previewUrl"] ) ) continue;
            $_score = 0;
            similar_text( strtolower( $_r["trackName"] ), strtolower( $object_item["title"] ), $_t_pct );
            $_score += $_t_pct;
            if ( !empty( $_r["artistName"] ) && !empty( $object_item["bof_dir_artist"]["name"] ) ){
              similar_text( strtolower( $_r["artistName"] ), strtolower( $object_item["bof_dir_artist"]["name"] ), $_a_pct );
              $_score += $_a_pct * 0.5;
            }
            if ( $_score > $_best_score ){ $_best_score = $_score; $_best = $_r; }
          }
          if ( $_best && $_best_score > 60 )
          $_itunes_preview = $_best["previewUrl"];
        }
      } catch( Exception $err ){}

      if ( $_itunes_preview ){

        // NOTE: previews are intentionally NOT stored as m_track_source rows —
        // a stored 30s preview later served as a "real" audio source makes the
        // track cut off after ~30s on every play ("aadha-adhura" bug).

        array_unshift( $sources, array(
          "source" => array(
            "type" => array( "audio", array(
              "type" => "free",
              "address" => $_itunes_preview,
              "format" => "audio/mp4",
              "preview" => true,
              "is_preview" => true
            ) )
          ),
          "data" => array(
            "ID" => $object_hash,
            "title" => $object_item["title"],
            "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
            "duration" => 30,
            "is_preview" => true,
            "cover" => !empty( $object_item["bof_file_cover"]["image_thumb"] ) ? $object_item["bof_file_cover"]["image_thumb"] : $_cover_from_spotify( $object_item ),
          ),
          "types" => array(
            "count" => 1,
            "sources" => array(
              "audio" => array(
                "count" => 1,
                "active" => true,
                "locked" => false,
                "sources" => array( array( "hook" => "itunes_preview", "hash" => md5( $object_hash . "itunes" ), "active" => true ) )
              )
            )
          )
        ) );

      }

    }

    if ( empty( $sources ) && !empty( $object_item ) ){

      // Force source resolution for new songs
      $youtube_id = null;
      
      // Try to get YouTube ID from existing sources
      if ( !empty( $object_item["bof_dir_sources"] ) ){
        foreach( $object_item["bof_dir_sources"] as $_source ){
          if ( $_source["type"] == "youtube" && !empty( $_source["data_decoded"]["youtube_id"] ) ){
            $youtube_id = $_source["data_decoded"]["youtube_id"];
            break;
          }
        }
      }

      if ( !$youtube_id && !empty( $object_item["youtube_id"] ) )
      $youtube_id = $object_item["youtube_id"];

      // If no YouTube ID found, search for it
      if ( !$youtube_id && !empty( $object_item["title"] ) ){
        $try_to_fetch_target_id = $loader->youtube->find_video( array(
          "title" => $object_item["title"],
          "sub_title" => !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
          "duration" => !empty( $object_item["duration"] ) ? $object_item["duration"] : null
        ) );
        
        if ( $try_to_fetch_target_id[0] ) $youtube_id = $try_to_fetch_target_id[1];
      }
      
      // Create source if YouTube ID found
      if ( $youtube_id ){

        if ( empty( $object_item["youtube_id"] ) )
        bof()->music->set_track_youtube_id( $object_item["ID"], $youtube_id );

        $forced_source = array(
          "source" => array(
            "type" => array( "youtube", array( 
              "ID" => $youtube_id,
              "raaz" => true,
              "youtube_piped" => (bool) intval( $loader->object->db_setting->get( "youtube_piped" ) ),
              "youtube_download" => (bool) intval( $loader->object->db_setting->get( "ut" ) )
            ) )
          ),
          "data" => array( "ID" => $object_hash ),
          "types" => array( "youtube" )
        );

        // Proactive resolve: cache peek -> saavn fast path -> guarded live
        // chain. Same order as the main branch — YouTube resolution from
        // this host is bot-blocked, so saavn goes first.
        $_forced_set = false;
        try {
          $stream = bof()->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
            "allow_live" => false
          ) );
          if ( $stream ){
            $forced_source["source"]["type"] = array(
              $stream["type"],
              array(
                "address" => $stream["url"],
                "mime" => $stream["mime"],
                "type" => "stream",
                "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : ( !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null ),
                "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
                "refresh_by" => !empty( $stream["expire"] ) ? intval( $stream["expire"] ) - 120 : null
              )
            );
            $_forced_set = true;
          }
        } catch( Exception | bofException | Error $err ){}

        if ( !$_forced_set && !$solve_disabled ){
          try {
            $_sv = bof()->hitune_saavn->resolve(
              $object_item["title"],
              !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
              !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null
            );
            if ( $_sv && !empty( $_sv["url"] ) ){
              $forced_source["source"]["type"] = array(
                "audio",
                array(
                  "address" => $_sv["url"],
                  "type" => "free",
                  "format" => $_sv["mime"],
                  "duration" => $_sv["duration"],
                  "saavn" => true
                )
              );
              $_forced_set = true;
              try {
                bof()->youtube_piped->cache_stream_url( $youtube_id, $_sv["url"], array(
                  "mime" => $_sv["mime"], "type" => "audio", "duration" => $_sv["duration"], "expire_in" => 259200
                ) );
              } catch( Exception | bofException | Error $err ){}
            }
          } catch( Exception | bofException | Error $err ){}
        }

        if ( !$_forced_set && !$solve_disabled ){
          try {
            $stream = bof()->youtube_piped->set_setting()->get_stream_cached( $youtube_id );
            if ( $stream ){
              $forced_source["source"]["type"] = array(
                $stream["type"],
                array(
                  "address" => $stream["url"],
                  "mime" => $stream["mime"],
                  "type" => "stream",
                  "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : ( !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null ),
                  "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
                  "refresh_by" => !empty( $stream["expire"] ) ? intval( $stream["expire"] ) - 120 : null
                )
              );
            }
          } catch( Exception | bofException | Error $err ){}
        }

        $sources[] = $forced_source;

      }
      
      // If still no sources, return error
      if ( empty( $sources ) ){
        $loader->api->set_error( "cant_play", array(
          "dont_seek_resolution" => true,
          "error_reason" => "no_sources_found"
        ) );
        return;
      }
    }

    if ( empty( $sources ) ){
      $loader->api->set_error( "cant_play", [ "no_sources" => true, "error_reason" => "access_denied_or_missing" ] );
      return;
    }

    $response = array(
      "sources" => $sources,
    );

    // Per-track loudness metadata (volume normalization) — measured via ffmpeg ebur128
    $_loudness = bof()->music->track_loudness( $object_item );
    if ( $_loudness ){
      $response["loudness"] = $_loudness;
      $response["lufs"] = $_loudness["lufs"];
      if ( $_loudness["peak_db"] !== null ) $response["peak_db"] = $_loudness["peak_db"];
    }

    // Offline download URL — direct (non-HLS) audio only, gated by plan_features.offline_downloads
    if ( $loader->client_config->user_has_feature( "offline_downloads" ) ){
      foreach( $sources as $_s ){
        $_t = !empty( $_s["source"]["type"] ) ? $_s["source"]["type"] : null;
        if ( is_array( $_t ) && $_t[0] == "audio" && !empty( $_t[1]["address"] ) && empty( $_t[1]["hls"] ) && !preg_match( "/\.m3u8($|\?)/i", $_t[1]["address"] ) ){
          $response["download_url"] = $_t[1]["address"];
          break;
        }
      }
    }

    $loader->api->set_message( "ok", $response );

    return;

  }

  $loader->api->set_error( "failed", [ "available" => !empty( $sources_by_type["all"] ) ? $sources_by_type["all"] : false ] );

}

?>