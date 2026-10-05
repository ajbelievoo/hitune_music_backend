<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class transit_cloudflare extends transit_aws_s3 {

  protected function getClientData(){

    $this->platform = "cloudflare";
    $server_data = $this->data;

    $_d = array(
      'version'  => 'latest',
      'region' => 'auto',
      'credentials' => array(
        'key'    => $server_data["key"],
        'secret' => $server_data["secret"],
      ),
      "endpoint" => "https://" . $server_data["endpoint"]
    );

    return $_d;

  }

}

?>
