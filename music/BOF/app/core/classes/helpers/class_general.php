<?php

if ( !defined( "bof_root" ) ) die;

class general {

  protected $full_fall = true;
  protected $fall_callback = null;

  public function generateCallTrace( $implodeBy=true ){

    // code by: https://www.php.net/manual/en/function.debug-backtrace.php#112238

    $implodeBy = $implodeBy !== true ? $implodeBy : ( php_sapi_name() === "cli" ? "\n" : "<br>" );

    $e = new Exception();
    $trace = explode("\n", $e->getTraceAsString());

    $trace = array_reverse($trace);
    array_shift($trace);
    array_pop($trace);
    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++){
      $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' '));
    }

    if ( $implodeBy )
    return implode( $implodeBy, $result ) . $implodeBy;
    return $result;

  }
  public function set_full_fall( $value ){
    $this->full_fall = $value ? true : false;
  }
  public function set_fall_callback( $function ){
    $this->fall_callback = $function;
  }
  public function get_full_fall(){
    return $this->full_fall;
  }
	public function fall( $reason=null, $extraData=null ){

    if ( !empty( ob_get_status() ) )
    ob_end_clean();

    if ( $this->full_fall ){

      header('HTTP/1.1 503 Service Temporarily Unavailable');
      header('Status: 503 Service Temporarily Unavailable');

      if ( $reason ){
        if ( php_sapi_name() === "cli" ){
          echo "\033[31mFatal Error: {$reason} \033[0m\n";
          echo $this->generateCallTrace();
        } else {
          echo json_encode( array(
            "success" => false,
            "error" => $reason,
            "messages" => [ $reason ],
            "trace" => $this->generateCallTrace(false)
          ) );
        }
      }

      if ( !defined("api_sent_dia") ){
        try {
          $e = new Exception();
          $ts = $e->getTrace();
          $fallData = [ "line" => "1", "file" => "bof" ];
          foreach( array_reverse( $ts ) as $t ){
            if ( $t["function"] == "fall" ){
              $fallData = $t;
              break;
            }
          }
          if ( bof()->defined("db") ){
            bof()->object->error_log->insert(array(
              "file" => $fallData["file"],
              "line" => $fallData["line"],
              "severity" => "BFall",
              "severity_name" => "BFall",
              "message" => json_encode( $reason ),
              "bof_version" => bof_version
            ));
          }
        } catch( Exception|bofException|Warning|Error $err ){
          echo $err->getMessage();
        }
      }

      exit();
  		die();

    }
    elseif ( $this->fall_callback ){
      $fallback_func = $this->fall_callback;
      $fallback_func( $reason, $extraData );
    }
    else {
      throw new bofException( $reason ? $reason : "Unkown", 0, null, $extraData );
    }

	}

  public function duration_cr( $hr_string ){

    $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $hr_string );
    sscanf( $str_time, "%d:%d:%d", $hours, $minutes, $seconds );
    $time_seconds = ( $hours * 60 * 60 ) + ( $minutes * 60 ) + $seconds;
    return $time_seconds;

  }
  public function duration_cr2( $hr_string ){

    $ds = explode( ":", $hr_string );

    if ( count( $ds ) == 1 )
    return intval( $ds[0] );

    if ( count( $ds ) == 2 )
    return intval( ($ds[0]*60) + $ds[1] );

    return intval( ($ds[0]*3600)+($ds[1]*60)+$ds[2] );

  }
  public function duration_hr( $seconds ){

    $_a = array_reverse( array(
      "seconds" => 1,
      "minutes" => 60,
      "hours"   => 60*60,
      "days"    => 24*60*60
    ) );

    $_e = array();

    foreach( $_a as $_k => $_i ){
      if ( $seconds >= $_i ){
        $_e[ $_k ] = floor( $seconds / $_i );
        $seconds -= floor( $seconds / $_i ) * $_i;
      }
    }

    foreach( $_a as $_k => $_i ){
      if ( isset( $_e[ $_k ] ) ){

        $_e[ $_k ] = sprintf( '%02d', $_e[ $_k ] );

        $_k_i = array_search( $_k, array_keys( $_a ) );
        foreach( array_slice( $_a, $_k_i+1 ) as $__k => $__i ){
          $_e[ $__k ] = empty( $_e[ $__k ] ) ? "00" : $_e[ $__k ];
        }

      }
    }

    if ( count( $_e ) == 1 && !empty( $_e["seconds"] ) ){
      $_e = array(
        "minutes" => "0",
        "seconds" => $_e["seconds"]
      );
    }

    return array(
      "data" => $_e,
      "string" => implode( ":", $_e )
    );

  }
  public function time_in_future_hr($seconds, $args = []){

    if (time() > $seconds) 
    return $this->passed_time_hr( time() - $seconds )["string"] . " ago";
    return "in " . bof()->general->passed_time_hr( $seconds - time() )["string"];

  }
  public function passed_time_hr( $seconds, $args=[] ){

    $maximum = 1;
    $delimiter = ", ";
    $texts = array(
      "recent"  => "Just now",
      "second"  => "second",
			"minute"  => "minute",
			"hour"    => "hour",
			"day"     => "day",
			"month"   => "month",
			"year"    => "year",
      "seconds" => "seconds",
			"minutes" => "minutes",
			"hours"   => "hours",
			"days"    => "days",
			"months"  => "months",
			"years"   => "years",
    );
    $translate = false;
    extract( $args );

		if ( $seconds < 3 )
    return array(
      "string" => $translate ? bof()->object->language->turn( "recent", [], [ "lang" => "users" ] ) : $texts["recent"],
      "data" => null
    );

		$hr_data = [];
		$hr_strings = [];

		foreach( array_reverse( array(

			"second"  => 1,
			"minute"  => 60,
			"hour"    => 60*60,
			"day"     => 24*60*60,
			"month"   => 31*24*60*60,
			"year"    => 365*24*60*60

		) ) as $seperator_key => $seperator_interval ){

			$seperator_count = 0;
			if ( $seconds >= $seperator_interval ){

				$seperator_count = floor( $seconds / $seperator_interval );
				$seconds -= $seperator_count * $seperator_interval;

				$hr_data[ $seperator_key ][ "count" ] = $seperator_count;
				$hr_data[ $seperator_key ][ "text" ]  = $seperator_count == 1 ? $texts[$seperator_key] : $texts["{$seperator_key}s"];
				if ( count( $hr_strings ) < $maximum ){
          $hr_strings[] = "{$hr_data[ $seperator_key ][ "count" ]} " . ( $translate ? bof()->object->language->turn( $seperator_count == 1 ? $seperator_key : "{$seperator_key}s", [], [ "lang" => "users" ] ) : $hr_data[ $seperator_key ][ "text" ] );
        }

			}

		}

		return array(
			"string" => implode( $delimiter , $hr_strings ),
			"data" => $hr_data
		);

	}
  public function passed_time_from_time_hr( $time, $args=[] ){
    return $time ? bof()->general->passed_time_hr( time() - ( is_string( $time ) ? strtotime( $time ) : $time ), $args )["string"] : "-";
  }
  public function filesize_hr( $bytes, $args=[] ){

    $decimals = 1;
    extract( $args );

    $sz = 'BKMGTP';
    $factor = $bytes ? floor( ( strlen($bytes) - 1 ) / 3 ) : 0;
    $factor_sz = $factor ? $sz[ intval($factor) ] : "B";

    if ( $factor_sz == "B" )
    return $bytes . $factor_sz;

    return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . $factor_sz;

  }
  public function filesize_cr( $string ){

    $sz = 'BKMGTP';
    $string = trim( $string );
    for( $i=1; $i<strlen($sz); $i++ ){

      if ( substr( strtolower( $string ), -2 ) == strtolower( substr( $sz, $i, 1 ) . "b" ) )
      return floatval( str_replace( ",", "", substr( $string, 0, -2 ) ) ) * pow( 1000, $i );

      if ( substr( strtolower( $string ), -1 ) == strtolower( substr( $sz, $i, 1 ) ) )
      return floatval( str_replace( ",", "", substr( $string, 0, -1 ) ) ) * pow( 1000, $i );


    }
    return floatval( str_replace( ",", "", $string ) );

  }
  public function number_format_hr( $input ){

    $suffixes = array('', 'k', 'm', 'g', 't');
    $suffixIndex = 0;

    while(abs($input) >= 1000 && $suffixIndex < sizeof($suffixes))
    {
      $suffixIndex++;
      $input /= 1000;
    }

    return ( $input > 0 ? floor($input * 10) / 10 : ceil($input * 10) / 10 ) . $suffixes[$suffixIndex];

  }
  public function seconds_to_timestamp( $seconds ){
    return date( "Y-m-d H:i:s", $seconds );
  }
  public function number_format_float( $float ){

    return number_format( floor( $float ) ) . "." . ( substr( round( $float * 100 ), -2 ) );

  }
  public function mysql_timestamp( $time=null ){
    $time = $time ? $time : time();
    return date( "Y-m-d H:i:s", $time );
  }
  public function strtotime( $string, $turn=false, $args=[] ){

    $min_year = 1900;
    extract( $args );

    $_m = null;
    $_d = null;
    $_h = null;
    $_mi = null;
    $_s = null;
    $__p = 1;

    // 20200101
    if ( strlen( $string ) == 8 && ( is_numeric( $string ) || is_int( $string ) ) ){
      $_y = substr( $string, 0, 4 );
      $_m = substr( $string, 4, 2 );
      $_d = substr( $string, 6, 2 );
    }
    // 2020
    else if ( strlen( $string ) == 4 && ( is_numeric( $string ) || is_int( $string ) ) ){
      $_y = $string;
    }
    // 2020-01 2020/01 2020-1 2020/1
    else if ( strlen( $string ) == 6 || strlen( $string ) == 7 ){
      $__seperate = explode( " ", str_replace( [ "/", "-", "_", "\\", ",", ".", ":", "  " ], " ", $string ) );
      if ( count( $__seperate ) == 2 ? strlen( $__seperate[0] ) == 4 : false ){
        list( $_y, $_m ) = $__seperate;
      }
    }
    // 2020-01-01 2020/01/01 2020-1-1 2020/1/1 2020/1-1
    else if ( strlen( $string ) == 8 || strlen( $string ) == 9 || strlen( $string ) == 10 ) {
      $__seperate = explode( " ", str_replace( [ "/", "-", "_", "\\", ",", ".", ":", "  " ], " ", $string ) );
      if ( count( $__seperate ) == 3 && is_numeric( implode( "", $__seperate ) ) ? strlen( $__seperate[0] ) == 4 : false ){
        list( $_y, $_m, $_d ) = $__seperate;
      }
    }
    // 2020/01/01 00:00:00   2020-01-01 00/00/00
    else if ( strlen( $string ) == 19 ) {
      $__seperate = explode( " ", str_replace( [ "/", "-", "_", "\\", ",", ".", ":", "  " ], " ", $string ) );
      if ( count( $__seperate ) == 6 && is_numeric( implode( "", $__seperate ) ) ? strlen( $__seperate[0] ) == 4 && strlen( $__seperate[1] ) == 2 && strlen( $__seperate[2] ) == 2 && strlen( $__seperate[3] ) == 2 && strlen( $__seperate[4] ) == 2 && strlen( $__seperate[5] ) == 2 : false ){
        list( $_y, $_m, $_d, $_h, $_mi, $_s ) = $__seperate;
      }
    }

    // 2023-04-21T00:00:00Z
    elseif ( strlen( $string ) == 20 ){
      $__seperate = explode( ":", str_replace( [ "T", "-" ], ":", substr( $string, 0, 19 ) ) );
      if ( count( $__seperate ) == 6 && is_numeric( implode( "", $__seperate ) ) ? strlen( $__seperate[0] ) == 4 && strlen( $__seperate[1] ) == 2 && strlen( $__seperate[2] ) == 2 && strlen( $__seperate[3] ) == 2 && strlen( $__seperate[4] ) == 2 && strlen( $__seperate[5] ) == 2 : false ){
        list( $_y, $_m, $_d, $_h, $_mi, $_s ) = $__seperate;
      }
    }

    if ( empty( $_y ) ? true : intval( $_y ) > 2030 || intval( $_y ) < $min_year )
    return false;

    if ( empty( $_m ) ? false : intval( $_m ) < 1 || intval( $_m ) > 12 )
    return false;
    elseif ( !empty( $_m ) ) $__p = 2;

    if ( empty( $_d ) ? false : intval( $_d ) < 1 || intval( $_d ) > 31 )
    return false;
    elseif ( !empty( $_d ) ) $__p = 3;

    if ( empty( $_h ) ? false : intval( $_h ) < 0 || intval( $_h ) > 23 )
    return false;
    elseif ( !empty( $_h ) ) $__p = 4;

    if ( empty( $_mi ) ? false : intval( $_mi ) < 0 || intval( $_mi ) > 59 )
    return false;
    elseif ( !empty( $_mi ) ) $__p = 5;

    if ( empty( $_s ) ? false : intval( $_s ) < 0 || intval( $_s ) > 59 )
    return false;
    elseif ( !empty( $_s ) ) $__p = 6;

    $_m  = empty( $_m ) ? "01" : $_m;
    $_d  = empty( $_d ) ? "01" : $_d;
    $_h  = empty( $_h ) ? "00" : $_h;
    $_mi = empty( $_mi ) ? "00" : $_mi;
    $_s  = empty( $_s ) ? "00" : $_s;
    $_m  = strlen( $_m ) == 1 ? "0{$_m}" : $_m;
    $_d  = strlen( $_d ) == 1 ? "0{$_d}" : $_d;

    $_fs = "{$_y}-{$_m}-{$_d} {$_h}:{$_mi}:{$_s}";
    return [ $turn ? strtotime( $_fs ) : $_fs, $__p ];

  }
  public function explode_by_line( $string ){
    return preg_split( '/\r\n|\r|\n/', $string );
  }
  public function numeric( $mixed ){

    if ( !is_numeric( $mixed ) && !is_int( $mixed ) )
    return false;

    if ( !preg_match( '/^[0-9]+$/i', $mixed ) )
    return false;

    return true;

  }
  public function bofify_options( $array, $keyFrom="key", $valueFrom="value" ){

    $newArray = [];

    if ( $array ? is_array( $array ) : false ){
      foreach( $array as $k => $v ){
        $newArray[] = [ ( $keyFrom == "key" ? $k : $v ), ( $valueFrom == "key" ? $k : $v ) ];
      }
    }

    return $newArray;

  }
  public function daycode(){
    return date("ymd");
  }
  public function extractIPRange( $ipAddress ){
    if ( !$ipAddress ) return false;
    $ipAddressParsed = explode( ".", $ipAddress );
    if ( count( $ipAddressParsed ) != 4 ) return false;
    foreach( $ipAddressParsed as $ipAddressPart ){
      if ( $ipAddressPart < 0 || $ipAddressPart > 255 )
      return false;
    }
    return implode( ".", array_slice( $ipAddressParsed, 0, 3 ) );
  }
  public function timestamp_difference( $timestamp, $oper, $difference ){

    if ( empty( $timestamp ) )
    $time = 0;
    elseif( is_int( $timestamp ) )
    $time = $timestamp;
    else
    $time = strtotime( $timestamp );

    if ( !$time ){
      if ( $oper == "+" )
      return false;
      return true;
    }

    $diff = abs( time() - $time );

    if ( $oper == "+" )
    return $diff > $difference;
    return $difference > $diff;

  }

  public function _get_server_maximum_upload_size( $hr=false ){

    $max_size = -1;

    $post_max_size = $this->filesize_cr(ini_get('post_max_size'));
    if ($post_max_size > 0)
    $max_size = $post_max_size;

    $upload_max = $this->filesize_cr(ini_get('upload_max_filesize'));
    if ($upload_max > 0 && $upload_max < $max_size)
    $max_size = $upload_max;

    return $hr ? $this->filesize_hr( $max_size ) : $max_size;

  }
  public function scandir( $dir, $args=[] ){
    return bof()->file->scandir( $dir, $args );
  }
  public function make_code( $string, $regex = "\p{L}0-9", $max_length = 100, $fallOnFail = false ){

    $o_string = $string;
    $strings = is_array( $string ) ? $string : [ $string ];
    $codes = [];

    foreach( $strings as $string ){

      $generic_code = null;
      if ( $string ){
        $generic_code =
        mb_substr(
          preg_replace(
            '/[^'.$regex.']/u',
            '',
            mb_strtolower( htmlspecialchars_decode( $string, ENT_QUOTES ), "UTF-8" )
          ),
          0,
          $max_length,
          "UTF-8"
        );
      }

      if ( $generic_code )
      $codes[] = $generic_code;

      elseif ( $fallOnFail )
      return false;

      elseif ( $generic_code )
      $codes[] = hash( "crc32", $string );

    }

    $full_code = implode( "_", $codes );

    if ( mb_strlen( $full_code, "UTF-8" ) > $max_length ? ( is_array( $o_string ) ? count( $o_string ) >= 3 : false ) : false ){

      $new_codes = [];
      foreach( $o_string as $string ){
        if ( !$string ) continue;
        $new_codes[] = mb_substr(
          preg_replace(
            '/[^'.$regex.']/u',
            '',
            mb_strtolower( htmlspecialchars_decode( $string, ENT_QUOTES ), "UTF-8" )
          ),
          0,
          floor( $max_length / count( $o_string ) ),
          "UTF-8"
        );
      }
      $full_code = implode( "_", $new_codes );
    }

    return mb_substr(
      $full_code,
      0,
      $max_length,
      "UTF-8"
    );

  }
  public function make_url( $string ){

		$URL = mb_strtolower( mb_substr( $string, 0, 100, "UTF-8" ), "UTF-8" );
		$URL = str_replace( [ " ", "___" , "__" ], "_", bof()->general->make_code( $URL, "\p{L}0-9\-_ " ) );
		if ( empty( $URL ) ? true : strlen( $URL ) <= 2 ) $URL = uniqid();

		return $URL;

	}

  public function startsWith( $haystack, $needle ){
    $length = strlen( $needle );
    if ( $length == 0 ) return false;
    return substr( $haystack, 0, $length ) == $needle;
  }
  public function startsWidth( $haystack, $needle ){
    return $this->startsWith( $haystack, $needle );
  }
  public function endsWith( $haystack, $needle ){
    $length = strlen( $needle );
    if ( $length == 0 ) return false;
    return substr( $haystack, -$length ) == $needle;
  }
  public function endsWidth( $haystack, $needle ){
    return $this->endsWith( $haystack, $needle );
  }

  // Upgrades http:// stream/media URLs to https://. Modern clients
  // (Android 9+, iOS ATS) block cleartext, so an http URL would fail
  // anyway — upgrading gives it a chance to work.
  public function https_url( $url ){

    if ( !is_string( $url ) || stripos( $url, "http://" ) !== 0 )
    return $url;

    return "https://" . substr( $url, 7 );

  }

  public function exec( $bin, $command='' ){

    $stream = null;

    $descriptor_spec = array(
      0 => [ 'pipe', 'r' ],
      1 => [ 'pipe', 'w' ]
    );

    $process = proc_open( $bin . ' 2>&1', $descriptor_spec, $pipes );

    if ( is_resource( $process ) ){

      fwrite( $pipes[0], $command );
      fclose( $pipes[0] );
      $stream = stream_get_contents( $pipes[1] );
      fclose( $pipes[1] );
      proc_close( $process );

    }

    return $stream;

  }

}

?>
