<?php

if ( !defined( "bof_root" ) ) die;

class openai extends bof_type_class {

  public function get_text_models( $output_type="raw" ){
    $models = [];
    foreach( $this->models_price as $model_name => $model_price ){
      if( count( $model_price ) == 3 ){
        if ( $output_type == "raw" )
        $models[ $model_name ] = $model_price[2];
        elseif( $output_type == "options" )
        $models[] = [ $model_name, $model_price[2] ];
      }
    }
    return $models;
  }
  public function check_settings( $service_name ){

    $settings = bof()->ai->get_settings();
    $keys = bof()->ai->get_keys();

    if ( empty( $keys["openai_key"] ) ){
      bof()->ai->set_key_from_db( "openai", "key", true );
      bof()->ai->set_key_from_db( "openai", "org", false );
    }

    if ( $service_name == "image" && empty( $settings["image.openai_model"] ) )
    bof()->ai->set_setting_from_db( "image", "openai", "model", "dalle_2", false );

    if ( $service_name == "image" && empty( $settings["image.openai_quality"] ) )
    bof()->ai->set_setting_from_db( "image", "openai", "quality", "standard", false );

    if ( $service_name == "text" && empty( $settings["text.openai_temperature"] ) )
    bof()->ai->set_setting_from_db( "text", "openai", "temperature", null, false );

    if ( $service_name == "text" && empty( $settings["text.openai_max_tokens"] ) )
    bof()->ai->set_setting_from_db( "text", "openai", "max_tokens", null, false );

    return $this;

  }
  public function generate_image_from_text( $prompt, $args=[] ){

    $size = "512x512";
    $model = bof()->ai->get_setting( "image.openai_model" );
    $quality = bof()->ai->get_setting( "image.openai_quality" );
    extract( $args );

    if ( $model == "dall-e-2" || $model == "dalle-2" || $model == "dalle_2" || $model == "dall_e_2" || !$model ){
      $model = "dall-e-2";
      $quality = "standard";
      if ( $size == "small" )
      $size = "256x256";
      elseif ( $size == "medium" )
      $size = "512x512";
      else
      $size = "1024x1024";
    }
    elseif ( $model == "dall-e-3" || $model == "dalle-3" || $model == "dalle_3" || $model == "dall_e_3" ){
      $model = "dall-e-3";
      if ( $size == "small" || $size = "medium" )
      $size = "1024x1024";
      else
      $size = "1792x1024";
    }

    $request = $this->__request( "images/generations", array(
      "posts" => array(
        "prompt" => $prompt,
        "size" => $size,
        "model" => $model,
        "quality" => $quality
      )
    ) );

    if ( empty( $request["data"][0]["url"] ) )
    throw new Exception("OpenAI.image_from_text failed");

    if ( !empty( $request["usage"] ) ){
      bof()->ai_service->fee( "image", "openai", "image_from_text", $request["usage"] );
    }

    return $request["data"][0]["url"];

  }
  public function generate_text_from_text( $prompt, $args=[] ){

    $model = bof()->ai->get_setting( "text.openai_model" );
    $temperature = bof()->ai->get_setting( "text.openai_temperature" );
    $max_tokens = bof()->ai->get_setting( "text.openai_max_tokens" );
    $prompt_role = "user";
    $prompt_system = null;
    $prompt_user = null;
    $messages = [];
    $json = null;
    extract( $args );

    if ( $model == "_gpt_3" || $model == "_gpt_3_16k" || $model == "gpt3_5" || $model == "gpt3" )
    $model = "gpt-3.5-turbo-0125";
    elseif( $model == "_gpt_4" || $model == "_gpt_4_32k" )
    $model = "gpt-4o";

    if ( empty( $messages ) ){

      if ( $prompt_role == "user" && !$prompt_user )
      $prompt_user = $prompt;
      elseif( $prompt_role == "system" && !$prompt_system )
      $prompt_system = $prompt;

      if ( $prompt_system )
      $messages[] = array( "role" => "system", "content" => $prompt_system );

      if ( $prompt_user )
      $messages[] = array( "role" => "user", "content" => $prompt_user );

    }

    $post_args = array(
      "model" => $model,
      "messages" => $messages,
      "temperature" => $temperature,
      "max_tokens" => $max_tokens,
    );

    if ( $json ){
      $post_args["response_format"] = array(
        "type" => "json_object"
      );
    }

    $request = $this->__request( "chat/completions", array(
      "posts" => $post_args,
    ) );

    if ( empty( $request["choices"]["0"]["message"]["content"] ) )
    throw new Exception("OpenAI.text_from_text failed");

    if ( !empty( $request["usage"] ) ){
      bof()->ai_service->fee( "text", "openai", "text_from_text", $request["usage"] );
    }

    return $request["choices"]["0"]["message"]["content"];

  }
  public function generate_text_from_audio( $file_path, $args=[] ){

    $model = "whisper-1";
    extract( $args );

    $request = $this->__request( "audio/transcriptions", array(
      "posts" => array(
        "model" => $model,
        "file" => new \CurlFile( $file_path, mime_content_type( $file_path ) )
      ),
      "json" => false
    ) );

    return $request["text"];

  }

  protected $base = "https://api.openai.com/v1/";
  protected $models_price = array(

    "gpt-3.5-turbo" => [ 0.0005, 0.0015, "3.5 Turbo" ],
    "gpt-3.5-turbo-0125" => [ 0.0005, 0.0015 ],
    "gpt-3.5-turbo-1106" => [ 0.001, 0.002 ],

    "gpt-4o-2024-05-13" => [ 0.005, 0.015 ],
    "gpt-4o" => [ 0.0025, 0.01, "4o" ],
    "gpt-4o-2024-06-08" => [ 0.0025, 0.01 ],
    "gpt-4o-2024-11-20" => [ 0.0025, 0.01 ],

    "gpt-4.1" => [ 0.002, 0.008, "4.1" ],
    "gpt-4.1-2025-04-14" => [ 0.002, 0.008 ],

    "gpt-4.1-mini" => [ 0.0004, 0.0016, "4.1 Mini" ],
    "gpt-4.1-mini-2025-04-14" => [ 0.0004, 0.0016 ],

    "gpt-4.1-nano" => [ 0.0001, 0.0004, "4.1 Nano" ],
    "gpt-4.1-nano-2025-04-14" => [ 0.0001, 0.0004 ],

    "gpt-4" => [ 0.03, 0.06 ],
    "gpt-4-32k" => [ 0.06, 0.12 ],

  );
  public function get_model_prices(){
    $model_prices = $this->models_price;
    $model_prices["_gpt_3"] = $model_prices["gpt-3.5-turbo-0125"];
    $model_prices["_gpt_4"] = $model_prices["gpt-4o"];
    return $model_prices;
  }

  public function models(){

    return $this->__request( "models", array(
      "cache" => true,
      "cache_load" => true,
      "cache_age" => 24
    ) );

  }
  protected function __request( $endpoint, $args=[], $retrying=false ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $posts = false;
    $json = true;
    extract( $args );

    $headers = array(
      "Authorization: Bearer " . bof()->ai->get_key( "openai_key" ),
    );

    if ( $json )
    $headers[] = "Content-Type: application/json";
    else
    $headers[] = "Content-Type: multipart/form-data";

    if ( bof()->ai->get_key( "openai_org" ) )
    $headers[] = "OpenAI-Organization: " . bof()->ai->get_key( "openai_org" );

    $_req = bof()->curl->exe(array(
      "url" => $this->base . $endpoint,
      "headers" => $headers,
      "posts" => $posts ? ( $json ? json_encode( $posts ) : $posts ) : null,
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age,
      "timeout" => 600
    ));

    if ( !empty( $_req["data"]["error"]["message"] ) )
    throw new Exception("openai_err: <b>{$_req["data"]["error"]["message"]}</b>");

    if ( $_req["http_code"] == 401 )
    throw new Exception("openai_invalid_key");

    if ( $_req["http_code"] == 429 ){

      if ( !empty( $_req["data"]["error"]["type"] ) ? $_req["data"]["error"]["type"] == "insufficient_quota" : false )
      throw new Exception("openai_insufficient_quota");

      if ( $retrying )
      throw new Exception("openai_limit_reached_even_after_wait");

      sleep(60);

      return $this->__request( $endpoint, $args, true );

    }

    $output = $_req["data"];

    if ( !empty( $output["model"] ) && !empty( $output["usage"]["total_tokens"] ) ){
      foreach( $this->models_price as $modelName => $modelPrices ){
        if ( substr( $output["model"], 0, strlen( $modelName ) ) == $modelName ){
          $output["usage"]["model_version"] = substr( $modelName, 0, strlen("gpt-4") ) == "gpt-4" ? 4 : 3;
          $output["usage"]["prompt_price"] = $output["usage"]["prompt_tokens"] / 1000 * $modelPrices[0];
          $output["usage"]["completion_price"] = $output["usage"]["completion_tokens"] / 1000 * $modelPrices[1];
          $output["usage"]["total_price"] = $output["usage"]["prompt_price"] + $output["usage"]["completion_price"];
          break;
        }
      }
    }
    if ( $endpoint == "images/generations" ){

      if ( $posts["model"] == "dall-e-2" && $posts["size"] == "256x256" )
      $_ip = 0.016;
      elseif ( $posts["model"] == "dall-e-2" && $posts["size"] == "512x512" )
      $_ip = 0.018;
      elseif ( $posts["model"] == "dall-e-2" && $posts["size"] == "1024x1024" )
      $_ip = 0.02;
      elseif ( $posts["model"] == "dall-e-3" && $posts["size"] == "1024x1024" && $posts["quality"] == "standard" )
      $_ip = 0.04;
      elseif ( $posts["model"] == "dall-e-3" && in_array( $posts["size"], ["1024x1792","1792x1024"], true ) && $posts["quality"] == "standard" )
      $_ip = 0.08;
      elseif ( $posts["model"] == "dall-e-3" && $posts["size"] == "1024x1024" && $posts["quality"] == "hd" )
      $_ip = 0.08;
      elseif ( $posts["model"] == "dall-e-3" && in_array( $posts["size"], ["1024x1792","1792x1024"], true ) && $posts["quality"] == "hd" )
      $_ip = 0.12;

      $output["usage"]["total_img_price"] = $_ip;
      $output["usage"]["total_price"] = $_ip;

    }

    return $output;

  }

}

?>
