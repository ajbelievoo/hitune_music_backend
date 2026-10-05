<?php

if ( !defined( "root" ) ) die;

function validator_username( &$value, $args, $nest ){

	$min_length = 4;
	$max_length = 40;
	$regex = "a-zA-Z0-9_.";
	$dolower = true;
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
		return false;

	$validate = filter_var(
		$value,
		FILTER_VALIDATE_REGEXP,
		array(
			"options" => array(
				"regexp" => "/^(?![.])[{$regex}]{{$min_length},{$max_length}}$/"
			)
		)
	);

	if ( $dolower )
	$value = strtolower( trim( $value ) );

	return $validate;

}

?>
