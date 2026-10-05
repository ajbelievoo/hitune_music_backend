<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_blitz_search( $loader, $excuter, $args ){

  $query = $loader->nest->user_input( "post", "query", "string" );
  
  try {
    $search_results = $loader->search->exe( array(
      "query" => $query,
      "page" => 1,
      "object_type" => "m_track"
    ) );
    
    $loader->api->set_message( "ok", [ 
      "results" => $search_results["widgets"]["m_track"]["items"] ?? []
    ] );
  } catch( Exception $error ){
    $loader->api->set_error( $error->getMessage() );
  }

}
