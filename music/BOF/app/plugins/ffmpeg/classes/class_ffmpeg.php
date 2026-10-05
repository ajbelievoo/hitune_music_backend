<?php

if ( !defined( "bof_root" ) ) die;

class ffmpeg {

  protected $client = false;

  public function getClient( $force=false, $forcePath=null ){

    $ffmpeg_path = $forcePath ? $forcePath : bof()->object->db_setting->get( "ffmpeg_path" );
    $ffmpeg_static = bof()->object->db_setting->get( "ffmpeg_static" );
    $ffmpeg_api = bof()->object->db_setting->get( "ffmpeg_api" );

    if ( ( !$force && $ffmpeg_static ) || ( $force === "static" ) )
    return ffmpeg_path . '/ffmpeg-git-20230313-amd64-static/ffmpeg';

    // if ( $ffmpeg_api )
    // return "API";

    if ( ( !$force && $ffmpeg_path ) || ( $force === "native" ) )
    return htmlspecialchars_decode( $ffmpeg_path );

    return false;

  }

  protected $error = null;
  public function setError( $string ){
    $this->error = $string;
  }
  public function getError(){
    return $this->error ? str_replace( PHP_EOL, "<br>", $this->error ) : null;
  }

  protected $command = null;
  public function setCommand( $string ){
    $this->command = $string;
  }
  public function getCommand(){
    return $this->command;
  }

  public function hls_encrypt( $path, $args=[] ){

    $type = "audio";
    $keyinfo_path = null;
    $destination = null;
    $filename = null;
    $keep_real_file = false;
    extract( $args );
    $destination_hls = "{$destination}/{$filename}_hls";
    $hls_slice_extension = $type == "audio" ? "ts" : "ts";
    $hls_slice_duration = $type == "audio" ? 20 : 60;
    $hls_extension = "m3u8";

    $client = $this->getClient();
    if ( !$client ) return false;

    if ( $client == "API" )
    die("TODO");

    if ( !is_dir( $destination_hls ) )
    mkdir( $destination_hls );
    bof()->id3->write_tags( $path, [] );

    $ffmpeg_command = "{$client} -y -i \"{$path}\" -preset veryslow -qp 0 -crf 0 -f hls -hls_playlist_type vod -hls_segment_filename \"{$destination_hls}/slice_%d.{$hls_slice_extension}\" -hls_time {$hls_slice_duration} -hls_key_info_file \"{$keyinfo_path}\" -hls_segment_type mpegts \"{$destination_hls}/map.{$hls_extension}\"";
    $ffmpeg_command = str_replace( [ PHP_EOL ], " ", $ffmpeg_command );

    $this->setCommand( $ffmpeg_command );
    $this->setError( bof()->general->exec( $ffmpeg_command ) );

    $_slices = array();
    $_slices_size = filesize( "{$destination_hls}/map.{$hls_extension}" );
    $_slices_count = 0;
    $_slice_i = 0;

    while( $_slice_i >= 0 ){
      if ( is_file( "{$destination_hls}/slice_{$_slice_i}.{$hls_slice_extension}" ) ){
        $_slices[] = bof()->object->file->clean_path( realpath( "{$destination_hls}/slice_{$_slice_i}.{$hls_slice_extension}" ), true );
        $_slices_size += filesize( "{$destination_hls}/slice_{$_slice_i}.{$hls_slice_extension}" );
        $_slice_i++;
        $_slices_count++;
      }
      else {
        $_slice_i = -1;
      }
    }

    if ( !$_slices_size )
    return false;

    unlink( $keyinfo_path );
    if ( !$keep_real_file )
    unlink( $path );

    return array(
      "slices" => $_slices,
      "slices_count" => $_slices_count,
      "slices_size" => $_slices_size,
      "map" => bof()->object->file->clean_path( realpath( "{$destination_hls}/map.{$hls_extension}" ), true ),
      "real_file" => $keep_real_file ? $path : false
    );

  }
  public function convert_to_mp3( $path, $object, $args=[] ){

    $dir = null;
    $name = null;
    $ab = "320k";
    $ca = "";
    $cut = "";
    $offset = "";
    $force_client = false;
    $force_path = null;
    extract( $args );

    $client = $this->getClient( $force_client, $force_path );
    if ( !$client ) return false;

    if ( $client == "API" )
    die("TODO");

    if ( $ca )
    $ca = " -c:a {$ca}";
    else
    $ca = "";

    if ( $ab )
    $ab = " -ab {$ab}";
    else
    $ab = "";

    if ( $cut )
    $cut = " -t {$cut} ";
    else
    $cut = "";

    if ( $offset )
    $offset = " -ss {$offset} ";
    else
    $offset = "";

    $path = str_replace( '$', '\$', escapeshellarg( $path ) );
    $output = str_replace( '$', '\$', escapeshellarg( "{$dir}/{$name}.mp3" ) );

    $ffmpeg_command = ( "\"{$client}\" -y -i {$path} {$offset} {$ca} {$ab} {$cut} -map_metadata 0 -id3v2_version 3 {$output} " );
    $ffmpeg_command = str_replace( [ PHP_EOL ], " ", $ffmpeg_command );

    $this->setCommand( $ffmpeg_command );
    $this->setError( bof()->general->exec( $ffmpeg_command ) );

    if ( is_file( "{$dir}/{$name}.mp3" ) )
    return realpath( "{$dir}/{$name}.mp3" );

    return false;

  }
  public function convert_to_mp4( $path, $object, $args=[] ){

    $dir = null;
    $name = null;
    $scale = "1080";
    $scale_format = "yuv420p";
    $crf = "23";
    $preset = "medium";
    $cv = "libx264";
    $ca = "aac";
    $ba = "128k";
    extract( $args );

    if ( $scale ){
      $scale = $scale % 2 == 1 ? $scale + 1 : $scale;
      $scale = " -vf scale=-2:{$scale}" . ( $scale_format ? ",format={$scale_format}" : "" );
    }

    if ( $crf )
    $crf = " -crf {$crf}";

    if ( $preset )
    $preset = " -preset {$preset}";

    if ( $ba )
    $ba = " -b:a {$ba}";

    if ( $ca )
    $ca = " -c:a {$ca}";

    if ( $cv )
    $cv = " -c:v {$cv}";

    $client = $this->getClient();
    if ( !$client ) return false;

    if ( $client == "API" )
    die("TODO");

    $ffmpeg_command = ( "\"{$client}\" -y -i \"{$path}\" {$cv} {$ca} {$scale} {$crf} {$preset} {$ba} \"{$dir}/{$name}.mp4\" " );
    $ffmpeg_command = str_replace( [ PHP_EOL ], " ", $ffmpeg_command );

    $this->setCommand( $ffmpeg_command );
    $this->setError( bof()->general->exec( $ffmpeg_command ) );

    if ( is_file( "{$dir}/{$name}.mp4" ) )
    return realpath( "{$dir}/{$name}.mp4" );

    return false;

  }
  public function merge_to_mp3( $pathes, $args=[] ){

    $client = $this->getClient();
    if ( !$client ) return false;

    $output = false;
    extract( $args );

    for( $i=0; $i<count($pathes); $i++ ){
      $ics[] = "-i '{$pathes[$i]}'";
      $fcs[] = "[{$i}:a]";
    }

    $ffmpeg_command = "\"{$client}\" ".(implode(" ",$ics))." -filter_complex \"".(implode(" ",$fcs))."concat=n=".(count($pathes)).":v=0:a=1[outa]\" -map \"[outa]\" -c:a libmp3lame '{$output}'";

    $this->setCommand( $ffmpeg_command );
    $this->setError( bof()->general->exec( $ffmpeg_command ) );

    if ( is_file( $output ) )
    return realpath( $output );

    return false;

  }

  public function measure_loudness( $path ){

    $client = $this->getClient();
    if ( !$client ) return false;

    if ( !is_file( $path ) )
    return false;

    $ffmpeg_command = "\"{$client}\" -hide_banner -nostats -i " . escapeshellarg( $path ) . " -af ebur128=peak=true -f null -";
    $this->setCommand( $ffmpeg_command );
    $output = bof()->general->exec( $ffmpeg_command );
    $this->setError( $output );

    if ( empty( $output ) || !is_string( $output ) )
    return false;

    // Parse the ebur128 summary block (last occurrence wins)
    $lufs = null;
    $peak = null;

    if ( preg_match_all( "/^\s*I:\s+(-?[0-9]+(?:\.[0-9]+)?)\s+LUFS/m", $output, $m ) )
    $lufs = (float) end( $m[1] );

    if ( preg_match_all( "/^\s*Peak:\s+(-?[0-9]+(?:\.[0-9]+)?)\s+dBFS/m", $output, $m ) )
    $peak = (float) end( $m[1] );

    if ( $lufs === null )
    return false;

    return array(
      "lufs" => $lufs,
      "peak_db" => $peak
    );

  }

  public function video_getQuality( $path ){

    $this->getClient();

    $ffprobe = FFMpeg\FFProbe::create();
    $width = $ffprobe
    ->streams( $path )
    ->videos()
    ->first()
    ->get('width');

    if ( $width >= 3840 )
    return "4k";

    if ( $width >= 1920 )
    return "1080p";

    if ( $width >= 1280 )
    return "720p";

    return "480p";

  }

}

?>
