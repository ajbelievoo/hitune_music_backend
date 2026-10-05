<?php

if ( !defined( "bof_root" ) ) die;

class response {

  protected $type = null;
  protected $required = [];
  protected $headers = [];

  public function __construct(){
    $this->required = (object) array();
  }

  public function __get( $name ){
    $_class_name = "response_{$name}";
    return bof()->$_class_name;
  }

  public function set( $type, $args ){

    $this->type = $type;
    return $this->$type->set( $args );

  }
  public function getType( $is=false ){
    return $is ? $this->type == $is : $this->type;
  }
  public function display(){

    if ( empty( $this->type ) )
    fall("Response: Display: Undefined type");

    if ( $this->headers ){
      foreach( $this->headers as $header ){
        header( $header );
      }
    }

    $__t = $this->type;
    $this->$__t->display();

  }

  public function set_header( $name, $string ){
    $this->headers[ $name ] = $string;
  }

}

?>
