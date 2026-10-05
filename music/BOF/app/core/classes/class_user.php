<?php

if ( !defined( "bof_root" ) ) die;

class user {

  private $groups = [];
  private $data = null;

  public function add_group( $name, $args=[] ){
    $this->groups[ $name ] = $args;
    return true;
  }
  public function check(){

    bof()->session->open();
    $logged = bof()->session->check();

    if ( $logged ){

      if ( empty( bof()->session->getAll()["_ed"]["role"] ) )
      fall( "UserCheck: Failed: User belongs to no user-group!" );

      $role = bof()->session->getAll()["_ed"]["role"];
      $userData =  bof()->session->getAll()["_ed"]["user"];

      $extraData = $this->get_extraData(
        bof()->object->core_setting->get( "session_live_user_data" ) ? true : ( rand(0,25) == 0 ),
        $logged,
        !bof()->object->core_setting->get( "session_live_user_data" )
      );

      if ( !empty( $extraData["user"] ) ){
        $userData = $extraData["user"];
        unset( $extraData["user"] );
      }

      if ( rand( 0, 4 ) == 0 ){
        bof()->object->user->update(
          array(
            "ID" => $userData["ID"]
          ),
          array(
            "time_online" => bof()->general->mysql_timestamp()
          ),
          false
        );
      }

    }
    else {
      $extraData = bof()->user->get_guest();
    }

    $this->data = (object) array(
      "cli"      => defined('STDIN'),
      "logged"   => $logged ? true : false,
      "ID"       => $logged,
      "data"     => !empty( $userData ) ? $userData : null,
      "groups"   => !empty( $role ) ? [ $role ] : [ "guest" ],
      "extra"    => !empty( $extraData ) ? $extraData : null,
      "language" => bof()->getName() == "bof_client" ? bof()->object->language->get_users() : false,
      "currency" => bof()->getName() == "bof_client" ? bof()->object->currency->get_users(["just_code"=>true]) : false,
    );

    return $this->data;

  }
  public function get( $force_recheck=false ){

    if ( !$this->data || $force_recheck )
    $this->check();

    return $this->data;

  }
  public function get_guest(){

    $parse_guest_roles = bof()->object->user_role->parse_user_roles(
      array(
        bof()->object->user_role->select(["ID"=>1])
      )
    );

    $extraData = array();
    $extraData["roles"] = $parse_guest_roles;
    $extraData["role_ids"] = ["1"];

    return $extraData;

  }
  public function save_session(){

    bof()->session->open();
    $logged = bof()->session->check();

    if ( $logged ){

      $extraData = $this->get_extraData(
        true,
        $logged,
        true
      );

    }

  }
  public function get_extraData( $forceFresh=false, $userID=null, $saveAfter=false ){

    if ( $userID === null ) $userID = $this->data["ID"];
    if ( empty( $userID ) ) return false;

    $extraData = !empty( bof()->session->getAll()["_ed"] ) ? bof()->session->getAll()["_ed"] : null;

    if ( $forceFresh ){

      $userDataLive = bof()->object->user->select(
        array(
          "ID" => $userID
        ),
        array(
          "_eq" => [
            "roles" => true,
            "avatar" => []
          ],
        )
      );

      $extraData = array(
        "user" => array(
          "ID" => $userDataLive["ID"],
          "email" => $userDataLive["email"],
          "username" => $userDataLive["username"],
          "name" => $userDataLive["name"] ? $userDataLive["name"] : $userDataLive["username"],
          "hash" => $userDataLive["hash"],
          "avatar" => !empty( $userDataLive["bof_file_avatar"] ) ? $userDataLive["bof_file_avatar"]["image_strings"][6]["html"] : null,
          "avatar_original" => !empty( $userDataLive["bof_file_avatar"] ) ? $userDataLive["bof_file_avatar"]["image_original"] : null,
          "avatar_thumb" => !empty( $userDataLive["bof_file_avatar"] ) ? $userDataLive["bof_file_avatar"]["image_thumb"] : null,
        ),
      );

      if ( bof()->getName() == "bof_client" ){
        $extraData["role"] = "user";
        $extraData["roles"] = !empty( $userDataLive["roles_parsed"]["user"] ) ? $userDataLive["roles_parsed"]["user"] : [];
      }
      elseif ( bof()->getName() == "bof_admin" ) {
        $extraData["role"] = !empty( $userDataLive["roles_parsed"]["admin"] ) ? "admin" : "moderator";
        $extraData["moderator_roles"] = !empty( $userDataLive["roles_parsed"]["moderator"] ) ? $userDataLive["roles_parsed"]["moderator"] : [];
      }

      $extraData["role_ids"] = array_keys( $userDataLive["roles_parsed"]["_raw"] );

      if ( $saveAfter ){
        bof()->session->set( "_ed", $extraData );
      }

    }

    return $extraData;

  }

}

?>
