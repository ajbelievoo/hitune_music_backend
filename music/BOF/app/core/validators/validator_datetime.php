<?php

if ( !defined( "root" ) ) die;

function validator_datetime( &$value, $args, $nest ){

	$strict = true;
	extract( $args );

	if ( $strict )
	return preg_match( "/^(\d{4})-(\d{2})-(\d{2})?$/", $value );

	$validate = preg_match( "/^(\d{4}|\d{3})[-\/](0[1-9]|1[0-2])[-\/](0[1-9]|[12][0-9]|3[01])(?: (\d{2}:\d{2}:\d{2}))?$/", $value );

	if ( !$validate )
	$value = false;

	else {
		$value = explode( " ", $value );
		$value = reset( $value );
		$value = str_replace( "/", "-", $value );
	}

	return $validate;

}

?>
