<?php

class html_dom_a {

  protected $args = [];

  public function validate( $args ){

    $href = null;
    extract( $args );

    if ( $href )
    $args["attributes"]["href"] = [ "key" => "href", "content" => $href ];

    $args = array_merge( $args, [ "tag" => "a" ] );
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
