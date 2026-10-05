<?php

class html_dom_wrapper {

  protected $args = [];

  public function validate( $args ){

    if ( !$args ? true : !is_array( $args ) )
    fall("Response: Html: Dom: Input: Invalid Args");

    $attributes = [];
    $content = null;
    $tag = "div";
    $close_tag = true;

    $type = null;
    $id = null;
    $placeholder = null;
    $name = null;
    $class = [];
    $styles = [];
    $value = null;
    $data = [];
    extract( $args );

    $args["tag"] = $tag;

    if ( $type )
    $args["attributes"]["type"] = array(
      "key" => "type",
      "content" => $type
    );

    if ( $id )
    $args["attributes"]["id"] = array(
      "key" => "id",
      "content" => $id
    );

    if ( $placeholder )
    $args["attributes"]["placeholder"] = array(
      "key" => "placeholder",
      "content" => $placeholder
    );

    if ( $name )
    $args["attributes"]["name"] = array(
      "key" => "name",
      "content" => $name
    );

    if ( $class )
    $args["attributes"]["class"] = array(
      "key" => "class",
      "content" => is_array( $class ) ? implode( " ", $class ) : $class
    );

    if ( $styles ){
      $_styles = [];
      foreach( $styles as $style_key => $style_val ){
        $_styles[] = "{$style_key}: {$style_val}";
      }
      $args["attributes"]["style"] = array(
        "key" => "style",
        "content" => implode( "; ", $_styles )
      );
    }

    if ( $value )
    $args["attributes"]["value"] = array(
      "key" => "value",
      "content" => $value
    );

    if ( $data ? is_array( $data ) : false ){
      foreach( $data as $_data_name => $_data_value )
      $args["attributes"]["data-{$_data_name}"] = array(
        "key" => "data-{$_data_name}",
        "content" => $_data_value
      );
    }

    return $args;

  }

  public function display( $args, $display=false ){

    $attributes = null;
    $content = null;
    $tag = null;
    $close_tag = true;
    $wrapper = false;
    extract( $this->validate( $args ) );

    $output = "<{$tag}";
    if ( $attributes ){
      foreach( $attributes as $attribute ){
        $output .= " {$attribute["key"]}=\"{$attribute["content"]}\" ";
      }
    }
    $output .= ">";

    if ( $content )
    $output .= is_array( $content ) ? implode( "", $content ) : $content;

    if ( $close_tag )
    $output .= "</{$tag}>";

    if ( $wrapper )
    $output = bof()->object->html->dom_display( "wrapper", array_merge( $wrapper, [ "content" => $output ] ), false  );

    if ( $display ) echo $output;
    return $output;

  }

}

?>
