<?php

if ( !defined( "bof_root" ) ) die;

class pht1 extends bof_type_class {

  public function check_settings( $service_name ){

    $keys = bof()->ai->get_keys();

    if ( empty( $keys["playht_key"] ) )
    bof()->ai->set_key_from_db( "playht", "key", true );

    if ( empty( $keys["playht_uid"] ) )
    bof()->ai->set_key_from_db( "playht", "uid", true );

    return $this;

  }
  protected $base = "https://play.ht/api/v1/";

  public function getVoices(){

    $request = $this->__request( "getVoices", array(
      "cache" => true,
      "cache_load" => true,
      "cache_age" => 6
    ) )["data"];

    if ( empty( $request["voices"] ) )
    throw new bofException( "no playht voice found. Check API credentials" );

    $voices_raw = $request["voices"];
    $voices = [];

    foreach( $voices_raw as $voice_raw ){

      $voice = array(
        "name" => $voice_raw["name"],
        "lang" => substr( $voice_raw["languageCode"], 0, 2 ),
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
      $tags["style_count"] = !empty( $voice_raw["styles"] ) ? count( $voice_raw["styles"] ) : 1;

      $voice["tags"] = $tags;
      $voice["ID"] = $voice_raw["value"];
      $voice["source"] = "pht1";

      $voices[ $voice_raw["value"] ] = $voice;

    }

    usort($voices, function($a, $b) {
      return $b['tags']['style_count'] <=> $a['tags']['style_count'];
    });

    return $voices;

  }

  public function ttsSubmit( $text, $voice, $args=[] ){

    extract( $args );

    $sub = $this->__request( "convert", array(
      "post" => array(
        "voice" => $voice,
        "content" => array(
          $text
        )
      )
    ) );

    if ( $sub["http_code"] != 201 )
    throw new bofException( "request failed" );

    if ( empty( $sub["data"]["transcriptionId"] ) )
    throw new bofException( "request failed: 2" );

    return array(
      "source_id" => $sub["data"]["transcriptionId"],
      "word_count" => $sub["data"]["wordCount"]
    );

  }
  public function ttsCheck( $id ){

    $check = $this->__request( "articleStatus?transcriptionId={$id}" );

    if ( $check["http_code"] != 200 )
    throw new bofException( "request failed" );

    if ( !empty( $check["error"] ) )
    throw new bofException( "tts failed: {$check["errorMessage"]}" );

    if ( empty( $check["data"]["converted"] ) )
    return false;

    return $check["data"]["audioUrl"];

  }

  protected function __request( $endpoint, $args=[] ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $post = false;
    extract( $args );

    $curlArray = array(
      "url" => $this->base . $endpoint,
      "headers" => array(
        "Authorization: " . bof()->ai->get_key( "playht_key" ),
        "X-USER-ID: " . bof()->ai->get_key( "playht_uid" ),
        "accept: application/json",
        "content-type: application/json",
      ),
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age
    );

    if ( $post )
    $curlArray["posts"] = json_encode( $post );

    return bof()->curl->exe( $curlArray );

  }

}

?>
