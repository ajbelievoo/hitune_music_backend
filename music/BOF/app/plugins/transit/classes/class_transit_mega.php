<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_mega extends transit_aws_s3 {

  protected function getClientData(){

    $this->platform = "mega";
    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region' => $server_data["region"],
      'credentials' => array(
        'key'    => $server_data["key"],
        'secret' => $server_data["secret"],
      ),
      "endpoint" => "https://s3.{$server_data["region"]}.s4.mega.io"
    );

    return $_d;

  }

}

?>
