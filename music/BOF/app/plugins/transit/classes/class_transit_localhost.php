<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_localhost extends bof_type_class {

  public function url( $path ){}
  public function connect(){
    return true;
  }
  public function upload( $_source, $_remote, $data ){

    $_remote_full = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory", "files" ) . "/" . $_remote;
    bof()->file->mkdir( dirname( $_remote_full ) );
    rename( $_source, $_remote_full );
    return [ true, bof()->object->file->clean_path( realpath( $_remote_full ), true ) ];

  }
  public function delete( $path ){

    $removed = null;

    try{
      unlink( base_root . "/" . $path );
      $removed = true;
    } catch( Exception $e ){
      $removed = false;
    }

    return $removed;

  }
  public function dir_create( $path ){

    return is_dir( base_root . "/" . $path );

  }
  public function dir_exists( $path ){

    return is_dir( base_root . "/" . $path );

  }
  public function dir_list( $path ){

    $dir_content = scandir( base_root . "/" . $path );

    foreach( $dir_content as $_c ){
      if ( strlen( $_c ) > 2 )
      $_contents = $_c;
    }

    return !empty( $_contents ) ? $_contents : [];

  }
  public function dir_remove( $path ){

    rmdir( $path );
    return true;

  }

}

?>
