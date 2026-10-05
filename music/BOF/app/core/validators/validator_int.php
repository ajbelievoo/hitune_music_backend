<?php

if ( !defined( "root" ) ) die;

function validator_int( &$value, $args, $nest ){

	$min = 1;
	$max = null;
	$forceZero = false;
	$forceNull = false;
	extract( $args );

	// Check value type
	if ( !in_array( gettype( $value ), [ "string", "integer", "float", "double" ], true ) )
	return false;

	$value = str_replace( ",", "", $value );
	$validate = is_numeric( $value );

	if ( $validate )
	$value = intval( $value );

	if ( $validate && $min !== null ? $min > $value : false )
	$validate = false;

	if ( $validate && $max ? $max < $value : false )
	$validate = false;

	if ( empty( $value ) && $forceZero )
	$value = 0;

	if ( empty( $value ) && $forceNull )
	$value = null;

	return $validate;

}

?>
