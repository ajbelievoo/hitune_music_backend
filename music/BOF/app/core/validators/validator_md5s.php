<?php

if ( !defined( "root" ) ) die;

function validator_md5s( &$value, $args, $nest ){

	extract( $args );

	if ( !is_string( $value ) )
	return false;

	$validate = true;
	$gValue = $value;
	$value = [];

	foreach( explode( ",", $gValue ) as $givenHash ){

		$validate_one = $nest->validate(
			$givenHash,
			"md5"
		);

		if ( $validate_one )
		$value[] = $givenHash;
		else{
  		$validate = false;
			$value = false;
			break;
		}

		$value = array_unique( $value );

	}

	return $validate;

}

?>
