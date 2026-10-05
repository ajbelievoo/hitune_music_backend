<?php

if ( !defined( "bof_root" ) ) die;

class web3 extends bof_type_class {

  public function setup(){

    bof()->object->core_files->add_key(
      "class",
      "web3_infura",
      dirname(__FILE__)."/class_infura.php"
    );

    bof()->object->core_files->add_key(
      "class",
      "web3_alchemy",
      dirname(__FILE__)."/class_alchemy.php"
    );

    bof()->object->core_files->add_key(
      "class",
      "web3_etherscan",
      dirname(__FILE__)."/class_etherscan.php"
    );

  }

}



?>
