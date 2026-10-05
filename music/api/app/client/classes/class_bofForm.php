<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class bofForm extends bof_type_class {

  public function parse( $args ){

    $ID = uniqid();
    $inputs = null;
    $becli = null;
    $groups = null;
    extract( $args );

    if ( !$inputs )
    throw new Exception( "no \$inputs" );

    $parse_inputs = bof()->bofForm->parse_inputs( $inputs );

    return array(
      "ID" => $ID,
      "inputs" => $parse_inputs,
      "becli" => $becli,
      "groups" => $groups
    );

  }
  public function parse_inputs( $data ){

    if ( !is_array( $data ) )
    throw new Exception( "\$inputs is not an array" );

    $inputs_parsed = [];
    foreach( $data as $n => $args ){
      $parse_input = bof()->bofForm->parse_input( $n, $args );
      if ( $parse_input )
      $inputs_parsed[ $n ] = $parse_input;
    }

    return $inputs_parsed;

  }
  public function parse_input( $name, $args, $caller="display" ){

    if ( !is_array( $args ) || ( empty( $args["input"] ) && empty( $args["bofInput"] ) ) )
    throw new Exception( "\$input {$name} is invalid" );

    $required = false;
    $label = null;
    $hook = null;
    $tip = null;
    $tip_hook = null;
    $input = null;
    $bofInput = null;
    $validator = null;
    $display_on = null;
    $value = null;
    $group = null;
    $func = null;
    $func_params = null;
    extract( $args );

    if ( empty( $label ) && !empty( $hook ) && $caller == "display" )
    $args["label"] = $label = bof()->object->language->turn( $hook, [], [ "uc_first" => true, "lang" => "users" ] );
    elseif ( empty( $label ) && !empty( $hook ) )
    $args["label"] = $label = $hook;

    if ( empty( $tip ) && !empty( $tip_hook ) && $caller == "display" )
    $args["tip"] = $tip = bof()->object->language->turn( $tip_hook, [], [ "uc_first" => true, "lang" => "users" ] );


    $args["input"] = $input = $input ? $input : [];
    $args["input"]["name"] = $input["name"] = $name;
    if ( $value ) $args["input"]["value"] = $input["value"] = $value;

    if ( $bofInput ){

      $parse_bofInput = bof()->bofInput->parse( $args );

      if ( !$parse_bofInput )
      return false;

      foreach( [ "label", "tip", "input", "bofInput", "validator" ] as $___n )
      $$___n = isset( $parse_bofInput["data"][ $___n ] ) ? $parse_bofInput["data"][ $___n ] : $$___n;

    }

    if ( $caller == "display" ){
      if ( $required ) $label .= "<span class='_rm'>*</span>";
      $validator = null;
      $bofInput = null;
    }

    $output = array(
      "label" => $label,
      "tip" => $tip,
      "input" => $input,
      "bofInput" => $bofInput,
      "validator" => $validator,
      "display_on" => $display_on,
      "group" => $group
    );

    if ( $func )
    $func( $name, $args, $output, $func_params );

    return $output;

  }

  public function validate( $form, $simplify=false, $args=[] ){

    $ID = uniqid();
    $inputs = null;
    $becli = null;
    extract( $form );

    if ( !$inputs )
    throw new Exception( "no \$inputs" );

    $validate_inputs = bof()->bofForm->validate_inputs( $inputs, $args );

    if ( $simplify ){
      return array_map(
        function( $val ){
          return $val["value"];
        },
        $validate_inputs
      );
    }

    return $validate_inputs;

  }
  public function validate_inputs( $data, $args=[] ){

    $remove_prefix = null;
    extract( $args );

    if ( !is_array( $data ) )
    throw new Exception( "\$inputs is not an array" );

    $inputs_validated = [];
    foreach( $data as $n => $args ){
      $validate_input = bof()->bofForm->validate_input( $n, $args );
      $inputs_validated[ $remove_prefix ? str_replace( $remove_prefix, "", $n ) : $n ] = $validate_input;
    }

    return $inputs_validated;

  }
  public function validate_input( $name, $args, $caller="executer" ){

    if ( !is_array( $args ) || ( empty( $args["validator"] ) && empty( $args["bofInput"] ) ) )
    throw new Exception( bof()->object->language->turn( "invalid_input", [ "input_name" => $name ], [ "uc_first" => true, "lang" => "users" ] ) );

    $required = false;
    $label = null;
    $input = null;
    $bofInput = null;
    $validator = null;
    $value = null;

    if ( !empty( $args["be_func"] ) )
    $args["be_func"]( $name, $args, !empty( $args["be_func_params"] ) ? $args["be_func_params"] : null );

    extract( $args );

    $args["input"] = $input = $input ? $input : [];
    $args["input"]["name"] = $input["name"] = $name;

    if ( $validator ? is_string( $validator ) : false )
    $args["validator"] = $validator = [ $validator ];


    $given_value = bof()->bofInput->__get_value( $name, $args, "post" );

    if ( $required && ( !$given_value[0] || !$given_value[1] ) ){
      if ( empty( $label ) && !empty( $hook ) )
      $label = bof()->object->language->turn( $hook, [], [ "lang" => "users" ] );
      throw new Exception( bof()->object->language->turn( "invalid_input", [ "input_name" => $label ? $label : $name ], [ "uc_first" => true, "lang" => "users" ] ) );
    }

    if ( $bofInput ){

      // $args["input"]["value"] = $given_value[1];
      $validate_bofInput = bof()->bofInput->validate( $args );

      if ( !$validate_bofInput ){
        $_value = 0;
      }
      else if ( $bofInput[0] == "object" ){
        $_value = $validate_bofInput;
      }
      else if ( $bofInput[0] == "file" ){

        $finalize_upload = bof()->object->file->finalize_upload(
          $bofInput[1]["type"],
          $bofInput[1]["object_type"],
          !empty( $bofInput[1]["object_id"] ) ? $bofInput[1]["object_name"] . $bofInput[1]["object_id"] : null,
          $validate_bofInput,
          $value,
          $bofInput[1]
        );

        $_value = 0;
        if ( $finalize_upload )
        $_value = $validate_bofInput;

        $parse_bofInput = bof()->bofInput->parse( $args );
        $input = $parse_bofInput["data"]["input"];

      }

    }
    else {
      $_value = $given_value[1];
    }

    return array(
      "value" => isset( $_value ) ? $_value : null,
      "exists" => $given_value[0],
      "validator" => $validator,
      "input" => $input
    );

  }

}

?>
