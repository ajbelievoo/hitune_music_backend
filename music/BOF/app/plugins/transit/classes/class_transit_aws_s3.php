<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_aws_s3 extends bof_type_class {

  protected $platform = "amazon";
  protected $client = null;
  public $data = [];

  public function load(){}
  protected function getClientData(){

    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region'   => $server_data["region"],
      'credentials' => array(
        'key'    => $server_data["key"],
        'secret' => $server_data["secret"],
      )
    );

    if ( !empty( $server_data["endpoint"] ) )
    $_d['endpoint'] = $server_data["endpoint"];

    return $_d;

  }
  protected function getClient(){

    if ( $this->client )
    return $this->client;

    require_once( bof_root . "/app/core/third/aws-sdk-php-3.272.2/autoload.php" );
    $this->client = new Aws\S3\S3Client( $this->getClientData() );
    return $this->client;

  }

  public function connect(){

    $server = bof()->transit->get_storage();
		$this->data = $server["data_decoded"];
    $this->client = null;

    return true;

  }

  public function upload( $_source, $_remote, $data ){

		if ( !( $client = $this->getClient() ) )
		return [false,false];

    $server_data = $this->data;

    $putArray = array(
      'Bucket' => $server_data["bucket"],
      'Key' => $_remote,
      'Body' => fopen( $_source, 'r' ),
      'ACL' => "public-read",
      'ContentDisposition' => 'attachment',
    );

    if ( 
      $this->platform == "backblaze" || 
      ( !empty( $server_data["endpoint"] ) ? preg_match( "/backblazeb2.com/", $server_data["endpoint"] ) : false )
    )
    unset( $putArray["ACL"] );

    try {
      $d = $client->putObject( $putArray );
    } catch (Aws\S3\Exception\S3Exception $e) {
      if ( bof()->transit->get_debug() ){
        throw new Exception( $e->getMessage() );
      }
      return [false,false];
    }

		return [ $this->exists( $_remote ), $_remote ];

	}
	protected function exists( $filename ){

		if ( !( $client = $this->getClient() ) )
		return false;

    $server_data = $this->data;

		if ( $client->doesObjectExist( $server_data["bucket"], $filename ) )
		return true;
		return false;

	}
	public function delete( $filename ){

		if ( !( $client = $this->getClient() ) )
		return false;

		if ( !$this->exists( $filename ) )
		return true;

    $server_data = $this->data;

    try {
      $client->deleteObject( array(
        'Bucket' => $server_data["bucket"],
        'Key'    => $filename,
      ) );
    } catch (Aws\S3\Exception\S3Exception $e) {
      return false;
    }

		if ( !$this->exists( $filename ) )
		return true;
		return false;

	}

}

?>
