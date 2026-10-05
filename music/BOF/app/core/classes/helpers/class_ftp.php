<?php

if ( !defined( "root" ) ) die;

class ftp extends bof_type_class {

  protected $debug = false;
  protected $client = null;
  protected $client_connect_time = null;
  protected $client_reconnect_time = 20;
  protected $data = [
    "addr" => null,
    "port" => null,
    "user" => null,
    "pass" => null,
    "type" => null,
    "path" => ""
  ];
  protected $base = null;

  public function __construct( $debug=false ){
    $this->debug = $debug;
  }
  public function __destruct(){
    $this->close();
  }
  public function set_debug( $value ){
    $this->debug = $value;
  }
  public function set_data( $args ){

    foreach( $this->data as $_k => $_v )
    if ( !isset( $args[ $_k ] ) ) fall( "FTP: Set_data: Invalid {$_k}" );

    $this->data = $args;

    if ( $this->data["path"] )
    $this->data["path"] = substr( $this->data["path"], -1 ) == "/" ? $this->data["path"] : $this->data["path"] . "/";

    $this->resetClient();

  }
	public function getClient( $fresh = false ){

    // Force reset?
		if ( $fresh )
    $this->resetClient();

    // Timed out connection?
    if ( $this->client_connect_time ? time() > $this->client_connect_time + $this->client_reconnect_time : false )
    $this->resetClient();

    // Active connection ready?
		if ( !empty( $this->client ) )
    return $this->client;

    if ( $this->data["type"] == "sshftp" ){

      require_once( realpath( bof_root . "/app/core/third/kotomono7_php_ssh2.php" ) );

      $ftp = new SFTPConnection( $this->data["addr"] , $this->data["port"] );
      $ftp->login( $this->data["user"] , $this->data["pass"] );

    }
    else {

      require_once( realpath( bof_root . "/app/core/third/nicolab-php-ftp-client/autoload.php" ) );

      $ftp = new \FtpClient\FtpClient();
      $ftp->connect( $this->data["addr"], $this->data["type"] == "sslftp", $this->data["port"], 60 );
      $ftp->login( $this->data["user"], $this->data["pass"] );

    }

		$this->client = $ftp;
    $this->client_connect_time = time();

    if ( $this->base === null ){
      $this->base = $this->client->pwd();
      if ( $this->debug )
      echo "Start Location: {$this->base}\n";
    }

		return $this->client;

	}
  public function resetClient(){

    $this->client = false;
    $this->client_connect_time = null;

  }
  public function test(){

    if ( !in_array( $this->data["type"], [ "sslftp", "sshftp", "ftp" ], true ) )
    return "FTP {$this->data["type"]} type is not supported";

    if ( $this->data["type"] != "sshftp" && !extension_loaded("ftp") )
    return "FTP extension is not loaded by PHP";

    try {
      $this->getClient();
    } catch( Exception $err ){
      return $err->getMessage();
    }

    return true;

  }
  public function close(){

    if ( ( $this->data["type"] == "sslftp" || $this->data["type"] == "ftp" ) && $this->client ){
      try {
        $this->client->close();
      } catch( Warning|Exception $err ){}
    }
  }

	public function upload( $local_file, $remote_file ){

    if ( $this->data["type"] == "sshftp" )
    return $this->ssh_upload( $local_file, $remote_file );

    $remote_dir = $this->data["path"] . dirname( $remote_file );
    $remote_name = basename( $remote_file );

		$client = $this->getClient();

    if ( $this->debug )
    echo "Uploading: FILE:{$local_file} to DIR:{$remote_dir} FILE:{$remote_name}\n";

    // create remote dir
		if ( !$this->dir_exists( $remote_dir ) ){

      if ( $this->debug )
      echo "Uploading: DIR:{$remote_dir} deson't exist, try to create it\n";

			$this->dir_create( $remote_dir, true );
			if ( !$this->dir_exists( $remote_dir ) )
			throw new Exception("Failed to create directory {$remote_dir}");

      if ( $this->debug )
      echo "Uploading: DIR:{$remote_dir} didn't exist, we created it\n";

		} else {
      if ( $this->debug )
      echo "Uploading: DIR:{$remote_dir} exists\n";
    }

    // goto remote dir
    $client->chdir( $remote_dir );
    if ( $this->debug )
    echo "Uploading: PWD: ".$client->pwd()."\n";

    $client->getWrapper()->set_option(FTP_USEPASVADDRESS, false);
    $pasv = $client->getWrapper()->pasv( true );
    if ( $this->debug )
    echo "Uploading: PASV: " . ( $pasv ? "ON" : "FAILURE" ) . "\n";

    // upload the localfile
    try {
      $client->putFromPath( $remote_name, $local_file );
      $send = true;
    } catch( Exception|FtpException $err ){
      $send = false;
      if ( $this->debug )
      echo "Uploading: FAILED\n";
    }

    $client->pasv( false );
		//$client->close();

		return $send;

	}
	public function delete( $remote_file ){

		$client = $this->getClient();

    $remote_dir = $this->data["path"] . dirname( $remote_file );
    $remote_name = basename( $remote_file );

		$client->chdir( $remote_dir );
		$client->pasv( true );
    $remove = $client->remove( $remote_name );
    $client->pasv( false );
		if ( $remove )
		return true;
		return false;

	}

	protected function dir_exists( $path ){

		$client = $this->getClient();
		$client->chdir( $this->base );
    if ( $this->debug )
    echo "Checking Dir: PWD: " .  $this->client->pwd() . "\n";

		return $client->isDir( $path );

	}
	protected function dir_create( $path, $rec = false ){

    $client = $this->getClient();
		$client->mkdir( $path, $rec );

	}
	protected function dir_delete( $path, $rec = false ){

		$client = $this->getClient();

		if ( !$this->dir_exists( $path ) )
    return;

		$client->rmdir( $path, $rec );

	}

  protected function ssh_upload( $local_file, $remote_file ){

    $client = $this->getClient();
    $remote_dir = dirname( $remote_file );
    $remote_name = basename( $remote_file );

    // remote dir
    if ( !$this->ssh_dir_exists( $remote_dir ) ){
      $this->ssh_dir_create( $remote_dir, true );
        if ( !$this->ssh_dir_exists( $remote_dir ) )
        throw new Exception("Failed to create directory {$remote_dir}");
    }

    // upload
    $client->uploadFile( $local_file, $remote_file );

    return true;

  }
  protected function ssh_dir_exists( $path ){

    $client = $this->getClient();

    if ( $client->realpath( $path ) )
    return true;

    return false;

  }
  protected function ssh_dir_create( $path, $rec = false ){

    $client = $this->getClient();
    $client->mkDir( $path, "0777", $rec );

  }

}

?>
