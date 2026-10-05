<?php

if ( !defined( "bof_root" ) ) die;

class oobabooga extends bof_type_class {

  public function check_settings( $service_name ){

    $settings = bof()->ai->get_settings();
    $keys = bof()->ai->get_keys();

    return $this;

  }

  public function generate_text_from_text( $prompt, $args=[] ){

    $temperature = bof()->ai->get_setting( "text.oobabooga_temperature" );
    $max_tokens = bof()->ai->get_setting( "text.oobabooga_max_tokens" );
    $prompt_role = "user";
    $prompt_system = null;
    $prompt_user = null;
    $messages = [];
    $json = null;
    extract( $args );


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
      "messages" => $messages,
    );

    if ( $temperature )
    $post_args["temperature"] = $temperature;

    if ( $max_tokens )
    $post_args["max_tokens"] = $max_tokens;

    if ( $json ){
      $post_args["response_format"] = array(
        "type" => "json_object"
      );
    }

    $post_args["mode"] = "instruct";

    $request = $this->__request( "chat/completions", array(
      "posts" => $post_args,
    ) );

    if ( empty( $request["choices"]["0"]["message"]["content"] ) )
    throw new Exception("oobabooga.text_from_text failed");

    $_c = $request["choices"]["0"]["message"]["content"];
    $_c = explode( "</think>", $_c );
    $_c = end( $_c );
    $_c = str_replace( [ "json", "```", "``"] , "", $_c );
    $_c = trim( $_c );

    echo $_c;
    echo PHP_EOL . PHP_EOL;

    return $_c;

  }


  protected function __request( $endpoint, $args=[], $retrying=false ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $posts = false;
    $json = true;
    $headers = [];
    extract( $args );

    $base = bof()->ai->get_setting("text.oobabooga_base");

    if ( $json )
    $headers[] = "Content-Type: application/json";

    $_req = bof()->curl->exe(array(
      "url" => $base . $endpoint,
      "headers" => $headers,
      "posts" => $posts ? ( $json ? json_encode( $posts ) : $posts ) : null,
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age,
      "timeout" => 600
    ));

    if ( !empty( $_req["data"]["error"]["message"] ) )
    throw new Exception("oobabooga_err: <b>{$_req["data"]["error"]["message"]}</b>");

    if ( $_req["http_code"] == 429 ){

      if ( $retrying )
      throw new Exception("oobabooga_limit_reached_even_after_wait");

      sleep(60);

      return $this->__request( $endpoint, $args, true );

    }

    $output = $_req["data"];
    return $output;

  }

}

?>
