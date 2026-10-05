<?php

if ( !defined( "bof_root" ) ) die;

class object_notification {

  public $table_name = "_bof_p_notifications";
  public $where_shortcuts = [ "ID", "user_id", "user_email", "user_phone", "user_telegram" ];
  public $create_where = [];
  public $create_args = array(
    "user_id" => array(
      "validator" => "int",
      "validator_args" => array(
        "empty()"
      )
    ),
    "user_email" => array(
      "validator" => "email",
      "validator_args" => array(
        "empty()"
      )
    ),
    "user_phone" => array(
      "validator" => "string",
      "validator_args" => array(
        "empty()"
      )
    ),
    "user_push_ids" => array(
      "validator" => "json",
      "validator_args" => array(
        "empty()",
        "encode" => true
      )
    ),
    "user_telegram" => array(
      "validator" => "username",
      "validator_args" => array(
        "empty()"
      )
    ),
    "object_type" => array(
      "validator" => "string",
      "validator_args" => array(
        "strict" => true,
        "strict_regex" => "[a-z0-9A-Z\-_ ]"
      )
    ),
    "object_event" => array(
      "validator" => "string",
      "validator_args" => array(
        "strict" => true,
        "strict_regex" => "[a-z0-9A-Z\-_ ]"
      )
    ),
    "object_args" => array(
      "validator" => "json",
      "validator_args" => array(
        "encode" => true,
        "empty()"
      )
    ),
    "send_telegram_sta" => array(
      "validator" => "in_array",
      "validator_args" => array(
        "values" => [ 0, 1 ],
        "strict" => false,
        "empty()"
      )
    ),
    "send_sms_sta" => array(
      "validator" => "in_array",
      "validator_args" => array(
        "values" => [ 0, 1 ],
        "strict" => false,
        "empty()"
      )
    ),
    "send_push_sta" => array(
      "validator" => "in_array",
      "validator_args" => array(
        "values" => [ 0, 1 ],
        "strict" => false,
        "empty()"
      )
    ),
    "send_email_sta" => array(
      "validator" => "in_array",
      "validator_args" => array(
        "values" => [ 0, 1 ],
        "strict" => false,
        "empty()"
      )
    ),
    "send_email_data" => array(
      "validator" => "json",
      "validator_args" => array(
        "encode" => true,
        "empty()"
      )
    ),
    "send_telegram_data" => array(
      "validator" => "json",
      "validator_args" => array(
        "encode" => true,
        "empty()"
      )
    ),
    "send_push_data" => array(
      "validator" => "json",
      "validator_args" => array(
        "encode" => true,
        "empty()"
      )
    ),
    "send_sms_data" => array(
      "validator" => "json",
      "validator_args" => array(
        "encode" => true,
        "empty()"
      )
    ),
  );
  public function __construct( $loader ){
    $this->loader = $loader;
  }

  public function create( $args ){
    $args["args"] = !empty( $args["args"] ) ? ( is_array( $args["args"] ) ? json_encode( $args["args"] ) : $args["args"] ) : null;
    $args["results"] = !empty( $args["results"] ) ? ( is_array( $args["results"] ) ? json_encode( $args["results"] ) : $args["results"] ) : null;
    return $this->loader->object->_create( $args, $this );
  }
  public function select( $args=[] ){
    return $this->loader->object->_select( $args, $this );
  }
  public function update( $ID, $args ){
    return $this->loader->object->_update( $ID, $args, $this );
  }
  public function clean( $item, $args ){
    $item["object_args"] = !empty( $item["object_args"] ) ? json_decode( $item["object_args"], 1 ) : $item["object_args"];
    $item["content"] = $this->convert( $item["object_type"], $item["object_event"], $item["object_args"] );
    return $item;
  }
  public function count_unseen( $user_id=false ){

    $user_id = $user_id ? $user_id : $this->loader->user->get()->ID;
    return $this->loader->db->query("SELECT 1 FROM {$this->table_name} WHERE user_id = '{$user_id}' AND time_seen IS NULL ")->num_rows;

  }
  public function convert( $object_type, $object_event, $object_args ){

    return $this->loader->object->$object_type->notification( $object_event, $object_args );

  }
  public function mark( $user_id=false ){

    $user_id = $user_id ? $user_id : $this->loader->user->get()->ID;
    $this->loader->db->query("UPDATE {$this->table_name} SET time_seen = now() WHERE user_id = '{$user_id}' AND time_seen IS NULL ");

  }

}

?>
