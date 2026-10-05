<?php

if ( !defined( "root" ) ) die;

function validator_timestamp( &$value, $args, $nest ){

	$validate = preg_match( "/^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}):(\d{2}))?$/", $value );

	if ( !$validate )
	$value = null;

	return $validate;

}

?>
