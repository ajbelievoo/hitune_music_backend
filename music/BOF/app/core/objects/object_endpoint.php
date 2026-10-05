<?php

if ( !defined( "bof_root" ) ) die;

class object_endpoint {

  protected $groups = [];
  protected $items  = [];

  public function add( $name, $args ){

    if ( !bof()->nest->validate( $name, "string_abcd" ) )
    fall( "Endpoint: Add Group: Invalid_name" );

    if ( !$this->validate_args( $args, $name ) )
    fall( "Endpoint: Add Group: Invalid_args" );

    $this->items[ $name ] = $args;
    return true;

  }
  public function remove( $name ){
    if ( !empty( $this->items[ $name ] ) )
    unset( $this->items[ $name ] );
  }
  public function add_group( $name, $args ){

    if ( !bof()->nest->validate( $name, "string_abcd" ) )
    fall( "Endpoint: Add Group: Invalid_name" );

    if ( !$this->validate_args( $args, $name ) )
    fall( "Endpoint: Add Group: Invalid_args" );

    $this->groups[ $name ] = $args;
    return true;

  }
  public function validate_args( &$args, $name ){

    $url = null;
    $groups = null;
    $groups_exclude_general = false;
    $skip_key_check = false;
    $comparators = [];
    $executers = [];
    $response_type = (string) "";
    $response_data = [];
    $log = true;
    $copy = null;
    $priority = 1;
    extract( $args );

    // Validate Comparators
    if ( $comparators ){
      foreach( $comparators as $comparator ){

        if ( !is_array( $comparator ) )
        fall( "Endpoint: Validate Args: comparator is not array" );

        if ( empty( $comparator[0] ) || empty( $comparator[1] ) ? true : !is_array( $comparator[1] ) || !is_string( $comparator[0] ) )
        fall( "Endpoint: Validate Args: comparator is not valid array" );

        $comparator_type = $comparator[0];
        $comparator_args = $comparator[1];

        if ( !bof()->object->core_files->validate_key( "comparator", $comparator_type ) )
        fall( "Endpoint: Validate Args: comparator {$comparator_type} is not defined" );

      }
    }

    // Validate Executers
    if ( $executers ){
      foreach( $executers as &$executer ){

        if ( !is_string( $executer ) )
        fall( "Endpoint: Validate Args: executer is not string" );

        // if ( !file_exists( $executer ) )
        // fall( "Endpoint: Validate Args: executer file ( {$executer} ) is missing" );

        $executer = realpath( $executer );

      }
    }

    // TODO:
    // Validate url
    // Validate Executers
    // Validate Response_type
    // Validate Response_data

    // Simplify Args
    $args = array(
      "url" => $url,
      "comparators" => $comparators,
      "groups" => $groups,
      "executers" => $executers,
      "response_type" => $response_type,
      "response_data" => $response_data,
      "log" => $log,
      "priority" => $priority
    );

    if ( $skip_key_check )
    $args["skip_key_check"] = true;

    // Get Group(s) Args
    if ( !$groups_exclude_general && !in_array( $name, [ "404", "no_access" ], true ) && ( $name != "general" ? ( $general_group = bof()->object->endpoint->get_group( "general" ) ) : false ) )
    $groups = $groups ? array_merge( $groups, [ "general" ] ) : [ "general" ];

    if ( !empty( $groups ) ){
      foreach( $groups as $__i => $__b ){

        $__k = is_int( $__i ) ? $__b : $__i;
        $__r = is_int( $__i ) ? [ "comparators", "executers", "response_type", "response_data" ] : $__b;
        if ( !is_array( $__r ) ) fall( "Endpoint: Validate Args: Groups: \$__r is not an array" );
        if ( !( $__d = bof()->object->endpoint->get_group( $__k ) ) ) fall( "Endpoint: Validate Args: Groups: {$__k} is not defined" );

        foreach( $__r as $___r ){
          if ( $__d[ $___r ] ){
            if ( gettype( $__d[$___r] ) != gettype( $args[ $___r ] ) )
            fall( "Endpoint: Validate Args: Type of {$___r}s don't match" );
            $args[ $___r ] = gettype( $__d[$___r] ) == "string" ? ( empty( $args[ $___r ] ) ? $__d[$___r] : $args[ $___r ] ) : array_merge( $args[ $___r ], $__d[$___r] );
          }
        }

      }
    }

    if ( $copy ? ( $copy_target = $this->get( $copy ) ) : false ){
      foreach( $copy_target as $k => $v ){

        if ( !$args[ $k ] && $v )
        $args[ $k ] = $v;

        else if ( is_array( $args[ $k ] ) && is_array( $v ) ){

          $args[ $k ] = array_merge( $v, $args[ $k ] );

          $both_one_dimension = true;
          foreach( $args[ $k ] as $__v ){
            if ( is_array( $__v ) || is_object( $__v ) )
            $both_one_dimension = false;
          }
          foreach( $v as $__v ){
            if ( is_array( $__v ) || is_object( $__v ) )
            $both_one_dimension = false;
          }

          if ( $both_one_dimension )
          $args[ $k ] = array_unique( $args[ $k ] );

        }

      }
    }

    return true;

  }

  public function get( $name ){

    return empty( $this->items[ $name ] ) ? null : $this->items[ $name ];

  }
  public function get_group( $name ){
    return empty( $this->groups[ $name ] ) ? null : $this->groups[ $name ];
  }
  public function get_all(){

    $items = $this->items;

    if ( !function_exists( "sort_by_priority" ) ){
      function sort_by_priority( $a, $b ) {
        return $b['priority'] - $a['priority'];
      }
    }

    uasort(
      $items,
      "sort_by_priority"
    );

    return $items;

  }
  public function get_all_groups(){

    return $this->groups;

  }

}

?>
