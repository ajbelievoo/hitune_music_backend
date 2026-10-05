<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_extension( $loader, $excuter, $args ){

  $plugin_list = $loader->plug->list();
  $plugin_name = $loader->nest->user_input( "get", "name", "string" );
  $action = $loader->nest->user_input( "get", "do", "in_array", [ "values" => [ "install", "uninstall", "activate", "deactivate", "update" ] ] );

  if ( $plugin_name == "self" && $action == "update" ){

    $process = $loader->plug->process_create( "update", "self" );
    $txt = "Creating a process to update script";

    /*$process = "script";
    $byCronjob = "Created the process. Waiting for cronjob to execute it";
    $txt = "Creating a process to update script.<br><br>This process is designed to update the script in the background using <b style='color:rgb(var(--c_green))'>cronjob</b><br><br>If no process was made a few minutes after seeing this message, you should check your <a style='text-decoration: underline' href='admin/cronjobs' target='_blank'>cronjob log</a> and make sure cronjob is running<br><br>";
    if ( ( $sta = bof()->object->db_setting->get( "self_update" ) ) ){

      $byCronjob = "There already is an ongoing process";

      if ( $sta == 1 )
      $byCronjob .= ". It has not been picked up by cronjob yet. If this has been going on more than few minutes, you should check your cronjob<br><br>";

      else
      $byCronjob .= ". It has been picked up by cronjob. You should be able to see the logs here or in cronjob logs<br><br>";

      $_time = bof()->object->db_setting->get( "self_update_time" );

      if ($sta == 2 && ((time() - $_time) > 30 * 60)) {
        $byCronjob .= "--> The existing process is more than 30 minutes old. Restarting the process<br><br>";
        bof()->object->db_setting->set("self_update_time", time());
        bof()->object->db_setting->set("self_update_pid", false);
        bof()->object->db_setting->set("self_update_c_pid", false);
        bof()->object->db_setting->set("self_update_c_gid", false);
        bof()->object->db_setting->set("self_update", "1");
      }

    } else {
      bof()->object->db_setting->set( "self_update_time", time() );
      bof()->object->db_setting->set( "self_update_pid", false );
      bof()->object->db_setting->set( "self_update_c_pid", false );
      bof()->object->db_setting->set( "self_update_c_gid", false );
      bof()->object->db_setting->set( "self_update", "1" );
    }*/

  }
  elseif ( $plugin_name && $action ? in_array( $plugin_name, array_keys( $plugin_list ), true ) : false ){

    $plugin_data = $plugin_list[ $plugin_name ];

    if ( $action == "install" && $plugin_data["installable"] && !$plugin_data["installed"] && in_array( $plugin_data["type"], [ "plugin", "tool", "theme" ], true ) ){
      $process = $loader->plug->process_create( "install", $plugin_data );
      $txt = "Creating a process to install <b>{$plugin_data["name"]}</b> version {$plugin_data["version_hr"]}";
    }

    else if ( $action == "update" && $plugin_data["installable"] && $plugin_data["installed"] && $plugin_data["exists"] && $plugin_data["installed_version"] < $plugin_data["version"] ){
      $process = $loader->plug->process_create( "update", $plugin_data );
      $txt = "Creating a process to update <b>{$plugin_data["name"]}</b> from version {$plugin_data["installed_version_hr"]} to version {$plugin_data["version_hr"]}";
    }

    else if ( $action == "activate" && $plugin_data["installable"] && !$plugin_data["installed"] && $plugin_data["exists"] && in_array( $plugin_data["type"], [ "plugin", "tool" ], true ) ){
      $process = $loader->plug->process_create( "activate", $plugin_data );
      $txt = "Creating a process to activate <b>{$plugin_data["name"]}</b>";
    }

    else if ( $action == "uninstall" && $plugin_data["exists"] && in_array( $plugin_data["type"], [ "plugin", "tool", "theme" ], true ) ){
      $process = $loader->plug->process_create( "uninstall", $plugin_data );
      $txt = "Creating a process to uninstall <b>{$plugin_data["name"]}</b>";
    }

    else if ( $action == "deactivate" && $plugin_data["installed"] && in_array( $plugin_data["type"], [ "plugin", "tool" ], true ) ){
      $process = $loader->plug->process_create( "deactivate", $plugin_data );
      $txt = "Creating a process to de-activate <b>{$plugin_data["name"]}</b>";
    }

  }

  if ( empty( $process ) )
  $loader->api->set_error( "Failed", [ $plugin_data ] );

  else
  $loader->api->set_message( "<div class='_head'>{$txt}</div>", array(
    "process" => $process,
    "byCronjob" => !empty( $byCronjob ) ? $byCronjob : false
  ) );

}

?>
