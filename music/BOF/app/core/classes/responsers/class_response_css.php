<?php

if ( !defined( "bof_root" ) ) die;

class response_css {

  protected $data = [];

  public function set( $args ){

    if ( !is_array( $args ) )
    return false;

    foreach( $args as $selector => $css_data ){
      bof()->response->css->add( $selector, $css_data );
    }

  }
  public function add( $selector, $css_data ){

    if ( !is_array( $css_data ) ) return false;
    if ( empty( $selector ) ) return false;
    $selector = !is_array( $selector ) ? [ $selector ] : $selector;

    if ( isset( $css_data["_Childs"] ) ){
      $_childs = $css_data["_Childs"];
      unset( $css_data["_Childs"] );
    }

    $selector_string = implode( " ", $selector );
    $this->data[ $selector_string ] = $this->render_css( $selector_string, $css_data );

    if ( !empty( $_childs ) ){
      foreach( $_childs as $_child_selector => $_child_css ){
        bof()->response->css->add( array_merge( $selector, [ $_child_selector ] ), $_child_css );
      }
    }

  }
  protected function render_css( $selector, $css_data ){

    if ( !is_array( $css_data ) )
    return "";

    if ( empty( $css_data ) )
    return "";

    $aliases_strings = "";
    if ( !empty( $css_data["_Aliases"] ) ){
      foreach( $css_data["_Aliases"] as $_Alias ){
        $aliases_strings .= implode( " ", $_Alias ) . ",\n";
      }
      unset( $css_data["_Aliases"] );
    }

    if ( !empty( $css_data["_Premades"] ) ){
      if ( in_array( "position_center", array_keys( $css_data["_Premades"] ), true ) ){
        $css_data[ "_Css" ][ "left" ] = "0px";
        $css_data[ "_Css" ][ "right" ] = "0px";
        $css_data[ "_Css" ][ "bottom" ] = "0px";
        $css_data[ "_Css" ][ "top" ] = "0px";
        $css_data[ "_Css" ][ "position" ] = "absolute";
        $css_data[ "_Css" ][ "margin" ] = "auto";
      }
      unset( $css_data["_Premades"] );
    }

    foreach( $css_data["_Css"] as $key => $val )
    $css_lines[] = $this->render_css_line( $key, $val );

    if ( empty( $css_lines ) )
    return "";

    $selector_string = $selector;
    $output = "{$aliases_strings}{$selector_string} {\n  ".implode(";\n  ",$css_lines)."\n}";
    return $output;

  }
  protected function render_css_line( $key, $val ){

    if ( empty( $key ) )
    return;

    return "{$key}: {$val}";

  }
  public function display(){

    header("Content-Type: text/css; charset=utf-8");
    foreach ( (array) $this->data as $selector => $css ) {
      echo $css;
      echo PHP_EOL;
    }

  }

}

?>
