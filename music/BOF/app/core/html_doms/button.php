<?php

class html_dom_button {

  protected $args = [];

  public function validate( $args ){

    $type = null;
    $text = null;
    $class = [];
    extract( $args );
    $content = null;

    if ( !$type ? true : !in_array( $type, [ "button", "a", "submit" ], true ) )
    fall("Dom: Button: Invalid type: {$type}");

    $class[] = "o_button";
    $class = array_unique( $class );
    $args["class"] = $class;

    if ( $type == "submit" ){
      $args["value"] = $text;
      $content = bof()->object->html->dom_display( "input", array_merge( $args, [ "type" => "submit" ] ), false );
    }

    if ( $type == "a" ){
      $args["content"] = $text;
      $content = bof()->object->html->dom_display( "a", $args, false );
    }

    if ( $type == "button" ){
      $args["content"] = $text;
      $content = bof()->object->html->dom_display( "wrapper", array_merge( $args, [ "tag" => "button" ] ), false );
    }

    $args["content"] = $content;

    return $args;

  }

  public function display( $args, $display=false ){

    $args = $this->validate( $args );
    $output = $args["content"];

    if ( $display ) echo $output;
    return $output;

  }

}

?>
