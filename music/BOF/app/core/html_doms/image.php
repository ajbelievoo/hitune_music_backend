<?php

class html_dom_image {

  protected $args = [];

  public function validate( $args ){

    $args = array_merge( array(
      "tag"        => "img",
      "close_tag"  => false,
      "attributes" => array(
        [ "key" => "src",   "content" => $args["src"] ],
        [ "key" => "title", "content" => $args["src"] ]
      )
    ), $args );

    return $args;

  }

  public function display( $args, $display=false ){

    $args = $this->validate( $args );
    $output = bof()->object->html->dom_display( "wrapper", $args, false );

    if ( $display ) echo $output;
    return $output;

  }

}

?>
