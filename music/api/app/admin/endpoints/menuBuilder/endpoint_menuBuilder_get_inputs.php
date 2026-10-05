<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_menuBuilder_get_inputs( $loader, $excuter, $args ){

  $data = $loader->nest->user_input( "post", "data", "json" );
  $newInputs = [];

  if ( $data ){
    if ( !empty( $data["user_roles_only"] ) ? $loader->nest->validate( $data["user_roles_only"], "int_imploded" ) : false ){

      $input_w = array(
        "input" => array(
          "name" => "user_roles_only",
          "value" => $data["user_roles_only"]
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user_role",
            "multi" => true
          )
        )
      );

      $newInputs["user_roles_only"] = $loader->bofInput->parse( $input_w )["data"];

    }
    if ( !empty( $data["user_roles_exclude"] ) ? $loader->nest->validate( $data["user_roles_exclude"], "int_imploded" ) : false ){

      $input_w = array(
        "input" => array(
          "name" => "user_roles_exclude",
          "value" => $data["user_roles_exclude"]
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user_role",
            "multi" => true
          )
        )
      );

      $newInputs["user_roles_exclude"] = $loader->bofInput->parse( $input_w )["data"];

    }
  }

  $loader->api->set_message( "Loaded", array(
    "changed" => $newInputs
  ) );

}

?>
