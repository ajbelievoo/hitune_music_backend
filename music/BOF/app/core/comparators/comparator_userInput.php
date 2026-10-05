<?php

function comparator_userInput( $args, $endpoint, $nest ){

  $type = null;
  $name = null;
  $validator = null;
  $validator_args = [];
  extract( $args );

  if ( empty( $type ) ) return false;
  if ( empty( $name ) ) return false;

  $userInput = $nest->user_input( $type, $name, $validator, $validator_args );

  return $userInput ? true : false;

}

?>
