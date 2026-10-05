<?php

if ( !defined( "bof_root" ) ) die;

define( "pgt_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "pgt",
  pgt_plugin_root . "/classes/class_pgt.php"
);

$bof->object->core_files->add_key(
  "class",
  "pgt_offline",
  pgt_plugin_root . "/classes/class_pgt_offline.php"
);

$bof->object->core_files->add_key(
  "class",
  "pgt_paypal",
  pgt_plugin_root . "/classes/class_pgt_paypal.php"
);

$bof->object->core_files->add_key(
  "class",
  "pgt_stripe",
  pgt_plugin_root . "/classes/class_pgt_stripe.php"
);

$bof->pgt_offline->setup();
$bof->pgt_paypal->setup();
$bof->pgt_stripe->setup();

?>
