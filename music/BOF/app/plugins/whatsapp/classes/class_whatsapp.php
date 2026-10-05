<?php

if ( !defined( "bof_root" ) ) die;

class whatsapp extends bof_type_class {

  protected $token = null;
  protected $phone_id = null;

  public function set_token( $string ){
    $this->token = $string;
  }
  public function set_phone_id( $string ){
    $this->phone_id = $string; // WhatsApp Business Phone Number ID
  }

  public function notify( $message ){

    bof()->curl->exe(array(
      "url" => "https://api.callmebot.com/whatsapp.php?phone=+{$this->phone_id}&text=".urlencode($message)."&apikey={$this->token}",
      "agent" => "chrome"
    ));

  }

}
?>
