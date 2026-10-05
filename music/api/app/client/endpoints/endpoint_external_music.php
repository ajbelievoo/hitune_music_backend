<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_external_music( $loader, $excuter, $args ){

  $source = $loader->nest->user_input( "get", "source", "in_array", [ "values" => [ "spotify" ] ] );
  $type = $loader->nest->user_input( "get", "type", "in_array", [ "values" => [ "artist", "album", "track" ] ] );
  $id = $loader->nest->user_input( "get", "id", "string" );

  if ( $source == "spotify" ? $loader->object->db_setting->get( "spotify_automation" ) : false ){

    if ( $type == "artist" ){

      $loader->spotify_helper->record( true, array(
        "create_album_get_artist_for_genres" => false,
        "create_track_get_artist_for_genres" => false
      ) );

      $sync = $loader->spotify_helper->get_artist( $id, array(
        "artist_albums" => true,
        "artist_albums_singular" => false,
        "artist_related" => true,
        "artist_tracks" => true,
      ) );

      $loader->spotify_helper->record( false );

    }
    elseif ( $type == "album" ){

      $loader->spotify_helper->record( true, array(
        "create_album_get_artist_for_genres" => false,
        "create_track_get_artist_for_genres" => false
      ) );

      $sync = $loader->spotify_helper->get_album( $id );
      $loader->spotify_helper->record( false );

    }

    $get = $loader->object->__get( "m_{$type}" )->select(["ID"=>$sync]);
    if ( $get ){
      $loader->api->set_message( "redirecting", array(
        "url" => $loader->seo->url( "m_{$type}", $get )
      ) );
      return;
    }

    return;
  }

  $loader->api->set_error( "Failed" );

}

?>
