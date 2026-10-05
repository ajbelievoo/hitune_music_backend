<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_spotify_browse_endpoint( $loader, $excuter, $args ){

  $type = bof()->nest->user_input( "post", "type", "in_array", [ "values" => [ "artist", "album", "track", "playlist", "user_lists", "cat_lists" ] ] );
  $query = bof()->nest->user_input( "post", "query", "string" );

  if ( $type && $query ){

    bof()->spotify->setup();
    $result = [];

    if ( in_array( $type, [ "artist", "album", "track", "playlist" ] ) ){
      $result_raw = bof()->spotify_helper->search( $query, $type );
      if ( !empty( $result_raw ) ){
        foreach( $result_raw as $_R ){
          $result[ $_R["id"] ] = array(
            "id" => $_R["id"],
            "name" => $_R["name"],
            "image" => !empty( $_R["images"] ) ? end( $_R["images"] )["url"] : false
          );
        }
      }
    }
    elseif( $type == "user_lists" ){
      $result_raw = bof()->spotify->user( $query );
      if ( !empty( $result_raw ) ){
        $result[ $result_raw["id"] ] = array(
          "id" => $result_raw["id"],
          "name" => $result_raw["display_name"] ? $result_raw["display_name"] : $result_raw["id"],
          "image" => !empty( $result_raw["images"] ) ? end( $result_raw["images"] )["url"] : false
        );
      }
    }
    elseif ( $type == "cat_lists" ){
      $result_raw = bof()->spotify->browse_categories( $query );
      if ( !empty( $result_raw["categories"]["items"] ) ){
        foreach( $result_raw["categories"]["items"] as $_R ){
          $result[ $_R["id"] ] = array(
            "id" => $_R["id"],
            "name" => $_R["name"],
            "image" => !empty( $_R["icons"] ) ? end( $_R["icons"] )["url"] : false
          );
        }
      }
    }

    $loader->api->set_message( "ok", [ "result" => $result ] );
    
  }

}

?>
