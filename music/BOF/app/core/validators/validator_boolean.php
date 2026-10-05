<?php

if ( !defined( "root" ) ) die;

function validator_boolean( &$value, $args, $nest ){

	$int = false;
	extract( $args );

	if ( !$value ) $value = $int ? 0 : false;
	else $value = $int ? 1 : true;

	return true;

}

?>
