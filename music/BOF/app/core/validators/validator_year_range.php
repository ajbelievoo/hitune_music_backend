<?php

if ( !defined( "root" ) ) die;

function validator_year_range( &$value, $args, $nest ){

	$min = 1900;
	$max = date( "Y" );
	extract( $args );

	if ( !$value )
	return;

	if ( !is_string( $value ) )
	return;

	$_vals = explode( "-", $value );
	if ( !is_array( $_vals ) )
	return;

	if ( count( $_vals ) != 2 )
	return;

	if ( !bof()->general->numeric( $_vals[0] ) || $_vals[0] < $min || $_vals[0] > $max || strlen( $_vals[0] ) != "4" )
	return;

	if ( !bof()->general->numeric( $_vals[1] ) || $_vals[1] < $min || $_vals[1] > $max || strlen( $_vals[1] ) != "4" )
	return;

	if ( $_vals[0] > $_vals[1] )
	return;

	$value = "{$_vals[0]}-{$_vals[1]}";
	return true;

}

?>
