<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_delete( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "playlist", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "playlist_hash", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );

  if ( !$playlist_id )
  return;

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

  $loader->object->ugc_playlist->delete(
    array(
      "ID" => $playlist["ID"]
    )
  );

  bof()->chapar->notify_admin("playlist_removed", array(
    "name" => $playlist["name"],
  ));

  $loader->api->set_message( "deleted" );

}

?>
