<?php

class object_theme {

  protected $items = [];

  public function add( $name, $args ){

    $class_path = null;
    $class_name = "theme_{$name}";
    extract( $args );

    if ( !$name ? true : !bof()->nest->validate( $name, "string_abcd" ) )
    fall("Theme: Add: Invalid name");

    if ( !bof()->nest->validate( $class_name, "string_abcd" ) )
    fall("Theme: Add: Invalid class_name");

    if ( !$class_path ? true : !file_exists( $class_path ) )
    fall("Theme: Add: Invalid class_path");

    require_once( $class_path );
    if ( !class_exists( $class_name ) )
    fall("Theme: Add: Class {$class_name} is undefined");

    $theme_class = new MayAccessProxy( bof(), $class_name );
    foreach( [ "_ini", "set_args", "get_address", "get_html_data" ] as $_method_name ){
      if ( !$theme_class->method_exists( $_method_name ) )
      fall("Theme: {$name} Method: {$_method_name} is missing");
    }
    $this->items[ $name ] = $theme_class;
    return true;

  }

  public function validate_name( $name ){
    return in_array( $name, array_keys( $this->items ), true );
  }

  public function get( $name ){

    if ( !$this->validate_name( $name ) )
    fall( "Theme: Get: Invalid name" );

    return $this->items[ $name ];

  }

}

?>
