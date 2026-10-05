<?php

if ( !defined( "bof_root" ) ) die;

define( "bof_sitemap_generator", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "sitemap_generator",
  bof_sitemap_generator . "/classes/class_sitemap_generator.php"
);

$bof->sitemap_generator->setup();

?>
