<?php

if ( !defined( "bof_root" ) ) die;

class object_db_setting extends bof_type_object {

  protected $loaded = [];

  public function bof(){
    return array(
      "name" => "db_setting",
      "label" => "DB Setting",
      "db_table_name" => "_bof_setting",
      "db_primary_column" => "var",
      "db_empty_select" => false
    );
  }
  public function columns(){
    return array(
      "var" => array(
        "validator" => "string_abcd"
      ),
      "val" => array(
        "validator" => array(
          "raw",
          array(
            "empty()"
          ),
        )
      ),
      "type" => array(
        "validator" => array(
          "string_abcd",
          array(
            "empty()"
          ),
        ),
      )
    );
  }
  public function selectors(){
    return array(
      "var" => [ "var", "=" ],
      "var_like" => [ "var", "LIKE" ],
      "var_null" => [ "var", null, true ]
    );
  }

  public function set_pre_loaded( $varName, $value ){
    $this->loaded[ $varName ] = $value;
  }

  public function set( $varName, $value, $type=null ){

    $sets = array(
      "var"  => $varName,
      "val"  => $value
    );

    if ( !empty( $type ) )
    $sets["type"] = $type;

    $create = bof()->object->_create(
      $this,
      array(
        "var" => $varName
      ),
      $sets,
      $sets
    );

    if ( in_array( $varName, [ "sitename", "shortname", "theme_color", "icon" ], true ) ){
      $this->manifest();
    }

    if ( in_array( 
      $varName, 
      [ "session_ip_lock", "session_pf_lock", "session_max", "session_life", "session_cc", "admin_nu_lock", "admin_ti_lock", "admin_ua_lock", "admin_ip_lock",
      "client_private", "client_constructing", "client_auto_images", "client_give_attribute", "fulltext_search", "youtube_piped" ], 
      true 
    ) ){
      $this->config_user( $varName, $value );
    }

    return $create;

  }
  public function get( $varName, $defaultValue=null, $fallOnFail=false, $forceRT=false, $fullReturn=false ){

    if ( ( !$forceRT && !$fullReturn && $this->loaded ) ? in_array( $varName, array_keys( $this->loaded ), true ) : false )
    return $this->loaded[ $varName ];

    $item = $this->select(
      array(
        "var" => $varName
      ),
      array(
        "limit" => 1,
        "single" => 1,
        "cache_load_rt" => $forceRT ? false : true
      )
    );

    if ( !$item ){

      if ( $fallOnFail )
      fall( "No record for {$varName} was found in database" );

      $this->loaded[ $varName ] = $defaultValue;
      return $defaultValue;

    }

    if ( $fullReturn )
    return $item;

    $this->loaded[ $varName ] = $item["val"];
    return $item["val"];

  }
  public function del( $varName ){

    return bof()->object->_delete(
      $this,
      array(
        "var" => $varName
      ),
      false
    );

  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    return bof()->object->_select(
      $this,
      $whereArgs,
      $selectArgs
    );

  }
  public function clean( $item ){

    if ( $item["type"] == "json" )
    $item["val"] = json_decode( $item["val"], 1 );

    if ( $item["type"] == "imploded" )
    $item["val"] = explode( ",", $item["val"] );

    if ( $item["type"] == "lined" )
    $item["val"] = explode( PHP_EOL, $item["val"] );

    return $item;

  }

  public function manifest(){

    $rawData = array(
      "name" => "",
      "short_name" => "",
      "theme_color" => "",
      "background_color" => "#131513",
      "display" => "fullscreen",
      "scope" => "",
      "start_url" => "",
      "icons" => []
    );

    $rawData["name"] = $sitename = $this->_bof_this->get( "sitename", null, false, true );
    $rawData["short_name"] = $shortname = $this->_bof_this->get( "shortname", null, false, true );
    $rawData["theme_color"] = $theme_color = "#" . $this->_bof_this->get( "theme_color", null, false, true );
    $rawData["scope"] = $rawData["start_url"] = web_address;

    $icon = $this->_bof_this->get( "icon", null, false, true );

    if ( $icon ){
      $icon_file = bof()->object->file->select(["ID"=>$icon],["cache"=>false,"cache_load_rt"=>false,"cache_load"=>false]);
      if ( $icon_file ){

        $icon_file_path = base_root . "/" . $icon_file["path"];

        foreach( [ 72, 96, 120, 128, 144, 152, 180, 192, 384, 512 ] as $pwa_icon_size ){
          if ( $icon_file["data_decoded"]["width"] >= $pwa_icon_size ){

            bof()->image->set( $icon_file_path )->square()->resize( array(
              "abs_width" => $pwa_icon_size,
              "abs_height" => $pwa_icon_size
            ) )->save( array(
              "path" => base_root . "/api/assets/images/icon_{$pwa_icon_size}.png",
              "save_ext" => "png"
            ) )->unset();

            if ( is_file( base_root . "/api/assets/images/icon_{$pwa_icon_size}.png" ) ){
              $rawData["icons"][] = [
                "src" => "api/assets/images/icon_{$pwa_icon_size}.png",
                "sizes" => "{$pwa_icon_size}x{$pwa_icon_size}",
                "type" => "image/png",
                "purpose" => "any maskable"
              ];
            }

          } else {
            if ( is_file( base_root . "/api/assets/images/icon_{$pwa_icon_size}.png" ) )
            unlink( base_root . "/api/assets/images/icon_{$pwa_icon_size}.png" );
          }
        }

      }
    }

    $new_content = json_encode( $rawData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES );
    file_put_contents( base_root . "/manifest.json", $new_content );

  }
  public function config_user( $var, $val ){

    $oFc = file_get_contents( root . "/app/config_user.php" );
    $val_f = is_string( $val ) ? "\"{$val}\"" : ( is_null( $val ) ? "false" : ( $val === true ? "true" : ( $val === false ? "false" : $val ) ) );

    if ( defined( $var ) ) {

      $oFc_break = explode( "define( \"{$var}\"", $oFc );
      $oFc_before = ( $oFc_break[0] );
      $oFc_after  = ( implode( ");", array_slice( explode( ");", $oFc_break[1] ), 1 ) ) );

      $nFc = $oFc_before;
      $nFc .= "define( \"{$var}\", {$val_f} );";
      $nFc .= $oFc_after;

    }
    else {

      $oFc_break = explode( "?>", $oFc );
      $oFc_before = $oFc_break[0];

      $nFc = $oFc_before;
      $nFc .= "define( \"{$var}\", {$val_f} );";
      $nFc .= PHP_EOL . "?>";

    }

    file_put_contents( root . "/app/config_user.php", $nFc );

  }

}

?>
