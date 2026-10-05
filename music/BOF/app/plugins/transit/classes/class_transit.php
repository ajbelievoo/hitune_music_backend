<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit extends bof_type_class {

  public $debug = false;
  public $file = null;
  public $storage = null;

  public function set_debug( $value ){

    $this->debug = $value;
    return $this;

  }
  public function get_debug(){
    return $this->debug;
  }
  public function set_file( $args ){

    if ( is_string( $args ) )
    $args = [ "path" => $args ];

    $this->file = $args;
    return $this;

  }
  public function set_storage( $storage_object ){
    $this->storage = $storage_object;
    return $this;
  }
  public function get_storage(){
    return $this->storage;
  }

  protected function _storage_connect(){

    if ( in_array( $this->storage["type"], [ "wasabi", "cloudflare", "backblaze", "storj", "cdn777", "mega", "minio" ], true ) )
    bof()->transit_aws_s3->load();

    elseif ( $this->storage["type"] == "bunny" )
    bof()->transit_ftp->load();

    return bof()->__get( "transit_" . $this->storage["type"] )->connect();

  }

  public function url(){

    $storage = $this->storage;
    $file = $this->file;

    if ( $storage["type"] == "ftp" )
    return $storage["data_decoded"]["web_address"] . $file["path"];

    else if ( $storage["type"] == "localhost" ){
      $cdn_url = bof()->object->db_setting->get( "cdn_url" );
      $base = $cdn_url ? rtrim( $cdn_url, "/" ) . "/" : web_address;
      return $base . bof()->object->file->clean_path( $file["path"] );
    }

    else if ( $storage["type"] == "aws_s3" )
    return (
      !empty( $storage["data_decoded"]["address"] ) ?
      $storage["data_decoded"]["address"] :
      (
        $storage["data_decoded"]["endpoint"] ?
          $storage["data_decoded"]["endpoint"] . "/" . $storage["data_decoded"]["bucket"] :
        "https://{$storage["data_decoded"]["bucket"]}.s3.{$storage["data_decoded"]["region"]}.amazonaws.com"
      )
    ) . "/" . $file["path"];

    else if ( $storage["type"] == "wasabi" )
    return "https://s3.{$storage["data_decoded"]["region"]}.wasabisys.com/{$storage["data_decoded"]["bucket"]}/{$file["path"]}";

    else if ( $storage["type"] == "minio" )
    return "{$storage["data_decoded"]["endpoint"]}/{$storage["data_decoded"]["bucket"]}/{$file["path"]}";

    else if ( $storage["type"] == "bunny" )
    return "https://{$storage["data_decoded"]["address"]}/{$file["path"]}";

    else if ( $storage["type"] == "storj" )
    return $storage["data_decoded"]["bucket_url"] . "/" . $file["path"] . "?wrap=0";

    else if ( $storage["type"] == "backblaze" )
    return "https://{$storage["data_decoded"]["bucket"]}.{$storage["data_decoded"]["endpoint"]}/{$file["path"]}";

    else if ( $storage["type"] == "cloudflare" ){
      if ( empty( $storage["data_decoded"]["address"] ) )
      return "https://{$storage["data_decoded"]["bucket"]}.{$storage["data_decoded"]["endpoint"]}/{$file["path"]}";
      return "https://{$storage["data_decoded"]["address"]}/{$file["path"]}";
    }

    else if ( $storage["type"] == "cdn777" )
    return "https://{$storage["data_decoded"]["bucket"]}.{$storage["data_decoded"]["bucket_url"]}/{$file["path"]}";

    else if ( $storage["type"] == "mega" )
    return "https://{$storage["data_decoded"]["bucket"]}.s3.{$storage["data_decoded"]["region"]}.s4.mega.io/{$file["path"]}";

  }

  public function move( $args=[] ){

    $storage = $this->storage;
    $file = $this->file;
    if ( !$file ) return false;
    if ( !$storage ) return false;
    $connect = $this->_storage_connect();
    if ( $connect !== true ) return $connect;

    $filename = pathinfo( $file["path"], PATHINFO_FILENAME );
    $extension = pathinfo( $file["path"], PATHINFO_EXTENSION );
    $dirname = null;
    $subdir = null;
    $by_date = true;
    $random_subdir = true;
    extract( $args );

    if ( !$subdir && $random_subdir )
    $subdir = uniqid();

    if ( $by_date )
    $subdir = date("y/m/d") . ( $subdir ? "/" . $subdir : "" );

    $_dir = $dirname . ( $subdir ? "/" . $subdir : "" );
    $_remote_path = $_dir . "/" . $filename . "." . $extension;
    $_source_path = realpath( base_root . "/" . $file["path"] );

    $upload = bof()->__get( "transit_" . $storage["type"] )->upload( $_source_path, $_remote_path, $storage["data_decoded"] );

    if ( file_exists( $_source_path ) ){
      try {
        unlink( $_source_path );
      } catch( Exception|bofException $err ){
      }
    }

    return $upload;

  }
  public function delete(){

    $storage = $this->storage;
    $file = $this->file;
    if ( !$file ) return false;
    if ( !$storage ) return false;
    $connect = $this->_storage_connect();
    if ( $connect !== true ) return $connect;

    return bof()->__get( "transit_" . $storage["type"] )->delete( $file["path"] );

  }

}

?>
