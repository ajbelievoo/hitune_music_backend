<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_storj extends transit_aws_s3 {

  protected function getClientData(){

    $this->platform = "storj";
    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region' => 'auto',
      'credentials' => array(
        'key'    => $server_data["key"],
        'secret' => $server_data["secret"],
      ),
      "endpoint" => $server_data["endpoint"]
    );

    return $_d;

  }

}

?>
