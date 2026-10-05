<?php

if ( !defined( "bof_root" ) ) die;

class object_user_setting extends bof_type_object {

  public function bof(){
    return array(
      "name" => "user_setting",
      "label" => "User Setting",
      "db_table_name" => "_u_setting",
      "db_primary_column" => "var",
      "db_empty_select" => false
    );
  }
  public function columns(){
    return array(
      "user_id" => array(
        "validator" => "int"
      ),
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
      "user_id" => [ "user_id", "=" ],
      "var" => [ "var", "=" ],
      "var_like" => [ "var", "LIKE" ],
      "var_null" => [ "var", null, true ]
    );
  }

  public function set( $userID, $varName, $value, $type=null ){

    $sets = array(
      "user_id" => $userID,
      "var"  => $varName,
      "val"  => $value
    );

    if ( !empty( $type ) )
    $sets["type"] = $type;

    $create = bof()->object->_create(
      $this,
      array(
        "var" => $varName,
        "user_id" => $userID,
        "and()"
      ),
      $sets,
      $sets
    );

    return $create;

  }
  public function get( $userID, $varName, $defaultValue=null, $fallOnFail=false, $forceRT=false ){

    $item = $this->select(
      array(
        "user_id" => $userID,
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

      return $defaultValue;

    }

    return $item["val"];

  }
  public function get_notification( $userID ){

    $userNotificationsRaw = $this->_bof_this->get( $userID, "notifications", [] );
    $definedNotifications = bof()->object->notification->select([],["empty_select"=>true,"single"=>false,"limit"=>false]);
    $userNotifications = [];
    if ( $definedNotifications ){
      foreach( $definedNotifications as $definedNotification ){

        if ( empty( $definedNotification["setting_decoded"]["methods"]["all"] ) )
        continue;

        $userNotifications[ $definedNotification["hook"] ] = in_array( $definedNotification["hook"], array_keys( $userNotificationsRaw ), true ) ? !empty( $userNotificationsRaw[$definedNotification["hook"]] ) : true;

      }
    }

    return $userNotifications;

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

}

?>
