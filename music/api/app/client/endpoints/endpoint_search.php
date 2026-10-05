<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_search( $loader, $excuter, $args ){

  $query = $loader->nest->user_input( "post", "query", "string" );
  $page = $loader->nest->user_input( "post", "page", "int", [ "min" => 1 ], 1 );
  $object_type = $loader->nest->user_input( "post", "ot", "string_abcd" );

  try {
    $search_results = $loader->search->exe( array(
      "query" => $query,
      "page" => $page,
      "object_type" => $object_type
    ) );
    $loader->api->set_message( "ok", [ 
      "widgets" => $search_results["widgets"], 
      "history" => !empty( $search_results["history"]["hash"] ) ? $search_results["history"]["hash"] : null 
    ] );
  } catch( Exception $error ){
    $loader->api->set_error( $error->getMessage(), [ "output_args" => [ "turn" => false ] ] );
  }



}

?>
