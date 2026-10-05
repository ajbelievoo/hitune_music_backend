<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_upload_config( $loader, $excuter, $args ){

  try {
    $config = $loader->upload->setup()->get_config();
    $loader->api->set_message( "ok", array_merge( $config, array(
      "seo" => array(
        "title" => bof()->object->language->turn( "upload", [], [ "uc_first" => true, "lang" => "users" ] )
      )
    ) ) );
  } catch( bofException $err ){
  }

}

?>
