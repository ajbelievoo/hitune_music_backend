<?php

class html_dom_label {

  protected $args = [];

  public function validate( $args ){

    if ( !$args ? true : !is_array( $args ) )
    fall("Response: Html: Dom: Input: Invalid Args");
    $tag = "label";
    extract( $args );
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
