<?php

if ( !defined( "bof_root" ) ) die;

class deepai extends bof_type_class {

  public function check_settings( $service_name  ){

    $settings = bof()->ai->get_settings();
    $keys = bof()->ai->get_keys();

    if ( empty( $keys["deepai_key"] ) )
    bof()->ai->set_key_from_db( "deepai", "key", true );


    if ( $service_name == "image" ){

      if ( empty( $settings["image.deepai_style"] ) )
      bof()->ai->set_setting_from_db( "image", "deepai", "style", null, true );

      if ( empty( $settings["image.deepai_quality"] ) )
      bof()->ai->set_setting_from_db( "image", "deepai", "quality", null, true );

    }

    return $this;

  }
  public function generate_image_from_text( $prompt, $args=[] ){

    $style = bof()->ai->get_setting( "image.deepai_style" );
    $quality = bof()->ai->get_setting( "image.deepai_quality" );
    $negative_prompt = bof()->ai->get_setting( "image.deepai_negative_prompt" );
    $width = $height = null;
    $size = null;
    extract( $args );

    if ( !$width && ( $size == "small" || $size == "medium" ) )
    $width = $height = 768;
    elseif ( !$width && $size == "large" )
    $width = $height = 1024;

    $_arr = array(
      "text" => $prompt,
      "image_generator_version" => $quality,
    );

    if ( $negative_prompt )
    $_arr["negative_prompt"] = $negative_prompt;
    if ( $width )
    $_arr["width"] = strval( $width );
    if ( $height )
    $_arr["height"] = strval( $height );

    $request = $this->__request( $style, array(
      "posts" => $_arr
    ) );

    if ( empty( $request["output_url"] ) )
    throw new Exception("DeepAI.image: creation failed");

    bof()->ai_service->fee( "image", "deepai", "image_from_text", array(
      "total_img_price" => 0.05,
      "total_price" => 0.05,
    ) );

    return $request["output_url"];

  }

  protected $base = "https://api.deepai.org/api/";
  protected function __request( $endpoint, $args=[], $retrying=false ){

    $cache = true;
    $cache_load = false;
    $cache_age = 6;
    $posts = false;
    extract( $args );

    $headers = array(
      "api-key: " . bof()->ai->get_key( "deepai_key" ),
    );

    $_req = bof()->curl->exe( array(
      "url" => $this->base . $endpoint,
      "headers" => $headers,
      "posts" => $posts ? json_encode( $posts ) : null,
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age,
      "timeout" => 60,
      "json" => true,
    ) );

    if ( $_req["http_code"] != 200 ){

      if ( !empty( $_req["data"]["status"] ) )
      throw new Exception( "deepai request failed: " . $_req["data"]["status"] );
      if ( !empty( $_req["data"]["err"] ) )
      throw new Exception( "deepai request failed: " . $_req["data"]["err"] );
      throw new Exception( "deepai request failed" );

    }

    $output = $_req["data"];

    return $output;

  }

}

?>
