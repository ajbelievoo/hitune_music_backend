<?php

if ( !defined( "bof_root" ) ) die;

class elio extends bof_type_class {

  public function check_settings( $service_name ){

    $keys = bof()->ai->get_keys();

    if ( empty( $keys["elevenlabs_key"] ) )
    bof()->ai->set_key_from_db( "elevenlabs", "key", true );

    if ( $service_name == "speech" ){
      if ( empty( $settings["speech.elevenlabs_model"] ) )
      bof()->ai->set_setting_from_db( "speech", "elevenlabs", "model", null, true );
    }

    return $this;

  }
  protected $base = "https://api.elevenlabs.io/v1/";

  public function getVoices( $args=[] ){

    $elevenlabs_shared = false;
    extract( $args );

    $request = $this->__request( "voices", array(
      "cache_load" => false,
      "cache_age" => 6,
      "json" => true
    ) )["data"];

    if ( empty( $request["voices"] ) )
    throw new bofException( "no elevenlabs voice found. Check API credentials" );

    $voices_raw = $request["voices"];
    $voices = [];

    foreach( $voices_raw as $voice_raw ){

      $voice = array(
        "name" => $voice_raw["name"],
        "lang" => !empty( $voice_raw["fine_tuning"]["language"] ) ? strtolower( substr( $voice_raw["fine_tuning"]["language"], 0, 2 ) ) : "00",
        "gender" => !empty( $voice_raw["labels"]["gender"] ) ? ( $voice_raw["labels"]["gender"] == "male" ? 1 : ( $voice_raw["labels"]["gender"] == "female" ? 2 : 3 ) ) : 3,
        "sample" => $voice_raw["preview_url"]
      );

      $tags = $voice_raw["labels"];

      $voice["tags"] = $tags;
      $voice["ID"] = $voice_raw["voice_id"];
      $voice["source"] = "elio";

      $voices[ $voice_raw["voice_id"] ] = $voice;

    }

    $has_more = true;
    $page = 1;
    while( $has_more && $elevenlabs_shared ){

      $request = $this->__request( "shared-voices?page_size=200&page={$page}", array(
        "cache" => true,
        "cache_load" => true,
        "cache_save" => true,
        "cache_age" => 6,
        "json" => true
      ) )["data"];

      if ( !empty( $request["voices"] ) ){
        foreach( $request["voices"] as $voice_raw ){

          $voice = array(
            "name" => mb_substr( $voice_raw["name"], 0, 100, "utf-8" ),
            "lang" => !empty( $voice_raw["language"] ) ? strtolower( substr( $voice_raw["language"], 0, 2 ) ) : "00",
            "gender" => $voice_raw["gender"] == "male" ? 1 : ( $voice_raw["gender"] == "female" ? 2 : 3 ),
            "sample" => $voice_raw["preview_url"],
            "ID" => $voice_raw["voice_id"],
            "source" => "elio",
            "tags" => []
          );

          $tags = [];

          foreach( [ "accent", "age", "category" ] as $_pt ){
            if ( !empty( $voice_raw[$_pt] ) ){
              $tags[] = $voice_raw[$_pt];
            }
          }

          $voice["tags"] = $tags;

          $voices[ $voice_raw["voice_id"] ] = $voice;

        }
      }

      if ( empty( $request["has_more"] ) ){
        $has_more = false;
        break;
      }

      $page++;

    }

    return $voices;

  }

  public function ttsSubmit( $text, $voice, $args=[] ){

    extract( $args );

    $sub = $this->__request( "text-to-speech/{$voice}?output_format=mp3_44100_128", array(
      "post" => array(
        "text" => $text,
        "model_id" => bof()->ai->get_setting( "speech.elevenlabs_model" ),
      ),
      "json" => true
    ) );

    $headers = $sub["header"];
    if ( $headers ){
      foreach( explode( PHP_EOL, $headers ) as $header ){
        if ( substr( $header, 0, strlen( "history-item-id:" ) ) == "history-item-id:" ){
          $itemID = trim( substr( $header, strlen( "history-item-id:" ) ) );
        }
      }
    }

    if ( empty( $itemID ) )
    throw new bofException( "request failed: no history-item-id" );

    return array(
      "source_id" => $itemID,
      "blob" => $sub["body"],
      "word_count" => count( explode( " ", $text ) )
    );

  }

  public function cloneVoice( $name, $gender, $file_path ){

    $sub = $this->__request( "voices/add", array(
      "post_encode" => false,
      "post" => array(
        "name" => $name,
        "files" => new \CurlFile( $file_path, mime_content_type( $file_path ) )
      ),
      "header_content_type" => "multipart/form-data",
    ) );

    $data = json_decode( $sub["body"], 1 );

    if ( empty( $data["voice_id"] ) )
    throw new bofException( "request failed: 2" );

    return $data["voice_id"];

  }
  public function removeClonedVoice( $voiceID ){

    $sub = $this->__request( "voices/{$voiceID}", array(
      "custom_request" => "DELETE"
    ) );

    if ( $sub["http_code"] != 200 )
    throw new bofException( "request failed" );

    return true;

  }

  public function getModels(){

    $request = $this->__request( "models", array(
      "cache_load" => true,
      "cache_save" => true,
      "cache_age" => 24,
      "json" => true
    ) )["data"];

    return $request;

  }

  protected function __request( $endpoint, $args=[] ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $post = false;
    $post_encode = true;
    $headers = [];
    $header_content_type = "application/json";
    $custom_request = false;
    $json = false;
    extract( $args );

    $curlArray = array(
      "url" => $this->base . $endpoint,
      "headers" => array(
        "xi-api-key: " . bof()->ai->get_key( "elevenlabs_key" ),
        "content-type: {$header_content_type}",
      ),
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age,
      // "json" => $json,
      "type" => $json ? "json" : "file"
    );

    if ( $post && $post_encode )
    $curlArray["posts"] = json_encode( $post );
    elseif ( $post )
    $curlArray["posts"] = $post;

    if ( $custom_request )
    $curlArray["custom_request"] = $custom_request;

    $_req = bof()->curl->exe( $curlArray );

    if ( $_req["http_code"] == 401 )
    throw new Exception("elevenlabs_invalid_key");

    if ( $_req["http_code"] != 200 )
    throw new Exception("elevenlabs_req_failed");

    return $_req;

  }

}

?>
