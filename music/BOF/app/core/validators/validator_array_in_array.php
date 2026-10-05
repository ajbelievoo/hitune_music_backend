<?php

if ( !defined( "root" ) ) die;

function validator_array_in_array( &$value, $args, $nest ){

	$values = null;
	$strict = true;
	$implode = false;
	$explode_by = ";";
	$implode_by = false;
	extract( $args );
	$implode_by = $implode_by ? $implode_by : $explode_by;

	$validate = false;

	if ( isset( $value ) ){

		$value = is_array( $value ) ? $value : explode( $explode_by, $value );
		$compare = array_diff( $value, $values );
		$validate = !count( $compare );
		if ( $validate && $implode ) $value = implode( $implode_by, $value );

	}

	return $validate;

}

?>
