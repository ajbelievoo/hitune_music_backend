<?php

if ( !defined( "root" ) ) die;

function validator_password( &$value, $args, $nest ){

	$min_length = 6;
	$max_length = 60;
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
		return false;

	$validate = filter_var(
		$value,
		FILTER_VALIDATE_REGEXP,
		array(
			"options" => array(
				"regexp" => "/^[a-zA-Z0-9_ -!@;#$%^&*()_=+-.'\"\/\?\:\<\>\{\}\[\]]{{$min_length},{$max_length}}$/"
			)
		)
	);

	return $validate;

}

?>
