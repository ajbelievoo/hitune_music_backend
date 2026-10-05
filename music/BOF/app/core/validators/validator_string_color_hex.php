<?php

if ( !defined( "root" ) ) die;

function validator_string_color_hex( &$value, $args, $nest ){

	$accept_transparent = false;
	extract( $args );

	if ( gettype( $value ) !== "string" )
	return false;

	if ( strlen( $value ) == 7 && substr( $value, 0, 1 ) == "#" )
	$value = substr( $value, 1 );

	if ( $accept_transparent && $value === "transparent" )
	return $value;

	return $nest->validate(
		$value,
		"string",
		array_merge(
			$args,
			array(
				"strict" => true,
				"strict_regex" => "/^[a-f0-9]{6}$/i",
				"strict_regex_raw" => true
			)
		)
	);

}

?>
