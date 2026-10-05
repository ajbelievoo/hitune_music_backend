<?php

if ( !defined( "bof_root" ) ) die;

class execute {

  protected $executed = false;
  protected $data = null;

  public function __construct(){
    $this->data = (object) array();
  }

  public function run(){

    if ( $this->executed ) return;

    if ( empty( bof()->endpoint->get()->name ) )
    fall( "Execute: Run: No Endpoint!" );

    $this->executed = true;
    bof()->execute->run_endpoint_response();
    bof()->execute->run_executers();

  }
  public function run_endpoint_response(){

    $endpoint = bof()->endpoint->get();
    $user = bof()->user->get();

    if ( empty( $endpoint->name ) )
    fall( "Execute: Run: No Endpoint!" );

    if ( !$endpoint->args["response_type"] )
    fall( "Execute: Run: No response_type!" );

    bof()->response->set(
      $endpoint->args["response_type"],
      $endpoint->args["response_data"]
    );

  }
  public function run_executers(){

    $endpoint = bof()->endpoint->get();
    $executers = $endpoint->args["executers"];
    if ( empty( $executers ) ) return;

    $results = (object) array();
    foreach( $executers as $executer ){
      $_exe_func_name = pathinfo( $executer, PATHINFO_FILENAME );
      $_exe = bof()->execute->run_executer( $executer );
      $results->$_exe_func_name = $_exe;
      if ( $_exe == "HALT" ) break;
    }
    return $results;

  }
  public function run_executer( $executer, $args=[] ){

    $_exe_func_name = pathinfo( $executer, PATHINFO_FILENAME );

    // second+ run
    if ( function_exists( $_exe_func_name ) )
    return $_exe_func_name( bof(), bof()->execute, $args );

    // check & require file
    if ( !file_exists( $executer ) )
    fall("Execute: Run: {$_exe_func_name} file missing");
    require_once($executer );

    // function name
    if ( !function_exists( $_exe_func_name ) )
    fall("Execute: Run: {$_exe_func_name} function missing");

    return $_exe_func_name( bof(), bof()->execute, $args );

  }

  public function set_data( $key, $args ){
    $this->data->$key = $args;
  }

  public function get_data( $key, $default_value=null ){

    if ( !isset( $this->data->$key ) )
    return $default_value;

    return $this->data->$key;

  }

}

?>
