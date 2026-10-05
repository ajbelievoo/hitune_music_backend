<?php

if ( !defined( "bof_root" ) ) die;

class nodejs extends bof_type_class {

  protected $client = false;

  public function getClient(){

    $nodejs_path = bof()->object->db_setting->get("nodejs_path");
    return htmlspecialchars_decode( $nodejs_path );
    return false;

  }

  /*protected $error = null;
  public function setError( $string ){
    $this->error = $string;
  }
  public function getError(){
    return $this->error ? str_replace( PHP_EOL, "<br>", $this->error ) : null;
  }*/

  protected $command = null;
  public function setCommand( $string ){
    $this->command = $string;
  }
  public function getCommand(){
    return $this->command;
  }

  public function run( $path, $args=[] ){

    extract( $args );

    $client = $this->getClient();
    if ( !$client ) return false;

    $node_command = ( "\"{$client}\" {$path}" );
    $node_command = str_replace( [ PHP_EOL, "   ", "  " ], " ", $node_command );

    $this->setCommand( $node_command );
    $exec = bof()->general->exec( $node_command );

    return $exec;

  }

}

?>
