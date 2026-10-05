<?php

if ( !defined( "root" ) ) die;

function validator_string_abcd( &$value, $args, $nest ){

	return $nest->validate(
		$value,
		"string",
		array_merge(
			$args,
			array(
				"strict" => true,
				"strict_regex" => "[a-zA-Z0-9_.\-]",
			)
		)
	);

}

?>
