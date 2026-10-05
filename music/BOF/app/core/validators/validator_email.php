<?php

if ( !defined( "root" ) ) die;

function validator_email( &$value, $args, $nest ){

	$check_dns = false;
	extract( $args );

	// Check value type
	if ( !is_string( $value ) )
		return false;

	$validate = filter_var(
		$value,
		FILTER_VALIDATE_EMAIL
	);

	// Check domain
	if ( $validate ){

		$domain = substr( $value, strpos( $value, "@" ) + 1 );
		if ( !$nest->validate( $domain, "domain", [ "check_dns" => $check_dns ] ) )
			$validate = false;

	}

	$value = strtolower( trim( filter_var(
		$value,
		FILTER_SANITIZE_EMAIL
	) ) );

	return $validate;

}

?>
