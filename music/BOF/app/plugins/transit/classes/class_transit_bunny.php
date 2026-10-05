<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_bunny extends transit_ftp {

  protected function getConnectData(){

    return array(
			"addr" => $this->data["ftp_host"],
			"port" => $this->data["ftp_port"],
			"user" => $this->data["ftp_user"],
			"pass" => $this->data["ftp_pass"],
			"type" => "sslftp",
			"path" => false
		);

  }

}

?>
