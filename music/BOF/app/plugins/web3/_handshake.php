<?php

if ( !defined( "bof_root" ) ) die;

$bof->object->core_files->add_key(
  "class",
  "web3",
  dirname(__FILE__)."/classes/class_web3.php"
);

bof()->web3->setup();

?>
