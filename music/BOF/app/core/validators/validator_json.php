<?php

if ( !defined( "root" ) ) die;

function validator_json( &$value, $args, $nest ){

	$encode = false;
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
		return false;

	try {
		$json_parsed = json_decode( $value, true );
		$validate = true;
	}
	catch( Exception $err ){
		$validate = false;
	}

	if ( $validate ){
		if ( $validate = json_last_error() === JSON_ERROR_NONE )
			$value = $json_parsed;
	}

	if ( $validate && $encode )
	$value = json_encode( $value );

	return $validate;

}

?>
