<?php

if ( !defined( "bof_root" ) ) die;

class nest{

  protected function call_function( $function_type, $type ){

    if ( !( $validaor_path = bof()->object->core_files->validate_key( $function_type, $type ) ) )
    fall("{$type} {$function_type} is not defined");

    if ( !file_exists( $validaor_path ) )
    fall("{$type} {$function_type} file:{$validaor_path} doesn't exists");

    require_once( $validaor_path );

    if ( !function_exists( "{$function_type}_{$type}" ) )
    fall("{$type} {$function_type} function is missing");

	}
  protected function call_validator( $type ){
    return $this->call_function( "validator", $type );
  }
  protected function call_comparator( $type ){
    return $this->call_function( "comparator", $type );
  }

	public function validate( &$value, $type, $args=[] ){

    $args = $args ? $args : [];
    $accept_empty = in_array( 'empty()', $args, true );
    $original_value = $value;

    $function_name = "validator_{$type}";
    if ( !function_exists( $function_name ) )
    $this->call_validator( $type );

    $validate = $function_name( $value, $args, $this );

    return $accept_empty && ( empty( $original_value ) ? $original_value !== "0" && $original_value !== 0 : false ) ? true : $validate;

	}
  public function compare( $type, $args=[], $endpoint=[] ){

    $function_name = "comparator_{$type}";
    if ( !function_exists( $function_name ) )
    $this->call_comparator( $type );
    $compare = $function_name( $args, $endpoint, $this );
    return $compare;

  }
  public function user_input( $type, $name, $validator_type=null, $validator_args=[], $default_value=null ){

    // select_m fix
    if ( $validator_args ? in_array( "select_m()", $validator_args, true ) : false ){

      $input = [];
      $_vals = $type == "post" ? $_POST : $_GET;
      if ( !empty( $validator_args["values"] ) ){
        foreach( $validator_args["values"] as $_v ){
          if ( !empty( $_vals[ $name . "_" . $_v ] ) )
          $input[] = $_v;
        }
        $input = $input ? implode( ";", $input ) : false;
      }

    }

    // Get input
    elseif ( $type == "post" ? isset( $_POST[ $name ] ) : false )
    $input = $_POST[ $name ];

    elseif( $type == "get" ? isset( $_GET[ $name ] ) : false )
    $input = $_GET[ $name ];

    elseif( $type == "file" ? isset( $_FILES[ $name ] ) : false )
    $input = $_FILES[ $name ];

    elseif( $type == "cookie" ? isset( $_COOKIE[ $name ] ) : false )
    $input = $_COOKIE[ $name ];

    elseif ( $type == "server" ? isset( $_SERVER[ $name ] ) : false )
    $input = $_SERVER[ $name ];

    elseif ( $type == "session" ? bof()->session->get( $name ) !== null : false )
    $input = bof()->session->get( $name );

    elseif ( $type == "http_header"  )
    return $this->user_input( "server", 'HTTP_' . str_replace( "-", "_", strtoupper( $name ) ), $validator_type, $validator_args, $default_value );

    elseif ( $type == "argv" ){
      global $argv;
      if ( !empty ($argv ) ){
        foreach( $argv as $i => $_arg ){
          if ( !$i ) continue;
          if ( substr( $_arg, 0, strlen( "{$name}=" ) ) == "{$name}=" )
          $input = substr( $_arg, strlen( "{$name}=" ) );
        }
      }
    }


    // Check input existence
    if ( !isset( $input ) )
    return $default_value;

    // Need raw ( unvalidated && unsanitized ) input?
    if ( !$validator_type )
    return $input;

    // Validate && Sanitize input
    $original_input = $input;
    $validate = $this->validate( $input, $validator_type, $validator_args );

    if ( !$validate ) return $default_value;

    // Input is valid & it was passed as a refernce to `validate` function so it's also sanitized
    return $input;

  }
	public function escape( $string ){

		return htmlspecialchars( $string, ENT_QUOTES, 'UTF-8', false );

	}

}

?>
