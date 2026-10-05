<?php

if ( !defined( "root" ) ) die;

function validator_url( &$value, $args, $nest ){

	$accept_port     = false;
	$accept_auth     = false;
	$remove_fragment = false;
	$remove_query    = false;
	$check_dns       = false;
	$default_scheme  = "http";
	$default_scheme_add = false;
	$acceptable_schemes = [ "http", "https" ];
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
	return false;

	$url_presented = $value;
	$url_parsed = parse_url( $url_presented );

	// Presented URL has no scheme
	if ( empty( $url_parsed["scheme"] ) ){

		// Scheme is required
		if ( !$default_scheme )
		return false;

		$url_presented = "http://{$url_presented}";
		if ( $default_scheme_add ) $value = $url_presented;
		$url_parsed = parse_url( $url_presented );

	}

	// Presented URL has a scheme BUT it's not an acceptable one so it's a no go
	if( $url_parsed ? !in_array( $url_parsed["scheme"], $acceptable_schemes, true ) : false )
		return false;

	// Presented URL has no host
	if ( empty( $url_parsed["host"] ) )
		return false;

	// Presnted URL has a hostname ( domain ), but is it in valid format?
	if ( !$nest->validate( $url_parsed["host"], "domain", [ "check_dns" => $check_dns ] ) )
		return false;

	// Presented URL has a port and we don't accept ports
	if ( !empty( $url_parsed["port"] ) && !$accept_port )
		return false;

	// Presented URL has a username OR password and we don't accept that!
	if ( ( !empty( $url_parsed["user"] ) || !empty( $url_parsed["pass"] ) ) && !$accept_auth )
		return false;

	// Recreate url string
	$url_string = "{$url_parsed["scheme"]}://" .
		( isset( $url_parsed["user"] ) && isset( $url_parsed["pass"] ) ? "{$url_parsed["user"]}:{$url_parsed["pass"]}@" : "" ) .
		$url_parsed["host"] .
		( isset( $url_parsed["port"] ) ? ":{$url_parsed["port"]}" : "" ) .
		( isset( $url_parsed["path"] ) ? $url_parsed["path"] : "/" ) .
		( isset( $url_parsed["query"] ) && !$remove_query ? "?{$url_parsed["query"]}" : "" ) .
		( isset( $url_parsed["fragment"] ) && !$remove_fragment ? "#{$url_parsed["fragment"]}" : "" );

	return true;

}

?>
