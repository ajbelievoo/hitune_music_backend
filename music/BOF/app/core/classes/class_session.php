<?php

if ( !defined( "bof_root" ) ) die;

class session extends bof_type_class {

  protected $is_open = false;
  protected $ID = null;
  protected $data = [];

  public function set( $var, $val, $saveAfter=true ){

    if ( !$this->ID )
    return false;
    if ( !$this->is_open ){
      if ( $this->open( $this->ID, false ) === false )
      return false;
    }

    $this->data[ $var ] = $val;

    if ( $saveAfter ){
      $this->write();
    }

    return true;

  }
  public function get( $var ){
    return $this->data ? ( in_array( $var, array_keys( $this->data ) ) ? $this->data[ $var ] : null ) : null;
  }
  public function getID(){
    return $this->ID;
  }
  public function getAll(){
    return $this->data;
  }

  public function create( $userID, $args=[], $agent_ID=null ){

    $agent_ID = $agent_ID ? $agent_ID : bof()->request->get_userAgent()["string"];
    extract( $args );

    // Set session cookie domain for cross-subdomain sharing
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => '.hitune.in',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    if ( session_status() == PHP_SESSION_ACTIVE )
    session_reset();

    else
    session_start();

    $this->ID = session_id();
    $this->data = [];
    $this->is_open = true;
    $key = uniqid();

    bof()->session->set( "user_id", $userID, false );
    bof()->session->set( "user_ip", bof()->request->get_userIP()["string"], false );
    bof()->session->set( "user_agent", $agent_ID, false );
    bof()->session->set( "created", time(), false );
    if ( !empty( $extra_data ) )
    bof()->session->set( "_ed", $extra_data, false );
    bof()->session->set( "key", $key );

    bof()->db->_insert(array(
      "table" => bof()->object->core_setting->get( "session_table_name", null, [ "invalid_death" => true ] ),
      "set" => array(
        [ "session_id", session_id() ],
        [ "user_id", $userID ],
        [ "ip", bof()->request->get_userIP()["string"] ],
        [ "ip_country", bof()->request->get_userIP()["country"] ],
        [ "platform_type", $platform_type ],
        [ "device_type", $device_type ],
      )
    ));

    return [
      "key" => $key,
      "id" => $this->ID
    ];

  }
  public function open( $sess_id=null, $closeAfter=true ){

    if ( $sess_id )
    session_id( $sess_id );

    // Set session cookie domain for cross-subdomain sharing
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => '.hitune.in',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    try {
      session_start();
    } catch( Exception $err ){
      return false;
    }

    if ( session_status() != 2 && session_status() != PHP_SESSION_ACTIVE )
    return false;

    if ( empty( $SID = session_id() ) )
    return false;

    $this->is_open = true;
    $this->ID = $SID;
    $this->data = $_SESSION;

    if ( $closeAfter === true ){
      $this->close();
    }

    return true;

  }
  public function write(){

    $_SESSION = $this->data;
    session_write_close();

  }
  public function close(){

    if ( !$this->is_open )
    return;

    $this->is_open = false;

    if ( session_status() != 2 && session_status() != PHP_SESSION_ACTIVE )
    return;

    session_abort();

  }
  public function destroy( $id ){

    if ( $id == $this->ID ){
      $this->ID = null;
      $this->data = [];
      $this->is_open = false;
    }

    // change to target session and remove it
    session_id( $id );
    session_start();
    session_destroy();
    session_write_close();

    // try to remove session files
    if ( file_exists( session_save_path() . "/sess_" . $id ) )
    unlink( realpath( session_save_path() . "/sess_" . $id ) );

    return true;

  }
  public function check_active( $userID, $maximum_allowed ){
    return true;
  }

  public function check( $agent_ID=null, $sess_key=null ){

    $agent_ID = $agent_ID === null ? bof()->request->get_userAgent()["string"] : $agent_ID;
    $sess_key = $sess_key === null ? bof()->nest->user_input( "http_header", "x-bof-sess-key", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_ ]" ] ) : $sess_key;
    if ( !$sess_key ) $sess_key = bof()->nest->user_input( "http_header", "bof-sess-key", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_ ]" ] );
    if ( !$sess_key ) $sess_key = bof()->nest->user_input( "post", "sess_key", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_ ]" ] );

    $userID = bof()->nest->user_input( "session", "user_id", "int" );
    $userIP = bof()->nest->user_input( "session", "user_ip", "ip" );
    $created = bof()->nest->user_input( "session", "created", "int" );
    $userAgent = bof()->nest->user_input( "session", "user_agent", "string" );


    if ( !$userID || !$userIP || !$userAgent || !$created )
    return false;

    $sett_lock_ip = bof()->object->core_setting->get( "session_lock_ip" );
    $sett_lock_agent = bof()->object->core_setting->get( "session_lock_agent" );
    $sett_expiration_period = bof()->object->core_setting->get( "session_expire" );

    if (
      // expired
      ( $sett_expiration_period ? time() > $created + $sett_expiration_period : false ) ||
      // agent locked & changed
      ( $sett_lock_agent ? $userAgent != $agent_ID : false ) ||
      // ip locked && changed
      ( $sett_lock_ip ? $userIP != bof()->request->get_userIP()["string"] : false ) ||
      // compare keys
      ( $sess_key != $this->get( "key" ) )
    ) {
      $this->destroy( $this->ID );
      return false;
    }

    $sett_max = bof()->object->core_setting->get( "session_max" );
    $sett_check_chance = bof()->object->core_setting->get( "session_cc" );

    if ( $sett_check_chance && $sett_max ? $sett_check_chance >= rand( 1, 100 ) : false ){
      $check_active = $this->check_active( $userID, $sett_max );
      if ( $check_active !== true )
      return false;
    }

    return $userID;

  }


}

?>
