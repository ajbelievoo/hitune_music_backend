<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_rename( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "playlist", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "playlist_hash", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );
  $name = $loader->nest->user_input( "post", "name", "string", [ "strip_emoji" => false ] );

  if ( !$playlist_id || !$name ){
    if ( !$name ) $loader->api->set_error( "name_cant_be_empty" );
    return;
  }

  $playlist = $loader->object->ugc_playlist->select(
    array(
      "hash" => $playlist_id,
      "user_id" => $loader->user->get()->ID
    )
  );

  if ( !$playlist ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $loader->object->ugc_playlist->update(
    array(
      "ID" => $playlist["ID"]
    ),
    array(
      "name" => $name,
      "time_update" => $loader->general->mysql_timestamp()
    )
  );

  bof()->chapar->notify_admin("playlist_edited", array(
    "name" => $playlist["name"],
    "new_name" => $name
  ));

  $loader->api->set_message( "ok" );

}

?>
