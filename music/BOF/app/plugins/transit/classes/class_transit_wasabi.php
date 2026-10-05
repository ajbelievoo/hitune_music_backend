<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_wasabi extends transit_aws_s3 {

  protected function getClientData(){

    $this->platform = "wasabi";
    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region'   => $server_data["region"],
      'credentials' => array(
        'key'    => $server_data["key"],
        'secret' => $server_data["secret"],
      ),
      "endpoint" => "https://s3.{$server_data["region"]}.wasabisys.com"
    );

    return $_d;

  }

}

?>
