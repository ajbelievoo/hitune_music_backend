<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_minio extends transit_aws_s3 {

  protected function getClientData(){

    $this->platform = "minio";
    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region'   => null,
      'region'   => 'eu-east-1',
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
