<?php

if ( !defined( "root" ) ) die;

function validator_file( &$value, $args, $nest ){

	$min_size = null;
	$max_size = null;
	$acceptable_extensions = [];
	$acceptable_types = [];
	extract( $args );

	// Validate && sanitize file array
	if ( !$nest->validate( $value["name"], "string" ) )
	return false;

	if ( !$nest->validate( $value["type"], "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\/\-_.+]", "empty()" ] ) )
	return false;

	if ( !$nest->validate( $value["tmp_name"], "string" ) )
	return false;

	if ( !$nest->validate( $value["size"], "int" ) )
	return false;

	if ( !empty( $value["error"] ) )
	return false;

	// Size check
	if ( $min_size ? $min_size > $value["size"] : false )
	return false;

	if ( $max_size ? $value["size"] > $max_size : false )
	return false;

	// Extension check
	$value["extension"] = pathinfo( $value["name"], PATHINFO_EXTENSION );
	if ( $acceptable_extensions && is_array( $acceptable_extensions ) ? !in_array( $value["extension"], $acceptable_extensions, true ) : false )
	return false;

	// Type check
	if ( $acceptable_types && is_array( $acceptable_types ) ? !in_array( $value["type"], $acceptable_types, true ) : false )
	return false;

	return true;

}

?>
