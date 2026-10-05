<?php

if ( !defined( "root" ) ) die;

function validator_equal( &$given_value, $args, $nest ){

	$reverse = null;
	$value = null;
	extract( $args );

	if ( !isset( $value ) ) return false;
	if ( $value == $given_value ) return $reverse ? false : true;
	return $reverse ? true : false;

}

?>
