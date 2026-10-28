<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_radios( $loader, $excuter, $args ){

  $items = array();

  $stations = bof()->object->db_setting->get( "radio_stations" );
  if ( is_string( $stations ) ) $stations = json_decode( $stations, true );

  if ( !empty( $stations ) && is_array( $stations ) ){
    foreach( $stations as $station ){

      if ( empty( $station["url"] ) && empty( $station["stream_url"] ) ) continue;

      $name = !empty( $station["name"] ) ? $station["name"] : ( !empty( $station["title"] ) ? $station["title"] : "Radio" );

      $items[] = array(
        "hash" => md5( $name . ( !empty( $station["url"] ) ? $station["url"] : $station["stream_url"] ) ),
        "title" => $name,
        "name" => $name,
        "sub_title" => !empty( $station["sub_title"] ) ? $station["sub_title"] : ( !empty( $station["genre"] ) ? $station["genre"] : null ),
        "url" => !empty( $station["url"] ) ? $station["url"] : $station["stream_url"],
        "stream_url" => !empty( $station["stream_url"] ) ? $station["stream_url"] : $station["url"],
        "image" => !empty( $station["image"] ) ? $station["image"] : ( !empty( $station["logo"] ) ? $station["logo"] : null ),
        "logo" => !empty( $station["logo"] ) ? $station["logo"] : ( !empty( $station["image"] ) ? $station["image"] : null ),
        "object_type" => "radio",
        "ot" => "radio"
      );

    }
  }

  $loader->api->set_message( "ok", array(
    "items" => $items
  ) );

}

?>
