<?php

if ( !defined( "bof_root" ) ) die;

class telegram extends bof_type_class {

  protected $name = null;
  protected $key = null;
  protected $target = null;

  public function set_name( $string ){
    $this->name = $string;
  }
  public function set_key( $string ){
    $this->key = $string;
  }
  public function set_target( $id ){
    $this->target = $id;
  }

  public function notify( $message, $parse_mode='html' ){

    return bof()->curl->exe(array(
      "url" => "https://api.telegram.org/bot{$this->key}/sendMessage",
      "posts" => array(
        'chat_id' => $this->target,
        'text' => $message,
        'parse_mode' => $parse_mode
      ),
    ));

  }

}

?>
