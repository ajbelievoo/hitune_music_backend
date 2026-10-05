<?php

if ( !defined( "root" ) ) die;

function validator_html( &$value, $args, $nest ){

	$allowed_tags = false;
	$encode = false;
	extract( $args );
	$validate = true;

	if ( $value && $allowed_tags )
	$value = strip_tags( $value, $allowed_tags );

	if ( $encode ){
		$value = htmlspecialchars_decode( $value );
		$value = htmlspecialchars( $value );
	}

	return $validate;

}

?>
