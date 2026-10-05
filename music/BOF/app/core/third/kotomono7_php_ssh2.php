<?php

if ( !defined( "root" ) ) die;

class SFTPConnection {

	    private $connection;
	    private $sftp;

	    public function __construct($host, $port)
	    {
	        $this->connection = @ssh2_connect($host, $port);
	        if (! $this->connection) {
	            throw new Exception("Could not connect to $host on port $port.");
	        }
	    }

	    public function login($username, $password)
	    {
	        if (!@ssh2_auth_password($this->connection, $username, $password)) {
	            throw new Exception("Could not authenticate with username $username and password $password.");
	        }

	        $this->sftp = @ssh2_sftp($this->connection);
	        if (!$this->sftp) {
	            throw new Exception("Could not initialize SFTP subsystem.");
	        }
	    }

	    public function readCSV($remote_file) {
	    	$sftp = $this->sftp;
	    	$path = "ssh2.sftp://$sftp$remote_file";
	    	$stream = @fopen($path, 'r');

	    	if (!$stream) {
	            throw new Exception("Could not open file: $remote_file");
	        }

	        @fclose($stream);

		    $data = @array_map('str_getcsv', @file($path));

		    return $data;
		}

	    public function readFile($remote_file) {
	    	$sftp = $this->sftp;
	    	$path = "ssh2.sftp://$sftp$remote_file";
	    	$stream = @fopen($path, 'r');

	    	if (!$stream) {
	            throw new Exception("Could not open file: $remote_file");
	        }

			$data = @fread($stream, @filesize($path));
			@fclose($stream);

			return $data;
	    }

	    public function uploadFile($local_file, $remote_file)
	    {
	        $sftp = $this->sftp;
	        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w');

	        if (!$stream) {
	            throw new Exception("Could not open file: $remote_file");
	        }

	        $data_to_send = @file_get_contents($local_file);
	        if ($data_to_send === false) {
	            throw new Exception("Could not open local file: $local_file.");
	        }

	        if (@fwrite($stream, $data_to_send) === false) {
	            throw new Exception("Could not send data from file: $local_file.");
	        }

	        @fclose($stream);
	    }

	   	function scanFilesystem($remote_file)
	   	{
			$sftp = $this->sftp;
			$dir = "ssh2.sftp://$sftp$remote_file";

			$tempArray = array();
			$handle = opendir($dir);

			// List all the files
			while (false !== ($file = readdir($handle))) {
				if (substr("$file", 0, 1) != ".") {
					if (is_dir($file)) {
						$tempArray[$file] = $this->scanFilesystem("$dir/$file");
					} else {
						$tempArray[] = $file;
					}
				}
			}

			closedir($handle);

			return $tempArray;
		}

	    public function receiveFile($remote_file, $local_file)
	    {
	        $sftp = $this->sftp;
	        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'r');

	        if (! $stream) {
	            throw new Exception("Could not open file: $remote_file");
	        }

	        $contents = fread($stream, filesize("ssh2.sftp://$sftp$remote_file"));
	        file_put_contents($local_file, $contents);
	        @fclose($stream);
	    }

	    public function deleteFile($remote_file)
	    {
	      	$sftp = $this->sftp;
	      	unlink("ssh2.sftp://$sftp$remote_file");
	    }

      public function getFileSize($file)
	    {
	      	$sftp = $this->sftp;
	        return filesize("ssh2.sftp://$sftp$file");
	    }

      public function mkDir($path, $mode="0777", $recursive=true)
	    {
	      	$sftp = $this->sftp;
	        $try = ssh2_sftp_mkdir( $sftp, $path, $mode, $recursive );
          if ( !$try ){
            throw new Exception("Could not make dir: {$path}");
          }
          return $try;
	    }

      public function stat( $path )
	    {
	      	$sftp = $this->sftp;
	        return ssh2_sftp_stat( $sftp, $path );
	    }

      public function realpath( $path )
	    {
	      	$sftp = $this->sftp;
	        return @ssh2_sftp_realpath( $sftp, $path );
	    }

	}

?>
