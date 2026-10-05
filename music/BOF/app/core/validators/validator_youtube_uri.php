<?php

if ( !defined( "root" ) ) die;

function validator_youtube_uri( &$value, $args, $nest ){

	$min_length = 4;
	$max_length = 40;
	extract( $args );

	$validate = true;

	$is_valid_url = bof()->nest->validate( $value, "url", array(
		"default_scheme" => false,
		"acceptable_schemes" => [ "https" ]
	) );

	// URL to ID
	if ( $is_valid_url ){

		$parse_url = parse_url( $value );
		if ( empty( $parse_url["query"] ) )
		throw new bofException( "invalid_youtube_url" );

		parse_str( $parse_url["query"], $queries );
		if ( empty( $queries ) ? true : empty( $queries["v"] ) )
		throw new bofException( "invalid_youtube_url" );

		$value = $queries["v"];

	}

	// Validate ID
	if ( !preg_match( '/^[a-zA-Z0-9_-]{11}$/' , $value ) )
	$validate = false;

	return $validate;

}

?>
