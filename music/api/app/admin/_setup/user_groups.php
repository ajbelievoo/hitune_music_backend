<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

bof()->user->add_group( "guest" );
bof()->user->add_group( "moderator" );
bof()->user->add_group( "admin" );

?>
