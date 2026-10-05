<?php

function comparator_user( $args, $endpoint, $nest ){

  $is_logged = null;
  $valid_groups = null;
  extract( $args );

  $compare = true;

  if ( $is_logged !== null ){
    $is_logged = $is_logged ? true : false;
    $_is_logged = bof()->user->get()->logged;
    if ( $_is_logged !== $is_logged )
    $compare = false;
  }

  if ( $compare && $valid_groups ){
    if ( !array_intersect( $valid_groups, bof()->user->get()->groups ) )
    $compare = false;
  }

  return $compare;

}

?>
