<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

$plugins = bof()->object->db_setting->get( "plugins" );

if ( !empty($plugins) ){

  foreach( $plugins as $plugin ){

    $handshake_file = plugins_root . "/{$plugin}/_handshake.php";
    if ( !is_file( $handshake_file ) ) continue;

    bof()->plugin( $plugin, array(
      "handshake_file" => $handshake_file
    ));

  }

}

if ( bof()->getName() == "bof_client" )
bof()->theme->run_functions();

?>
