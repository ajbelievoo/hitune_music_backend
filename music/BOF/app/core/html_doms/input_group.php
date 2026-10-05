<?php

class html_dom_input_group {

  protected $args = [];

  public function validate( $args ){

    if ( !$args ? true : !is_array( $args ) )
    fall("Response: Html: Dom: InputGroup: Invalid Args");

    $label = null;
    $tag = "div";
    extract( $args );

    if ( empty( $input ) )
    fall("Response: Html: Dom: InputGroup: Invalid Args: input args is empty");

    $args["content"] = bof()->object->html->dom_display( "wrapper", array(
      "content" => bof()->object->html->dom_display( "input", $input, false ),
      "class" => "o_input_wrapper",
    ), false );

    if ( $label ){
      $args["content"] = bof()->object->html->dom_display( "label", $label, false ) . $args["content"];
    }

    $class[] = "o_input_group";
    $class = array_unique( $class );
    $args["class"] = $class;
    $args["tag"] = $tag;

    return $args;

  }

  public function display( $args, $display=false ){

    $args = $this->validate( $args );
    extract( $args );

    $output = bof()->object->html->dom_display( "wrapper", $args, false );

    if ( $display ) echo $output;
    return $output;

  }

}

?>
