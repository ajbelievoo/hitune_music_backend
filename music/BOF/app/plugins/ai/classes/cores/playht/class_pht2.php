<?php

if ( !defined( "bof_root" ) ) die;

class pht2 extends bof_type_class {

  public function check_settings( $service_name ){

    $keys = bof()->ai->get_keys();

    if ( empty( $keys["playht_key"] ) )
    bof()->ai->set_key_from_db( "playht", "key", true );

    if ( empty( $keys["playht_uid"] ) )
    bof()->ai->set_key_from_db( "playht", "uid", true );

    return $this;

  }
  protected $base = "https://play.ht/api/v2/";

  public function getVoices(){

    $request = $this->__request( "voices", array(
      "cache" => true,
      "cache_load" => true,
      "cache_age" => 6
    ) )["data"];

    if ( empty( $request ) ? true : !is_array( $request ) )
    throw new bofException( "no playht voice found. Check API credentials" );

    $voices_raw = $request;
    $voices = [];

    foreach( $voices_raw as $voice_raw ){

      $voice = array(
        "name" => $voice_raw["name"],
        "lang" => substr( $voice_raw["language_code"], 0, 2 ),
        "gender" => $voice_raw["gender"] == "male" ? 1 : ( $voice_raw["gender"] == "female" ? 2 : 3 ),
        "sample" => $voice_raw["sample"]
      );

      $tags = [];
      foreach( [ "accent", "age", "loudness", "tempo", "texture" ] as $_p ){
        if ( !empty( $voice_raw[ $_p ] ) )
        $tags[ $_p ] = ucfirst( $voice_raw[ $_p ] );
      }

      $tags["styles"] = $voice_raw["style"];
      $tags["style_count"] = !empty( $voice_raw["style"] ) ? count( explode( " ", $voice_raw["style"] ) ) : 1;

      $voice["tags"] = $tags;
      $voice["ID"] = $voice_raw["id"];
      $voice["source"] = "pht2";

      $voices[ $voice_raw["id"] ] = $voice;

    }

    return $voices;

  }

  public function ttsSubmit( $text, $voice, $args=[] ){

    extract( $args );

    $sub = $this->__request( "tts", array(
      "post" => array(
        "voice" => $voice,
        "text" => $text,
        "output_format" => "wav",
        "quality" => "premium"
      )
    ) );

    if ( $sub["http_code"] != 201 )
    throw new bofException( "request failed" );

    if ( empty( $sub["data"]["id"] ) )
    throw new bofException( "request failed: 2" );

    return array(
      "source_id" => $sub["data"]["id"],
      "word_count" => count( explode( " ", $text ) )
    );

  }
  public function ttsCheck( $id ){

    $check = $this->__request( "tts/{$id}" );

    if ( $check["http_code"] == 403 )
    throw new bofException( "access to v2 of playht denied" );

    if ( $check["http_code"] == 404 )
    throw new bofException( "request failed: not found" );

    if ( $check["http_code"] == 429 )
    throw new bofException( "request failed: limit" );

    if ( $check["http_code"] != 200 )
    throw new bofException( "request failed" );

    if ( empty( $check["data"]["output"]["url"] ) )
    return false;

    return $check["data"]["output"]["url"];

  }

  public function cloneVoice( $name, $gender, $file_path ){

    $sub = $this->__request( "cloned-voices/instant", array(
      "post_encode" => false,
      "post" => array(
        "voice_name" => $name,
        "sample_file" => new \CurlFile( $file_path, mime_content_type( $file_path ) )
      ),
      "header_content_type" => "multipart/form-data",
    ) );

    if ( $sub["http_code"] == 403 )
    throw new bofException( "clone limit reached on core" );

    if ( $sub["http_code"] != 201 )
    throw new bofException( "request failed" );

    if ( empty( $sub["data"]["id"] ) )
    throw new bofException( "request failed: 2" );

    return $sub["data"]["id"];

  }
  public function removeClonedVoice( $voiceID ){

    $sub = $this->__request( "cloned-voices/", array(
      "post" => array(
        "voice_id" => $voiceID,
      ),
      "custom_request" => "DELETE"
    ) );

    if ( $sub["http_code"] != 200 )
    throw new bofException( "request failed" );

    return true;

  }

  protected function __request( $endpoint, $args=[] ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $post = null;
    $post_encode = true;
    $headers = [];
    $header_content_type = "application/json";
    $custom_request = false;
    extract( $args );

    $headers = array_merge( $headers ? $headers : [], array(
      "Authorization: " . bof()->ai->get_key( "playht_key" ),
      "X-USER-ID: " . bof()->ai->get_key( "playht_uid" ),
      "accept: application/json",
      "content-type: {$header_content_type}",
    ) );

    $curlArray = array(
      "url" => $this->base . $endpoint,
      "headers" => $headers,
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age
    );

    if ( $post && $post_encode )
    $curlArray["posts"] = json_encode( $post );
    elseif ( $post )
    $curlArray["posts"] = $post;

    if ( $custom_request )
    $curlArray["custom_request"] = $custom_request;

    return bof()->curl->exe( $curlArray );

  }

}

?>
