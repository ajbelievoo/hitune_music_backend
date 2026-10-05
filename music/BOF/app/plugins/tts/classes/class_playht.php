<?php

if ( !defined( "bof_root" ) ) die;

class playht extends bof_type_class {

  protected $base = "https://play.ht/api/v1/";
  protected $user_id = null;
  protected $secret = null;

  public function setup_client(){

    if ( !( $this->user_id = bof()->object->db_setting->get( "pht_user_id" ) ) )
    throw new bofException( "no playht user-id set" );

    if ( !( $this->secret = bof()->object->db_setting->get( "pht_secret" ) ) )
    throw new bofException( "no playht secret set" );

  }
  public function getVoices(){

    $request = $this->__request( "getVoices", array(
      "cache" => true,
      "cache_load" => true,
      "cache_age" => 6
    ) );

    if ( empty( $request["voices"] ) )
    throw new bofException( "no playht voice found. Check API credentials" );

    $voices_raw = $request["voices"];
    $voices = [];

    foreach( $voices_raw as $voice_raw ){

      if ( $voice_raw["languageCode"] != "en-US" )
      continue;

      $voice = array(
        "name" => $voice_raw["name"],
        "lang" => $voice_raw["languageCode"],
        "gender" => $voice_raw["gender"] == "Male" ? 1 : ( $voice_raw["gender"] == "Female" ? 2 : 3 ),
        "sample" => $voice_raw["sample"]
      );

      $tags = [];
      $tags["voiceType"] = ucfirst( $voice_raw["voiceType"] );
      $tags["language"] = ucfirst( $voice_raw["language"] );
      $tags["gender"] = ucfirst( $voice_raw["gender"] );
      $tags["service"] = ucfirst( $voice_raw["service"] );
      $tags["name"] = ucfirst( $voice_raw["name"] );

      if ( !empty( $voice_raw["isKid"] ) )
      $tags["age"] = "kid";

      if ( !empty( $voice_raw["styles"] ) )
      $tags["styles"] = implode( ", ",  $voice_raw["styles"] );

      $voice["tags"] = $tags;

      $voices[ $voice_raw["value"] ] = $voice;

    }

    die( json_encode( $voices ) );

    return $voices_raw;

  }

  protected function __request( $endpoint, $args=[] ){

    $cache = false;
    $cache_load = false;
    $cache_age = 6;
    extract( $args );

    return bof()->curl->exe(array(
      "url" => $this->base . $endpoint,
      "headers" => array(
        "Authorization: {$this->secret}",
        "X-USER-ID: {$this->user_id}",
      ),
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age
    ))["data"];

  }


}

?>
