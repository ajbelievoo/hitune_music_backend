<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_track_lyrics( $loader, $excuter, $args ){

  $object_type = $loader->nest->user_input( "post", "object_type", "string" );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "object", "md5" );

  // Accept legacy field names too (ot/hash)
  if ( !$object_type )
  $object_type = $loader->nest->user_input( "post", "ot", "string" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "hash", "md5" );

  if ( $object_type != "m_track" || !$object_hash ){
    $loader->api->set_error( "bad_inputs" );
    return;
  }

  $object_item = $loader->object->m_track->select(
    array(
      "hash" => $object_hash
    ),
    array(
      "clean" => false,
      "cache_load_rt" => false
    )
  );

  if ( !$object_item ){
    $loader->api->set_error( "found_nothing" );
    return;
  }

  $raw_lyrics = !empty( $object_item["lyrics"] ) ? $object_item["lyrics"] : "";

  // Fall back to automated sources when nothing is stored
  if ( !strlen( trim( $raw_lyrics ) ) ){

    $fetch_automated = bof()->lyric->fetch( $object_item );

    if ( $fetch_automated === false || $fetch_automated === null ){
      $loader->api->set_message( "ok", array(
        "type" => "none",
        "lyrics" => "",
        "lrc" => ""
      ) );
      return;
    }

    if ( $fetch_automated["type"] === "string" ){
      $raw_lyrics = str_replace( [ "<br>", "<br/>", "<br />" ], "\n", $fetch_automated["data"] );
    }
    elseif ( $fetch_automated["type"] === "musixmatch" ){
      $loader->api->set_message( "ok", array(
        "type" => "musixmatch",
        "lyrics" => $fetch_automated["lyrics"],
        "lrc" => ""
      ) );
      return;
    }

  }

  if ( !strlen( trim( $raw_lyrics ) ) ){
    $loader->api->set_message( "ok", array(
      "type" => "none",
      "lyrics" => "",
      "lrc" => ""
    ) );
    return;
  }

  $raw_lyrics = str_replace( "\r\n", "\n", $raw_lyrics );

  // Detect LRC (time-synced) content
  $is_lrc = (bool) preg_match( "/\[\d{1,2}:\d{2}(?:[\.:]\d{1,3})?\]/", $raw_lyrics );

  $lrc = "";
  $lyrics = $raw_lyrics;

  if ( $is_lrc ){
    $lrc = $raw_lyrics;
    // Plain-text view: strip LRC timestamps/tags
    $lyrics = preg_replace( "/\[[^\]]*\]/", "", $raw_lyrics );
    $lyrics = preg_replace( "/\n{2,}/", "\n", $lyrics );
    $lyrics = trim( $lyrics );
  }

  $loader->api->set_message( "ok", array(
    "type" => "local",
    "lyrics" => $lyrics,
    "lrc" => $lrc
  ) );

}

?>
