<?php

if ( !defined( "bof_root" ) ) die;

$bof->listen( "object_page", "_get_widgets_pre", function( $method_args, $method_results, $loader ){

  

} );
$bof->listen( "object_ads", "get_placements_after", function( $method_args, &$method_results, $loader ){

  $objects = [ "m_album", "m_artist", "a_book", "a_narrator", "a_translator", "a_writer", "p_show", "p_podcaster", "m_track", "p_episode", "r_station", "b_post" ];

  foreach( $objects as $object_name ){
    if ( bof()->object->core_files->validate_key( "object", $object_name ) ){
      $the_object = bof()->object->__get( $object_name )->bof()["label"];
      $theme_placements[ "theme_{$object_name}_top" ] = $the_object . " - Top - 975*250";
    }
  }

  $method_results = array_merge( $method_results, $theme_placements );

} );

require_once( dirname(__FILE__) . "/bof_functions_shared.php" );

?>
