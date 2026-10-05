<?php

if (!defined("root")) die;

function validator_web3_tx(&$value, $args, $nest)
{

	$validate = false;

	if (preg_match("/^(0x)?([A-Fa-f0-9]{64})$/", $value, $matches)) {
		$value = isset($matches[2]) ? $matches[2] : $matches[1];
		$value = strtolower( $value );
		$validate = true;
	}

	return $validate;
}
