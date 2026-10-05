<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_pageBuilder_widget_edit( $loader, $excuter, $args ){

  $wid_id = $loader->nest->user_input( "post", "wid_id", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9]", "min" => 10, "max" => 10 ] );
  if ( !$wid_id ){
    $loader->api->set_error( "Invalid Request", [] );
    return;
  }

  $wid_data = $loader->object->page_widget->select(["unique_id"=>$wid_id]);
  if ( !$wid_data ){
    $loader->api->set_error( "Invalid Request", [] );
    return;
  }

  $native_data = false;
  if ( !empty( $wid_data["native"] ) ){
    $pre_designs = bof()->object->page->_get_pre_designs();
    if ( $pre_designs ){
      foreach( $pre_designs as $pre_design ){
        if ( empty( $pre_design["widgets"] ) ) continue;
        if ( !empty( $pre_design["widgets"][ $wid_data["native"] ] ) ){
          $native_data = $pre_design["widgets"][ $wid_data["native"] ];
        }
      }
    }
  }

  $widgets = $loader->object->page->_get_widgets(["raw"=>true]);
  $_widget_structure = $widgets["items"][ $wid_data["name"] ];
  $_widget_inputs = !empty( $_widget_structure[ "inputs" ] ) ? $_widget_structure[ "inputs" ] : [];
  $_group_inputs = $widgets["groups"][ $_widget_structure["group"] ][ "inputs" ];
  $_rules_inputs = $widgets["rules"];
  $inputs = $_group_inputs ? array_merge( $_rules_inputs, $_group_inputs, $_widget_inputs ) : $_widget_inputs;

  if ( $wid_data["args_decoded"] ){
    foreach( $wid_data["args_decoded"] as $_k => $_v ){
      if ( !empty( $inputs[ $_k ] ) )
      $inputs[ $_k ][ "input" ][ "value" ] = $_v;
    }
  }

  foreach( $inputs as $_k => &$_v ){

    if ( !empty( $_v["bofInput"] ) ){
      $_v = $loader->bofInput->parse( $_v )["data"];
    }

    if ( !empty( $native_data["inputs"][ $_k ]["locked"] ) ){
      $_v["class"] = !empty( $_v["class"] ) ? "{$_v["class"]} locked" : "locked";
    }

  }

  $loader->api->set_message( "Loaded", [ "inputs" => $inputs ] );

}

?>
