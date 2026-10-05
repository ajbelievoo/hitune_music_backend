<?php

if ( !defined( "bof_root" ) ) die;

class object_session extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "session",
      "label" => "Session",
      "icon" => "fingerprint",
      "db_table_name" => bof()->object->core_setting->get( "session_table_name", null, [ "invalid_death" => true ] ),
    );
  }
  public function columns(){
    return array(
      "session_id" => array(
        "validator" => "string"
      ),
      "push_id" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
      ),
      "user_id" => array(
        "validator" => "int"
      ),
      "ip" => array(
        "validator" => array(
          "ip",
          array(
            "empty()"
          )
        )
      ),
      "ip_country" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
      ),
      "platform_type" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "device_type" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "active" => array(
        "validator" => array(
          "boolean",
          array(
            "empty()"
          )
        )
      ),
      "time_online" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          )
        )
      ),
      "time_renew" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          )
        )
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
           "empty()",
           "encode" => true
         )
       )
      ),
    );
  }
  public function selectors(){
    return array(
      "session_id" => [ "session_id", "=" ],
      "active"     => [ "active", "=" ],
      "user_id"    => [ "user_id", "=" ],
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add",
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){
    return bof()->object->_select( $this, $whereArgs, $selectArgs );
  }

}

?>
