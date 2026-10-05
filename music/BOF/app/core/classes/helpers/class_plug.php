<?php

if ( !defined( "bof_root" ) ) die;

class plug extends bof_type_class{

  protected $plugins = null;
  public function list( $types=["plugin","theme","tool","script"], $full=false, $simple=false ){

    if ( !is_array( $types ) ) $types = [ $types ];
    $extension_list = bof()->boac->get_extensions( $types, $simple );
    $extension_activated = $this->_bof_this->activated_plugins();
    $extension_existing = $this->_bof_this->existing_plugins();

    $_plugins = $extension_list["list"];

    if ( $_plugins ){
      foreach( $_plugins as $code => &$plugin ){

        $plugin["exists"] = in_array( $code, $extension_existing, true );
        $plugin["installed"] = $plugin["type"] == "theme" ? $plugin["exists"] : in_array( $code, $extension_activated, true );

        if ( $plugin["sta"] == 1 ){
          $btn = array(
            "title" => "Coming soon",
            "type" => "secondary",
          );
        }
        elseif ( $plugin["installable"] ){
          if ( $plugin["installed"] && $plugin["exists"] ){

            $plugin["installed_version"] = $this->_bof_this->read( $plugin["type"], $plugin["code"] )["version"];
            $plugin["installed_version_hr"] = ( substr( $plugin["installed_version"], 0, 1 ) . "." . substr( $plugin["installed_version"], 1, 1 ) . "." . substr( $plugin["installed_version"], 2, 2 ) );
            $plugin["price_label"] = "Installed";

            $_cs = array(
              array(
                "title" => "De-activate",
                "link" => "extension/{$plugin["code"]}&do=deactivate",
              ),
              array(
                "title" => "Uninstall",
                "link" => "extension/{$plugin["code"]}&do=uninstall",
              ),
            );

            if ( $plugin["type"] == "theme" )
            unset( $_cs[0] );

            $btn = array(
              "title" => "Manage",
              "type" => "primary",
              "childs" => $_cs
            );

            if ( $plugin["version"] > $plugin["installed_version"] ){
              array_unshift( $btn["childs"], array(
                  "title" => "Update",
                  "link" => "extension/{$plugin["code"]}&do=update",
              ) );
              $btn["title"] = "Update";
              $plugin["price_label"] = "Installed v{$plugin["installed_version_hr"]}";
            }

          }
          elseif ( $plugin["exists"] && !$plugin["installed"] ){

            $_cs = array(
              array(
                "title" => "Activate",
                "link" => "extension/{$plugin["code"]}&do=activate",
              ),
              array(
                "title" => "Uninstall",
                "link" => "extension/{$plugin["code"]}&do=uninstall",
              )
            );

            if ( $plugin["type"] == "theme" )
            unset( $_cs[0] );

            $btn = array(
              "title" => "Activate",
              "type" => "primary",
              "childs" => $_cs
            );

          }
          else {

            $btn = array(
              "title" => "Install",
              "type" => "primary",
              "childs" => array(
                array(
                  "title" => "Install",
                  "link" => "extension/{$plugin["code"]}&do=install",
                )
              )
            );

          }
        }
        elseif ( !$plugin["purchased"] && $plugin["price"] ){

          $_btns = array(
            array(
              "title" => "How to purchase?",
              "link" => "https://support.busyowl.co/documentation/how_to_purchase_addons"
            )
          );

          if ( !empty( $plugin["busyowl_sale"] ) && !bof()->plugin_exists( "demo_babyProof" ) ){
            $_btns = array_merge(
              $_btns,
              array(
                array(
                  "title" => "Buy from Busyowl - \${$plugin["busyowl_sale"]}",
                  "link" => "https://support.busyowl.co/store"
                ),
              )
            );
          }

          if ( !empty( $plugin["envato_url"] ) ){
            $_btns = array_merge(
              $_btns,
              array(
                array(
                  "title" => "Buy from Envato - \${$plugin["price"]}",
                  "link" => $plugin["envato_url"]
                ),
                array(
                  "title" => "Enter purchase code",
                  "action" => "submit_ppc"
                )
              )
            );
          }

          $btn = array(
            "title" => "Purchase",
            "type" => "secondary",
            "childs" => $_btns
          );

          if ( $plugin["installed"] && $plugin["exists"] ){
            $btn["childs"] = array_merge( $btn["childs"], array(
              array(
                "title" => "De-activate",
                "link" => "extension/{$plugin["code"]}&do=deactivate",
              ),
              array(
                "title" => "Uninstall",
                "link" => "extension/{$plugin["code"]}&do=uninstall",
              ),
            ) );
          }
          elseif( $plugin["exists"] ){
            $btn["childs"] = array_merge( $btn["childs"], array(
              array(
                "title" => "Uninstall",
                "link" => "extension/{$plugin["code"]}&do=uninstall",
              ),
            ) );
          }

        }
        else {

          $btn = array(
            "title" => "Unkown Problem",
            "type" => "secondary",
          );

        }

        $plugin["btn"] = $btn;

      }
    }

    if ( !$full )
    return $_plugins;

    unset( $extension_list["list"] );
    return array_merge( array(
      "list" => $_plugins
    ), $extension_list );

  }

  // DB
  public function activated_plugins(){

    if( $this->plugins ) return $this->plugins;
    $db = bof()->object->db_setting->get( "plugins" );
    if ( empty( $db ) || !is_array( $db ) ? true : empty( $db[0] ) ) return [];
    $this->plugins = $db;
    return $db;

  }
  public function activate_plugin( $code ){

    $files = $this->_bof_this->existing_plugins();
    $db = $this->_bof_this->activated_plugins();

    if ( in_array( $code, $files, true ) && !in_array( $code, $db, true ) ){
      $new_db = array_merge( $db, [ $code ] );
      bof()->object->db_setting->set( "plugins", implode( ",", $new_db ) );
    }

    return true;

  }
  public function deactivate_plugin( $code ){

    $db = $this->_bof_this->activated_plugins();

    if ( in_array( $code, $db, true ) ){
      $new_db = array_diff( $db, [ $code ] );
      bof()->object->db_setting->set( "plugins", implode( ",", $new_db ) );
    }

    return true;

  }
  public function import_sql( $path, $fallOnFail=true, $process=false ){

    $file_content = file_get_contents( $path );
    $file_lines = bof()->general->explode_by_line( $file_content );
    $query_line = "";
    $ok_count = 0;

    foreach( $file_lines as $sql_line ){

      $sql_line = trim( $sql_line );
      if ( empty( $sql_line ) ) continue;
      if ( strlen( $sql_line ) <= 3 ) continue;
      if ( substr( $sql_line, 0, 2 ) == "--" ) continue;

      // is this line a full query?
      $query_line .= $sql_line;
      if ( substr( $sql_line, -1 ) == ";" ){
        $query_lines[] = substr( $query_line, 0, -1 );
        $query_line = "";
      }

    }

    if ( !empty( $query_lines ) ){
      foreach( $query_lines as $query_line ){

        $error = false;

        try {
          $query = bof()->db->query( $query_line );
        } catch( Exception|bofException $err ){
          $query = false;
          $error = $err->getMessage();
        }

        if ( $process && $error ){
          $this->_bof_this->process_log( $process, "txt", "SQL -> <i>{$query_line}</i> -> Error: {$error}" );
        }

        if ( !$query && !empty( bof()->db->error ) ){
          if ( $fallOnFail )
          throw new bofException( "Executing \"{$query_line}\" failed: <br><br>" . bof()->db->error );
        } else {
          $ok_count++;
        }

      }
    }

    return !empty( $query_lines ) ? $ok_count : 0;

  }

  // Files
  public function existing_plugins( $read=false ){

    $extension_directory = realpath( base_root . "/plugins" );
    $extension_directory_contents = scandir( $extension_directory );
    $theme_directory = realpath( base_root . "/themes" );
    $theme_directory_contents = [];
    if ( is_dir( $theme_directory ) )
    $theme_directory_contents = scandir( $theme_directory );

    if ( $read ){

      foreach( $extension_directory_contents as $extension_directory_content ){
        if ( $extension_directory_content == "." || $extension_directory_content == ".." ) continue;
        $extension_directory_content_version = $this->_bof_this->read( "plugin", $extension_directory_content )["version"];
        $all[ $extension_directory_content ] = $extension_directory_content_version;
      }
      if ( $theme_directory_contents ){
        foreach( $theme_directory_contents as $extension_directory_content ){
          if ( $extension_directory_content == "." || $extension_directory_content == ".." ) continue;
          $extension_directory_content_version = $this->_bof_this->read( "theme", $extension_directory_content )["version"];
          $all[ $extension_directory_content ] = $extension_directory_content_version;
        }
      }

      return $all;

    }

    $all = array_merge( $extension_directory_contents, $theme_directory_contents );

    if ( !empty( $all ) ){
      foreach( $all as $i => $v ){
        if ( $v == "." || $v == ".." )
        unset( $all[$i] );
      }
    }

    return $all;

  }
  public function read( $type, $code ){

    try {

      if ( $type == "plugin" || $type == "tool" ){
        if ( !is_file( plugins_root . "/{$code}/bof.php" ) )
        throw new Exception("no bof.php file");
        require( plugins_root . "/{$code}/bof.php" );
        if ( empty( $Config ) )
        throw new Exception("no \$Config variable");
        if ( defined( "beta_tester" ) && bof()->getName() == "bof_admin" && !empty( $Config["version"] ) ){
          $Config["version"] = $Config["version"] - 1;
        }
        return $Config;
      }

      elseif ( $type == "theme" ){
        if ( !is_file( themes_root . "/{$code}/bof.php" ) )
        throw new Exception("no bof.php file");
        require( themes_root . "/{$code}/bof.php" );
        if ( empty( $Config ) )
        throw new Exception("no \$Config variable");
        if ( defined( "beta_tester" ) && bof()->getName() == "bof_admin" && !empty( $Config["version"] ) ){
          $Config["version"] = $Config["version"] - 1;
        }
        return $Config;
      }

    }
    catch( Exception|Error $err ){
      return array(
        "version" => 0000
      );
    }

    fall("TODO:: plug:60");

  }

  // Process
  public function process_create( $type, $plugin ){

    if ( $plugin == "self" )
    $plugin = array(
      "type" => "script",
      "code" => "rkhmusic",
      "version" => "latest",
      "version_hr" => "latest",
      "installed_version" => version,
      "installed_version_hr" => version,
    );

    $ID = bof()->db->_insert( array(
      "table" => "_bof_plug_processes",
      "set" => array(
        [ "extension_type", $plugin["type"] ],
        [ "extension_name", $plugin["code"] ],
        [ "extension_version", $plugin["version"] ],
        [ "action", $type ],
        [ "user_id", bof()->user->get()->ID ],
      )
    ) );

    return $ID;

  }
  public function process_execute( $process ){

    bof()->general->set_full_fall( false );

    switch( $process["action"] ){

      case "install":
      case "update":
        try {
          $exe_process = $this->_p_install( $process );
        } catch( bofException $err ){

          $this->_bof_this->process_log( $process, "err", $err->getMessage() );

          if ( is_dir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/release" ) )
          bof()->file->rmdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/release" );

        }
        break;

      case "uninstall":
        $exe_process = $this->_p_uninstall( $process );
        break;

      case "activate":
        $exe_process = $this->_p_activate( $process );
        break;

      case "deactivate":
        $exe_process = $this->_p_deactivate( $process );
        break;

    }

    if ( empty( $exe_process ) ){
      bof()->db->_update( array(
        "table" => "_bof_plug_processes",
        "set" => array(
          [ "time_finish", "now()", true ],
          [ "sta", "0" ]
        ),
        "where" => array(
          [ "ID", "=", $process["ID"] ]
        )
      ) );
    }
    else {
      bof()->db->_update( array(
        "table" => "_bof_plug_processes",
        "set" => array(
          [ "time_finish", "now()", true ],
          [ "sta", "1" ]
        ),
        "where" => array(
          [ "ID", "=", $process["ID"] ]
        )
      ) );
    }


  }
  public function process_log( $process, $code, $text ){

    bof()->db->_insert( array(
      "table" => "_bof_plug_logs",
      "set" => array(
        [ "process_id", $process["ID"] ],
        [ "code", $code ],
        [ "text", $text ],
      )
    ) );

    if ( bof()->response->getType( "cli" ) ){
      bof()->cronjob->log_p(
        bof()->object->db_setting->get( "self_update_c_pid" ),
        bof()->object->db_setting->get( "self_update_c_gid" ),
        $text
      );
    }

  }

  protected function _p_install( $process ){

    if (!extension_loaded('zip'))
    throw new bofException( "PHP ZIP extension is not installed" );

    // Get latest release
    $this->_bof_this->process_log( $process, "txt", "Release -> Getting version {$process["extension_version"]}" );
    $release_data = bof()->boac->get_release( $process["extension_type"], $process["extension_name"], $process["extension_version"] );

    if ( !$release_data ? true : empty( $release_data["release"] ) || empty( $release_data["release"]["bof_file_source"] ) )
    throw new bofException( "Release -> Failed to find" );

    $release = $release_data["release"];
    $release_file = $release["bof_file_source"];
    $extension = $release_data["extension"];

    $this->_bof_this->process_log( $process, "txt", "Release -> Successfull. Size: " . ( bof()->general->filesize_hr( $release_file["size"] ) ) );
    if ( $extension["type"] != $process["extension_type"] || $extension["code"] != $process["extension_name"] )
    throw new bofException( "Release -> Corrupted process" );

    // Download the release
    $download = bof()->boac->download_release( $release_file["url"] );

    if ( !$download )
    throw new bofException( "Download -> Failed" );

    $download_dir = bof()->file->mkdir( pathinfo( $download, PATHINFO_DIRNAME ) . "/" . pathinfo( $download, PATHINFO_FILENAME ) . "_eded" );
    $this->_bof_this->process_log( $process, "txt", "Download -> Successfull: {$download_dir}" );

    // Compare the files
    $this->_bof_this->process_log( $process, "txt", "MD5 -> Checking" );
    if ( md5_file( $download ) != $release_file["md5"] )
    throw new bofException( "MD5 -> Failed to validate. Corrupted file - download failed" );
    $this->_bof_this->process_log( $process, "txt", "MD5 -> Successfully validated" );

    // Unzip the release
    try {
      $unzip = bof()->file->unzip_e( $download, $download_dir );
      $unzipErr = null;
    } catch( Exception $err ){
      $unzip = false;
      $unzipErr = $err->getMessage();;
    }
    if ( !$unzip )
    throw new bofException( "Zip -> Failed to unzip the release: {$unzipErr}" );
    $this->_bof_this->process_log( $process, "txt", "Zip -> Successfully unzipped the release" );

    // Check the content
    $zip_content = scandir( $download_dir );
    foreach( $zip_content as $zip_file ){
      $zip_file_path = "{$download_dir}/{$zip_file}";
      if ( is_file( $zip_file_path ) ){
        $zip_files[ pathinfo( $zip_file_path, PATHINFO_BASENAME ) ] = realpath( $zip_file_path );
        $this->_bof_this->process_log( $process, "txt", "Zip -> Found {$zip_file} in main zip file" );
      }
    }

    $destination = null;
    if ( $process["extension_type"] == "plugin" || $process["extension_type"] == "tool" )
    $destination = plugins_root . $process["extension_name"];
    elseif ( $process["extension_type"] == "theme" )
    $destination = themes_root . $process["extension_name"];
    elseif ( $process["extension_type"] == "script" )
    $destination = base_root;

    if ( $destination && $process["extension_type"] != "script" )
    $destination = bof()->file->mkdir( $destination );

    if ( $process["extension_type"] == "script" ){

      if ( empty( $zip_files ) ? true : empty( $zip_files["new_files.zip"] ) )
      throw new bofException( "Corrupted file: no new_files.zip found" );

      $new_files_path = bof()->file->mkdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/release/new_files" );
      try {
        $unzip = bof()->file->unzip_e( $zip_files["new_files.zip"], $new_files_path, [ "remove_after" => true ] );
        $unzipErr = null;
      } catch( Exception $err ){
        $unzip = false;
        $unzipErr = $err->getMessage();;
      }
      if ( !$unzip )
      throw new bofException( "Zip -> Failed to unzip the release - new_files.zip: {$unzipErr}" );
      $this->_bof_this->process_log( $process, "txt", "Zip -> Successfully unzipped the release - new_files.zip" );

      $n_zip_files = [];
      foreach( scandir( $new_files_path ) as $n_zip_file ){
        $n_zip_file_path = "{$new_files_path}/{$n_zip_file}";
        if ( is_file( $n_zip_file_path ) ? ( pathinfo( $n_zip_file_path, PATHINFO_EXTENSION ) == "zip" || pathinfo( $n_zip_file_path, PATHINFO_EXTENSION ) == "txt" ) : false ){
          $n_zip_files[ pathinfo( $n_zip_file_path, PATHINFO_BASENAME ) ] = realpath( $n_zip_file_path );
          $this->_bof_this->process_log( $process, "txt", "Zip -> Found {$n_zip_file} in new_files.zip" );
        }
      }

      ksort( $n_zip_files );

      $this->_bof_this->process_log( $process, "txt", "Update -> Processing: " . json_encode(array_keys($n_zip_files)) );

      foreach( $n_zip_files as $n_zip_name => $n_zip_file ){

        $_f_version = substr( pathinfo( $n_zip_file, PATHINFO_FILENAME ), 0, 4 );

        if ( !bof()->general->numeric( $_f_version ) ){
          $this->_bof_this->process_log( $process, "txt", "Zip -> Invalid new zipFile: {$n_zip_file}:{$_f_version}" );
          continue;
        }

        if ( $_f_version < version ){
          $this->_bof_this->process_log( $process, "txt", "Update -> Skipping {$_f_version}" );
          continue;
        }

        if ( pathinfo( $n_zip_file, PATHINFO_EXTENSION ) == "zip" ){

          try {
            $unzip = bof()->file->unzip_e( $n_zip_file, base_root, [ "remove_after" => true ] );
            $unzipErr = null;
          } catch( Exception $err ){
            $unzip = false;
            $unzipErr = $err->getMessage();;
          }

          if ( !$unzip ){
            $this->_bof_this->process_log( $process, "txt", "Zip -> Failed to unzip the release - new_files_{$_f_version}.zip: {$unzipErr}" );
            throw new bofException( "Zip -> Failed to unzip the release - new_files_{$_f_version}.zip: {$unzipErr}" );
          }
          $this->_bof_this->process_log( $process, "txt", "Zip -> Successfully unzipped the release - new_files_{$_f_version}.zip" );

        }
        else{

          $file_content = file_get_contents( $n_zip_file );
          $file_lines = json_decode( $file_content, true );

          if ( !empty( $file_lines ) ){
            foreach( $file_lines as $removed_file ){
              $this->_bof_this->process_log( $process, "txt", "File -> Removed no-longer-used-file {$removed_file}" );
              if ( is_file( base_root . "{$removed_file}" ) )
              unlink( base_root . "{$removed_file}" );
            }
          }

        }

      }

    }
    else {

      if ( !is_dir( $destination ) )
      throw new bofException( "Failed to create {$destination} directory" );

      if ( empty( $zip_files ) ? true : empty( $zip_files["files.zip"] ) )
      throw new bofException( "Corrupted file: no files.zip found" );

      try {
        $unzip = bof()->file->unzip( $zip_files["files.zip"], $destination, [ "remove_after" => true ] );
        $unzipErr = null;
      } catch( Exception $err ){
        $unzip = false;
        $unzipErr = $err->getMessage();;
      }

      if ( !$unzip )
      throw new bofException( "Zip -> Failed to unzip the release - files.zip: {$unzipErr}" );
      $this->_bof_this->process_log( $process, "txt", "Zip -> Successfully unzipped the release - files.zip" );

    }

    if ( $process["action"] == "install" && $process["extension_type"] != "script" ){

      if ( !empty( $zip_files["raw.sql"] ) ){
        $import = $this->_bof_this->import_sql( $zip_files["raw.sql"], true, $process );
        $this->_bof_this->process_log( $process, "txt", "SQL -> Successfully executed {$import} query lines" );
      }

      if ( $extension["type"] != "theme" ){
        $this->_bof_this->activate_plugin( $extension["code"] );
        $this->_bof_this->process_log( $process, "txt", "Successfully installed & activated <b>{$extension["name"]}</b> {$extension["type"]}" );
      } else {
        $this->_bof_this->process_log( $process, "txt", "Successfully installed <b>{$extension["name"]}</b> {$extension["type"]}" );
      }

    }
    else {

      if ( $process["extension_type"] == "script" )
      $current_version = version;
      else
      $current_version = $this->_bof_this->read( $process["extension_type"], $process["extension_name"] )["version"];

      // SQL Updates
      $updates = [];
      foreach( $zip_files as $zip_file ){
        if ( pathinfo( $zip_file, PATHINFO_EXTENSION ) == "sql" ){

          if ( in_array( pathinfo( $zip_file, PATHINFO_FILENAME ), [ "raw", "new" ], true ) )
          continue;

          $sql_version = substr( pathinfo( $zip_file, PATHINFO_FILENAME ), 0, 4 );

          if ( !bof()->general->numeric( $sql_version ) )
          continue;

          if ( $process["extension_type"] == "script" ? $sql_version <= $current_version : false ){
            $this->_bof_this->process_log( $process, "txt", "SQL -> Skipping {$current_version}" );
            continue;
          }

          if ( $process["extension_type"] != "script" ? $sql_version < $current_version : false ){
            $this->_bof_this->process_log( $process, "txt", "SQL -> Skipping {$current_version}" );
            continue;
          }

          $updates[ $sql_version ] = $zip_file;

        }
      }

      if ( $updates )
      ksort( $updates );

      if ( !empty( $zip_files["new.sql"] ) )
      $updates["latest"] = $zip_files["new.sql"];

      if ( !empty( $updates ) ){
        foreach( $updates as $update_name => $update ){
          $import = $this->_bof_this->import_sql( $update, false, $process );
          $this->_bof_this->process_log( $process, "txt", "SQL -> Successfully executed {$import} query lines: version {$update_name}" );
        }
      }

      // PHP Updates
      $updates_php = [];
      foreach( $zip_files as $zip_file ){
        if ( pathinfo( $zip_file, PATHINFO_EXTENSION ) == "php" ){

          if ( substr( pathinfo( $zip_file, PATHINFO_FILENAME ), 0, strlen("bof_update_") ) != "bof_update_" )
          continue;

          $php_version = substr( pathinfo( $zip_file, PATHINFO_FILENAME ), strlen("bof_update_"), 4 );

          if ( !bof()->general->numeric( $php_version ) )
          continue;

          if ( $php_version < $current_version )
          continue;

          $updates_php[ $php_version ] = $zip_file;

        }
      }

      if ( $updates_php )
      ksort( $updates_php );

      if ( !empty( $zip_files["bof_update.php"] ) )
      $updates_php["latest"] = $zip_files["bof_update.php"];

      if ( !empty( $updates_php ) ){
        foreach( $updates_php as $update_name => $update ){
          require_once( $update );
          $this->_bof_this->process_log( $process, "txt", "SQL -> Successfully executed {$update_name} php file" );
        }
      }

      // Removed files
      if (
        !empty( $zip_files["{$current_version}_to_{$extension["version"]}.txt"] ) &&
        ( $process["extension_type"] == "plugin" || $process["extension_type"] == "tool" )
      ){

        $file_content = file_get_contents( $zip_files["{$current_version}_to_{$extension["version"]}.txt"] );
        $file_lines = bof()->general->explode_by_line( $file_content );

        if ( !empty( $file_lines ) ){
          foreach( $file_lines as $removed_file ){
            if ( is_file( plugins_root . "/{$extension["code"]}/{$removed_file}" ) )
            unlink( plugins_root . "/{$extension["code"]}/{$removed_file}" );
          }
        }

      }

      $this->_bof_this->process_log( $process, "txt", "Successfully updated <b>{$extension["name"]}</b> {$extension["type"]} to latest version" );

    }

    bof()->file->rmdir( base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/release" );

    return true;

  }
  protected function _p_uninstall( $process ){

    $extension_data = $this->_bof_this->read( $process["extension_type"], $process["extension_name"] );

    $destination = null;
    if ( $process["extension_type"] == "plugin" || $process["extension_type"] == "tool" )
    $destination = plugins_root . $process["extension_name"];
    elseif ( $process["extension_type"] == "theme" )
    $destination = themes_root . $process["extension_name"];

    if ( is_file( "{$destination}/drop.sql" ) ){
      $drop = $this->_bof_this->import_sql( "{$destination}/drop.sql", false, $process );
      if ( $drop ){
        $this->_bof_this->process_log( $process, "txt", "Executed drop.sql: {$drop} queries" );
      }
    }

    if ( in_array( $process["extension_type"], [ "plugin", "tool" ], true ) ){

      $tables = [];

      bof()->plugin( $process["extension_name"], array(
        "handshake_file" => $destination . "/_handshake.php"
      ));

      if ( !empty( $extension_data["objects"] ) ){
        foreach( $extension_data["objects"] as $object ){

          $the_object = bof()->object->__get( $object );
          if ( $the_object->method_exists("bof") ){

            $object_bof = $the_object->bof();

            if ( !empty( $object_bof["db_table_name"] ) ){
              bof()->db->query("DROP TABLE {$object_bof["db_table_name"]}");
              $this->_bof_this->process_log( $process, "txt", "Dropped {$object}'s table" );
            }

            if ( !empty( $object_bof["db_rel_table_name"] ) ){
              bof()->db->query("DROP TABLE {$object_bof["db_rel_table_name"]}");
              $this->_bof_this->process_log( $process, "txt", "Dropped {$object}'s relation table" );
            }

          }

          bof()->db->query(" DELETE FROM `_d_pages_widgets` WHERE name = '{$object}' " );
          bof()->db->query(" DELETE FROM `_u_playlists` WHERE object_type = '{$object}' " );
          bof()->db->query(" DELETE FROM `_u_actions` WHERE object_name = '{$object}' " );
          bof()->db->query(" DELETE FROM `_u_properties` WHERE object_name = '{$object}' " );
          $this->_bof_this->process_log( $process, "txt", "Removed {$object}'s data from other tables" );

        }
      }

    }

    if ( !empty( $extension_data["objects"] ) ){
      foreach( $extension_data["objects"] as $object ){
        $object_files_removed = 0;
        while( $object_file = bof()->object->file->select( [ "used_in_object" => $object ], [ "limit" => 1, "cache_load_rt" => false, "single" => true, "clean" => false ] ) ){
          bof()->object->file->unlink( $object_file["ID"], false );
          $object_files_removed++;
        }
        if ( $object_files_removed )
        $this->_bof_this->process_log( $process, "txt", "Removed {$object}'s {$object_files_removed} files" );
      }
    }

    bof()->file->rmdir( $destination );

    $this->_bof_this->deactivate_plugin( $process["extension_name"] );

    $this->_bof_this->process_log( $process, "txt", "Successfully uninstalled <b>{$process["extension_name"]}</b> {$process["extension_type"]}" );
    return true;

  }
  protected function _p_activate( $process ){

    $this->_bof_this->process_log( $process, "txt", "Successfully activated <b>{$process["extension_name"]}</b> {$process["extension_type"]}" );
    $this->_bof_this->activate_plugin( $process["extension_name"] );
    return true;

  }
  protected function _p_deactivate( $process ){

    $this->_bof_this->process_log( $process, "txt", "Successfully de-activated <b>{$process["extension_name"]}</b> {$process["extension_type"]}" );
    $this->_bof_this->deactivate_plugin( $process["extension_name"] );
    return true;

  }

}

?>
