<?php

if ( !defined( "bof_root" ) ) die;

class response_json {

  private $forceMessage = true;
  public function disableMessage(){
    $this->forceMessage = false;
    return $this;
  }
  public function set( $args ){
    if ( $args )
    bof()->execute->set_data( "json", $args );
  }
  public function get(){

    return bof()->execute->get_data( "json" );

  }
  public function display(){

    if ( !headers_sent() )
    header('Content-Type: application/json');

    $json = bof()->execute->get_data( "json", array(
      "success" => false,
      "messages" => [ "Unkown error" ]
    ) );

    if ( $this->forceMessage ){
      
      $json["messages"] = !isset( $json["messages"] ) ? [] : $json["messages"];
      if ( !empty( $json["message"] ) ){
        $json["messages"][] = $json["message"];
        // unset( $json["message"] ); // Keep message for Flutter compatibility
      }
      
      if ( !empty( $json["messages"][0] ) && empty( $json["message"] ) ) {
        $json["message"] = $json["messages"][0];
      }

    }

    $flags = JSON_PRETTY_PRINT;
    if ( defined( "JSON_UNESCAPED_UNICODE" ) ) $flags = $flags | JSON_UNESCAPED_UNICODE;
    if ( defined( "JSON_INVALID_UTF8_SUBSTITUTE" ) ) $flags = $flags | JSON_INVALID_UTF8_SUBSTITUTE;

    $encoded = json_encode( $json, $flags );
    if ( $encoded === false ){
      $encoded = json_encode( array(
        "success" => false,
        "messages" => [ "Json encode failed" ]
      ), JSON_PRETTY_PRINT );
    }
    echo $encoded;

  }

}

?>
