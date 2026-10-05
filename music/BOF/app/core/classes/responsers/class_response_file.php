<?php

if ( !defined( "bof_root" ) ) die;

class response_file {

  public function set( $args ){

    if ( $args )
    bof()->execute->set_data( "file", $args );

  }
  public function display(){

    $data = bof()->execute->get_data( "file" );
    if ( empty( $data["file"] ) && empty( $data["string_data"] )&& empty( $data["path"] ) ) return false;

    $mime_type = null;
    $string_data = null;
    $path = null;
    extract( $data );

    if ( $path ? file_exists( $path ) : false ){
      $mime_type = $this->get_mime_type( $path );
      $string_data = file_get_contents( $path );
    }

    if ( !$mime_type || !$string_data )
    return false;

    header( 'Content-Type: ' . $mime_type );
    echo $string_data;

  }
  public function get_mime_type( $path ){

    if ( !$path ) return false;

    $extension = pathinfo( $path, PATHINFO_EXTENSION );

    if ( $extension == "css" )
    $mime_type = "text/css";

    elseif( $extension == "js" )
    $mime_type = "application/javascript";

    else
    $mime_type = mime_content_type( $path );

    return $mime_type;

  }

}

?>
