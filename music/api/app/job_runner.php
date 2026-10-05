<?php

if ( !defined( "bof_root" ) ) die;

function job_runner( $loader, $excuter, $args ){

  $loader->cronjob->execute();

}

?>
