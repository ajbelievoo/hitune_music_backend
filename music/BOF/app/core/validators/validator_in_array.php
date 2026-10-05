<?php

if ( !defined( "root" ) ) die;

function validator_in_array( &$value, $args, $nest ){

	$values = null;
	$strict = true;
	extract( $args );

	// Check value type
	if ( !in_array( gettype( $value ), [ "string", "integer", "float", "double" ], $strict ) )
		return false;

	if ( empty( $values ) ? true : !is_array( $values ) )
		return false;

	return in_array(
		$value,
		$values,
		$strict
	);

}

?>
