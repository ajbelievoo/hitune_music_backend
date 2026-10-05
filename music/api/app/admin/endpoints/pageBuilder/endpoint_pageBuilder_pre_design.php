<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_pageBuilder_pre_design( $loader, $excuter, $args ){

  $pre_designs = bof()->object->page->_get_pre_designs();
  $code = $loader->nest->user_input( "post", "code", "in_array", [ "values" => array_keys( $pre_designs ) ] );

  if ( $code ){

    $pre_design = $pre_designs[ $code ];

    if ( !empty( $pre_design["required_plugins"] ) ){
      foreach ( $pre_design["required_plugins"] as $req_plugin ){
        if ( !bof()->plugin_exists( $req_plugin ) ){
          $loader->api->set_error( "A required plugin: {$req_plugin} for this design, is missing!" );
          return;
        }
      }
    }

    $pageID = bof()->object->page->insert( array(
      "name" => $pre_design["name"],
      "pre_design" => $code,
      "seo_url" => uniqid(),
      "active" => 1
    ) );

    if ( !empty( $pre_design["widgets"] ) ){
      $ids = [];
      foreach( $pre_design["widgets"] as $pre_design_widget_code => $pre_design_widget ){

        if ( empty( $pre_design_widget["install"] ) ) continue;

        $ids["ID"] = $ids[ "{$pre_design_widget_code}_ID" ] = $unique_id = substr( md5(uniqid().rand(1,999999999).microtime()), 0, 10 );

        $pre_design_widget_install_t = $pre_design_widget["install"]["t"];
        $pre_design_widget_install_i = $pre_design_widget["install"]["i"];
        $pre_design_widget_install_args = $pre_design_widget["install"]["args"];
        foreach( $ids as $_k => $_v ){
          $pre_design_widget_install_i = str_replace( "%{$_k}%", $_v, $pre_design_widget_install_i );
          $pre_design_widget_install_args = str_replace( "%{$_k}%", $_v, $pre_design_widget_install_args );
        }

        bof()->object->page_widget->insert( array(
          "page_id" => $pageID,
          "unique_id" => $unique_id,
          "i" => $pre_design_widget_install_i,
          "name" => $pre_design_widget_install_t,
          "native" => $pre_design_widget_code,
          "args" => $pre_design_widget_install_args,
          "active" => 1
        ) );

      }
    }

    if ( !empty( $pre_design["generic_widgets"] ) ){
      $ids = [];
      foreach( $pre_design["generic_widgets"] as $pre_design_widget_code => $pre_design_widget ){

        $ids["ID"] = $ids[ "{$pre_design_widget_code}_ID" ] = $unique_id = substr( md5(uniqid().rand(1,999999999).microtime()), 0, 10 );

        $pre_design_widget_install_t = $pre_design_widget["t"];
        $pre_design_widget_install_i = $pre_design_widget["i"];
        $pre_design_widget_install_args = $pre_design_widget["args"];
        foreach( $ids as $_k => $_v ){
          $pre_design_widget_install_i = str_replace( "%{$_k}%", $_v, $pre_design_widget_install_i );
          $pre_design_widget_install_args = str_replace( "%{$_k}%", $_v, $pre_design_widget_install_args );
        }

        bof()->object->page_widget->insert( array(
          "page_id" => $pageID,
          "unique_id" => $unique_id,
          "i" => $pre_design_widget_install_i,
          "name" => $pre_design_widget_install_t,
          "native" => $pre_design_widget_code,
          "args" => $pre_design_widget_install_args,
          "active" => 1
        ) );

      }
    }

    $loader->api->set_message( "done", [ "pageID" => $pageID ] );

  }

}

?>
