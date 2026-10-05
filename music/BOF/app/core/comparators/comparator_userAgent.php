<?php

function comparator_userAgent( $args, $endpoint, $nest ){

  $device_type = null;
  extract( $args );

  $valid = true;
  $userAgent_data = $nest->loader->request->get_userAgent();

  if ( $device_type ? is_array( $device_type ) : false ){
    $valid_device_type = $nest->validate( $userAgent_data["data"]["device"]["type"], $device_type[0], $device_type[1] );
    if ( !$valid_device_type ) $valid = false;
  }

  return $valid;

}

?>
