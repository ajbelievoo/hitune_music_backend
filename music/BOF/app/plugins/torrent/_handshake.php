<?php

if ( !defined( "bof_root" ) ) die;

$bof->object->core_files->add_key(
  "class",
  "torrent",
  dirname(__FILE__)."/classes/class_torrent.php"
);

if ( !empty( $args["deluge_web_api_host"] ) && !empty( $args["deluge_web_api_password"] ) ){
  $bof->torrent->set_option( "deluge_web_api_host", $args["deluge_web_api_host"] );
  $bof->torrent->set_option( "deluge_web_api_password", $args["deluge_web_api_password"] );
}

if ( !empty( $args["transmission_rpc_addr"] ) && !empty( $args["transmission_user_pwd"] ) && !empty( $args["transmission_dl_dir"] ) ){
  $bof->torrent->set_option( "transmission_rpc_addr", $args["transmission_rpc_addr"] );
  $bof->torrent->set_option( "transmission_user_pwd", $args["transmission_user_pwd"] );
  $bof->torrent->set_option( "transmission_dl_dir", $args["transmission_dl_dir"] );
}

?>
