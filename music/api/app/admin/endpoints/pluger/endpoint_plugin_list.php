<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_plugin_list( $loader, $excuter, $args ){

  $reqed_type = $loader->nest->user_input( "get", "type", "in_array", [ "values" => [ "plugin", "tool", "theme" ] ], "plugin" );

  $list = $loader->plug->list(
    $reqed_type
  );

  if ( $reqed_type == "tool" ){
    $scrappers = [ "bof_tool_soundcloud_scrapper", "bof_tool_youtube_scrapper", "bof_tool_wikimedia_scrapper", "bof_tool_spotify_scrapper" ];
    $reqed_scrappers = bof()->nest->user_input( "get", "scrapper", "equal", [ "value" => "yes" ] );
    if ( $reqed_scrappers ){
      $oList = $list;
      $list = [];
      foreach( $scrappers as $scrapper ){
        if ( !empty( $oList[ $scrapper ] ) )
        $list[ $scrapper ] = $oList[ $scrapper ];
      }
    } else {
      foreach( $scrappers as $scrapper )
      unset( $list[ $scrapper ] );
    }
  }

  $loader->api->set_message( "ok", [ "list" => $list ] );

}

?>
