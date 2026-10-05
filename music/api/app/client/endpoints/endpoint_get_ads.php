<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_get_ads( $loader, $excuter, $args ){

  $placement = $loader->nest->user_input( "post", "placement", "string" );

  if ( !empty( $loader->user->check()->extra["roles"] ) ? in_array( "ads", array_keys( $loader->user->check()->extra["roles"] ), true ) : false ){
    if ( $loader->user->check()->extra["roles"]["ads"] === false )
    {
      $loader->api->set_error( "none", [ "output_args" => [ "turn" => false ] ] );
      return;
    }
  }


  if ( $placement === "bof_AUDI0" ){

    $get_ads = $loader->object->ads->select(
      array(
        "displayable" => true,
        "for_muse" => true
      ),
      array(
        "for_display" => true,
        "limit" => 1,
        "order_by" => "RAND()",
        "order" => " ",
        "cache_load_rt" => false,
      )
    );

    if ( $get_ads ){

      if ( $get_ads["type"] == "audio" ){
        $address = $get_ads["audio_file"]["web_address"];
      } elseif ( $get_ads["type"] == "video" ){
        $address = $get_ads["video_file"]["web_address"];
      } elseif ( $get_ads["type"] == "youtube" ){
        $address = $get_ads["data_decoded"]["youtube_id"];
        $hook = "youtube_id";
      }
  
      if ( $get_ads ){
        $loader->api->set_message( "ok", array(
          "thingie_interval" => bof()->object->db_setting->get("ads_audio_interval"),
          "thingie_skipable" => true,
          "thingie_skipable_threshold" => 15,
          "thingie_type" => $get_ads["type"],
          "thingie" => array(
            !empty( $hook ) ? $hook : "address" => $address,
            "banner" => $get_ads["banner_file"]["image_original"],
            "url" => web_address . "api/redirect_to/{$get_ads["ID"]}/?t=".microtime(true),
            "title" => $get_ads["name"]
          )
        ) );
        return;
      }

    }

    $loader->api->set_error( "none", [ "output_args" => [ "turn" => false ] ] );
    return;

  }
  elseif ( $placement ){

    $get_ads = $loader->object->ads->select(
      array(
        "place_id" => $placement,
        "displayable" => true,
        array(
          "oper" => "OR",
          "cond" => array(
            [ "type", "=", "banner" ],
            [ "type", "=", "script" ],
            [ "type", "=", "gau" ],
          )
        )
      ),
      array(
        "for_display" => true,
        "limit" => 1,
        "order_by" => "RAND()",
        "order" => " ",
        "cache_load_rt" => false,
      )
    );

    if ( $get_ads ){
      $loader->api->set_message( "ok", array(
        "placement" => $placement,
        "html" => $get_ads
      ) );
      return;
    }

    $loader->api->set_error( "none", [ "output_args" => [ "turn" => false ] ] );
    return;

  }

}

?>
