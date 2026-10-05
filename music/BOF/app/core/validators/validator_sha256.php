<?php

if ( !defined( "root" ) ) die;

function validator_sha256( &$value, $args, $nest ){

	$length = 64;
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
		return false;

	$validate = filter_var(
		$value,
		FILTER_VALIDATE_REGEXP,
		array(
			"options" => array(
				"regexp" => "/^[a-zA-Z0-9]{{$length}}$/"
			)
		)
	);

	$value = strval( $value );

	return $validate;

}

?>
