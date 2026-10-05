<?php

if ( !defined( "root" ) ) die;

function validator_float( &$value, $args, $nest ){

	$min = 0;
	$max = null;
	$forceZero = false;
	$forceNull = false;
	$big_round = true;
	$maxPer = 2;
	extract( $args );

	// Check value type
	if ( !in_array( gettype( $value ), [ "string", "integer", "float", "double" ], true ) )
		return false;

	if (gettype($value) == "string") {
		$value = (float) str_replace(",", "", $value);
		$value = number_format($value,$maxPer, '.', '');
	}

	$validate = gettype( filter_var(
		$value,
		FILTER_VALIDATE_FLOAT
	) ) === "double";

	$value = filter_var(
		$value,
		FILTER_SANITIZE_NUMBER_FLOAT,
		array(
			"flags" => FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND
		)
	);

	if ( $validate && $min !== null ? $min > $value : false ){
		$validate = false;
	}

	if ( $validate && $max ? $value > $max : false ){
		$validate = false;
	}

	if ( empty( $value ) && $forceZero )
	$value = 0;

	if ( empty( $value ) && $forceNull )
	$value = null;

	if ( !empty( $value ) && $big_round ){
		$value = (float) round( $value, 4 );
	}

	return $validate;

}

?>
