<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

bof()->define_db( "db", array(
  "host" => db_host,
  "user" => db_user,
  "pass" => db_pass,
  "name" => db_name
) );

?>
