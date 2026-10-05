<?php

if ( !defined( "root" ) ) die;

function validator_bofClient_object( &$object_name, $args, $nest ){

	$loader = bof();
	$has_button = false;
	$has_bofClinet = false;
	extract( $args );

	$objects = $loader->bofClient->_get_objects();
	if ( !in_array( $object_name, array_keys( $objects ), true ) )
	return false;

	$the_object = $loader->object->__get( $object_name );
	if( !$the_object )
	return false;

	if ( $has_bofClinet || $has_button ){
		if ( !$the_object->method_exists( "bof_client" ) )
		return false;
		$bofClient = $the_object->bof_client();
	}

	if ( $has_button ? empty( $bofClient["buttons"][ $has_button ] ) : false )
	return false;

	return true;

}

?>
