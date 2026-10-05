<?php

if ( !defined( "root" ) ) die;

function validator_domain( &$value, $args, $nest ){

	$check_dns = false;
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
		return false;

	// Presnted URL has a hostname ( domain ), but is it in valid format?
	if ( !(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $value )
            && preg_match("/^.{1,253}$/", $value )
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $value ) ) )
		return false;

	// Should we check DNS of domain?
	if ( $check_dns ? !checkdnsrr( $value, 'ANY' ) : false )
		return false;

	$value = strtolower( $value );

	return true;

}

?>
