<?php

class html_dom_form {

  public function validate( $args ){

    if ( !$args ? true : !is_array( $args ) )
    fall("Response: Html: Dom: Form: Invalid Args");

    $inputs = null;
    $buttons = null;
    $content = "";
    $content_prepend = "";
    $content_append = "";
    $class = [];
    $wrapper = [];
    $becli = null;
    extract( $args );

    if ( empty( $inputs ) ? true : !is_array( $inputs ) )
    fall("Response: Html: Dom: Form: Invalid Inputs args");

    if ( $becli ){
      $args["wrapper"]["class"] = empty( $wrapper["class"] ) ? [ "becli_form" ] : array_merge( $wrapper["class"], [ "becli_form" ] );
      if ( is_array( $becli ) ){
        foreach( $becli as $_k => $_v )
        $args["wrapper"]["data"][$_k] = $_v;
      }
    }

    foreach( $inputs as $input_group ){
      $input_group["class"] = !empty( $input_group["class"] ) ? array_merge( $input_group["class"], [ "o_form_input_group" ] ) : [ "o_form_input_group" ];
      $input_group["input"]["class"] = !empty( $input_group["input"]["class"] ) ? array_merge( $input_group["input"]["class"], [ "o_form_input" ] ) : [ "o_form_input" ];
      $content .= bof()->object->html->dom_display( "input_group", $input_group, false );
    }

    if ( $buttons ? is_array( $buttons ) : false ){
      $buttons_string = "";
      foreach( $buttons as $button ){
        $button["class"] = !empty( $button["class"] ) ? array_merge( $button["class"], [ "o_form_button" ] ) : [ "o_form_button" ];
        $buttons_string .= bof()->object->html->dom_display( "button", $button, false );
      }
      $content .= bof()->object->html->dom_display( "wrapper", [ "content" => $buttons_string, "class" => [ "o_button_group" ] ], false );
    }

    $final_content = $content_prepend . $content . $content_append;
    if ( $wrapper )
    $final_content = bof()->object->html->dom_display( "wrapper", array_merge( $wrapper, [ "content" => $final_content ] ), false );

    $class[] = "o_form";

    $args[ "class" ] = $class;
    $args[ "content" ] = $final_content;

    return $args;

  }

  public function display( $args, $display=false ){

    $output = bof()->object->html->dom_display( "wrapper", $this->validate( $args ), false );

    if ( $display ) echo $output;
    return $output;

  }

}

?>
