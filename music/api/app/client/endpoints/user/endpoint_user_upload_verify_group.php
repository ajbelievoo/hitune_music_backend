<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_upload_verify_group( $loader, $excuter, $args ){

  try {
    $verify = $loader->upload->setup()->verify_content_group();
  } catch( bofException $err ){
    $loader->api->set_error( $err->getMessage(), $err->getExtra(), [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $loader->api->set_message( "ok", $verify );

}

?>
