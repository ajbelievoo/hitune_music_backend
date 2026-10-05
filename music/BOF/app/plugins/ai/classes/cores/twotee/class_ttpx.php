<?php

if ( !defined( "bof_root" ) ) die;

class ttpx extends bof_type_class {

  public function check_settings( $service_name ){

    $keys = bof()->ai->get_keys();

    if ( empty( $keys["twotee_key"] ) )
    bof()->ai->set_key_from_db( "twotee", "key", true );

    if ( empty( $keys["twotee_uid"] ) )
    bof()->ai->set_key_from_db( "twotee", "uid", true );

    return $this;

  }
  protected $base = "https://api.twotee.busyowl.co/x/";

  public function getVoices(){

    $request = $this->__request( "get_voices", array() )["data"];

    if ( empty( $request ) ? true : empty( $request["list"] ) )
    throw new bofException( "no twotee voice found. Check API credentials" );

    $voices_raw = $request["list"];
    $voices = [];

    foreach( $voices_raw as $voice_raw ){

      $voice = array(
        "name" => $voice_raw["name"],
        "lang" => $voice_raw["lang"],
        "gender" => $voice_raw["gender"],
        "sample" => $voice_raw["sample"]
      );

      $tags = [];

      $tags["styles"] = "normal";
      $tags["style_count"] = 1;

      $voice["tags"] = $tags;
      $voice["ID"] = $voice_raw["ID"];
      $voice["source"] = "ttpx";

      $voices[ $voice_raw["code"] ] = $voice;

    }

    return $voices;

  }

  public function ttsSubmit( $text, $voice, $args=[] ){

    extract( $args );

    $sub = $this->__request( "tts", array(
      "post" => array(
        "voice" => $voice,
        "text" => $text,
      )
    ) );

    if ( $sub["http_code"] != 200 )
    throw new bofException( "request failed" );

    if ( empty( $sub["data"]["jobID"] ) )
    throw new bofException( "request failed: 2" );

    return array(
      "source_id" => $sub["data"]["jobID"],
      "word_count" => $sub["data"]["wordCount"],
    );

  }
  public function ttsCheck( $id ){

    $check = $this->__request( "tts_check?jobID={$id}" );

    if ( $check["http_code"] != 200 )
    throw new bofException( "request failed" );

    if ( empty( $check["data"]["done"] ) )
    return false;

    return $check["data"]["audioUrl"];

  }

  public function cloneVoice( $name, $gender, $file_path ){

    $sub = $this->__request( "clone", array(
      "post_encode" => false,
      "post" => array(
        "name" => $name,
        "gender" => $gender,
        "voice" => new \CurlFile( $file_path, mime_content_type( $file_path ) )
      ),
      "header_content_type" => "multipart/form-data",
    ) );

    if ( empty( $sub["data"]["success"] ) )
    throw new bofException( $sub["data"]["messages"][0] );

    // if ( $this->version == 1 )
    // return $sub["data"]["id"];

    return $sub["data"]["jobID"];

  }
  public function removeClonedVoice( $voiceID ){

    $sub = $this->__request( "clone_remove", array(
      "post" => array(
        "voice_id" => $voiceID,
      ),
    ) );

    if ( $sub["http_code"] != 200 )
    throw new bofException( "request failed" );

    return true;

  }
  public function cloneVoiceCheck( $jobID, $data ){

    $sub = $this->__request( "clone_check", array(
      "post" => array(
        "jobID" => $jobID,
        "data" => json_encode( $data )
      ),
    ) );

    if ( empty( $sub["data"]["success"] ) )
    throw new bofException( "failed" );

    if ( empty( $sub["data"]["voice_id"] ) )
    return "pending";

    return $sub["data"];

  }

  protected function __request( $endpoint, $args=[] ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $post = null;
    extract( $args );

    $curlArray = array(
      "url" => $this->base . $endpoint,
      "headers" => array(
        "x-AUTH: " . bof()->ai->get_key( "twotee_key" ),
        "X-USERID: " . bof()->ai->get_key( "twotee_uid" ),
      ),
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age
    );

    if ( $post )
    $curlArray["posts"] = $post;

    return bof()->curl->exe( $curlArray );

  }

}

?>
