<?php

if ( !defined( "root" ) ) die;

function validator_string_code( &$value, $args, $nest ){

	return $nest->validate(
		$value,
		"string",
		array_merge(
			$args,
			array(
				"strict" => true,
				"strict_regex" => "[\p{L}0-9\_\-]",
			)
		)
	);

}

?>
