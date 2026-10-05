<?php

if ( !defined( "bof_root" ) ) die;

class soundcloud extends bof_type_class {

  protected $base = "https://api.soundcloud.com/";
  protected $id = null;
  protected $secret = null;
  protected $token = null;

  protected function set_keys(){

    if ( $this->id && $this->secret )
    return true;

    $id = bof()->object->db_setting->get( "soundcloud_api_id" );
    $secret = bof()->object->db_setting->get( "soundcloud_api_key" );
    if ( !$id || !$secret ) return false;

    $this->id = $id;
    $this->secret = $secret;
    $this->token = bof()->object->db_setting->get( "soundcloud_api_token" );
    return true;

  }

  public function find_track( $data ){

    $title = null;
		$sub_title = null;
		$duration = null;
		extract( $data );

		$query = ( $sub_title ? $sub_title . " - " : "" ) . "{$title}" ;

    if ( !$this->set_keys() )
    return [ false, "SoundCloudAPI: No keys provided" ];

    $get = $this->__req( "tracks", array(
      "params" => array(
        "q" => $query,
        "access" => "playable"
      )
    ) );

    if ( empty( $get ) )
    return [ false, "SoundCloudAPI: Not found!" ];

    return [ true, $get[0]["id"] ];

  }
  public function get_track( $id ){

    if ( !$this->set_keys() )
    return [ false, "SoundCloudAPI: No keys provided" ];

    $get = $this->__req( "tracks/{$id}" );
    
    if ( empty( $get ) )
    return [ false, "SoundCloudAPI: Not found!" ];

    return [ true, $get ];

  }

  protected function __req( $endpoint, $args=[] ){

    $params = [];
    $headers = [];
    extract( $args );

    $params_string = $params ? http_build_query( $params ) : false;
    $url = $this->base . $endpoint . ( $params_string ? "?" . $params_string : "" );

    if ( $this->token )
    $headers[] = "Authorization: Bearer " . $this->token;

    $exe = bof()->curl->exe( array(
      "url" => $url,
      "hook" => md5( $url ),
      "headers" => $headers,
    ) );

    // Outdated token
    if ( !empty( $exe["data"] ) && $exe["http_code"] == 401 ? $exe["data"]["code"] == 401 : false ){

      $tokenExe = bof()->curl->exe( array(
        "url" => $this->base . "oauth2/token",
        "hook" => md5( $url ),
        "headers" => $headers,
        "posts" => http_build_query( array(
          "grant_type"    => "client_credentials",
          "client_id"     => $this->id,
          "client_secret" => $this->secret,
        ) )
      ) );

      if ( empty( $tokenExe ) ? true : empty( $tokenExe["data"]["access_token"] ) )
      return false;

      $this->token = $tokenExe["data"]["access_token"];
      bof()->object->db_setting->set( "soundcloud_api_token", $this->token, "string" );
      return $this->__req( $endpoint, $args );

    }

    return $exe["data"];

  }

}

?>
