<?php

class html_dom_input {

  protected $args = [];
  protected $supported_types = [ "text", "textarea", "password", "email", "digit", "checkbox", "radio", "select", "submit" ];

  public function validate( $args ){

    if ( !$args ? true : !is_array( $args ) )
    fall("Response: Html: Dom: Input: Invalid Args");

    $name = null;
    $tag = "input";
    $type = null;
    $class = [];
    extract( $args );

    if ( !is_array( $class ) )
    fall("Response: Html: Dom: Input: Invalid Args: class is not array");

    if ( empty( $type ) )
    fall("Response: Html: Dom: Input: Invalid Args: type is empty");
    if ( !in_array( $type, $this->supported_types, true ) )
    fall("Response: Html: Dom: Input: Invalid Args: type is not supported");

    $class[] = "o_input";
    $class[] = "o_input_type_{$type}";
    $class[] = "o_input_name_{$name}";
    $class = array_unique( $class );
    $args["class"] = $class;
    $args["tag"] = $tag;

    return $args;

  }

  public function display( $args, $display=false ){

    $output = bof()->object->html->dom_display( "wrapper", $this->validate( $args ), false );

    if ( $display ) echo $output;
    return $output;

  }

}

?>
