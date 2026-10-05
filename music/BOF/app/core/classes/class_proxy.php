<?php

class bofProxy {

  private $_obj;
  private $class_name;

  public function __construct( $class_name ) {

    if ( !class_exists( $class_name ) )
    fall( "Class {$class_name} doesn't exists" );

    $this->_obj = new $class_name();
    $this->class_name = $class_name;

  }
  public function __call( $methodName, $raw_args ) {

    $args = [];
    foreach( $raw_args as $i => $arg ){
      $args[$i] = &$arg;
      unset( $arg );
    }

    if ( !production ){
      $time_start = microtime(true);
      bof()->record_proxy_stat( "call", $this->class_name, $methodName );
    }

    $pre = bof()->call( $this->class_name, "{$methodName}_pre", $args );

    if ( !production ){
      $time_pre = microtime(true) - $time_start;
      $time_start = microtime(true);
      bof()->record_proxy_stat( "pre", $this->class_name, $methodName, $time_pre );
    }

    if ( ( $run = bof()->call( $this->class_name, $methodName, $args ) ) === null ){
      if ( !method_exists( $this->_obj, $methodName ) ){

        if ( method_exists( $this->_obj, "bof" ) ){

          if ( !empty( $this->_obj->sample_parent ) ? $this->_obj->_parent->method_exists( $methodName ) : false ){

            $object_name = str_replace( [ "object_", "_extend" ], "", $this->class_name );
            $_object = bof()->object->__get( $object_name );
            $_exe = call_user_func_array( array( $this->_obj->_parent, $methodName ), array_merge( [ $_object ], $raw_args ) );

            if ( !production ){
              $time_par = microtime(true) - $time_start;
              $time_start = microtime(true);
              bof()->record_proxy_stat( "parent", $this->class_name, $methodName, $time_par );
              bof()->record_proxy_stat( "child", $this->_obj->sample_parent, $methodName, $time_par );
            }

            return $_exe;

          }

          if ( in_array( $methodName, [ "insert", "select", "select_m", "sid", "shash", "update", "delete", "create", "count", "count_v2", "get_free_hash", "get_free_url", "mark_time", "make_rels", "delete_rels", "select_rels", "search", "publicize" ] ) ){

            $object_name = str_replace( [ "object_", "_extend" ], "", $this->class_name );
            $_object = bof()->object->__get( $object_name );

            // bof()->object->set_caller( $_object->direct(), $_object );
            $_exe = call_user_func_array( array( bof()->object, "_{$methodName}" ), array_merge( [ $_object ], $raw_args ) );

            if ( !production ){
              $time_bof = microtime(true) - $time_start;
              $time_start = microtime(true);
              bof()->record_proxy_stat( "bof", $this->class_name, $methodName, $time_bof );
            }

            return $_exe;

          }

        }

        if ( empty( $failOnFailure ) )
        fall("Class {$this->class_name} method {$methodName} doesn't exists.");

        return false;

      }
    }

    $replace = bof()->call( $this->class_name, "{$methodName}_replace", $args, $run );

    if ( $replace !== null ){

      $run = $replace;

      if ( !production ){
        $time_replace = microtime(true) - $time_start;
        $time_start = microtime(true);
        bof()->record_proxy_stat( "rep", $this->class_name, $methodName, $time_replace );
      }

    }
    else {

      $run = call_user_func_array( array( $this->_obj, $methodName ), $args );

      if ( !production ){
        $time_run = microtime(true) - $time_start;
        $time_start = microtime(true);
        bof()->record_proxy_stat( "run", $this->class_name, $methodName, $time_run );
      }

    }

    if ( ( $after = bof()->call( $this->class_name, "{$methodName}_after", $args, $run ) ) !== null ){

      $run = $after;

      if ( !production ){
        $time_run = microtime(true) - $time_start;
        $time_start = microtime(true);
        bof()->record_proxy_stat( "aft", $this->class_name, $methodName, $time_run );
      }

    }

    return $run;

  }
  public function __get( $name ){

    if ( !in_array( $this->class_name, [ "bof_object", "response" ], true ) ){
      if ( empty( $this->_obj->nester ) )
      fall("{$this->class_name}:{$name} doesn't exists. Object not a nester");
    }

    if ( !( $run = bof()->call( $this->class_name, $name ) ) )
    $run = $this->_obj->$name;

    return $run;

  }
  public function method_exists( $name ){

    $exists = method_exists( $this->_obj, $name );
    if ( $exists ) return true;

    if ( !empty( $this->_obj->sample_parent ) ){
      if ( $this->_obj->_parent->method_exists( $name ) )
      return true;
    }

    return false;

  }
  public function direct(){
    return $this->_obj;
  }
  public function get_class_name(){
    return $this->class_name;
  }

}

?>
