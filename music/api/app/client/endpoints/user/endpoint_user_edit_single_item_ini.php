<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_edit_single_item_ini( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object" );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( $object_name && $object_hash ){

    $content_types = $loader->upload->setup()->get_c_types();
    if ( $content_types ){
      foreach( $content_types as $content_type ){
        if ( $content_type["single_object"] == $object_name ){
          $_type = "single";
          $_content = $content_type;
          break;
        }
        elseif ( $content_type["group_object"] == $object_name ){
          $_type = "group";
          $_content = $content_type;
          break;
        }
      }
    }

    if ( !empty( $_type ) ){

      if ( $_type == "single" ){
        $inputs = $_content["get_item_inputs"]();
        $inputs_filled = $_content["fill_item_inputs"]( $inputs, $object_name, $object_hash );
        if ( $inputs_filled ){
          $inputs_parsed = $loader->bofForm->parse_inputs( $inputs_filled["inputs"] );
          foreach( $inputs_parsed as &$p ){
            $p["label"] = bof()->object->language->turn( $p["label"], [], [ "uc_first" => true, "lang" => "users" ] );
          }
          $loader->api->set_message( "ok", [ "inputs" => $inputs_parsed, "groups" => $inputs_filled["groups"] ] );
        }
      } else {
        $ginputs = $_content["get_group_inputs"](null,"studio");
        $ginputs_filled = $_content["fill_group_inputs"]( $ginputs, $object_name, $object_hash );
        if ( $ginputs_filled ){
          $ginputs_parsed = $loader->bofForm->parse_inputs( $ginputs_filled["inputs"] );
          foreach( $ginputs_parsed as &$p ){
            $p["label"] = bof()->object->language->turn( $p["label"], [], [ "uc_first" => true, "lang" => "users" ] );
            $p["group"] = "basic";
          }
          $loader->api->set_message( "ok", [ "inputs" => $ginputs_parsed, "groups" => $ginputs_filled["groups"] ] );
        }
      }

    }

  }

}

?>
