<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_cdn777 extends transit_aws_s3 {

  protected function getClientData(){

    $this->platform = "cdn777";
    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region' => 'auto',
      'credentials' => array(
        'key'    => $server_data["key"],
        'secret' => $server_data["secret"],
      ),
      "endpoint" => "https://" . $server_data["bucket_url"]
    );

    return $_d;

  }

}

?>
