<?php

function comparator_platform( $args, $endpoint, $nest ){

  $given_platform = bof()->nest->user_input( "http_header", "x-bof-platform", "in_array", array(
    "values" => bof()->object->core_setting->get("supported_platforms")
  ) );

  return $given_platform ? true : false;

}

?>
