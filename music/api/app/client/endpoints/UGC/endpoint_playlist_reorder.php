<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_playlist_reorder( $loader, $excuter, $args ){

  $playlist_id = $loader->nest->user_input( "post", "playlist", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "playlist_hash", "md5" );
  if ( !$playlist_id )
  $playlist_id = $loader->nest->user_input( "post", "id", "md5" );
  $order_csv = $loader->nest->user_input( "post", "order", "string" );

  if ( !$playlist_id || !$order_csv )
  return;

  $playlist = $loader->object->ugc_playlist->select(
    array(
      "hash" => $playlist_id,
      "user_access_id" => $loader->user->get()->ID
    )
  );

  if ( !$playlist ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $hashes = array();
  foreach( explode( ",", $order_csv ) as $h ){
    $h = trim( $h );
    if ( preg_match( "/^[a-f0-9]{32}$/i", $h ) )
    $hashes[] = strtolower( $h );
  }

  if ( empty( $hashes ) )
  return;

  $items = $loader->object->ugc_property->select(
    array(
      "related_object_id" => $playlist["ID"],
      "related_object_name" => "ugc_playlist",
      "type" => "playlist",
    ),
    array(
      "single" => false,
      "limit" => false,
      "clean" => false
    )
  );

  if ( empty( $items ) ){
    $loader->api->set_message( "ok" );
    return;
  }

  $hash_to_property = array();
  foreach( $items as $item ){
    $the_object = $loader->object->__get( $item["object_name"] );
    if ( !$the_object ) continue;
    $row = $the_object->select(
      array( "ID" => $item["object_id"] ),
      array( "clean" => false )
    );
    if ( !empty( $row["hash"] ) )
    $hash_to_property[ strtolower( $row["hash"] ) ] = $item["ID"];
  }

  $i = 1;
  foreach( $hashes as $hash ){
    if ( isset( $hash_to_property[ $hash ] ) ){
      $loader->db->_update( array(
        "table" => "_u_properties",
        "where" => array(
          [ "ID", "=", $hash_to_property[ $hash ] ]
        ),
        "set" => array(
          [ "i", $i ]
        )
      ) );
    }
    $i++;
  }

  $loader->object->ugc_playlist->update(
    array(
      "ID" => $playlist["ID"]
    ),
    array(
      "time_update" => $loader->general->mysql_timestamp()
    )
  );

  $loader->db->query("DELETE FROM _bof_cache_db WHERE query_hash = '878f14ec7de994025b527a2e3b3bd196' ");
  $loader->api->set_message( "ok" );

}

?>
