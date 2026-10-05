<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_files_storage_setting( $loader, $excuter, $args ){

  $structure = $loader->object->file_host->get_ss();
  $file_hosts_setting = $loader->object->db_setting->get( "fh_setting", null, true );
  $file_hosts = $loader->object->file_host->select([],[
    "limit" => 100,
    "empty_select" => true
  ]);

  foreach( $file_hosts as $_file_host ){
    $file_hosts_simplified[ $_file_host["ID"] ] = [ $_file_host["ID"], $_file_host["name"] ];
  }

  if ( !empty( $structure ) ){
    foreach( $structure as &$setting_group ){
      foreach( $setting_group["inputs"] as $setting_group_input_key => &$setting_group_input ){
        if ( substr( $setting_group_input_key, 0, 3 ) == "fh_" ){
          $setting_group_input["input"]["args"]["values"] = $setting_group_input_key == "fh_default" ? $file_hosts_simplified : array_merge( $file_hosts_simplified, array( "default" => [ "default", "-- Default Storage --" ] ) );
          $setting_group_input["validator"]["args"]["values"] = array_keys( $file_hosts_simplified );
          if ( !empty( $file_hosts_setting[ substr( $setting_group_input_key, 3 ) ] ) )
          $setting_group_input["input"]["value"] = $file_hosts_setting[ substr( $setting_group_input_key, 3 ) ];
        }
      }
    }
  }

  $loader->api->set_message( "Welcome", [ "groups" => $structure ] );

}

?>
