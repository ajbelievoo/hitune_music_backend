<?php

if ( !defined( "bof_root" ) ) die;

class endpoint extends bof_type_class {

  protected $defaults = array(
    "landing" => null,
    "404" => "404",
    "no_access" => "no_access"
  );
  protected $name = null;
  protected $data = null;

  protected function set_default( $name, $value ){

    $exists = bof()->object->endpoint->get( $value );
    if ( !$exists ) fall( "Endpoint: {$value} is not defined" );

    $this->defaults[ $name ] = $value;

  }
  public function set_landing( $name ){
    $this->set_default( "landing", $name );
  }
  public function set_no_access( $name ){
    $this->set_default( "no_access", $name );
  }
  public function set_404( $name ){
    $this->set_default( "404", $name );
  }
  public function set( $name, $data=[] ){

    $exists = bof()->object->endpoint->get( $name );
    if ( !$exists ) fall( "Endpoint: {$name} is not defined" );

    $this->name = $name;
    $this->data = $data;

  }
  public function get(){

    if ( empty( $this->name ) )
    $this->check();

    return (object) array(
      "name" => $this->name,
      "data" => is_bool( $this->data ) ? null : $this->data,
      "args" => bof()->object->endpoint->get( $this->name )
    );

  }

  public function check( $set=true ){

    $url = (string) bof()->request->get_requested_url();
    $url_exists = false;
    $endpoints = bof()->object->endpoint->get_all();
    $endpoints_names = array_keys( $endpoints );
    if ( empty( $endpoints ) ) fall("No endpoints defined");

    while( empty( $_endpoint ) && !empty( $endpoints_names ) ){

      $endpoint_name = array_shift( $endpoints_names );
      $endpoint = $endpoints[ $endpoint_name ];
      $_endpoint_data = null;

      // URL Comparator config
      if ( !is_array( $endpoint["url"] ) && !is_string( $endpoint["url"] ) && $endpoint["url"] !== true ) continue;

      if ( $endpoint["url"] !== true ){
        if ( gettype( $endpoint["url"] ) == "string" ) $endpoint["url"] = [ "equal" => $endpoint["url"] ];
        if ( empty( $endpoint["url"][0] ) ? true : $endpoint["url"][0] != "url" ) $endpoint["url"] = [ "url", $endpoint["url"] ];
        array_unshift( $endpoint["comparators"], $endpoint["url"] );
      }

      // echo "<br>========================================<br>";
      // var_dump( $endpoint_name );

      $all_valid = true;
      foreach( $endpoint["comparators"] as $_comparator ){

        $compare = bof()->nest->compare( $_comparator[0], $_comparator[1], $endpoint );

        // var_dump( $_comparator[0], $_comparator[1], $compare );
        // echo "<br>-------------------<br>";

        if ( $compare === false ){
          $all_valid = false;
          break;
        }
        if ( $_comparator[0] == "url" ){
          $_endpoint_data = $compare;
          $url_exists = true;
        }

      }

      if ( $all_valid )
      $_endpoint = $endpoint_name;

    }

    if ( empty( $_endpoint ) ){
      if ( empty( $url ) ? true : $url == "" ) $_endpoint = $this->defaults["landing"];
      else $_endpoint = $url_exists ? $this->defaults["no_access"] : $this->defaults["404"];
      $_endpoint_data = [ "url" => $url ];
    }

    if ( $set )
    bof()->endpoint->set( $_endpoint, $_endpoint_data );

    $__d = (object) array(
      "name" => $_endpoint,
      "data" => !is_bool( $_endpoint_data ) ? $_endpoint_data : null,
      "args" => bof()->object->endpoint->get( $this->name )
    );

    return $__d;

  }

}

?>
