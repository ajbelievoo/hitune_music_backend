<?php

if ( !defined( "root" ) ) die;

function validator_ip( &$value, $args, $loader ){

	// Check value type
	if ( !is_string( $value ) )
		return false;

	$validate = filter_var(
		$value,
		FILTER_VALIDATE_IP
	);

	$value = filter_var(
		$value,
		FILTER_SANITIZE_FULL_SPECIAL_CHARS
	);

	return $validate;

}

?>
