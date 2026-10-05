<?php

if ( !defined( "bof_root" ) ) die;

class prodia extends bof_type_class {

  public function check_settings( $service_name  ){

    $settings = bof()->ai->get_settings();
    $keys = bof()->ai->get_keys();

    if ( empty( $keys["prodia_key"] ) )
    bof()->ai->set_key_from_db( "prodia", "key", true );


    if ( $service_name == "image" ){

      if ( empty( $settings["image.prodia_base_model"] ) )
      bof()->ai->set_setting_from_db( "image", "prodia", "base_model", null, true );

      $base_model = bof()->ai->get_setting( "image.prodia_base_model" );

      if ( $base_model == "sd" ){

        if ( empty( $settings["image.prodia_sd_model"] ) )
        bof()->ai->set_setting_from_db( "image", "prodia", "sd_model", null, true );

        if ( empty( $settings["image.prodia_sd_sampler"] ) )
        bof()->ai->set_setting_from_db( "image", "prodia", "sd_sampler", null, true );

      }
      else {

        if ( empty( $settings["image.prodia_sdxl_model"] ) )
        bof()->ai->set_setting_from_db( "image", "prodia", "sdxl_model", null, true );

        if ( empty( $settings["image.prodia_sdxl_sampler"] ) )
        bof()->ai->set_setting_from_db( "image", "prodia", "sdxl_sampler", null, true );

      }

      if ( empty( $settings["image.prodia_negative_prompt"] ) )
      bof()->ai->set_setting_from_db( "image", "prodia", "negative_prompt", null, false );

      if ( empty( $settings["image.prodia_steps"] ) )
      bof()->ai->set_setting_from_db( "image", "prodia", "steps", null, false );

      if ( empty( $settings["image.prodia_cfg"] ) )
      bof()->ai->set_setting_from_db( "image", "prodia", "cfg", null, false );

      if ( empty( $settings["image.prodia_seed"] ) )
      bof()->ai->set_setting_from_db( "image", "prodia", "seed", null, false );

    }

    return $this;

  }
  public function generate_image_from_text( $prompt, $args=[] ){

    $base_model = bof()->ai->get_setting( "image.prodia_base_model" );
    $model = bof()->ai->get_setting( "image.prodia_{$base_model}_model" );
    $sampler = bof()->ai->get_setting( "image.prodia_{$base_model}_sampler" );
    $negative_prompt = bof()->ai->get_setting( "image.prodia_negative_prompt" );
    $steps = bof()->ai->get_setting( "image.prodia_steps" );
    $cfg = bof()->ai->get_setting( "image.prodia_cfg" );
    $seed = bof()->ai->get_setting( "image.prodia_seed" );
    $width = null;
    $height = null;
    $size = null;
    extract( $args );

    if ( empty( $steps ) )
    $steps = 25;
    if ( empty( $cfg ) )
    $cfg = 6;
    if ( empty( $seed ) )
    $seed = "-1";

    if ( $base_model == "sd" ? ( empty( $width ) || empty( $height )  ) && !empty( $size ) : false ){
        if ( $size == "small" || $size == "medium" )
        $width = $height = 512;
        else
        $width = $height = 1024;
    }
    if ( $base_model == "sdxl" ? ( empty( $width ) || empty( $height )  ) && !empty( $size ) : false ){
        $width = $height = 1024;
    }

    $request = $this->__request( "{$base_model}/generate", array(
      "posts" => array(
        "prompt" => $prompt,
        "model" => $model,
        "steps" => intval( $steps ),
        "cfg_scale" => intval( $cfg ),
        "seed" => intval( $seed ),
        "sampler" => $sampler,
        "width" => intval( $width ),
        "height" => intval( $height ),
        "negative_prompt" => $negative_prompt
      )
    ) );

    if ( empty( $request["job"] ) )
    throw new Exception( "prodia generation no job returned" );

    $jobID = $request["job"];

    $maxTries = 12;
    $tries = 0;

    while( $tries < $maxTries ){

      $checkOnJob = $this->__request( "job/" . $jobID );

      if ( !empty( $checkOnJob["status"] ) ? $checkOnJob["status"] == "succeeded" && !empty( $checkOnJob["imageUrl"] ) : false ){

        if ( $base_model == "sd" ){
          $_mp = $width * $height / 1000000;
          if ( $_mp >= 0.6 ) $_ip = $steps > 25 ? 0.0100 : 0.0080;
          elseif ( $_mp >= 0.4 ) $_ip = $steps > 25 ? 0.0075 : 0.0050;
          else $_ip = $steps > 25 ? 0.0030 : 0.0025;
        } else {
          $_ip = $steps > 25 ? 0.0025 : 0.0020;
        }

        bof()->ai_service->fee( "image", "prodia", "image_from_text", array(
          "total_img_price" => $_ip,
          "total_price" => $_ip
        ) );

        return $checkOnJob["imageUrl"];
        break;

      }

      $tries++;
      sleep(5);

    }

    throw new Exception( "prodia generation failled" );

  }

  protected $base = "https://api.prodia.com/v1/";

  public function models( $type ){

    return $this->__request( "{$type}/models", array(
      "cache" => true,
      "cache_load" => true,
      "cache_age" => 24
    ) );

  }
  public function samplers( $type ){

    return $this->__request( "{$type}/samplers", array(
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
    extract( $args );

    $headers = array(
      "X-Prodia-Key: " . bof()->ai->get_key( "prodia_key" ),
    );

    $_req = bof()->curl->exe( array(
      "url" => $this->base . $endpoint,
      "headers" => $headers,
      "posts" => $posts ? json_encode( $posts ) : null,
      "cache" => $cache,
      "cache_load" => $cache_load,
      "cache_age" => $cache_age,
      "timeout" => 60,
      "json" => true
    ) );

    if ( $_req["http_code"] != 200 )
    throw new Exception( "proodia request failed" );

    $output = $_req["data"];

    return $output;

  }

}

?>
