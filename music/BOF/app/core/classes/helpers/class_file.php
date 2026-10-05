<?php

if ( !defined( "bof_root" ) ) die;

class file extends bof_type_class {

  public function save( $file, $args=[] ){

    $directory = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" );
    $sub_directory = null;
    $remove_src = true;
    $overwrite = true;
    $filename = pathinfo( $file, PATHINFO_FILENAME );
    $extension = pathinfo( $file, PATHINFO_EXTENSION );
    extract( $args );

    $filename = bof()->file->_filter_filename( $filename );

    $full_directory = $this->mkdir( $this->mkdir( $directory ) . ( $sub_directory ? $sub_directory : "" ) );
    $full_path = "{$full_directory}/{$filename}.{$extension}";

    if ( $overwrite === false ? file_exists( $full_path ) : false ){
      $full_path = "{$full_directory}/{$filename}_".uniqid().".{$extension}";
    }

    copy( $file, $full_path );

    if ( $remove_src )
    unlink( $file );

    return str_replace( base_root, "", realpath( $full_path ) );

  }
  public function mkdir( $path, $args=[] ){

    $recursive = true;
    $mode = 0755;
    extract( $args );

    if ( !is_dir( $path ) ){
      mkdir( $path, $mode, $recursive );
      chmod( $path, $mode );
    }
    return realpath( $path ) . "/";

  }
  public function extract( $path, $args=[] ){

    $extension = pathinfo( $path, PATHINFO_EXTENSION );
    $directory = pathinfo( $path, PATHINFO_FILENAME );
    extract( $args );

    $directory = $this->mkdir( $directory );

    if ( $extension == "zip" ){

      $xmlZip = new ZipArchive();
      if ( $xmlZip->open( $path ) !== true )
      return false;

      $xmlZip->extractTo( $directory );
      return true;

    }
    elseif ( $extension == "rar" ) {

      if ( !( $rar_file = rar_open( $path ) ) )
      return false;

      $entries = rar_list( $rar_file );
      if ( !$entries ){
        rar_close( $rar_file );
        return false;
      }

      foreach( $entries as $entry ) {
        $entry->extract( $directory );
      }

      rar_close( $rar_file );
      return true;

    }

    return false;

  }
  public function scandir( $dir, $args=[] ){

    $search_by_extension = null;
    $no_base = false;
    extract( $args );
    $search_by_extension = $search_by_extension ? ( is_array( $search_by_extension ) ? $search_by_extension : [ $search_by_extension ] ) : false;

    $dir = realpath( $dir );
    $dirs = [ $dir ];
    $_scanned_dirs = [];
    $_dirs_to_scan = [ $dir ];
    $files = [];
    $search = [];

    while( !empty( $_dirs_to_scan ) ){

      $_dir_to_scan = array_shift( $_dirs_to_scan );

      if( in_array( $_dir_to_scan, $_scanned_dirs ) )
      continue;

      if ( !is_dir( $_dir_to_scan ) )
      continue;

      $ents = scandir( $_dir_to_scan );
      foreach( $ents as $ent ){

        if ( in_array( $ent, [ ".", ".." ] ) ) continue;
        $_ent = realpath( $_dir_to_scan . "/{$ent}"  );

        if ( is_dir( $_ent ) ){
          $dirs[] = $_ent;
          $_dirs_to_scan[] = $_ent;
        }
        else {

          $file_name = $_ent;
          if ( $no_base )
          $file_name = substr( $file_name, strlen( $dir ) + 1 );

          $files[] = $file_name;

          if ( $search_by_extension ){
            if ( in_array( pathinfo( $_ent, PATHINFO_EXTENSION ), $search_by_extension ) )
            $search[] = $_ent;
          }

        }

        $_scanned_dirs[] = $_dir_to_scan;

      }

    }

    return array(
      "files" => $files,
      "dirs" => $dirs,
      "search" => $search
    );

  }
  public function rmdir( $dir, $args=[] ){

    $ents = $this->scandir( $dir );

    if ( !empty( $ents["files"] ) ){
      foreach( $ents["files"] as $_file )
      unlink( $_file );
    }

    if ( !empty( $ents["dirs"] ) ){
      foreach( array_reverse( $ents["dirs"] ) as $_dir ){
        if( is_dir( $_dir ) )
        rmdir( $_dir );
      }
    }

  }
  public function unzip( $zip_file, $destionation, $args=[] ){

    try {
      $try = $this->unzip_e( $zip_file, $destionation, $args );
    } catch ( Exception $err ){
      return false;
    }

    return $try;

  }
  public function unzip_e( $zip_file, $destionation, $args=[] ){

    $remove_after = false;
    extract( $args );

    $zip = new ZipArchive;
    $res = $zip->open( $zip_file, ZipArchive::CHECKCONS );
    if ( $res !== true )
    throw new Exception( "Opening zip file failed: {$res}" );

    $try = $zip->extractTo( bof()->file->mkdir( $destionation ) );
    $zip->close();

    if ( $remove_after )
    unlink( $zip_file );

    return $try;

  }
  public function zip( $destination_zip, $source, $args=[] ){

    $remove_after = false;
    extract( $args );

    $zip = new ZipArchive;
    $res = $zip->open( $destination_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE );
    if ( $res !== true ) return false;

    foreach( $this->scandir( $source, [ "no_base" => true ] )["files"] as $_file ){
      $zip->addFile( realpath( $source . "/" . $_file ), $_file );
    }

    $zip->close();

    if ( $remove_after )
    $this->rmdir( $source );

    return true;

  }

  public function _filter_filename( $filename ) {

    // writer: https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    $filename = preg_replace(
        '~
        [<>:"/\\\|?*]|
        [\x00-\x1F]|
        [\x7F\xA0\xAD]|
        [#\[\]@!$&\'()+,;=]|
        [{}^\~`]
        ~x',
        '-', $filename);

    $filename = ltrim($filename, '.-');
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $filename = mb_strcut( pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename) );
    $filename = mb_strtolower( $filename );
    $filename = trim( $filename );
    $filename = str_replace( [ "---", "--", "  -  ", "  - ", " -  ", " - ", " -", "- " ], "-", $filename );
    $filename = str_replace( [ "    ", "   ", "  " ], " ", $filename );

    if ( empty( $filename ) ? true : mb_strlen( $filename, "utf-8" ) < 7 ) $filename = uniqid();
    if ( $ext ) $filename .= '.' . $ext;

    return $filename;

  }

}

?>
