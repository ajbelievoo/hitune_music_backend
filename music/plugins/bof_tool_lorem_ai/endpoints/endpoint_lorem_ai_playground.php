<?php

set_time_limit( 120 );

if ( !defined( "bof_root" ) ) die;

function endpoint_lorem_ai_playground( $loader, $excuter, $args ){


  $all = bof()->ai->get_admin_aiapi_settings( "lorem_ai" );

  $inputs = array(
    "prompt" => array(
      "title" => "Prompt",
      "input" => array(
        "type" => "textarea",
        "name" => "prompt"
      ),
      "validator" => array(
        "string",
        array()
      )
    )
  );

  $cores = $all["groups"]["services"]["inputs"]["lorem_ai_i_core"];
  unset( $cores["tip"], $cores["col_name"] );

  $inputs["lorem_ai_i_core"] = $cores;

  foreach( $all["groups"] as $gName => $gData ){
    foreach( $gData["inputs"] as $iName => $iData ){
      if ( empty( $iData["play"] ) ) continue;
      $iData["input"]["value"] = bof()->object->db_setting->get( $iName );
      unset( $iData["col_name"], $iData["play"], $iData["tip"] );
      $iData["display_on"] = array(
        "lorem_ai_i_core" => [ "equal", "{$gName}" ]
      );
      $inputs[$iName] = $iData;
    }
  }

  $inputs["lorem_ai_openai_i_quality"]["display_on"]["lorem_ai_openai_i_model"] = [ "equal", "dalle_3" ];
  $inputs["lorem_ai_prodia_i_sd_model"]["display_on"]["lorem_ai_prodia_i_base_model"] = [ "equal", "sd" ];
  $inputs["lorem_ai_prodia_i_sd_sampler"]["display_on"]["lorem_ai_prodia_i_base_model"] = [ "equal", "sd" ];
  $inputs["lorem_ai_prodia_i_sdxl_model"]["display_on"]["lorem_ai_prodia_i_base_model"] = [ "equal", "sdxl" ];
  $inputs["lorem_ai_prodia_i_sdxl_sampler"]["display_on"]["lorem_ai_prodia_i_base_model"] = [ "equal", "sdxl" ];

  if ( !bof()->nest->user_input( "get", "exe", "equal", [ "value" => "yes" ] ) ){
    $loader->api->set_message( "ok", array(
      "inputs" => $inputs
    ) );
    return;
  }

  $prompt = bof()->nest->user_input( "post", "prompt", $inputs["prompt"]["validator"][0], $inputs["prompt"]["validator"][1] );
  $core_name = bof()->nest->user_input( "post", "lorem_ai_i_core", $inputs["lorem_ai_i_core"]["validator"][0], $inputs["lorem_ai_i_core"]["validator"][1] );

  if ( empty( $prompt ) || empty( $core_name ) ){
    $loader->api->set_error( "Prompt can't be empty" );
    return;
  }

  $args = [];
  foreach( $inputs as $_k => $_v ){
    if ( substr( $_k, 0, strlen("lorem_ai_{$core_name}") ) == "lorem_ai_{$core_name}" ){
      $args["image.{$core_name}_".str_replace("lorem_ai_{$core_name}_i_","",$_k)] = bof()->nest->user_input( "post", $_k, $_v["validator"][0], $_v["validator"][1] );
    }
  }

  try {
    $output = bof()->ai->set_settings($args)->__get( $core_name )->check_settings('none')->generate_image_from_text( $prompt, array(
      "size" => "small"
    ) );
  } catch( Exception $err ){
    $loader->api->set_error( "Failed: " . $err->getMessage() );
    return;
  }

  $loader->api->set_message( "ok", array(
    "url" => $output
  ) );

}

?>
