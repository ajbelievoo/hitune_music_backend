<?php

if ( !defined( "bof_root" ) ) die;

class session_extend extends session {

  public function create( $userID, $args=[], $agent_ID=null ){

    $extra_data = null;
    $agent_ID = $agent_ID ? $agent_ID : bof()->request->get_userAgent()["string"];
    extract( $args );

    $this->ID = md5( uniqid() . microtime(true) );
    $this->data = [];
    $this->is_open = true;
    $key = uniqid();

    bof()->session->set( "user_id", $userID, false );
    bof()->session->set( "user_ip", bof()->request->get_userIP()["string"], false );
    bof()->session->set( "user_agent", $agent_ID, false );
    bof()->session->set( "created", time(), false );
    bof()->session->set( "key", $key, false );
    bof()->session->set( "_ed", $extra_data, false );

    $sess_db_id = bof()->object->session->insert(array(
      "session_id" => $this->ID,
      "user_id" => $userID,
      "ip" => bof()->request->get_userIP()["string"],
      "ip_country" => bof()->request->get_userIP()["country"],
      "platform_type" => $platform_type,
      "device_type" => $device_type,
    ));

    $this->write();

    return [
      "key" => $key,
      "id" => $this->ID
    ];

  }
  public function open( $sess_id=null, $closeAfter=true ){

    $sess_id = bof()->nest->user_input( "http_header", "x-bof-sess-id", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_ ]" ] );
    if ( !$sess_id ) $sess_id = bof()->nest->user_input( "http_header", "bof-sess-id", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_ ]" ] );
    if ( !$sess_id ) $sess_id = bof()->nest->user_input( "post", "sess_id", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_ ]" ] );

    if ( $sess_id ){
      $check = bof()->object->session->select(["session_id"=>$sess_id,"active"=>1]);
      if ( $check ){
        $this->is_open = true;
        $this->ID = $check["session_id"];
        $this->data = json_decode( $check["data"], 1 );
        bof()->object->session->update(["ID"=>$check["ID"]],["time_online"=>bof()->general->mysql_timestamp()]);
        return true;
      }
    }

    return false;

  }
  public function write(){

    $sess_db_id = bof()->object->session->update(
      array(
        "session_id" => $this->ID
      ),
      array(
        "data" => json_encode($this->data),
      )
    );

  }
  public function close(){
    return;
  }
  public function destroy( $id ){

    bof()->object->session->delete(array(
      "session_id" => $id
    ));

    if ( $id == $this->ID ){
      $this->ID = null;
      $this->data = null;
      $this->is_open = false;
    }

    return true;

  }
  public function check_active( $userID, $maximum_allowed ){

    $sessions = bof()->object->session->select(
      array(
        "user_id" => $userID,
        "active" => 1
      ),
      array(
        "order_by" => "time_add",
        "order" => "DESC",
        "limit" => false,
        "single" => false
      )
    );

    if ( count( $sessions ) <= $maximum_allowed )
    return true;

    foreach( $sessions as $i => $session ){

      if ( $i >= $maximum_allowed )
      $this->destroy( $session["session_id"] );

      if ( $session["session_id"] == $this->getID() )
      $cur_sess_i = $i;

    }

    if ( $cur_sess_i >= $maximum_allowed )
    return false;

    return true;

  }

}

?>
