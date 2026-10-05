<?php

function comparator_userIP( $args, $endpoint, $nest ){

  $equal = null;
  extract( $args );

  $valid = true;

  if ( $equal ){
    $userIP_data = bof()->request->get_userIP();
    $userIP = $userIP_data["string"];
    if ( $equal != $userIP )
    $valid = false;
  }

  return $valid;

}

?>
