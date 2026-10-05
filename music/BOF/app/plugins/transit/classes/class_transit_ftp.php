<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_ftp extends bof_type_class {

  public $data = null;

  public function load(){}
  protected function getConnectData(){

    $server_data = $this->data;

    return array(
			"addr" => $server_data["address"],
			"port" => $server_data["port"],
			"user" => $server_data["username"],
			"pass" => $server_data["password"],
			"type" => $server_data["type"],
			"path" => $server_data["path"]
		);

  }

  public function connect(){

    $server = bof()->transit->get_storage();
    // bof()->ftp->set_debug( bof()->transit->get_debug() );
    $this->data = $server["data_decoded"];

		bof()->ftp->set_data( $this->getConnectData() );

		return true;

  }

	public function upload( $_source, $_remote, $data ){

		$upload = bof()->ftp->upload( $_source, $_remote );
		return [ $upload, $_remote ];

	}

	public function delete( $_remote ){

		return bof()->ftp->delete( $_remote );

	}


}

?>
