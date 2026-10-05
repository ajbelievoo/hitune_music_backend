<?php

if ( !defined( "bof_root" ) ) die;

define( "transit_plugin_root", dirname(__FILE__) );

$bof->object->core_files->add_key(
  "class",
  "transit",
  transit_plugin_root . "/classes/class_transit.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_aws_s3",
  transit_plugin_root . "/classes/class_transit_aws_s3.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_ftp",
  transit_plugin_root . "/classes/class_transit_ftp.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_localhost",
  transit_plugin_root . "/classes/class_transit_localhost.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_wasabi",
  transit_plugin_root . "/classes/class_transit_wasabi.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_minio",
  transit_plugin_root . "/classes/class_transit_minio.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_bunny",
  transit_plugin_root . "/classes/class_transit_bunny.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_storj",
  transit_plugin_root . "/classes/class_transit_storj.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_backblaze",
  transit_plugin_root . "/classes/class_transit_backblaze.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_cloudflare",
  transit_plugin_root . "/classes/class_transit_cloudflare.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_cdn777",
  transit_plugin_root . "/classes/class_transit_cdn777.php"
);

$bof->object->core_files->add_key(
  "class",
  "transit_mega",
  transit_plugin_root . "/classes/class_transit_mega.php"
);

?>
