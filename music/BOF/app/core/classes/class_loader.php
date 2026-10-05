<?php

if ( !defined( "bof_root" ) ) die;

class BusyOwlFramework {

  protected $args = [];
  protected $name = null;
  protected $required = null;
  protected $listeners = [];
  protected $extenders = [];
  protected $plugins = [];
  private $call_depth = 0;

  public function __construct( $args=[] ){
    $this->args = $args;
  }
  public function __setup(){

    // Require objecct class
    $this->required = (object)[];

    require_once( bof_root . "/app/core/classes/class_object.php" );
    $this->required->object = new bofProxy( "bof_object" );

    $plugins = [];
    $name = null;
    extract( $this->args );
    if ( $plugins ){
      foreach( $plugins as $plugin_name => $plugin_args ){
        $this->plugin( $plugin_name, $plugin_args );
      }
    }
    $this->plugin( "transit", [] );
    $this->name = $name;

  }
  public function getName(){
    return $this->name;
  }
  public function __get( $name ){

    if ( !empty( $this->required->$name ) )
    return $this->required->$name;

    if ( !( $path = $this->required->object->core_files->validate_key( "class", $name ) ) || $name == "db" )
    $this->general->fall("class {$name} is undefined");

    if ( !file_exists( $path ) )
    $this->general->fall("class {$name} file: {$path} is missing");

    require_once( realpath( $path ) );

    $extender = $this->get_extender( "class", $name );
    if ( $extender ){

      if ( !file_exists( $extender["path"] ) )
      fall("class {$extender["name"]} file: {$extender["path"]} is missing");

      require_once( realpath( $extender["path"] ) );

      $_func = new bofProxy( $extender["name"] );
      $this->required->$name = $_func;
      return $this->required->$name;

    }

    if ( $name == "nest" ) $this->required->$name = new $name();
    else {
      $_func = new bofProxy( $name );
      $this->required->$name = $_func;
    }
    return $this->required->$name;

  }

  public function plugin( $name, $args=[] ){

    if ( in_array( $name, $this->plugins, true ) )
    return false;

    $handshake_file = bof_root . "/app/plugins/{$name}/_handshake.php";
    extract( $args );

    if ( !is_file( $handshake_file ) )
    fall("Plugin: {$name} handshake file:{$handshake_file} is missing");

    $bof = $this;
    $nest = $this->nest;
    require_once( $handshake_file );

    $this->plugins[] = $name;

  }
  public function plugin_exists( $name ){
    return in_array( $name, $this->plugins, true );
  }
  public function extend( $_type, $_class, $extend_class_file, $extend_class_name ){

    if ( !in_array( $_type, [ "object", "class" ], true ) ) fall("Extend invalid type: {$_type}");
    $this->extenders[ $_type ][ $_class ] = [ "path" => $extend_class_file, "name" => $extend_class_name ];
    return true;

  }
  public function get_extender( $_type, $_class ){
    return !empty( $this->extenders[ $_type ][ $_class ] ) ? $this->extenders[ $_type ][ $_class ] : null;
  }
  public function listen( $class_name, $method_name, $function, $args=[] ){
    $this->listeners[ $class_name ][ $method_name ][] = [ $function, $args ];
  }
  public function call( $class_name, $method_name, &$method_args=null, &$method_result=null ){

    if (
      1 == 2 &&
      // in_array( $class_name , [ "response_css" ], true ) &&
      $class_name != "object"
    ) {

      $type = @end( explode( "_", $method_name ) ) == "pre" ? "pre" : ( @end( explode( "_", $method_name ) ) == "after" ? "after" : "direct" );

      if ( $type == "pre" ) $this->call_depth++;
      else if ( $type == "after" ) $this->call_depth--;
      else {
        for( $i=0; $i<$this->call_depth; $i++ ) echo "--- ";
        echo "> {$class_name} --> {$method_name}: <pre style='display:inline-block;margin:0;font-size:8pt;opacity:0.6'>".strip_tags(json_encode($method_args),"")."</pre><br>";
      }

    }

    $output = null;
    if ( !empty( $this->listeners[ $class_name ][ $method_name ] ) ){
      foreach( $this->listeners[ $class_name ][ $method_name ] as $__f ){
        $_output = $__f[0]( $method_args, $method_result, bof(), $output, $__f[1] );
        if ( $_output !== null ) $output = $_output;
      }
      if ( isset( $output ) )
      return $output;
    }


    return null;

  }
  public function define_db( $var_name, $args ){

    $path = $this->object->core_files->validate_key( "class", "db" );
    require_once( realpath( $path ) );
    $this->required->$var_name = new db();
    $this->required->$var_name->__set_auth( $args );
    return $this->required->$var_name;

  }
  public function defined( $name ){
    if ( !empty( $this->required->$name ) )
    return true;
    return false;
  }
  public function setFuncAsClass( $name, $func ){
    if ( empty( $this->required->$name ) )
    $this->required->$name = $func;
  }

  protected $proxy_stats = [];

  public function record_proxy_stat( $type, $class, $method, $time=null ){

    if ( empty( $this->proxy_stats[ $class ]["calls"] ) ){
      $this->proxy_stats[ $class ] = array(
        "calls" => 0,
        "time" => 0,
        "methods" => []
      );
    }
    if ( empty( $this->proxy_stats[ $class ]["methods"][ $method ]["calls"] ) ){
      $this->proxy_stats[ $class ]["methods"][ $method ] = array(
        "calls" => 0,
        "time" => 0,
        "funcs" => []
      );
    }

    if ( $type == "call" ){
      $this->proxy_stats[ $class ]["calls"]++;
      $this->proxy_stats[ $class ]["methods"][ $method ]["calls"]++;
    }
    else {
      $this->proxy_stats[ $class ]["time"] += $time;
      $this->proxy_stats[ $class ]["methods"][ $method ]["time"] += $time;

      if ( empty( $this->proxy_stats[ $class ]["methods"][ $method ]["funcs"][ $type ]["calls"] ) )
      $this->proxy_stats[ $class ]["methods"][ $method ]["funcs"][ $type ] = array(
        "calls" => 0,
        "time" => 0
      );
      $this->proxy_stats[ $class ]["methods"][ $method ]["funcs"][ $type ]["calls"]++;
      $this->proxy_stats[ $class ]["methods"][ $method ]["funcs"][ $type ]["time"] += $time;
    }

  }
  public function return_proxy_stats(){
    return $this->proxy_stats;
  }

}

class bof_child {

  public function __get( $name ){

    if ( $name == "_bof_this" )
    return bof()->object->__get( str_replace( "object_", "", get_class( $this ) ) );

    if ( $name == "_parent" && !empty( $this->sample_parent ) ){
      return bof()->object->__get( $this->sample_parent );
    }

    fall( "class:" . get_class($this) . " method:{$name} doesnt exists" );

  }

}
class bof_type_object extends bof_child {}
class bof_type_object_sample extends bof_type_object {}
class bof_type_object_child extends bof_type_object {}
class bof_type_class extends bof_child {

  public function __get( $name ){

    if ( $name == "_bof_this" )
    return bof()->__get( get_class( $this ) );

    fall( "class:" . get_class($this) . " method:{$name} doesnt exists" );

  }

}

?>
