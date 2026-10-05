<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_pageBuilder_widget_order( $loader, $excuter, $args ){

  $_orders_raw = $loader->nest->user_input( "post", "order", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9|_]", "min" => 10 ] );
  $_grid_orders = $loader->nest->user_input( "post", "grid_order", "json", [ "empty()" ] );
  $_page_id = $loader->nest->user_input( "post", "page_id", "int" );

  if ( $_page_id && $_orders_raw ){

    $_page_data = $loader->object->page->select(array("ID"=>$_page_id),array("_eq"=>["widgets"=>true],"pageBuilder" => true));
    $_page_widgets = $_page_data["widgets"];

    if ( !empty( $_page_widgets ) ){
      foreach( $_page_widgets as $_page_widget )
      $_page_widgets_wids[] = $_page_widget["unique_id"];
    }

    if ( !empty( $_page_widgets_wids ) ){

      $_orders = explode( "||", $_orders_raw );
      foreach( $_orders as $_order ){
        if ( $loader->nest->validate( $_order, "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9]", "min" => 10, "max" => 10 ] ) ){
          $_orders_validated[] = $_order;
        }
      }

      if ( count( array_diff( $_orders_validated, $_page_widgets_wids ) ) == 0 ){
        foreach( $_orders_validated as $_i => $_order_validated ){
          $loader->object->page_widget->update(array(
            "unique_id" => $_order_validated
          ),array(
            "i" => ( $_i + 1 )
          ));
        }
      }

      if ( !empty( $_grid_orders ) ){
        foreach( $_grid_orders as $_gid => $_gws ){
          if ( in_array( $_gid, $_orders_validated, true ) ){
            foreach( $_gws as $_gOrder => $_gw ){
              if ( !in_array( $_gw, $_orders_validated, true ) ){
                $loader->object->page_widget->update(array(
                  "unique_id" => $_gw
                ),array(
                  "i" => "{$_gid}_{$_gOrder}"
                ));
              }
            }
          }
        }
      }

    }

  }

  $loader->api->set_message( "done", [ "D" => $_page_data ] );

}

?>
