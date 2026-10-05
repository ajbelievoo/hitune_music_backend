<?php

if ( !defined( "bof_root" ) ) die;

class ai_service extends bof_type_class {

  protected $core_name = null;
  protected $core_class = null;
  protected $core = null;
  public function setup(){}
  public function set_core( $name, $class ){
    $this->core_name = $name;
    $this->core = $this->core_class = $class;
  }

  public function fee( $service, $core, $action, $fees ){
    return bof()->ai->fee( $service, $core, $action, $fees );
  }

}

?>
