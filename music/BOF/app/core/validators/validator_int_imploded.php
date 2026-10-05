<?php

if ( !defined( "root" ) ) die;

function validator_int_imploded( &$value, $args, $nest ){

	$min = 1;
	$max = null;
	$forceNull = false;
	$max_numbers = false;
	$min_numbers = false;
	$seperator = ",";
	$implode = true;
	extract( $args );

	// Check value type
	if ( !in_array( gettype( $value ), [ "string", "integer", "float", "double" ], true ) )
	return false;

	$validate = false;

	if ( !empty( $value ) ){

		$validate = true;

		$_values = explode( $seperator, $value );

		if ( $min_numbers ? count( $_values ) < $min_numbers : false )
		$validate = false;

		if ( $max_numbers ? count( $_values ) > $max_numbers : false )
		$validate = false;

		foreach( $_values as &$_value ){

			if ( !is_numeric( $_value ) )
			$validate = false;

			else {

				$_value = intval( $_value );

				if ( $min !== null ? $_value < $min : false )
				$validate = false;

				if ( $max ? $_value > $max : false )
				$validate = false;

			}

		}

		if ( $validate && $implode )
		$value = implode( $seperator, $_values );
		elseif ( $validate && !$implode )
		$value = $_values;

	}

	if ( !$validate && $forceNull )
	$value = null;

	return $validate;

}

?>
