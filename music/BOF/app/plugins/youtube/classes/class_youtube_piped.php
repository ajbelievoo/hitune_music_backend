<?php

if ( !defined( "bof_root" ) ) die;

class youtube_piped extends bof_type_class {

    protected $instance_urls = [];
    protected $preferred_type = null;

    public function set_setting(){

        if ( $this->instance_urls )
        return $this;

        $default_urls = implode( PHP_EOL, array(
            "https://pipedapi.kavin.rocks/",
            "https://pipedapi.adminforge.de/",
            "https://piped-api.garudalinux.org/",
            "https://pipedapi.moomoo.me/",
            "https://api.piped.private.coffee/",
            "https://pipedapi.leptons.xyz/",
            "https://pipedapi.astartes.nl/",
            "https://pipedapi.qis.sh/",
            "https://pipedapi.tokyo.ovh/",
            "https://pipedapi.suyu.sh/",
            "https://pipedapi.nosebs.ru/",
            "https://piped-api.privacy.com.de/",
            "https://pipedapi.drgns.space/",
            "https://pipedapi.owo.si/",
            "https://pipedapi.ducks.party/",
            "https://piped-api.codespace.cz/",
            "https://pipedapi.reallyaweso.me/",
            "https://pipedapi.orangenet.cc/",
            "https://pipedapi.darkness.services/",
            "https://az.piped.video/",
            "https://nf.piped.video/",
            "https://do.piped.video/",
            "https://fl.piped.video/",
            "https://re.piped.video/",
            "https://vc.piped.video/",
            "https://cf.piped.video/"
        ) );

        $setting_urls = bof()->object->db_setting->get( "youtube_piped_iu" );
        if ( $setting_urls )
        $this->set_instance_urls( $setting_urls );
        $this->set_instance_urls( $default_urls );

        $this->preferred_type = bof()->object->db_setting->get( "youtube_piped_st" );
        if ( !$this->preferred_type )
        $this->preferred_type = "audio_hq";

        return $this;
    }
    public function set_instance_urls( $urls ){

        foreach( explode( PHP_EOL, $urls ) as $url ){
            $url = trim( $url );
            if ( !$url )
            continue;
            $url = $url . ( substr( $url, -1 ) == "/" ? "" : "/" );
            $this->instance_urls[] = $url;
        } 
        $this->instance_urls = array_unique( $this->instance_urls );
        shuffle( $this->instance_urls );
        return $this;

    }
    public function get_stream_cached( $id, $args=[] ){

        // Server-side cache for resolved googlevideo/piped stream URLs.
        // URLs carry an `expire` unix param (~6h validity); we reuse the same
        // URL for every client until shortly before it expires.

        $allow_live = true;
        $min_ttl    = 900; // treat cache as stale when <15min of validity remain
        $prefer     = null;
        $allow_ytdlp = true;
        extract( $args );

        if ( $prefer )
        $this->preferred_type = $prefer;

        $stream_key = "yt:" . $id . ":" . ( $this->preferred_type ? $this->preferred_type : "audio_hq" );

        $row = bof()->db->_select( array(
            "table" => "_bof_cache_streams",
            "where" => array(
                [ "stream_key", "=", $stream_key ]
            ),
            "single" => true,
            "cache_load_rt" => false
        ) );

        if ( $row ){

            $expire_at = !empty( $row["expire_at"] ) ? intval( $row["expire_at"] ) : null;

            if ( $expire_at ? ( $expire_at - time() ) > $min_ttl : false ){
                bof()->db->query( "UPDATE _bof_cache_streams SET used = used + 1 WHERE ID = " . intval( $row["ID"] ), null, true );
                return array(
                    "url"      => bof()->general->https_url( $row["url"] ),
                    "mime"     => $row["mime"],
                    "type"     => $row["type"],
                    "duration" => !empty( $row["duration"] ) ? intval( $row["duration"] ) : null,
                    "expire"   => $expire_at,
                    "cached"   => true
                );
            }

        }

        if ( !$allow_live )
        return null;

        // Stampede protection: serialize live resolutions per stream_key
        $lock_name = "bof_stream_" . md5( $stream_key );
        $got_lock = false;
        try {
            $_l = bof()->db->query( "SELECT GET_LOCK('{$lock_name}', 10) AS l", null, true );
            $got_lock = $_l && $_l->fetch_assoc()["l"] == 1;
        } catch( Exception $err ){}

        if ( $got_lock ){

            // Another request may have resolved it while we waited
            $row = bof()->db->_select( array(
                "table" => "_bof_cache_streams",
                "where" => array(
                    [ "stream_key", "=", $stream_key ]
                ),
                "single" => true,
                "cache_load_rt" => false
            ) );

            if ( $row && !empty( $row["expire_at"] ) && ( intval( $row["expire_at"] ) - time() ) > $min_ttl ){
                bof()->db->query( "SELECT RELEASE_LOCK('{$lock_name}')", null, true );
                return array(
                    "url"      => bof()->general->https_url( $row["url"] ),
                    "mime"     => $row["mime"],
                    "type"     => $row["type"],
                    "duration" => !empty( $row["duration"] ) ? intval( $row["duration"] ) : null,
                    "expire"   => intval( $row["expire_at"] ),
                    "cached"   => true
                );
            }

        }

        try {
            $stream = $this->get_stream( $id, $allow_ytdlp );
        } catch( Exception | bofException $err ){
            if ( $got_lock )
            bof()->db->query( "SELECT RELEASE_LOCK('{$lock_name}')", null, true );
            throw $err;
        }

        if ( $got_lock )
        bof()->db->query( "SELECT RELEASE_LOCK('{$lock_name}')", null, true );

        $expire_at = $this->url_expire_ts( $stream["url"] );
        if ( !$expire_at )
        $expire_at = time() + 5 * 3600;

        $this->store_stream(
            $stream_key,
            $stream["url"],
            !empty( $stream["mime"] ) ? $stream["mime"] : null,
            !empty( $stream["type"] ) ? $stream["type"] : null,
            !empty( $stream["duration"] ) ? intval( $stream["duration"] ) : null,
            $expire_at,
            $row
        );

        // Occasional prune of long-expired rows
        if ( mt_rand( 1, 20 ) == 1 )
        bof()->db->_delete( array(
            "table" => "_bof_cache_streams",
            "where" => array(
                [ "expire_at", "<", time() - 86400 ]
            )
        ) );

        return array_merge( $stream, array(
            "expire" => $expire_at,
            "cached" => false
        ) );

    }
    public function cache_stream_url( $id, $url, $args=[] ){

        // Store a stream URL that a trusted client resolved itself.
        // Lets one device's resolution serve every other client.

        $mime = null; $type = null; $duration = null; $prefer = null;
        extract( $args );

        if ( !$id || !$url || !is_string( $url ) || strlen( $url ) > 4000 )
        return false;

        // Clients may resolve an http:// variant — upgrade to https for
        // known CDN hosts rather than dropping the report entirely.
        $url = bof()->general->https_url( $url );

        $parts = parse_url( $url );
        if ( !$parts || empty( $parts["host"] ) || strtolower( !empty( $parts["scheme"] ) ? $parts["scheme"] : "" ) !== "https" )
        return false;

        // Only accept YouTube CDN hosts or one of our configured piped instances
        $host = strtolower( $parts["host"] );
        $host_ok = preg_match( "/(^|\.)googlevideo\.com$/i", $host ) || preg_match( "/(^|\.)youtube\.com$/i", $host ) || preg_match( "/(^|\.)ytimg\.com$/i", $host );

        if ( !$host_ok ){
            $this->set_setting();
            foreach( $this->instance_urls as $iu ){
                $ih = parse_url( $iu, PHP_URL_HOST );
                if ( $ih && ( $host === $ih || substr( $host, -strlen( "." . $ih ) ) === "." . $ih ) ){
                    $host_ok = true;
                    break;
                }
            }
        }
        if ( !$host_ok )
        return false;

        $expire_at = $this->url_expire_ts( $url );

        // Reject URLs that are already expired or about to be
        if ( $expire_at && $expire_at < time() + 300 )
        return false;

        if ( !$expire_at )
        $expire_at = time() + 2 * 3600;

        if ( $prefer )
        $this->preferred_type = $prefer;

        $stream_key = "yt:" . $id . ":" . ( $this->preferred_type ? $this->preferred_type : "audio_hq" );

        $row = bof()->db->_select( array(
            "table" => "_bof_cache_streams",
            "where" => array(
                [ "stream_key", "=", $stream_key ]
            ),
            "single" => true,
            "cache_load_rt" => false
        ) );

        $this->store_stream(
            $stream_key,
            $url,
            $mime,
            $type ? $type : "audio",
            $duration ? intval( $duration ) : null,
            $expire_at,
            $row
        );

        return true;

    }
    protected function store_stream( $stream_key, $url, $mime, $type, $duration, $expire_at, $row=null ){

        $set = array(
            [ "url", $url ],
            [ "mime", $mime ],
            [ "type", $type ],
            [ "duration", $duration ],
            [ "expire_at", $expire_at ]
        );

        if ( $row === null ){
            $row = bof()->db->_select( array(
                "table" => "_bof_cache_streams",
                "where" => array(
                    [ "stream_key", "=", $stream_key ]
                ),
                "single" => true,
                "cache_load_rt" => false
            ) );
        }

        if ( $row )
        bof()->db->_update( array(
            "table" => "_bof_cache_streams",
            "set"   => $set,
            "where" => array(
                [ "ID", "=", $row["ID"] ]
            )
        ) );
        else
        bof()->db->_insert( array(
            "table"  => "_bof_cache_streams",
            "ignore" => true,
            "set"    => array_merge( array( [ "stream_key", $stream_key ] ), $set )
        ) );

    }
    protected function url_expire_ts( $url ){

        $query = parse_url( $url, PHP_URL_QUERY );
        if ( !$query ) return null;
        parse_str( $query, $params );
        return !empty( $params["expire"] ) && is_numeric( $params["expire"] ) ? intval( $params["expire"] ) : null;

    }
    public function get_stream( $id, $allow_ytdlp=true ){
        
        $i=0;
        $max_instances = 6; // Limit searching to 6 instances to avoid timeout
        foreach ($this->instance_urls as $instance_url) {
            $i++;
            if ( $i > $max_instances ) break;
            try {
                $data = $this->get_video_stream($id, $instance_url);
                $gotOne = true;
                break;    
            } catch (Exception | bofException $err) {
                $lastException = $err->getMessage();
                continue;
            }
        }

        if ( empty( $gotOne ) ){

            if ( $allow_ytdlp && bof()->object->db_setting->get( "ut" ) ){

                $_ytdlp = bof()->object->db_setting->get( "ut_youtubedl_path" );
                $_ytdlp = $_ytdlp ? htmlspecialchars_decode( $_ytdlp ) : null;
                if ( !$_ytdlp || !is_file( $_ytdlp ) || !is_executable( $_ytdlp ) )
                $_ytdlp = "/usr/local/bin/yt-dlp";

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

                $_proxy_arg = "";
                $_ytdlp_proxy = bof()->object->db_setting->get( "ut_youtubedl_proxy" );
                if ( !empty( $_ytdlp_proxy ) )
                $_proxy_arg = " --proxy \"" . str_replace( "\"", "", $_ytdlp_proxy ) . "\" ";

                // Try the web client first, then the android client - partial
                // IP/bot-check blocks sometimes hit only one of them.
                $_ytdlp_attempts = [
                    " --user-agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\" ",
                    " --extractor-args \"youtube:player_client=android\" --user-agent \"Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36\" ",
                ];
                foreach( $_ytdlp_attempts as $_ytdlp_ua ){
                    $command = "\"{$_ytdlp}\" -g -f ba --force-ipv4 --no-check-certificate " . $_proxy_arg . $js_runtime_string . " --cache-dir \"" . $cache_dir . "\" --remote-components ejs:github " . $_ytdlp_ua . $cookies . " 'https://www.youtube.com/watch?v=" . $id . "' 2>&1";
                    $output = shell_exec($command);
                    if ( $output && strpos($output, 'http') !== false && strpos($output, 'ERROR:') === false ) {
                        $lines = explode("\n", trim($output));
                        $final_url = end($lines);
                        if ( strpos($final_url, 'http') === 0 ){
                            $final_url = bof()->general->https_url( $final_url );
                            $mime = "audio/webm";
                            if ( strpos($final_url, "m4a") !== false ) $mime = "audio/mp4";
                            return array(
                                "url" => $final_url,
                                "mime" => $mime,
                                "type" => "audio",
                                "duration" => null
                            );
                        }
                    }
                }
            }

            throw new bofException( "yt_piped failed. searched {$i} instances" . ( !empty( $lastException ) ? "; last={$lastException}" : "" ) );

        }

        $preferred_type = $this->preferred_type;
        list( $p_t, $p_q ) = explode( "_", $preferred_type );

        $chosenURL = null;
        $chosenMIME = null;
        $chosenURLScore = 0;

        $sources = $p_t == "audio" ? $data["audioStreams"] : $data["videoStreams"];
        foreach( $sources as $source ){

            if ( !empty( $source["videoOnly"] ) )
            continue;

            $choose = false;
            $score = $p_t == "audio" ? intval( $source["quality"] ) : $source["width"];

            if ( $chosenURL === null )
            $choose = true;
            elseif ( $p_q == "hq" && $score > $chosenURLScore )
            $choose = true;
            elseif ( $p_q == "lq" && $score < $chosenURLScore )
            $choose = true;

            if ( $choose ){
                $chosenURL = $source["url"];
                $chosenMIME = $source["mimeType"];
                $chosenURLScore = $score;
            }

        }

        if ( !$chosenURL )
        throw new bofException("No Valid Stream");
        
        return array(
            "url" => bof()->general->https_url( $chosenURL ),
            "mime" => $chosenMIME,
            "type" => $p_t,
            "duration" => !empty( $data["duration"] ) ? intval( $data["duration"] ) : null
        );

    }
    public function get_video_stream( $id, $instance_url ){

        $exe = bof()->curl->exe( array(
            "url" => $instance_url . "streams/{$id}",
            "cache" => false,
            "agent" => "chrome",
            "timeout" => 6, // 6s total
            "ctimeout" => 3, // 3s connect
            "headers" => array(
                "Origin: " . web_address,
                "X-Requested-With: XMLHttpRequest"
            )
        ) );

        if ( $exe["http_code"] != 200 )
        throw new bofException( "Invalid http code {$exe["http_code"]}" );

        if ( empty( $exe["data"] ) )
        throw new bofException( "Invalid body" );

        if ( empty( $exe["data"]["videoStreams"] ) )
        throw new bofException( "No Video Streams" );

        return $exe["data"];

    }

    public function get_instances(){
        $this->set_setting();
        return $this->instance_urls;

    }

}

?>
