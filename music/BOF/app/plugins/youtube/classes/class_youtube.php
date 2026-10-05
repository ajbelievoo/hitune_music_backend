<?php

if ( !defined( "bof_root" ) ) die;

class youtube extends bof_type_class {

  protected $base = "https://www.googleapis.com/youtube/v3/";
  protected $key  = null;
  protected $setting = array(
    "regionCode" => "us",
    "simRatio" => 30
  );

  protected function set_key(){

    $keys = bof()->object->db_setting->get( "youtube_api_keys" );
    if ( !$keys ) return false;

    $keys = explode( PHP_EOL, str_replace( [ "<br>", "\r\n", "\n" ], PHP_EOL, $keys ) );

    $this->key = $keys[ rand( 0, count( $keys ) - 1 ) ];
    return true;

  }
  protected function set_setting(){

    if ( ( $regionCode = bof()->object->db_setting->get( "youtube_api_regionCode" ) ) )
    $this->setting["regionCode"] = $regionCode;

    if ( ( $simRatio = bof()->object->db_setting->get( "youtube_api_simRatio" ) ) )
    $this->setting["simRatio"] = $simRatio;

  }

  public function find_video( $data ){

    $title = null;
    $sub_title = null;
    $duration = null;
    extract( $data );
    $_query = ( $sub_title ? $sub_title . " - " : "" ) . "{$title}" ;

    $cache_key = "yt_fv_" . md5( $_query . ( $duration ? "_" . $duration : "" ) );
    $cached_result = bof()->object->db_setting->get( $cache_key );
    if ( $cached_result ){
      $cached_result_decoded = json_decode( $cached_result, true );
      if ( !empty( $cached_result_decoded["id"] ) )
        return [ true, $cached_result_decoded["id"], $cached_result_decoded["data"] ];
    }

    $this->set_setting();

    $exe_search = $this->_bof_this->clean_search( $_query );

    if ( !$exe_search[0] ){
      $fallback = $this->_bof_this->find_video_ytdlp( $_query );
      if ( $fallback[0] ) return $fallback;
      return $exe_search;
    }

    $exe_search_items = $exe_search[1];

    if ( empty( $exe_search_items ) ){
      $fallback = $this->_bof_this->find_video_ytdlp( $_query );
      if ( $fallback[0] ) return $fallback;
      return [ false, "YoutubeAPI: Found Nothing" ];
    }

    $highest_similarity = 0;
    $highest_similarity_youtube_id = null;
    $highest_similarity_youtube_data = null;

    foreach( $exe_search_items as $result_item ){

      $found_bad_word = false;
      foreach( [ "re-action", "reaction", "re action", "live", "cover", "meaning", "verified", "awards", "show", "reverb", "explaining", "instrumental", "mashup", "tour", "interview", "slowed", "remix" ] as $not_allowed_text ){
				if ( !preg_match( "/{$not_allowed_text}/i", $_query ) ){
					if ( preg_match( "/{$not_allowed_text}/i", $result_item["title"] ) ){
            $found_bad_word = true;
          }
				}
			}

      if ( $found_bad_word )
      continue;

      if ( !empty( $duration ) && !empty( $result_item["duration"] ) ? abs( $duration - $result_item["duration"] ) > 60 : false )
      continue;

      similar_text(
        mb_strtolower( $_query, "UTF-8" ),
        mb_strtolower( $result_item["title"], "UTF-8" ),
        $sim
      );

      foreach( [ "official audio" => 13, "official video" => 13, "music video" => 10, "audio" => 7 , "video" => 7, "lyrics" => 4, "explicit" => 6 ] as $good_text => $good_text_point ){
				if ( preg_match( "/{$good_text}/i", $result_item["title"] ) ) $sim += $good_text_point;
			}

			if ( $sim > $highest_similarity && $sim >= $this->setting["simRatio"] ){
				$highest_similarity_youtube_id  = $result_item["id"];
        $highest_similarity_youtube_data = $result_item;
				$highest_similarity = $sim;
			}

    }

    if ( $highest_similarity_youtube_id ){
      $res = [ true, $highest_similarity_youtube_id, $highest_similarity_youtube_data ];
      bof()->object->db_setting->set( $cache_key, json_encode( [ "id" => $highest_similarity_youtube_id, "data" => $highest_similarity_youtube_data ] ) );
      return $res;
    }

    if ( $sub_title ){
      $exe_search2 = $this->_bof_this->clean_search( $title );
      if ( $exe_search2[0] && !empty( $exe_search2[1] ) ){
        foreach( $exe_search2[1] as $result_item ){
          similar_text( mb_strtolower( $title, "UTF-8" ), mb_strtolower( $result_item["title"], "UTF-8" ), $sim );
          foreach( [ "official audio" => 13, "official video" => 13, "music video" => 10, "audio" => 7 , "video" => 7, "lyrics" => 4, "explicit" => 6 ] as $good_text => $good_text_point ){
            if ( preg_match( "/{$good_text}/i", $result_item["title"] ) ) $sim += $good_text_point;
          }
          if ( $sim > $highest_similarity && $sim >= $this->setting["simRatio"] ){
            $highest_similarity_youtube_id  = $result_item["id"];
            $highest_similarity_youtube_data = $result_item;
            $highest_similarity = $sim;
          }
        }
        if ( $highest_similarity_youtube_id ){
          $res = [ true, $highest_similarity_youtube_id, $highest_similarity_youtube_data ];
          bof()->object->db_setting->set( $cache_key, json_encode( [ "id" => $highest_similarity_youtube_id, "data" => $highest_similarity_youtube_data ] ) );
          return $res;
        }
      }
    }

    $fallback = $this->_bof_this->find_video_ytdlp( $_query );
    if ( $fallback[0] ){
      bof()->object->db_setting->set( $cache_key, json_encode( [ "id" => $fallback[1], "data" => $fallback[2] ] ) );
      return $fallback;
    }

    if ( $sub_title ){
      $fallback2 = $this->_bof_this->find_video_ytdlp( "{$title}" );
      if ( $fallback2[0] ){
        bof()->object->db_setting->set( $cache_key, json_encode( [ "id" => $fallback2[1], "data" => $fallback2[2] ] ) );
        return $fallback2;
      }
    }

    return [ false, "YoutubeAPI: Found Nothing Relevant" ];

  }
  public function find_video_ytdlp( $query ){

    $youtube_dl_location = bof()->object->db_setting->get( "ut_youtubedl_path" );
    $youtube_dl_location = $youtube_dl_location ? htmlspecialchars_decode( $youtube_dl_location ) : null;

    if ( !$youtube_dl_location )
    $youtube_dl_location = "/usr/local/bin/yt-dlp";

    if ( !is_file( $youtube_dl_location ) || !is_executable( $youtube_dl_location ) )
    return [ false, "yt-dlp: Not Available" ];

    $tmp_dir = function_exists( "sys_get_temp_dir" ) ? sys_get_temp_dir() : null;
    $cache_dir = rtrim( $tmp_dir ? $tmp_dir : ( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp" ), "/\\" ) . "/ytdlp_cache";
    if ( !is_dir( $cache_dir ) ){
      @mkdir( $cache_dir, 0777, true );
      @chmod( $cache_dir, 0777 );
    }

    $js_runtime_string = "";
    if ( is_file( "/usr/local/bin/deno" ) && is_executable( "/usr/local/bin/deno" ) )
    $js_runtime_string = " --js-runtimes deno:/usr/local/bin/deno ";
    elseif ( is_file( "/usr/local/bin/node" ) && is_executable( "/usr/local/bin/node" ) )
    $js_runtime_string = " --js-runtimes node:/usr/local/bin/node ";

    $ua_string = " --user-agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\" ";
    $search = "ytsearch1:" . trim( (string)$query );
    $command = "\"{$youtube_dl_location}\" --skip-download --no-warnings --force-ipv4 --no-check-certificate --flat-playlist --playlist-end 1 --print \"%(id)s\" {$js_runtime_string} --cache-dir \"{$cache_dir}\" --remote-components ejs:github {$ua_string} " . escapeshellarg( $search );

    $o = [];
    $ret = 0;
    exec( $command . " 2>&1", $o, $ret );

    if ( $ret !== 0 )
    return [ false, "yt-dlp: Search Failed" ];

    foreach( $o as $line ){
      $line = trim( (string)$line );
      if ( preg_match( "/^[a-zA-Z0-9\\-_]{11}$/", $line ) )
      return [ true, $line, [ "title" => $query ] ];
    }

    return [ false, "yt-dlp: No Results" ];

  }
  public function clean_search( $query, $args=[] ){

    if ( !( $this->set_key() ) )
    return [ false, "YoutubeAPI: No Keys" ];

    $search = $this->_bof_this->search( $query, $args );
    if ( !$search[0] ) return $search;

    $clean = [];
    foreach( $search[1]["items"] as $item ){
      $clean[] = array(
        "id" => $item["id"]["videoId"],
        "title" => $item["snippet"]["title"],
        "description" => !empty( $item["snippet"]["description"] ) ? $item["snippet"]["description"] : null,
        "channel_id" => $item["snippet"]["channelId"],
        "channel_title" => $item["snippet"]["channelTitle"],
        "images" => !empty( $item["snippet"]["thumbnails"] ) ? $item["snippet"]["thumbnails"] : null,
      );
    }

    return [ true, $clean ];

  }
  public function search( $query, $args=[] ){

    $q = urldecode( $query );
    $part = "snippet";
    $maxResults = 15;
    $order = "relevance";
    $safeSearch = "moderate";
    $type = "video";
    $regionCode = $this->setting["regionCode"];
    extract( $args );

    $res = $this->_req( "search", array(
      "params" => array(
        "q" => $q,
        "part" => $part,
        "maxResults" => $maxResults,
        "order" => $order,
        "safeSearch" => $safeSearch,
        "type" => $type,
        "regionCode" => $regionCode,
        "key" => $this->key
      )
    ) );

    if ( !empty( $res["error"] ) )
    return [ false, "YoutubeAPI Error: " . $res["error"]["message"] ];

    return [ true, $res ];

  }

  public function get_video_clean( $id, $args=[] ){

    $get_video = $this->_bof_this->get_video( $id, $args );
    if ( !$get_video[0] ) return $get_video;

    $api_data = $get_video[1];
    if ( empty( $api_data["items"] ) || empty( $api_data["pageInfo"] ) ? true : $api_data["pageInfo"]["totalResults"] != 1 || $api_data["items"][0]["kind"] != "youtube#video" )
    return [ 0, "not_found" ];

    $video_snippet = $api_data["items"][0]["snippet"];

    return [ true, array(
      "id" => $id,
      "title" => $video_snippet["title"],
      "channel_id" => $video_snippet["channelId"],
      "channel_name" => $video_snippet["channelTitle"],
      "description" => $video_snippet["description"],
      "tags" => !empty( $video_snippet["tags"] ) ? $video_snippet["tags"] : null,
      "time_publish" => $video_snippet["publishedAt"],
      "covers" => $video_snippet["thumbnails"],
      "live" => !empty( $video_snippet["liveBroadcastContent"] ) ? $video_snippet["liveBroadcastContent"] == "live" : false
    ) ];

  }
  public function get_video( $id, $args=[] ){

    $part = "snippet";
    extract( $args );

    if ( !( $this->set_key() ) )
    return [ false, "YoutubeAPI: No Keys" ];

    $this->set_setting();

    return $this->_req( "videos", array(
      "params" => array(
        "id" => $id,
        "part" => $part,
      )
    ) );

  }

  public function download_sub( $youtube_id, $args = [] ){

    $youtube_dl_location = null;
    $simplify = false;
    extract( $args );

    // Get & Check setting
    $youtube_dl_location = $youtube_dl_location ? $youtube_dl_location : bof()->object->db_setting->get( "ut_youtubedl_path" );
    if ( !$youtube_dl_location ){
      $_paths = [
        "/usr/bin/yt-dlp",
        "/usr/local/bin/yt-dlp",
        "/usr/bin/youtube-dl",
        "/usr/local/bin/youtube-dl"
      ];
      foreach( $_paths as $_p ){
        if ( is_file( $_p ) && is_executable( $_p ) ){
          $youtube_dl_location = $_p;
          break;
        }
      }
    }
    $youtube_dl_proxy = bof()->object->db_setting->get( "ut_youtubedl_proxy" );
    if ( !$youtube_dl_location  )
    throw new Exception("Invalid youtube_dl path");

    $youtube_dl_location = htmlspecialchars_decode( $youtube_dl_location );

    // Variables
    $youtube_dir_path  = bof()->file->mkdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/youtube_dl_" . uniqid()  );
    $youtube_file_path = null;
    $proxy_string = "";
    if ( $youtube_dl_proxy ){
      $parts = parse_url( $youtube_dl_proxy );
      $is_valid = $parts && !empty( $parts["host"] ) && !empty( $parts["port"] ) && is_numeric( $parts["port"] );
      if ( $is_valid ){
        $errno = 0; $errstr = "";
        $sock = @fsockopen( $parts["host"], intval( $parts["port"] ), $errno, $errstr, 3 );
        if ( $sock ){
          fclose( $sock );
          $proxy_string = " --proxy {$youtube_dl_proxy} ";
        }
      }
    }
    $cache_dir = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/ytdlp_cache";
    bof()->file->mkdir( $cache_dir );
    $home_dir = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/ytdlp_home";
    bof()->file->mkdir( $home_dir );
    @putenv( "HOME={$home_dir}" );
    @putenv( "XDG_CACHE_HOME={$cache_dir}" );
    $cache_string = " --cache-dir \"" . $cache_dir . "\" ";
    $js_runtime_string = "";
    $cookies = "";
    $ua_string = " --user-agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\" ";
    $extractor_string = "";
    $rate_limit_string = " --remote-components ejs:github --sleep-requests 1 --concurrent-fragments 1 --force-ipv4 --socket-timeout 30 --retries 5 --fragment-retries 5 --retry-sleep 2 --geo-bypass --no-check-certificate --no-mtime ";
    $_cookie_candidates = [
      base_root . "/files/protected/yt_cookies.php",
      base_root . "/files/protected/yt_cookies.txt",
      base_root . "/files/protected/cookies.txt",
      base_root . "/files/yt_cookies.php",
      base_root . "/files/yt_cookies.txt",
      base_root . "/files/cookies.txt",
    ];
    foreach( $_cookie_candidates as $_cp ){
      if ( is_file( $_cp ) && is_readable( $_cp ) ){
        $size_ok = @filesize( $_cp ) > 16;
        $first = "";
        if ( $size_ok ){
          $fh = @fopen( $_cp, "r" );
          if ( $fh ){
            $first = fgets( $fh );
            fclose( $fh );
          }
        }
        if ( $size_ok && preg_match( "/Netscape HTTP Cookie File/i", $first ) ){
          $cookies = " --cookies \"".realpath( $_cp )."\" ";
          break;
        }
      }
    }
    $help_o = [];
    @exec( "\"{$youtube_dl_location}\" --help 2>&1", $help_o );
    $_help = is_array( $help_o ) ? implode( "\n", $help_o ) : $help_o;
    if ( preg_match( "/--js-runtimes/", $_help ) ){
      $_runtime_candidates = [
        [ "deno", "/usr/local/bin/deno" ],
        [ "deno", "/usr/bin/deno" ],
        [ "deno", base_root . "/bin/deno" ],
        [ "node", "/usr/local/bin/node" ],
        [ "node", "/usr/bin/node" ],
        [ "node", base_root . "/bin/node" ],
      ];
      foreach( $_runtime_candidates as $_rc ){
        list( $_name, $_np ) = $_rc;
        if ( is_file( $_np ) && is_executable( $_np ) ){
          $js_runtime_string = " --js-runtimes {$_name}:{$_np} ";
          break;
        }
      }
    }

    // Run youtube-dl and catch the output
    $command = "\"{$youtube_dl_location}\"" . $proxy_string . $cache_string . $js_runtime_string . $ua_string . $extractor_string . $rate_limit_string . $cookies . ' --write-auto-sub --skip-download -o "'.$youtube_dir_path.'sub" "https://www.youtube.com/watch?v=' . $youtube_id . '"';
    $o = [];
    $ret = 0;
    exec( $command . " 2>&1", $o, $ret );
    $stderr = is_array( $o ) ? implode( "\n", $o ) : ( $o ? $o : "" );
    if ( !glob( $youtube_dir_path . "/*.vtt" ) ){
      $ua_string = " --user-agent \"Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36\" ";
      $extractor_string = " --extractor-args \"youtube:player_client=android\" ";
      $o = [];
      $ret = 0;
      exec( ( "\"{$youtube_dl_location}\"" . $proxy_string . $cache_string . $js_runtime_string . $ua_string . $extractor_string . $rate_limit_string . $cookies . ' --write-auto-sub --skip-download -o "'.$youtube_dir_path.'sub" "https://www.youtube.com/watch?v=' . $youtube_id . '"' ) . " 2>&1", $o, $ret );
      $stderr = $stderr . "\n" . ( is_array( $o ) ? implode( "\n", $o ) : ( $o ? $o : "" ) );
    }
    
    foreach( scandir( $youtube_dir_path ) as $youtube_dir_ent ){
      if ( substr( $youtube_dir_ent, -4 ) == ".vtt" )
      $youtube_file_path = realpath( $youtube_dir_path . "/" . $youtube_dir_ent );
    }

    if ( empty( $youtube_file_path ) ){
      $stderr = is_array( $o ) ? implode( "\n", $o ) : ( $o ? $o : "" );
      throw new Exception("youtube_dl error: " . ( $stderr ? $stderr : "no vtt file found" ) );
    }

    if ( !$simplify )
    return $youtube_file_path;

    $raw_lines = [];
    foreach( bof()->general->explode_by_line( file_get_contents( $youtube_file_path ) ) as $raw_line ){
    
      $raw_line = trim( $raw_line );
    
      if ( preg_match( "/-->/i", $raw_line ) )
      continue;

      if ( !in_array( $raw_line, $raw_lines, true ) )
      $raw_lines[] = strip_tags( $raw_line, "" );

    }

    return implode( ". ", $raw_lines );

  }
  public function download( $youtube_id, $args = [] ){

    $file_name = substr( md5( $youtube_id ), 0, 20 );
    $test = false;
    $youtube_dl_location = null;
    $convert_if_required = true;
    $returnCommand = false;
    $ftype = "ba";
    extract( $args );

    // Get & Check setting
    $youtube_dl_location = $youtube_dl_location ? $youtube_dl_location : bof()->object->db_setting->get( "ut_youtubedl_path" );
    if ( !$youtube_dl_location ){
      $_paths = [
        "/usr/bin/yt-dlp",
        "/usr/local/bin/yt-dlp",
        "/usr/bin/youtube-dl",
        "/usr/local/bin/youtube-dl"
      ];
      foreach( $_paths as $_p ){
        if ( is_file( $_p ) && is_executable( $_p ) ){
          $youtube_dl_location = $_p;
          break;
        }
      }
    }
    $youtube_dl_proxy = bof()->object->db_setting->get( "ut_youtubedl_proxy" );
    if ( !$youtube_dl_location  )
    throw new Exception("Invalid youtube_dl path");

    $youtube_dl_location = htmlspecialchars_decode( $youtube_dl_location );

    // Variables
    $random_file_name  = $file_name;
    $youtube_dir_path  = bof()->file->mkdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/youtube_dl_" . uniqid()  );
    $youtube_file_path = null;
    $youtube_mp3_path  = null;
    $proxy_string = "";
    if ( $youtube_dl_proxy ){
      $parts = parse_url( $youtube_dl_proxy );
      $is_valid = $parts && !empty( $parts["host"] ) && !empty( $parts["port"] ) && is_numeric( $parts["port"] );
      if ( $is_valid ){
        $errno = 0; $errstr = "";
        $sock = @fsockopen( $parts["host"], intval( $parts["port"] ), $errno, $errstr, 3 );
        if ( $sock ){
          fclose( $sock );
          $proxy_string = " --proxy {$youtube_dl_proxy} ";
        }
      }
    }
    $cache_dir = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/ytdlp_cache";
    bof()->file->mkdir( $cache_dir );
    $home_dir = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/tmp/ytdlp_home";
    bof()->file->mkdir( $home_dir );
    @putenv( "HOME={$home_dir}" );
    @putenv( "XDG_CACHE_HOME={$cache_dir}" );
    $cache_string = " --cache-dir \"" . $cache_dir . "\" ";
    $js_runtime_string = "";
    $ua_string = " --user-agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\" ";
    $extractor_string = "";
    $rate_limit_string = " --remote-components ejs:github --sleep-requests 1 --concurrent-fragments 1 --force-ipv4 --socket-timeout 30 --retries 5 --fragment-retries 5 --retry-sleep 2 --geo-bypass --no-check-certificate --no-mtime ";
    $help_o = [];
    @exec( "\"{$youtube_dl_location}\" --help 2>&1", $help_o );
    $_help = is_array( $help_o ) ? implode( "\n", $help_o ) : $help_o;
    if ( preg_match( "/--js-runtimes/", $_help ) ){
      $_runtime_candidates = [
        [ "deno", "/usr/local/bin/deno" ],
        [ "deno", "/usr/bin/deno" ],
        [ "deno", base_root . "/bin/deno" ],
        [ "node", "/usr/local/bin/node" ],
        [ "node", "/usr/bin/node" ],
        [ "node", base_root . "/bin/node" ],
      ];
      foreach( $_runtime_candidates as $_rc ){
        list( $_name, $_np ) = $_rc;
        if ( is_file( $_np ) && is_executable( $_np ) ){
          $js_runtime_string = " --js-runtimes {$_name}:{$_np} ";
          break;
        }
      }
    }

    // Cookies
    $cookies = "";
    $_cookie_candidates = [
      base_root . "/files/protected/yt_cookies.php",
      base_root . "/files/protected/yt_cookies.txt",
      base_root . "/files/protected/cookies.txt",
      base_root . "/files/yt_cookies.php",
      base_root . "/files/yt_cookies.txt",
      base_root . "/files/cookies.txt",
    ];
    foreach( $_cookie_candidates as $_cp ){
      if ( is_file( $_cp ) && is_readable( $_cp ) ){
        $size_ok = @filesize( $_cp ) > 16;
        $first = "";
        if ( $size_ok ){
          $fh = @fopen( $_cp, "r" );
          if ( $fh ){
            $first = fgets( $fh );
            fclose( $fh );
          }
        }
        if ( $size_ok && preg_match( "/Netscape HTTP Cookie File/i", $first ) ){
          $cookies = " --cookies \"".realpath( $_cp )."\" ";
          break;
        }
      }
    }

    // Run youtube-dl and catch the output
    $command = "\"{$youtube_dl_location}\"" . $proxy_string . $cache_string . $js_runtime_string . $ua_string . $extractor_string . $rate_limit_string . $cookies . ' -f '.$ftype.' "https://www.youtube.com/watch?v=' . $youtube_id . '" --output "' . $youtube_dir_path . '/' . $random_file_name . '.%(ext)s" ';
    if ( $returnCommand ) return $command;
    $o = [];
    $ret = 0;
    $oo = exec( $command . " 2>&1", $o, $ret );
    $stderr_all = is_array( $o ) ? implode( "\n", $o ) : ( $o ? $o : "" );
    if ( !glob( $youtube_dir_path . "/".$random_file_name.".*" ) ){
      $ua_string = " --user-agent \"Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36\" ";
      $extractor_string = " --extractor-args \"youtube:player_client=android\" ";
      $o = [];
      $ret = 0;
      $oo = exec( ( "\"{$youtube_dl_location}\"" . $proxy_string . $cache_string . $js_runtime_string . $ua_string . $extractor_string . $rate_limit_string . $cookies . ' -f '.$ftype.' "https://www.youtube.com/watch?v=' . $youtube_id . '" --output "' . $youtube_dir_path . '/' . $random_file_name . '.%(ext)s" ' ) . " 2>&1", $o, $ret );
      $stderr_all = $stderr_all . "\n" . ( is_array( $o ) ? implode( "\n", $o ) : ( $o ? $o : "" ) );
    }

    // Get youtube_dl output. we don't know the extension so search all dir ents for the file
    foreach( scandir( $youtube_dir_path ) as $youtube_dir_ent ){
      if ( substr( $youtube_dir_ent, 0, strlen( $random_file_name ) ) == $random_file_name ){
        $youtube_file_path = realpath( $youtube_dir_path . "/" . $youtube_dir_ent );
      }
    }
    if ( empty( $youtube_file_path ) ){
      $stderr = is_array( $o ) ? implode( "\n", $o ) : ( $o ? $o : "" );
      $stderr = $stderr_all ? $stderr_all : $stderr;
      throw new Exception("youtube_dl error: " . ( $stderr ? $stderr : "failed" ) );
    }

    // If output is not mp3, convert it to mp3
    if ( substr( $youtube_file_path, -4 ) == ".mp3" ){

      $youtube_mp3_path = $youtube_file_path;

    } elseif ( $convert_if_required ) {

      $convert = bof()->ffmpeg->convert_to_mp3( $youtube_file_path, null, array(
        "dir" => $youtube_dir_path,
        "name" => $random_file_name,
        "ab" => null,
        "ca" => "mp3",
      ) );

      if ( $convert ){
        $youtube_mp3_path = $convert;
        unlink( $youtube_file_path );
      }

    } else {
      $youtube_mp3_path = $youtube_file_path;
    }

    if ( empty( $youtube_mp3_path ) )
    throw new Exception("FFmpeg failed: no MP3 files found");

    if ( $test )
    unlink( $youtube_mp3_path );

    return $youtube_mp3_path;

  }

  protected function _req( $endpoint, $args=[] ){

    $params = null;
    extract( $args );
    $params[ "key" ] = $this->key;

    $url = $this->base . $endpoint . ( $params ? "?" . http_build_query( $params ) : "" );
    $curl = bof()->curl->exe( array(
      "url" => $url,
      "cache_load" => true,
      "cache" => true
    ) );

    if ( empty( $curl["data"] ) )
    return [ false, "YoutubeAPI: cURL Failed" ];
    return [ true, $curl["data"] ];

  }

}

?>
